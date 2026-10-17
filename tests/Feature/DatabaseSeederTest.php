<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_an_admin_seed_password_no_admin_is_created(): void
    {
        config(['admin.seed_email' => 'admin@piseth.design', 'admin.seed_password' => null]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'admin@piseth.design']);
        // The rest of the demo data still seeds.
        $this->assertDatabaseHas('users', ['email' => 'pisethyoeurn@gmail.com']);
    }

    public function test_the_admin_is_created_from_env_with_a_hashed_password(): void
    {
        config(['admin.seed_email' => 'owner@client.example', 'admin.seed_password' => 'Chosen-In-Env-123']);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'owner@client.example')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertNotSame('Chosen-In-Env-123', $admin->password);
        $this->assertTrue(Hash::check('Chosen-In-Env-123', $admin->password));
    }

    public function test_no_seeded_account_uses_the_old_default_password(): void
    {
        config(['admin.seed_password' => 'Chosen-In-Env-123']);

        $this->seed(DatabaseSeeder::class);

        foreach (User::all() as $user) {
            $this->assertFalse(Hash::check('password', $user->password), "{$user->email} still has the default password");
        }
    }

    public function test_re_seeding_never_overwrites_an_existing_admin_password(): void
    {
        config(['admin.seed_email' => 'admin@piseth.design', 'admin.seed_password' => 'First-Password-123']);
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@piseth.design')->firstOrFail();
        $admin->forceFill(['password' => 'Changed-Later-456'])->save();

        config(['admin.seed_password' => 'Different-Env-Value-789']);
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('Changed-Later-456', $admin->fresh()->password));
        $this->assertSame(1, User::where('email', 'admin@piseth.design')->count());
    }

    public function test_an_existing_admin_is_kept_even_without_a_seed_password(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@piseth.design',
            'password' => 'Existing-Password-123',
        ]);
        config(['admin.seed_email' => 'admin@piseth.design', 'admin.seed_password' => null]);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('Existing-Password-123', $admin->fresh()->password));
        $this->assertTrue($admin->fresh()->isAdmin());
    }
}
