#!/bin/bash
# Cole este conteúdo no campo "Deployment script" do Git da Hostinger
# (hPanel > Websites > Gerenciar > Avançado > Git). Ele roda automaticamente
# logo depois de cada "git pull" que a Hostinger fizer na branch "main".
#
# public/build (assets do Vite) é enviado separadamente, direto por SSH,
# pelo workflow .github/workflows/deploy-assets.yml — este servidor não tem
# Node/npm, então o build não pode rodar aqui.
set -e

composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
