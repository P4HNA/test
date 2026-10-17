<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Which language to show.
 *
 * 1. A choice made with the EN/KM switch always wins: this session, or the
 *    cookie that remembers it for a year (LocaleController).
 * 2. With no choice yet, pick a first language: Khmer for visitors in
 *    Cambodia, English for everyone else. Signals, in order, all without
 *    any location lookup or third-party service:
 *      a. the country header a CDN in front of the site adds (Cloudflare's
 *         CF-IPCountry), when there is one;
 *      b. the browser's own time zone, which a small script reports once in
 *         the pd_tz cookie (partials/_head-shared): Asia/Phnom_Penh is Cambodia;
 *      c. the browser's preferred language (Khmer first means Khmer).
 *    Nothing is stored for this: it is worked out again, cheaply, until the
 *    visitor picks a language themselves.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'km'];

    /** Remembers a choice made with the switch. */
    public const CHOICE_COOKIE = 'pd_locale';

    /** The browser's time zone name, e.g. "Asia/Phnom_Penh". Set by JavaScript. */
    public const ZONE_COOKIE = 'pd_tz';

    public const CAMBODIA_ZONE = 'Asia/Phnom_Penh';

    public function handle(Request $request, Closure $next): Response
    {
        [$locale, $source] = $this->resolve($request);

        app()->setLocale($locale);
        // "chosen" or "auto": the time-zone script only acts on an automatic pick.
        $request->attributes->set('locale_source', $source);

        return $next($request);
    }

    /** @return array{0: string, 1: 'chosen'|'auto'} */
    private function resolve(Request $request): array
    {
        $chosen = $request->hasSession() ? $request->session()->get('locale') : null;
        $chosen ??= $request->cookie(self::CHOICE_COOKIE);

        if (in_array($chosen, self::SUPPORTED, true)) {
            return [$chosen, 'chosen'];
        }

        return [$this->looksLikeCambodia($request) ? 'km' : 'en', 'auto'];
    }

    private function looksLikeCambodia(Request $request): bool
    {
        $country = strtoupper(trim((string) $request->headers->get(config('app.country_header', 'CF-IPCountry'), '')));

        // "XX" and "T1" are Cloudflare's "unknown" and "Tor": not an answer.
        if ($country !== '' && ! in_array($country, ['XX', 'T1'], true)) {
            return $country === 'KH';
        }

        $zone = (string) $request->cookie(self::ZONE_COOKIE, '');

        if ($zone !== '') {
            return $zone === self::CAMBODIA_ZONE;
        }

        return $request->getPreferredLanguage(self::SUPPORTED) === 'km';
    }
}
