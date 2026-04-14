<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreInspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureAccess($request);

        $query = Equipment::where('status', 'Pre Inspection')->latest('updated_at');

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

        return Inertia::render('PreInspection', [
            'equipments' => $query->paginate(15)->withQueryString(),
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

        $equipment->update(['status' => 'Functional']);

        return redirect()->back();
    }

    public function condemn(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->ensureAccess($request);
        abort_unless($equipment->status === 'Pre Inspection', 422);

        $equipment->update(['status' => 'Condemned']);

        return redirect()->back();
    }

    private function ensureAccess(Request $request): void
    {
        abort_unless(in_array($request->user()?->account_type, ['Admin', 'Moderator', 'Biomed_Technician'], true), 403);
    }
}
