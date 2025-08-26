@echo off
echo ============================================
echo   PHP Installation and Requirements Check
echo ============================================
echo.

echo Checking PHP installation...
php --version 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH
    echo.
    echo Please install PHP from one of these options:
    echo 1. Official PHP: https://www.php.net/downloads.php
    echo 2. XAMPP ^(includes Apache/MySQL^): https://www.apachefriends.org/
    echo 3. WAMP ^(Windows^): https://www.wampserver.com/
    echo 4. Laragon ^(Developer friendly^): https://laragon.org/
    echo.
    echo After installation, add PHP to your system PATH.
    pause
    exit /b 1
)

echo [OK] PHP is installed
echo.

echo Checking required PHP extensions...

php -m | findstr /i "pdo" >nul
if %errorlevel% neq 0 (
    echo [ERROR] PDO extension is missing
) else (
    echo [OK] PDO extension
)

php -m | findstr /i "sqlite3" >nul
if %errorlevel% neq 0 (
    echo [WARNING] SQLite3 extension is missing ^(needed for SQLite database^)
) else (
    echo [OK] SQLite3 extension
)

php -m | findstr /i "openssl" >nul
if %errorlevel% neq 0 (
    echo [ERROR] OpenSSL extension is missing ^(required for encryption^)
) else (
    echo [OK] OpenSSL extension
)

php -m | findstr /i "mbstring" >nul
if %errorlevel% neq 0 (
    echo [ERROR] MBString extension is missing
) else (
    echo [OK] MBString extension
)

php -m | findstr /i "json" >nul
if %errorlevel% neq 0 (
    echo [ERROR] JSON extension is missing
) else (
    echo [OK] JSON extension
)

echo.
echo Checking Composer...
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer is not installed
    echo Download from: https://getcomposer.org/download/
) else (
    echo [OK] Composer is installed
)

echo.
echo ============================================
echo   Next Steps:
echo ============================================
echo 1. If all checks passed, run: setup.bat
echo 2. Configure database in .env file
echo 3. Run: php artisan key:generate
echo 4. Run: php artisan migrate
echo 5. Run: php artisan serve
echo.
pause
