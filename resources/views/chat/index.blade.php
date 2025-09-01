@extends('layout')

@section('title','chatty_cat')

@section('content')
<style>
.conversation-item.bg-secondary {
    background: linear-gradient(90deg, #fbc2eb 0%, #e0e7ff 100%) !important;
    color: #7f53ac !important;
            const seenMark = (m.is_me && m.read_at) ? ' <span class="text-info seen-flag" data-mid="'+m.id+'">Seen</span>' : '';
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
    background:linear-gradient(120deg, rgba(var(--cc-softA-rgb),0.96) 0%, rgba(var(--cc-softA-rgb),0.82) 100%);
    color:#7f53ac; /* unified readable text */
    font-size:1.05rem;
    font-family:'Nunito',cursive,sans-serif;
    line-height:1.5;
    position:relative;
    word-break:break-word;
    box-shadow:0 4px 18px -8px rgba(var(--cc-softA-rgb),0.45),0 1.5px 8px 0 #7f53ac11;
    transition:background .25s, transform .15s;
    border:2px solid rgba(var(--cc-softA-rgb),0.5);
}
.msg-bubble.other {
    background: inherit; /* same as base */
    border:2px solid rgba(var(--cc-softA-rgb),0.5);
    color:#7f53ac;
}
.msg-bubble.me {
    background: inherit; /* same as base */
    color:#7f53ac;
    box-shadow:0 6px 20px -8px rgba(var(--cc-softA-rgb),0.55);
    border:2px solid rgba(var(--cc-softA-rgb),0.5);
}
.msg-bubble .small { color:#7f53ac!important; }
.msg-bubble:hover {
    transform:translateY(-3px) scale(1.03) rotate(-1deg);
    box-shadow:0 8px 24px -8px #fbc2eb88;
}
/* Subtle tail using pseudo elements */
/* Removed cat paw from chat bubbles */
@media (prefers-reduced-motion:reduce){ .msg-bubble:hover{transform:none;} }
</style>
<div id="chat-app" class="row g-0" style="min-height:72vh;border-radius:2.5rem;overflow:hidden;background:linear-gradient(135deg, rgba(var(--cc-softA-rgb),1) 0%, rgba(var(--cc-softB-rgb),1) 100%);box-shadow:0 8px 32px 0 #7f53ac22,0 1.5px 8px 0 #fbc2eb22;backdrop-filter:blur(12px) saturate(120%);">
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
    <div id="messagesPane" class="flex-grow-1 px-3 py-2" style="overflow-y:auto; max-height: 60vh; min-height: 300px; background: transparent;">
            <div id="messagesContent">
                <div class="text-muted small py-5 text-center">No conversation selected. Pick a user on the left.</div>
            </div>
            <div id="typingBanner" class="small text-info d-none mt-1" style="opacity:.85;">Typing…</div>
        </div>
    <form id="messageForm" class="p-2 d-flex gap-2 align-items-end border-top" autocomplete="off" style="background:linear-gradient(120deg, rgba(var(--cc-softA-rgb),1) 0%, rgba(var(--cc-softB-rgb),1) 100%);border-radius:0 0 2rem 2rem;box-shadow:0 -2px 12px -4px #fbc2eb33;">
            <input type="hidden" id="receiverId" name="receiver_id" value="">

            <input type="file" id="photoInput" name="image" accept="image/*" style="display:none;">
            <span id="fileName" style="font-size:13px; color:#7f53ac; margin-left:4px;"></span>
            <img id="imgPreview" src="" alt="" style="display:none; max-width:48px; max-height:48px; border-radius:6px; margin-left:6px;" />
            <textarea id="messageBody" name="body" class="form-control" rows="1" placeholder="Type a message..." disabled style="resize:none;max-height:160px;background:#fff0fa;border:2px solid #fbc2eb;border-radius:1.5rem;color:#7f53ac;font-family:'Nunito',cursive;box-shadow:0 2px 8px -2px #fbc2eb33;"></textarea>
            <input type="file" id="chatFileInput" name="file" style="display:none;" accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt">
            <button id="attachBtn" type="button" class="btn btn-light" style="border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-paperclip" style="font-size:1.3rem;"></i>
            </button>
            <span id="fileNameDisplay" class="small text-muted" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:none;"></span>
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
// File attachment logic
const chatFileInput = document.getElementById('chatFileInput');
const attachBtn = document.getElementById('attachBtn');
const fileNameDisplay = document.getElementById('fileNameDisplay');
attachBtn.addEventListener('click', () => chatFileInput.click());
chatFileInput.addEventListener('change', function() {
    if (chatFileInput.files.length > 0) {
        fileNameDisplay.textContent = chatFileInput.files[0].name;
        fileNameDisplay.style.display = 'inline-block';
        document.getElementById('sendBtn').disabled = false;
    } else {
        fileNameDisplay.textContent = '';
        fileNameDisplay.style.display = 'none';
        // Only enable send if message body is not empty
        document.getElementById('sendBtn').disabled = document.getElementById('messageBody').value.trim() === '';
    }
});
// When send is clicked, if file is attached, send file; else send text
document.getElementById('messageForm').addEventListener('submit', function(e) {
    const fileAttached = chatFileInput.files.length > 0;
    if (fileAttached) {
        document.getElementById('messageBody').value = '';
    }
});
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

            // Custom rendering for file/image messages
            let bodyHtml = '';
            const fileRegex = /^\[file\] ([^\n]+)\n([^\n]+)$/i;
            const match = m.body.match(fileRegex);
            if (match) {
                const originalName = match[1];
                const savedName = match[2];
                const fileUrl = `/chat_files/${savedName}`;
                const isImage = /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(originalName);
                // Prefer inline base64 if available from server (m.file_blob). Fallback to static file URL.
                const hasBlob = m.file_blob && typeof m.file_blob === 'string' && m.file_blob.length > 0;
                const dataUrl = hasBlob ? `data:${m.file_mime || 'application/octet-stream'};base64,${m.file_blob}` : null;
                const downloadHref = hasBlob ? dataUrl : fileUrl;
                const downloadIcon = `<a href="${downloadHref}" download="${escapeHtml(originalName)}" title="Download" style="position:absolute;top:10px;right:14px;z-index:2;color:#7f53ac;background:#fff0fa;border-radius:50%;padding:6px;box-shadow:0 2px 8px #fbc2eb33;transition:background 0.18s;" onmouseover="this.style.background='#fbc2eb';" onmouseout="this.style.background='#fff0fa';"><i class='fas fa-download'></i></a>`;
                if (isImage) {
                    const imgSrc = hasBlob ? dataUrl : fileUrl;
                    bodyHtml = `<div style="position:relative;display:inline-block;">${downloadIcon}<img src="${imgSrc}" alt="${escapeHtml(originalName)}" style="max-width:180px;max-height:180px;border-radius:1rem;box-shadow:0 2px 8px #0002;margin-bottom:6px;display:block;"></div>`;
                } else {
                    bodyHtml = `<div style="position:relative;display:inline-block;min-width:120px;">${downloadIcon}<div style="padding:18px 8px 8px 8px;"><i class='fas fa-file-alt' style="font-size:1.5em;color:#7f53ac;"></i><br><span style="font-size:0.97em;color:#7f53ac;word-break:break-all;">${escapeHtml(originalName)}</span></div></div>`;
                }
            } else {
                bodyHtml = `<div>${escapeHtml(m.body)}</div>`;
            }

            // Bubble layout only, no reactions
            let bubble = `<div class=\"msg-bubble ${m.is_me?'me':'other'}\">`+
                `${bodyHtml}`+
                `<div class=\"small text-end opacity-75 mt-1\">${timeAndTick}</div>`+
            `</div>`;
            wrap.innerHTML = bubble + (m.is_me && m.read_at ? seenMark : '');
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
    const fileInput = document.getElementById('chatFileInput');
    const file = fileInput.files[0];
    const text = bodyEl.value.trim();
    if(!text && !file) return;
    bodyEl.disabled = true;
    fileInput.disabled = true;
    const formData = new FormData();
    formData.append('conversation_id', currentConversationId);
    if (file) {
        formData.append('file', file);
    }
    if (text && !file) {
        formData.append('body', text);
    }
    try {
        const res = await fetch('/chat/send', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });
        const sendRes = await res.json();
        if(sendRes && sendRes.message){
            lastOutgoingId = sendRes.message.id;
            renderMessages([{...sendRes.message, type:'message'}], true);
        }
        if(rtcReady && rtcChannel?.readyState==='open'){
            try { rtcChannel.send(JSON.stringify({type:'msg',body:(file ? '[file]' : text),ts:Date.now()})); } catch(_){ }
        }
        bodyEl.value='';
        fileInput.value = '';
        document.getElementById('fileNameDisplay').textContent = '';
        document.getElementById('fileNameDisplay').style.display = 'none';
        loadConversationList();
    } catch(e){ console.error(e); }
    bodyEl.disabled = false; fileInput.disabled = false; bodyEl.focus();
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
