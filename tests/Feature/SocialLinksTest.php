<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_footer_has_real_facebook_and_instagram_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('https://www.facebook.com/yoeurnpiseth1', false)
            ->assertSee('https://www.instagram.com/piseth.design/', false);
    }

    /**
     * Telegram opens the QR popup instead of navigating anywhere: a real
     * button (no href="#", no new tab) wired to a labelled modal dialog
     * that shows the stored QR image.
     */
    private function assertTelegramQrPopup(string $html): void
    {
        $this->assertMatchesRegularExpression(
            '/<button type="button"\s+aria-haspopup="dialog"\s+aria-label="Telegram \(show QR code\)"[^>]*\$dispatch\(\'open-telegram-qr\'\)/',
            $html
        );
        $this->assertStringNotContainsString('Telegram (coming soon)', $html);
        $this->assertDoesNotMatchRegularExpression('/<a [^>]*aria-label="Telegram/', $html);

        $this->assertSame(1, substr_count($html, 'x-on:open-telegram-qr.window'), 'The dialog should be included exactly once.');
        $this->assertStringContainsString('<dialog', $html);
        $this->assertStringContainsString('aria-labelledby="telegram-qr-title"', $html);
        $this->assertStringContainsString('id="telegram-qr-title" class="font-display font-bold text-xl leading-tight">Join us on Telegram</h2>', $html);
        $this->assertStringContainsString('Scan this QR code with your phone to contact us on Telegram.', $html);
        $this->assertStringContainsString('aria-label="Close Telegram QR code"', $html);
        $this->assertStringContainsString(asset('images/telegram-qr.jpg'), $html);
        $this->assertStringContainsString('alt="Telegram QR code for @PISETH_YOEURN.', $html);
    }

    public function test_the_telegram_qr_image_exists_in_public_assets(): void
    {
        $this->assertFileExists(public_path('images/telegram-qr.jpg'));
        $this->assertSame('image/jpeg', mime_content_type(public_path('images/telegram-qr.jpg')));
    }

    public function test_homepage_footer_telegram_opens_the_qr_popup(): void
    {
        $this->assertTelegramQrPopup($this->get('/')->assertOk()->getContent());
    }

    public function test_classroom_footer_telegram_opens_the_qr_popup(): void
    {
        $this->assertTelegramQrPopup($this->get('/about')->assertOk()->getContent());
    }

    public function test_classroom_footer_has_real_facebook_and_instagram_links(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('https://www.facebook.com/yoeurnpiseth1', false)
            ->assertSee('https://www.instagram.com/piseth.design/', false);
    }
}
