<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class CredentialCheckService
{
    private $passwordService;
    private $encryptionService;
    private $macService;
    
    public function __construct(
        PasswordService $passwordService,
        EncryptionService $encryptionService,
        MACService $macService
    ) {
        $this->passwordService = $passwordService;
        $this->encryptionService = $encryptionService;
        $this->macService = $macService;
    }
    
    // Validate user credentials during login

    public function validateCredentials(string $email, string $password): array
    {
        try {
            $user = $this->findUserByEmail($email);
            
            if (!$user) {
                Log::warning('Login attempt with non-existent email', ['email' => $email]);
                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'user' => null
                ];
            }
            
            // Verify password
            $isPasswordValid = $this->passwordService->verifyPassword(
                $password,
                $user->password_hash,
                $user->password_salt
            );
            
            if (!$isPasswordValid) {
                Log::warning('Login attempt with invalid password', ['user_id' => $user->id]);
                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'user' => null
                ];
            }
            
            // Verify data integrity using MAC
            if (!$this->verifyUserIntegrity($user)) {
                Log::error('User data integrity check failed', ['user_id' => $user->id]);
                return [
                    'success' => false,
                    'message' => 'Data integrity error',
                    'user' => null
                ];
            }
            
            Log::info('Successful login', ['user_id' => $user->id]);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
            
        } catch (\Exception $e) {
            Log::error('Credential validation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Authentication error',
                'user' => null
            ];
        }
    }
    
    // Find user for hash and decryption

    private function findUserByEmail(string $email): ?User
    {
        $hash = hash('sha256', strtolower(trim($email)));
        $user = User::where('email_hash', $hash)->first();
        if ($user) {
            try {
                $decryptedEmail = $this->encryptionService->decrypt($user->email, 'user_info_email');
                if (hash_equals(strtolower($decryptedEmail), strtolower($email))) {
                    return $user;
                }
            } catch (\Throwable $e) {
                Log::warning('Email hash matched but decrypt failed', ['user_id' => $user->id]);
            }
        }
        return null;
    }

    // user data integrity MAC

    private function verifyUserIntegrity(User $user): bool
    {
        if (empty($user->data_mac)) {
            Log::warning('User has no MAC for integrity check', ['user_id' => $user->id]);
            return true; 
        }
        
        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'date_of_birth' => $user->date_of_birth
        ];
        
        return $this->macService->verifyUserDataMAC($userData, $user->id, $user->data_mac);
    }
    
    // Validate credential strength during registration or password change

    public function validateCredentialStrength(string $email, string $password): array
    {
        $errors = [];
        
        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Password strength check
        $passwordCheck = $this->passwordService->checkPasswordStrength($password);
        if ($passwordCheck['strength'] === 'weak') {
            $errors = array_merge($errors, $passwordCheck['feedback']);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'password_strength' => $passwordCheck['strength'] ?? 'unknown'
        ];
    }

    // Generate secure session token
    public function generateSessionToken(User $user): string
    {
        $tokenData = [
            'user_id' => $user->id,
            'email' => $user->email,
            'timestamp' => time(),
            'random' => bin2hex(random_bytes(16))
        ];
        
        $tokenString = json_encode($tokenData);
        $encryptedToken = $this->encryptionService->encrypt($tokenString, 'session_token');
        
        // Generate MAC for token
        $mac = $this->macService->generateMAC($encryptedToken, 'session_token');
        
        return base64_encode($encryptedToken . '|' . $mac);
    }

    // Validate session token
    public function validateSessionToken(string $token): ?User
    {
        try {
            $decodedToken = base64_decode($token);
            $parts = explode('|', $decodedToken, 2);
            
            if (count($parts) !== 2) {
                return null;
            }
            
            [$encryptedToken, $mac] = $parts;
            
            // Verify MAC
            if (!$this->macService->verifyMAC($encryptedToken, $mac, 'session_token')) {
                Log::warning('Session token MAC verification failed');
                return null;
            }
            
            // Decrypt token
            $tokenString = $this->encryptionService->decrypt($encryptedToken, 'session_token');
            $tokenData = json_decode($tokenString, true);
            
            if (!$tokenData || !isset($tokenData['user_id'])) {
                return null;
            }
            
            // Check token age (24 hours)
            if (time() - $tokenData['timestamp'] > 86400) {
                Log::info('Session token expired', ['user_id' => $tokenData['user_id']]);
                return null;
            }
            
            return User::find($tokenData['user_id']);
            
        } catch (\Exception $e) {
            Log::error('Session token validation failed: ' . $e->getMessage());
            return null;
        }
    }
}
