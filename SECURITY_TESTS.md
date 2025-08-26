# Security Test Script for Secure Laravel Application

## Test Cases for Security Features

### 1. Encryption Testing

#### Test User Data Encryption
```php
// Test in Laravel Tinker: php artisan tinker

use App\Services\EncryptionService;
use App\Models\User;

$encryptionService = app(EncryptionService::class);

// Test encryption
$testData = ['name' => 'Test User', 'email' => 'test@example.com'];
$encrypted = $encryptionService->encryptUserInfo($testData);
echo "Encrypted data: " . print_r($encrypted, true);

// Test decryption
$decrypted = $encryptionService->decryptUserInfo($encrypted);
echo "Decrypted data: " . print_r($decrypted, true);

// Verify original data matches decrypted data
assert($testData['name'] === $decrypted['name']);
assert($testData['email'] === $decrypted['email']);
echo "✓ Encryption/Decryption test passed!";
```

#### Test Post Content Encryption
```php
use App\Services\EncryptionService;

$encryptionService = app(EncryptionService::class);

$title = "Test Post Title";
$content = "This is a test post content that should be encrypted.";

// Test encryption
$encrypted = $encryptionService->encryptPost($content, $title);
echo "Encrypted post: " . print_r($encrypted, true);

// Test decryption
$decrypted = $encryptionService->decryptPost($encrypted);
echo "Decrypted post: " . print_r($decrypted, true);

assert($title === $decrypted['title']);
assert($content === $decrypted['content']);
echo "✓ Post encryption/decryption test passed!";
```

### 2. Password Security Testing

#### Test Password Hashing and Verification
```php
use App\Services\PasswordService;

$passwordService = app(PasswordService::class);

$password = "TestPassword123!";

// Test hashing
$hashed = $passwordService->hashPassword($password);
echo "Password hash: " . $hashed['hash'];
echo "Salt: " . $hashed['salt'];

// Test verification with correct password
$isValid = $passwordService->verifyPassword($password, $hashed['hash'], $hashed['salt']);
assert($isValid === true);
echo "✓ Password verification test passed!";

// Test verification with wrong password
$isInvalid = $passwordService->verifyPassword("WrongPassword", $hashed['hash'], $hashed['salt']);
assert($isInvalid === false);
echo "✓ Wrong password rejection test passed!";
```

#### Test Password Strength Checker
```php
use App\Services\PasswordService;

$passwordService = app(PasswordService::class);

// Test weak password
$weak = $passwordService->checkPasswordStrength("123");
assert($weak['strength'] === 'weak');
echo "✓ Weak password detection test passed!";

// Test strong password
$strong = $passwordService->checkPasswordStrength("MyStrongP@ssw0rd!");
assert($strong['strength'] === 'strong');
echo "✓ Strong password detection test passed!";
```

### 3. MAC Integrity Testing

#### Test MAC Generation and Verification
```php
use App\Services\MACService;

$macService = app(MACService::class);

$testData = "This is test data for MAC verification";
$context = "test_context";

// Generate MAC
$mac = $macService->generateMAC($testData, $context);
echo "Generated MAC: " . $mac;

// Verify MAC with correct data
$isValid = $macService->verifyMAC($testData, $mac, $context);
assert($isValid === true);
echo "✓ MAC verification test passed!";

// Test with tampered data
$tamperedData = "This is tampered data for MAC verification";
$isInvalid = $macService->verifyMAC($tamperedData, $mac, $context);
assert($isInvalid === false);
echo "✓ Tampered data detection test passed!";
```

#### Test User Data MAC
```php
use App\Services\MACService;

$macService = app(MACService::class);

$userData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890'
];
$userId = 1;

// Generate MAC for user data
$mac = $macService->generateUserDataMAC($userData, $userId);
echo "User data MAC: " . $mac;

// Verify MAC
$isValid = $macService->verifyUserDataMAC($userData, $userId, $mac);
assert($isValid === true);
echo "✓ User data MAC test passed!";
```

### 4. Credential Check Testing

#### Test Credential Validation
```php
use App\Services\CredentialCheckService;

$credentialService = app(CredentialCheckService::class);

// Test credential strength validation
$validation = $credentialService->validateCredentialStrength(
    'test@example.com',
    'WeakPass'
);
assert($validation['valid'] === false);
echo "✓ Weak credential rejection test passed!";

$validation = $credentialService->validateCredentialStrength(
    'test@example.com',
    'StrongP@ssw0rd123!'
);
assert($validation['valid'] === true);
echo "✓ Strong credential acceptance test passed!";
```

