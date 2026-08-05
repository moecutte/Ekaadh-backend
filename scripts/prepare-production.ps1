# Production-style prepare for Coolify / server (PowerShell).
# First deploy: keep db:seed. Later deploys: remove the seed line.
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

Write-Host "Build ready. Change seeded passwords. Do not re-seed on future deploys."
