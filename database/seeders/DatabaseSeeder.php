<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedAdmin();

        // Demo students get a long random password nobody knows: they exist
        // to fill the classroom with sample data, not to be logged into.
        // (Use "Forgot password" if you ever need into one.)
        $student = $this->seedUser('pisethyoeurn@gmail.com', [
            'name' => 'Piseth Yoeurn',
            'role' => 'student',
            'phone' => '+855 12 345 678',
        ], Str::random(40));

        foreach ([
            ['name' => 'Sokha Chan', 'email' => 'sokha.chan@gmail.com'],
            ['name' => 'Vicheka Seng', 'email' => 'vicheka.seng@creative.kh'],
            ['name' => 'Dara Meas', 'email' => 'dara.meas@designhub.com'],
            ['name' => 'Bopha Lim', 'email' => 'bopha.lim@studio.kh'],
        ] as $person) {
            $this->seedUser($person['email'], [
                'name' => $person['name'],
                'role' => 'student',
            ], Str::random(40));
        }

        $this->call(CourseSeeder::class);

        $photoshop = Course::query()->where('slug', 'adobe-photoshop-beginner')->first();
        $illustrator = Course::query()->where('slug', 'adobe-illustrator-beginner')->first();
        $fundamentals = Course::query()->where('slug', 'graphic-design-fundamentals')->first();

        foreach ([$photoshop, $illustrator, $fundamentals] as $course) {
            if (! $course) {
                continue;
            }

            $enrollment = Enrollment::query()->firstOrCreate(
                [
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                ],
                ['enrolled_at' => now()->subDays(20)]
            );

            $lessons = $course->load('modules.lessons')->modules->flatMap->lessons;

            if ($course->is($fundamentals)) {
                foreach ($lessons as $lesson) {
                    $enrollment->markLessonComplete($lesson);
                }
            }

            if ($course->is($photoshop)) {
                foreach ($lessons->take(7) as $lesson) {
                    $enrollment->markLessonComplete($lesson);
                }
            }

            if ($course->is($illustrator)) {
                $first = $lessons->first();
                if ($first) {
                    $enrollment->markLessonComplete($first);
                }
            }
        }

    }

    /**
     * The admin's email and password come from .env (ADMIN_SEED_EMAIL /
     * ADMIN_SEED_PASSWORD), never from source code.
     */
    private function seedAdmin(): void
    {
        $email = (string) config('admin.seed_email');
        $password = (string) config('admin.seed_password');
        $exists = User::query()->where('email', $email)->exists();

        if (! $exists && $password === '') {
            $this->command?->warn(
                'Admin account not created: set ADMIN_SEED_PASSWORD in .env, then run "php artisan db:seed" again.'
            );

            return;
        }

        $this->seedUser($email, [
            'name' => 'Piseth Design',
            'role' => 'admin',
            'phone' => '+855 12 000 001',
        ], $password);
    }

    /**
     * Create the account, or refresh its profile fields if it already exists.
     * The password is only set on creation: re-running the seeder never
     * overwrites a password someone has since changed.
     */
    private function seedUser(string $email, array $attributes, string $password): User
    {
        $attributes['email_verified_at'] = now();

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->forceFill($attributes)->save();

            return $user;
        }

        // The "hashed" cast on User::$password hashes this before saving.
        return User::query()->forceCreate($attributes + [
            'email' => $email,
            'password' => $password,
        ]);
    }
}
