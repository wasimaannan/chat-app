<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeyPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'public_key', 'private_key_encrypted', 'fingerprint', 'algorithm', 'bits', 'key_version', 'revoked_at'
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
        'bits' => 'integer',
        'key_version' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (KeyPair $kp) {
            if (empty($kp->fingerprint) && $kp->public_key) {
                $kp->fingerprint = hash('sha256', self::normalizePublicKey($kp->public_key));
            }
            if (empty($kp->algorithm)) { $kp->algorithm = 'RSA'; }
            if (empty($kp->bits)) { $kp->bits = 2048; }
            if (empty($kp->key_version)) { $kp->key_version = 1; }
        });
    }

    private static function normalizePublicKey(string $pem): string
    {
        return preg_replace('~-----[^-]+-----|\s~', '', $pem);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
