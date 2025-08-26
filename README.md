# Secure Laravel Application

A comprehensive Laravel application demonstrating advanced security features including end-to-end encryption, secure authentication, and data integrity verification.

## 🔒 Security Features

This application implements all the requested security features:

### 1. **Login and Registration System**
- Secure user authentication with encrypted session management
- Registration with comprehensive data validation
- Custom session token generation and verification

### 2. **Data Encryption**
- **User Information Encryption**: All personal data (name, email, phone, address, date of birth) is encrypted before database storage
- **Post Content Encryption**: All post titles and content are encrypted end-to-end
- **Advanced Encryption**: Uses `defuse/php-encryption` library with AES-256 encryption

### 3. **Password Security**
- **Salted Hashing**: Passwords are hashed using bcrypt with unique random salts
- **Password Strength Validation**: Real-time password strength checking
- **Secure Storage**: Password hashes and salts stored separately

### 4. **Credential Verification**
- **Separate Credential Check Service**: Dedicated service for validating user credentials
- **Multi-layer Verification**: Email lookup through encrypted data comparison
- **Session Token Validation**: Secure token-based authentication

### 5. **Key Management Module**
- **Key Rotation**: Automatic key generation and rotation capabilities
- **Key Derivation**: Purpose-specific key derivation from master keys
- **Secure Storage**: Environment-based key storage with automatic generation

### 6. **Post Management with Encryption**
- **Encrypted Posts**: Users can create, edit, and view posts with full encryption
- **Secure Viewing**: Real-time decryption for authorized users only
- **Draft System**: Encrypted draft storage and management

### 7. **Database-Level Security**
- **Complete Encryption**: Every sensitive field in the database is encrypted
- **Attack Protection**: Even with database access, data remains unreadable
- **No Plain Text**: Zero plain text storage of sensitive information

### 8. **MAC Integrity Verification** *(Optional Feature Implemented)*
- **Data Integrity**: HMAC-SHA256 signatures for all sensitive data
- **Tamper Detection**: Automatic detection of data modification attempts
- **Timestamped MACs**: Time-based integrity verification with expiration

## 🏗️ Project Structure

```
secure-laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          # Authentication logic
│   │   │   ├── PostController.php          # Post management
│   │   │   └── DashboardController.php     # Dashboard
│   │   └── Middleware/
│   │       └── SecureAuth.php              # Authentication middleware
│   ├── Models/
│   │   ├── User.php                        # User model with encryption
│   │   └── Post.php                        # Post model with encryption
│   ├── Services/
│   │   ├── KeyManagementService.php        # Key management
│   │   ├── EncryptionService.php           # Encryption/decryption
│   │   ├── PasswordService.php             # Password hashing
│   │   ├── CredentialCheckService.php      # Credential verification
│   │   └── MACService.php                  # Data integrity (MAC)
│   └── Providers/
│       └── AppServiceProvider.php          # Service registration
├── database/
│   └── migrations/                         # Database schema
├── resources/
│   └── views/                              # Blade templates
├── routes/
│   └── web.php                             # Application routes
└── config/
    └── app.php                             # Application configuration
```

## 🛠️ Installation & Setup

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Web server (Apache/Nginx)

### Installation Steps

1. **Clone/Download the project**
   ```bash
   cd "f:\wasima proma\secure-laravel-app"
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Configuration**
   Edit `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=secure_laravel_app
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate
   ```

6. **Generate Encryption Keys**
   The application will automatically generate encryption keys on first run.

7. **Start Development Server**
   ```bash
   php artisan serve
   ```

## 🔑 Security Implementation Details

### Encryption Service
- **Algorithm**: AES-256-CBC via `defuse/php-encryption`
- **Key Management**: Separate master and data encryption keys
- **Context-Specific**: Different encryption contexts for different data types

### Password Service
- **Hashing**: bcrypt with individual salts
- **Strength Validation**: Multi-criteria password strength checking
- **Salt Generation**: Cryptographically secure random salt generation

### MAC Service
- **Algorithm**: HMAC-SHA256
- **Integrity**: Data integrity verification for all sensitive information
- **Timestamping**: Optional timestamp-based MAC with expiration

### Key Management
- **Master Key**: Primary encryption key for key derivation
- **Data Key**: Specific key for user data encryption
- **MAC Secret**: Separate key for integrity verification
- **Rotation**: Built-in key rotation capabilities

## 🌐 Application Features

### Authentication
- **Secure Login**: Encrypted credential verification
- **Registration**: Complete user onboarding with encryption
- **Session Management**: Token-based secure sessions
- **Profile Management**: Encrypted profile data editing

### Post Management
- **Create Posts**: Encrypted post creation with integrity verification
- **View Posts**: Real-time decryption for authorized viewing
- **Edit Posts**: Secure post modification with re-encryption
- **Draft System**: Encrypted draft storage and management

### Dashboard
- **Security Status**: Real-time security feature status
- **Statistics**: User activity metrics
- **Recent Activity**: Encrypted activity feed

## 🔍 Usage Examples

### Creating a User
```php
// Data is automatically encrypted in the User model
$user = new User();
$user->setEncryptedData([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890',
    'address' => '123 Main St',
    'date_of_birth' => '1990-01-01'
]);
```

### Verifying Credentials
```php
$credentialService = app(CredentialCheckService::class);
$result = $credentialService->validateCredentials($email, $password);

