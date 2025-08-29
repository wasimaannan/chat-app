@extends('layout')

@section('title', 'Dashboard - chatty_cat')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-cat"></i> Hi, {{ $userData['name'] ?? 'User' }}</h2>
            <div class="text-muted small">Have a calm conversation today.</div>
        </div>
    </div>
</div>

<div class="mb-4 p-3 rounded" style="background:#1a1f2e;border:1px solid #2c3448;">
    <div class="row g-3 text-center small">
        <div class="col-6 col-md-3">
            <div class="fw-semibold text-muted">Posts</div>
            <div class="h4 mb-0">{{ $stats['total_posts'] }}</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="fw-semibold text-muted">Published</div>
            <div class="h4 mb-0">{{ $stats['published_posts'] }}</div>
        </div>
        <div class="col-6 col-md-3 mt-3 mt-md-0">
            <div class="fw-semibold text-muted">This Week</div>
            <div class="h4 mb-0">{{ $stats['recent_posts_count'] }}</div>
        </div>
        <div class="col-6 col-md-3 mt-3 mt-md-0">
            <div class="fw-semibold text-muted">Users</div>
            <div class="h4 mb-0">{{ $stats['total_users'] }}</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7 col-lg-8">
        <div class="card mb-4" style="background:#1c1f2b;border:1px solid #2c3448;">
            <div class="card-header py-2"><h6 class="mb-0 text-uppercase small">Recent Posts</h6></div>
            <div class="card-body">
                @if(count($decryptedRecentPosts) > 0)
                    <div class="list-group list-group-flush">
                        @foreach($decryptedRecentPosts as $post)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <a href="{{ route('posts.show', $post['id']) }}" class="text-decoration-none">
                                            {{ $post['title'] }}
                                        </a>
                                        @if($post['is_published'])
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </h6>
                                    <small>{{ $post['created_at']->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1 text-muted">{{ $post['content'] }}</p>
                                <small class="text-muted">Updated {{ $post['created_at']->diffForHumans() }}</small>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('posts.my-posts') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> View All My Posts
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No posts yet</h5>
                        <p class="text-muted">Start sharing your thoughts!</p>
                        <a href="{{ route('posts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Post
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-5 col-lg-4">
        <div class="card mb-4" style="background:#1c1f2b;border:1px solid #2c3448;">
            <div class="card-header py-2"><h6 class="mb-0 text-uppercase small">Profile</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <strong><i class="fas fa-user"></i> Name:</strong><br>
                    <span class="text-muted">{{ $userData['name'] ?? 'Not set' }}</span>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-envelope"></i> Email:</strong><br>
                    <span class="text-muted">{{ $userData['email'] ?? 'Not set' }}</span>
                </div>
                
                @if(!empty($userData['phone']))
                <div class="mb-3">
                    <strong><i class="fas fa-phone"></i> Phone:</strong><br>
                    <span class="text-muted">{{ $userData['phone'] }}</span>
                </div>
                @endif
                
                <div class="text-center">
                    <a href="{{ route('profile') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
    <!-- Security status panel removed for minimalist design -->
        
                <div class="card" style="background:#1c1f2b;border:1px solid #2c3448;">
                        <div class="card-header py-2"><h6 class="mb-0 text-uppercase small">Quick Actions</h6></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('posts.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Create New Post
                    </a>
                    <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list"></i> Browse All Posts
                    </a>
                                        <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-comments"></i> Open Chat
                                        </a>
                </div>
            </div>
        </div>
                <div class="card" style="background:#1c1f2b;border:1px solid #2c3448;">
                        <div class="card-header py-2"><h6 class="mb-0 text-uppercase small">Recent Conversations</h6></div>
                        <div class="card-body small" id="recentConvos">Loading...</div>
                </div>
    </div>
</div>
@section('scripts')
<script>
// Fetch last few conversations for sidebar
fetch('/chat/users',{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{
    const box=document.getElementById('recentConvos');
    if(!d.users||d.users.length===0){ box.textContent='No conversations yet.'; return; }
    box.innerHTML='';
    d.users.slice(0,5).forEach(u=>{
        const div=document.createElement('div');
        div.className='d-flex justify-content-between align-items-center py-1';
        div.innerHTML=`<span class="text-truncate" style="max-width:140px;">${u.name||('User #'+u.id)}</span>`+
            `<a href="/chat#${u.id}" class="text-decoration-none small">${u.unread>0?('<span class=\'badge bg-danger\'>'+u.unread+'</span>'):'View'}</a>`;
        box.appendChild(div);
    });
}).catch(()=>{const b=document.getElementById('recentConvos'); if(b) b.textContent='Failed to load.'});
</script>
@endsection
@endsection
