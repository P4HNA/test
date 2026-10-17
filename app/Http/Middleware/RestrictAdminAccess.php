<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the admin area private to a list of IPs / CIDR ranges.
 *
 * - Fails closed: if ADMIN_ALLOWED_IPS is empty, nobody gets in.
 * - Fails closed again if the real caller cannot be identified (below).
 * - Answers 404 instead of 403, so outsiders cannot tell an admin area exists.
 * - Blocked attempts are written to the log so you can see who is probing.
 */
class RestrictAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('admin.allowed_ips', []);

        if ($allowed === []) {
            return $this->block($request, 'ADMIN_ALLOWED_IPS is empty');
        }

        // Put a reverse proxy in front of this app -- nginx, Apache, a
        // cloudflared/ngrok tunnel -- and the connection arrives from the proxy
        // itself, almost always 127.0.0.1. The visitor's real address is then
        // only in X-Forwarded-For, which Laravel reads just for proxies named
        // in TRUSTED_PROXIES.
        //
        // So a forwarded request from an untrusted proxy carries an address
        // that is not the caller's. Checking it against the allow-list would
        // compare the list to our own front door: with 127.0.0.1 on the list,
        // as it is by default, every visitor in the world would match. That is
        // the whole allow-list quietly turning into "allow everyone", with
        // nothing in the log to show for it.
        //
        // We cannot tell who this is, so we do not guess. Set TRUSTED_PROXIES
        // to your proxy's address and the header becomes readable, which is the
        // one fix -- see bootstrap/app.php.
        if ($this->isForwarded($request) && ! $request->isFromTrustedProxy()) {
            return $this->block($request, 'forwarded by an untrusted proxy, so the caller is unknown');
        }

        if (! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            return $this->block($request, 'not on the allow-list');
        }

        return $next($request);
    }

    /** Does this request claim to have come through a proxy? */
    private function isForwarded(Request $request): bool
    {
        return $request->headers->has('x-forwarded-for')
            || $request->headers->has('forwarded');
    }

    /**
     * 404 rather than 403, so a probe learns nothing, plus a log line that says
     * which of the three reasons it was.
     */
    private function block(Request $request, string $reason): never
    {
        Log::warning('Blocked admin access', [
            'ip' => $request->ip(),
            'path' => $request->path(),
            'reason' => $reason,
        ]);

        abort(404);
    }
}
