#!/bin/sh
set -e

php artisan optimize:clear

composer dump-autoload --optimize

php artisan package:discover --ansi

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