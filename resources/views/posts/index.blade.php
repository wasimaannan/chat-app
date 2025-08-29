@extends('layout')

@section('title', 'All Posts - Secure App')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Feed</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('chat.index') }}" class="btn btn-sm btn-primary"><i class="fas fa-comments"></i> Chat</a>
        <a href="{{ route('posts.create') }}" class="btn btn-sm btn-outline-light"><i class="fas fa-plus"></i> New Post</a>
    </div>
</div>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-list"></i> 
        All Posts
    </h2>
    <a href="{{ route('posts.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Post
    </a>
</div>

@if(count($decryptedPosts) > 0)
    <div class="row">
        @foreach($decryptedPosts as $post)
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-user"></i> {{ $post['author'] }}
                        </h6>
                        <span class="integrity-check">
                            <i class="fas fa-shield-alt"></i> Verified
                        </span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="{{ route('posts.show', $post['id']) }}" class="text-decoration-none">
                                {{ $post['title'] }}
                            </a>
                        </h5>
                        <p class="card-text">
                            {{ Str::limit($post['content'], 150) }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> 
                                {{ $post['created_at']->diffForHumans() }}
                            </small>
                            @if($post['is_published'])
                                <span class="badge bg-success">Published</span>
                            @else
                                <span class="badge bg-secondary">Draft</span>
                            @endif
                        </div>
                        {{-- Removed encryption status line --}}
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('posts.show', $post['id']) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye"></i> Read More
                        </a>
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
        <a href="{{ route('posts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create First Post
        </a>
    </div>
@endif

{{-- Removed security info notice card as requested --}}
@endsection
