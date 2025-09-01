<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Conversation;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;

// Find a conversation with messages containing file_blob
$conv = \App\Models\Conversation::has('messages')->first();
if (!$conv) { echo "NO_CONV\n"; exit(0); }
// Simulate a request as the first participant
$firstUser = $conv->participants()->first()->user;
if (!$firstUser) { echo "NO_USER\n"; exit(0); }
// Fake session/auth
auth()->loginUsingId($firstUser->id);

$req = Request::create('/','GET',[]);
$controller = app(MessageController::class);
$response = $controller->messages($req, $conv->id);
// Dump JSON body
echo $response->getContent();
