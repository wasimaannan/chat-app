<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\EncryptionService;
use App\Services\MACService;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'data_mac',
        'is_published',
        'published_at'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get decrypted post data
     */
    public function getDecryptedData(): array
    {
        $encryptionService = app(EncryptionService::class);
        
        try {
            return $encryptionService->decryptPost([
                'title' => $this->title,
                'content' => $this->content,
                'encrypted_at' => $this->created_at
            ]);
        } catch (\Exception $e) {
            return [
                'title' => '[ENCRYPTED]',
                'content' => '[ENCRYPTED]',
                'encrypted_at' => $this->created_at
            ];
        }
    }

    /**
     * Set encrypted post data
     */
    public function setEncryptedData(string $title, string $content): void
    {
        $encryptionService = app(EncryptionService::class);
        $macService = app(MACService::class);
        
        // Encrypt post data
        $encryptedData = $encryptionService->encryptPost($content, $title);
        
        // Set encrypted attributes
        $this->title = $encryptedData['title'];
        $this->content = $encryptedData['content'];
        
        // Generate MAC for data integrity (after save when ID is available)
        if ($this->id) {
            $this->updateDataMAC();
        }
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
        
        $postData = [
            'title' => $this->title,
            'content' => $this->content,
            'user_id' => $this->user_id
        ];
        
        return $macService->verifyPostDataMAC($postData, $this->id, $this->data_mac);
    }

    /**
     * Update MAC after data changes
     */
    public function updateDataMAC(): void
    {
        $macService = app(MACService::class);
        
        $postData = [
            'title' => $this->title,
            'content' => $this->content,
            'user_id' => $this->user_id
        ];
        
        $this->data_mac = $macService->generatePostDataMAC($postData, $this->id);
    }

    /**
     * Override save method to update MAC
     */
    public function save(array $options = [])
    {
        $needsMac = $this->isDirty(['title', 'content', 'user_id']);
        $result = parent::save($options);
        if ($this->id && (empty($this->data_mac) || $needsMac)) {
            $this->updateDataMAC();
            parent::save(['timestamps' => false]);
        }
        return $result;
    }

    /**
     * Get decrypted title
     */
    public function getDecryptedTitleAttribute(): string
    {
        try {
            $encryptionService = app(EncryptionService::class);
            return $encryptionService->decrypt($this->title, 'post_title');
        } catch (\Exception $e) {
            return '[ENCRYPTED]';
        }
    }

    /**
     * Get decrypted content
     */
    public function getDecryptedContentAttribute(): string
    {
        try {
            $encryptionService = app(EncryptionService::class);
            return $encryptionService->decrypt($this->content, 'post_content');
        } catch (\Exception $e) {
            return '[ENCRYPTED]';
        }
    }

    /**
     * Scope for published posts
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for user's posts
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
