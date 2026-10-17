<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * ABA PayWay integration (QR API + Check Transaction API).
 *
 * Docs:
 *  - QR API:            https://developer.payway.com.kh/qr-api-14530840e0
 *  - Check transaction: https://developer.payway.com.kh/check-transaction-14530826e0
 *
 * This class only talks to PayWay. Deciding what a payment means for a course
 * and a student is PayWayVerifier's job.
 */
class PayWayService
{
    /**
     * The exact order the QR request's fields are concatenated in before being
     * signed. PayWay recomputes the same HMAC on its side, so a single field out
     * of place -- or a field left out -- comes back as error code 1 ("invalid
     * hash") with nothing to say which one it was.
     *
     * Every field counts, including the ones we send empty: an empty field
     * contributes the empty string, not nothing.
     */
    private const HASH_ORDER = [
        'req_time',
        'merchant_id',
        'tran_id',
        'amount',
        'items',
        'first_name',
        'last_name',
        'email',
        'phone',
        'purchase_type',
        'payment_option',
        'callback_url',
        'return_deeplink',
        'currency',
        'custom_fields',
        'return_params',
        'payout',
        'lifetime',
        'qr_image_template',
    ];

    /** PayWay refuses a lifetime under 3 minutes. */
    public const MIN_LIFETIME_MINUTES = 3;

    /** PayWay's tran_id column. Our invoice_no has to fit in it. */
    public const MAX_TRAN_ID_LENGTH = 20;

    public function isConfigured(): bool
    {
        return $this->configProblems() === [];
    }

    /**
     * Everything missing or malformed in the .env settings, in plain words.
     *
     * Written for the log and for "php artisan payway:test" -- never shown to a
     * customer, because it names .env keys.
     *
     * @return list<string>
     */
    public function configProblems(): array
    {
        $problems = [];

        if (blank(config('services.payway.merchant_id'))) {
            $problems[] = 'PAYWAY_MERCHANT_ID is empty.';
        }

        if (blank(config('services.payway.api_key'))) {
            $problems[] = 'PAYWAY_API_KEY is empty (ABA calls it the "Public Key").';
        }

        $baseUrl = trim((string) config('services.payway.base_url'));

        if ($baseUrl === '') {
            $problems[] = 'PAYWAY_BASE_URL is empty.';
        } elseif (! preg_match('#^https://#i', $baseUrl)) {
            $problems[] = 'PAYWAY_BASE_URL must start with https:// (got "'.$baseUrl.'").';
        }

        $callback = $this->callbackUrl();

        if ($callback === '') {
            $problems[] = 'PAYWAY_CALLBACK_URL is empty and APP_URL gave no usable address.';
        } elseif (! preg_match('#^https?://#i', $callback)) {
            $problems[] = 'PAYWAY_CALLBACK_URL must be a full URL (got "'.$callback.'").';
        } elseif ($this->callbackPointsAtGateway($callback)) {
            // Pasting one of PayWay's own API endpoints here is an easy mistake,
            // and a silent one: QR generation still succeeds, so the self-test
            // passes, but PayWay posts the confirmation to itself and this app
            // is never told. Every payment then depends on the polling fallback,
            // and a customer who closes the page stays locked out.
            $problems[] = 'PAYWAY_CALLBACK_URL points at PayWay ("'.$callback.'"), but it must point at THIS app\'s '
                .'own POST /payway/callback route — the address PayWay should notify. See PAYMENTS.md section 3.';
        }

        if (! in_array($this->currency(), ['USD', 'KHR'], true)) {
            $problems[] = 'PAYWAY_CURRENCY must be USD or KHR (got "'.$this->currency().'").';
        }

        return $problems;
    }

