<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token(): void
    {
        $payload = [
            'name' => 'API Shopper',
            'email' => 'shopper@example.com',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'is_admin'], 'token'])
            ->assertJsonPath('user.email', 'shopper@example.com')
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', ['email' => 'shopper@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => 'X',
            'email' => 'taken@example.com',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'u@example.com',
            'password' => 'correcthorse12',
        ]);

        $this->postJson('/api/login', [
            'email' => 'u@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Invalid credentials.']);
    }

    public function test_login_returns_token_for_valid_user(): void
    {
        User::factory()->create([
            'email' => 'ok@example.com',
            'password' => 'password12',
        ]);

        $this->postJson('/api/login', [
            'email' => 'ok@example.com',
            'password' => 'password12',
        ])->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_endpoint_returns_payload_when_authenticated(): void
    {
        $user = User::factory()->create(['name' => 'Tok']);
        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('name', 'Tok');
    }
}
