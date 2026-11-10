#!/bin/zsh
cd "$(dirname "$0")"
PHP_BIN="php"
if [ -x "/Applications/MAMP/bin/php/php8.3.30/bin/php" ]; then
    PHP_BIN="/Applications/MAMP/bin/php/php8.3.30/bin/php"
fi
echo "Kütüphane Ödünç Sistemi başlatılıyor: http://localhost:8080"
(sleep 1 && open "http://localhost:8080") &
"$PHP_BIN" -S localhost:8080 -t public
