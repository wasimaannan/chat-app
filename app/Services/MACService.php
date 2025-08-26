<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MACService
{
    private $secretKey;
    
    public function __construct()
    {
        $this->secretKey = config('app.mac_secret_key') ?: $this->generateSecretKey();
    }
    
    /**
     * Generate MAC secret key
     */
    private function generateSecretKey(): string
    {
        $key = bin2hex(random_bytes(32));
        
        // Update .env file
        $this->updateEnvFile('MAC_SECRET_KEY', $key);
        config(['app.mac_secret_key' => $key]);
        
        return $key;
    }
    
    /**
     * Update environment file
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
     * Generate HMAC for data integrity
     */
    public function generateMAC(string $data, string $context = ''): string
    {
        try {
            $message = $context . '|' . $data;
            $mac = hash_hmac('sha256', $message, $this->secretKey);
            
            Log::info('MAC generated successfully', ['context' => $context]);
            return $mac;
            
        } catch (\Exception $e) {
            Log::error('MAC generation failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to generate MAC');
        }
    }
    
    /**
     * Verify HMAC for data integrity
     */
    public function verifyMAC(string $data, string $mac, string $context = ''): bool
    {
        try {
            $message = $context . '|' . $data;
            $expectedMAC = hash_hmac('sha256', $message, $this->secretKey);
            
            $isValid = hash_equals($expectedMAC, $mac);
            
            Log::info('MAC verification completed', [
                'context' => $context,
                'valid' => $isValid
            ]);
            
            return $isValid;
            
        } catch (\Exception $e) {
            Log::error('MAC verification failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate MAC for user data
     */
    public function generateUserDataMAC(array $userData, int $userId): string
    {
        $dataString = json_encode($userData) . $userId;
        return $this->generateMAC($dataString, 'user_data');
    }
    
    /**
     * Verify MAC for user data
     */
    public function verifyUserDataMAC(array $userData, int $userId, string $mac): bool
    {
        $dataString = json_encode($userData) . $userId;
        return $this->verifyMAC($dataString, $mac, 'user_data');
    }
    
    /**
     * Generate MAC for post data
     */
    public function generatePostDataMAC(array $postData, int $postId): string
    {
        $dataString = json_encode($postData) . $postId;
        return $this->generateMAC($dataString, 'post_data');
    }
    
    /**
     * Verify MAC for post data
     */
    public function verifyPostDataMAC(array $postData, int $postId, string $mac): bool
    {
        $dataString = json_encode($postData) . $postId;
        return $this->verifyMAC($dataString, $mac, 'post_data');
    }
    
    /**
     * Generate timestamped MAC
     */
    public function generateTimestampedMAC(string $data, string $context = ''): array
    {
        $timestamp = time();
        $message = $context . '|' . $data . '|' . $timestamp;
        $mac = hash_hmac('sha256', $message, $this->secretKey);
        
        return [
            'mac' => $mac,
            'timestamp' => $timestamp
        ];
    }
    
    /**
     * Verify timestamped MAC with expiration
     */
    public function verifyTimestampedMAC(string $data, string $mac, int $timestamp, string $context = '', int $maxAge = 3600): bool
    {
        // Check if MAC has expired
        if (time() - $timestamp > $maxAge) {
            Log::warning('MAC verification failed - expired', ['context' => $context]);
            return false;
        }
        
        $message = $context . '|' . $data . '|' . $timestamp;
        $expectedMAC = hash_hmac('sha256', $message, $this->secretKey);
        
        return hash_equals($expectedMAC, $mac);
    }
}
