<?php

namespace Tests\Feature;

use App\Http\Middleware\RestrictAdminAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * The admin allow-list has to survive a reverse proxy.
 *
 * ADMIN_ALLOWED_IPS is checked against $request->ip(). Put nginx, Apache or a
 * tunnel in front of the app -- which ADMIN_PANEL.md and PAYMENTS.md both tell
 * you to do for real use -- and that address becomes the PROXY's, which on the
 * same host is 127.0.0.1. Since 127.0.0.1 is the default entry in the list, the
 * list then matches every request on earth and the lock is gone, silently: the
 * panel answers normally and nothing is written to the log.
 *
 * This was found by running the app behind a cloudflared tunnel: /admin
 * answered 302 to the login page from the public internet instead of 404, and
 * the "Blocked admin access" counter never moved.
 *
 * So: a forwarded request is refused unless the proxy that forwarded it is
 * named in TRUSTED_PROXIES, and once it is named, the allow-list is applied to
 * the real caller rather than to the proxy.
 */
class AdminNetworkLockTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.allowed_ips', ['127.0.0.1', '::1']);
    }

    public function test_a_direct_request_from_an_allowed_address_still_gets_in(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin', ['REMOTE_ADDR' => '127.0.0.1'])
            ->assertOk();
    }

    public function test_a_direct_request_from_anywhere_else_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get('/admin')
            ->assertNotFound();
    }

    /**
     * The bug. The connection is from the proxy on 127.0.0.1 -- an address on
     * the list -- but the caller behind it is a stranger.
     */
    public function test_a_forwarded_request_is_refused_when_no_proxy_is_trusted(): void
    {
        $this->actingAs($this->admin())
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin', ['X-Forwarded-For' => '203.0.113.9'])
            ->assertNotFound();
    }

    /** Spoofing the header on a direct connection must not open anything either. */
    public function test_a_forged_forwarding_header_does_not_let_a_stranger_in(): void
    {
        $this->actingAs($this->admin())
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get('/admin', ['X-Forwarded-For' => '127.0.0.1'])
            ->assertNotFound();
    }

    /** RFC 7239's header does the same job, so it is refused the same way. */
    public function test_the_rfc_forwarded_header_is_treated_the_same(): void
    {
        $this->actingAs($this->admin())
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin', ['Forwarded' => 'for=203.0.113.9'])
            ->assertNotFound();
    }

    /**
     * With the proxy trusted, the header is read and the allow-list is applied
     * to the person who actually made the request.
     *
     * Driven straight at the middleware rather than through $this->get(),
     * because Laravel's own TrustProxies middleware runs on every test request
     * and resets the trusted list from config, undoing anything set here first.
     */
    public function test_a_trusted_proxy_lets_the_real_address_be_judged(): void
    {
        // Forwarded from an address that is not on the list: still refused.
        $this->assertSame(404, $this->throughProxy('203.0.113.9')->getStatusCode());

        // Forwarded from an allowed address: let through.
        $this->assertSame('reached', $this->throughProxy('127.0.0.1')->getContent());

        Request::setTrustedProxies([], 0);
    }

    /**
     * One request arriving from a trusted proxy on 127.0.0.1, forwarded on
     * behalf of $client. Returns what the middleware did with it.
     */
    private function throughProxy(string $client): \Symfony\Component\HttpFoundation\Response
    {
        Request::setTrustedProxies(
            ['127.0.0.1'],
            Request::HEADER_X_FORWARDED_FOR
        );

        $request = Request::create('/admin', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => $client,
        ]);

        try {
            return (new RestrictAdminAccess)->handle(
                $request,
                fn () => new Response('reached')
            );
        } catch (HttpException $e) {
            return new Response('', $e->getStatusCode());
        }
    }

    /**
     * The lock runs before auth, so a stranger learns nothing.
     *
     * Laravel sorts route middleware by its own priority list, and Authenticate
     * is on it. Ours was not, so auth went first and an unauthenticated probe
     * from a blocked address was answered with a redirect to /login -- which
     * tells the prober there is an admin area here, the one thing the 404 is
     * meant to hide. Pinned for signed-out callers, since that is the case that
     * regressed.
     */
    public function test_a_blocked_stranger_gets_the_404_not_the_login_page(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get('/admin')
            ->assertNotFound();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin', ['X-Forwarded-For' => '203.0.113.9'])
            ->assertNotFound();
    }

    /** From an allowed address, a signed-out visitor is sent to log in as usual. */
    public function test_an_allowed_address_still_reaches_the_login_redirect(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin')
            ->assertRedirect('/login');
    }

    /** An empty list still means nobody, proxy or no proxy. */
    public function test_an_empty_allow_list_still_refuses_everyone(): void
    {
        config()->set('admin.allowed_ips', []);

        $this->actingAs($this->admin())
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin')
            ->assertNotFound();
    }
}
