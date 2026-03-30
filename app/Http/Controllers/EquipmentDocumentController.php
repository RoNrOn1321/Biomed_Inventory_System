<?php

namespace App\Http\Controllers;

use App\Models\EquipmentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EquipmentDocumentController extends Controller
{
    public function index($equipmentId)
    {
        $documents = EquipmentDocument::where('equipment_id', $equipmentId)->get();
        return response()->json($documents);
    }

    public function store(Request $request, $equipmentId)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|max:20480',
        ]);

        $uploaded = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store("equipment_documents/{$equipmentId}", 'public');
            $doc = EquipmentDocument::create([
                'equipment_id' => $equipmentId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
            $uploaded[] = $doc;
        }

        return response()->json($uploaded, 201);
    }

    public function preview($documentId)
    {
        $document = EquipmentDocument::findOrFail($documentId);
        $path = Storage::disk('public')->path($document->file_path);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name) . '"',
        ]);
    }

    public function download($documentId)
    {
        $document = EquipmentDocument::findOrFail($documentId);
        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy($documentId)
    {
        $document = EquipmentDocument::findOrFail($documentId);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
