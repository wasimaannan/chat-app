@extends('layout')

@section('content')
<h2>Conversation with User #{{ $other->id }}</h2>
<a href="{{ route('messages.inbox') }}">Back to Inbox</a>
<div style="margin-top:1rem;">
@foreach($messages as $m)
    <div style="margin-bottom:8px;">
        <strong>{{ $m->sender_id == auth()->id() ? 'You' : 'User '.$other->id }}:</strong>
        {{ $m->decrypted_body }}
        <small>{{ $m->created_at }}</small>
    </div>
@endforeach
</div>
<form method="POST" action="{{ route('messages.store') }}" style="margin-top:1rem;">
    @csrf
    <input type="hidden" name="receiver_id" value="{{ $other->id }}">
    <textarea name="body" required></textarea>
    <button type="submit">Send</button>
</form>
@endsection
