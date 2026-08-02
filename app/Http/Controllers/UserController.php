<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use App\Services\UserQueryService;
use DomainException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserManagementService $userManagementService,
        private UserQueryService $userQueryService,
    ) {
    }

    public function index(Request $request)
    {
        $search = $request->filled('buscar') ? (string) $request->string('buscar') : null;

        return view('users.index', [
            'users' => $this->userQueryService->paginated($search),
        ]);
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $this->userManagementService->create($request->userData());

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        return view('users.edit', ['user' => $usuario]);
    }

    public function update(UpdateUserRequest $request, User $usuario)
    {
        $this->userManagementService->update($usuario, $request->userData());

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        try {
            $this->userManagementService->delete($usuario, auth()->id());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    public function toggleActive(User $usuario)
    {
        try {
            $this->userManagementService->toggleActive($usuario, auth()->id());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Estado del usuario actualizado.');
    }
}