    /**
     * Reasons the settings are usable but probably not what you meant.
     *
     * @return list<string>
     */
    public function configWarnings(): array
    {
        $warnings = [];
        $callback = $this->callbackUrl();

        // PayWay calls us from the internet. A private address cannot be reached,
        // so the payment would only ever be confirmed by the polling fallback.
        if (preg_match('#^https?://(localhost|127\.|0\.0\.0\.0|192\.168\.|10\.|\[::1\])#i', $callback)) {
            $warnings[] = 'PAYWAY_CALLBACK_URL points at a local address ('.$callback.'). '
                .'PayWay cannot reach it, so callbacks will never arrive. Use a cloudflared/ngrok URL.';
        } elseif (! preg_match('#^https://#i', $callback)) {
            $warnings[] = 'PAYWAY_CALLBACK_URL is http, not https. PayWay expects https in production.';
        }

        if (! $this->isSandbox() && app()->environment('local')) {
            $warnings[] = 'PAYWAY_BASE_URL is the LIVE gateway while APP_ENV=local. Real money will move.';
        }

        // The route is registered at exactly this path, so a callback URL ending
        // anywhere else reaches a 404 and the confirmation is thrown away.
        if ($callback !== '' && ! $this->callbackPointsAtGateway($callback)
            && ! preg_match('#/payway/callback/?$#i', (string) parse_url($callback, PHP_URL_PATH))) {
            $warnings[] = 'PAYWAY_CALLBACK_URL does not end in /payway/callback ('.$callback.'). '
                .'That is the only path this app answers callbacks on.';
        }

        return $warnings;
    }

    /**
     * Is this "callback" URL actually one of PayWay's own addresses?
     *
     * Compares against the configured gateway host and PayWay's domain, so both
     * the sandbox and live hosts are caught however base_url is set.
     */
    private function callbackPointsAtGateway(string $callback): bool
    {
        $callbackHost = strtolower((string) parse_url($callback, PHP_URL_HOST));

        if ($callbackHost === '') {
            return false;
        }

        $gatewayHost = strtolower((string) parse_url($this->baseUrl(), PHP_URL_HOST));

        return $callbackHost === $gatewayHost
            || (bool) preg_match('/(^|\.)payway\.com\.kh$/i', $callbackHost);
    }

    public function isSandbox(): bool
    {
        return str_contains($this->baseUrl(), 'sandbox');
    }

    /** Scheme + host (+ port) only, so a pasted path or trailing slash cannot break the endpoints. */
    public function baseUrl(): string
    {
        $url = trim((string) config('services.payway.base_url'));
        $parts = parse_url($url);

        if (! empty($parts['scheme']) && ! empty($parts['host'])) {
            return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        }

        return rtrim($url, '/');
    }

    public function currency(): string
    {
        return strtoupper(trim((string) config('services.payway.currency', 'USD')));
    }

    public function merchantId(): string
    {
        return trim((string) config('services.payway.merchant_id'));
    }

    /**
     * Where PayWay should POST when a payment lands.
     *
     * Configured explicitly because the address PayWay needs is usually not the
     * one the app thinks it is running on: on XAMPP the app is 127.0.0.1 while
     * the callback has to arrive through a public tunnel.
     */
    public function callbackUrl(): string
    {
        $configured = trim((string) config('services.payway.callback_url'));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        try {
            return route('payway.callback');
        } catch (Throwable) {
            return '';
        }
    }

    public function lifetimeMinutes(): int
    {
        return max(
            self::MIN_LIFETIME_MINUTES,
            (int) config('services.payway.qr_lifetime', 10)
        );
    }

    /**
     * Format an amount the way PayWay wants it, as a string.
     *
     * A string, not a float, so the value we hash is byte-for-byte the value we
     * send: number_format gives "35.00" where json_encode(35.0) would give "35"
     * and the hash would no longer match. KHR has no minor unit.
     */
    public function formatAmount(float $amount): string
    {
        return $this->currency() === 'KHR'
            ? (string) (int) round($amount)
            : number_format($amount, 2, '.', '');
    }

