<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->user() && $request->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('The provided credentials do not match our records.'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->is_admin) {
            Auth::logout();

            return back()->withErrors([
                'email' => __('This account is not authorised for admin access.'),
            ])->onlyInput('email');
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => __('This account is currently :status. Please contact support.', [
                    'status' => $user->status,
                ]),
            ])->onlyInput('email');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
