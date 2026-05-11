<?php

namespace App\Http\Middleware;

use App\Support\JwtService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->jwtService->userFromToken($request->bearerToken());
        if ($user === null) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $user->isActive()) {
            return new JsonResponse([
                'message' => 'Account is not active.',
                'status' => $user->status,
            ], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
