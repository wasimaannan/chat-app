# Simple PHP Download and Setup Script
Write-Host "Downloading and Installing PHP..." -ForegroundColor Cyan

# Create PHP directory
$phpDir = "C:\php"
$tempDir = "C:\temp"
New-Item -ItemType Directory -Force -Path $phpDir | Out-Null
New-Item -ItemType Directory -Force -Path $tempDir | Out-Null

# Download PHP manually approach
Write-Host "Opening PHP download page..." -ForegroundColor Yellow
Start-Process "https://windows.php.net/download/"

Write-Host "`nDOWNLOAD INSTRUCTIONS:" -ForegroundColor Yellow
Write-Host "1. Download 'Thread Safe' x64 version (zip file)" -ForegroundColor White
Write-Host "2. Extract the zip file to: $phpDir" -ForegroundColor White
Write-Host "3. Press Enter when done..." -ForegroundColor White
Read-Host

# Check if PHP was extracted
if (Test-Path "$phpDir\php.exe") {
    Write-Host "PHP found! Configuring..." -ForegroundColor Green
    
    # Create php.ini
    if (Test-Path "$phpDir\php.ini-development") {
        Copy-Item "$phpDir\php.ini-development" "$phpDir\php.ini"
    }
    
    # Add to PATH
    $currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
    if ($currentPath -notlike "*$phpDir*") {
        $newPath = "$phpDir;$currentPath"
        [Environment]::SetEnvironmentVariable("Path", $newPath, "User")
        Write-Host "Added PHP to PATH" -ForegroundColor Green
    }
    
    # Download Composer
    Write-Host "Installing Composer..." -ForegroundColor Yellow
    try {
        $composerSetup = "$tempDir\composer-setup.php"
        Invoke-WebRequest -Uri "https://getcomposer.org/installer" -OutFile $composerSetup
        & "$phpDir\php.exe" $composerSetup --install-dir="$phpDir" --filename=composer
        
        # Create composer.bat
        "@echo off`nphp `"%~dp0composer.phar`" %*" | Out-File "$phpDir\composer.bat" -Encoding ASCII
        Write-Host "Composer installed!" -ForegroundColor Green
    }
    catch {
        Write-Host "Composer installation failed. Download from getcomposer.org" -ForegroundColor Red
    }
    
    Write-Host "`nSUCCESS! Close and reopen your terminal." -ForegroundColor Green
    Write-Host "Then run: php --version" -ForegroundColor Cyan
    
} else {
    Write-Host "PHP not found. Please extract to: $phpDir" -ForegroundColor Red
}

Write-Host "`nPress Enter to continue..." -ForegroundColor Yellow
Read-Host
