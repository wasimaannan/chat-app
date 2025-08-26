@extends('layout')

@section('title', 'Login - Secure App')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-sign-in-alt"></i> 
                    Secure Login
                    <span class="security-badge">Encrypted</span>
                </h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Security Notice:</strong> All credentials are encrypted and verified using advanced security protocols.
                </div>
                
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input 
                            type="email" 
                            class="form-control encrypted-field @error('email') is-invalid @enderror" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required
                            placeholder="Enter your email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input 
                            type="password" 
                            class="form-control @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            required
                            placeholder="Enter your password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login Securely
                        </button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Don't have an account?</p>
                    <a href="{{ route('register') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-user-plus"></i> Create Secure Account
                    </a>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt text-success"></i>
                        <strong>Security Features:</strong> Password verification with salt, encrypted data transmission, and MAC integrity checks.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
