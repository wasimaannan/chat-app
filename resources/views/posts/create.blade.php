@extends('layout')

@section('title', 'Create Post')

@section('content')
<style>
/* Scoped readability & custom write color for create post card */
.post-create-card { color:#212529 !important; }
.post-create-card h1,.post-create-card h2,.post-create-card h3,
.post-create-card h4,.post-create-card h5,.post-create-card h6,
.post-create-card .form-label, .post-create-card label, .post-create-card small { color:#212529 !important; }
/* Typing (write) color */
.post-create-card input, .post-create-card textarea { color:#ffffff !important; }
/* Placeholder color adjusted to grey */
.post-create-card input::placeholder, .post-create-card textarea::placeholder { color:#6c757d !important; opacity:1; }
.post-create-card .text-muted { color:#495057 !important; }
</style>
<div class="row justify-content-center">
    <div class="col-md-10">
    <div class="card post-create-card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-plus"></i> 
                    Create New Post
                </h4>
            </div>
            <div class="card-body">
                {{-- Removed security notice per request --}}
                
                <form action="{{ route('posts.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">
                            <i class="fas fa-heading"></i> Post Title *
                        </label>
                        <input 
                            type="text" 
                            class="form-control encrypted-field @error('title') is-invalid @enderror" 
                            id="title" 
                            name="title" 
                            value="{{ old('title') }}" 
                            required
                            placeholder="Enter a compelling title for your post">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        {{-- Removed encryption hint --}}
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">
                            <i class="fas fa-edit"></i> Post Content *
                        </label>
                        <textarea 
                            class="form-control encrypted-field @error('content') is-invalid @enderror" 
                            id="content" 
                            name="content" 
                            rows="10" 
                            required
                            placeholder="Write your post content here...">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        {{-- Removed encryption hint --}}
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="is_published" 
                                name="is_published" 
                                value="1"
                                {{ old('is_published') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">
                                <i class="fas fa-globe"></i> Publish immediately
                            </label>
                        </div>
                        <small class="text-muted">
                            If unchecked, the post will be saved as a draft and you can publish it later.
                        </small>
                    </div>
                    
                    {{-- Removed security features applied card --}}
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Post
                        </button>
                        <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Character counter for content
    document.getElementById('content').addEventListener('input', function() {
        const content = this.value;
        const length = content.length;
        
        // You can add character counting logic here
        console.log('Content length:', length);
    });
    
    // Auto-save draft functionality (optional)
    let autoSaveTimer;
    function autoSaveDraft() {
        const title = document.getElementById('title').value;
        const content = document.getElementById('content').value;
        
        if (title.length > 0 || content.length > 0) {
            // Store in localStorage as backup
            localStorage.setItem('draft_title', title);
            localStorage.setItem('draft_content', content);
            console.log('Draft auto-saved locally');
        }
    }
    
    // Auto-save every 30 seconds
    document.getElementById('title').addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(autoSaveDraft, 30000);
    });
    
    document.getElementById('content').addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(autoSaveDraft, 30000);
    });
    
    // Restore draft on page load
    window.addEventListener('load', function() {
        const draftTitle = localStorage.getItem('draft_title');
        const draftContent = localStorage.getItem('draft_content');
        
        if (draftTitle && !document.getElementById('title').value) {
            document.getElementById('title').value = draftTitle;
        }
        
        if (draftContent && !document.getElementById('content').value) {
            document.getElementById('content').value = draftContent;
        }
    });
    
    // Clear draft on successful submission
    document.querySelector('form').addEventListener('submit', function() {
        localStorage.removeItem('draft_title');
        localStorage.removeItem('draft_content');
    });
</script>
@endsection
