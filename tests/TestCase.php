<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Vite;

abstract class TestCase extends BaseTestCase
{
    /**
     * Render assets the way production does, never the way the developer's
     * machine happens to be set up.
     *
     * `npm run dev` writes public/hot, and while that file exists @vite points
     * every script and stylesheet at the Vite dev server (http://localhost:5173)
     * instead of at the built manifest. Tests that read the markup then pass or
     * fail depending on whether a dev server happens to be running in another
     * terminal: PayWayPaymentTest's "no third party scripts" check sees
     * localhost:5173 as an off-site host and fails, though nothing about the
     * app has changed.
     *
     * Pointing Vite at a hot file that cannot exist forces the manifest path in
     * every test, so the suite gives the same answer either way.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Vite::class)
            ->useHotFile(storage_path('framework/testing/vite-hot-disabled'));
    }
}
