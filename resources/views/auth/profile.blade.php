@extends('layout')

@section('title','Profile - chatty_cat')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0" style="font-weight:600;">Your Profile</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('chat.index') }}" class="btn btn-sm btn-outline-light"><i class="fas fa-comments"></i> Chat</a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></button></form>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="background:#1c1c1c;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:70px;height:70px;background:#2a2a2a;font-size:1.75rem;font-weight:600;">
                        {{ strtoupper(mb_substr($decryptedData['name'] ?? 'U',0,1)) }}
                    </div>
                    <div>
                        <div class="h5 mb-1">{{ $decryptedData['name'] ?? 'Unknown User' }}</div>
                        <div class="text-muted small">Member ID: #{{ $user->id }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile') }}" autocomplete="off" id="profileForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase">Full Name</label>
                            <input type="text" name="name" class="form-control bg-dark text-light border-secondary @error('name') is-invalid @enderror" value="{{ old('name', $decryptedData['name'] ?? '') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase">Email (read only)</label>
                            <input type="text" class="form-control bg-dark text-light border-secondary" value="{{ $decryptedData['email'] ?? '' }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase">Phone</label>
                            <input type="text" name="phone" class="form-control bg-dark text-light border-secondary @error('phone') is-invalid @enderror" value="{{ old('phone', $decryptedData['phone'] ?? '') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control bg-dark text-light border-secondary @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $decryptedData['date_of_birth'] ?? '') }}">
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-uppercase">Address</label>
                            <textarea name="address" rows="3" class="form-control bg-dark text-light border-secondary @error('address') is-invalid @enderror">{{ old('address', $decryptedData['address'] ?? '') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4 gap-2">
                        <button type="reset" class="btn btn-outline-secondary">Reset</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    {{-- Removed encryption notice per request --}}
    </div>
</div>
@endsection

@section('scripts')
<script>
// Auto-resize textarea
const addr = document.querySelector('textarea[name="address"]');
if(addr){
  const resize=()=>{addr.style.height='auto';addr.style.height=(addr.scrollHeight)+"px";};
  addr.addEventListener('input', resize); resize();
}
</script>
@endsection
