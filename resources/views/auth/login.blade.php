@extends('layout')

@section('title', 'Login - chatty_cat')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-lg border-0">
            <div class="card-body p-5">
                <h3 class="mb-4 text-center" style="color:#7f53ac;font-weight:800;letter-spacing:.5px;"><i class="fas fa-cat"></i> Welcome back</h3>
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                        <input 
                            type="email" 
                            class="form-control form-control-lg @error('email') is-invalid @enderror" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required
                            placeholder="you@example.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label"><i class="fas fa-lock"></i> Password</label>
                        <input 
                            type="password" 
                            class="form-control form-control-lg @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            required
                            placeholder="••••••••">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4 tiny-hint">
                        <span class="text-muted">Forgot password?</span>
                        <a href="#" class="link-secondary">Reset</a>
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-gradient btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <span class="text-muted small">No account yet?</span>
                    <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4 ms-2">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
