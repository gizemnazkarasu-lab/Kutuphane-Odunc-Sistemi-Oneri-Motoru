@echo off
cd /d "%~dp0"
echo Kutuphane Odunc Sistemi baslatiliyor: http://localhost:8080
start http://localhost:8080
php -S localhost:8080 -t public
pause
