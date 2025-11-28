@echo off
cd /d %~dp0
php artisan queue:work --timeout=3600 --tries=3
pause