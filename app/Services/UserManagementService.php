<?php

namespace App\Services;

use App\Models\User;
use DomainException;

class UserManagementService
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): void
    {
        $user->update($data);
    }

    public function delete(User $user, ?int $actorId): void
    {
        if ($actorId === $user->id) {
            throw new DomainException('No puede eliminar su propio usuario.');
        }

        $user->delete();
    }

    public function toggleActive(User $user, ?int $actorId): void
    {
        if ($actorId === $user->id) {
            throw new DomainException('No puede desactivar su propio usuario.');
        }

        $user->update(['active' => ! $user->active]);
    }
}
