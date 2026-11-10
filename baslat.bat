@echo off
chcp 65001 >nul
cd /d "%~dp0"

set "PHP_EXE="

where php >nul 2>nul
if %errorlevel%==0 (
    set "PHP_EXE=php"
)

if "%PHP_EXE%"=="" (
    if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
)
if "%PHP_EXE%"=="" (
    if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"
)
if "%PHP_EXE%"=="" (
    for /d %%D in ("C:\wamp64\bin\php\php*") do (
        if exist "%%D\php.exe" set "PHP_EXE=%%D\php.exe"
    )
)
if "%PHP_EXE%"=="" (
    for /d %%D in ("C:\laragon\bin\php\php*") do (
        if exist "%%D\php.exe" set "PHP_EXE=%%D\php.exe"
    )
)

if "%PHP_EXE%"=="" (
    echo Bilgisayarinizda PHP kurulu olmadigi icin dogrudan Tarayici Onizleme surumu aciliyor...
    start "" "%~dp0canli-onizleme.html"
    exit /b 0
)

echo Kutuphane Odunc Sistemi baslatiliyor: http://localhost:8080
start http://localhost:8080
"%PHP_EXE%" -S localhost:8080 -t public
pause
