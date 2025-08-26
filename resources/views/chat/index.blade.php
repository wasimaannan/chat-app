@extends('layout')

@section('title','chatty_cat')

@section('content')
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
            <div class="text-muted small py-5 text-center">No conversation selected. Pick a user on the left.</div>
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
// Basic polling chat client (upgradeable to WebSockets later)
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let currentUserId = null; // conversation partner
let pollHandle = null;
let lastMessageId = 0;

async function fetchJSON(url, opts = {}) {
    const res = await fetch(url, Object.assign({ headers:{ 'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':csrfToken }}, opts));
    if(!res.ok) throw new Error('HTTP '+res.status);
    return res.json();
}

function renderConversationList(items) {
    const ul = document.getElementById('conversationList');
    ul.innerHTML = '';
    if(items.length === 0){
        ul.innerHTML = '<li class="text-muted small px-3 py-2">No users found.</li>';
        document.getElementById('convCount').textContent = 0;
        return;
    }
    document.getElementById('convCount').textContent = items.length;
    items.forEach(it => {
        const li = document.createElement('li');
        li.className = 'px-3 py-2 conversation-item d-flex align-items-center gap-2';
        li.style.cursor='pointer';
        if(currentUserId === it.id) li.classList.add('bg-secondary');
        li.innerHTML = `<div class="flex-grow-1"><div class="fw-semibold">${it.name??('User #'+it.id)}</div><div class="small text-muted text-truncate" style="max-width:170px;">${(it.last_message||'')}</div></div>` + (it.unread>0?`<span class="badge bg-danger">${it.unread}</span>`:'');
        li.addEventListener('click', ()=>selectConversation(it.id, it));
        ul.appendChild(li);
    });
    // Auto-select first if none active
    if(!currentUserId && items.length>0){
        selectConversation(items[0].id, items[0]);
    }
}

async function loadConversationList() {
    try {
        const q = document.getElementById('userSearch').value.trim();
        const data = await fetchJSON('/chat/users'+(q?('?q='+encodeURIComponent(q)):'') );
        renderConversationList(data.users);
    } catch(e){ console.error(e); }
}

function scrollMessagesToBottom(){
    const pane = document.getElementById('messagesPane');
    pane.scrollTop = pane.scrollHeight;
}

function renderMessages(list, append=false){
    const pane = document.getElementById('messagesPane');
    if(!append) pane.innerHTML='';
    list.forEach(m => {
        if(m.type === 'separator') {
            const sep = document.createElement('div');
            sep.className = 'text-center my-3';
            sep.innerHTML = `<span class=\"badge rounded-pill bg-dark border\" style=\"background:rgba(0,0,0,.35)!important;\">${escapeHtml(m.label)}</span>`;
            pane.appendChild(sep);
            return;
        }
        if(m.type === 'message') {
            const wrap = document.createElement('div');
            wrap.className = 'mb-2 d-flex ' + (m.is_me ? 'justify-content-end':'justify-content-start');
            wrap.innerHTML = `<div class=\"msg-bubble ${m.is_me?'me':'other'}\" style=\"max-width:72%;\">`+
                `<div>${escapeHtml(m.body)}</div>`+
                `<div class=\"small text-end opacity-75 mt-1\">${m.time}${m.read_at?' ✓':''}</div>`+
                `</div>`;
            pane.appendChild(wrap);
            if(m.id>lastMessageId) lastMessageId = m.id;
        }
    });
    scrollMessagesToBottom();
}

function escapeHtml(str){ return str.replace(/[&<>]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }

async function loadMessages(initial=false){
    if(!currentUserId) return;
    try {
        const url = `/chat/conversation/${currentUserId}` + (lastMessageId?('?after='+lastMessageId):'');
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

async function selectConversation(id, meta){
    currentUserId = id;
    lastMessageId = 0;
    document.getElementById('receiverId').value = id;
    document.getElementById('messageBody').disabled = false;
    document.getElementById('sendBtn').disabled = false;
    document.getElementById('conversationTitle').textContent = meta?.name || ('User #'+id);
    document.getElementById('conversationMeta').textContent = '';
    // Avatar initial
    const ac = document.getElementById('avatarCircle');
    const initial = (meta?.name||'U').substring(0,1).toUpperCase();
    ac.textContent = initial;
    await loadMessages(true);
}

async function sendMessage(e){
    e.preventDefault();
    if(!currentUserId) return;
    const bodyEl = document.getElementById('messageBody');
    const text = bodyEl.value.trim();
    if(!text) return;
    bodyEl.disabled = true;
    try {
        await fetchJSON('/chat/message', { method:'POST', body: new URLSearchParams({ receiver_id: currentUserId, body: text }) });
        bodyEl.value='';
        await loadMessages();
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

// Typing indicator (local only mock)
let typingTimeout=null;
const typingEl=document.getElementById('typingIndicator');
const bodyEl=document.getElementById('messageBody');
bodyEl.addEventListener('input',()=>{
    if(bodyEl.value.trim().length===0){typingEl.style.display='none';return;}
    typingEl.style.display='flex';
    clearTimeout(typingTimeout);
    typingTimeout=setTimeout(()=>typingEl.style.display='none',1500);
});

loadConversationList().then(()=>startPolling());

</script>
@endsection
