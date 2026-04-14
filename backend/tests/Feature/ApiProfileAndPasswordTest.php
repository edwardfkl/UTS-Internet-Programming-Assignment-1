<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiProfileAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_profile_show_returns_shipping_fields(): void
    {
        $user = User::factory()->create([
            'phone' => '0400000000',
            'shipping_city' => 'Sydney',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('phone', '0400000000')
            ->assertJsonPath('shipping_city', 'Sydney');
    }

    public function test_profile_update_persists_changes(): void
    {
        $user = User::factory()->create(['name' => 'Old']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', [
            'name' => 'New Name',
            'phone' => '0411111111',
        ])->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('phone', '0411111111');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'phone' => '0411111111',
        ]);
    }

    public function test_password_update_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-secret12'),
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/password', [
            'current_password' => 'not-the-password',
            'password' => 'new-secret12',
            'password_confirmation' => 'new-secret12',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_password_update_succeeds_with_correct_current(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-secret12'),
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/password', [
            'current_password' => 'old-secret12',
            'password' => 'new-secret12',
            'password_confirmation' => 'new-secret12',
        ])->assertOk()
            ->assertJson(['ok' => true]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret12', $user->password));
    }
}
