<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Services\EncryptionService;
use App\Services\MACService;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
    'email_hash',
        'phone',
        'address',
        'date_of_birth',
        'password_hash',
        'password_salt',
        'data_mac',
        'email_verified_at',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'password_salt',
        'data_mac',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with posts
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get decrypted user data
     */
    public function getDecryptedData(): array
    {
        $encryptionService = app(EncryptionService::class);
        
        return $encryptionService->decryptUserInfo([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth,
        ]);
    }

    /**
     * Set encrypted user data
     */
    public function setEncryptedData(array $userData): void
    {
        $encryptionService = app(EncryptionService::class);
        $macService = app(MACService::class);
        
        // Encrypt user data
        $encryptedData = $encryptionService->encryptUserInfo($userData);
        
        // Set encrypted attributes
        $this->fill($encryptedData);

        // Maintain deterministic email hash for fast lookups (lowercase SHA-256 of decrypted email)
        if (!empty($userData['email'])) {
            $this->email_hash = hash('sha256', strtolower(trim($userData['email'])));
        }
        
        // Generate MAC for data integrity
        $this->data_mac = $macService->generateUserDataMAC($encryptedData, $this->id ?? 0);
    }

    /**
     * Verify data integrity
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->data_mac)) {
            return true; // Allow for backward compatibility
        }
        
        $macService = app(MACService::class);
        
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth
        ];
        
        return $macService->verifyUserDataMAC($userData, $this->id, $this->data_mac);
    }

    /**
     * Update MAC after data changes
     */
    public function updateDataMAC(): void
    {
        $macService = app(MACService::class);
        
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth
        ];
        
        $this->data_mac = $macService->generateUserDataMAC($userData, $this->id);
    }

    /**
     * Override save method to update MAC
     */
    public function save(array $options = [])
    {
        $result = parent::save($options);
        
        // Update MAC after save if ID is available
        if ($this->id && $this->isDirty(['name', 'email', 'phone', 'address', 'date_of_birth'])) {
            $this->updateDataMAC();
            parent::save(['timestamps' => false]);
        }
        
        return $result;
    }

    /**
     * Get user's full name (decrypted)
     */
    public function getFullNameAttribute(): string
    {
        try {
            $encryptionService = app(EncryptionService::class);
            return $encryptionService->decrypt($this->name, 'user_info_name');
        } catch (\Exception $e) {
            return '[ENCRYPTED]';
        }
    }

    /**
     * Get user's email (decrypted)
     */
    public function getEmailAddressAttribute(): string
    {
        try {
            $encryptionService = app(EncryptionService::class);
            return $encryptionService->decrypt($this->email, 'user_info_email');
        } catch (\Exception $e) {
            return '[ENCRYPTED]';
        }
    }
}
