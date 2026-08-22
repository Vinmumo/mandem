<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_member_can_join_and_view_dashboard(): void
    {
        $response = $this->post('/join', [
            'name' => 'Mandem Member', 'email' => 'member@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'invite_code' => config('app.invite_code'), 'fpl_entry_id' => 12345,
        ]);
        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->get('/')->assertOk();
    }

    public function test_invalid_invite_is_rejected(): void
    {
        $this->post('/join', [
            'name'=>'Nope','email'=>'nope@example.com','password'=>'password123',
            'password_confirmation'=>'password123','invite_code'=>'wrong',
        ])->assertStatus(422);
    }
}
