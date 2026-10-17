<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->useCaBundleForOutgoingHttp();
        $this->forceHttpsOnTunnels();
    }

    /**
     * When the site is opened through a tunnel (localtunnel, Cloudflare), the
     * browser sees https but PHP only sees plain http from the tunnel, so
     * Laravel would generate http:// links for CSS/JS and the phone would
     * block them as mixed content. Force https for tunnel hostnames only.
     */
    private function forceHttpsOnTunnels(): void
    {
        $host = request()->getHost();

        if (
            str_ends_with($host, '.loca.lt') ||
            str_ends_with($host, '.trycloudflare.com')
        ) {
            URL::forceScheme('https');
        }
    }

    /**
     * Windows PHP builds often ship without a CA certificate bundle, so every
     * HTTPS call (e.g. to the ABA PayWay API) fails with "cURL error 60" unless
     * php.ini sets curl.cainfo. Point Laravel's HTTP client at a bundle
     * instead, so the app works on any teammate's machine without editing
     * php.ini. Certificate checking stays ON -- this only says where the
     * trusted certificates are.
     */
    private function useCaBundleForOutgoingHttp(): void
    {
        if (filled(ini_get('curl.cainfo'))) {
            return; // php.ini already handles it
        }

        $bundle = collect([
            config('services.http.ca_bundle'),
            // Laragon ships one here...
            'C:\laragon\etc\ssl\cacert.pem',
            // ...and XAMPP here. Without this, generating a PayWay QR on XAMPP
            // fails with cURL error 60 and the customer is simply told that
            // payment is temporarily unavailable.
            'C:\xampp\apache\bin\curl-ca-bundle.crt',
            'C:\xampp\php\extras\ssl\cacert.pem',
        ])->first(fn ($path) => filled($path) && is_file($path));

        if ($bundle) {
            Http::globalOptions(['verify' => $bundle]);
        }
    }
}
