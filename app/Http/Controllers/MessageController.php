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
    
    private function emitSignal(int $conversationId, int $fromUserId, string $type, array $payload = []): void
    {
        try {
            DB::table('call_signals')->insert([
                'conversation_id' => $conversationId,
                'from_user_id' => $fromUserId,
                'type' => $type,
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) { /* ignore */ }
    }
    // Get current authenticated user
    private function currentUser(Request $request)
    {
        $auth = \Illuminate\Support\Facades\Auth::user();
        if ($auth) return $auth;
        $attr = $request->attributes->get('authenticated_user');
        if ($attr) return $attr;
        if (session()->has('auth_token')) {
            try {
                $svc = app(\App\Services\CredentialCheckService::class);
                $validated = $svc->validateSessionToken(session('auth_token'));
                if ($validated) return $validated;
            } catch (\Throwable $e) { /* ignore */ }
        }
        if (session()->has('user_id')) {
            return User::find(session('user_id'));
        }
        return null;
    }
    // Inbox 
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

    // Conversation
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
            'image' => 'nullable|file|image|max:5120', // 5MB max
        ]);

        $msg = new Message();
        $msg->sender_id = Auth::id();
        $msg->receiver_id = $data['receiver_id'];
        $msg->setEncryptedBody($data['body']);
        if ($request->hasFile('image')) {
            $imgContent = base64_encode(file_get_contents($request->file('image')->getRealPath()));
            $msg->setEncryptedImage($imgContent);
        }
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

    public function chat()
    {
        return view('chat.index');
    }

    // List users 
    public function users(Request $request)
    {
        $auth = $this->currentUser($request);
        $selfId = $auth?->id ?? 0;
        $q = trim($request->query('q', ''));
        $query = User::query()->where('id', '!=', $selfId)->limit(40);
        if ($q !== '') {
            $query->orderBy('id');
        }
        $users = $query->get();
        $encSvc = app(EncryptionService::class);
        $result = [];
        foreach ($users as $u) {
            $name = null; $email = null;
            try {
                // Decrypt user information
                $decrypted = $encSvc->decryptUserInfoHybrid($u, ['name'=>$u->name,'email'=>$u->email]);
                $name = $decrypted['name'] ?? null;
                if ($q !== '') { $email = $decrypted['email'] ?? null; }
                \Log::debug('User decryption', ['user_id'=>$u->id, 'decrypted_name'=>$name, 'decrypted_email'=>$email]);
            } catch (\Throwable $e) {


                try { $name = $encSvc->decrypt($u->name, 'user_info_name'); } catch (\Throwable $e2) { $name = null; }
                if ($q !== '') { try { $email = $encSvc->decrypt($u->email, 'user_info_email'); } catch (\Throwable $e3) { $email = null; } }
                \Log::error('User decryption failed', ['user_id'=>$u->id, 'error'=>$e->getMessage()]);
            }
            if ($q !== '' && !str_contains(strtolower(($name ?? '').($email ?? '')), strtolower($q))) {
                continue;
            }
            $lastMsg = null; $unread = 0; $snippet = null;
            if ($auth) {
                $lastMsg = Message::where(function($x) use ($auth,$u){
                    $x->where('sender_id',$auth->id)->where('receiver_id',$u->id);
                })->orWhere(function($x) use ($auth,$u){
                    $x->where('sender_id',$u->id)->where('receiver_id',$auth->id);
                })->latest()->first();
                $unread = Message::where('sender_id',$u->id)->where('receiver_id',$auth->id)->whereNull('read_at')->count();
            }
            if ($lastMsg) {
                try {
                    $bodyCipher = $lastMsg->body;
                    if (substr_count($bodyCipher, ':') === 2) {
                        $snippet = $lastMsg->decrypted_body ?? null;
                        if (!$snippet) { throw new \RuntimeException('Accessor missing'); }
                    } else {
                        $snippet = $encSvc->decrypt($bodyCipher, 'message_body');
                    }
                } catch (\Throwable $e) {
                    try { $snippet = $encSvc->decrypt($lastMsg->body, 'message_body'); } catch (\Throwable $e2) { $snippet='[enc]'; }
                }
                if (is_string($snippet) && strlen($snippet) > 40) { $snippet = substr($snippet,0,40).'…'; }
            }
            $result[] = [
                'id' => $u->id,
                'name' => $name,
                'last_message' => $snippet,
                'unread' => $unread,
                'online' => true,
            ];
        }
        \Log::debug('chat.users', [ 'selfId'=>$selfId, 'q'=>$q, 'returned'=>count($result), 'total_users'=>User::count() ]);
        return response()->json(['users'=>$result,'meta'=>['self_id'=>$selfId,'total'=>User::count()]]);
    }

    // Conversation messages (JSON)
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
            // 'decrypt' the message body
            try { $body = $enc->decrypt($m->body, 'message_body'); } catch (\Exception $e) { $body='[enc]'; }
            $file_blob = null;
            $file_mime = null;
            if ($m->file_blob && $m->file_mime) {
                try {
                    $encService = app(\App\Services\EncryptionService::class);
                    $file_blob = base64_encode($encService->decrypt($m->file_blob, 'chat_file'));
                    $file_mime = $m->file_mime;
                } catch (\Throwable $e) { $file_blob = null; $file_mime = null; }
            }
            if ($m->receiver_id === $auth->id && !$m->read_at) { 
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
                'file_blob' => $file_blob,
                'file_mime' => $file_mime,
            ];
        }
        return response()->json(['messages'=>$payload]);
    }

    // Store (JSON variant)
    public function storeJson(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);
        $auth = $this->currentUser($request);
        if (!$auth) { return response()->json(['error' => 'Unauthenticated']); }
        $receiverId = (int)$request->input('receiver_id');
        if ($receiverId === $auth->id) {
            return response()->json(['error' => 'Cannot message yourself'], 422);
        }
        $msg = new Message();
        $msg->sender_id = $auth->id;
        $msg->receiver_id = $receiverId;
        $msg->setEncryptedBody($data['body']);
        $msg->save();
        $msg->updateDataMAC();
        $msg->save();
        return response()->json(['status' => 'ok', 'id' => $msg->id]);
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
    // Mark unread inbound messages as read now and emit seen signal
    $updated = \App\Models\Message::where('conversation_id',$conv->id)->where('receiver_id',$auth->id)->whereNull('read_at')->update(['read_at'=>now()]);
    if($updated>0){
        $lastSeenId = \App\Models\Message::where('conversation_id',$conv->id)->where('receiver_id',$auth->id)->max('id');
        if($lastSeenId){ $this->emitSignal($conv->id, $auth->id, 'seen', ['last_seen_id'=>$lastSeenId]); }
    }
        $messages = $conv->messages()->orderByDesc('id')->limit(60)->get()->sortBy('id')->values()->map(function($m) use ($auth){
            $plain = null; try { $plain = $this->encryptionService->decrypt($m->body,'message_body'); } catch(\Throwable $e){ $plain='[enc]'; }
            $file_blob = null; $file_mime = null;
            if ($m->file_blob && $m->file_mime) {
                try {
                    $encService = app(\App\Services\EncryptionService::class);
                    $file_blob = base64_encode($encService->decrypt($m->file_blob, 'chat_file'));
                    $file_mime = $m->file_mime;
                } catch (\Throwable $e) { $file_blob = null; $file_mime = null; }
            }
            return [
                'id'=>$m->id,
                'body'=>$plain,
                'is_me'=>$m->sender_id===$auth->id,
        'time'=>$m->created_at->format('H:i'),
        'read_at'=>$m->read_at ? $m->read_at->timestamp : null,
        'file_blob'=>$file_blob,
        'file_mime'=>$file_mime,
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
            'body'=>'nullable|string|max:4000',
            'file'=>'nullable|file|max:10240', // 10MB max
        ]);
        if (empty($data['body']) && !$request->hasFile('file')) {
            return response()->json(['error'=>'Message text or file required'], 422);
        }
        if(empty($data['conversation_id']) && empty($data['user_id'])) {
            return response()->json(['error'=>'target_missing'],422);
        }
        if(!empty($data['user_id']) && (int)$data['user_id']===$auth->id) {
            return response()->json([ 'error' => 'self' ], 422);
        }
        $conv = null;
        if(!empty($data['conversation_id'])) {
            $conv = Conversation::forUser($auth->id)->where('id',$data['conversation_id'])->firstOrFail();
        } else {
            $conv = $this->conversationService->findOrCreateDirect($auth->id,(int)$data['user_id']);
        }
        $plain = null;
        $file_blob = null;
        $file_mime = null;
        $resp_file_blob = null; // base64 for immediate response
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $mime = $file->getMimeType();
            $binary = file_get_contents($file->getRealPath());
            $encService = app(\App\Services\EncryptionService::class);
            $encrypted = $encService->encrypt($binary, 'chat_file');
            // Store file info in DB
            $plain = '[file] ' . $originalName . "\n" . $originalName;
            $file_blob = $encrypted;
            $file_mime = $mime;
            // Keep an immediate base64 copy for the response so client can display without extra request
            $resp_file_blob = base64_encode($binary);
        } else {
            $plain = trim($data['body'] ?? '');
        }
        if ($plain === '') {
            return response()->json(['error'=>'empty'],422);
        }
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
        $msg->file_blob = $file_blob;
        $msg->file_mime = $file_mime;
        $msg->save();
        $msg->updateDataMAC();
        $msg->save();
        return response()->json(['message'=>[
            'id'=>$msg->id,
            'body'=>$plain,
            'image'=>$msg->decrypted_image_base64 ?? null,
            'is_me'=>true,
            'time'=>$msg->created_at->format('H:i'),
            'read_at'=>null,
            'file_blob' => $resp_file_blob ?: ($file_blob ? true : false),
            'file_mime' => $file_mime,
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
        // Mark unread inbound and emit seen
        $updated = \App\Models\Message::where('conversation_id',$conv->id)->where('receiver_id',$auth->id)->whereNull('read_at')->update(['read_at'=>now()]);
        if($updated>0){
            $lastSeenId = \App\Models\Message::where('conversation_id',$conv->id)->where('receiver_id',$auth->id)->max('id');
            if($lastSeenId){ $this->emitSignal($conv->id, $auth->id, 'seen', ['last_seen_id'=>$lastSeenId]); }
        }
        $rows = $query->limit(120)->get()->map(function($m) use ($auth){
            $plain=null; try { $plain=$this->encryptionService->decrypt($m->body,'message_body'); } catch(\Throwable $e){ $plain='[enc]'; }
            $file_blob = null;
            $file_mime = null;
            if ($m->file_blob && $m->file_mime) {
                try {
                    $encService = app(\App\Services\EncryptionService::class);
                    $file_blob = base64_encode($encService->decrypt($m->file_blob, 'chat_file'));
                    $file_mime = $m->file_mime;
                } catch (\Throwable $e) { $file_blob = null; $file_mime = null; }
            }
            return [
                'id'=>$m->id,
                'body'=>$plain,
                'is_me'=>$m->sender_id===$auth->id,
                'time'=>$m->created_at->format('H:i'),
                'read_at'=>$m->read_at ? $m->read_at->timestamp : null,
                'file_blob'=>$file_blob,
                'file_mime'=>$file_mime,
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
    // Allow ephemeral typing + seen indicator events
    $allowed=['offer','answer','candidate','bye','typing','seen'];
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