    /**
     * Ask PayWay to create a KHQR for this transaction.
     *
     * @param  string  $tranId  Our invoice_no. Must be unique and <= 20 chars.
     * @param  array{name?: string, email?: string, phone?: string}  $payer
     * @param  array<int, array{name: string, quantity: int, price: string|float}>  $items
     * @return array{qr: string, qr_image: ?string, deeplink: ?string, amount: string, raw: array}
     */
    public function generateQr(float $amount, string $tranId, array $payer = [], array $items = []): array
    {
        $apiKey = $this->apiKey();

        if ($tranId === '' || strlen($tranId) > self::MAX_TRAN_ID_LENGTH) {
            throw new RuntimeException('PayWay tran_id must be 1-'.self::MAX_TRAN_ID_LENGTH.' characters, got '.strlen($tranId).'.');
        }

        // PayWay only accepts [A-Za-z0-9] here; a stray dash is error 96.
        if (! preg_match('/^[A-Za-z0-9]+$/', $tranId)) {
            throw new RuntimeException('PayWay tran_id must be letters and digits only, got "'.$tranId.'".');
        }

        [$firstName, $lastName] = $this->splitName((string) ($payer['name'] ?? ''));

        $fields = [
            // PayWay compares req_time against its own clock in UTC and rejects
            // anything too far out, so this must not use the app timezone.
            'req_time' => now('UTC')->format('YmdHis'),
            'merchant_id' => $this->merchantId(),
            'tran_id' => $tranId,
            'amount' => $this->formatAmount($amount),
            'items' => $items === [] ? '' : base64_encode((string) json_encode(array_values($items))),
            'first_name' => $this->clean($firstName, 50),
            'last_name' => $this->clean($lastName, 50),
            'email' => $this->clean((string) ($payer['email'] ?? ''), 150),
            'phone' => $this->clean((string) ($payer['phone'] ?? ''), 20),
            'purchase_type' => 'purchase',
            'payment_option' => 'abapay_khqr',
            // Base64 of the URL, per PayWay's spec -- not the URL itself.
            'callback_url' => base64_encode($this->callbackUrl()),
            'return_deeplink' => '',
            'currency' => $this->currency(),
            'custom_fields' => '',
            'return_params' => '',
            'payout' => '',
            'lifetime' => (string) $this->lifetimeMinutes(),
            'qr_image_template' => 'template3_color',
        ];

        $fields['hash'] = $this->sign(
            array_map(fn (string $key) => $fields[$key], self::HASH_ORDER),
            $apiKey
        );

        // The optional fields we left blank are part of the hash as empty
        // strings (above) but are not posted: PayWay rejects some of them when
        // they arrive empty. Anything we actually filled in is always sent.
        $body = array_filter($fields, fn ($value) => $value !== '');

        $json = $this->post('/api/payment-gateway/v1/payments/generate-qr', $body);

        $code = (string) ($json['status']['code'] ?? '');

        if ($code !== '0') {
            throw new RuntimeException(sprintf(
                'PayWay QR error [%s]: %s',
                $code === '' ? '?' : $code,
                $this->explainQrError($code, (string) ($json['status']['message'] ?? 'Unknown error'))
            ));
        }

        // The QR payload can arrive at the top level or nested under data,
        // depending on the endpoint version.
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];
        $qr = (string) ($json['qrString'] ?? $data['qrString'] ?? '');

        if ($qr === '') {
            throw new RuntimeException('PayWay accepted the request but returned no QR content.');
        }

