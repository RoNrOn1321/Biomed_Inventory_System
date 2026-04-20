<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountService
{
    public function createUser(string $name, string $email, string $password, string $accountType, ?string $department = null): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'account_type' => $accountType,
            'department' => $department,
        ]);
    }

    public function listUsers(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'account_type', 'avatar', 'department', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'account_type' => $user->account_type,
                'avatar' => $user->avatar_storage_path ? '/storage/' . $user->avatar_storage_path : null,
                'created_at' => optional($user->created_at)->toDateString(),
            ])
            ->all();
    }

    public function updateProfile(User $user, string $name, ?string $department): void
    {
        $user->update(['name' => $name, 'department' => $department]);
    }

    public function updateAccountType(User $user, string $accountType): void
    {
        $user->update(['account_type' => $accountType]);
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => Hash::make($password)]);
    }

    public function deleteUser(User $user): void
    {
        if ($user->avatar_storage_path) {
            Storage::disk('public')->delete($user->avatar_storage_path);
        }

        $user->delete();
    }
}
