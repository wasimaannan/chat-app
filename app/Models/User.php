<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Services\EncryptionService;
use App\Services\MACService;
use App\Services\KeyManagementService;

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
        'is_active',
        'wrapped_userinfo_key',
        'key_pair_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'password_salt',
        'data_mac',
        'remember_token',
        'wrapped_userinfo_key'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function (User $user) {
            app(KeyManagementService::class)->ensureUserKeyPair($user);
        });
    }

    /**
     * Relationship with posts
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Relationship with key pair
     */
    public function keyPair()
    {
        return $this->belongsTo(KeyPair::class);
    }

    /**
     * Get decrypted user data
     */
    public function getDecryptedData(): array
    {
        $encryptionService = app(EncryptionService::class);
        
        return $encryptionService->decryptUserInfoHybrid($this, [
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
        $kms = app(KeyManagementService::class);
        if ($this->id) { $kms->ensureUserKeyPair($this); }
        $encryptedData = $encryptionService->encryptUserInfoHybrid($this, $userData);
        $this->fill($encryptedData);
        if (!empty($userData['email'])) { $this->email_hash = hash('sha256', strtolower(trim($userData['email']))); }
        $this->data_mac = $macService->generateUserDataMAC($encryptedData, $this->id ?? 0);
    }

    /**
     * Verify data integrity
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->data_mac)) { return true; }
        $macService = app(MACService::class);
        $userData = [ 'name'=>$this->name,'email'=>$this->email,'phone'=>$this->phone,'address'=>$this->address,'date_of_birth'=>$this->date_of_birth ];
        return $macService->verifyUserDataMAC($userData, $this->id, $this->data_mac);
    }

    /**
     * Update MAC after data changes
     */
    public function updateDataMAC(): void
    {
        $macService = app(MACService::class);
        $userData = [ 'name'=>$this->name,'email'=>$this->email,'phone'=>$this->phone,'address'=>$this->address,'date_of_birth'=>$this->date_of_birth ];
        $this->data_mac = $macService->generateUserDataMAC($userData, $this->id);
    }

    /**
     * Override save method to update MAC
     */
    public function save(array $options = [])
    {
        $needsMac = $this->isDirty(['name','email','phone','address','date_of_birth']);
        $result = parent::save($options);
        if ($this->id && (empty($this->data_mac) || $needsMac)) {
            $this->updateDataMAC();
            parent::save(['timestamps'=>false]);
        }
        return $result;
    }

    /**
     * Get user's full name (decrypted)
     */
    public function getFullNameAttribute(): string
    {
        try { return $this->getDecryptedData()['name'] ?? '[ENCRYPTED]'; } catch (\Exception $e) { return '[ENCRYPTED]'; }
    }

    /**
     * Get user's email (decrypted)
     */
    public function getEmailAddressAttribute(): string
    {
        try { return $this->getDecryptedData()['email'] ?? '[ENCRYPTED]'; } catch (\Exception $e) { return '[ENCRYPTED]'; }
    }
}
