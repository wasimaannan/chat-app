@extends('layout')

@section('title', 'Login - chatty_cat')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card" style="background:#1c1c1c;">
            <div class="card-body p-4">
                <h4 class="mb-4"><i class="fas fa-cat"></i> Welcome back</h4>
                
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
                    <p class="mb-2">No account?</p>
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-user-plus"></i> Register</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
