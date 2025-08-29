<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'chatty_cat')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="/css/chatty.css" rel="stylesheet">
    <style>
        :root {
            --cc-bg: linear-gradient(135deg,#1b1f3a,#252d5a 40%,#3d1f54);
            --cc-surface: #1f243d;
            --cc-surface-alt:#242b48;
            --cc-accent:#ff9f43;
            --cc-accent-alt:#ff5e8e;
            --cc-accent-grad: linear-gradient(135deg,var(--cc-accent),var(--cc-accent-alt));
            --cc-glow: 0 0 0 0 rgba(255,159,67,.4);
            --cc-text:#eceff8;
        }
    body { background:var(--cc-bg); background-attachment:fixed; color:var(--cc-text); font-family:'Nunito', sans-serif; min-height:100vh; padding-bottom:70px; }
    .navbar { background:rgba(20,24,45,.75)!important; backdrop-filter: blur(12px); border-bottom:1px solid rgba(255,255,255,.05); position:relative; z-index:6000; }
        .navbar-brand { font-weight:700; letter-spacing:.5px; display:flex; align-items:center; }
        .navbar-brand .brand-icon { color:var(--cc-accent); margin-right:.55rem; filter:drop-shadow(0 0 6px rgba(255,159,67,.5)); animation:catPulse 3s ease-in-out infinite; }
        .chat-badge { background:var(--cc-accent-grad); color:#111; border-radius:14px; padding:3px 10px; font-size:.65rem; margin-left:.45rem; font-weight:700; box-shadow:0 2px 6px -2px rgba(255,94,142,.4); }
        a { color:var(--cc-accent); transition:.25s; }
        a:hover { color:var(--cc-accent-alt); }
        main.container { animation:fadeSlide .6s ease; }
    footer { background:rgba(20,24,45,.85); backdrop-filter:blur(10px); border-top:1px solid rgba(255,255,255,.05); position:fixed; left:0; right:0; bottom:0; z-index:5000; }
        @keyframes fadeSlide { from{opacity:0; transform:translateY(12px);} to{opacity:1; transform:translateY(0);} }
        @keyframes catPulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.15);} }
    
    .navbar .dropdown-menu { z-index: 6500; }
   
    .gradient-border, main.container, .gradient-border:before { position:relative; z-index:1; }

    .card { position:relative; z-index:2; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('posts.index') }}">
                <i class="fas fa-cat brand-icon"></i>
                chatty_cat <span class="chat-badge">beta</span>
            </a>
            
            @if(session('user_id'))
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> Account
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('posts.index') }}">
                            <i class="fas fa-rss"></i> Feed
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('posts.index') }}">
                            <i class="fas fa-list"></i> All Posts
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('posts.my-posts') }}">
                            <i class="fas fa-user-edit"></i> My Posts
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('posts.create') }}">
                            <i class="fas fa-plus"></i> Create Post
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('chat.index') }}">
                            <i class="fas fa-comments"></i> Chat
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('profile') }}">
                            <i class="fas fa-user-cog"></i> Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </nav>

    <main class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error') || $errors->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('error') ?? $errors->first('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any() && !$errors->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Please correct the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="text-light py-2 small">
        <div class="container text-center small">
            <span>&copy; 2024 chatty_cat • crafted with <i class="fas fa-heart text-danger"></i> colorful chatting</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>// layout base JS placeholder</script>
    @yield('scripts')
</body>
</html>
