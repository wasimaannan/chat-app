@extends('layout')

@section('title','Profile - chatty_cat')

@section('content')

<div class="profile-banner mb-0 position-relative">
    <div class="profile-banner-img"></div>
    <div class="profile-banner-actions position-absolute top-0 end-0 p-3">
        <a href="{{ route('posts.index') }}" class="btn btn-newsfeed-glass btn-lg px-4 py-2 me-2 shadow" style="font-size:1.15rem;font-weight:700;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border-radius:1.2rem;border:2px solid #ff4e50;color:#ff4e50;transition:all 0.2s;box-shadow:0 4px 24px 0 rgba(255,78,80,0.12);">
            <i class="fas fa-newspaper me-1"></i> <span style="background:linear-gradient(90deg,#f9d423,#ff4e50);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Newsfeed</span>
        </a>
        <style>
        .btn-newsfeed-glass {
            background: linear-gradient(135deg, #fff 60%, #f9f9f9 100%) !important;
            color: #ff4e50 !important;
            border: 2px solid #ff4e50 !important;
            box-shadow: 0 4px 24px 0 rgba(255,78,80,0.12);
            border-radius: 1.2rem;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-newsfeed-glass:hover {
            background: linear-gradient(135deg, #fffbe6 60%, #ffe3e3 100%) !important;
            color: #ff4e50 !important;
        }
        </style>
        </a>
        <a href="{{ route('chat.index') }}" class="btn btn-chat-white btn-lg px-4 py-2 me-2" style="font-size:1.15rem;font-weight:700;"><i class="fas fa-comments me-1"></i> Chat</a>
        <form action="{{ route('logout') }}" method="POST" class="d-inline">@csrf<button class="btn btn-logout-white btn-lg px-4 py-2" style="font-size:1.15rem;font-weight:700;"><i class="fas fa-sign-out-alt me-1"></i> Logout</button></form>
    </div>
    <div class="profile-avatar-outer">
        @php
            $pic = $user->getDecryptedProfilePictureBase64();
        @endphp
        <div class="position-relative d-inline-block" style="width:120px;height:120px;">
            @if($pic)
                <img src="data:{{ $pic['mime'] }};base64,{{ $pic['base64'] }}" alt="Profile Picture" class="profile-avatar-img" id="profilePicImg" style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:4px solid #fff;box-shadow:0 2px 12px #e0e7ff;cursor:pointer;">
                <button type="button" id="changePicBtn" class="btn btn-light position-absolute top-50 start-50 translate-middle" style="display:none;border-radius:1rem;padding:0.25rem 0.75rem;font-size:0.9rem;z-index:2;">Change</button>
            @else
                <div class="profile-avatar-lg d-flex align-items-center justify-content-center" id="profileInitial" style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:4px solid #fff;box-shadow:0 2px 12px #e0e7ff;cursor:pointer;position:relative;">
                    {{ strtoupper(mb_substr($decryptedData['name'] ?? 'U',0,1)) }}
                </div>
            @endif
            <!-- Floating + button -->
            <!-- + button removed -->
        </div>
        <!-- Hidden file input outside the form -->
        <form id="profilePicUploadForm" method="POST" action="{{ route('profile') }}" enctype="multipart/form-data" style="display:none;">
            @csrf
            <input type="file" id="profilePicInput" name="profile_picture" accept="image/*">
        </form>
    </div>
</div>
<div class="container profile-main d-flex flex-column align-items-center" style="margin-top: 0;">
<script>
// + button logic removed
</script>
    <div class="text-center mt-4 mb-3" style="margin-top: 60px !important;">
        <div class="profile-name-lg">{{ $decryptedData['name'] ?? 'Unknown User' }}</div>
        <div class="profile-handle">@user{{ $user->id }}</div>
        <div class="profile-joined">Joined {{ $user->created_at->format('F Y') }}</div>
        <div class="profile-bio mt-3 mb-4">{{ $decryptedData['bio'] ?? 'No bio set. Tell us about yourself!' }}</div>
        <div class="profile-stats d-flex justify-content-center gap-4 mb-4">
            <div><span class="stat-num">0</span> <span class="stat-label">Posts</span></div>
            <div><span class="stat-num">0</span> <span class="stat-label">Friends</span></div>
            <div><span class="stat-num">0</span> <span class="stat-label">Likes</span></div>
        </div>
    </div>
    <div class="dropdown mb-5" style="max-width: 520px; width: 100%;">
        <button class="btn btn-gradient w-100 mb-2" type="button" id="editProfileDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:1.2rem;font-weight:700;">
            Edit Profile <i class="fas fa-chevron-down ms-1"></i>
        </button>
        <div class="dropdown-menu w-100 p-0 border-0 shadow show" id="editProfileDropdownMenu" style="display:none;position:static;float:none;min-width:100%;background:transparent;box-shadow:none;">
            <div class="profile-card glassy-card border-0 shadow-lg mt-0" style="max-width: 520px; width: 100%;">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile') }}" autocomplete="off" id="profileForm" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small text-uppercase">Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*" class="form-control profile-input">
                        <small class="text-muted">Max 2MB. JPG/PNG only.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-uppercase">Full Name</label>
                        <input type="text" name="name" class="form-control profile-input @error('name') is-invalid @enderror" value="{{ old('name', $decryptedData['name'] ?? '') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-uppercase">Email (read only)</label>
                        <input type="text" class="form-control profile-input" value="{{ $decryptedData['email'] ?? '' }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-uppercase">Phone</label>
                        <input type="text" name="phone" class="form-control profile-input @error('phone') is-invalid @enderror" value="{{ old('phone', $decryptedData['phone'] ?? '') }}">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-uppercase">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control profile-input @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $decryptedData['date_of_birth'] ?? '') }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-uppercase">Address</label>
                        <textarea name="address" rows="3" class="form-control profile-input @error('address') is-invalid @enderror">{{ old('address', $decryptedData['address'] ?? '') }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-uppercase">Bio</label>
                        <textarea name="bio" rows="2" class="form-control profile-input">{{ old('bio', $decryptedData['bio'] ?? ($decryptedData['bio'] ?? '')) }}</textarea>
                    </div>
                </div>
                        <div class="d-flex justify-content-end mt-4 gap-2">
                            <button type="reset" class="btn btn-outline-accent">Reset</button>
                            <button type="submit" class="btn btn-gradient"><i class="fas fa-save me-1"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</div>

<style>
    body {
        background: linear-gradient(120deg, #fbc2eb 0%, #e0e7ff 100%) !important;
    }
    .profile-banner {
        width: 100vw;
        min-height: 220px;
        position: relative;
        margin-left: calc(-50vw + 50%);
        margin-right: calc(-50vw + 50%);
        background: transparent;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    margin-top: -56px; /* Pull banner further up to sit directly under the header/logo */
        z-index: 1;
    }
    .profile-banner-img {
        width: 100%;
        height: 220px;
        background: linear-gradient(120deg, #fbc2eb 0%, #a18cd1 100%);
        background-size: cover;
        border-bottom-left-radius: 2.5rem;
        border-bottom-right-radius: 2.5rem;
        border-top-left-radius: 0 !important;
        border-top-right-radius: 0 !important;
        box-shadow: 0 8px 32px 0 #a18cd122;
        margin: 0;
        padding: 0;
    }
    .profile-banner-actions {
        z-index: 2;
    }
    .profile-avatar-outer {
        position: absolute;
        left: 50%;
        bottom: -55px;
        transform: translateX(-50%);
        z-index: 10;
        display: flex;
        justify-content: center;
        width: 100%;
        pointer-events: none;
    }
    .profile-avatar-lg {
        pointer-events: auto;
    }
    .profile-avatar-lg {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fff0fa 0%, #e0e7ff 100%);
        color: #7f53ac;
        font-size: 3.5rem;
        font-weight: 900;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 16px -4px #fbc2eb55;
        border: 3.5px solid #e0c3fc;
    }
    .profile-main {
        margin-top: 60px;
        max-width: 700px;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
    }
    .profile-name-lg {
        font-family: 'Nunito', cursive, sans-serif;
        font-weight: 900;
        font-size: 2.2rem;
        color: #7f53ac;
    }
    .profile-handle {
        color: #a18cd1;
        font-size: 1.1rem;
        font-family: 'Nunito', cursive, sans-serif;
        margin-bottom: 0.2rem;
    }
    .profile-joined {
        color: #b39ddb;
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
    }
    .profile-bio {
        color: #7f53ac;
        font-size: 1.1rem;
        font-family: 'Nunito', cursive, sans-serif;
        background: rgba(255,255,255,0.7);
        border-radius: 1.2rem;
        padding: 0.7rem 1.2rem;
        display: inline-block;
        min-width: 180px;
        max-width: 90vw;
    }
    .profile-stats {
        font-family: 'Nunito', cursive, sans-serif;
        font-size: 1.1rem;
        color: #a18cd1;
    }
    .stat-num {
        font-weight: 800;
        color: #7f53ac;
        font-size: 1.2rem;
    }
    .stat-label {
        color: #b39ddb;
        font-weight: 600;
        margin-left: 2px;
    }
    .btn-chat-white {
        background: #fff;
        color: #a18cd1;
        border: none;
        border-radius: 1.2rem;
        font-weight: 700;
        box-shadow: 0 2px 12px 0 #e0c3fc44;
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .btn-chat-white:hover, .btn-chat-white:focus {
        background: linear-gradient(90deg, #fbc2eb 0%, #e0e7ff 100%);
        color: #7f53ac;
        box-shadow: 0 4px 16px -4px #a18cd1aa;
    }
    .btn-logout-white {
        background: #fff;
        color: #e57373;
        border: none;
        border-radius: 1.2rem;
        font-weight: 700;
        box-shadow: 0 2px 12px 0 #e0c3fc44;
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .btn-logout-white:hover, .btn-logout-white:focus {
        background: linear-gradient(90deg, #fbc2eb 0%, #e57373 100%);
        color: #fff;
        box-shadow: 0 4px 16px -4px #e57373aa;
    }
</style>

<style>
    .profile-title {
        font-family: 'Nunito', cursive, sans-serif;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #7f53ac;
        background: linear-gradient(90deg, #fbc2eb 0%, #a18cd1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .glassy-card {
        background: rgba(255,255,255,0.85);
        border-radius: 2.2rem;
        box-shadow: 0 8px 32px 0 #a18cd122, 0 1.5px 8px 0 #fbc2eb22;
        backdrop-filter: blur(12px) saturate(120%);
        border: 1.5px solid #e0c3fc;
    }
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fbc2eb 0%, #e0e7ff 100%);
        color: #7f53ac;
        font-size: 2.5rem;
        font-weight: 900;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px -2px #fbc2eb55;
        border: 2.5px solid #e0c3fc;
    }
    .profile-name {
        font-family: 'Nunito', cursive, sans-serif;
        font-weight: 700;
        color: #a06cd5;
    }
    .profile-input {
        background: rgba(255,255,255,0.7) !important;
        border-radius: 1.2rem !important;
        border: 1.5px solid #e0c3fc !important;
        color: #7f53ac !important;
        font-family: 'Nunito', cursive, sans-serif;
        font-weight: 600;
        box-shadow: 0 2px 8px 0 #fbc2eb22;
        transition: border 0.18s, box-shadow 0.18s;
    }
    .profile-input:focus {
        border: 1.5px solid #a18cd1 !important;
        box-shadow: 0 4px 16px -4px #a18cd1aa;
    }
    .btn-outline-accent {
        border: 1.5px solid #a18cd1;
        color: #a18cd1;
        background: transparent;
        font-weight: 700;
        border-radius: 1.2rem;
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .btn-outline-accent:hover, .btn-outline-accent:focus {
        background: linear-gradient(90deg, #fbc2eb 0%, #a18cd1 100%);
        color: #fff;
        box-shadow: 0 4px 16px -4px #a18cd1aa;
    }
    .btn-gradient {
        background: linear-gradient(90deg, #a18cd1 0%, #fbc2eb 100%);
        color: #fff;
        font-weight: 700;
        border: none;
        border-radius: 1.2rem;
        box-shadow: 0 2px 8px 0 #fbc2eb44;
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .btn-gradient:hover, .btn-gradient:focus {
        background: linear-gradient(90deg, #7f53ac 0%, #fbc2eb 100%);
        color: #fff;
        box-shadow: 0 4px 16px -4px #a18cd1aa;
    }
</style>
@endsection

@section('scripts')
<script>
// Auto-resize textarea
const addr = document.querySelector('textarea[name="address"]');
if(addr){
    const resize=()=>{addr.style.height='auto';addr.style.height=(addr.scrollHeight)+"px";};
    addr.addEventListener('input', resize); resize();
}
// Toggle Edit Profile dropdown
const editBtn = document.getElementById('editProfileDropdownBtn');
const dropdownMenu = document.getElementById('editProfileDropdownMenu');
if(editBtn && dropdownMenu){
    editBtn.addEventListener('click', function(e){
        e.preventDefault();
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });
}
</script>
@endsection
