@extends('layout')

@section('content')
<h2>Inbox</h2>
<a href="{{ route('messages.sent') }}">Sent</a>
<form method="POST" action="{{ route('messages.store') }}" style="margin-top:1rem;">
    @csrf
    <div>
        <label>To (User ID)</label>
        <input type="number" name="receiver_id" required>
    </div>
    <div>
        <label>Message</label>
        <textarea name="body" required></textarea>
    </div>
    <button type="submit">Send</button>
</form>
<hr>
@foreach($messages as $m)
    <div style="border:1px solid #ccc; padding:8px; margin-bottom:6px;">
        <strong>From:</strong> {{ $m->sender->id }} @if(!$m->read_at)<span style="color:red;">(unread)</span>@endif<br>
        <div>{{ $m->decrypted_body }}</div>
        <small>{{ $m->created_at }}</small>
    </div>
@endforeach
{{ $messages->links() }}
@endsection
