<?php

namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    private $keyManagement;
    
    public function __construct(KeyManagementService $keyManagement)
    {
        $this->keyManagement = $keyManagement;
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt(string $data, string $context = 'default'): string
    {
        try {
            $key = $this->keyManagement->getDataKey();
            $encrypted = Crypto::encrypt($data, $key);
            
            Log::info('Data encrypted successfully', ['context' => $context]);
            return base64_encode($encrypted);
            
        } catch (\Exception $e) {
            Log::error('Encryption failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to encrypt data');
        }
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt(string $encryptedData, string $context = 'default'): string
    {
        try {
            $key = $this->keyManagement->getDataKey();
            $decodedData = base64_decode($encryptedData);
            $decrypted = Crypto::decrypt($decodedData, $key);
            
            Log::info('Data decrypted successfully', ['context' => $context]);
            return $decrypted;
            
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            Log::error('Decryption failed - wrong key or tampered data: ' . $e->getMessage());
            throw new \RuntimeException('Failed to decrypt data - integrity check failed');
        } catch (\Exception $e) {
            Log::error('Decryption failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to decrypt data');
        }
    }
    
    /**
     * Encrypt user information for storage
     */
    public function encryptUserInfo(array $userInfo): array
    {
        $encryptedInfo = [];
        
        $sensitiveFields = ['name', 'email', 'phone', 'address', 'date_of_birth'];
        
        foreach ($userInfo as $field => $value) {
            if (in_array($field, $sensitiveFields) && !empty($value)) {
                $encryptedInfo[$field] = $this->encrypt($value, "user_info_{$field}");
            } else {
                $encryptedInfo[$field] = $value;
            }
        }
        
        return $encryptedInfo;
    }
    
    /**
     * Decrypt user information for viewing
     */
    public function decryptUserInfo(array $encryptedUserInfo): array
    {
        $decryptedInfo = [];
        
        $sensitiveFields = ['name', 'email', 'phone', 'address', 'date_of_birth'];
        
        foreach ($encryptedUserInfo as $field => $value) {
            if (in_array($field, $sensitiveFields) && !empty($value)) {
                try {
                    $decryptedInfo[$field] = $this->decrypt($value, "user_info_{$field}");
                } catch (\Exception $e) {
                    Log::warning("Failed to decrypt field {$field}: " . $e->getMessage());
                    $decryptedInfo[$field] = '[ENCRYPTED]';
                }
            } else {
                $decryptedInfo[$field] = $value;
            }
        }
        
        return $decryptedInfo;
    }
    
    /**
     * Encrypt post content
     */
    public function encryptPost(string $content, string $title = ''): array
    {
        return [
            'title' => !empty($title) ? $this->encrypt($title, 'post_title') : '',
            'content' => $this->encrypt($content, 'post_content'),
            'encrypted_at' => now()
        ];
    }
    
    /**
     * Decrypt post content
     */
    public function decryptPost(array $encryptedPost): array
    {
        return [
            'title' => !empty($encryptedPost['title']) ? $this->decrypt($encryptedPost['title'], 'post_title') : '',
            'content' => $this->decrypt($encryptedPost['content'], 'post_content'),
            'encrypted_at' => $encryptedPost['encrypted_at'] ?? null
        ];
    }
}
