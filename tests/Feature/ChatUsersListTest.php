<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Http\Middleware\SecureAuth;

class ChatUsersListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!config('app.key')) { $this->artisan('key:generate'); }
    }

    private function makeUser(string $name, string $email): User
    {
        $u = new User();
        $u->setEncryptedData([
            'name' => $name,
            'email' => $email,
        ]);
        $salt = bin2hex(random_bytes(8));
        $u->password_salt = $salt;
        $u->password_hash = Hash::make('secret123'.$salt);
        $u->save();
        return $u;
    }

    public function test_users_list_returns_other_users()
    {
        $alice = $this->makeUser('Alice','alice@example.test');
        $bob   = $this->makeUser('Bob','bob@example.test');
        $carol = $this->makeUser('Carol','carol@example.test');

        $this->withSession(['user_id'=>$alice->id]);
        $this->withoutMiddleware(SecureAuth::class);

        $resp = $this->getJson('/chat/users');
        $resp->assertStatus(200)->assertJsonStructure(['users']);
        $users = $resp->json('users');
        // Should not include Alice (self)
        $ids = array_column($users,'id');
        $this->assertNotContains($alice->id,$ids);
        $this->assertContains($bob->id,$ids);
        $this->assertContains($carol->id,$ids);
    }
}
