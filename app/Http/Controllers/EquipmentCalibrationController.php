<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCalibration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EquipmentCalibrationController extends Controller
{
    public function index($equipmentId)
    {
        $files = EquipmentCalibration::where('equipment_id', $equipmentId)
            ->orderBy('calibration_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($files);
    }

    public function store(Request $request, $equipmentId)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|max:20480',
        ]);

        $equipment = Equipment::findOrFail($equipmentId);
        $calibrationDate = now()->toDateString();

        $uploaded = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store("equipment_calibrations/{$equipmentId}", 'public');
            $record = EquipmentCalibration::create([
                'equipment_id'     => $equipmentId,
                'file_name'        => $file->getClientOriginalName(),
                'file_path'        => $path,
                'calibration_date' => $calibrationDate,
            ]);
            $uploaded[] = $record;
        }

        $equipment->load('latestCalibration');
        $equipment->update(['calibration' => $equipment->computedCalibrationStatus()]);

        return response()->json($uploaded, 201);
    }

    public function preview($fileId)
    {
        $record = EquipmentCalibration::findOrFail($fileId);
        $path = Storage::disk('public')->path($record->file_path);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($record->file_name) . '"',
        ]);
    }

    public function download($fileId)
    {
        $record = EquipmentCalibration::findOrFail($fileId);
        return Storage::disk('public')->download($record->file_path, $record->file_name);
    }

    public function destroy($fileId)
    {
        $record = EquipmentCalibration::findOrFail($fileId);
        Storage::disk('public')->delete($record->file_path);
        $equipmentId = $record->equipment_id;
        $record->delete();

        $equipment = Equipment::findOrFail($equipmentId);
        $equipment->load('latestCalibration');
        $equipment->update(['calibration' => $equipment->computedCalibrationStatus()]);

        return response()->json(['message' => 'Deleted']);
    }
}
