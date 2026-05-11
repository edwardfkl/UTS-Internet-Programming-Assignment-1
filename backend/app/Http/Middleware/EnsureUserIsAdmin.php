<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'Admin access only.');
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()?->invalidate();
            $request->session()?->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'email' => __('This account is currently :status. Please contact support.', [
                        'status' => $user->status,
                    ]),
                ]);
        }

        return $next($request);
    }
}
