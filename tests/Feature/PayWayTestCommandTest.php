<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * "php artisan payway:test" is the command you run after every .env change, so
 * it has to be right about what it reports. In particular it must fail loudly
 * rather than quietly passing on a broken configuration.
 */
class PayWayTestCommandTest extends TestCase
{
    use RefreshDatabase;

    private function configure(array $overrides = []): void
    {
        config()->set('services.payway', array_merge([
            'base_url' => 'https://checkout-sandbox.payway.com.kh',
            'merchant_id' => 'test_merchant',
            'api_key' => 'test_api_key',
            'callback_url' => 'https://tunnel.example.com/payway/callback',
            'currency' => 'USD',
            'qr_lifetime' => 10,
            'timeout' => 15,
        ], $overrides));
    }

    public function test_it_passes_when_payway_answers_both_endpoints(): void
    {
        $this->configure();

        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => ['code' => '0'],
                'qrString' => '00020101021230560016abaakhppxxx@abaa',
                'qrImage' => base64_encode('fake-png'),
                'abapay_deeplink' => 'https://link.payway.com.kh/aba?id=TEST',
            ]),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);

        $this->artisan('payway:test')
            ->expectsOutputToContain('[OK] .env settings look complete')
            ->expectsOutputToContain('[OK] PayWay accepted the request and returned a QR')
            ->expectsOutputToContain('All checks passed.')
            ->assertSuccessful();
    }

    public function test_it_fails_on_missing_credentials_without_calling_payway(): void
    {
        $this->configure(['api_key' => '']);
        Http::fake();

        $this->artisan('payway:test')
            ->expectsOutputToContain('PAYWAY_API_KEY is empty')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_it_fails_when_payway_rejects_the_hash(): void
    {
        $this->configure();

        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => ['code' => '1', 'message' => 'invalid hash'],
            ]),
        ]);

        $this->artisan('payway:test')
            // The operator-facing hint, which is the whole point of the command.
            ->expectsOutputToContain('PAYWAY_API_KEY')
            ->assertFailed();
    }

    /**
     * Generating a QR is not enough: if check-transaction cannot be reached, no
     * payment could ever be CONFIRMED, and that must not read as a pass.
     */
    public function test_it_fails_when_only_check_transaction_is_broken(): void
    {
        $this->configure();

        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => ['code' => '0'],
                'qrString' => 'qr-payload',
                'qrImage' => base64_encode('fake-png'),
            ]),
            '*/check-transaction-2' => Http::response('<html>403 Forbidden</html>', 403),
        ]);

        $this->artisan('payway:test')
            ->expectsOutputToContain('payments could never be CONFIRMED')
            ->assertFailed();
    }

    public function test_it_warns_when_the_callback_url_is_local(): void
    {
        // The single most common local-setup mistake: PayWay cannot reach this.
        $this->configure(['callback_url' => 'http://127.0.0.1:8000/payway/callback']);

        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => ['code' => '0'],
                'qrString' => 'qr-payload',
                'qrImage' => base64_encode('fake-png'),
            ]),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);

        $this->artisan('payway:test')
            ->expectsOutputToContain('PayWay cannot reach it')
            ->assertSuccessful();
    }

    public function test_the_test_transaction_id_fits_payways_limits(): void
    {
        $this->configure();

        Http::fake([
            '*/generate-qr' => Http::response([
                'status' => ['code' => '0'],
                'qrString' => 'qr-payload',
                'qrImage' => base64_encode('fake-png'),
            ]),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '0'],
                'data' => ['payment_status' => 'PENDING'],
            ]),
        ]);

        $this->artisan('payway:test')->assertSuccessful();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'generate-qr')) {
                return true;
            }

            $tranId = $request->data()['tran_id'];

            $this->assertLessThanOrEqual(20, strlen($tranId));
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $tranId);

            return true;
        });
    }
}
