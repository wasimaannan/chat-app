@extends('layout')

@section('title', 'Register - chatty_cat')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-lg border-0">
            <div class="card-body p-5">
                <h3 class="mb-4 text-center" style="color:#7f53ac;font-weight:800;letter-spacing:.5px;"><i class="fas fa-user-plus"></i> Create your account</h3>
                <form action="{{ route('register') }}" method="POST" id="registerForm" enctype="multipart/form-data">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="mb-4">
                        <label for="profile_picture" class="form-label"><i class="fas fa-image"></i> Profile Picture</label>
                        <input type="file" class="form-control form-control-lg @error('profile_picture') is-invalid @enderror" id="profile_picture" name="profile_picture" accept="image/*">
                        @error('profile_picture')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="name" class="form-label"><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Your name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="phone" class="form-label"><i class="fas fa-phone"></i> Phone</label>
                            <input type="text" class="form-control form-control-lg @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Optional">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="date_of_birth" class="form-label"><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                            <input type="date" class="form-control form-control-lg @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="address" class="form-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea class="form-control form-control-lg @error('address') is-invalid @enderror" id="address" name="address" rows="2" placeholder="Optional">{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="password" class="form-label"><i class="fas fa-lock"></i> Password *</label>
                            <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Create password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="passwordStrength" class="mt-1"></div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="password_confirmation" class="form-label"><i class="fas fa-lock"></i> Confirm Password *</label>
                            <input type="password" class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password">
                            @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="passwordMatch" class="mt-1"></div>
                        </div>
                    </div>
                    <div class="small text-muted mb-3">Password must be 8+ chars with mixed case, number & symbol.</div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gradient btn-lg">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-4"><i class="fas fa-sign-in-alt"></i> Already have an account?</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Password strength checker
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('passwordStrength');
        
        let strength = 0;
        let feedback = [];
        
        if (password.length >= 8) strength++;
        else feedback.push('8 chars');
        
        if (/[A-Z]/.test(password)) strength++;
        else feedback.push('uppercase');
        
        if (/[a-z]/.test(password)) strength++;
        else feedback.push('lowercase');
        
        if (/[0-9]/.test(password)) strength++;
        else feedback.push('number');
        
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
        else feedback.push('symbol');
        
        const levels = ['Weak','Weak','Medium','Good','Strong','Strong'];
        const cls = strength >=4 ? 'text-success': strength==3? 'text-warning':'text-danger';
        
        strengthDiv.innerHTML = `<small class="${cls}">
            Strength: ${levels[strength]}
            ${feedback.length ? ' (Missing: ' + feedback.join(', ') + ')' : ''}
        </small>`;
    });
    
    // Password confirmation checker
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmation = document.getElementById('password_confirmation').value;
        const matchDiv = document.getElementById('passwordMatch');
        
        if (confirmation.length > 0) {
            if (password === confirmation) {
                matchDiv.innerHTML = '<small class="text-success">Passwords match</small>';
            } else {
                matchDiv.innerHTML = '<small class="text-danger">Mismatch</small>';
            }
        } else {
            matchDiv.innerHTML = '';
        }
    }
    
    document.getElementById('password_confirmation').addEventListener('input', checkPasswordMatch);
    document.getElementById('password').addEventListener('input', checkPasswordMatch);
</script>
@endsection
