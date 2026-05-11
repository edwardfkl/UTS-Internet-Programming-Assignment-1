<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_can_update_basic_fields_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'is_admin' => '0',
                'status' => User::STATUS_SUSPENDED,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame(User::STATUS_SUSPENDED, $user->status);
        $this->assertFalse((bool) $user->is_admin);
    }

    public function test_admin_can_change_user_password_via_confirmed_input(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'status' => User::STATUS_ACTIVE,
                'password' => 'fresh-secret',
                'password_confirmation' => 'fresh-secret',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('fresh-secret', $user->fresh()->password));
    }

    public function test_password_must_match_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $user))
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'status' => User::STATUS_ACTIVE,
                'password' => 'pw-one',
                'password_confirmation' => 'pw-two',
            ])
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHasErrors(['password']);
    }

    public function test_admin_cannot_change_email_to_one_already_in_use(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $user))
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => 'taken@example.com',
                'status' => User::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHasErrors(['email']);

        $this->assertSame('mine@example.com', $user->fresh()->email);
        $this->assertSame('taken@example.com', $other->fresh()->email);
    }

    public function test_admin_cannot_disable_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'is_admin' => '1',
                'status' => User::STATUS_SUSPENDED,
            ])
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHasErrors(['status']);

        $this->assertSame(User::STATUS_ACTIVE, $admin->fresh()->status);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors(['delete']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_delete_last_admin_user(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->from(route('admin.users.index'));

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors(['delete']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
