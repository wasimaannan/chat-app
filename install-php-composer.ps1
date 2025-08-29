# PHP, Composer & Laravel Installer (PowerShell)
# Secure Laravel App Setup

Write-Host "========================================" -ForegroundColor Green
Write-Host "   PHP, Composer & Laravel Installer" -ForegroundColor Green
Write-Host "   Secure Laravel App Setup" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Check if running as administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
if (-not $isAdmin) {
    Write-Host "[INFO] For best results, run this script as Administrator" -ForegroundColor Yellow
    Write-Host "       Right-click PowerShell and select 'Run as administrator'" -ForegroundColor Yellow
    Write-Host ""
    Start-Sleep -Seconds 3
}

# Set TLS version for downloads
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

# Installation directory
$installDir = "C:\laravel-tools"
Write-Host "[STEP 1] Creating installation directory..." -ForegroundColor Cyan

if (-not (Test-Path $installDir)) {
    New-Item -ItemType Directory -Path $installDir -Force | Out-Null
    Write-Host "Created directory: $installDir" -ForegroundColor Green
} else {
    Write-Host "Directory already exists: $installDir" -ForegroundColor Yellow
}

# Download and install PHP
Write-Host ""
Write-Host "[STEP 2] Setting up PHP..." -ForegroundColor Cyan

$phpVersion = "8.2.12"
$phpUrl = "https://windows.php.net/downloads/releases/php-$phpVersion-Win32-vs16-x64.zip"
$phpDir = "$installDir\php"
$phpZip = "$installDir\php.zip"

if (-not (Test-Path $phpDir)) {
    Write-Host "Downloading PHP $phpVersion..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip -UseBasicParsing
        Write-Host "Extracting PHP..." -ForegroundColor Yellow
        Expand-Archive -Path $phpZip -DestinationPath $phpDir -Force
        Remove-Item $phpZip -Force
        Write-Host "PHP extracted to: $phpDir" -ForegroundColor Green
    } catch {
        Write-Host "[ERROR] Failed to download PHP: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Please download manually from: https://windows.php.net/downloads/" -ForegroundColor Yellow
        Read-Host "Press Enter to continue..."
        exit 1
    }
} else {
    Write-Host "PHP directory already exists: $phpDir" -ForegroundColor Yellow
}

# Configure PHP
Write-Host ""
Write-Host "[STEP 3] Configuring PHP..." -ForegroundColor Cyan

$phpIni = "$phpDir\php.ini"
if (-not (Test-Path $phpIni)) {
    Copy-Item "$phpDir\php.ini-development" $phpIni
    Write-Host "Created php.ini from development template" -ForegroundColor Green
}

# Enable required extensions
$extensions = @(
    ';extension=pdo_sqlite',
    ';extension=pdo_mysql', 
    ';extension=openssl',
    ';extension=mbstring',
    ';extension=curl',
    ';extension=fileinfo',
    ';extension=gd'
)

$content = Get-Content $phpIni
foreach ($ext in $extensions) {
    $enabled = $ext.Substring(1)  # Remove semicolon
    $content = $content -replace [regex]::Escape($ext), $enabled
}
$content | Set-Content $phpIni

Write-Host "Enabled required PHP extensions" -ForegroundColor Green

# Add PHP to PATH
Write-Host ""
Write-Host "[STEP 4] Adding PHP to PATH..." -ForegroundColor Cyan

$currentPath = [Environment]::GetEnvironmentVariable("PATH", [EnvironmentVariableTarget]::User)
if ($currentPath -notlike "*$phpDir*") {
    $newPath = if ($currentPath) { "$currentPath;$phpDir" } else { $phpDir }
    [Environment]::SetEnvironmentVariable("PATH", $newPath, [EnvironmentVariableTarget]::User)
    Write-Host "Added PHP to user PATH" -ForegroundColor Green
    Write-Host "Please restart PowerShell for PATH changes to take effect" -ForegroundColor Yellow
} else {
    Write-Host "PHP already in PATH" -ForegroundColor Yellow
}

# Download Composer
Write-Host ""
Write-Host "[STEP 5] Setting up Composer..." -ForegroundColor Cyan

$composerPhar = "$installDir\composer.phar"
$composerUrl = "https://getcomposer.org/download/latest-stable/composer.phar"

if (-not (Test-Path $composerPhar)) {
    Write-Host "Downloading Composer..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri $composerUrl -OutFile $composerPhar -UseBasicParsing
        Write-Host "Composer downloaded successfully" -ForegroundColor Green
    } catch {
        Write-Host "[ERROR] Failed to download Composer: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Please download manually from: https://getcomposer.org/download/" -ForegroundColor Yellow
        Read-Host "Press Enter to continue..."
        exit 1
    }
} else {
    Write-Host "Composer already exists" -ForegroundColor Yellow
}

# Create Composer batch file
Write-Host ""
Write-Host "[STEP 6] Creating Composer launcher..." -ForegroundColor Cyan

$composerBat = "$installDir\composer.bat"
@"
@echo off
php "$composerPhar" %*
"@ | Out-File -FilePath $composerBat -Encoding ASCII

# Add Composer directory to PATH
if ($currentPath -notlike "*$installDir*") {
    $newPath = if ($currentPath) { "$currentPath;$installDir" } else { $installDir }
    [Environment]::SetEnvironmentVariable("PATH", $newPath, [EnvironmentVariableTarget]::User)
    Write-Host "Added Composer to user PATH" -ForegroundColor Green
}

# Test installations
Write-Host ""
Write-Host "[STEP 7] Testing installations..." -ForegroundColor Cyan

Write-Host "Testing PHP..." -ForegroundColor Yellow
try {
    $phpOutput = & "$phpDir\php.exe" --version
    Write-Host $phpOutput -ForegroundColor Green
    Write-Host "[OK] PHP is working" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] PHP test failed" -ForegroundColor Red
}

Write-Host ""
Write-Host "Testing Composer..." -ForegroundColor Yellow
try {
    $composerOutput = & "$phpDir\php.exe" $composerPhar --version
    Write-Host $composerOutput -ForegroundColor Green
    Write-Host "[OK] Composer is working" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Composer test failed" -ForegroundColor Red
}

# Installation summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "   Installation Summary" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "PHP installed at: $phpDir" -ForegroundColor White
Write-Host "Composer installed at: $installDir" -ForegroundColor White
Write-Host ""
Write-Host "Tools added to PATH:" -ForegroundColor White
Write-Host "- php.exe" -ForegroundColor Gray
Write-Host "- composer.bat" -ForegroundColor Gray
Write-Host ""
Write-Host "[IMPORTANT] Please restart PowerShell for PATH changes to take effect" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:" -ForegroundColor White
Write-Host "1. Restart PowerShell" -ForegroundColor Gray
Write-Host "2. Navigate to your Laravel project directory" -ForegroundColor Gray
Write-Host "3. Run: composer install" -ForegroundColor Gray
Write-Host "4. Run: php artisan key:generate" -ForegroundColor Gray
Write-Host "5. Run: php artisan migrate" -ForegroundColor Gray
Write-Host "6. Run: php artisan serve" -ForegroundColor Gray
Write-Host ""
Write-Host "========================================" -ForegroundColor Green

Read-Host "Press Enter to continue..."
