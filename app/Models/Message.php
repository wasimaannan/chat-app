<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\EncryptionService;
use App\Services\MACService;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id', 'receiver_id', 'body', 'data_mac', 'read_at'
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

    public function setEncryptedBody(string $plain): void
    {
        $encryptionService = app(EncryptionService::class);
        $this->body = $encryptionService->encrypt($plain, 'message_body');
        if ($this->id) {
            $this->updateDataMAC();
        }
    }

    public function getDecryptedBodyAttribute(): string
    {
        try {
            $enc = app(EncryptionService::class);
            return $enc->decrypt($this->body, 'message_body');
        } catch (\Exception $e) {
            return '[ENCRYPTED]';
        }
    }

    public function verifyIntegrity(): bool
    {
        if (empty($this->data_mac)) return true;
        $mac = app(MACService::class);
        $data = [
            'body' => $this->body,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
        ];
        return $mac->verifyMessageDataMAC($data, $this->id, $this->data_mac);
    }

    public function updateDataMAC(): void
    {
        $mac = app(MACService::class);
        $data = [
            'body' => $this->body,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
        ];
        $this->data_mac = $mac->generateMessageDataMAC($data, $this->id);
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);
        if ($this->id && $this->isDirty(['body'])) {
            $this->updateDataMAC();
            parent::save(['timestamps' => false]);
        }
        return $result;
    }
}
