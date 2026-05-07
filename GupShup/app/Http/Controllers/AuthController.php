<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle registration.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'public_key' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'public_key' => $request->public_key,
            'avatar_color' => User::generateAvatarColor(),
            'is_online' => true,
            'last_seen' => now(),
        ]);

        Auth::login($user);

        return redirect('/chat');
    }

    /**
     * Show the login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials are incorrect.',
            ])->withInput($request->only('email'));
        }

        Auth::login($user, $request->boolean('remember'));

        // Update online status
        $user->update([
            'is_online' => true,
            'last_seen' => now(),
        ]);

        return redirect('/chat');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */

        $user = Auth::user();
        if ($user) {
            $user->update([
                'is_online' => false,
                'last_seen' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Update user's public key (API endpoint).
     */
    public function updatePublicKey(Request $request)
    {
        $request->validate([
            'public_key' => ['required', 'string'],
        ]);

        /** @var \App\Models\User $user */

        $user = Auth::user();
        $user->update(['public_key' => $request->public_key]);

        return response()->json(['success' => true]);
    }
}
