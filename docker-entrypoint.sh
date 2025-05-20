#!/bin/sh
set -e

php artisan optimize:clear

composer dump-autoload --optimize

php artisan package:discover --ansi

php artisan migrate --force

echo "Running RolesAndPermissionsSeeder..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force
echo "RolesAndPermissionsSeeder completed."

if [ -f ".env" ] && ! grep -q "^APP_KEY=.\+" ".env"; then
  echo "APP_KEY not found or empty in .env, generating..."
  php artisan key:generate --ansi
fi

echo "Laravel setup tasks complete. Starting PHP-FPM..."
exec "$@"