if ($result['success']) {
    // Login successful, data integrity verified
    $user = $result['user'];
}
```

### Creating Encrypted Posts
```php
$post = new Post();
$post->user_id = $user->id;
$post->setEncryptedData($title, $content);
$post->save(); // Automatically generates MAC for integrity
```

## 🔒 Security Guarantees

1. **Encryption at Rest**: All sensitive data encrypted before database storage
2. **Password Security**: Bcrypt hashing with unique salts per password
3. **Data Integrity**: HMAC verification prevents data tampering
4. **Key Security**: Proper key management with rotation capabilities
5. **Session Security**: Encrypted session tokens with expiration
6. **Attack Resistance**: Protection against SQL injection, data breaches

## 📝 Database Schema

### Users Table
- `name` (TEXT, ENCRYPTED): User's full name
- `email` (TEXT, ENCRYPTED): Email address
- `phone` (TEXT, ENCRYPTED): Phone number
- `address` (TEXT, ENCRYPTED): Physical address
- `date_of_birth` (TEXT, ENCRYPTED): Date of birth
- `password_hash` (VARCHAR): Bcrypt hash of salted password
- `password_salt` (VARCHAR): Unique salt for password
- `data_mac` (TEXT): HMAC for data integrity

### Posts Table
- `title` (TEXT, ENCRYPTED): Post title
- `content` (LONGTEXT, ENCRYPTED): Post content
- `data_mac` (TEXT): HMAC for data integrity
- `user_id` (FOREIGN KEY): Reference to user
- `is_published` (BOOLEAN): Publication status

## 🛡️ Security Best Practices Implemented

1. **Defense in Depth**: Multiple layers of security
2. **Principle of Least Privilege**: Minimal data exposure
3. **Data Minimization**: Only necessary data stored
4. **Encryption Everywhere**: End-to-end encryption
5. **Integrity Verification**: MAC-based tampering detection
6. **Secure Key Management**: Proper key storage and rotation
7. **Input Validation**: Comprehensive data validation
8. **Error Handling**: Secure error messages without information leakage

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME="Secure Laravel App"
APP_KEY=base64:your_app_key

# Database
DB_CONNECTION=mysql
DB_DATABASE=secure_laravel_app

# Custom Security Keys (Auto-generated)
MASTER_ENCRYPTION_KEY=your_master_key
DATA_ENCRYPTION_KEY=your_data_key
MAC_SECRET_KEY=your_mac_secret
```

## 📋 Testing

The application includes comprehensive security testing:

1. **Encryption Testing**: Verify all data is properly encrypted
2. **Authentication Testing**: Test credential verification
3. **Integrity Testing**: Verify MAC implementation
4. **Key Management Testing**: Test key rotation and derivation

## 🚀 Production Deployment

For production deployment:

1. Use HTTPS/TLS for all communications
2. Implement proper key backup and recovery
3. Set up monitoring for security events
4. Regular security audits and key rotation
5. Database encryption at rest
6. Web Application Firewall (WAF)

## 📄 License

This project is created for educational purposes demonstrating advanced security implementations in Laravel applications.

---

**⚠️ Security Notice**: This application demonstrates advanced security features for educational purposes. Always conduct security audits before production use.
