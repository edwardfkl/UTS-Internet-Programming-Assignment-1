<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_token_has_three_url_safe_segments(): void
    {
        $user = User::factory()->create();
        $jwt = new JwtService();

        $token = $jwt->issueToken($user);
        $parts = explode('.', $token);

        $this->assertCount(3, $parts);
        foreach ($parts as $part) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $part);
        }
    }

    public function test_user_from_token_resolves_subject(): void
    {
        $user = User::factory()->create();
        $jwt = new JwtService();

        $token = $jwt->issueToken($user);
        $resolved = $jwt->userFromToken($token);

        $this->assertNotNull($resolved);
        $this->assertSame($user->id, $resolved->id);
    }

    public function test_user_from_token_rejects_blank_or_malformed_tokens(): void
    {
        $jwt = new JwtService();

        $this->assertNull($jwt->userFromToken(null));
        $this->assertNull($jwt->userFromToken(''));
        $this->assertNull($jwt->userFromToken('not.a.jwt'));
        $this->assertNull($jwt->userFromToken('only-one-segment'));
    }

    public function test_user_from_token_rejects_signature_tampering(): void
    {
        $user = User::factory()->create();
        $jwt = new JwtService();

        $token = $jwt->issueToken($user);
        $tampered = substr($token, 0, -4) . 'AAAA';

        $this->assertNull($jwt->userFromToken($tampered));
    }

    public function test_invalidate_token_blacklists_jti(): void
    {
        $user = User::factory()->create();
        $jwt = new JwtService();

        $token = $jwt->issueToken($user);
        $this->assertNotNull($jwt->userFromToken($token));

        $jwt->invalidateToken($token);

        $this->assertNull($jwt->userFromToken($token));
    }
}
