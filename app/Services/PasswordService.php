<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordService
{
    /**
     * Hash password with salt
     */
    public function hashPassword(string $password): array
    {
        try {
            // Generate a random salt
            $salt = Str::random(32);
            
            // Combine password with salt
            $saltedPassword = $password . $salt;
            
            // Hash the salted password using bcrypt
            $hashedPassword = Hash::make($saltedPassword);
            
            Log::info('Password hashed successfully');
            
            return [
                'hash' => $hashedPassword,
                'salt' => $salt
            ];
            
        } catch (\Exception $e) {
            Log::error('Password hashing failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to hash password');
        }
    }
    
    /**
     * Verify password against hash and salt
     */
    public function verifyPassword(string $password, string $hash, string $salt): bool
    {
        try {
            // Combine password with stored salt
            $saltedPassword = $password . $salt;
            
            // Verify against stored hash
            $isValid = Hash::check($saltedPassword, $hash);
            
            Log::info('Password verification completed', ['valid' => $isValid]);
            
            return $isValid;
            
        } catch (\Exception $e) {
            Log::error('Password verification failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate secure random password
     */
    public function generateSecurePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($characters, ceil($length / strlen($characters)))), 0, $length);
    }
    
    /**
     * Check password strength
     */
    public function checkPasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];
        
        // Length check
        if (strlen($password) >= 8) {
            $score += 2;
        } else {
            $feedback[] = 'Password should be at least 8 characters long';
        }
        
        // Uppercase letter
        if (preg_match('/[A-Z]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'Include at least one uppercase letter';
        }
        
        // Lowercase letter
        if (preg_match('/[a-z]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'Include at least one lowercase letter';
        }
        
        // Number
        if (preg_match('/[0-9]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'Include at least one number';
        }
        
        // Special character
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = 'Include at least one special character';
        }
        
        // Determine strength level
        if ($score >= 5) {
            $strength = 'strong';
        } elseif ($score >= 3) {
            $strength = 'medium';
        } else {
            $strength = 'weak';
        }
        
        return [
            'strength' => $strength,
            'score' => $score,
            'feedback' => $feedback
        ];
    }
}
