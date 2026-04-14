<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\JobRequest;
use App\Services\ApprovalsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalsController extends Controller
{
    public function __construct(private ApprovalsService $approvalsService) {}

    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('Approvals', [
            'pendingJobRequests' => $this->approvalsService->pendingJobRequests(),
            'pendingEquipment' => $this->approvalsService->pendingEquipment(),
        ]);
    }

    public function approveJobRequest(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->approvalsService->approveJobRequest($jobRequest, $request->user()->id, $validated['notes'] ?? null);

        return redirect()->back();
    }

    public function rejectJobRequest(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->approvalsService->rejectJobRequest($jobRequest, $request->user()->id, $validated['notes'] ?? null);

        return redirect()->back();
    }

    public function approveEquipment(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->approvalsService->approveEquipment($equipment, $request->user()->id, $validated['notes'] ?? null);

        return redirect()->back();
    }

    public function rejectEquipment(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->approvalsService->rejectEquipment($equipment, $request->user()->id, $validated['notes'] ?? null);

        return redirect()->back();
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->account_type === 'Admin', 403);
    }
}
