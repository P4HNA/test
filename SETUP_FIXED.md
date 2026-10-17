# ProjectWebsite - Windows Setup

## Important PowerShell note

PowerShell does not run a `.bat` file from the current folder by typing only its name.
From the project root, run:

```powershell
.\setup.bat
```

The setup script uses `call` for Composer and npm so it works correctly when those commands are Windows `.bat`/`.cmd` files.

## Requirements

- PHP 8.2 or newer
- Composer 2.x
- Node.js + npm
- PHP extensions required by Laravel/Composer (normally enabled by XAMPP)

Check them with:

```powershell
php -v
composer --version
node -v
npm -v
```

## Install

From the folder containing `artisan` and `setup.bat`:

```powershell
.\setup.bat
```

The script will:

1. Check PHP and Composer.
2. Install PHP dependencies with Composer.
3. Create `.env` from `.env.example` if needed.
4. Create the SQLite database.
5. Generate the Laravel application key.
6. Run migrations and seed demo data.
7. Link `public/storage` so uploaded course thumbnails are visible.
8. Install npm dependencies and build the frontend.

## Start

```powershell
php artisan serve
```

Open:

`http://127.0.0.1:8000`

## Admin account

Before running setup (or `php artisan db:seed`), set these in your own `.env`:

- `ADMIN_SEED_EMAIL` -- the admin login email (default `admin@piseth.design`)
- `ADMIN_SEED_PASSWORD` -- a password you choose (never commit it)

Then log in at `/login` with that email and password. The seeded demo students get
random passwords nobody knows; they only fill the classroom with sample data.

## If Composer is not found

Install Composer, restart VS Code, and verify:

```powershell
composer --version
```

Then run:

```powershell
.\setup.bat
```

## If `vendor/autoload.php` is missing

Do not run `php artisan serve` yet. Run:

```powershell
composer install
```

Make sure `vendor\autoload.php` exists, then run:

```powershell
php artisan serve
```

## If the site loads with no styling, or images are broken

Two things are generated on your machine and are deliberately not stored in Git
(`public/build` and `public/storage` are both in `.gitignore`). After a fresh
clone you must create them, or `.\setup.bat` will do it for you.

**"Vite manifest not found" / the page has no CSS:**

```powershell
npm install
npm run build
```

**Uploaded course thumbnails show as broken images:**

```powershell
php artisan storage:link
```

## ABA PayWay / KHQR

Paid courses use ABA PayWay KHQR. Put your real values in `.env`:

- `PAYWAY_BASE_URL` (sandbox or live)
- `PAYWAY_MERCHANT_ID`
- `PAYWAY_API_KEY` (ABA calls it the "Public Key")
- `PAYWAY_CALLBACK_URL` (a **public** https URL ending in `/payway/callback`)
- `PAYWAY_CURRENCY`
- `PAYWAY_QR_LIFETIME`
- `PAYWAY_TIMEOUT`

Check them with:

```powershell
php artisan config:clear
php artisan payway:test
```

PayWay confirms payments by calling `PAYWAY_CALLBACK_URL`, which it cannot do if
that points at localhost. **See `PAYMENTS.md` section 3** for the cloudflared /
ngrok recipe and the full setup.

The old `BAKONG_*` keys are no longer read by anything — that gateway has been
removed. You can delete them from your `.env`.

Do not commit `.env` or real credentials to Git.
