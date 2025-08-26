@extends('layout')

@section('title', 'Create Post - Secure App')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-plus"></i> 
                    Create New Post
                    <span class="security-badge">Will be Encrypted</span>
                </h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Security Notice:</strong> Your post content will be encrypted before storage and 
                    protected with integrity verification (MAC). Only you and authorized users can decrypt and read the content.
                </div>
                
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
                        <small class="text-muted">
                            <i class="fas fa-lock text-success"></i> Will be encrypted before storage
                        </small>
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
                        <small class="text-muted">
                            <i class="fas fa-lock text-success"></i> Will be encrypted before storage
                        </small>
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
                    
                    <div class="mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-shield-alt text-success"></i> Security Features Applied:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled small">
                                            <li><i class="fas fa-check text-success"></i> Title encryption</li>
                                            <li><i class="fas fa-check text-success"></i> Content encryption</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled small">
                                            <li><i class="fas fa-check text-success"></i> Data integrity verification (MAC)</li>
                                            <li><i class="fas fa-check text-success"></i> Secure key management</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Post Securely
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
