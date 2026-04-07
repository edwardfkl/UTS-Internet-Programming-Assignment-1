<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class SanctumBearer
{
    public static function user(Request $request): ?User
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return null;
        }
        $token = PersonalAccessToken::findToken($bearer);
        if (! $token || ! $token->tokenable instanceof User) {
            return null;
        }

        return $token->tokenable;
    }
}
