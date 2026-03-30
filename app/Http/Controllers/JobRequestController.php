<?php

namespace App\Http\Controllers;

use App\Models\JobRequest;
use App\Services\JobRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobRequestController extends Controller
{
    public function __construct(private JobRequestService $jobRequestService) {}

    public function index(Request $request): Response
    {
        $this->ensureCanManageRequests($request);

        return Inertia::render('JobRequests', [
            'jobRequests' => $this->jobRequestService->listAll(),
        ]);
    }

    public function accept(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureCanManageRequests($request);

        $this->jobRequestService->accept($jobRequest, $request->user()->id);

        return redirect()->back();
    }

    public function complete(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $this->ensureCanManageRequests($request);

        $validated = $request->validate([
            'receive_by' => 'nullable|string|max:255',
            'performed_by' => 'nullable|string|max:255',
            'date_receive' => 'nullable|date',
            'date_performed' => 'nullable|date',
            'estimated_no_days' => 'nullable|integer',
            'technician_date_received' => 'nullable|date',
            'date_started' => 'nullable|date',
            'date_finished' => 'nullable|date',
            'date_returned' => 'nullable|date',
            'receive_by_end_user' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        $this->jobRequestService->complete($jobRequest, $validated);

        return redirect()->back();
    }

    private function ensureCanManageRequests(Request $request): void
    {
        abort_unless(in_array($request->user()?->account_type, ['Biomed_Technician', 'Admin'], true), 403);
    }
}