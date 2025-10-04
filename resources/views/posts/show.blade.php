@extends('layout')

@section('title', $decryptedPost['title'] . ' - Post')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $decryptedPost['title'] }}</h4>
                    <!-- All posts are public, no badge needed -->
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        <i class="fas fa-user"></i> {{ $decryptedPost['author'] }}
                        <span class="mx-2">|</span>
                        <i class="fas fa-clock"></i> {{ $decryptedPost['created_at']->format('M d, Y H:i') }}
                    </p>
                    <hr>
                    <div class="mb-3" style="white-space: pre-line;">
                        {{ $decryptedPost['content'] }}
                    </div>
                </div>
                <!-- Comments Section -->
                <div class="px-4 pb-4">
                    <h5 class="mb-3 mt-2">Comments</h5>
                    @auth
                    <form action="{{ route('comments.store', $decryptedPost['id']) }}" method="POST" class="mb-4">
                        @csrf
                        <div class="mb-2">
                            <textarea name="content" class="form-control" rows="2" placeholder="Write a comment..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-gradient">Post Comment</button>
                    </form>
                    @endauth
                    <div class="comments-list">
                        @forelse($comments as $comment)
                            <div class="mb-3 p-3" style="background:var(--cc-surface); border:1px solid #ffffff22; backdrop-filter:var(--cc-surface-blur); border-radius:1rem;">
                                <div class="small text-muted mb-1">
                                    <i class="fas fa-user"></i> {{ $comment->user->full_name ?? 'User' }}
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-clock"></i> {{ $comment->created_at->diffForHumans() }}
                                </div>
                                <div>{{ $comment->content }}</div>
                            </div>
                        @empty
                            <div class="text-muted">No comments yet.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    @if($decryptedPost['can_edit'])
                        <a href="{{ route('posts.edit', $decryptedPost['id']) }}" class="btn btn-outline-purple btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                    @endif
                    <a href="{{ route('posts.index') }}" class="btn btn-outline-purple btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Posts
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
