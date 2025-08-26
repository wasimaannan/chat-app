<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
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
                try { $snippet = app(\App\Services\EncryptionService::class)->decrypt($lastMsg->body_encrypted, 'message_body'); } catch (\Exception $e) { $snippet='[enc]'; }
                if (strlen($snippet) > 40) { $snippet = substr($snippet,0,40).'…'; }
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
        return response()->json(['users'=>$result]);
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
            try { $body = $enc->decrypt($m->body_encrypted, 'message_body'); } catch (\Exception $e) { $body='[enc]'; }
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
}
