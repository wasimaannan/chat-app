@extends('layout')

@section('content')
<h2>Sent Messages</h2>
<a href="{{ route('messages.inbox') }}">Inbox</a>
<hr>
@foreach($messages as $m)
    <div style="border:1px solid #ccc; padding:8px; margin-bottom:6px;">
        <strong>To:</strong> {{ $m->receiver->id }}<br>
        <div>{{ $m->decrypted_body }}</div>
        <small>{{ $m->created_at }}</small>
    </div>
@endforeach
{{ $messages->links() }}
@endsection
