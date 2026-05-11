<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $this->jwtService->issueToken($user);

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $user = User::query()->where('email', $data['email'])->firstOrFail();

        if (! $user->isActive()) {
            Auth::logout();

            return response()->json([
                'message' => $user->status === User::STATUS_BANNED
                    ? 'This account has been banned. Please contact support.'
                    : 'This account is suspended. Please contact support.',
                'status' => $user->status,
            ], 403);
        }

        $token = $this->jwtService->issueToken($user);

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if ($token !== null) {
            $this->jwtService->invalidateToken($token);
        }

        return response()->json(['ok' => true]);
    }

    public function user(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json($this->userPayload($u));
    }

    /**
     * @return array{id: int, name: string, email: string, avatar_url: ?string, is_admin: bool}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'is_admin' => (bool) $user->is_admin,
        ];
    }
}
