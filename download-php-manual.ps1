# Manual PHP Download Script
# Downloads PHP from alternative reliable sources

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   Manual PHP Download & Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Create installation directory
$installDir = "C:\php"
$tempDir = "C:\laravel-tools"

Write-Host "[STEP 1] Creating directories..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path $installDir | Out-Null
New-Item -ItemType Directory -Force -Path $tempDir | Out-Null

Write-Host "[STEP 2] Downloading PHP..." -ForegroundColor Yellow

# Direct download approach
$phpUrl = "https://windows.php.net/downloads/releases/archives/php-8.2.12-Win32-vs16-x64.zip"
$phpZip = "$tempDir\php.zip"

try {
    Write-Host "Downloading PHP 8.2.12..." -ForegroundColor Gray
    
    # Use System.Net.WebClient for better compatibility
    $webClient = New-Object System.Net.WebClient
    $webClient.Headers.Add("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64)")
    $webClient.DownloadFile($phpUrl, $phpZip)
    
    if (Test-Path $phpZip) {
        Write-Host "Download successful!" -ForegroundColor Green
        $downloaded = $true
    } else {
        $downloaded = $false
    }
}
catch {
    Write-Host "Download failed: $($_.Exception.Message)" -ForegroundColor Red
    $downloaded = $false
}

if (-not $downloaded) {
    Write-Host "`n[MANUAL DOWNLOAD REQUIRED]" -ForegroundColor Red
    Write-Host "Please manually download PHP from:" -ForegroundColor Yellow
    Write-Host "https://windows.php.net/download/" -ForegroundColor Cyan
    Write-Host "`nDownload PHP 8.2.x or 8.3.x (Thread Safe, x64) and extract to: $installDir" -ForegroundColor Yellow
    
    # Open the download page
    Start-Process "https://windows.php.net/download/"
    
    Write-Host "`nPress Enter after manual download and extraction..." -ForegroundColor Yellow
    Read-Host
} else {
    Write-Host "[STEP 3] Extracting PHP..." -ForegroundColor Yellow
    
    try {
        Expand-Archive -Path $phpZip -DestinationPath $installDir -Force
        Write-Host "PHP extracted successfully!" -ForegroundColor Green
    }
    catch {
        Write-Host "Extraction failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Please extract manually to: $installDir" -ForegroundColor Yellow
        return
    }
}
}

Write-Host "[STEP 4] Configuring PHP..." -ForegroundColor Yellow

# Configure php.ini
$phpIni = "$installDir\php.ini"
$phpIniDev = "$installDir\php.ini-development"

if (Test-Path $phpIniDev) {
    Copy-Item $phpIniDev $phpIni -Force
    Write-Host "✓ Created php.ini from development template" -ForegroundColor Green
} elseif (-not (Test-Path $phpIni)) {
    # Create basic php.ini
    $basicIni = @"
[PHP]
extension_dir = "ext"
extension=openssl
extension=pdo_sqlite
extension=pdo_mysql
extension=mbstring
extension=curl
extension=fileinfo
extension=zip
max_execution_time = 300
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
"@
    Set-Content -Path $phpIni -Value $basicIni
    Write-Host "✓ Created basic php.ini" -ForegroundColor Green
}

Write-Host "[STEP 5] Adding PHP to PATH..." -ForegroundColor Yellow

# Add to PATH
$currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
if ($currentPath -notlike "*$installDir*") {
    $newPath = "$installDir;$currentPath"
    [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
    Write-Host "✓ Added PHP to user PATH" -ForegroundColor Green
} else {
    Write-Host "✓ PHP already in PATH" -ForegroundColor Green
}

Write-Host "[STEP 6] Installing Composer..." -ForegroundColor Yellow

# Download and install Composer
try {
    $composerInstaller = "$tempDir\composer-setup.php"
    $webClient = New-Object System.Net.WebClient
    $webClient.DownloadFile("https://getcomposer.org/installer", $composerInstaller)
    
    # Install Composer globally
    & "$installDir\php.exe" $composerInstaller --install-dir="$installDir" --filename=composer
    
    if (Test-Path "$installDir\composer.phar") {
        # Create composer.bat
        $composerBat = @"
@echo off
php "$installDir\composer.phar" %*
"@
        Set-Content -Path "$installDir\composer.bat" -Value $composerBat
        Write-Host "✓ Composer installed successfully!" -ForegroundColor Green
    }
}
catch {
    Write-Host "✗ Composer installation failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Please install Composer manually from: https://getcomposer.org/" -ForegroundColor Yellow
}

Write-Host "`n[STEP 7] Verification..." -ForegroundColor Yellow

# Test PHP installation
try {
    $phpVersion = & "$installDir\php.exe" --version
    Write-Host "✓ PHP Version:" -ForegroundColor Green
    Write-Host $phpVersion -ForegroundColor Gray
}
catch {
    Write-Host "✗ PHP verification failed" -ForegroundColor Red
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Installation Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

Write-Host "`nNext Steps:" -ForegroundColor Yellow
Write-Host "1. Close and reopen your terminal" -ForegroundColor White
Write-Host "2. Run: php --version" -ForegroundColor White
Write-Host "3. Run: composer --version" -ForegroundColor White
Write-Host "4. Navigate to your Laravel project and run:" -ForegroundColor White
Write-Host "   composer install" -ForegroundColor Cyan
Write-Host "   php artisan key:generate" -ForegroundColor Cyan
Write-Host "   php artisan migrate" -ForegroundColor Cyan
Write-Host "   php artisan serve" -ForegroundColor Cyan

Write-Host "`nPress Enter to finish..." -ForegroundColor Yellow
Read-Host
