#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"
composer install --no-interaction
[ -f .env ] || cp .env.example .env
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
[ -e public/storage ] || php artisan storage:link
npm install
npm run build
echo "Setup completed. Run: php artisan serve"
