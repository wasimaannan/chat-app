# Database Setup Guide

## Quick Setup with SQLite (Recommended for Testing)

The application is pre-configured to use SQLite, which requires no additional database server installation.

### Steps:
1. SQLite database file is already created at `database/database.sqlite`
2. Run the following commands:
   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan serve
   ```

## MySQL Setup

If you prefer MySQL, follow these steps:

### 1. Install MySQL
- Download from: https://dev.mysql.com/downloads/mysql/
- Or use XAMPP: https://www.apachefriends.org/

### 2. Create Database
```sql
CREATE DATABASE secure_laravel_app;
CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON secure_laravel_app.* TO 'laravel_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Update .env file
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=secure_laravel_app
DB_USERNAME=laravel_user
DB_PASSWORD=secure_password
```

### 4. Run Laravel commands
```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

## PostgreSQL Setup

If you prefer PostgreSQL:

### 1. Install PostgreSQL
- Download from: https://www.postgresql.org/download/

### 2. Create Database
```sql
CREATE DATABASE secure_laravel_app;
CREATE USER laravel_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE secure_laravel_app TO laravel_user;
```

### 3. Update .env file
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=secure_laravel_app
DB_USERNAME=laravel_user
DB_PASSWORD=secure_password
```

### 4. Run Laravel commands
```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

## Database Schema

The application will create the following tables:

### users
- All personal information encrypted (name, email, phone, address, date_of_birth)
- Password hashed with unique salts
- MAC for data integrity verification

### posts
- Title and content encrypted
- MAC for data integrity verification
- User relationship

## Testing Database Security

After setup, you can verify the encryption is working:

1. Register a user through the web interface
2. Create some posts
3. Check the database directly - you should see encrypted data like:
   ```
   name: "def502005b7c8f..."
   email: "def502007a9d2e..."
   content: "def502009c4b1a..."
   ```

## Troubleshooting

### PHP Extensions Required
Make sure these PHP extensions are enabled:
- PDO
- sqlite3 (for SQLite)
- mysql (for MySQL)
- pgsql (for PostgreSQL)
- openssl
- mbstring
- json

### Common Issues

1. **"Class 'PDO' not found"**
   - Enable PDO extension in php.ini

2. **"could not find driver"**
   - Enable the specific database driver in php.ini

3. **Permission errors**
   - Ensure web server has write access to storage/ directories

4. **Key generation fails**
   - Ensure .env file is writable
   - Check openssl extension is enabled

## Security Notes

- All sensitive data is automatically encrypted before database storage
- Even with database access, attackers cannot read the actual data
- Password verification includes integrity checks
- MAC verification prevents data tampering
