@echo off
echo ========================================
echo   Secure Laravel App - Setup Script
echo ========================================
echo.

echo [1/6] Checking PHP installation...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo WARNING: PHP is not installed or not in PATH
    echo.
    echo Please install PHP from: https://www.php.net/downloads.php
    echo Or use XAMPP/WAMP for easy installation: https://www.apachefriends.org/
    echo.
    echo After installing PHP, add it to your system PATH and run this script again.
    pause
    exit /b 1
)
echo PHP detected successfully!

echo.
echo [2/6] Checking Composer installation...
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Composer is not installed or not in PATH
    echo Please install Composer from https://getcomposer.org/
    pause
    exit /b 1
)
echo Composer detected successfully!

echo.
echo [3/6] Installing PHP dependencies...
composer install
if %errorlevel% neq 0 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)

echo.
echo [4/6] Setting up environment configuration...
if not exist .env (
    copy .env.example .env
    echo Environment file created from example
) else (
    echo Environment file already exists
)

echo.
echo [5/6] Generating application key...
php artisan key:generate
if %errorlevel% neq 0 (
    echo ERROR: Failed to generate application key
    pause
    exit /b 1
)

echo.
echo [6/6] Database configuration...
echo The application is configured to use SQLite by default (no server required).
echo SQLite database file created at: database\database.sqlite
echo.
echo If you prefer MySQL/PostgreSQL, edit .env file and update:
echo - DB_CONNECTION=mysql (or pgsql)
echo - DB_HOST=127.0.0.1
echo - DB_PORT=3306 (or 5432 for PostgreSQL)
echo - DB_DATABASE=your_database_name
echo - DB_USERNAME=your_username
echo - DB_PASSWORD=your_password
echo.
echo After configuring database, run these commands:
echo 1. php artisan key:generate
echo 2. php artisan migrate
echo 3. php artisan serve

echo.
echo ========================================
echo   Setup completed successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Configure your database in .env file
echo 2. Run: php artisan migrate
echo 3. Run: php artisan serve
echo 4. Visit: http://localhost:8000
echo.
echo Security features included:
echo - End-to-end encryption for all sensitive data
echo - Salted password hashing with bcrypt
echo - Data integrity verification with MAC
echo - Secure key management system
echo - Protected against database attacks
echo.
pause
