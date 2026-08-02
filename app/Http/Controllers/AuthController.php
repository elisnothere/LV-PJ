<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(LoginRequest $request)
    {
        $user = $this->authService->authenticate($request->credentials(), $request->boolean('remember'));

        $request->session()->regenerate();

        $redirect = $user->role === 'admin' ? route('dashboard') : route('catalog.index');

        return redirect()->intended($redirect);
    }

    public function register()
    {
        return view('auth.register');
    }

    public function storeRegister(RegisterRequest $request)
    {
        $user = $this->authService->registerCustomer($request->registrationData());

        $this->authService->login($user);
        $request->session()->regenerate();

        return redirect()->route('catalog.index');
    }

    public function logout(Request $request)
    {
        $this->authService->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
