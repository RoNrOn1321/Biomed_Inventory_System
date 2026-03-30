<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Services\EquipmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function __construct(private EquipmentService $equipmentService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['year', 'month', 'search']);
        $filters['year'] = $filters['year'] ?? now()->year;
        $filters['month'] = $filters['month'] ?? now()->format('m');

        $queryFilters = $filters;
        if ($queryFilters['year'] === 'all') {
            unset($queryFilters['year']);
        }
        if ($queryFilters['month'] === 'all') {
            unset($queryFilters['month']);
        }

        return Inertia::render('Inventory', [
            'equipments' => $this->equipmentService->paginatedList($queryFilters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => ['required', 'in:' . implode(',', $this->equipmentService->getExportFormats())],
            'from' => ['required', 'date_format:Y-m'],
            'to' => ['required', 'date_format:Y-m'],
            'search' => ['nullable', 'string'],
        ]);

        $from = Carbon::createFromFormat('Y-m', $validated['from'])->startOfMonth();
        $to = Carbon::createFromFormat('Y-m', $validated['to'])->endOfMonth();

        abort_if($from->gt($to), 422, 'The export date range is invalid.');

        return $this->equipmentService->export($validated['format'], $from, $to, $validated['search'] ?? null);
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
            'status' => 'nullable|string|max:255',
        ]);

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
            'status' => 'nullable|string|max:255',
        ]);

        $this->equipmentService->update($equipment, $validated);

        return redirect()->back();
    }

    public function destroy(Equipment $equipment)
    {
        $this->equipmentService->delete($equipment);

        return redirect()->back();
    }
}
