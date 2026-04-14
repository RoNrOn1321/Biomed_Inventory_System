<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Services\EquipmentService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function __construct(private EquipmentService $equipmentService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['year', 'month', 'search', 'status']);
        $filters['year'] = $filters['year'] ?? now()->year;
        $filters['month'] = $filters['month'] ?? now()->format('m');

        $user = $request->user();
        $isEndUser = $user->account_type === 'End_User';
        $viewAll = $request->boolean('viewAll', false);

        $queryFilters = $filters;
        if ($queryFilters['year'] === 'all') {
            unset($queryFilters['year']);
        }
        if ($queryFilters['month'] === 'all') {
            unset($queryFilters['month']);
        }
        if (empty($queryFilters['status']) || $queryFilters['status'] === 'all') {
            unset($queryFilters['status']);
        }

        // End users see only their own equipment by default (matched by name in location)
        if ($isEndUser && !$viewAll) {
            $queryFilters['location'] = $user->name;
        }

        return Inertia::render('Inventory', [
            'equipments' => $this->equipmentService->paginatedList($queryFilters),
            'filters' => $filters,
            'users' => User::where('account_type', 'End_User')->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->all(),
            'isEndUser' => $isEndUser,
            'viewAll' => $viewAll,
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => ['required', 'in:' . implode(',', $this->equipmentService->getExportFormats())],
            'from' => ['required', 'date_format:Y-m'],
            'to' => ['required', 'date_format:Y-m'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:Functional,Defective,Unserviceable'],
        ]);

        $from = Carbon::createFromFormat('Y-m', $validated['from'])->startOfMonth();
        $to = Carbon::createFromFormat('Y-m', $validated['to'])->endOfMonth();

        abort_if($from->gt($to), 422, 'The export date range is invalid.');

        return $this->equipmentService->export($validated['format'], $from, $to, $validated['search'] ?? null, $validated['status'] ?? null);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'tag_number' => 'nullable|string|max:255',
            'pm_date_done' => 'nullable|date',
        ]);

        $validated['status'] = 'Functional';

        $this->equipmentService->create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'tag_number' => 'nullable|string|max:255',
            'pm_date_done' => 'nullable|date',
        ]);

        $this->equipmentService->update($equipment, $validated);

        return redirect()->back();
    }

    public function destroy(Equipment $equipment)
    {
        $this->equipmentService->delete($equipment);

        return redirect()->back();
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        if (empty($query)) {
            return response()->json([]);
        }

        $user = $request->user();
        $isEndUser = $user && $user->account_type === 'End_User';

        $equipments = \App\Models\Equipment::where(function ($q) use ($query) {
                $q->where('description', 'like', "%{$query}%")
                  ->orWhere('serial_number', 'like', "%{$query}%")
                  ->orWhere('tag_number', 'like', "%{$query}%")
                  ->orWhere('brand', 'like', "%{$query}%");
            })
            ->where('status', '!=', 'Condemned')
            ->when($isEndUser, fn($q) => $q->where('location', $user->name))
            ->limit(10)
            ->get();

        // Check which equipment serial numbers are already in an active job request queue
        $activeSerials = \App\Models\DescEquAccessory::whereHas('jobRequest', function ($q) {
                $q->whereIn('status', ['Pending', 'Accepted', 'In Progress']);
            })
            ->whereNotNull('serial_number')
            ->pluck('serial_number')
            ->toArray();

        $result = $equipments->map(function ($eq) use ($activeSerials) {
            $data = $eq->toArray();
            $data['in_queue'] = $eq->serial_number && in_array($eq->serial_number, $activeSerials);
            return $data;
        });

        return response()->json($result);
    }

    public function jobHistory(Request $request, Equipment $equipment)
    {
        $history = \App\Models\JobRequest::query()
            ->where('equipment_id', $equipment->id)
            ->with(['assignedTo:id,name', 'acceptedBy:id,name', 'biomedicalServiceDoc'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (\App\Models\JobRequest $jr) => [
                'id' => $jr->id,
                'equipment_name' => $jr->equipment_name,
                'requester_name' => $jr->requester_name,
                'department' => $jr->department,
                'issue_summary' => $jr->issue_summary,
                'priority' => $jr->priority,
                'status' => $jr->status,
                'repair_category' => $jr->repair_category,
                'repair_outcome' => $jr->repair_outcome,
                'admin_approval' => $jr->admin_approval,
                'assigned_to_name' => $jr->assignedTo?->name,
                'accepted_by' => $jr->acceptedBy?->name,
                'requested_at' => optional($jr->requested_at)->toIso8601String(),
                'completed_at' => $jr->status === 'Done' ? optional($jr->updated_at)->toIso8601String() : null,
                'remarks' => $jr->biomedicalServiceDoc?->remarks,
            ]);

        return response()->json($history);
    }
}
