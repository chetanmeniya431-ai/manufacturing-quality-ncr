#!/bin/bash
set -e

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

mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p storage/app/private/quality-documents
mkdir -p storage/app/private/ncr-attachments
chown -R www-data:www-data storage bootstrap/cache

# The container is configured entirely via real environment variables (see
# docker-compose.yml) — there is no .env file inside the image. If no
# APP_KEY was provided, generate one for this process tree so Laravel boots;
# set APP_KEY in your host .env for a key that survives container restarts.
if [ -z "${APP_KEY}" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo "No APP_KEY set — generated a temporary one for this container run."
fi

php artisan storage:link --force || true
php artisan migrate --force

exec "$@"
