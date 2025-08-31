@extends('layout')

@section('content')
<h2>Conversation with User #{{ $other->id }}</h2>
<a href="{{ route('messages.inbox') }}">Back to Inbox</a>
<div style="margin-top:1rem;">
@foreach($messages as $m)
    <div style="margin-bottom:8px;">
        <strong>{{ $m->sender_id == auth()->id() ? 'You' : 'User '.$other->id }}:</strong>
        {{ $m->decrypted_body }}
        @if($m->decrypted_image_base64)
            <br>
            <img src="data:image/*;base64,{{ $m->decrypted_image_base64 }}" alt="Image" style="max-width:200px; max-height:200px; border-radius:8px; margin-top:4px;" />
        @endif
        <small>{{ $m->created_at }}</small>
    </div>
@endforeach
</div>
<form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" class="p-2 d-flex gap-2 align-items-end border-top" autocomplete="off" style="background:linear-gradient(120deg,#fbc2eb 0%,#e0c3fc 100%);border-radius:0 0 2rem 2rem;box-shadow:0 -2px 12px -4px #fbc2eb33;">
    @csrf
    <input type="hidden" name="receiver_id" value="{{ $other->id }}">
    <button type="button" id="addPicBtn" style="padding:6px 18px; border:1px solid #ccc; border-radius:4px; background:#f8f9fa; color:#333; font-size:15px; cursor:pointer; order:-1;">Add Picture</button>
    <input type="file" id="photoInput" name="image" accept="image/*" style="display:none;">
    <textarea id="messageBody" name="body" class="form-control" rows="1" placeholder="Type a message..." style="resize:none;max-height:160px;background:#fff0fa;border:2px solid #fbc2eb;border-radius:1.5rem;color:#7f53ac;font-family:'Nunito',cursive;box-shadow:0 2px 8px -2px #fbc2eb33;"></textarea>
    <button type="submit" class="d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#8ec5fc,#e0c3fc); border:none; border-radius:50%; width:38px; height:38px; box-shadow:0 2px 6px rgba(0,0,0,0.08);">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#6c3483;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2l-7 20-4-9-9-4 20-7z" />
        </svg>
    </button>
</form>
<script>
    document.getElementById('addPicBtn').addEventListener('click', function() {
        document.getElementById('photoInput').click();
    });
</script>
@endsection
