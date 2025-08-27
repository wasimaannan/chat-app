<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    public $timestamps = false;
    protected $fillable = ['conversation_id','user_id','role','last_read_message_id','joined_at'];

    public function conversation(){ return $this->belongsTo(Conversation::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
