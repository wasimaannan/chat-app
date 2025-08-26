<?php

namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Illuminate\Support\Facades\Log;

class KeyManagementService
{
    private $masterKey;
    private $dataKey;
    
    public function __construct()
    {
        $this->initializeKeys();
    }
    
    /**
     * Initialize encryption keys
     */
    private function initializeKeys()
    {
        try {
            // Load master key from environment or generate new one
            $masterKeyHex = config('app.master_encryption_key');
            if (!$masterKeyHex) {
                $this->generateAndSaveMasterKey();
                $masterKeyHex = config('app.master_encryption_key');
            }
            
            $this->masterKey = Key::loadFromAsciiSafeString($masterKeyHex);
            
            // Generate data encryption key if not exists
            $dataKeyHex = config('app.data_encryption_key');
            if (!$dataKeyHex) {
                $this->generateAndSaveDataKey();
                $dataKeyHex = config('app.data_encryption_key');
            }
            
            $this->dataKey = Key::loadFromAsciiSafeString($dataKeyHex);
            
        } catch (\Exception $e) {
            Log::error('Key management initialization failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to initialize encryption keys');
        }
    }
    
    /**
     * Generate and save master encryption key
     */
    private function generateAndSaveMasterKey()
    {
        $key = Key::createNewRandomKey();
        $keyHex = $key->saveToAsciiSafeString();
        
        // Update .env file
        $this->updateEnvFile('MASTER_ENCRYPTION_KEY', $keyHex);
        config(['app.master_encryption_key' => $keyHex]);
    }
    
    /**
     * Generate and save data encryption key
     */
    private function generateAndSaveDataKey()
    {
        $key = Key::createNewRandomKey();
        $keyHex = $key->saveToAsciiSafeString();
        
        // Update .env file
        $this->updateEnvFile('DATA_ENCRYPTION_KEY', $keyHex);
        config(['app.data_encryption_key' => $keyHex]);
    }
    
    /**
     * Update environment file with new key
     */
    private function updateEnvFile($key, $value)
    {
        $envFile = base_path('.env');
        if (file_exists($envFile)) {
            $env = file_get_contents($envFile);
            $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            if (!preg_match("/^{$key}=/m", $env)) {
                $env .= "\n{$key}={$value}";
            }
            file_put_contents($envFile, $env);
        }
    }
    
    /**
     * Get master encryption key
     */
    public function getMasterKey(): Key
    {
        return $this->masterKey;
    }
    
    /**
     * Get data encryption key
     */
    public function getDataKey(): Key
    {
        return $this->dataKey;
    }
    
    /**
     * Rotate encryption keys
     */
    public function rotateKeys()
    {
        $this->generateAndSaveMasterKey();
        $this->generateAndSaveDataKey();
        $this->initializeKeys();
        
        Log::info('Encryption keys rotated successfully');
    }
    
    /**
     * Key derivation for specific purposes
     */
    public function deriveKey(string $purpose, string $context = ''): Key
    {
        $salt = hash('sha256', $purpose . $context);
        $derivedKeyMaterial = hash_pbkdf2('sha256', $this->masterKey->saveToAsciiSafeString(), $salt, 10000, 32, true);
        
        return Key::loadFromAsciiSafeString(base64_encode($derivedKeyMaterial));
    }
}
