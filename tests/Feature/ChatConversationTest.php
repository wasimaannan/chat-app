<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Http\Middleware\SecureAuth;

class ChatConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure encryption key generated for tests if app expects it.
        if (!config('app.key')) {
            $this->artisan('key:generate');
        }
    }

    private function makeUser(string $name, string $email): User
    {
        $u = new User();
        $u->setEncryptedData([
            'name' => $name,
            'email' => $email,
        ]);
        // Provide password_hash & salt fields expected by schema
        $salt = bin2hex(random_bytes(8));
        $u->password_salt = $salt;
        $u->password_hash = Hash::make('secret123'.$salt);
        $u->save();
        return $u;
    }

    public function test_open_and_send_direct_conversation(): void
    {
        $alice = $this->makeUser('Alice','alice@example.test');
        $bob   = $this->makeUser('Bob','bob@example.test');

        // Simulate auth by setting session user_id (align with custom currentUser fallback).
        $this->withSession(['user_id'=>$alice->id]);

        // Open direct conversation
    $this->withoutMiddleware(SecureAuth::class);
    $this->withoutExceptionHandling();
    $open = $this->postJson('/chat/open',[ 'user_id'=>$bob->id ]);
        $open->assertStatus(200)->assertJsonStructure(['conversation_id','messages']);
    $convId = (int)$open->json('conversation_id');

        // Send a message
        $send = $this->postJson('/chat/send',[ 'conversation_id'=>$convId, 'body'=>'Hello Bob' ]);
        $send->assertStatus(200)->assertJsonPath('message.body','Hello Bob');

        // Fetch messages list
    $messages = $this->getJson('/chat/conversations/'.$convId.'/messages');
    $messages->assertStatus(200);
    $this->assertSame('Hello Bob', $messages->json('messages.0.body'));
    }
}
