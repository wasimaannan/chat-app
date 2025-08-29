<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\EncryptionService;
use App\Services\MACService;
use App\Services\KeyManagementService;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id','sender_id','receiver_id','body','data_mac','read_at','wrapped_key','iv','tag'
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function setEncryptedBody(string $plain): void
    {
        $kms = app(KeyManagementService::class);
        $user = $this->sender ?? $this->sender()->first();
        if ($user) { $kms->ensureUserKeyPair($user); }
        $pub = $user ? $kms->getPublicKey($user) : null;
        if ($pub) {
            $symKey = random_bytes(32); $iv = random_bytes(12);
            $this->body = $this->encryptField($plain, $symKey, 'message_body');
            openssl_public_encrypt($symKey, $wrapped, $pub, OPENSSL_PKCS1_OAEP_PADDING);
            $this->wrapped_key = base64_encode($wrapped);
            $this->iv = ''; $this->tag='';
        } else {
            $enc = app(EncryptionService::class);
            $this->body = $enc->encrypt($plain, 'message_body');
        }
        if ($this->id) { $this->updateDataMAC(); }
    }

    private function encryptField(string $plain, string $symKey, string $aad): string
    {
        $iv = random_bytes(12); $cipher = openssl_encrypt($plain,'aes-256-gcm',$symKey,OPENSSL_RAW_DATA,$iv,$tag,$aad);
        return base64_encode($iv).':'.base64_encode($tag).':'.base64_encode($cipher);
    }
    private function decryptField(string $packed, string $symKey, string $aad): string
    {
        if (substr_count($packed, ':') !== 2) { try { return app(EncryptionService::class)->decrypt($packed, $aad); } catch (\Exception $e) { return '[ENCRYPTED]'; } }
        [$ivB,$tagB,$ctB] = explode(':',$packed);
        $iv=base64_decode($ivB); $tag=base64_decode($tagB); $ct=base64_decode($ctB);
        $plain = openssl_decrypt($ct,'aes-256-gcm',$symKey,OPENSSL_RAW_DATA,$iv,$tag,$aad);
        return $plain === false ? '[ENCRYPTED]' : $plain;
    }

    public function getDecryptedBodyAttribute(): string
    {
        try {
            if ($this->wrapped_key) {
                $priv = app(KeyManagementService::class)->getPrivateKey($this->sender ?: $this->sender()->first());
                if ($priv && openssl_private_decrypt(base64_decode($this->wrapped_key), $symKey, $priv, OPENSSL_PKCS1_OAEP_PADDING)) {
                    return $this->decryptField($this->body, $symKey, 'message_body');
                }
            }
            $enc = app(EncryptionService::class);
            return $enc->decrypt($this->body, 'message_body');
        } catch (\Exception $e) { return '[ENCRYPTED]'; }
    }

    public function verifyIntegrity(): bool
    {
        if (empty($this->data_mac)) return true;
        $mac = app(MACService::class);
        $data = [ 'body'=>$this->body, 'sender_id'=>$this->sender_id, 'receiver_id'=>$this->receiver_id ];
        return $mac->verifyMessageDataMAC($data, $this->id, $this->data_mac);
    }

    public function updateDataMAC(): void
    {
        $mac = app(MACService::class);
        $data = [ 'body'=>$this->body, 'sender_id'=>$this->sender_id, 'receiver_id'=>$this->receiver_id ];
        $this->data_mac = $mac->generateMessageDataMAC($data, $this->id);
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);
        if ($this->id && $this->isDirty(['body'])) { $this->updateDataMAC(); parent::save(['timestamps'=>false]); }
        return $result;
    }
}
