<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailChangeVerificationCode;
use App\Rules\NotRestrictedName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_name_and_phone_are_saved_immediately_but_email_change_is_not(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'old@example.com', 'phone' => null]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'phone' => '12345678',
                'email' => 'new@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        // Name and phone apply right away.
        $this->assertSame('Test User', $user->name);
        $this->assertSame('12345678', $user->phone);

        // The email does not change until the code is confirmed.
        $this->assertSame('old@example.com', $user->email);
        $this->assertDatabaseHas('email_change_requests', [
            'user_id' => $user->id,
            'new_email' => 'new@example.com',
        ]);
        Notification::assertSentOnDemand(
            EmailChangeVerificationCode::class,
            fn ($notification) => $notification->newEmail === 'new@example.com'
        );
    }

    public function test_a_phone_number_with_non_digit_characters_is_rejected(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'phone' => '012-345 (678)',
                'email' => $user->email,
            ]);

        $response->assertSessionHasErrors('phone');
        $this->assertNull($user->fresh()->phone);
    }

    public function test_editing_an_existing_legacy_format_phone_number_left_untouched_still_works(): void
    {
        $user = User::factory()->create(['phone' => '+855 12 000 001']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'New Name',
                'phone' => $user->phone,
                'email' => $user->email,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('+855 12 000 001', $user->fresh()->phone);
        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_changing_an_existing_legacy_format_phone_number_to_a_new_value_must_be_digits_only(): void
    {
        $user = User::factory()->create(['phone' => '+855 12 000 001']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'phone' => '+855 98 765 432',
                'email' => $user->email,
            ]);

        $response->assertSessionHasErrors('phone');
        $this->assertSame('+855 12 000 001', $user->fresh()->phone);
    }

    public function test_a_new_all_digit_phone_number_is_accepted(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'phone' => '85512345678',
                'email' => $user->email,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('85512345678', $user->fresh()->phone);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_restricted_khmer_name_is_rejected_with_a_friendly_message(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'ចុយ',
                'email' => $user->email,
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(NotRestrictedName::MESSAGE, session('errors')->first('name'));
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_restricted_latin_slang_name_is_rejected_regardless_of_case_or_spacing(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => '  J.K.J  ',
                'email' => $user->email,
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_an_inappropriate_english_name_is_rejected_with_the_policy_message(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Big Shit',
                'email' => $user->email,
            ]);

        $response->assertSessionHasErrors(['name' => NotRestrictedName::MESSAGE]);
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_an_english_profanity_variant_is_rejected_and_the_red_warning_is_shown(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->followingRedirects()
            ->patch('/profile', ['name' => 'The mother fucker', 'email' => $user->email]);

        $response->assertSee(NotRestrictedName::MESSAGE);
        $response->assertSee('value="The mother fucker"', false);
        $this->assertSame('Original Name', $user->fresh()->name);
    }

    public function test_a_transliterated_khmer_slang_name_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', ['name' => 'Kdmv', 'email' => $user->email])
            ->assertSessionHasErrors(['name' => NotRestrictedName::MESSAGE]);

        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_a_valid_name_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Sok Dara', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertSame('Sok Dara', $user->fresh()->name);
    }

    public function test_the_name_warning_is_shown_under_the_name_field_without_revealing_the_dictionary(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profile')
            ->followingRedirects()
            ->patch('/profile', ['name' => 'JMR', 'email' => $user->email]);

        $response->assertOk();
        $html = $response->getContent();

        // The message sits directly under the Name input, which points at it
        // for screen readers and is focused so it is read out.
        $this->assertMatchesRegularExpression(
            '/<input\s[^>]*id="name"[^>]*autofocus[^>]*aria-invalid="true"[^>]*aria-describedby="name-error"[^>]*>\s*<ul (?=[^>]*role="alert")[^>]*id="name-error"[^>]*>\s*<li>'.preg_quote(e(NotRestrictedName::MESSAGE), '/').'<\/li>/',
            $html
        );

        // No hint of which term matched, and no other dictionary terms.
        foreach (['jmr', 'jkj', 'kdmv', 'ov hg'] as $term) {
            $this->assertStringNotContainsStringIgnoringCase(
                $term,
                strip_tags(preg_replace('/value="[^"]*"/', '', $html))
            );
        }
    }

    #[DataProvider('invalidNewPhoneNumbers')]
    public function test_new_phone_numbers_with_non_digit_characters_are_rejected(string $phone): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', ['name' => $user->name, 'phone' => $phone, 'email' => $user->email])
            ->assertSessionHasErrors('phone');

        $this->assertNull($user->fresh()->phone);
    }

    public static function invalidNewPhoneNumbers(): array
    {
        return [
            'spaces' => ['012 345 678'],
            'plus sign' => ['+85512345678'],
            'dashes' => ['012-345-678'],
            'letters' => ['012abc678'],
            'punctuation' => ['(012) 345.678'],
            'embedded newline' => ["012345678\n0"],
            'khmer digits' => ['០១២៣៤៥៦៧៨'],
        ];
    }

    public function test_an_empty_phone_number_is_allowed_and_clears_the_phone(): void
    {
        $user = User::factory()->create(['phone' => '012345678']);

        $this->actingAs($user)
            ->patch('/profile', ['name' => $user->name, 'phone' => '', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->phone);
    }

    public function test_a_legacy_format_phone_number_can_be_changed_to_a_digits_only_value(): void
    {
        $user = User::factory()->create(['phone' => '+855 12 000 001']);

        $this->actingAs($user)
            ->patch('/profile', ['name' => $user->name, 'phone' => '85512000001', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertSame('85512000001', $user->fresh()->phone);
    }

    public function test_the_phone_error_is_shown_under_the_phone_field(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->followingRedirects()
            ->patch('/profile', ['name' => $user->name, 'phone' => '+855 12', 'email' => $user->email]);

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<input\s[^>]*id="phone"[^>]*aria-invalid="true"[^>]*aria-describedby="phone-error phone-hint"[^>]*>/',
            $html
        );
        $this->assertMatchesRegularExpression('/<ul [^>]*id="phone-error"[^>]*>\s*<li>The phone number may only contain digits/', $html);
    }

    public function test_the_phone_input_has_no_pattern_that_would_block_an_untouched_legacy_value(): void
    {
        $user = User::factory()->create(['phone' => '+855 12 000 001']);

        $html = $this->actingAs($user)->get('/profile')->getContent();

        $this->assertMatchesRegularExpression('/<input\s[^>]*id="phone"[^>]*value="\+855 12 000 001"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input\s[^>]*id="phone"[^>]*\spattern=/', $html);
    }
}
