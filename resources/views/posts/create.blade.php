@extends('layout')

@section('title', 'Create Post')

@section('content')
<style>
/* Cuter, pastel, chatty_cat theme for create post card */
.post-create-card {
    background: linear-gradient(135deg, #fbc2eb 0%, #e0e7ff 100%);
    color: #7f53ac !important;
    border-radius: 2.2rem;
    box-shadow: 0 8px 32px 0 #7f53ac22, 0 1.5px 8px 0 #fbc2eb22;
    font-family: 'Nunito', cursive, sans-serif;
    border: 2.5px solid #fbc2eb88;
    margin-top: 2.5rem;
}
.post-create-card h1, .post-create-card h2, .post-create-card h3,
.post-create-card h4, .post-create-card h5, .post-create-card h6,
.post-create-card .form-label, .post-create-card label, .post-create-card small {
    color: #7f53ac !important;
    font-family: 'Nunito', cursive, sans-serif;
}
.post-create-card input, .post-create-card textarea {
    color: #7f53ac !important;
    background: #fff0fa !important;
    border-radius: 1.5rem !important;
    border: 2px solid #fbc2eb !important;
    box-shadow: 0 2px 8px -2px #fbc2eb33;
    font-family: 'Nunito', cursive, sans-serif;
}
.post-create-card input::placeholder, .post-create-card textarea::placeholder {
    color: #b2b8c2 !important;
    opacity: 1;
    font-style: italic;
}
.post-create-card .text-muted {
    color: #b2b8c2 !important;
}
.cute-accent {
    font-size: 2.2rem;
    margin-right: 0.5rem;
    vertical-align: middle;
}
.btn-gradient {
    background: linear-gradient(90deg, #a18cd1 0%, #fbc2eb 100%);
    color: #fff;
    border: none;
    border-radius: 1.5rem;
    font-size: 1.15rem;
    font-family: 'Nunito', cursive, sans-serif;
    font-weight: 700;
    padding: 0.5rem 1.5rem;
    box-shadow: 0 2px 8px 0 #fbc2eb44;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    outline: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-gradient:hover {
    background: linear-gradient(90deg, #7f53ac 0%, #fbc2eb 100%);
    color: #fff;
    box-shadow: 0 4px 16px -4px #a18cd1aa;
}
</style>
<div class="row justify-content-center">
    <div class="col-md-10">
    <div class="card post-create-card">
            <div class="card-header bg-primary text-white" style="background:linear-gradient(90deg,#fbc2eb 0%,#e0e7ff 100%)!important;border-radius:2.2rem 2.2rem 0 0;box-shadow:0 2px 8px -2px #fbc2eb33;">
                <h4 class="mb-0" style="color:#7f53ac;font-family:'Nunito',cursive;">
                    Create New Post
                </h4>
            </div>
            <div class="card-body">
                {{-- Removed security notice per request --}}
                
                <form action="{{ route('posts.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">
                            Post Title *
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
                            Post Content *
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
                        <!-- Publish immediately option removed: all posts are now public by default -->
                    </div>
                    
                    {{-- Removed security features applied card --}}
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gradient">
                            Create Post
                        </button>
                        <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary" style="border-radius:1.5rem;font-family:'Nunito',cursive;">
                            Cancel
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
