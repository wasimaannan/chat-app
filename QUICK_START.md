# Quick Start Guide - Secure Laravel Application

## Option 1: Automated Installation (Recommended)

### Step 1: Install PHP & Composer
Run one of these installation scripts:

**For Windows Command Prompt:**
```bash
install-php-composer.bat
```

**For PowerShell (Recommended):**
```powershell
.\install-php-composer.ps1
```

### Step 2: Restart Terminal
After installation, **restart your terminal** (Command Prompt or PowerShell) for PATH changes to take effect.

### Step 3: Setup Laravel Application
```bash
# Install Laravel dependencies
composer install

# Generate application key
php artisan key:generate

# Run database migrations (creates tables)
php artisan migrate

# Start the development server
php artisan serve
```

### Step 4: Access the Application
Open your browser and go to: http://localhost:8000

## Option 2: Manual Installation

### Install PHP
1. Download PHP from: https://windows.php.net/downloads/
2. Extract to `C:\php`
3. Add `C:\php` to your system PATH
4. Copy `php.ini-development` to `php.ini`
5. Enable extensions: `pdo_sqlite`, `openssl`, `mbstring`, `curl`, `fileinfo`

### Install Composer
1. Download from: https://getcomposer.org/download/
2. Run the installer
3. Or download `composer.phar` and create a batch file

### Setup Laravel
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

## Application Features

Once running, you can:

1. **Register a new account** - All data will be encrypted
2. **Login securely** - Password verification with integrity checks
3. **Create encrypted posts** - Content encrypted before storage
4. **View posts** - Real-time decryption for viewing
5. **Check security status** - Dashboard shows encryption status

## Database Configuration

The application is pre-configured with SQLite (no server required):
- Database file: `database/database.sqlite`
- All sensitive data encrypted
- MAC integrity verification enabled

To use MySQL/PostgreSQL instead, edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Security Features Verification

After setup, verify encryption is working:

1. Register a user and create posts
2. Check the database file directly - you should see encrypted data like:
   ```
   name: "def502005b7c8f..."
   email: "def502007a9d2e..."
   ```
3. All passwords are hashed with unique salts
4. MAC verification prevents data tampering

## Troubleshooting

### "php is not recognized"
- Restart your terminal after installation
- Verify PHP is in PATH: `echo $env:PATH` (PowerShell) or `echo %PATH%` (CMD)

### "Class PDO not found"
- Enable PDO extension in `php.ini`
- Uncomment: `extension=pdo_sqlite`

### "Permission denied"
- Run terminal as Administrator
- Check storage directory permissions

### Composer installation fails
- Ensure PHP OpenSSL extension is enabled
- Download composer.phar manually if needed

## Advanced Setup

### Security Commands
```bash
# Rotate encryption keys
php artisan security:rotate-keys

# Verify data integrity
php artisan security:verify-integrity

# Check requirements
check-requirements.bat
```

### Production Deployment
1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Use HTTPS/TLS
4. Configure proper database server
5. Set up key backup and recovery

## Support

For issues:
1. Check `storage/logs/laravel.log`
2. Verify PHP extensions are enabled
3. Ensure database is properly configured
4. Check file permissions

## Security Guarantees

✅ All sensitive data encrypted before database storage
✅ Passwords hashed with unique salts using bcrypt
✅ Data integrity verification with HMAC-SHA256
✅ Protection against SQL injection and data tampering
✅ Secure session management with encrypted tokens
✅ Advanced key management with rotation capabilities

Even with database access, attackers cannot read actual user data without encryption keys.
