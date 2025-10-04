<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Services\EncryptionService;
use App\Services\MACService;
use App\Services\KeyManagementService;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    
    protected $fillable = [
        'name',
        'email',
        'email_hash',
        'phone',
        'address',
        'date_of_birth',
        'bio',
        'profile_picture',
        'profile_picture_mime',
        'password_hash',
        'password_salt',
        'data_mac',
        'email_verified_at',
        'is_active',
        'wrapped_userinfo_key',
        'key_pair_id'
    ];


    //decrypt profile picture

    public function getDecryptedProfilePictureBase64(): ?array
    {
        if (!$this->profile_picture) return null;
        $encryptionService = app(\App\Services\EncryptionService::class);
        try {
            $binary = $encryptionService->decrypt($this->profile_picture, 'profile_picture');
            $mime = $this->profile_picture_mime ?: 'image/jpeg';
            return [
                'base64' => base64_encode($binary),
                'mime' => $mime
            ];
        } catch (\Exception $e) {
            \Log::error('Profile picture decryption failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    //protected $hidden = [
    //    'profile_picture',
    //    'profile_picture_mime',
    //];
    protected $hidden = [
        'password_hash',
        'password_salt',
        'data_mac',
        'remember_token',
        'wrapped_userinfo_key'
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];


    // call to generate user key pair

    protected static function boot()
    {
        parent::boot();

        static::created(function (User $user) {
            app(KeyManagementService::class)->ensureUserKeyPair($user);
        });
    }

    // Relationships
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    
    public function keyPair()
    {
        return $this->belongsTo(KeyPair::class);
    }

    // decryption of profile
    // public function getDecryptedData(): array
    // {
    //     $encryptionService = app(EncryptionService::class);
    //     $fields = [
    //         'name' => $this->name,
    //         'email' => $this->email,
    //         'phone' => $this->phone,
    //         'address' => $this->address,
    //         'date_of_birth' => $this->date_of_birth,
    //         'bio' => $this->bio,
    //     ];
    //     try {
    //         $decrypted = $encryptionService->decryptUserInfoHybrid($this, $fields);
    //     } catch (\Exception $e) {
    //         \Log::error('Decryption failed for user fields', ['user_id' => $this->id, 'exception' => $e->getMessage(), 'fields' => $fields]);
    //         // fallback: set all fields to null
    //         $decrypted = [];
    //         foreach (array_keys($fields) as $key) {
    //             $decrypted[$key] = null;
    //         }
    //     }
    //     return $decrypted;
    // }
    public function getDecryptedData(): array
    {
        $encryptionService = app(EncryptionService::class);
        $attrs = $this->getAttributes();
        $decrypted = $encryptionService->decryptUserInfoHybrid($this, [
            'name' => $attrs['name'] ?? null,
            'email' => $attrs['email'] ?? null,
            'phone' => $attrs['phone'] ?? null,
            'address' => $attrs['address'] ?? null,
            'date_of_birth' => $attrs['date_of_birth'] ?? null,
        ]);
        // Decrypt bio if present
        $bio = $attrs['bio'] ?? null;
        if ($bio) {
            try {
                $bio = $encryptionService->decrypt($bio, 'user_bio');
            } catch (\Exception $e) {
                $bio = '[ENCRYPTED]';
            }
        }
        $decrypted['bio'] = $bio;
        return $decrypted;
    }
    //set encrypted data

    public function setEncryptedData(array $userData, $profilePicEncrypted = null, $profilePicMime = null): void
    {
        $encryptionService = app(EncryptionService::class);
        $macService = app(MACService::class);
        $kms = app(KeyManagementService::class);
        if ($this->id) { $kms->ensureUserKeyPair($this); }
        $userDataForEncryption = $userData;
        unset($userDataForEncryption['bio']);
        $encryptedData = $encryptionService->encryptUserInfoHybrid($this, $userDataForEncryption);
        $this->fill($encryptedData);
        if (isset($userData['bio']) && $userData['bio'] !== '') {
            $this->bio = $encryptionService->encrypt($userData['bio'], 'user_bio');
        } else {
            $this->bio = '';
        }
        // Set profile_picture 
        if ($profilePicEncrypted !== null) {
            $this->profile_picture = $profilePicEncrypted;
        }
        // store mime 
        if ($profilePicMime !== null) {
            $this->profile_picture_mime = $profilePicMime;
        }
        if (!empty($userData['email'])) { $this->email_hash = hash('sha256', strtolower(trim($userData['email']))); }
        $this->data_mac = $macService->generateUserDataMAC($encryptedData, $this->id ?? 0);
    }

    // data integrity MAC

    public function verifyIntegrity(): bool
    {
        if (empty($this->data_mac)) { return true; }
        $macService = app(MACService::class);
        $userData = [ 'name'=>$this->name,'email'=>$this->email,'phone'=>$this->phone,'address'=>$this->address,'date_of_birth'=>$this->date_of_birth ];
        return $macService->verifyUserDataMAC($userData, $this->id, $this->data_mac);
    }

    // Update MAC

    public function updateDataMAC(): void
    {
        $macService = app(MACService::class);
        $userData = [ 'name'=>$this->name,'email'=>$this->email,'phone'=>$this->phone,'address'=>$this->address,'date_of_birth'=>$this->date_of_birth ];
        $this->data_mac = $macService->generateUserDataMAC($userData, $this->id);
    }

   

    public function save(array $options = [])
    {
        // If a legacy/alternate attribute was set by other code (profile_picture_encrypted)
        // and the database does not have that column, move it into the canonical
        // `profile_picture` attribute so Eloquent won't attempt to update a missing column.
        if (array_key_exists('profile_picture_encrypted', $this->attributes) && !Schema::hasColumn('users', 'profile_picture_encrypted')) {
            $this->attributes['profile_picture'] = $this->attributes['profile_picture_encrypted'];
            unset($this->attributes['profile_picture_encrypted']);
        }

        \Log::info('User::save attributes before DB write', [
            'user_id' => $this->id,
            'profile_picture_set' => isset($this->attributes['profile_picture']),
            'profile_picture_length' => isset($this->attributes['profile_picture']) ? strlen($this->attributes['profile_picture']) : null,
            'profile_picture_mime' => $this->attributes['profile_picture_mime'] ?? null,
        ]);

        $needsMac = $this->isDirty(['name','email','phone','address','date_of_birth']);
        $result = parent::save($options);
        if ($this->id && (empty($this->data_mac) || $needsMac)) {
            $this->updateDataMAC();
            parent::save(['timestamps'=>false]);
        }
        return $result;
    }

    //fetch the decrypted data 

    public function getFullNameAttribute(): string
    {
        try { return $this->getDecryptedData()['name'] ?? '[ENCRYPTED]'; } catch (\Exception $e) { return '[ENCRYPTED]'; }
    }

    public function getEmailAddressAttribute(): string
    {
        try { return $this->getDecryptedData()['email'] ?? '[ENCRYPTED]'; } catch (\Exception $e) { return '[ENCRYPTED]'; }
    }
}
