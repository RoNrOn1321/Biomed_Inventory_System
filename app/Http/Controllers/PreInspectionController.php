<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\JobRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreInspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureAccess($request);

        $query = Equipment::withCount('documents')
            ->whereIn('status', ['Pre Inspection', 'Awaiting Approval'])->latest('updated_at');

        if ($search = $request->input('search')) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', $term)
                  ->orWhere('location', 'like', $term)
                  ->orWhere('brand', 'like', $term)
                  ->orWhere('serial_number', 'like', $term)
                  ->orWhere('tag_number', 'like', $term);
            });
        }

        $paginated = $query->paginate(15)->withQueryString();

        $paginated->getCollection()->transform(function ($eq) {
            $eq->has_pir_form = !empty($eq->pre_inspection_form_data);
            return $eq;
        });

        return Inertia::render('PreInspection', [
            'equipments' => $paginated,
            'filters' => $request->only(['search']),
        ]);
    }

    public function send(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->ensureAccess($request);
        abort_unless($equipment->status === 'Unserviceable', 422, 'Only unserviceable equipment can be sent to Pre Inspection.');

        $equipment->update(['status' => 'Pre Inspection']);

        return redirect()->back();
    }

    public function restore(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->ensureAccess($request);
        abort_unless($equipment->status === 'Pre Inspection', 422);

        $equipment->update([
            'status' => 'Awaiting Approval',
            'admin_approval' => 'Pending',
            'pending_action' => 'Restore',
        ]);

        return redirect()->back();
    }

    public function condemn(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->ensureAccess($request);
        abort_unless($equipment->status === 'Pre Inspection', 422);

        $equipment->update([
            'status' => 'Awaiting Approval',
            'admin_approval' => 'Pending',
            'pending_action' => 'Condemn',
        ]);

        return redirect()->back();
    }

    public function prepare(Request $request, Equipment $equipment): JsonResponse
    {
        $this->ensureAccess($request);
        abort_unless(in_array($equipment->status, ['Pre Inspection', 'Awaiting Approval'], true), 422);

        if (!$equipment->pre_inspection_control_no) {
            $year = now()->year;
            $prefix = "$year-";
            $last = Equipment::whereNotNull('pre_inspection_control_no')
                ->where('pre_inspection_control_no', 'like', $prefix . '%')
                ->orderByDesc('pre_inspection_control_no')
                ->first();

            $nextNumber = 1;
            if ($last && preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', $last->pre_inspection_control_no, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            }

            $equipment->update([
                'pre_inspection_control_no' => sprintf('%s%04d', $prefix, $nextNumber),
                'pre_inspectioned_at' => now()->toDateString(),
            ]);
        }

        $endUser = '';
        $jobRequest = JobRequest::where('equipment_id', $equipment->id)->latest('created_at')->first();
        if ($jobRequest) {
            $endUser = $jobRequest->descEquAccessories->pluck('end_user')->filter()->join(', ');
        }

        $formData = $equipment->pre_inspection_form_data ?: [];

        return response()->json([
            'pre_inspection_control_no' => $equipment->pre_inspection_control_no,
            'pre_inspectioned_at' => $equipment->pre_inspectioned_at,
            'end_user' => $endUser,
            'form' => $formData,
        ]);
    }

    public function save(Request $request, Equipment $equipment): JsonResponse
    {
        $this->ensureAccess($request);
        abort_unless(in_array($equipment->status, ['Pre Inspection', 'Awaiting Approval'], true), 422);

        $validated = $request->validate([
            'pir_control_no' => 'nullable|string|max:255',
            'property_no' => 'nullable|string|max:255',
            'location_ward' => 'nullable|string|max:255',
            'end_user' => 'nullable|string|max:255',
            'acquisition_date' => 'nullable|date',
            'acquisition_cost' => 'nullable|string|max:255',
            'defects_complaints' => 'nullable|string',
            'nature_of_work' => 'nullable|string',
            'parts_to_supply' => 'nullable|string',
            'requested_by' => 'nullable|string|max:255',
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'inspector_name' => 'nullable|string|max:255',
            'checked_by' => 'nullable|string|max:255',
            'recommending_approval' => 'nullable|string|max:255',
            'approved_by' => 'nullable|string|max:255',
        ]);

        $equipment->update(['pre_inspection_form_data' => $validated]);

        return response()->json(['success' => true]);
    }

    private function ensureAccess(Request $request): void
    {
        abort_unless(in_array($request->user()?->account_type, ['Admin', 'Moderator', 'Biomed_Technician'], true), 403);
    }
}
