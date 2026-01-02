<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $input = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginField = $input['email'];
        $password = $input['password'];

        // Try to login with email first
        if (Auth::attempt(['email' => $loginField, 'password' => $password])) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // If email login fails, try with username
        if (Auth::attempt(['username' => $loginField, 'password' => $password])) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email/Username atau password tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
