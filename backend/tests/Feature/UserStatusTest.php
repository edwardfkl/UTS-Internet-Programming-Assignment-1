<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_api_login_rejects_suspended_account(): void
    {
        User::factory()->suspended()->create([
            'email' => 'suspended@example.com',
            'password' => 'password12',
        ]);

        $this->postJson('/api/login', [
            'email' => 'suspended@example.com',
            'password' => 'password12',
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', User::STATUS_SUSPENDED);
    }

    public function test_api_login_rejects_banned_account(): void
    {
        User::factory()->banned()->create([
            'email' => 'banned@example.com',
            'password' => 'password12',
        ]);

        $this->postJson('/api/login', [
            'email' => 'banned@example.com',
            'password' => 'password12',
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', User::STATUS_BANNED);
    }

    public function test_api_login_accepts_active_account(): void
    {
        User::factory()->create([
            'email' => 'active@example.com',
            'password' => 'password12',
        ]);

        $this->postJson('/api/login', [
            'email' => 'active@example.com',
            'password' => 'password12',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }

    public function test_jwt_protected_route_blocks_token_for_suspended_user(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtTokenFor($user);

        $user->status = User::STATUS_SUSPENDED;
        $user->save();

        $this->withToken($token)->getJson('/api/profile')
            ->assertStatus(403)
            ->assertJsonPath('status', User::STATUS_SUSPENDED);
    }

    public function test_admin_login_rejects_suspended_admin(): void
    {
        User::factory()->admin()->suspended()->create([
            'email' => 'sus-admin@example.com',
            'password' => 'password12',
        ]);

        $this->from(route('admin.login'))->post(route('admin.login'), [
            'email' => 'sus-admin@example.com',
            'password' => 'password12',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_middleware_logs_out_suspended_admin_in_session(): void
    {
        $admin = User::factory()->admin()->create();

        $admin->status = User::STATUS_SUSPENDED;
        $admin->save();

        $this->actingAs($admin)->get('/admin/users')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
