# Admin panel

A live, Luno-style admin dashboard for the classroom: courses, modules, video lessons, students and passwords.

## First run (in this order)

```bash
php artisan migrate      # adds video_path and video_size to the lessons table (one new migration)
php artisan db:seed      # optional: demo courses and students, and the admin account below
npm run dev              # or: npm run build
```

Then log in and open **`/admin`**. The "Admin" link in the site menu opens it too.

The seeder creates the admin account from two settings in your own `.env`:
`ADMIN_SEED_EMAIL` (default `admin@piseth.design`) and `ADMIN_SEED_PASSWORD` (you choose it).
There is no default password: if `ADMIN_SEED_PASSWORD` is empty, the seeder skips the admin
account and tells you so. The password is only used when the account is first created, so
running the seeder again never resets a password that was changed later.

## What is where

| Page | URL | What it does |
|---|---|---|
| Dashboard | `/admin` | Live numbers (refresh every 6 seconds): students, enrollments, courses, who is online, students-vs-enrollments chart, latest enrollments, popular courses, video storage |
| Courses | `/admin/courses` | All courses with modules, lessons, uploaded videos and student counts. Publish or unpublish one from here |
| Course editor | `/admin/courses/{uuid}/edit` | Course details, then the curriculum: add, rename, re-order and delete modules and lessons |
| Videos | `/admin/videos` | Every lesson's video, across every course, in one place |
| Course pictures | `/admin/thumbnails` | A picture-first gallery for swapping covers without opening the full editor |
| Students | `/admin/users` | Everyone, filtered by role, with activity counts and online status. Change any password, give a course by hand or take it back, ban or delete |
| Certificates | `/admin/certificates` | Requests from students who finished a course. Approving one issues the certificate in the name you checked; taking an approval back withdraws it |
| Payments | `/admin/payments` | The money book. Archive, restore and export it; refunding is the one thing here that closes a course again |
| Promotions | `/admin/promotions` | Discounts and announcements for one course, a whole category or everything. Several can be live; the highest priority is the one shown |

Admin URLs identify a record by its `uuid`, not by its position in the table, so
no admin link says how many students or payments exist. A course is the
exception on the public side only: `/courses/{slug}` stays readable because it is
meant to be shared.

## Adding a video lesson

Open a course, add a module, then press **Add lesson** on it. For the video pick one of:

- **Upload a file** (MP4, WebM, MOV, M4V). It uploads in small pieces with a progress bar and starts as soon as you choose the file.
- **YouTube link** or **Google Drive link**, exactly as before.

Uploaded videos are stored privately in `storage/app/private/lesson-videos`. They are not reachable by a public web address.
Students watch them through `courses.lessons.video`, which applies the same rule as the lesson page: enrolled students and admins only. The player supports skipping around.

## Security

- `/admin` only opens for the IPs in `.env` under `ADMIN_ALLOWED_IPS`. It is set to this computer (`127.0.0.1,::1`). Add your server's or office IP before you deploy. An empty list blocks everyone. Everyone else gets a 404.
- **Behind nginx, Apache or a tunnel, also set `TRUSTED_PROXIES`.** The connection then reaches PHP from the proxy, so the app sees *its* address -- `127.0.0.1` on the same host -- and not the visitor's. Since `127.0.0.1` is on the list above, the list would match everyone and the lock would be gone, with nothing in the log to show it. Set `TRUSTED_PROXIES` to the proxy's address (or `*` when only the proxy can reach PHP) and the real address is read from `X-Forwarded-For` instead. Until you do, admin requests that arrive forwarded are refused outright, because the caller cannot be identified.
- Then you must be logged in and have the admin role (students get a 403, as before).
- Changing a password needs your own password too, signs that person out of every device, and is written to `storage/logs/laravel.log`.

## What changed in the project

New: `app/Http/Controllers/Admin/{Dashboard,User,Course,Module,Lesson,VideoUpload}Controller.php`,
`app/Http/Middleware/RestrictAdminAccess.php`, `app/Support/Bytes.php`, `config/admin.php`, `routes/admin.php`,
`resources/views/admin/*`, `resources/views/components/admin-layout.blade.php`, `resources/views/components/admin/icon.blade.php`,
`database/migrations/2026_09_21_000001_add_video_upload_to_lessons_table.php`, `tests/Feature/AdminPanelTest.php`.

Changed: `app/Models/Lesson.php` (uploaded videos), `app/Http/Controllers/LessonController.php` (the `video` method),
`resources/views/courses/player.blade.php` (plays uploaded videos), `routes/web.php`, `bootstrap/app.php` (one middleware name),
the "Admin" links in the site menu, footer and homepage (they now open the dashboard), `tests/Feature/ClassroomTest.php`,
`phpunit.xml`, `.env`, `.env.example`.

Replaced: the old admin (`ClassroomController` and `resources/views/admin/{courses,videos,students,analytics,_course-fields}.blade.php`, `partials/_admin-tabs.blade.php`).
They are still in your git history if you want anything back, for example `git show HEAD:app/Http/Controllers/Admin/ClassroomController.php`.

## Tests

`php artisan test` runs everything, including the new `AdminPanelTest` (network lock, dashboard numbers, creating a course, the enrollment-gated video, password change).

## Good to know

- New courses appear on the homepage automatically. The homepage already reads courses from the database.
- Deleting a course also removes its students' enrollments, progress and certificates. The confirmation says how many.
- `php artisan serve` handles one request at a time, so a big video can hold up other pages while it streams. It is fine for testing; use Apache (XAMPP) or Nginx for real use.
