<?php
// Developer context: This controller exposes the CRUD and export endpoints for reusable DM records; it stays thin by delegating validation and export logic to dedicated classes.
// Clear explanation: This file handles saving, loading, deleting, and exporting DM records.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDmRecordRequest;
use App\Models\DmRecord;
use App\Support\DmRecordHomebrewExporter;
use Illuminate\Http\JsonResponse;

class DmRecordController extends Controller
{
    // Developer context: This endpoint returns the saved DM records together with the config metadata the page needs for labels and filters.
    // Clear explanation: This sends the DM page the saved records plus the basic option lists for record types and statuses.
    public function index(): JsonResponse
    {
        return response()->json([
            'kinds' => config('dm.kinds', []),
            'statuses' => config('dm.statuses', []),
            'records' => DmRecord::query()
                ->with('linkedHomebrewEntry')
                ->latest()
                ->get(),
        ]);
    }

    // Developer context: This endpoint creates a new reusable DM record from the shared validated request payload.
    // Clear explanation: This saves a new DM record.
    public function store(StoreDmRecordRequest $request): JsonResponse
    {
        $record = DmRecord::create($request->recordData());

        return response()->json([
            'message' => 'DM record saved.',
            'data' => $record->fresh('linkedHomebrewEntry'),
        ], 201);
    }

    // Developer context: This endpoint updates an existing DM record using the same validation rules as creation.
    // Clear explanation: This updates an existing DM record.
    public function update(StoreDmRecordRequest $request, DmRecord $dmRecord): JsonResponse
    {
        $dmRecord->update($request->recordData());

        return response()->json([
            'message' => 'DM record updated.',
            'data' => $dmRecord->fresh('linkedHomebrewEntry'),
        ]);
    }

    // Developer context: This endpoint deletes a saved DM record while leaving any separately exported homebrew entry untouched.
    // Clear explanation: This removes a saved DM record from the DM tools.
    public function destroy(DmRecord $dmRecord): JsonResponse
    {
        $dmRecord->delete();

        return response()->json([
            'message' => 'DM record removed.',
        ]);
    }

    // Developer context: This endpoint performs the explicit DM-record-to-homebrew export and stores the link back on the record.
    // Clear explanation: This copies a DM record into the homebrew workshop when the DM chooses to export it.
    public function exportToHomebrew(DmRecord $dmRecord, DmRecordHomebrewExporter $exporter): JsonResponse
    {
        $entry = $exporter->export($dmRecord);

        return response()->json([
            'message' => 'DM record exported to homebrew.',
            'data' => $dmRecord->fresh('linkedHomebrewEntry'),
            'homebrew_entry' => $entry,
        ]);
    }
}
