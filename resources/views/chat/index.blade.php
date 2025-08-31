@extends('layout')

@section('title','chatty_cat')

@section('content')
</style>
<style>
/* Pookie conversation list selected user */
.conversation-item.bg-secondary {
    background: linear-gradient(90deg, #fbc2eb 0%, #e0e7ff 100%) !important;
    color: #7f53ac !important;
    border-radius: 1.2rem;
    font-weight: 700;
    box-shadow: 0 2px 8px -2px #fbc2eb55;
    border: 2px solid #fbc2eb88;
    margin: 0.2rem 0;
    font-family: 'Nunito', cursive, sans-serif;
    letter-spacing: 0.2px;
}
/* Pookie chat bubble styling */
.msg-bubble {
    padding:.7rem 1.1rem;
    border-radius:2rem 2rem 2rem 0.7rem;
    background:linear-gradient(120deg,#fbc2eb 0%,#e0e7ff 100%);
    color:#7f53ac;
    font-size:1.05rem;
    font-family:'Nunito',cursive,sans-serif;
    line-height:1.5;
    position:relative;
    word-break:break-word;
    box-shadow:0 4px 18px -8px #fbc2eb55,0 1.5px 8px 0 #7f53ac11;
    transition:background .25s, transform .15s;
    border:2px solid #fbc2eb55;
}
.msg-bubble.other {
    background:linear-gradient(120deg,#e0e7ff 0%,#fbc2eb 100%);
    border:2px solid #7f53ac22;
    color:#5e3a8c;
}
.msg-bubble.me {
    background:linear-gradient(120deg,#7f53ac 0%,#fbc2eb 100%);
    color:#fff;
    box-shadow:0 6px 20px -8px #7f53ac55;
    border:2px solid #7f53ac55;
}
.msg-bubble.me .small {
    color:rgba(255,255,255,.85)!important;
}
.msg-bubble.other .small {
    color:#7f53ac!important;
}
.msg-bubble:hover {
    transform:translateY(-3px) scale(1.03) rotate(-1deg);
    box-shadow:0 8px 24px -8px #fbc2eb88;
}
/* Subtle tail using pseudo elements */
/* Removed cat paw from chat bubbles */
@media (prefers-reduced-motion:reduce){ .msg-bubble:hover{transform:none;} }
</style>
</style>
<div id="chat-app" class="row g-0" style="min-height:72vh;border-radius:2.5rem;overflow:hidden;background:linear-gradient(135deg,#fbc2eb 0%,#e0e7ff 100%);box-shadow:0 8px 32px 0 #7f53ac22,0 1.5px 8px 0 #fbc2eb22;backdrop-filter:blur(12px) saturate(120%);">
    <div class="col-12 col-md-4 col-lg-3 d-flex flex-column" style="background:rgba(255,255,255,0.82);backdrop-filter:blur(16px) saturate(180%);box-shadow:0 4px 24px -8px #fbc2eb55;">
        <div class="p-3 border-bottom d-flex align-items-center">
            <h6 class="mb-0 flex-grow-1 text-uppercase small fw-bold tracking-wide">Chats</h6>
            <button id="refreshList" class="btn btn-sm btn-outline-accent" title="Refresh" style="border-radius:1.5rem;font-size:1.1rem;padding:.4rem 1.1rem;"><i class="fas fa-rotate"></i> <span style="font-family:'Nunito',cursive;">Refresh</span></button>
        </div>
        <div class="p-2">
            <input id="userSearch" type="text" class="form-control form-control-sm" placeholder="Search users..." style="border-radius:1.5rem;border:2px solid #fbc2eb;background:#fff0fa;color:#7f53ac;font-family:'Nunito',cursive;box-shadow:0 2px 8px -2px #fbc2eb33;">
        </div>
        <ul id="conversationList" class="list-unstyled mb-0 flex-grow-1" style="overflow-y:auto;">
            <!-- filled dynamically -->
        </ul>
        <div class="p-2 border-top d-flex justify-content-center">
            <span class="user-count-pookie">Showing <span id="convCount">0</span> users</span>
        </div>

<style>
    .user-count-pookie {
        display: inline-block;
        background: linear-gradient(90deg, #f8e1ff 0%, #e1f0ff 100%);
        color: #a06cd5;
        font-family: 'Nunito', cursive, sans-serif;
        font-size: 1rem;
        padding: 4px 16px;
        border-radius: 18px;
        box-shadow: 0 2px 8px 0 rgba(160, 108, 213, 0.08);
        font-weight: 600;
        letter-spacing: 0.02em;
        border: 1.5px solid #e0c3fc;
        margin: 0 auto;
        transition: background 0.2s, color 0.2s;
    }
</style>
    </div>
    <div class="col-12 col-md-8 col-lg-9 d-flex flex-column" style="background:rgba(255,255,255,0.92);backdrop-filter:blur(16px) saturate(180%);box-shadow:0 4px 24px -8px #7f53ac55;">
        <div id="conversationHeader" class="p-3 border-bottom" style="min-height:62px;">
            <div class="d-flex align-items-center gap-3">
                <div id="avatarCircle" class="rounded-circle d-flex justify-content-center align-items-center" style="width:48px;height:48px;background:linear-gradient(135deg,#fbc2eb 0%,#e0e7ff 100%);font-weight:700;font-size:1.2rem;color:#7f53ac;box-shadow:0 2px 8px -2px #fbc2eb55;">🐱</div>
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
    <form id="messageForm" class="p-2 d-flex gap-2 align-items-end border-top" autocomplete="off" style="background:linear-gradient(120deg,#fbc2eb 0%,#e0e7ff 100%);border-radius:0 0 2rem 2rem;box-shadow:0 -2px 12px -4px #fbc2eb33;">
            <input type="hidden" id="receiverId" name="receiver_id" value="">
            <button type="button" id="addPicBtn" style="padding:6px 18px; border:1px solid #ccc; border-radius:4px; background:#f8f9fa; color:#333; font-size:15px; cursor:pointer;">Add Picture</button>
            <input type="file" id="photoInput" name="image" accept="image/*" style="display:none;">
            <span id="fileName" style="font-size:13px; color:#7f53ac; margin-left:4px;"></span>
            <img id="imgPreview" src="" alt="" style="display:none; max-width:48px; max-height:48px; border-radius:6px; margin-left:6px;" />
            <textarea id="messageBody" name="body" class="form-control" rows="1" placeholder="Type a message..." disabled style="resize:none;max-height:160px;background:#fff0fa;border:2px solid #fbc2eb;border-radius:1.5rem;color:#7f53ac;font-family:'Nunito',cursive;box-shadow:0 2px 8px -2px #fbc2eb33;"></textarea>
            <button id="sendBtn" class="btn btn-accent send-btn-normal" type="submit" disabled>
                <i class="fas fa-paper-plane"></i>
            </button>
<script>
const addPicBtn = document.getElementById('addPicBtn');
const photoInput = document.getElementById('photoInput');
const fileNameSpan = document.getElementById('fileName');
const imgPreview = document.getElementById('imgPreview');
const sendBtn = document.getElementById('sendBtn');
const messageBody = document.getElementById('messageBody');

addPicBtn.addEventListener('click', function() {
    photoInput.click();
});

photoInput.addEventListener('change', function() {
    if (photoInput.files && photoInput.files[0]) {
        fileNameSpan.textContent = photoInput.files[0].name;
        const reader = new FileReader();
        reader.onload = function(e) {
            imgPreview.src = e.target.result;
            imgPreview.style.display = 'inline-block';
        };
        reader.readAsDataURL(photoInput.files[0]);
        sendBtn.disabled = false;
    } else {
        fileNameSpan.textContent = '';
        imgPreview.src = '';
        imgPreview.style.display = 'none';
        if (!messageBody.value.trim()) sendBtn.disabled = true;
    }
});

messageBody.addEventListener('input', function() {
    if (messageBody.value.trim() || (photoInput.files && photoInput.files[0])) {
        sendBtn.disabled = false;
    } else {
        sendBtn.disabled = true;
    }
});
</script>

<style>
    /* Fix bottom left chat area rounding */
    #chat-app > .col-12.col-md-4.col-lg-3 {
        border-bottom-left-radius: 2.5rem;
        overflow: hidden;
    }
    /* Normal send button style */
    .send-btn-normal {
        background: linear-gradient(90deg, #a18cd1 0%, #fbc2eb 100%);
        color: #fff;
        border: none;
        border-radius: 1.5rem;
        font-size: 1.25rem;
        font-family: 'Nunito', cursive, sans-serif;
        font-weight: 700;
        padding: 0.5rem 1.5rem;
        box-shadow: 0 2px 8px 0 #fbc2eb44;
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
        outline: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .send-btn-normal:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .send-btn-normal:hover:not(:disabled) {
        background: linear-gradient(90deg, #7f53ac 0%, #fbc2eb 100%);
        color: #fff;
        box-shadow: 0 4px 16px -4px #a18cd1aa;
    }
</style>
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
            // For sent messages, use flex-column to stack bubble and seen mark
            if(m.is_me) {
                wrap.className = 'mb-2 d-flex flex-column align-items-end';
            } else {
                wrap.className = 'mb-2 d-flex justify-content-start';
            }
            if(m.is_me) lastOutgoingId = Math.max(lastOutgoingId, m.id);
            const seenMark = (m.is_me && m.read_at) ? ' <span class="text-info seen-flag" data-mid="'+m.id+'">Seen</span>' : (m.read_at && !m.is_me ? ' ✓':'');
            // Place delivered tick (✓) inside the bubble for received messages
            let timeAndTick = m.time;
            if (m.read_at && !m.is_me) timeAndTick += ' <span class="delivered-tick">✓</span>';
            wrap.innerHTML = `<div class=\"msg-bubble ${m.is_me?'me':'other'}\" style=\"max-width:72%;\">`+
                `<div>${escapeHtml(m.body)}</div>`+
                `<div class=\"small text-end opacity-75 mt-1\">${timeAndTick}</div>`+
                `</div>`+
                (m.is_me && m.read_at ? seenMark : '');
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
    const fileInput = document.getElementById('photoInput');
    if(!text && (!fileInput.files || !fileInput.files[0])) return;
    bodyEl.disabled = true;
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('body', text);
        if(fileInput.files && fileInput.files[0]) {
            formData.append('image', fileInput.files[0]);
        }
        const res = await fetch('/chat/send', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });
        if(!res.ok) throw new Error('Failed to send');
        const sendRes = await res.json();
        if(sendRes && sendRes.message){
            lastOutgoingId = sendRes.message.id;
            renderMessages([{...sendRes.message, type:'message'}], true);
        }
        if(rtcReady && rtcChannel?.readyState==='open'){
            try { rtcChannel.send(JSON.stringify({type:'msg',body:text,ts:Date.now()})); } catch(_){ }
        }
    bodyEl.value='';
    fileInput.value = '';
    fileNameSpan.textContent = '';
    imgPreview.src = '';
    imgPreview.style.display = 'none';
    sendBtn.disabled = true;
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
