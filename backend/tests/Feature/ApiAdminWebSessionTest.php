<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAdminWebSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_returns_ok_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/web-session')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_store_forbids_non_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);

        $this->postJson('/api/admin/web-session')->assertForbidden();
    }

    public function test_destroy_returns_ok_for_non_admin_without_error(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/admin/web-session')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
