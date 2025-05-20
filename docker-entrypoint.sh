#!/bin/sh
set -e

echo "Clearing potentially stale Laravel caches..."
php artisan optimize:clear

echo "Optimizing Composer autoloader..."
composer dump-autoload --optimize

echo "Ensuring Composer packages are discovered..."
php artisan package:discover --ansi

echo "Running database migrations..."
php artisan migrate --force

echo "Checking if seeding is necessary..."
if php artisan tinker --execute='echo \App\Models\User::where("email", "admin@example.com")->exists();' | grep -q "true"; then
    echo "Admin user (admin@example.com) already exists. Skipping RolesAndPermissionsSeeder."
else
    echo "Admin user (admin@example.com) not found. Running RolesAndPermissionsSeeder..."
    php artisan db:seed --class=RolesAndPermissionsSeeder --force
    echo "RolesAndPermissionsSeeder completed."
fi

if [ -f ".env" ] && ! grep -q "^APP_KEY=.\+" ".env"; then
  echo "APP_KEY not found or empty in .env, generating..."
  php artisan key:generate --ansi
fi

echo "Laravel setup tasks complete. Starting PHP-FPM..."
exec "$@"