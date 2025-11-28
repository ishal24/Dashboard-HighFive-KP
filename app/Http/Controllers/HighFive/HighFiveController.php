<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\SpreadsheetSnapshot;
use App\Models\Divisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HighFiveController extends Controller
{
    /**
     * Display the High Five RLEGS TR3 dashboard page
     *
     * ✅ KEPT FROM ORIGINAL - No changes needed
     */
    public function index()
    {
        // Get all divisi untuk button group
        $divisiList = Divisi::whereIn('kode', ['DSS', 'DPS'])->get();

        return view('high-five.index', compact('divisiList'));
    }

    /**
     * 🔄 REVISED: Get snapshots by divisi for dropdown options
     *
     * Old: getDatasetsByDivisi() from dataset_high_five table
     * New: getSnapshots() from spreadsheet_snapshots table
     *
     * Returns successful snapshots ordered by date (newest first)
     */
    public function getSnapshots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'divisi_id' => 'required|exists:divisi,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Divisi tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get all successful snapshots for this divisi
            $snapshots = SpreadsheetSnapshot::with('divisi')
                ->where('divisi_id', $request->divisi_id)
                ->where('fetch_status', 'success')
                ->orderBy('snapshot_date', 'desc')
                ->get()
                ->map(function ($snapshot) {
                    return [
                        'id' => $snapshot->id,
                        'label' => $snapshot->display_name, // e.g., "DPS 22 Nov 2024"
                        'tanggal' => $snapshot->snapshot_date->format('Y-m-d'),
                        'tanggal_formatted' => $snapshot->formatted_date,
                        'total_rows' => $snapshot->total_rows,
                        'total_ams' => $snapshot->total_ams,
                        'total_customers' => $snapshot->total_customers,
                        'total_products' => $snapshot->total_products,
                        'fetched_at' => $snapshot->fetched_at->locale('id')->isoFormat('DD MMM YYYY HH:mm'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $snapshots,
                'count' => $snapshots->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get latest 2 snapshots for auto-selection
     *
     * ➕ NEW METHOD: Helper untuk auto-populate dataset 1 & 2 dropdown
     * Returns: [latest, previous] snapshots
     */
    public function getLatestSnapshots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'divisi_id' => 'required|exists:divisi,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Divisi tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get latest 2 snapshots
            $snapshots = SpreadsheetSnapshot::with('divisi')
                ->where('divisi_id', $request->divisi_id)
                ->where('fetch_status', 'success')
                ->orderBy('snapshot_date', 'desc')
                ->limit(2)
                ->get();

            if ($snapshots->count() < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal 2 snapshot diperlukan untuk benchmarking',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'latest' => [
                        'id' => $snapshots[0]->id,
                        'label' => $snapshots[0]->display_name,
                        'date' => $snapshots[0]->snapshot_date->format('Y-m-d'),
                    ],
                    'previous' => [
                        'id' => $snapshots[1]->id,
                        'label' => $snapshots[1]->display_name,
                        'date' => $snapshots[1]->snapshot_date->format('Y-m-d'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }
}