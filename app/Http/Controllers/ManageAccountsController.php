<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class ManageAccountsController extends Controller
{
    public function __construct(private AccountService $accountService) {}

    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('ManageAccounts', [
            'users' => $this->accountService->listUsers(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'account_type' => ['required', 'in:End_User,Biomed_Technician,Admin'],
        ]);

        $this->accountService->updateAccountType($user, $validated['account_type']);

        return redirect()->back();
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        $this->ensureTechnician($user);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->accountService->updatePassword($user, $validated['password']);

        return redirect()->back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        $this->ensureTechnician($user);

        $this->accountService->deleteUser($user);

        return redirect()->back();
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->account_type === 'Admin', 403);
    }

    private function ensureTechnician(User $user): void
    {
        abort_unless($user->account_type === 'Biomed_Technician', 403);
    }
}