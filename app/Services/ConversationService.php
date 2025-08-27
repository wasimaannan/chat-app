<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function findOrCreateDirect(int $a, int $b): Conversation
    {
        if ($a === $b) throw new \InvalidArgumentException('Cannot create direct conversation with self');
        $ids = [$a,$b]; sort($ids);
        $existing = Conversation::where('type','direct')
            ->whereHas('participants', fn($q)=>$q->where('user_id',$ids[0]))
            ->whereHas('participants', fn($q)=>$q->where('user_id',$ids[1]))
            ->first();
        if ($existing) return $existing;
        return DB::transaction(function() use ($ids){
            $c = Conversation::create(['type'=>'direct','created_by'=>$ids[0]]);
            foreach ($ids as $uid) {
                ConversationParticipant::create([
                    'conversation_id'=>$c->id,
                    'user_id'=>$uid,
                    'role'=>'member','joined_at'=>now()
                ]);
            }
            return $c;
        });
    }
}
