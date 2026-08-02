<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function authenticate(array $credentials, bool $remember = false): User
    {
        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales ingresadas no son correctas.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'El usuario esta inactivo.',
            ]);
        }

        return $user;
    }

    public function registerCustomer(array $data): User
    {
        return User::create([
            ...$data,
            'role' => 'cliente',
            'active' => true,
        ]);
    }

    public function login(User $user): void
    {
        Auth::login($user);
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
