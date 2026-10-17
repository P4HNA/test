<?php

namespace App\Console\Commands;

use App\Services\PayWayService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * php artisan payway:test
 *
 * Checks every ABA PayWay setting and generates one real sandbox QR, without
 * anybody paying anything. Run it after any .env change.
 */
class PayWayTest extends Command
{
    protected $signature = 'payway:test {--keep : Leave the generated QR in place instead of reminding you it expires}';

    protected $description = 'Check the ABA PayWay settings and generate a test KHQR (no payment needed)';

    public function handle(PayWayService $payway): int
    {
        $this->newLine();
        $this->line('<options=bold>ABA PayWay self-test</>');
        $this->line('Gateway:  '.($payway->baseUrl() ?: '(empty)').($payway->isSandbox() ? ' <fg=green>[sandbox]</>' : ' <fg=yellow>[LIVE]</>'));
        $this->line('Merchant: '.($payway->merchantId() ?: '(empty)'));
        $this->line('Callback: '.($payway->callbackUrl() ?: '(empty)'));
        $this->line('Currency: '.$payway->currency());
        $this->line('QR life:  '.$payway->lifetimeMinutes().' min');
        $this->newLine();

        // 1. .env values
        if ($problems = $payway->configProblems()) {
            foreach ($problems as $problem) {
                $this->error('  [X] '.$problem);
            }

            $this->newLine();
            $this->warn('Fix the .env values above, run "php artisan config:clear", then run this test again.');

            return self::FAILURE;
        }

        $this->info('  [OK] .env settings look complete');

        foreach ($payway->configWarnings() as $warning) {
            $this->warn('  [!] '.$warning);
        }

        // 2. A real request to PayWay. This is the only way to know the API key
        //    hashes correctly and this machine's IP is whitelisted.
        $tranId = 'TEST'.now()->format('ymdHis').Str::upper(Str::random(2));
        $amount = 0.01;

        $this->newLine();
        $this->line('Generating a test QR (tran_id '.$tranId.', '.$payway->currency().' '.$payway->formatAmount($amount).')…');

        try {
            $result = $payway->generateQr(
                amount: $amount,
                tranId: $tranId,
                payer: ['name' => 'PayWay Test', 'email' => 'test@example.com'],
                items: [['name' => 'Self test', 'quantity' => 1, 'price' => $payway->formatAmount($amount)]],
            );
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('  [X] '.$e->getMessage());
            $this->newLine();
            $this->line('Common causes:');
            $this->line('  - PAYWAY_API_KEY wrong or has a trailing space/newline  -> error code 1');
            $this->line('  - This machine\'s public IP is not whitelisted by ABA   -> error code 6 / 102');
            $this->line('  - KHQR not enabled on the merchant profile             -> error code 23');
            $this->line('  - PAYWAY_MERCHANT_ID wrong                             -> error code 96');

            return self::FAILURE;
        }

        $this->info('  [OK] PayWay accepted the request and returned a QR');
        $this->line('       qrString:  '.Str::limit($result['qr'], 48));
        $this->line('       qrImage:   '.($result['qr_image'] ? strlen($result['qr_image']).' bytes of base64 PNG' : '<fg=yellow>none returned</>'));
        $this->line('       deeplink:  '.($result['deeplink'] ?: '<fg=yellow>none returned</>'));

        // 3. Check Transaction against the id we just created. An unpaid
        //    transaction is expected to come back PENDING or not-found -- what
        //    matters is that the endpoint answers us at all, because this is the
        //    call every real payment is confirmed by.
        $this->newLine();
        $this->line('Asking check-transaction-2 about it…');

        try {
            $check = $payway->checkTransaction($tranId);
        } catch (Throwable $e) {
            $this->error('  [X] '.$e->getMessage());
            $this->newLine();
            $this->warn('QR generation works but payments could never be CONFIRMED. Fix this before taking money.');

            return self::FAILURE;
        }

        $this->info('  [OK] check-transaction-2 answered: '.($check['status'] ?: '(no status)'));

        if ($check['approved']) {
            $this->warn('  [!] It reports APPROVED for a QR nobody paid. Check PAYWAY_MERCHANT_ID is yours.');
        }

        $this->newLine();
        $this->line('<options=bold;fg=green>All checks passed.</>');

        if (! $this->option('keep')) {
            $this->line('The test QR expires on its own in '.$payway->lifetimeMinutes().' minutes. Nothing was charged.');
        }

        // 4. The callback is the whole point of this integration, and it is the
        //    one thing this command cannot verify by itself.
        $this->newLine();
        $this->line('<options=bold>Still to verify by hand:</> that PayWay can REACH your callback.');
        $this->line('  '.$payway->callbackUrl());
        $this->line('Open that URL from your phone (off wifi). A 405 means it is reachable — good.');
        $this->line('See PAYMENTS.md section 3 for the cloudflared/ngrok recipe.');
        $this->newLine();

        return self::SUCCESS;
    }
}
