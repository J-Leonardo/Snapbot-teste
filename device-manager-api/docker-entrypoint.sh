#!/bin/bash

echo "Aguardando MySQL..."
while ! mysqladmin ping -h"mysql" --silent; do
    sleep 1
done
echo "MySQL pronto!"

php artisan migrate --force


php artisan config:cache
php artisan route:cache
php artisan view:cache

php-fpm



docker-compose build

docker-compose up -d
docker-compose ps
docker-compose exec laravel-app php artisan migrate
docker-compose logs -f