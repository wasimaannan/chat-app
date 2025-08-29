<?php

namespace App\Services;

use App\Models\User;
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

    // -------- Legacy symmetric encryption (Defuse crypto) --------
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

    // -------- Hybrid per-user field encryption (RSA wrap + AES-256-GCM) --------
    private const USER_FIELDS = ['name', 'email', 'phone', 'address', 'date_of_birth'];

    public function encryptUserInfoHybrid(User $user, array $userInfo): array
    {
        // Ensure user key pair exists
        $this->keyManagement->ensureUserKeyPair($user);
        $pub = $this->keyManagement->getPublicKey($user);
        if (!$pub) throw new \RuntimeException('Missing public key for user');

        $symKey = random_bytes(32); // AES-256 key
        $result = [];
        foreach ($userInfo as $field => $value) {
            if (in_array($field, self::USER_FIELDS) && $value !== null && $value !== '') {
                $result[$field] = $this->encryptFieldGCM((string)$value, $symKey, 'user_info_'.$field);
            } else {
                $result[$field] = $value;
            }
        }
        // Wrap symmetric key with RSA
        openssl_public_encrypt($symKey, $wrapped, $pub, OPENSSL_PKCS1_OAEP_PADDING);
        $result['wrapped_userinfo_key'] = base64_encode($wrapped);
        return $result;
    }

    public function decryptUserInfoHybrid(User $user, array $encryptedUserInfo): array
    {
        $priv = $this->keyManagement->getPrivateKey($user);
        if (!$priv || empty($user->wrapped_userinfo_key)) {
            // Fallback to legacy decrypt if no hybrid key yet
            return $this->decryptUserInfoLegacy($encryptedUserInfo);
        }
        if (!openssl_private_decrypt(base64_decode($user->wrapped_userinfo_key), $symKey, $priv, OPENSSL_PKCS1_OAEP_PADDING)) {
            Log::warning('Failed to unwrap symmetric key for user '.$user->id);
            return $this->decryptUserInfoLegacy($encryptedUserInfo);
        }
        $out = [];
        foreach ($encryptedUserInfo as $field => $value) {
            if (in_array($field, self::USER_FIELDS) && !empty($value)) {
                // Detect legacy vs hybrid format (legacy is base64 string w/ Defuse; hybrid has two colons)
                if (substr_count($value, ':') === 2) {
                    $out[$field] = $this->decryptFieldGCM($value, $symKey, 'user_info_'.$field);
                } else {
                    try { $out[$field] = $this->decrypt($value, 'user_info_'.$field); } catch (\Exception $e) { $out[$field] = '[ENCRYPTED]'; }
                }
            } else {
                $out[$field] = $value;
            }
        }
        return $out;
    }

    private function encryptFieldGCM(string $plain, string $symKey, string $aad): string
    {
        $iv = random_bytes(12);
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $symKey, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        return base64_encode($iv).':'.base64_encode($tag).':'.base64_encode($cipher);
    }

    private function decryptFieldGCM(string $packed, string $symKey, string $aad): string
    {
        [$ivB64,$tagB64,$ctB64] = explode(':', $packed);
        $iv = base64_decode($ivB64);
        $tag = base64_decode($tagB64);
        $ct = base64_decode($ctB64);
        $plain = openssl_decrypt($ct, 'aes-256-gcm', $symKey, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        return $plain === false ? '[ENCRYPTED]' : $plain;
    }

    // -------- Legacy bulk user info encryption/decryption retained --------
    /**
     * Encrypt user information for storage
     */
    public function encryptUserInfo(array $userInfo): array
    {
        $encryptedInfo = [];

        foreach ($userInfo as $field => $value) {
            if (in_array($field, self::USER_FIELDS) && !empty($value)) {
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
        return $this->decryptUserInfoLegacy($encryptedUserInfo);
    }

    private function decryptUserInfoLegacy(array $encryptedUserInfo): array
    {
        $decryptedInfo = [];

        foreach ($encryptedUserInfo as $field => $value) {
            if (in_array($field, self::USER_FIELDS) && !empty($value)) {
                try { $decryptedInfo[$field] = $this->decrypt($value, "user_info_{$field}"); }
                catch (\Exception $e) { Log::warning("Failed to decrypt legacy field {$field}: ".$e->getMessage()); $decryptedInfo[$field] = '[ENCRYPTED]'; }
            } else { $decryptedInfo[$field] = $value; }
        }
        return $decryptedInfo;
    }

    // -------- Post encryption (legacy) --------
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

    /**
     * Encrypt chat/message body
     */
    public function encryptMessage(string $body): string
    {
        return $this->encrypt($body, 'message_body');
    }

    /**
     * Decrypt chat/message body
     */
    public function decryptMessage(string $encryptedBody): string
    {
        return $this->decrypt($encryptedBody, 'message_body');
    }
}
