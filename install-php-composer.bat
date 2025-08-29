@echo off
setlocal EnableDelayedExpansion

echo ========================================
echo   PHP, Composer & Laravel Installer
echo   Secure Laravel App Setup
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] For best results, run this script as Administrator
    echo       Right-click and select "Run as administrator"
    echo.
    timeout /t 3 >nul
)

echo [STEP 1] Creating installation directory...
set INSTALL_DIR=C:\laravel-tools
if not exist "%INSTALL_DIR%" (
    mkdir "%INSTALL_DIR%"
    echo Created directory: %INSTALL_DIR%
) else (
    echo Directory already exists: %INSTALL_DIR%
)

echo.
echo [STEP 2] Downloading PHP...
set PHP_VERSION=8.2.12
set PHP_URL=https://windows.php.net/downloads/releases/php-%PHP_VERSION%-Win32-vs16-x64.zip
set PHP_DIR=%INSTALL_DIR%\php

if not exist "%PHP_DIR%" (
    echo Downloading PHP %PHP_VERSION%...
    powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%PHP_URL%' -OutFile '%INSTALL_DIR%\php.zip'}"
    
    if exist "%INSTALL_DIR%\php.zip" (
        echo Extracting PHP...
        powershell -Command "Expand-Archive -Path '%INSTALL_DIR%\php.zip' -DestinationPath '%PHP_DIR%' -Force"
        del "%INSTALL_DIR%\php.zip"
        echo PHP extracted to: %PHP_DIR%
    ) else (
        echo [ERROR] Failed to download PHP
        echo Please download manually from: https://windows.php.net/downloads/
        pause
        exit /b 1
    )
) else (
    echo PHP directory already exists: %PHP_DIR%
)

echo.
echo [STEP 3] Configuring PHP...
set PHP_INI=%PHP_DIR%\php.ini
if not exist "%PHP_INI%" (
    copy "%PHP_DIR%\php.ini-development" "%PHP_INI%"
    echo Created php.ini from development template
)

REM Enable required extensions
powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite' | Set-Content '%PHP_INI%'"
powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=pdo_mysql', 'extension=pdo_mysql' | Set-Content '%PHP_INI%'"
powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=openssl', 'extension=openssl' | Set-Content '%PHP_INI%'"
powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=mbstring', 'extension=mbstring' | Set-Content '%PHP_INI%'"
powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=curl', 'extension=curl' | Set-Content '%PHP_INI%'"
powershell -Command "(Get-Content '%PHP_INI%') -replace ';extension=fileinfo', 'extension=fileinfo' | Set-Content '%PHP_INI%'"

echo PHP extensions enabled in php.ini

echo.
echo [STEP 4] Adding PHP to PATH...
set PHP_PATH=%PHP_DIR%
echo Current PATH includes PHP: 
echo %PATH% | findstr /i "%PHP_PATH%" >nul
if %errorlevel% neq 0 (
    REM Add to user PATH
    for /f "tokens=2*" %%A in ('reg query "HKCU\Environment" /v PATH 2^>nul') do set UserPath=%%B
    if defined UserPath (
        setx PATH "%UserPath%;%PHP_PATH%" >nul
    ) else (
        setx PATH "%PHP_PATH%" >nul
    )
    echo Added PHP to user PATH
    echo Please restart your command prompt for PATH changes to take effect
) else (
    echo PHP already in PATH
)

echo.
echo [STEP 5] Downloading Composer...
set COMPOSER_URL=https://getcomposer.org/download/latest-stable/composer.phar
if not exist "%INSTALL_DIR%\composer.phar" (
    echo Downloading Composer...
    powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%COMPOSER_URL%' -OutFile '%INSTALL_DIR%\composer.phar'}"
    
    if exist "%INSTALL_DIR%\composer.phar" (
        echo Composer downloaded successfully
    ) else (
        echo [ERROR] Failed to download Composer
        echo Please download manually from: https://getcomposer.org/download/
        pause
        exit /b 1
    )
) else (
    echo Composer already exists
)

echo.
echo [STEP 6] Creating Composer batch file...
set COMPOSER_BAT=%INSTALL_DIR%\composer.bat
echo @echo off > "%COMPOSER_BAT%"
echo php "%INSTALL_DIR%\composer.phar" %%* >> "%COMPOSER_BAT%"

REM Add Composer to PATH
echo %PATH% | findstr /i "%INSTALL_DIR%" >nul
if %errorlevel% neq 0 (
    for /f "tokens=2*" %%A in ('reg query "HKCU\Environment" /v PATH 2^>nul') do set UserPath=%%B
    if defined UserPath (
        setx PATH "%UserPath%;%INSTALL_DIR%" >nul
    ) else (
        setx PATH "%INSTALL_DIR%" >nul
    )
    echo Added Composer to user PATH
)

echo.
echo [STEP 7] Testing installations...
echo Testing PHP...
"%PHP_DIR%\php.exe" --version
if %errorlevel% neq 0 (
    echo [ERROR] PHP test failed
) else (
    echo [OK] PHP is working
)

echo.
echo Testing Composer...
"%PHP_DIR%\php.exe" "%INSTALL_DIR%\composer.phar" --version
if %errorlevel% neq 0 (
    echo [ERROR] Composer test failed
) else (
    echo [OK] Composer is working
)

echo.
echo ========================================
echo   Installation Summary
echo ========================================
echo PHP installed at: %PHP_DIR%
echo Composer installed at: %INSTALL_DIR%
echo.
echo Tools added to PATH:
echo - php.exe
echo - composer.bat
echo.
echo [IMPORTANT] Please restart your command prompt or PowerShell
echo            for PATH changes to take effect.
echo.
echo Next steps:
echo 1. Restart command prompt
echo 2. Navigate to your Laravel project directory
echo 3. Run: composer install
echo 4. Run: php artisan key:generate
echo 5. Run: php artisan migrate
echo 6. Run: php artisan serve
echo.
echo ========================================
pause