        return [
            'qr' => $qr,
            'qr_image' => $this->nullableString($json['qrImage'] ?? $data['qrImage'] ?? null),
            'deeplink' => $this->nullableString($json['abapay_deeplink'] ?? $data['abapay_deeplink'] ?? null),
            'amount' => $fields['amount'],
            'raw' => $json,
        ];
    }

    /**
     * Ask PayWay what really happened to a transaction.
     *
     * This is the only statement about a payment that is trusted. A callback is
     * just a nudge to come and ask this question.
     *
     * @return array{approved: bool, pending: bool, final: bool, status: string, amount: ?float, currency: ?string, data: ?array, raw: array}
     */
    public function checkTransaction(string $tranId): array
    {
        $apiKey = $this->apiKey();
        $merchantId = $this->merchantId();
        $reqTime = now('UTC')->format('YmdHis');

        $json = $this->post('/api/payment-gateway/v1/payments/check-transaction-2', [
            'req_time' => $reqTime,
            'merchant_id' => $merchantId,
            'tran_id' => $tranId,
            'hash' => $this->sign([$reqTime, $merchantId, $tranId], $apiKey),
        ]);

        $data = is_array($json['data'] ?? null) ? $json['data'] : null;

        if ($data === null) {
            // PayWay has not heard of this tran_id (yet). Unpaid, not refused.
            return [
                'approved' => false,
                'pending' => true,
                'final' => false,
                'status' => (string) ($json['status']['message'] ?? 'NOT_FOUND'),
                'amount' => null,
                'currency' => null,
                'data' => null,
                'raw' => $json,
            ];
        }

        $status = strtoupper(trim((string) ($data['payment_status'] ?? '')));

        return [
            // The one string that means money moved. Everything else is not paid.
            'approved' => $status === 'APPROVED',
            'pending' => in_array($status, ['PENDING', ''], true),
            // Reached an end state that will never become APPROVED.
            'final' => in_array($status, ['DECLINED', 'CANCELLED', 'REFUNDED', 'FAILED'], true),
            'status' => $status,
            'amount' => isset($data['payment_amount']) && is_numeric($data['payment_amount'])
                ? (float) $data['payment_amount']
                : (isset($data['total_amount']) && is_numeric($data['total_amount']) ? (float) $data['total_amount'] : null),
            'currency' => $this->nullableString($data['payment_currency'] ?? $data['currency'] ?? null),
            'data' => $data,
            'raw' => $json,
        ];
    }

    /* ------------------------------------------------------------------ */

    private function apiKey(): string
    {
        $apiKey = trim((string) config('services.payway.api_key'));

        if ($apiKey === '' || $this->merchantId() === '') {
            throw new RuntimeException('Missing PAYWAY_MERCHANT_ID or PAYWAY_API_KEY in .env');
        }

        return $apiKey;
    }

    /** base64(hmac_sha512(concatenated fields, api key)) -- PayWay's signature everywhere. */
    private function sign(array $parts, string $apiKey): string
    {
        return base64_encode(hash_hmac('sha512', implode('', $parts), $apiKey, true));
    }

    private function post(string $path, array $body): array
    {
        $url = $this->baseUrl().$path;

        try {
            $response = Http::timeout((int) config('services.payway.timeout', 15))
                ->acceptJson()
                ->asJson()
                ->post($url, $body);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not reach PayWay: '.$e->getMessage(), 0, $e);
        }

        $json = $response->json();

        if (! is_array($json)) {
            // Usually an HTML error page: a non-whitelisted IP, or a typo in the
            // base URL. Keep a short excerpt so the log says which.
            throw new RuntimeException(
                'PayWay HTTP '.$response->status().' at '.$url.' — '
                .mb_substr(trim(strip_tags($response->body())), 0, 300)
            );
        }

        return $json;
    }

    /**
     * "Piseth Yoeurn" -> ["Piseth", "Yoeurn"]. A single word becomes the first
     * name with the last name left empty, which PayWay accepts.
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return ['', ''];
        }

        $first = array_shift($parts);

        return [$first, implode(' ', $parts)];
    }

    /**
     * PayWay's name fields reject most punctuation, and a rejected field comes
     * back as a bare "invalid data" with no hint which one. These are cosmetic
     * on the payer's receipt, so strip rather than fail the payment.
     */
    private function clean(string $value, int $max): string
    {
        if (str_contains($value, '@')) {
            // An email address: keep it whole, only trim.
            return mb_substr(trim($value), 0, $max);
        }

        $value = preg_replace('/[^\p{L}\p{N} .\-+_]/u', '', trim($value)) ?? '';

        return mb_substr(trim($value), 0, $max);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * PayWay's status codes are numbers with no explanation attached. These are
     * the ones that actually come up while setting the integration up, turned
     * into the thing you have to go and do about them.
     */
    private function explainQrError(string $code, string $message): string
    {
        return match ($code) {
            '1' => 'Invalid hash — check PAYWAY_API_KEY (ABA\'s "Public Key") has no extra spaces or newlines.',
            '2' => 'Invalid transaction ID format.',
            '3' => 'Transaction ID already used — tran_id must be unique.',
            '4' => 'Invalid amount for this currency.',
            '6', '102' => 'Your server domain/IP is not whitelisted. Ask PayWay to whitelist it.',
            '11' => 'Invalid or expired req_time — check the server clock is correct in UTC.',
            '23' => 'KHQR is not enabled on this merchant profile — ask PayWay to enable it.',
            '96' => 'Invalid merchant data — check PAYWAY_MERCHANT_ID.',
            '403' => 'Duplicated transaction ID.',
            default => $message,
        };
    }
}
