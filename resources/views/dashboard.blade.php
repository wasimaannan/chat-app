@extends('layout')

@section('title', 'Dashboard - Secure App')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-tachometer-alt"></i> 
                Welcome, {{ $userData['name'] ?? 'User' }}!
                <span class="security-badge">Data Encrypted</span>
            </h2>
            <div class="integrity-check">
                <i class="fas fa-check-circle"></i> Data Integrity Verified
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Posts</h6>
                        <h3>{{ $stats['total_posts'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Published</h6>
                        <h3>{{ $stats['published_posts'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">This Week</h6>
                        <h3>{{ $stats['recent_posts_count'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-week fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Users</h6>
                        <h3>{{ $stats['total_users'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock"></i> Recent Posts
                    <span class="security-badge">Encrypted</span>
                </h5>
            </div>
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
                                <small class="integrity-check">
                                    <i class="fas fa-shield-alt"></i> Data integrity verified
                                </small>
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
                        <p class="text-muted">Start sharing your thoughts securely!</p>
                        <a href="{{ route('posts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Post
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user"></i> Profile Overview
                    <span class="security-badge">Encrypted</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong><i class="fas fa-user"></i> Name:</strong><br>
                    <span class="text-muted">{{ $userData['name'] ?? 'Not set' }}</span>
                    <small class="integrity-check d-block">
                        <i class="fas fa-lock"></i> Encrypted & Verified
                    </small>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-envelope"></i> Email:</strong><br>
                    <span class="text-muted">{{ $userData['email'] ?? 'Not set' }}</span>
                    <small class="integrity-check d-block">
                        <i class="fas fa-lock"></i> Encrypted & Verified
                    </small>
                </div>
                
                @if(!empty($userData['phone']))
                <div class="mb-3">
                    <strong><i class="fas fa-phone"></i> Phone:</strong><br>
                    <span class="text-muted">{{ $userData['phone'] }}</span>
                    <small class="integrity-check d-block">
                        <i class="fas fa-lock"></i> Encrypted & Verified
                    </small>
                </div>
                @endif
                
                <div class="text-center">
                    <a href="{{ route('profile') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt"></i> Security Status
                </h5>
            </div>
            <div class="card-body">
                <div class="security-status">
                    <div class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <span class="small">Data encryption active</span>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <span class="small">Password securely hashed</span>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <span class="small">Integrity verification enabled</span>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        <span class="small">Secure session management</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('posts.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Create New Post
                    </a>
                    <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list"></i> Browse All Posts
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
