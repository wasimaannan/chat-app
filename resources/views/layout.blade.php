<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Secure Laravel App')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .security-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .encrypted-field {
            position: relative;
        }
        
        .encrypted-field::after {
            content: '🔒';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #28a745;
        }
        
        .integrity-check {
            color: #28a745;
            font-size: 0.8rem;
        }
        
        .navbar-brand .security-icon {
            color: #ffc107;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-shield-alt security-icon"></i>
                Secure App
                <span class="security-badge">Encrypted</span>
            </a>
            
            @if(session('user_id'))
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> Account
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
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

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-shield-alt"></i> Security Features</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success"></i> End-to-end encryption</li>
                        <li><i class="fas fa-check text-success"></i> Salted password hashing</li>
                        <li><i class="fas fa-check text-success"></i> Data integrity with MAC</li>
                        <li><i class="fas fa-check text-success"></i> Secure key management</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle"></i> About</h6>
                    <p class="small">
                        This application demonstrates advanced security features including
                        encryption, authentication, and data integrity verification.
                    </p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <small>&copy; 2024 Secure Laravel Application. All data is encrypted and protected.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
