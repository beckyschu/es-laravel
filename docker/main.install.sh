#!/usr/bin/env bash

echo '[*] Installing composer dependencies'
composer install --prefer-dist
echo

echo '[*] Installing Yarn dependencies'
yarn
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

echo '[*] Migrating database'
php artisan migrate
echo

echo '[*] Seeding database'
php artisan db:seed
echo

echo '[*] Optimizing framework'
php artisan optimize
echo

echo '[*] Installation complete'
echo '[*] You should now run "npm start" which will start application services'
echo '[*] You can login using "dan@dangreaves.com" with "password"'
