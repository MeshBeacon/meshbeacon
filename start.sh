#!/usr/bin/env bash
cp .env.example .env
sed -i 's/MQTT_HOST=/MQTT_HOST=mqtt-server/g' .env
docker run --rm -it --volume $PWD:/app -w /app composer:2.2.29 composer install
docker run --rm -it --volume $PWD:/app -w /app php:8.4.24-zts-alpine3.23 php artisan key:generate
docker run --rm -v $PWD:/app -w /app node:20-alpine npm install
docker run --rm -v $PWD:/app -w /app node:20-alpine npm run build
chmod -R 777 storage
docker compose up
