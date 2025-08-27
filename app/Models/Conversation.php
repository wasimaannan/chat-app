<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['type','created_by','title_enc','title_mac'];

    public function participants() { return $this->hasMany(ConversationParticipant::class); }
    public function messages() { return $this->hasMany(Message::class)->orderBy('created_at'); }
    public function scopeForUser($q, int $userId) { return $q->whereHas('participants', fn($p)=>$p->where('user_id',$userId)); }
}
