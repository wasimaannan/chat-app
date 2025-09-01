@extends('layout')

@section('title', 'All Posts')

@section('content')
<style>
/* Minimal, theme-matching badge for comment count */
.badge-accent-soft {
    background: rgba(127, 83, 172, 0.10); /* purple accent, soft */
    color: #5e3a8c; /* darker accent for readability */
    border: 1px solid rgba(127, 83, 172, 0.25);
    border-radius: 999px;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
}
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Feed</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('chat.index') }}" class="btn btn-sm btn-outline-purple"><i class="fas fa-comments me-1"></i> Chat</a>
        <a href="{{ route('profile') }}" class="btn btn-sm btn-outline-purple"><i class="fas fa-user me-1"></i> Profile</a>
    </div>
    </div>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="fas fa-list me-2"></i>
        All Posts
    </h2>
    <a href="{{ route('posts.create') }}" class="btn btn-gradient">
        <i class="fas fa-plus me-1"></i> Create New Post
    </a>
    </div>

@if(count($decryptedPosts) > 0)
    <div class="row">
        @foreach($decryptedPosts as $post)
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-user me-1"></i> {{ $post['author'] }}</h6>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title d-flex justify-content-between align-items-center">
                            <a href="{{ route('posts.show', $post['id']) }}" class="text-decoration-none">
                                {{ $post['title'] }}
                            </a>
                            <span class="badge-accent-soft ms-2" title="Comments">
                                <i class="fas fa-comment me-1"></i>{{ $post['comments_count'] ?? 0 }}
                            </span>
                        </h5>
                        <p class="card-text mt-2 mb-3">
                            {{ Str::limit($post['content'], 150) }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="fas fa-clock me-1"></i>{{ $post['created_at']->diffForHumans() }}</small>
                            <a href="{{ route('posts.show', $post['id']) }}" class="btn btn-outline-purple btn-sm">
                                <i class="fas fa-eye me-1"></i> Read More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">No posts available</h4>
    <p class="text-muted">Be the first to share something!</p>
        <a href="{{ route('posts.create') }}" class="btn btn-gradient">
            <i class="fas fa-plus"></i> Create First Post
        </a>
    </div>
@endif

{{-- Removed security info notice card as requested --}}
@endsection
