#!/bin/bash
cd ..
rm ../storage
ln -s ./site/storage/app/public ../storage
git pull origin live_ca
export COMPOSER_HOME=/usr/local/bin/composer
composer install -d /home/firmt/public_html/site/ --no-dev
php artisan migrate --force
php artisan config:cache
php artisan config:clear
php artisan quick:log --m="Deploy Live Completed"