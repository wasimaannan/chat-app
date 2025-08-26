@extends('layout')

@section('title', 'Register - Secure App')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-plus"></i> 
                    Create Secure Account
                    <span class="security-badge">Encrypted</span>
                </h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Privacy Protection:</strong> All personal information will be encrypted before storage. 
                    Passwords are hashed with unique salts for maximum security.
                </div>
                
                <form action="{{ route('register') }}" method="POST" id="registerForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user"></i> Full Name *
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control encrypted-field @error('name') is-invalid @enderror" 
                                    id="name" 
                                    name="name" 
                                    value="{{ old('name') }}" 
                                    required
                                    placeholder="Enter your full name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-lock text-success"></i> Will be encrypted
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email Address *
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
                                <small class="text-muted">
                                    <i class="fas fa-lock text-success"></i> Will be encrypted
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> Phone Number
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control encrypted-field @error('phone') is-invalid @enderror" 
                                    id="phone" 
                                    name="phone" 
                                    value="{{ old('phone') }}"
                                    placeholder="Enter your phone number">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-lock text-success"></i> Will be encrypted
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">
                                    <i class="fas fa-birthday-cake"></i> Date of Birth
                                </label>
                                <input 
                                    type="date" 
                                    class="form-control encrypted-field @error('date_of_birth') is-invalid @enderror" 
                                    id="date_of_birth" 
                                    name="date_of_birth" 
                                    value="{{ old('date_of_birth') }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-lock text-success"></i> Will be encrypted
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Address
                        </label>
                        <textarea 
                            class="form-control encrypted-field @error('address') is-invalid @enderror" 
                            id="address" 
                            name="address" 
                            rows="3"
                            placeholder="Enter your address">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            <i class="fas fa-lock text-success"></i> Will be encrypted
                        </small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Password *
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    id="password" 
                                    name="password" 
                                    required
                                    placeholder="Create a strong password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="passwordStrength" class="mt-1"></div>
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt text-success"></i> Will be hashed with salt
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">
                                    <i class="fas fa-lock"></i> Confirm Password *
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control @error('password_confirmation') is-invalid @enderror" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    required
                                    placeholder="Confirm your password">
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="passwordMatch" class="mt-1"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Password Requirements:</h6>
                            <ul class="mb-0 small">
                                <li>At least 8 characters long</li>
                                <li>Include uppercase and lowercase letters</li>
                                <li>Include at least one number</li>
                                <li>Include at least one special character</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Create Secure Account
                        </button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Already have an account?</p>
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
                
                <div class="mt-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-shield-alt text-success"></i> Security Guarantees:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> All personal data encrypted</li>
                                        <li><i class="fas fa-check text-success"></i> Passwords salted and hashed</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Data integrity verification</li>
                                        <li><i class="fas fa-check text-success"></i> Secure key management</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
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
        else feedback.push('At least 8 characters');
        
        if (/[A-Z]/.test(password)) strength++;
        else feedback.push('Uppercase letter');
        
        if (/[a-z]/.test(password)) strength++;
        else feedback.push('Lowercase letter');
        
        if (/[0-9]/.test(password)) strength++;
        else feedback.push('Number');
        
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
        else feedback.push('Special character');
        
        let strengthText = '';
        let strengthClass = '';
        
        if (strength >= 4) {
            strengthText = 'Strong';
            strengthClass = 'text-success';
        } else if (strength >= 3) {
            strengthText = 'Medium';
            strengthClass = 'text-warning';
        } else {
            strengthText = 'Weak';
            strengthClass = 'text-danger';
        }
        
        strengthDiv.innerHTML = `<small class="${strengthClass}">
            Strength: ${strengthText}
            ${feedback.length > 0 ? ' (Missing: ' + feedback.join(', ') + ')' : ''}
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
                matchDiv.innerHTML = '<small class="text-danger">Passwords do not match</small>';
            }
        } else {
            matchDiv.innerHTML = '';
        }
    }
    
    document.getElementById('password_confirmation').addEventListener('input', checkPasswordMatch);
    document.getElementById('password').addEventListener('input', checkPasswordMatch);
</script>
@endsection
