<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * First language: Khmer in Cambodia, English elsewhere, and a choice made
 * with the EN/KM switch always wins and is remembered.
 */
class LocaleDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_with_nothing_known_gets_english(): void
    {
        $this->get('/')->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_a_visitor_in_cambodia_by_cdn_country_gets_khmer(): void
    {
        $this->withHeader('CF-IPCountry', 'KH')->get('/')->assertSee('<html lang="km"', false);
    }

    public function test_a_visitor_elsewhere_by_cdn_country_gets_english_even_with_a_khmer_browser(): void
    {
        $this->withHeaders(['CF-IPCountry' => 'US', 'Accept-Language' => 'km-KH,km;q=0.9'])
            ->get('/')->assertSee('<html lang="en"', false);
    }

    public function test_a_browser_on_cambodia_time_gets_khmer(): void
    {
        $this->withUnencryptedCookie('pd_tz', 'Asia/Phnom_Penh')->get('/')->assertSee('<html lang="km"', false);
    }

    public function test_a_browser_on_another_time_zone_gets_english(): void
    {
        $this->withUnencryptedCookie('pd_tz', 'Asia/Bangkok')->get('/')->assertSee('<html lang="en"', false);
    }

    public function test_a_khmer_first_browser_gets_khmer_when_nothing_else_is_known(): void
    {
        $this->withHeader('Accept-Language', 'km-KH,km;q=0.9,en;q=0.5')->get('/')->assertSee('<html lang="km"', false);
    }

    public function test_the_switch_wins_over_detection_and_is_remembered(): void
    {
        $this->withHeader('CF-IPCountry', 'KH')
            ->post('/locale', ['locale' => 'en'])
            ->assertCookie('pd_locale', 'en');

        // A later visit with a fresh session, still in Cambodia: the choice holds.
        $this->flushSession();
        $this->withHeader('CF-IPCountry', 'KH')
            ->withCookie('pd_locale', 'en')
            ->get('/')->assertSee('<html lang="en"', false);
    }

    public function test_a_signed_in_student_keeps_their_choice_too(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withUnencryptedCookie('pd_tz', 'Asia/Phnom_Penh')
            ->withCookie('pd_locale', 'en')
            ->get(route('learning.index'))->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_the_page_tells_the_time_zone_script_whether_the_language_was_chosen(): void
    {
        $this->get('/')->assertSee('var auto = true;', false);
        $this->withCookie('pd_locale', 'km')->get('/')->assertSee('var auto = false;', false);
    }
}
