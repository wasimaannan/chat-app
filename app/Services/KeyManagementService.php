<?php

namespace App\Services;

use App\Models\KeyPair;
use App\Models\User;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class KeyManagementService
{
    private $masterKey;
    private $dataKey;
    
    public function __construct()
    {
        $this->initializeKeys();
    }

    // Initialize encryption keys

    private function initializeKeys()
    {
        try {
            // Load master key from env or generate new one
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
    
    // Generate and save master encryption key
    private function generateAndSaveMasterKey()
    {
        $key = Key::createNewRandomKey();
        $keyHex = $key->saveToAsciiSafeString();
        
        // Update .env file
        $this->updateEnvFile('MASTER_ENCRYPTION_KEY', $keyHex);
        config(['app.master_encryption_key' => $keyHex]);
    }
    
    //Generate and save data encryption key
     
    private function generateAndSaveDataKey()
    {
        $key = Key::createNewRandomKey();
        $keyHex = $key->saveToAsciiSafeString();
        
        // Update .env file
        $this->updateEnvFile('DATA_ENCRYPTION_KEY', $keyHex);
        config(['app.data_encryption_key' => $keyHex]);
    }
    
    // Update env file with new key
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
    
    // Get master encryption key
    public function getMasterKey(): Key
    {
        return $this->masterKey;
    }
    
    // Get data encryption key
    public function getDataKey(): Key
    {
        return $this->dataKey;
    }

    // Rotate encryption keys
    public function rotateKeys()
    {
        $this->generateAndSaveMasterKey();
        $this->generateAndSaveDataKey();
        $this->initializeKeys();
        
        Log::info('Encryption keys rotated successfully');
    }
    
    // Key derivation for specific purposes
    public function deriveKey(string $purpose, string $context = ''): Key
    {
        $salt = hash('sha256', $purpose . $context);
        $derivedKeyMaterial = hash_pbkdf2('sha256', $this->masterKey->saveToAsciiSafeString(), $salt, 10000, 32, true);
        
        return Key::loadFromAsciiSafeString(base64_encode($derivedKeyMaterial));
    }

    // RSA key generation
    private function generateRsaKeyPair(): array
    {
        $sizes = [4096, 3072, 2048];
        $lastErrors = [];
        foreach ($sizes as $bits) {
            $cfg = [ 'private_key_bits' => $bits, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'digest_alg' => 'sha256' ];
            $res = @openssl_pkey_new($cfg);
            if ($res === false) {
                $errStrings = [];
                while ($e = openssl_error_string()) { $errStrings[] = $e; }
                $lastErrors[] = "bits {$bits}: ".implode('; ', $errStrings ?: ['unknown error']);
                continue;
            }
            if (!@openssl_pkey_export($res, $privPem)) {
                $errStrings = [];
                while ($e = openssl_error_string()) { $errStrings[] = $e; }
                $lastErrors[] = "export {$bits}: ".implode('; ', $errStrings ?: ['unknown error']);
                continue;
            }
            $details = openssl_pkey_get_details($res);
            if (!$details || empty($details['key'])) {
                $lastErrors[] = "details {$bits}: unable to retrieve public key"; continue;
            }
            $pub = $details['key'];
            $fp = hash('sha256', preg_replace('~-----[^-]+-----|\s~','',$pub));
            return ['public' => $pub, 'private' => $privPem, 'bits' => $bits, 'fingerprint' => $fp, 'engine' => 'openssl'];
        }

        try {
            if (class_exists('phpseclib\\Crypt\\RSA')) { 
                // ignore
            }
            if (class_exists('phpseclib3\\Crypt\\RSA')) {
                $rsa = \phpseclib3\Crypt\RSA::createKey(2048); // returns Object with toString()
                $priv = $rsa->toString('PKCS1');
                $pub = $rsa->getPublicKey()->toString('PKCS1');
                $fp = hash('sha256', preg_replace('~-----[^-]+-----|\s~','',$pub));
                return ['public' => $pub, 'private' => $priv, 'bits' => 2048, 'fingerprint' => $fp, 'engine' => 'phpseclib'];
            }
        } catch (\Exception $e) {
            $lastErrors[] = 'phpseclib: '.$e->getMessage();
        }
        Log::error('RSA key generation failed after fallbacks', ['attempts' => $lastErrors]);
        throw new \RuntimeException('RSA key generation failed: '.implode(' | ', $lastErrors));
    }
    
    // per-user RSA key pair management
    public function ensureUserKeyPair(User $user): KeyPair
    {
        // Check the database for an existing keypair
        if ($user->key_pair_id) {
            $existing = \App\Models\KeyPair::find($user->key_pair_id);
            if ($existing) {
                return $existing;
            }
        }
        $pair = $this->generateRsaKeyPair();
        // Collision check
        if (KeyPair::where('fingerprint',$pair['fingerprint'])->exists()) {
            $pair = $this->generateRsaKeyPair();
        }
        $kp = KeyPair::create([
            'user_id' => $user->id,
            'public_key' => $pair['public'],
            'private_key_encrypted' => Crypt::encryptString($pair['private']),
            'fingerprint' => $pair['fingerprint'],
            'algorithm' => 'RSA',
            'bits' => $pair['bits'],
            'key_version' => 1
        ]);
        $user->key_pair_id = $kp->id;
        $user->save();
        Log::info('Generated RSA key pair for user', ['user_id' => $user->id, 'bits' => $pair['bits'], 'engine' => $pair['engine']]);
        return $kp;
    }

    public function getPublicKey(User $user): ?string
    {
        return $user->keyPair?->public_key;
    }

    public function getPrivateKey(User $user): ?string
    {
        if (!$user->keyPair) return null;
        try {
            return Crypt::decryptString($user->keyPair->private_key_encrypted);
        } catch (\Exception $e) {
            Log::error('Private key decrypt failed: '.$e->getMessage());
            return null;
        }
    }

    public function rotateUserKeyPair(User $user, bool $rewrap = true): KeyPair
    {
        return DB::transaction(function () use ($user, $rewrap) {
            $plain = null;
            if ($rewrap) {
                try {
                    $enc = app(\App\Services\EncryptionService::class);
                    $plain = $enc->decryptUserInfoHybrid($user, [
                        'name'=>$user->name,
                        'email'=>$user->email,
                        'phone'=>$user->phone,
                        'address'=>$user->address,
                        'date_of_birth'=>$user->date_of_birth,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to decrypt before rotation; proceeding without rewrap', ['user_id'=>$user->id,'err'=>$e->getMessage()]);
                    $rewrap = false; // fallback
                }
            }
            $old = $user->keyPair;
            if ($old && !$old->revoked_at) { $old->revoked_at = now(); $old->save(); }
            $newPair = $this->generateRsaKeyPair();
            $kp = KeyPair::create([
                'user_id'=>$user->id,
                'public_key'=>$newPair['public'],
                'private_key_encrypted'=>Crypt::encryptString($newPair['private']),
                'fingerprint'=>$newPair['fingerprint'],
                'algorithm'=>'RSA',
                'bits'=>$newPair['bits'],
                'key_version'=>($old? ($old->key_version+1):1)
            ]);
            $user->key_pair_id = $kp->id; $user->save();
            if ($rewrap && $plain) {
                try {
                    $user->setEncryptedData($plain);
                    $user->save();
                    Log::info('Rewrapped user data under new key', ['user_id'=>$user->id,'key_version'=>$kp->key_version]);
                } catch (\Throwable $e) {
                    Log::error('Failed to rewrap user data after rotation', ['user_id'=>$user->id,'err'=>$e->getMessage()]);
                }
            }
            Log::info('Rotated user key pair', ['user_id'=>$user->id,'new_version'=>$kp->key_version,'rewrap'=>$rewrap]);
            return $kp;
        });
    }

    public function revokeUserKeyPair(User $user, bool $detach = false): void
    {
        $kp = $user->keyPair; if(!$kp) return; if(!$kp->revoked_at){ $kp->revoked_at = now(); $kp->save(); }
        if ($detach) { $user->key_pair_id = null; $user->save(); }
        Log::warning('Revoked user key pair', ['user_id'=>$user->id,'key_pair_id'=>$kp->id]);
    }
}
