@extends('layout')

@section('title','chatty_cat')

@section('content')
<style>
/* Chat bubble styling */
.msg-bubble {\n    padding:.55rem .75rem;\n    border-radius:16px;\n    background:#1d2534;\n    color:#e6ebf3;\n    font-size:.95rem;\n    line-height:1.3;\n    position:relative;\n    word-break:break-word;\n    box-shadow:0 2px 4px -2px rgba(0,0,0,.4);\n    transition:background .25s ease, transform .15s ease;\n}
.msg-bubble.other {\n    background:#1e2738;\n    border:1px solid #253042;\n}
.msg-bubble.me {\n    background:linear-gradient(135deg,#ff8a5c,#ff5f7e);\n    color:#fff;\n    box-shadow:0 4px 14px -4px rgba(255,101,112,.55);\n    border:1px solid rgba(255,255,255,.15);\n}
.msg-bubble.me .small {\n    color:rgba(255,255,255,.8)!important;\n}
.msg-bubble.other .small {\n    color:rgba(255,255,255,.55)!important;\n}
.msg-bubble:hover {\n    transform:translateY(-2px);\n}
/* Subtle tail using pseudo elements */
.msg-bubble.me:after, .msg-bubble.other:after {\n    content:"";\n    position:absolute;\n    bottom:0;\n    width:12px;\n    height:12px;\n    transform:translateY(45%) rotate(45deg);\n    border-radius:2px;\n}
.msg-bubble.me:after {\n    right:6px;\n    background:linear-gradient(135deg,#ff8a5c,#ff5f7e);\n}
.msg-bubble.other:after {\n    left:6px;\n    background:#1e2738;\n}
@media (prefers-reduced-motion:reduce){ .msg-bubble:hover{transform:none;} }
</style>
<div id="chat-app" class="row g-0" style="min-height:72vh;border-radius:18px;overflow:hidden;background:#1b2033;box-shadow:0 4px 18px -6px rgba(0,0,0,.5);">
    <div class="col-12 col-md-4 col-lg-3 d-flex flex-column" style="background:#161b29;">
        <div class="p-3 border-bottom d-flex align-items-center">
            <h6 class="mb-0 flex-grow-1 text-uppercase small fw-bold tracking-wide">Chats</h6>
            <button id="refreshList" class="btn btn-sm btn-outline-light" title="Refresh"><i class="fas fa-rotate"></i></button>
        </div>
        <div class="p-2">
            <input id="userSearch" type="text" class="form-control form-control-sm" placeholder="Search users...">
        </div>
        <ul id="conversationList" class="list-unstyled mb-0 flex-grow-1" style="overflow-y:auto;">
            <!-- filled dynamically -->
        </ul>
        <div class="p-2 border-top small text-muted">Showing <span id="convCount">0</span> users</div>
    </div>
    <div class="col-12 col-md-8 col-lg-9 d-flex flex-column" style="background:#121622;">
        <div id="conversationHeader" class="p-3 border-bottom" style="min-height:62px;">
            <div class="d-flex align-items-center gap-3">
                <div id="avatarCircle" class="rounded-circle d-flex justify-content-center align-items-center" style="width:42px;height:42px;background:#222a3d;font-weight:600;font-size:1rem;">?</div>
                <div class="flex-grow-1">
                    <div id="conversationTitle" class="fw-semibold">Select a user</div>
                    <div id="conversationMeta" class="cc-small"></div>
                </div>
                <span id="unreadBadge" class="badge bg-danger d-none"></span>
            </div>
        </div>
        <div id="messagesPane" class="flex-grow-1 px-3 py-2" style="overflow-y:auto;">
            <div id="messagesContent">
                <div class="text-muted small py-5 text-center">No conversation selected. Pick a user on the left.</div>
            </div>
            <div id="typingBanner" class="small text-info d-none mt-1" style="opacity:.85;">Typing…</div>
        </div>
        <form id="messageForm" class="p-2 d-flex gap-2 align-items-end border-top" autocomplete="off" style="background:#161b29;">
            <input type="hidden" id="receiverId" name="receiver_id" value="">
            <textarea id="messageBody" name="body" class="form-control" rows="1" placeholder="Type a message..." disabled style="resize:none;max-height:160px;background:#121826;border:1px solid #283248;color:#fff;"></textarea>
            <button id="sendBtn" class="btn btn-primary d-flex align-items-center justify-content-center" style="width:48px;height:48px;" type="submit" disabled>
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Basic polling chat client + experimental WebRTC data channel signaling (poll-based)
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let currentPartnerUserId = null; // selected user id
let currentConversationId = null; // active conversation id
let pollHandle = null;
let lastMessageId = 0;
let selfUserId = null; // populated from /chat/users meta
// WebRTC globals
let rtcPeer = null; let rtcChannel = null; let rtcReady=false; let rtcInit=false; let signalAfter=0; let lastOutgoingId=0;

async function fetchJSON(url, opts = {}) {
    const res = await fetch(url, Object.assign({ headers:{ 'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':csrfToken }}, opts));
    if(!res.ok) throw new Error('HTTP '+res.status);
    const ct = res.headers.get('content-type')||'';
    if(!ct.includes('application/json')) {
        throw new Error('Non-JSON response (possible session timeout)');
    }
    return res.json();
}

function renderConversationList(items) {
    const ul = document.getElementById('conversationList');
    ul.innerHTML = '';
    if(items.length === 0){
        ul.innerHTML = '<li class="text-muted small px-3 py-2">No other users yet. Log out and register a second account in another browser to start chatting.</li>';
        document.getElementById('convCount').textContent = 0;
        return;
    }
    document.getElementById('convCount').textContent = items.length;
    items.forEach(it => {
        const li = document.createElement('li');
        li.className = 'px-3 py-2 conversation-item d-flex align-items-center gap-2';
        li.style.cursor='pointer';
    if(currentPartnerUserId === it.id) li.classList.add('bg-secondary');
        li.innerHTML = `<div class="flex-grow-1"><div class="fw-semibold">${it.name??('User #'+it.id)}</div><div class="small text-muted text-truncate" style="max-width:170px;">${(it.last_message||'')}</div></div>` + (it.unread>0?`<span class="badge bg-danger">${it.unread}</span>`:'');
        li.addEventListener('click', ()=>selectConversation(it.id, it));
        ul.appendChild(li);
    });
    // Auto-select first if none active
    if(!currentPartnerUserId && items.length>0){
        selectConversation(items[0].id, items[0]);
    }
}

async function loadConversationList() {
    try {
        const q = document.getElementById('userSearch').value.trim();
        const data = await fetchJSON('/chat/users'+(q?('?q='+encodeURIComponent(q)):'') );
    if(data.meta && typeof data.meta.self_id !== 'undefined') selfUserId = data.meta.self_id;
        renderConversationList(data.users);
    } catch(e){
        console.error('Failed loading users', e);
        const ul = document.getElementById('conversationList');
        ul.innerHTML = '<li class="text-warning small px-3 py-2">Session expired or server error. Reload / login.</li>';
        document.getElementById('convCount').textContent = 0;
    }
}

function scrollMessagesToBottom(){
    const pane = document.getElementById('messagesPane');
    pane.scrollTop = pane.scrollHeight;
}

function renderMessages(list, append=false){
    const content = document.getElementById('messagesContent');
    if(!append) content.innerHTML='';
    list.forEach(raw => {
        // Normalize legacy / new API shapes
        const m = raw && typeof raw === 'object' ? (raw.type ? raw : { ...raw, type:'message'}) : raw;
        if(m.type === 'separator') {
            const sep = document.createElement('div');
            sep.className = 'text-center my-3';
            sep.innerHTML = `<span class=\"badge rounded-pill bg-dark border\" style=\"background:rgba(0,0,0,.35)!important;\">${escapeHtml(m.label)}</span>`;
            content.appendChild(sep);
            return;
        }
        if(m.type === 'message') {
            const wrap = document.createElement('div');
            wrap.className = 'mb-2 d-flex ' + (m.is_me ? 'justify-content-end':'justify-content-start');
            if(m.is_me) lastOutgoingId = Math.max(lastOutgoingId, m.id);
            const seenMark = (m.is_me && m.read_at) ? ' <span class="text-info seen-flag" data-mid="'+m.id+'">Seen</span>' : (m.read_at && !m.is_me ? ' ✓':'');
            wrap.innerHTML = `<div class=\"msg-bubble ${m.is_me?'me':'other'}\" style=\"max-width:72%;\">`+
                `<div>${escapeHtml(m.body)}</div>`+
                `<div class=\"small text-end opacity-75 mt-1\">${m.time}${seenMark}</div>`+
                `</div>`;
            content.appendChild(wrap);
            if(m.id>lastMessageId) lastMessageId = m.id;
        }
    });
    scrollMessagesToBottom();
}

function escapeHtml(str){ return str.replace(/[&<>]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }

async function loadMessages(initial=false){
    if(!currentConversationId) return;
    try {
        const url = `/chat/conversations/${currentConversationId}/messages` + (lastMessageId?('?after='+lastMessageId):'');
        const data = await fetchJSON(url);
        if(initial) {
            lastMessageId = 0; // reset
            renderMessages(data.messages, false);
        } else if(data.messages.length){
            renderMessages(data.messages, true);
        }
        document.getElementById('unreadBadge').classList.add('d-none');
    } catch(e){ console.error(e); }
}

async function selectConversation(userId, meta){
    currentPartnerUserId = userId;
    lastMessageId = 0; currentConversationId=null;
    document.getElementById('receiverId').value = userId;
    document.getElementById('messageBody').disabled = true;
    document.getElementById('sendBtn').disabled = true;
    document.getElementById('conversationTitle').textContent = meta?.name || ('User #'+userId);
    document.getElementById('conversationMeta').textContent = 'Loading...';
    const ac = document.getElementById('avatarCircle');
    ac.textContent = (meta?.name||'U').substring(0,1).toUpperCase();
    try {
        const res = await fetchJSON('/chat/open',{method:'POST',body:new URLSearchParams({user_id:userId})});
        currentConversationId = res.conversation_id;
        renderMessages(res.messages || [], false);
        document.getElementById('messageBody').disabled = false;
        document.getElementById('sendBtn').disabled = false;
        document.getElementById('conversationMeta').textContent = '';
        ensureRTC();
    } catch(e){ console.error(e); document.getElementById('conversationMeta').textContent='Error'; }
}

async function sendMessage(e){
    e.preventDefault();
    if(!currentConversationId) return;
    const bodyEl = document.getElementById('messageBody');
    const text = bodyEl.value.trim();
    if(!text) return;
    bodyEl.disabled = true;
    try {
        const sendRes = await fetchJSON('/chat/send', { method:'POST', body: new URLSearchParams({ conversation_id: currentConversationId, body: text }) });
        // Optimistic append (sendRes.message already plaintext)
        if(sendRes && sendRes.message){
            lastOutgoingId = sendRes.message.id;
            renderMessages([{...sendRes.message, type:'message'}], true);
        }
        // Fast path via data channel
        if(rtcReady && rtcChannel?.readyState==='open'){
            try { rtcChannel.send(JSON.stringify({type:'msg',body:text,ts:Date.now()})); } catch(_){ }
        }
        bodyEl.value='';
        loadConversationList();
    } catch(e){ console.error(e); }
    bodyEl.disabled = false; bodyEl.focus();
}

function startPolling(){
    if(pollHandle) clearInterval(pollHandle);
    pollHandle = setInterval(()=>{ loadMessages(); loadConversationList(); }, 4000);
}

document.getElementById('messageForm').addEventListener('submit', sendMessage);
document.getElementById('userSearch').addEventListener('input', ()=>{
    loadConversationList();
});
document.getElementById('refreshList').addEventListener('click', ()=>loadConversationList());

// Typing indicator (real-time via signaling + data channel)
let lastTypingSent=0, typingHideTimer=null;
const typingBanner=document.getElementById('typingBanner');
const bodyEl=document.getElementById('messageBody');
function showTyping(fromUserId){
    // Only render if conversation active and event from other user
    if(!currentConversationId) return;
    if(fromUserId && fromUserId === selfUserId) return; // ignore self
    // For direct chat we know remote user id; name in header
    const title = document.getElementById('conversationTitle').textContent || 'User';
    typingBanner.textContent = title + ' is typing…';
    typingBanner.classList.remove('d-none');
    clearTimeout(typingHideTimer);
    typingHideTimer=setTimeout(()=>typingBanner.classList.add('d-none'),1700);
}
bodyEl.addEventListener('input',()=>{
    const val = bodyEl.value.trim();
    if(!currentConversationId || val.length===0) return;
    const now=Date.now();
    if(now - lastTypingSent > 800){
        lastTypingSent=now;
        postSignal('typing',{ts:now});
        if(rtcReady && rtcChannel?.readyState==='open'){
            try { rtcChannel.send(JSON.stringify({type:'typing',ts:now,from:selfUserId})); } catch(_){ }
        }
    }
});

loadConversationList().then(()=>startPolling());

// ================= WebRTC (simple offer/answer via REST) =================
async function ensureRTC(){
    if(rtcInit || !currentConversationId) return; rtcInit=true;
    rtcPeer = new RTCPeerConnection({iceServers:[{urls:'stun:stun.l.google.com:19302'}]});
    rtcChannel = rtcPeer.createDataChannel('chat');
    rtcChannel.onopen=()=>{ rtcReady=true; };
    rtcChannel.onmessage = ev => {
        try { const d = JSON.parse(ev.data); 
            if(d.type==='msg'){
                renderMessages([{type:'message',id:Date.now(),body:d.body,is_me:false,time:new Date(d.ts).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}], true);
            } else if(d.type==='typing') { showTyping(d.from); }
            else if(d.type==='seen') { markSeenUpTo(d.last_seen_id); }
        } catch(_){ }
    };
    rtcPeer.onicecandidate = ev => { if(ev.candidate) postSignal('candidate',{candidate:ev.candidate}); };
    // Create offer
    const offer = await rtcPeer.createOffer();
    await rtcPeer.setLocalDescription(offer);
    await postSignal('offer',{sdp:offer});
    pollSignals();
}
async function postSignal(type,payload){
    try { await fetch('/chat/conversations/'+currentConversationId+'/signal',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},body:JSON.stringify({type,payload})}); } catch(e){}
}
async function pollSignals(){
    if(!currentConversationId) return;
    try {
    const r = await fetch(`/chat/conversations/${currentConversationId}/signals?after=${signalAfter}`,{headers:{'Accept':'application/json'}});
        if(r.ok){
            const data = await r.json();
            for(const s of data.signals){ signalAfter=Math.max(signalAfter,s.id); await handleSignal(s); }
        }
    } catch(e){}
    setTimeout(pollSignals,1500);
}
async function handleSignal(sig){
    // ignore signals we originated (server does not echo an origin flag so just skip if needed later)
    if(!rtcPeer || (sig.type==='offer' && rtcPeer.currentRemoteDescription)) return;
    if(!rtcPeer || !rtcChannel){ // act as answerer
        rtcPeer = new RTCPeerConnection({iceServers:[{urls:'stun:stun.l.google.com:19302'}]});
    rtcPeer.ondatachannel = ev => { rtcChannel=ev.channel; rtcChannel.onopen=()=>rtcReady=true; rtcChannel.onmessage=ev=>{ try{const d=JSON.parse(ev.data); if(d.type==='msg'){ renderMessages([{type:'message',id:Date.now(),body:d.body,is_me:false,time:new Date(d.ts).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}], true);} else if(d.type==='typing'){ showTyping(d.from); } }catch(_){}}; };
        rtcPeer.onicecandidate = ev => { if(ev.candidate) postSignal('candidate',{candidate:ev.candidate}); };
    }
    if(sig.type==='typing'){ showTyping(sig.from_user_id); return; }
    if(sig.type==='seen'){ markSeenUpTo(sig.payload.last_seen_id); return; }
    if(sig.type==='offer' && !rtcPeer.currentRemoteDescription){
        await rtcPeer.setRemoteDescription(new RTCSessionDescription(sig.payload.sdp));
        const answer = await rtcPeer.createAnswer();
        await rtcPeer.setLocalDescription(answer);
        await postSignal('answer',{sdp:answer});
    } else if(sig.type==='answer' && !rtcPeer.currentRemoteDescription){
        await rtcPeer.setRemoteDescription(new RTCSessionDescription(sig.payload.sdp));
    } else if(sig.type==='candidate' && sig.payload.candidate){
        try { await rtcPeer.addIceCandidate(new RTCIceCandidate(sig.payload.candidate)); } catch(e){}
    }
}

// Attempt RTC after selecting a conversation
document.addEventListener('visibilitychange',()=>{ if(document.visibilityState==='visible' && currentConversationId) ensureRTC(); });

function markSeenUpTo(id){
    if(!id) return;
    // Mark any of our message bubbles (no existing Seen) with id <= id
    const bubbles = document.querySelectorAll('#messagesContent .msg-bubble.me .small');
    bubbles.forEach(b => {
        if(!b.innerHTML.includes('Seen')){
            b.innerHTML += ' <span class="text-info">Seen</span>';
        }
    });
}


</script>
@endsection
