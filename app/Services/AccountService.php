<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AccountService
{
    public function listUsers(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'account_type', 'avatar', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'created_at' => optional($user->created_at)->toDateString(),
            ])
            ->all();
    }

    public function updateAccountType(User $user, string $accountType): void
    {
        $user->update(['account_type' => $accountType]);
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    public function deleteUser(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();
    }
}
