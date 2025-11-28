<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

/**
 * RevenueImportController - Main Import Router
 *
 * ✅ FIXED VERSION - 2025-11-06
 *
 * ========================================
 * CHANGELOG
 * ========================================
 *
 * ✅ FIXED PROBLEM: Enhanced validation error debugging
 *    - Line 75-89: Added detailed error logging and debug info in response
 *    - Line 351-365: Added detailed error logging in legacy import
 *    - Now shows exact validation errors to help debugging
 *
 * ✅ MAINTAINED: All existing functionality
 *    - Two-step import (preview + execute)
 *    - Legacy single-step import
 *    - Template downloads
 *    - Error log downloads
 *    - Import history
 *    - Temp file cleanup
 *
 * ✅ ENHANCED: Better error messages
 *    - Shows which field failed validation
 *    - Shows received vs expected values
 *    - Logs to Laravel log for debugging
 */
class RevenueImportController extends Controller
{
    /**
     * ✅ STEP 1: Preview Import - Check for duplicates
     * ENHANCED: Better validation error messages with debug info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewImport(Request $request)
    {
        try {
            // Validate import_type
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                'file' => 'required|file|mimes:csv,txt|max:10240'
            ]);

            if ($validator->fails()) {
                Log::warning('Preview Import - Basic validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->except(['file'])
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $importType = $request->import_type;
            $file = $request->file('file');

            Log::info("Preview Import started", [
                'type' => $importType,
                'filename' => $file->getClientOriginalName(),
                'filesize' => $file->getSize(),
                'all_params' => $request->except(['file'])
            ]);

            // ✅ FIX: Cast month/year to integer (frontend sends "05" as string, we need 5 as integer)
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                if ($request->has('month')) {
                    $request->merge(['month' => (int) $request->input('month')]);
                }
                if ($request->has('year')) {
                    $request->merge(['year' => (int) $request->input('year')]);
                }
                if ($request->has('divisi_id')) {
                    $request->merge(['divisi_id' => (int) $request->input('divisi_id')]);
                }

                Log::info("Params after casting to integer", [
                    'year' => $request->input('year'),
                    'month' => $request->input('month'),
                    'divisi_id' => $request->input('divisi_id')
                ]);
            }

            // ✅ ENHANCED: Additional validation for revenue imports with detailed error info
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);

                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
                    // ✅ ENHANCED: Detailed error logging
                    Log::error('Preview Import - Additional validation failed', [
                        'import_type' => $importType,
                        'request_data' => $request->except(['file']),
                        'validation_rules' => $additionalRules,
                        'failed_rules' => $additionalValidator->errors()->toArray()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter tambahan gagal',
                        'errors' => $additionalValidator->errors(),
                        // ✅ ENHANCED: Add debug info to help identify the problem
                        'debug' => [
                            'import_type' => $importType,
                            'received_params' => $request->only(['year', 'month', 'divisi_id', 'jenis_data']),
                            'expected_rules' => $additionalRules,
                            'hint' => 'Periksa apakah divisi_id exists di database dan jenis_data valid (revenue/target)'
                        ]
                    ], 422);
                }
            }

            // Store file temporarily with unique session ID
            $sessionId = uniqid('import_', true);
            $tempPath = storage_path('app/temp_imports');

            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $tempFilename = $sessionId . '_' . $file->getClientOriginalName();
            $tempFullPath = $tempPath . '/' . $tempFilename;
            $file->move($tempPath, $tempFilename);

            // Route to specific preview handler
            $previewResult = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $previewResult = $controller->previewDataCC($tempFullPath);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $previewResult = $controller->previewDataAM($tempFullPath);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $previewResult = $controller->previewRevenueCC(
                        $tempFullPath,
                        $request->divisi_id,
                        $request->jenis_data,
                        $request->year,
                        $request->month
                    );
                    break;

                case 'revenue_am':
                    // Pass year & month from form to preview
                    $controller = new ImportAMController();
                    $previewResult = $controller->previewRevenueAM(
                        $tempFullPath,
                        $request->year,
                        $request->month
                    );
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak dikenali'
                    ], 400);
            }

            if (!$previewResult['success']) {
                // Clean up temp file on error
                if (file_exists($tempFullPath)) {
                    unlink($tempFullPath);
                }

                Log::warning('Preview Import - Controller returned error', [
                    'import_type' => $importType,
                    'error' => $previewResult
                ]);

                return response()->json($previewResult);
            }

            // Store session data WITH additional params
            $sessionData = [
                'import_type' => $importType,
                'temp_file' => $tempFullPath,
                'original_filename' => $file->getClientOriginalName(),
                'created_at' => now()->toISOString()
            ];

            // Save additional params to session (year, month, divisi_id, jenis_data)
            if ($importType === 'revenue_cc') {
                $sessionData['additional_params'] = [
                    'divisi_id' => $request->divisi_id,
                    'jenis_data' => $request->jenis_data,
                    'year' => $request->year,
                    'month' => $request->month
                ];
            } elseif ($importType === 'revenue_am') {
                // Save year/month for revenue_am
                $sessionData['additional_params'] = [
                    'year' => $request->year,
                    'month' => $request->month
                ];
            }

            Cache::put($sessionId, $sessionData, now()->addHours(2));

            // Prepare response
            $previewResult['session_id'] = $sessionId;
            $previewResult['expires_at'] = now()->addHours(2)->toISOString();

            Log::info("Preview Import completed successfully", [
                'type' => $importType,
                'session_id' => $sessionId,
                'additional_params' => $sessionData['additional_params'] ?? null,
                'preview_result' => [
                    'total_rows' => $previewResult['data']['summary']['total_rows'] ?? 0,
                    'new_count' => $previewResult['data']['summary']['new_count'] ?? 0,
                    'update_count' => $previewResult['data']['summary']['update_count'] ?? 0
                ]
            ]);

            return response()->json($previewResult);

        } catch (\Exception $e) {
            Log::error("Preview Import exception caught", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['file'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat preview import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ STEP 2: Execute Import - Process with user confirmation
     * MAINTAINED: Merge additional_params from session to request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeImport(Request $request)
    {
        try {
            // Validate session
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'confirmed_updates' => 'array',
                'confirmed_updates.*' => 'string',
                'skip_updates' => 'array',
                'skip_updates.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sessionId = $request->session_id;
            $sessionData = Cache::get($sessionId);

            if (!$sessionData) {
                Log::warning('Execute Import - Session not found or expired', [
                    'session_id' => $sessionId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak valid atau sudah expired. Silakan upload ulang file.'
                ], 400);
            }

            $importType = $sessionData['import_type'];
            $tempFile = $sessionData['temp_file'];

            // Validate temp file
            if (empty($tempFile)) {
                Cache::forget($sessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'Path file temporary tidak valid. Silakan upload ulang.'
                ], 400);
            }

            if (!file_exists($tempFile)) {
                Cache::forget($sessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan. Silakan upload ulang.'
                ], 400);
            }

            Log::info("Execute Import started", [
                'type' => $importType,
                'session_id' => $sessionId,
                'temp_file' => $tempFile,
                'confirmed_updates' => count($request->confirmed_updates ?? []),
                'skip_updates' => count($request->skip_updates ?? [])
            ]);

            // Prepare request
            $importRequest = new Request();
            $importRequest->merge([
                'temp_file' => $tempFile,
                'confirmed_updates' => $request->confirmed_updates ?? [],
                'skip_updates' => $request->skip_updates ?? []
            ]);

            // Merge additional params from session (year, month, divisi_id, jenis_data)
            if (!empty($sessionData['additional_params'])) {
                $importRequest->merge($sessionData['additional_params']);

                Log::info("Merged additional params to request", [
                    'params' => $sessionData['additional_params']
                ]);
            }

            // Validate importRequest has temp_file
            if (!$importRequest->has('temp_file') || empty($importRequest->input('temp_file'))) {
                Cache::forget($sessionId);
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter temp_file tidak valid dalam request.'
                ], 400);
            }

            // Route to specific execute handler
            $executeResult = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeDataCC($importRequest);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeDataAM($importRequest);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $executeResult = $controller->executeRevenueCC($importRequest);
                    break;

                case 'revenue_am':
                    // Year & month sudah ada di $importRequest dari session merge
                    $controller = new ImportAMController();
                    $executeResult = $controller->executeRevenueAM($importRequest);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe import tidak dikenali'
                    ], 400);
            }

            // Clean up temp file and cache
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            Cache::forget($sessionId);

            Log::info("Execute Import completed", [
                'type' => $importType,
                'session_id' => $sessionId,
                'result' => $executeResult
            ]);

            return response()->json($executeResult);

        } catch (\Exception $e) {
            Log::error("Execute Import error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Legacy single-step import (backward compatibility)
     * ENHANCED: Better error logging
     */
    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
                'file' => 'required|file|mimes:csv,txt|max:10240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $importType = $request->import_type;
            $file = $request->file('file');

