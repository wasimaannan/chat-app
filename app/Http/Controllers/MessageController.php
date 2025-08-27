<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Conversation;
use App\Services\ConversationService;
use App\Services\EncryptionService;
use App\Services\MACService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    private ConversationService $conversationService;
    private EncryptionService $encryptionService;
    private MACService $macService;

    public function __construct(ConversationService $conversationService, EncryptionService $encryptionService, MACService $macService)
    {
        $this->conversationService = $conversationService;
        $this->encryptionService = $encryptionService;
        $this->macService = $macService;
    }
    /**
     * Resolve the currently authenticated user using multiple fallbacks.
     */
    private function currentUser(Request $request)
    {
        $auth = \Illuminate\Support\Facades\Auth::user();
        if ($auth) return $auth;
        $attr = $request->attributes->get('authenticated_user');
        if ($attr) return $attr;
        // Try session token validation (custom auth scheme)
        if (session()->has('auth_token')) {
            try {
                $svc = app(\App\Services\CredentialCheckService::class);
                $validated = $svc->validateSessionToken(session('auth_token'));
                if ($validated) return $validated;
            } catch (\Throwable $e) { /* ignore */ }
        }
        // Direct user_id lookup fallback
        if (session()->has('user_id')) {
            return User::find(session('user_id'));
        }
        return null;
    }
    // Inbox (messages received)
    public function index()
    {
        $user = Auth::user();
        $messages = Message::where('receiver_id', $user->id)
            ->latest()
            ->with('sender')
            ->paginate(20);
        return view('messages.inbox', compact('messages'));
    }

    // Sent messages
    public function sent()
    {
        $user = Auth::user();
        $messages = Message::where('sender_id', $user->id)
            ->latest()
            ->with('receiver')
            ->paginate(20);
        return view('messages.sent', compact('messages'));
    }

    // Conversation with a user
    public function conversation($userId, Request $request)
    {
        $auth = $this->currentUser($request);
        if (!$auth) { abort(401, 'Unauthenticated'); }
        $other = User::findOrFail($userId);
        $messages = Message::where(function($q) use ($auth, $other) {
                $q->where('sender_id', $auth->id)->where('receiver_id', $other->id);
            })->orWhere(function($q) use ($auth, $other) {
                $q->where('sender_id', $other->id)->where('receiver_id', $auth->id);
            })
            ->orderBy('created_at')
            ->get();

        return view('messages.conversation', compact('messages', 'other'));
    }

    // Store new message
    public function store(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => 'required|exists:users,id|different:sender_id',
            'body' => 'required|string|max:5000',
        ]);

        $msg = new Message();
        $msg->sender_id = Auth::id();
        $msg->receiver_id = $data['receiver_id'];
        $msg->setEncryptedBody($data['body']);
        $msg->save();
        $msg->updateDataMAC();
        $msg->save();

        return redirect()->back()->with('status', 'Message sent');
    }

    // Mark message as read
    public function read(Message $message)
    {
        $this->authorizeAccess($message);
        if (!$message->read_at && $message->receiver_id === Auth::id()) {
            $message->read_at = now();
            $message->save();
        }
        return redirect()->back();
    }

    private function authorizeAccess(Message $message)
    {
        if (!in_array(Auth::id(), [$message->sender_id, $message->receiver_id])) {
            abort(403);
        }
    }

    // New chat UI root view
    public function chat()
    {
        return view('chat.index');
    }

    // List users with last message snippet + unread count (JSON)
    public function users(Request $request)
    {
        $auth = $this->currentUser($request);
        $selfId = $auth?->id ?? 0;
        $q = trim($request->query('q', ''));
        $query = User::query()->where('id', '!=', $selfId)->limit(40);
        if ($q !== '') {
            // Very limited: decrypt each candidate name/email lazily (simple approach for now)
            $query->orderBy('id');
        }
        $users = $query->get();

        $result = [];
        foreach ($users as $u) {
            $name = null;
            try {
                $name = app(\App\Services\EncryptionService::class)->decrypt($u->name, 'user_info_name');
            } catch (\Exception $e) {}
            $email = null;
            if ($q !== '') {
                try { $email = app(\App\Services\EncryptionService::class)->decrypt($u->email, 'user_info_email'); } catch (\Exception $e) {}
                if ($q !== '' && !str_contains(strtolower($name.$email), strtolower($q))) {
                    continue; // skip if not matching search
                }
            }
            $lastMsg = null; $unread = 0;
            if ($auth) {
                $lastMsg = Message::where(function($x) use ($auth,$u){
                    $x->where('sender_id',$auth->id)->where('receiver_id',$u->id);
                })->orWhere(function($x) use ($auth,$u){
                    $x->where('sender_id',$u->id)->where('receiver_id',$auth->id);
                })->latest()->first();
                $unread = Message::where('sender_id',$u->id)->where('receiver_id',$auth->id)->whereNull('read_at')->count();
            }
            $snippet = null;
            if ($lastMsg) {
                // Encrypted payload stored in 'body' (not body_encrypted); broad catch to avoid TypeError fatal
                try { $snippet = app(\App\Services\EncryptionService::class)->decrypt($lastMsg->body, 'message_body'); }
                catch (\Throwable $e) { $snippet='[enc]'; }
                if (is_string($snippet) && strlen($snippet) > 40) { $snippet = substr($snippet,0,40).'…'; }
            }
            $result[] = [
                'id' => $u->id,
                'name' => $name,
                'last_message' => $snippet,
                'unread' => $unread,
                // Placeholder presence (could be replaced with real-time presence service)
                'online' => true,
            ];
        }
        \Log::debug('chat.users', [
            'selfId'=>$selfId,
            'q'=>$q,
            'returned'=>count($result),
            'total_users'=>User::count()
        ]);
        return response()->json(['users'=>$result,'meta'=>['self_id'=>$selfId,'total'=>User::count()]]);
    }

    // Conversation messages (incremental if after=id provided)
    public function conversationJson($userId, Request $request)
    {
        $auth = $this->currentUser($request);
        if (!$auth) { return response()->json(['messages'=>[]]); }
        $other = User::findOrFail($userId);
        if ($other->id === $auth->id) {
            return response()->json(['messages'=>[]]);
        }
        $after = (int)$request->query('after', 0);
        $query = Message::where(function($q) use ($auth, $other) {
                $q->where('sender_id', $auth->id)->where('receiver_id', $other->id);
            })->orWhere(function($q) use ($auth, $other) {
                $q->where('sender_id', $other->id)->where('receiver_id', $auth->id);
            });
        if ($after > 0) {
            $query->where('id', '>', $after);
        }
        $messages = $query->orderBy('id')->limit(200)->get();

        $enc = app(\App\Services\EncryptionService::class);
        $payload = [];
        $lastDate = null;
        foreach ($messages as $m) {
            // 'body' stores the ciphertext for messages (no body_encrypted column)
            try { $body = $enc->decrypt($m->body, 'message_body'); } catch (\Exception $e) { $body='[enc]'; }
            if ($m->receiver_id === $auth->id && !$m->read_at) { // mark read lazily
                $m->read_at = now();
                $m->save();
            }
            $day = $m->created_at->toDateString();
            if ($day !== $lastDate) {
                $payload[] = [
                    'type' => 'separator',
                    'label' => $m->created_at->format('M j, Y')
                ];
                $lastDate = $day;
            }
            $payload[] = [
                'type' => 'message',
                'id' => $m->id,
                'body' => $body,
                'is_me' => $m->sender_id === $auth->id,
                'time' => $m->created_at->format('H:i'),
                'read_at' => $m->read_at ? $m->read_at->timestamp : null,
            ];
        }
        return response()->json(['messages'=>$payload]);
    }

    // Store (JSON variant)
    public function storeJson(Request $request)
    {
        // Validation: previous version incorrectly used different:receiver_id (always fails comparing field to itself)
        $data = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);
    $auth = $this->currentUser($request);
    if (!$auth) { return response()->json(['error'=>'Unauthenticated']); }
    $receiverId = (int)$request->input('receiver_id');
    if ($receiverId === $auth->id) {
            return response()->json(['error'=>'Cannot message yourself'], 422);
        }
        $msg = new Message();
    $msg->sender_id = $auth->id;
        $msg->receiver_id = $receiverId;
        $msg->setEncryptedBody($data['body']);
        $msg->save();
        $msg->updateDataMAC();
        $msg->save();
        return response()->json(['status'=>'ok','id'=>$msg->id]);
    }

    // === New Conversation-based API ===
    public function openDirect(Request $request)
    {
        $auth = $this->currentUser($request);
        if(!$auth) return response()->json(['error'=>'unauth'],401);
        $data = $request->validate(['user_id'=>'required|integer|exists:users,id']);
        $other = (int)$data['user_id'];
        if($other === $auth->id) return response()->json(['error'=>'self'],422);
        $conv = $this->conversationService->findOrCreateDirect($auth->id, $other);
        $messages = $conv->messages()->orderByDesc('id')->limit(60)->get()->sortBy('id')->values()->map(function($m) use ($auth){
            $plain = null; try { $plain = $this->encryptionService->decrypt($m->body,'message_body'); } catch(\Throwable $e){ $plain='[enc]'; }
            return [
                'id'=>$m->id,
                'body'=>$plain,
                'is_me'=>$m->sender_id===$auth->id,
                'time'=>$m->created_at->format('H:i')
            ];
        });
        return response()->json(['conversation_id'=>$conv->id,'messages'=>$messages]);
    }

    public function send(Request $request)
    {
        $auth = $this->currentUser($request);
        if(!$auth) return response()->json(['error'=>'unauth'],401);
        $data = $request->validate([
            'conversation_id'=>'nullable|integer|exists:conversations,id',
            'user_id'=>'nullable|integer|exists:users,id',
            'body'=>'required|string|max:4000'
        ]);
        if(empty($data['conversation_id']) && empty($data['user_id'])) {
            return response()->json(['error'=>'target_missing'],422);
        }
        if(!empty($data['user_id']) && (int)$data['user_id']===$auth->id) {
            return response()->json(['error'=>'self'],422);
        }
        $conv = null;
        if(!empty($data['conversation_id'])) {
            $conv = Conversation::forUser($auth->id)->where('id',$data['conversation_id'])->firstOrFail();
        } else {
            $conv = $this->conversationService->findOrCreateDirect($auth->id,(int)$data['user_id']);
        }
        $plain = trim($data['body']);
        $cipher = $this->encryptionService->encrypt($plain, 'message_body');
        $msg = new Message();
        $msg->conversation_id = $conv->id;
        $msg->sender_id = $auth->id;
        // Maintain legacy receiver_id (first other participant) for direct conversations so existing queries & NOT NULL constraint work
        $receiverId = null;
        if ($conv->type === 'direct') {
            $other = $conv->participants()->where('user_id','!=',$auth->id)->first();
            if ($other) { $receiverId = $other->user_id; }
        }
        if (!$receiverId) { // Fallback: keep self to avoid null constraint, though should not happen
            $receiverId = $auth->id; 
        }
        $msg->receiver_id = $receiverId;
        $msg->body = $cipher;
        $msg->save();
        $msg->updateDataMAC();
        $msg->save();
        return response()->json(['message'=>[
            'id'=>$msg->id,
            'body'=>$plain,
            'is_me'=>true,
            'time'=>$msg->created_at->format('H:i')
        ],'conversation_id'=>$conv->id]);
    }

    public function messages(Request $request, $id)
    {
        $auth = $this->currentUser($request);
        if(!$auth) return response()->json(['error'=>'unauth'],401);
        $after = (int)$request->query('after',0);
        $conv = Conversation::forUser($auth->id)->where('id',$id)->firstOrFail();
        $query = $conv->messages()->orderBy('id');
        if($after>0) $query->where('id','>',$after);
        $rows = $query->limit(120)->get()->map(function($m) use ($auth){
            $plain=null; try { $plain=$this->encryptionService->decrypt($m->body,'message_body'); } catch(\Throwable $e){ $plain='[enc]'; }
            return [
                'id'=>$m->id,
                'body'=>$plain,
                'is_me'=>$m->sender_id===$auth->id,
                'time'=>$m->created_at->format('H:i')
            ];
        });
        return response()->json(['messages'=>$rows]);
    }

    // --- WebRTC signaling endpoints ---
    public function signal(Request $request, $id)
    {
        $auth = $this->currentUser($request); if(!$auth) return response()->json(['error'=>'unauth'],401);
        $conv = Conversation::forUser($auth->id)->where('id',$id)->firstOrFail();
        $data = $request->validate([
            'type'=>'required|string|max:32',
            'payload'=>'nullable|array'
        ]);
        $allowed=['offer','answer','candidate','bye'];
        if(!in_array($data['type'],$allowed)) return response()->json(['error'=>'type'],422);
        DB::table('call_signals')->insert([
            'conversation_id'=>$conv->id,
            'from_user_id'=>$auth->id,
            'type'=>$data['type'],
            'payload'=>json_encode($data['payload'] ?? []),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
        if(random_int(1,25)===13){
            DB::table('call_signals')->where('created_at','<',now()->subMinutes(15))->delete();
        }
        return response()->json(['status'=>'ok']);
    }

    public function fetchSignals(Request $request, $id)
    {
        $auth = $this->currentUser($request); if(!$auth) return response()->json(['error'=>'unauth'],401);
        $conv = Conversation::forUser($auth->id)->where('id',$id)->firstOrFail();
        $after=(int)$request->query('after',0);
        $rows = DB::table('call_signals')->where('conversation_id',$conv->id)
            ->when($after>0, fn($q)=>$q->where('id','>',$after))
            ->orderBy('id')->limit(60)->get()->map(function($r){
                return [
                    'id'=>$r->id,
                    'from_user_id'=>$r->from_user_id,
                    'type'=>$r->type,
                    'payload'=>json_decode($r->payload ?: '{}', true)
                ];
            });
        return response()->json(['signals'=>$rows]);
    }
}
