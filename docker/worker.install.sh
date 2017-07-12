#!/usr/bin/env bash

echo '[*] Installing composer dependencies'
composer install --prefer-dist
echo

echo '[*] Creating config file'
cp docker/.env .env
echo

echo '[*] Generating encryption key'
php artisan key:generate
echo

echo '[*] Generating tiny ID key'
php artisan tiny:generate
echo

echo '[*] Optimizing framework'
php artisan optimize
echo

echo '[*] Installing composer depdendencies for crawlers'
cd ./bots/crawlers
./install_composer.sh
cd ../../
echo

echo '[*] Installing composer depdendencies for enforcers'
cd ./bots/enforcers
./install_composer.sh
cd ../../
echo

echo '[*] Initialise configuration for bots'
php artisan shark:init-bot-configs
echo

echo '[*] Installation complete'
echo '[*] You should now run "npm start" which will start application services'
echo '[*] You can login using "dan@dangreaves.com" with "password"'
