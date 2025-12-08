<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\DatasetLink;
use App\Models\SpreadsheetSnapshot;
use App\Models\Divisi;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HighFiveSettingsController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    /**
     * Display settings page with all dataset links
     *
     * Shows:
     * - List of existing links (DPS, DSS)
     * - Form to add new link
     * - Manual fetch section (for dashboard)
     */
    public function index()
    {
        // Get all dataset links with divisi and latest snapshot
        $links = DatasetLink::with(['divisi', 'latestSnapshot'])
            ->withDivisi()
            ->get();

        // Get available divisi untuk dropdown (yang belum punya link)
        $availableDivisi = Divisi::whereIn('kode', ['DSS', 'DPS'])
            ->whereNotIn('id', $links->pluck('divisi_id'))
            ->get();

        return view('high-five.settings', compact('links', 'availableDivisi'));
    }

    /**
     * Get all available dataset links (for dashboard dropdown)
     *
     * Returns active links that can be selected for manual fetch
     */
    public function getAvailableLinks()
    {
        $links = DatasetLink::with('divisi')
            ->where('is_active', true)
            ->get()
            ->map(function ($link) {
                return [
                    'id' => $link->id,
                    'divisi_id' => $link->divisi_id,
                    'divisi_name' => $link->divisi->kode ?? 'Unknown',
                    'link' => $link->link_spreadsheet,
                    'last_fetched' => $link->last_fetched_at
                        ? $link->last_fetched_at->locale('id')->isoFormat('DD MMM YYYY HH:mm')
                        : 'Belum pernah',
                    'total_snapshots' => $link->total_snapshots,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $links,
        ]);
    }

    /**
     * Store new dataset link (with first fetch)
     *
     * Creates link AND immediately fetches first snapshot
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'divisi_id' => 'required|exists:divisi,id|unique:dataset_links,divisi_id',
            'link_spreadsheet' => [
                'required',
                'string',
                'url',
                'regex:/^https:\/\//',
            ],
        ], [
            'link_spreadsheet.regex' => 'Link harus diawali dengan https://',
            'link_spreadsheet.url' => 'Link spreadsheet tidak valid',
            'divisi_id.unique' => 'Divisi ini sudah memiliki link spreadsheet. Gunakan Edit untuk mengubah.',
            'divisi_id.required' => 'Divisi harus dipilih',
            'divisi_id.exists' => 'Divisi tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Create dataset link
            $link = DatasetLink::create([
                'divisi_id' => $request->divisi_id,
                'link_spreadsheet' => $request->link_spreadsheet,
                'is_active' => true,
            ]);

            // 2. Fetch data immediately (auto date)
            $fetchResult = $this->fetchAndStoreSnapshot($link, null);

            if (!$fetchResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Link berhasil disimpan tetapi gagal fetch data: ' . $fetchResult['message']
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Link berhasil disimpan dan data berhasil di-fetch!',
                'data' => [
                    'link_id' => $link->id,
                    'snapshot_id' => $fetchResult['snapshot_id'],
                    'snapshot_date' => $fetchResult['snapshot_date'],
                    'total_rows' => $fetchResult['total_rows'],
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing dataset link
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'link_spreadsheet' => [
                'required',
                'string',
                'url',
                'regex:/^https:\/\//',
            ],
            'is_active' => 'sometimes|boolean',
        ], [
            'link_spreadsheet.regex' => 'Link harus diawali dengan https://',
            'link_spreadsheet.url' => 'Link spreadsheet tidak valid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $link = DatasetLink::findOrFail($id);

            $link->update([
                'link_spreadsheet' => $request->link_spreadsheet,
                'is_active' => $request->input('is_active', $link->is_active),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Link berhasil diupdate',
                'data' => [
                    'link_id' => $link->id,
                    'link' => $link->link_spreadsheet,
                    'is_active' => $link->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete dataset link
     *
     * WARNING: This will cascade delete all snapshots!
     */
    public function delete($id)
    {
        try {
            $link = DatasetLink::findOrFail($id);
            $snapshotCount = $link->snapshots()->count();

            $link->delete(); // Cascade delete snapshots

            return response()->json([
                'success' => true,
                'message' => "Link berhasil dihapus (termasuk {$snapshotCount} snapshot)"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 NEW: Manual fetch with custom date (from dashboard)
     *
     * User selects:
     * - Link (divisi)
     * - Date (snapshot_date)
     * Then clicks "Simpan Data"
     */
    public function fetchWithCustomDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link_id' => 'required|exists:dataset_links,id',
            'snapshot_date' => 'required|date',
        ], [
            'link_id.required' => 'Link spreadsheet harus dipilih',
            'link_id.exists' => 'Link tidak ditemukan',
            'snapshot_date.required' => 'Tanggal harus dipilih',
            'snapshot_date.date' => 'Format tanggal tidak valid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $link = DatasetLink::with('divisi')->findOrFail($request->link_id);

            if (!$link->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link tidak aktif. Aktifkan link terlebih dahulu di halaman Settings.'
                ], 422);
            }

            // Fetch with custom date
            $fetchResult = $this->fetchAndStoreSnapshot($link, $request->snapshot_date);

            if (!$fetchResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $fetchResult['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => $fetchResult['is_update']
                    ? 'Data berhasil diupdate!'
                    : 'Data berhasil disimpan!',
                'data' => [
                    'snapshot_id' => $fetchResult['snapshot_id'],
                    'snapshot_date' => $fetchResult['snapshot_date'],
                    'total_rows' => $fetchResult['total_rows'],
                    'total_ams' => $fetchResult['total_ams'],
                    'total_customers' => $fetchResult['total_customers'],
                    'total_products' => $fetchResult['total_products'],
                    'divisi' => $link->divisi->kode ?? 'Unknown',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal fetch data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual fetch trigger (Fetch Now button) - Auto date
     *
     * This fetches data from Google Sheets with auto-calculated date
     */
    public function fetchNow($id)
    {
        try {
            $link = DatasetLink::with('divisi')->findOrFail($id);

            if (!$link->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link tidak aktif. Aktifkan link terlebih dahulu.'
                ], 422);
            }

            // Fetch with auto date
            $fetchResult = $this->fetchAndStoreSnapshot($link, null);

            if (!$fetchResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $fetchResult['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil di-fetch!',
                'data' => [
                    'snapshot_id' => $fetchResult['snapshot_id'],
                    'snapshot_date' => $fetchResult['snapshot_date'],
                    'total_rows' => $fetchResult['total_rows'],
                    'total_ams' => $fetchResult['total_ams'],
                    'total_customers' => $fetchResult['total_customers'],
                    'total_products' => $fetchResult['total_products'],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal fetch data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View fetch history for a dataset link
     */
    public function history($id)
    {
        try {
            $link = DatasetLink::with('divisi')->findOrFail($id);

            $snapshots = SpreadsheetSnapshot::where('dataset_link_id', $id)
                ->orderBy('snapshot_date', 'desc')
                ->get()
                ->map(function ($snapshot) {
                    return [
                        'id' => $snapshot->id,
                        'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
                        'snapshot_date_formatted' => $snapshot->formatted_date,
                        'total_rows' => $snapshot->total_rows,
                        'total_ams' => $snapshot->total_ams,
                        'total_customers' => $snapshot->total_customers,
                        'total_products' => $snapshot->total_products,
                        'fetch_status' => $snapshot->fetch_status,
                        'status_color' => $snapshot->status_color,
                        'status_icon' => $snapshot->status_icon,
                        'fetched_at' => $snapshot->fetched_at->locale('id')->isoFormat('DD MMM YYYY HH:mm'),
                        'error' => $snapshot->fetch_error,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'link' => [
                        'id' => $link->id,
                        'divisi' => $link->divisi->kode ?? 'Unknown',
                        'link_spreadsheet' => $link->link_spreadsheet,
                    ],
                    'snapshots' => $snapshots,
                    'total' => $snapshots->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry failed snapshot
     */
    public function retrySnapshot($snapshotId)
    {
        try {
            $snapshot = SpreadsheetSnapshot::findOrFail($snapshotId);
            $link = $snapshot->datasetLink;

            if (!$link) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dataset link tidak ditemukan'
                ], 404);
            }

            // Re-fetch data
            $parsedData = $this->googleSheetService->fetchSpreadsheetData($link->link_spreadsheet);

            // Update snapshot
            $snapshot->storeSpreadsheetData($parsedData);

            // Update link stats
            $link->updateFetchStats('success');

            // Clear cache so new data is displayed immediately
            $this->googleSheetService->clearCache($link->link_spreadsheet);

            return response()->json([
                'success' => true,
                'message' => 'Snapshot berhasil di-retry!',
                'data' => $snapshot->getStatsSummary()
            ]);

        } catch (\Exception $e) {
            $snapshot->markAsFailed($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Retry gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ========== PRIVATE HELPER METHODS ==========
     */

    /**
     * Fetch data from Google Sheets and store as snapshot
     *
     * @param DatasetLink $link
     * @param string|null $customDate - If provided, use this date. Otherwise auto-calculate.
     * @return array ['success' => bool, 'message' => string, ...]
     */
    private function fetchAndStoreSnapshot(DatasetLink $link, $customDate = null)
    {
        try {
            // 1. Fetch data from Google Sheets
            $parsedData = $this->googleSheetService->fetchSpreadsheetData($link->link_spreadsheet);

            if (empty($parsedData)) {
                throw new \Exception('Spreadsheet kosong atau tidak ada data');
            }

            // 2. Determine snapshot date
            $snapshotDate = $customDate ?? $this->calculateSnapshotDate();

            // 3. Check if snapshot for this date already exists
            $existingSnapshot = SpreadsheetSnapshot::where('dataset_link_id', $link->id)
                ->where('snapshot_date', $snapshotDate)
                ->first();

            $isUpdate = false;

            if ($existingSnapshot) {
                // Update existing snapshot
                $snapshot = $existingSnapshot;
                $snapshot->storeSpreadsheetData($parsedData);
                $isUpdate = true;
            } else {
                // Create new snapshot
                $snapshot = SpreadsheetSnapshot::create([
                    'dataset_link_id' => $link->id,
                    'divisi_id' => $link->divisi_id,
                    'snapshot_date' => $snapshotDate,
                    'data_json' => json_encode([]),
                    'fetched_at' => now(),
                ]);

                $snapshot->storeSpreadsheetData($parsedData);
            }

            // 4. Update link statistics
            $link->updateFetchStats('success');

            // 5. Clear cache so new data is displayed immediately
            $this->googleSheetService->clearCache($link->link_spreadsheet);

            return [
                'success' => true,
                'message' => 'Data berhasil di-fetch',
                'snapshot_id' => $snapshot->id,
                'snapshot_date' => $snapshot->formatted_date,
                'total_rows' => $snapshot->total_rows,
                'total_ams' => $snapshot->total_ams,
                'total_customers' => $snapshot->total_customers,
                'total_products' => $snapshot->total_products,
                'is_update' => $isUpdate,
            ];

        } catch (\Exception $e) {
            // Update link with error
            $link->updateFetchStats('failed', $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate snapshot date (last Friday)
     *
     * Logic:
     * - If today is Friday and time >= 01:00 → use today
     * - Otherwise → use last Friday
     */
    private function calculateSnapshotDate()
    {
        $now = Carbon::now();

        // If today is Friday and time >= 01:00
        if ($now->isFriday() && $now->hour >= 1) {
            return $now->toDateString();
        }

        // Otherwise, get last Friday
        return $now->previous(Carbon::FRIDAY)->toDateString();
    }
}