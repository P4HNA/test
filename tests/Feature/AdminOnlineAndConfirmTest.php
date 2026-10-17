<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The "N admins online" pill lists signed-in admins only, and destructive
 * admin actions ask through the site's own dialog, never the browser's box.
 */
class AdminOnlineAndConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_live_feed_lists_only_admins_active_in_the_last_five_minutes(): void
    {
        config(['session.driver' => 'database']);

        $me = User::factory()->admin()->create(['name' => 'Me Admin']);
        $other = User::factory()->admin()->create(['name' => 'Other Admin']);
        $gone = User::factory()->admin()->create(['name' => 'Gone Admin']);
        $student = User::factory()->create(['name' => 'Busy Student']);

        $row = fn (User $u, int $ago) => [
            'id' => 'sess-'.$u->id, 'user_id' => $u->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test',
            'payload' => '', 'last_activity' => now()->subSeconds($ago)->timestamp,
        ];
        DB::table('sessions')->insert([$row($other, 20), $row($gone, 3600), $row($student, 5)]);

        $online = $this->actingAs($me)->getJson(route('admin.live'))->assertOk()->json('online');

        $names = collect($online['admins'])->pluck('name')->all();
        $this->assertContains('Other Admin', $names);
        $this->assertNotContains('Gone Admin', $names);      // idle for an hour
        $this->assertNotContains('Busy Student', $names);    // a student, not an admin
        $this->assertSame('Active now', collect($online['admins'])->firstWhere('name', 'Other Admin')['when']);
    }

    public function test_destructive_admin_forms_use_the_site_dialog(): void
    {
        $admin = User::factory()->admin()->create();
        $promotion = Promotion::query()->create([
            'kind' => 'announcement', 'title_en' => 'Plain news', 'applies_to' => 'all', 'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.promotions.edit', $promotion))
            ->assertOk()
            ->assertSee('data-confirm=', false)
            ->assertSee('data-confirm-title="Delete this promotion?"', false)
            ->assertSee('adConfirm()', false)
            ->assertDontSee('confirm(@js', false);
    }
}
