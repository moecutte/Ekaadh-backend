#!/usr/bin/env bash
# Production-style prepare for Coolify / server deploy.
# Run from Ekaadh-backend after code is on the server (or in Coolify terminal).
set -euo pipefail

php artisan migrate --force
php artisan db:seed --force   # first deploy only — comment out on later deploys
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Build ready. Change seeded passwords. Do not re-seed on future deploys."
