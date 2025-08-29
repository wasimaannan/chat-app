<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\EncryptionService;
use App\Services\MACService;
use App\Services\KeyManagementService;

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
        'published_at',
        'wrapped_key',
        'iv',
        'tag'
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
        $enc = app(EncryptionService::class);
        
        try {
            // If hybrid fields present (wrapped_key etc.), decrypt manually
            if ($this->wrapped_key && $this->iv && $this->tag) {
                $priv = app(KeyManagementService::class)->getPrivateKey($this->user);
                if ($priv && openssl_private_decrypt(base64_decode($this->wrapped_key), $symKey, $priv, OPENSSL_PKCS1_OAEP_PADDING)) {
                    $title = $this->title ? $this->decryptField($this->title, $symKey, 'post_title') : '';
                    $content = $this->content ? $this->decryptField($this->content, $symKey, 'post_content') : '';
                    return ['title'=>$title,'content'=>$content,'encrypted_at'=>$this->created_at];
                }
            }
            return $enc->decryptPost(['title'=>$this->title,'content'=>$this->content,'encrypted_at'=>$this->created_at]);
        } catch (\Exception $e) {
            return ['title'=>'[ENCRYPTED]','content'=>'[ENCRYPTED]','encrypted_at'=>$this->created_at];
        }
    }

    /**
     * Set encrypted post data
     */
    public function setEncryptedData(string $title, string $content): void
    {
        // Use hybrid approach now
        $kms = app(KeyManagementService::class);
        if ($this->user) { $kms->ensureUserKeyPair($this->user); }
        $pub = $this->user ? $kms->getPublicKey($this->user) : null;
        if ($pub) {
            $symKey = random_bytes(32); $iv = random_bytes(12);
            $this->title = $title !== '' ? $this->encryptField($title, $symKey, 'post_title') : '';
            $this->content = $this->encryptField($content, $symKey, 'post_content');
            openssl_public_encrypt($symKey, $wrapped, $pub, OPENSSL_PKCS1_OAEP_PADDING);
            $this->wrapped_key = base64_encode($wrapped);
            $this->iv = ''; // per-field iv included in packed value; keep column for compatibility
            $this->tag = '';
        } else {
            // Fallback legacy
            $enc = app(EncryptionService::class);
            $data = $enc->encryptPost($content, $title);
            $this->title = $data['title']; $this->content = $data['content'];
        }
        if ($this->id) { $this->updateDataMAC(); }
    }

    private function encryptField(string $plain, string $symKey, string $aad): string
    {
        $iv = random_bytes(12);
        $cipher = openssl_encrypt($plain,'aes-256-gcm',$symKey,OPENSSL_RAW_DATA,$iv,$tag,$aad);
        return base64_encode($iv).':'.base64_encode($tag).':'.base64_encode($cipher);
    }
    private function decryptField(string $packed, string $symKey, string $aad): string
    {
        if (substr_count($packed, ':') !== 2) { // legacy
            try { return app(EncryptionService::class)->decrypt($packed, $aad); } catch (\Exception $e) { return '[ENCRYPTED]'; }
        }
        [$ivB,$tagB,$ctB] = explode(':',$packed);
        $iv=base64_decode($ivB); $tag=base64_decode($tagB); $ct=base64_decode($ctB);
        $plain = openssl_decrypt($ct,'aes-256-gcm',$symKey,OPENSSL_RAW_DATA,$iv,$tag,$aad);
        return $plain === false ? '[ENCRYPTED]' : $plain;
    }

    /**
     * Verify data integrity
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->data_mac)) { return true; }
        $macService = app(MACService::class);
        $postData = [ 'title'=>$this->title, 'content'=>$this->content, 'user_id'=>$this->user_id ];
        return $macService->verifyPostDataMAC($postData, $this->id, $this->data_mac);
    }

    /**
     * Update MAC after data changes
     */
    public function updateDataMAC(): void
    {
        $macService = app(MACService::class);
        $postData = [ 'title'=>$this->title, 'content'=>$this->content, 'user_id'=>$this->user_id ];
        $this->data_mac = $macService->generatePostDataMAC($postData, $this->id);
    }

    /**
     * Override save method to update MAC
     */
    public function save(array $options = [])
    {
        $needsMac = $this->isDirty(['title','content','user_id']);
        $result = parent::save($options);
        if ($this->id && (empty($this->data_mac) || $needsMac)) { $this->updateDataMAC(); parent::save(['timestamps'=>false]); }
        return $result;
    }

    /**
     * Get decrypted title
     */
    public function getDecryptedTitleAttribute(): string
    {
        try { return $this->getDecryptedData()['title'] ?? '[ENCRYPTED]'; } catch (\Exception $e) { return '[ENCRYPTED]'; }
    }
    /**
     * Get decrypted content
     */
    public function getDecryptedContentAttribute(): string
    {
        try { return $this->getDecryptedData()['content'] ?? '[ENCRYPTED]'; } catch (\Exception $e) { return '[ENCRYPTED]'; }
    }

    /**
     * Scope for published posts
     */
    public function scopePublished($q) { return $q->where('is_published', true); }
    /**
     * Scope for user's posts
     */
    public function scopeByUser($q, $userId) { return $q->where('user_id', $userId); }
}
