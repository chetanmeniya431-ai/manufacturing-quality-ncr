#!/bin/bash

echo "Waiting for Postgres at ${DB_HOST:-db}:${DB_PORT:-5432}..."
until php -r "
try {
    new PDO('pgsql:host=${DB_HOST:-db};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-quality_ncr}', '${DB_USERNAME}', '${DB_PASSWORD}');
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
"; do
  sleep 1
done
echo "Postgres is up."

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache
mkdir -p storage/logs
mkdir -p storage/app/private/quality-documents
mkdir -p storage/app/private/ncr-attachments
mkdir -p storage/fonts
mkdir -p storage/framework/cache/laravel-excel
chown -R www-data:www-data storage bootstrap/cache

if [ -z "${APP_KEY}" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo "No APP_KEY set — generated a temporary one for this container run."
fi

php artisan storage:link --force || true
php artisan migrate --force

# Seed only when the database is empty — skip on re-deploys to avoid
# unique-constraint errors from seeders that use plain create().
USER_COUNT=$(php -r "
try {
    \$pdo = new PDO('pgsql:host=${DB_HOST:-db};port=${DB_PORT:-5432};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');
    echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (Exception \$e) { echo '0'; }
" 2>/dev/null)
if [ "${USER_COUNT:-0}" -eq "0" ]; then
    echo "Fresh database — running seeders..."
    php artisan db:seed --force || echo "Seed warning: some records may already exist. Continuing."
else
    echo "Database already has data (${USER_COUNT} users) — skipping seed."
fi

exec "$@"
