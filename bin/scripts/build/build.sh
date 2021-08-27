#!/bin/sh
cp -r ./src/ ./dist/src/
cp -r ./public/ ./dist/public/
cp -r ./composer.json ./dist/composer.json
cp -r ./composer.lock ./dist/composer.lock
cd ./dist/
composer install --no-dev --no-interaction
composer dump-autoload -o --no-dev --no-interaction --classmap-authoritative