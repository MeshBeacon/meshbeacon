#!/usr/bin/env bash
cp .env.example .env
sed -i 's/MQTT_HOST=/MQTT_HOST=mqtt-server/g' .env
composer install
php artisan key:generate
npm install
npm run build
chmod -R 777 storage
docker compose up
