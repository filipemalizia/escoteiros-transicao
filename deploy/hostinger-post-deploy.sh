#!/bin/bash
# O plano da Hostinger usado aqui não tem um campo de "Deployment script" no
# Git do hPanel — o auto-deploy deles só faz o `git pull` sozinho, sem rodar
# nada depois. Por isso este script não é chamado automaticamente: ele roda
# via SSH, disparado manualmente pela action
# .github/workflows/rodar-migrations-producao.yml (aba Actions > Run
# workflow), depois que o auto-deploy da Hostinger já colocou o código novo
# no ar.
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
