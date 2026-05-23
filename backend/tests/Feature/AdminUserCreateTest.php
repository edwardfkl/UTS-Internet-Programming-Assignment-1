<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_can_create_user_with_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Phone Customer',
                'email' => 'phone@example.com',
                'password' => 'secret-pass',
                'password_confirmation' => 'secret-pass',
                'status' => User::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'phone@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->assertFalse($user->is_admin);
    }

    public function test_create_requires_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Bad',
                'email' => 'bad@example.com',
                'password' => 'secret-pass',
                'password_confirmation' => 'mismatch',
                'status' => User::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors(['password']);
    }
}
