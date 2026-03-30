<?php

namespace App\Http\Controllers;

use App\Services\EndUserJobRequestService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EndUserJobRequestController extends Controller
{
    public function __construct(private EndUserJobRequestService $service) {}

    public function history(Request $request): Response
    {
        return Inertia::render('JobRequestHistory', [
            'jobRequests' => $this->service->getHistory($request->user()->id),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CreateJobRequest');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => 'required|string|max:255',
            'date' => 'required|date',
            'control_number' => 'nullable|string|max:255',
            'department' => 'nullable|string',
            'department_other' => 'nullable|string',
            'location' => 'nullable|string|max:255',

            'equipments' => 'required|array|min:1',
            'equipments.*.equipment_name' => 'required|string|max:255',
            'equipments.*.brand' => 'nullable|string|max:255',
            'equipments.*.model' => 'nullable|string|max:255',
            'equipments.*.serial_number' => 'nullable|string|max:255',
            'equipments.*.end_user' => 'nullable|string|max:255',

            'problem_description' => 'nullable|string',
            'services_desired' => 'nullable|array',
            'other_service' => 'nullable|string',
            'repair_type' => 'nullable|string',
            'nature_of_work' => 'nullable|string',
        ]);

        $this->service->store($validated, $request->user()?->id);

        return redirect()->back()->with('success', 'Job request submitted successfully.');
    }
}