            Log::info('Legacy Import started', [
                'type' => $importType,
                'filename' => $file->getClientOriginalName()
            ]);

            // ✅ FIX: Cast month/year/divisi_id to integer
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                if ($request->has('month')) {
                    $request->merge(['month' => (int) $request->input('month')]);
                }
                if ($request->has('year')) {
                    $request->merge(['year' => (int) $request->input('year')]);
                }
                if ($request->has('divisi_id')) {
                    $request->merge(['divisi_id' => (int) $request->input('divisi_id')]);
                }
            }

            // ✅ ENHANCED: Additional validation for revenue imports
            if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
                $additionalRules = $this->getAdditionalValidationRules($importType);
                $additionalValidator = Validator::make($request->all(), $additionalRules);

                if ($additionalValidator->fails()) {
                    // ✅ ENHANCED: Detailed error logging
                    Log::error('Legacy Import - Validation failed', [
                        'import_type' => $importType,
                        'request_data' => $request->except(['file']),
                        'failed_rules' => $additionalValidator->errors()->toArray()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi parameter tambahan gagal',
                        'errors' => $additionalValidator->errors(),
                        'debug' => [
                            'import_type' => $importType,
                            'received_params' => $request->only(['year', 'month', 'divisi_id', 'jenis_data'])
                        ]
                    ], 422);
                }
            }

            // Create temp file
            $tempPath = storage_path('app/temp_imports');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $tempFilename = uniqid('import_') . '_' . $file->getClientOriginalName();
            $tempFullPath = $tempPath . '/' . $tempFilename;
            $file->move($tempPath, $tempFilename);

            // Prepare request
            $importRequest = new Request();
            $importRequest->merge([
                'temp_file' => $tempFullPath
            ]);

            // Merge params based on import type
            if ($importType === 'revenue_cc') {
                $importRequest->merge([
                    'divisi_id' => $request->divisi_id,
                    'jenis_data' => $request->jenis_data,
                    'year' => $request->year,
                    'month' => $request->month
                ]);
            } elseif ($importType === 'revenue_am') {
                // Add year/month for revenue_am
                $importRequest->merge([
                    'year' => $request->year,
                    'month' => $request->month
                ]);
            }

            // Execute import
            $result = null;
            switch ($importType) {
                case 'data_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeDataCC($importRequest);
                    break;

                case 'data_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeDataAM($importRequest);
                    break;

                case 'revenue_cc':
                    $controller = new ImportCCController();
                    $result = $controller->executeRevenueCC($importRequest);
                    break;

                case 'revenue_am':
                    $controller = new ImportAMController();
                    $result = $controller->executeRevenueAM($importRequest);
                    break;
            }

            // Clean up
            if (file_exists($tempFullPath)) {
                unlink($tempFullPath);
            }

            Log::info('Legacy Import completed', [
                'type' => $importType,
                'result' => $result
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Legacy Import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MAINTAINED: Legacy validate import (for backward compatibility)
     */
    public function validateImport(Request $request)
    {
        // Redirect to preview
        return $this->previewImport($request);
    }

    /**
     * ✅ MAINTAINED: Download error log file
     */
    public function downloadErrorLog($filename)
    {
        $filepath = public_path('storage/import_logs/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return response()->download($filepath);
    }

    /**
     * ✅ MAINTAINED: Get import history
     */
    public function getImportHistory()
    {
        try {
            $logPath = public_path('storage/import_logs');

            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $files = array_diff(scandir($logPath), ['.', '..']);
            $history = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                    $history[] = [
                        'filename' => $file,
                        'created_at' => date('Y-m-d H:i:s', filemtime($logPath . '/' . $file)),
                        'download_url' => route('revenue.download.error.log', ['filename' => $file])
                    ];
                }
            }

            // Sort by newest first
            usort($history, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENHANCED: Get additional validation rules based on import type
     * Better organized and documented
     *
     * @param string $importType
     * @return array
     */
    private function getAdditionalValidationRules($importType)
    {
        $rules = [];

        if ($importType === 'revenue_cc') {
            $rules['divisi_id'] = 'required|exists:divisi,id';
            $rules['jenis_data'] = 'required|in:revenue,target';
            $rules['year'] = 'required|integer|min:2020|max:2100';
            $rules['month'] = 'required|integer|min:1|max:12';
        }

        if ($importType === 'revenue_am') {
            $rules['year'] = 'required|integer|min:2020|max:2100';
            $rules['month'] = 'required|integer|min:1|max:12';
        }

        Log::debug('Additional validation rules generated', [
            'import_type' => $importType,
            'rules' => $rules
        ]);

        return $rules;
    }

    /**
     * ✅ MAINTAINED: Cleanup old temp files (can be called by cron job)
     */
    public function cleanupTempFiles()
    {
        try {
            $tempPath = storage_path('app/temp_imports');

            if (!file_exists($tempPath)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No temp directory found',
                    'deleted_count' => 0
                ]);
            }

            $files = array_diff(scandir($tempPath), ['.', '..']);
            $deletedCount = 0;
            $olderThan = now()->subHours(3);

            foreach ($files as $file) {
                $filepath = $tempPath . '/' . $file;

                if (!is_file($filepath)) {
                    continue;
                }

                $fileTime = filemtime($filepath);

                if ($fileTime < $olderThan->timestamp) {
                    unlink($filepath);
                    $deletedCount++;
                }
            }

            Log::info('Temp files cleanup completed', [
                'deleted_count' => $deletedCount,
                'older_than' => $olderThan->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old temp files",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Cleanup temp files error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error during cleanup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get validation rules info (for debugging)
     * Endpoint untuk melihat aturan validasi yang berlaku
     */
    public function getValidationRules(Request $request)
    {
        $importType = $request->input('import_type');

        if (!$importType) {
            return response()->json([
                'success' => false,
                'message' => 'import_type parameter required'
            ], 400);
        }

        $basicRules = [
            'import_type' => 'required|in:data_cc,data_am,revenue_cc,revenue_am',
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ];

        $additionalRules = [];
        if (in_array($importType, ['revenue_cc', 'revenue_am'])) {
            $additionalRules = $this->getAdditionalValidationRules($importType);
        }

        return response()->json([
            'success' => true,
            'import_type' => $importType,
            'basic_rules' => $basicRules,
            'additional_rules' => $additionalRules,
            'all_rules' => array_merge($basicRules, $additionalRules)
        ]);
    }

    /**
     * ✅ NEW: Health check for import system
     */
    public function healthCheck()
    {
        try {
            $tempPath = storage_path('app/temp_imports');
            $logPath = public_path('storage/import_logs');

            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'directories' => [
                    'temp_imports' => [
                        'exists' => file_exists($tempPath),
                        'writable' => file_exists($tempPath) && is_writable($tempPath),
                        'file_count' => file_exists($tempPath) ? count(array_diff(scandir($tempPath), ['.', '..'])) : 0
                    ],
                    'import_logs' => [
                        'exists' => file_exists($logPath),
                        'writable' => file_exists($logPath) && is_writable($logPath),
                        'file_count' => file_exists($logPath) ? count(array_diff(scandir($logPath), ['.', '..'])) : 0
                    ]
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'working' => Cache::has('__health_check__') || Cache::put('__health_check__', true, 10)
                ],
                'database' => [
                    'connected' => false,
                    'tables_exist' => []
                ]
            ];

            // Check database connection and tables
            try {
                DB::connection()->getPdo();
                $health['database']['connected'] = true;

                $requiredTables = ['divisi', 'corporate_customers', 'account_managers', 'cc_revenues', 'am_revenues'];
                foreach ($requiredTables as $table) {
                    $health['database']['tables_exist'][$table] = DB::getSchemaBuilder()->hasTable($table);
                }
            } catch (\Exception $e) {
                $health['database']['error'] = $e->getMessage();
            }

            return response()->json($health);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}