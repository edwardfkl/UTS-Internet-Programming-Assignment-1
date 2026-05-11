<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserStatusFlagsTest extends TestCase
{
    public function test_status_constants_match_known_values(): void
    {
        $this->assertSame('active', User::STATUS_ACTIVE);
        $this->assertSame('suspended', User::STATUS_SUSPENDED);
        $this->assertSame('banned', User::STATUS_BANNED);
    }

    public function test_statuses_list_is_active_first_then_others(): void
    {
        $this->assertSame(
            [User::STATUS_ACTIVE, User::STATUS_SUSPENDED, User::STATUS_BANNED],
            User::STATUSES,
        );
    }

    public function test_is_active_returns_true_only_for_active_status(): void
    {
        $user = new User();
        $user->status = User::STATUS_ACTIVE;
        $this->assertTrue($user->isActive());

        $user->status = User::STATUS_SUSPENDED;
        $this->assertFalse($user->isActive());

        $user->status = User::STATUS_BANNED;
        $this->assertFalse($user->isActive());
    }
}