### 5. Key Management Testing

#### Test Key Generation and Management
```php
use App\Services\KeyManagementService;

$keyService = app(KeyManagementService::class);

// Test key retrieval
$masterKey = $keyService->getMasterKey();
$dataKey = $keyService->getDataKey();

assert($masterKey !== null);
assert($dataKey !== null);
echo "✓ Key management test passed!";

// Test key derivation
$derivedKey = $keyService->deriveKey('test_purpose', 'test_context');
assert($derivedKey !== null);
echo "✓ Key derivation test passed!";
```

### 6. Database Security Testing

#### Test Encrypted Storage
```sql
-- Check that sensitive data is encrypted in database
-- Run these queries directly in your database

-- Check users table - should show encrypted data
SELECT id, name, email, phone FROM users LIMIT 1;
-- Expected: Encrypted strings, not readable plain text

-- Check posts table - should show encrypted content
SELECT id, title, content FROM posts LIMIT 1;
-- Expected: Encrypted strings, not readable plain text
```

#### Test Data Integrity
```php
use App\Models\User;
use App\Models\Post;

// Test user data integrity
$user = User::first();
if ($user) {
    $integrityCheck = $user->verifyIntegrity();
    assert($integrityCheck === true);
    echo "✓ User data integrity test passed!";
}

// Test post data integrity
$post = Post::first();
if ($post) {
    $integrityCheck = $post->verifyIntegrity();
    assert($integrityCheck === true);
    echo "✓ Post data integrity test passed!";
}
```

### 7. Session Security Testing

#### Test Session Token Generation and Validation
```php
use App\Services\CredentialCheckService;
use App\Models\User;

$credentialService = app(CredentialCheckService::class);
$user = User::first();

if ($user) {
    // Generate session token
    $token = $credentialService->generateSessionToken($user);
    echo "Generated token: " . substr($token, 0, 50) . "...";
    
    // Validate session token
    $validatedUser = $credentialService->validateSessionToken($token);
    assert($validatedUser !== null);
    assert($validatedUser->id === $user->id);
    echo "✓ Session token test passed!";
}
```

### 8. Attack Simulation Tests

#### Test SQL Injection Protection
```php
// Test with malicious input
$maliciousEmail = "'; DROP TABLE users; --";
$maliciousPassword = "password' OR '1'='1";

use App\Services\CredentialCheckService;
$credentialService = app(CredentialCheckService::class);

$result = $credentialService->validateCredentials($maliciousEmail, $maliciousPassword);
assert($result['success'] === false);
echo "✓ SQL injection protection test passed!";
```

#### Test Data Tampering Detection
```php
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Get a user
$user = User::first();
if ($user) {
    $originalMac = $user->data_mac;
    
    // Tamper with data directly in database
    DB::table('users')
        ->where('id', $user->id)
        ->update(['name' => 'Tampered Name']);
    
    // Reload user and test integrity
    $user->refresh();
    $integrityCheck = $user->verifyIntegrity();
    assert($integrityCheck === false);
    echo "✓ Data tampering detection test passed!";
    
    // Restore original MAC
    DB::table('users')
        ->where('id', $user->id)
        ->update(['data_mac' => $originalMac]);
}
```

## Running Tests

### Using Laravel Tinker
```bash
php artisan tinker
# Then copy and paste test code blocks
```

### Using PHPUnit (if configured)
```bash
php artisan test
```

### Manual Browser Testing
1. Register a new user
2. Login with credentials
3. Create encrypted posts
4. Verify data appears correctly
5. Check database to ensure encryption
6. Test various attack scenarios

## Expected Results

### Database Content Should Show:
- ✅ Encrypted user names (base64 encoded strings)
- ✅ Encrypted email addresses
- ✅ Encrypted post titles and content
- ✅ Hashed passwords with salts
- ✅ MAC values for integrity verification

### Application Should Provide:
- ✅ Seamless user experience with transparent encryption
- ✅ Secure authentication and session management
- ✅ Data integrity verification
- ✅ Protection against common attacks

## Security Verification Checklist

- [ ] All sensitive data encrypted in database
- [ ] Passwords properly hashed with salts
- [ ] MAC integrity verification working
- [ ] Session tokens secure and expiring
- [ ] Key management functioning
- [ ] Attack protection mechanisms active
- [ ] No plain text sensitive data visible
- [ ] Encryption/decryption cycle working correctly
