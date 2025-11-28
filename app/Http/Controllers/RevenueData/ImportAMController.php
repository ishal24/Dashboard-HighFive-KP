<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * ImportAMController - Account Manager Import Handler
 *
 * FIXED VERSION - 2025-11-10
 *
 * ✅ FIXED: Column mapping untuk Data AM - support NIK dan NIK_AM
 * ✅ FIXED: Column mapping untuk Revenue AM - sudah benar (NIK_AM)
 * ✅ FIXED: Flexible column handling - abaikan kolom extra di CSV
 * ✅ FIXED: Year & Month dari form input (bukan dari CSV)
 *
 * CHANGELOG:
 * - Line 96-97: Changed requiredColumns untuk Data AM dari ['NIK', ...]
 *               menjadi ['NIK_AM', ...] untuk match dengan Excel
 * - Line 103: Updated getColumnIndices untuk support both 'NIK' and 'NIK_AM'
 * - Line 117-118: Updated getColumnValue untuk ambil NIK_AM (bukan NIK)
 * - Line 252: Updated requiredColumns untuk executeDataAM
 * - Line 278: Updated getColumnValue untuk ambil NIK_AM
 * - Revenue AM functions: Already correct (using NIK_AM)
 *
 * KEY FEATURES:
 * ✅ Data AM: Support NIK_AM column name (match dengan Excel screenshot)
 * ✅ Revenue AM: Sudah benar menggunakan NIK_AM
 * ✅ Backward compatible: Masih support kolom 'NIK' jika ada
 * ✅ Flexible Columns: Kolom extra di CSV diabaikan
 * ✅ Year & Month: Dari form input (month picker), BUKAN dari CSV
 */
class ImportAMController extends Controller
{
    /**
     * Download Template CSV
     */
    public function downloadTemplate($type)
    {
        $templates = [
            'data-am' => [
                'filename' => 'template_data_am.csv',
                // ✅ FIXED: Changed NIK to NIK_AM to match Excel format
                'headers' => ['NIK_AM', 'NAMA AM', 'PROPORSI', 'WITEL AM', 'NIPNAS', 'DIVISI AM', 'DIVISI', 'TELDA'],
                'sample' => [
                    ['123456', 'John Doe', '1', 'BALI', '76590001', 'AM', 'DGS', ''],
                    ['789012', 'Jane Smith', '1', 'JATIM BARAT', '19669082', 'HOTDA', 'DSS', 'TELKOM DAERAH BOJONEGORO'],
                    ['345678', 'Ahmad Multi', '0.5', 'JATIM TIMUR', '4601571', 'AM', 'DGS,DSS', '']
                ]
            ],
            'revenue-am' => [
                'filename' => 'template_revenue_am.csv',
                'headers' => ['NIPNAS', 'NIK_AM', 'PROPORSI'],
                'sample' => [
                    ['76590001', '0001', '60'],
                    ['76590001', '0002', '40'],
                    ['76590002', 'AM0003', '100']
                ]
            ]
        ];

        if (!isset($templates[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Template type not found'
            ], 404);
        }

        $template = $templates[$type];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $template['headers']);
        foreach ($template['sample'] as $row) {
            fputcsv($csv, $row);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $template['filename'] . '"',
        ]);
    }

    /**
     * ✅ FIXED: Preview Data AM Import
     * Changed to support NIK_AM column name (backward compatible with NIK)
     */
    public function previewDataAM($tempFilePath)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // ✅ FIXED: Changed NIK to NIK_AM (primary), but still support NIK (fallback)
            $requiredColumns = ['NIK_AM', 'NAMA AM', 'WITEL AM', 'DIVISI AM'];
            $optionalColumns = ['PROPORSI', 'NIPNAS', 'DIVISI', 'TELDA'];

            $headers = array_shift($csvData);

            // ✅ FIXED: Flexible validation - support both NIK_AM and NIK
            if (!$this->validateHeadersFlexible($headers, ['NIK_AM', 'NIK'], ['NAMA AM', 'WITEL AM', 'DIVISI AM'])) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan. Pastikan ada kolom: NIK_AM (atau NIK), NAMA AM, WITEL AM, DIVISI AM'
                ];
            }

            // Get indices untuk kolom wajib + opsional
            $allColumns = array_merge($requiredColumns, $optionalColumns, ['NIK']); // Add NIK as fallback
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                // ✅ FIXED: Try NIK_AM first, then fallback to NIK
                $nik = $this->getColumnValue($row, $columnIndices['NIK_AM'])
                    ?? $this->getColumnValue($row, $columnIndices['NIK']);
                $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                $divisiAM = strtoupper(trim($this->getColumnValue($row, $columnIndices['DIVISI AM'])));

                // Validasi kolom wajib tidak boleh kosong
                if (empty($nik) || empty($namaAM) || empty($divisiAM)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIK_AM' => $nik ?? 'N/A',
                            'NAMA_AM' => $namaAM ?? 'N/A',
                            'ROLE' => $divisiAM ?? 'N/A',
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A'
                        ],
                        'error' => 'NIK_AM, NAMA AM, atau DIVISI AM kosong'
                    ];
                    continue;
                }

                // Validasi DIVISI AM harus AM atau HOTDA
                if (!in_array($divisiAM, ['AM', 'HOTDA'])) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIK_AM' => $nik,
                            'NAMA_AM' => $namaAM,
                            'ROLE' => $divisiAM,
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A'
                        ],
                        'error' => 'DIVISI AM harus AM atau HOTDA'
                    ];
                    continue;
                }

                // Check if AM already exists
                $existingAM = DB::table('account_managers')
                    ->where('nik', $nik)
                    ->first();

                if ($existingAM) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => [
                            'NIK_AM' => $nik,
                            'NAMA_AM' => $namaAM,
                            'ROLE' => $divisiAM,
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A',
                            'DIVISI' => $this->getColumnValue($row, $columnIndices['DIVISI']) ?? 'N/A'
                        ],
                        'old_data' => [
                            'nama' => $existingAM->nama,
                            'role' => $existingAM->role
                        ]
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'NIK_AM' => $nik,
                            'NAMA_AM' => $namaAM,
                            'ROLE' => $divisiAM,
                            'WITEL' => $this->getColumnValue($row, $columnIndices['WITEL AM']) ?? 'N/A',
                            'DIVISI' => $this->getColumnValue($row, $columnIndices['DIVISI']) ?? 'N/A'
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Preview berhasil',
                'data' => [
                    'summary' => [
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount,
                        'total_rows' => count($csvData)
                    ],
                    'rows' => $detailedRows
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Preview Data AM Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ FIXED: Execute Data AM Import
     * Changed to support NIK_AM column name (backward compatible with NIK)
     */
    public function executeDataAM($request)
    {
        DB::beginTransaction();

        try {
            // Extract temp file path from request
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;

            // Validate temp file exists
            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // ✅ FIXED: Changed NIK to NIK_AM (primary)
            $requiredColumns = ['NIK_AM', 'NAMA AM', 'WITEL AM', 'DIVISI AM'];
            $optionalColumns = ['PROPORSI', 'NIPNAS', 'DIVISI', 'TELDA', 'NIK']; // NIK as fallback

            $headers = array_shift($csvData);

            // ✅ FIXED: Flexible validation
            if (!$this->validateHeadersFlexible($headers, ['NIK_AM', 'NIK'], ['NAMA AM', 'WITEL AM', 'DIVISI AM'])) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan. Pastikan ada kolom: NIK_AM (atau NIK), NAMA AM, WITEL AM, DIVISI AM'
                ];
            }

            $allColumns = array_merge($requiredColumns, $optionalColumns);
            $columnIndices = $this->getColumnIndices($headers, $allColumns);

            $statistics = [
                'total_rows' => count($csvData),
                'success_count' => 0,
                'failed_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    // ✅ FIXED: Try NIK_AM first, then fallback to NIK
                    $nik = $this->getColumnValue($row, $columnIndices['NIK_AM'])
                        ?? $this->getColumnValue($row, $columnIndices['NIK']);
                    $namaAM = $this->getColumnValue($row, $columnIndices['NAMA AM']);
                    $witelName = $this->getColumnValue($row, $columnIndices['WITEL AM']);
                    $divisiAM = strtoupper(trim($this->getColumnValue($row, $columnIndices['DIVISI AM'])));

                    $proporsi = $this->getColumnValue($row, $columnIndices['PROPORSI']) ?? '1';
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $divisiList = $this->getColumnValue($row, $columnIndices['DIVISI']);
                    $teldaName = $this->getColumnValue($row, $columnIndices['TELDA']);

                    if (empty($nik) || empty($namaAM) || empty($witelName) || empty($divisiAM)) {
                        throw new \Exception('NIK_AM, NAMA AM, WITEL AM, atau DIVISI AM kosong');
                    }

                    // Validasi DIVISI AM
                    if (!in_array($divisiAM, ['AM', 'HOTDA'])) {
                        throw new \Exception('DIVISI AM harus AM atau HOTDA');
                    }

                    // Get Witel ID
                    $witel = DB::table('witel')
                        ->whereRaw('UPPER(nama) = ?', [strtoupper($witelName)])
                        ->first();

                    if (!$witel) {
                        throw new \Exception("Witel '{$witelName}' tidak ditemukan");
                    }

                    // Get TELDA ID (jika ada dan HOTDA)
                    $teldaId = null;
                    if ($divisiAM === 'HOTDA' && !empty($teldaName)) {
                        $telda = DB::table('teldas')
                            ->whereRaw('UPPER(nama) = ?', [strtoupper($teldaName)])
                            ->first();

                        if ($telda) {
                            $teldaId = $telda->id;
                        }
                    }

                    // Check existing AM
                    $existingAM = DB::table('account_managers')
                        ->where('nik', $nik)
                        ->first();

                    $amData = [
                        'nama' => $namaAM,
                        'role' => $divisiAM,
                        'witel_id' => $witel->id,
                        'telda_id' => $teldaId,
                        'updated_at' => now()
                    ];

                    if ($existingAM) {
                        DB::table('account_managers')
                            ->where('id', $existingAM->id)
                            ->update($amData);

                        $amId = $existingAM->id;
                        $statistics['updated_count']++;
                    } else {
                        $amData['nik'] = $nik;
                        $amData['created_at'] = now();

                        $amId = DB::table('account_managers')->insertGetId($amData);
                        $statistics['inserted_count']++;
                    }

                    // Handle many-to-many divisi relationships
                    if (!empty($divisiList)) {
                        DB::table('account_manager_divisi')
                            ->where('account_manager_id', $amId)
                            ->delete();

                        $divisiArray = array_map('trim', explode(',', $divisiList));

                        foreach ($divisiArray as $divisiKode) {
                            $divisi = DB::table('divisi')
                                ->whereRaw('UPPER(kode) = ?', [strtoupper($divisiKode)])
                                ->first();

                            if ($divisi) {
                                DB::table('account_manager_divisi')->insert([
                                    'account_manager_id' => $amId,
                                    'divisi_id' => $divisi->id,
                                    'is_primary' => false,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                            }
                        }
                    }

                    $statistics['success_count']++;
                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nik' => $nik ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'data_am');
            }

            $message = 'Import Data AM selesai';
            if ($statistics['updated_count'] > 0 && $statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update, {$statistics['inserted_count']} data baru)";
            } elseif ($statistics['updated_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update)";
            } elseif ($statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['inserted_count']} data baru)";
            }

            return [
                'success' => true,
                'message' => $message,
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'updated_count' => $statistics['updated_count'],
                    'inserted_count' => $statistics['inserted_count']
                ],
                'error_log_path' => $errorLogPath
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Data AM Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0
                ]
            ];
        }
    }

    /**
     * ✅ FIXED: Preview Revenue AM Import
     * Already correct - using NIK_AM column
     */
    public function previewRevenueAM($tempFilePath, $year, $month)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // ✅ Already correct: NIK_AM for Revenue AM
            $requiredColumns = ['NIPNAS', 'NIK_AM', 'PROPORSI'];
            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns) .
                        '. Kolom lain boleh ada dan akan diabaikan.'
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            $newCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            $detailedRows = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);
                $proporsi = $this->getColumnValue($row, $columnIndices['PROPORSI']);

                if (empty($nipnas) || empty($nikAM) || empty($proporsi)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'NIK_AM' => $nikAM ?? 'N/A',
                            'PROPORSI' => $proporsi ?? 'N/A'
                        ],
                        'error' => 'NIPNAS, NIK_AM, atau PROPORSI kosong'
                    ];
                    continue;
                }

                $am = DB::table('account_managers')->where('nik', $nikAM)->first();
                if (!$am) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi
                        ],
                        'error' => "Account Manager dengan NIK {$nikAM} tidak ditemukan"
                    ];
                    continue;
                }

                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi
                        ],
                        'error' => "Corporate Customer dengan NIPNAS {$nipnas} tidak ditemukan"
                    ];
                    continue;
                }

                $existingRecord = DB::table('am_revenues')
                    ->where('account_manager_id', $am->id)
                    ->where('corporate_customer_id', $cc->id)
                    ->where('tahun', $year)
                    ->where('bulan', $month)
                    ->first();

                if ($existingRecord) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi,
                            'AM_NAME' => $am->nama,
                            'CC_NAME' => $cc->nama
                        ]
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'YEAR' => $year,
                            'MONTH' => $month,
                            'NIPNAS' => $nipnas,
                            'NIK_AM' => $nikAM,
                            'PROPORSI' => $proporsi,
                            'AM_NAME' => $am->nama,
                            'CC_NAME' => $cc->nama
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Preview berhasil',
                'data' => [
                    'summary' => [
                        'new_count' => $newCount,
                        'update_count' => $updateCount,
                        'error_count' => $errorCount,
                        'total_rows' => count($csvData)
                    ],
                    'rows' => $detailedRows
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Preview Revenue AM Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ FIXED: Execute Revenue AM Import
     * Already correct - using NIK_AM column
     */
    public function executeRevenueAM($request)
    {
        DB::beginTransaction();

        try {
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;
            $year = $request instanceof Request ? $request->input('year') : null;
            $month = $request instanceof Request ? $request->input('month') : null;

            if (!$year || !$month) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Parameter year dan month wajib diisi (dari form input)'
                ];
            }

            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);
            $requiredColumns = ['NIPNAS', 'NIK_AM', 'PROPORSI'];
            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            $statistics = [
                'total_rows' => count($csvData),
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $nikAM = $this->getColumnValue($row, $columnIndices['NIK_AM']);
                    $proporsi = floatval($this->getColumnValue($row, $columnIndices['PROPORSI']));

                    if (empty($nipnas) || empty($nikAM) || empty($proporsi)) {
                        throw new \Exception('NIPNAS, NIK_AM, atau PROPORSI kosong');
                    }

                    $am = DB::table('account_managers')->where('nik', $nikAM)->first();
                    if (!$am) {
                        throw new \Exception("Account Manager dengan NIK {$nikAM} tidak ditemukan");
                    }

                    $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        throw new \Exception("Corporate Customer dengan NIPNAS {$nipnas} tidak ditemukan");
                    }

                    $ccRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    if (!$ccRevenue) {
                        $statistics['skipped_count']++;
                        $statistics['failed_rows'][] = [
                            'row_number' => $rowNumber,
                            'nipnas' => $nipnas,
                            'nik_am' => $nikAM,
                            'error' => "Data Revenue CC untuk periode {$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . " belum ada. Import Revenue CC terlebih dahulu."
                        ];
                        continue;
                    }

                    $targetRevenueAM = ($ccRevenue->target_revenue * $proporsi) / 100;
                    $realRevenueAM = ($ccRevenue->real_revenue * $proporsi) / 100;
                    $achievementRate = $targetRevenueAM > 0 ? ($realRevenueAM / $targetRevenueAM) * 100 : 0;

                    $dataToSave = [
                        'account_manager_id' => $am->id,
                        'corporate_customer_id' => $cc->id,
                        'divisi_id' => $ccRevenue->divisi_id,
                        'witel_id' => $am->witel_id,
                        'telda_id' => $am->telda_id,
                        'proporsi' => $proporsi,
                        'target_revenue' => $targetRevenueAM,
                        'real_revenue' => $realRevenueAM,
                        'achievement_rate' => $achievementRate,
                        'bulan' => $month,
                        'tahun' => $year,
                        'updated_at' => now()
                    ];

                    $existingRecord = DB::table('am_revenues')
                        ->where('account_manager_id', $am->id)
                        ->where('corporate_customer_id', $cc->id)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    if ($existingRecord) {
                        DB::table('am_revenues')
                            ->where('id', $existingRecord->id)
                            ->update($dataToSave);

                        $statistics['updated_count']++;
                    } else {
                        $dataToSave['created_at'] = now();
                        DB::table('am_revenues')->insert($dataToSave);

                        $statistics['inserted_count']++;
                    }

                    $statistics['success_count']++;
                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nipnas' => $nipnas ?? 'N/A',
                        'nik_am' => $nikAM ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_am');
            }

            $message = 'Import Revenue AM selesai';
            if ($statistics['updated_count'] > 0 && $statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update, {$statistics['inserted_count']} data baru)";
            } elseif ($statistics['updated_count'] > 0) {
                $message .= " ({$statistics['updated_count']} data di-update)";
            } elseif ($statistics['inserted_count'] > 0) {
                $message .= " ({$statistics['inserted_count']} data baru)";
            }

            return [
                'success' => true,
                'message' => $message,
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'skipped_count' => $statistics['skipped_count'],
                    'updated_count' => $statistics['updated_count'],
                    'inserted_count' => $statistics['inserted_count']
                ],
                'error_log_path' => $errorLogPath
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Revenue AM Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage(),
                'statistics' => [
                    'total_rows' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'skipped_count' => 0
                ]
            ];
        }
    }

    // ==================== HELPER METHODS ====================

    /**
     * Parse CSV file from uploaded file object
     */
    private function parseCsvFile($file)
    {
        $csvData = [];
        $handle = fopen($file->getRealPath(), 'r');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $csvData[] = $row;
        }

        fclose($handle);
        return $csvData;
    }

    /**
     * Parse CSV file from file path
     */
    private function parseCsvFileFromPath($filepath)
    {
        $csvData = [];
        $handle = fopen($filepath, 'r');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $csvData[] = $row;
        }

        fclose($handle);
        return $csvData;
    }

    /**
     * ✅ MAINTAINED: Flexible header validation
     * Only checks if required columns exist, ignores extra columns
     */
    private function validateHeaders($headers, $requiredColumns)
    {
        $cleanHeaders = array_map(function ($h) {
            return strtoupper(trim($h));
        }, $headers);

        foreach ($requiredColumns as $column) {
            $cleanColumn = strtoupper(trim($column));
            if (!in_array($cleanColumn, $cleanHeaders)) {
                return false;
            }
        }
        return true;
    }

    /**
     * ✅ NEW: Flexible header validation with alternative column names
     * Support multiple possible column names (e.g., NIK_AM or NIK)
     *
     * @param array $headers CSV headers
     * @param array $alternativeColumns Alternative column names (e.g., ['NIK_AM', 'NIK'])
     * @param array $requiredColumns Other required columns
     * @return bool
     */
    private function validateHeadersFlexible($headers, $alternativeColumns, $requiredColumns)
    {
        $cleanHeaders = array_map(function ($h) {
            return strtoupper(trim($h));
        }, $headers);

        // Check if at least one alternative column exists
        $hasAlternative = false;
        foreach ($alternativeColumns as $altCol) {
            $cleanAltCol = strtoupper(trim($altCol));
            if (in_array($cleanAltCol, $cleanHeaders)) {
                $hasAlternative = true;
                break;
            }
        }

        if (!$hasAlternative) {
            return false;
        }

        // Check all other required columns
        foreach ($requiredColumns as $column) {
            $cleanColumn = strtoupper(trim($column));
            if (!in_array($cleanColumn, $cleanHeaders)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get column indices from headers
     * Returns array with column name as key and index as value
     */
    private function getColumnIndices($headers, $columns)
    {
        $indices = [];

        $cleanHeaders = array_map(function ($h) {
            return strtoupper(trim($h));
        }, $headers);

        foreach ($columns as $column) {
            $cleanColumn = strtoupper(trim($column));
            $index = array_search($cleanColumn, $cleanHeaders);
            $indices[$column] = $index !== false ? $index : null;
        }
        return $indices;
    }

    /**
     * Get column value from row by index
     * Returns null if index is null or value doesn't exist
     */
    private function getColumnValue($row, $index)
    {
        return $index !== null && isset($row[$index]) ? trim($row[$index]) : null;
    }

    /**
     * Generate error log CSV file
     *
     * @param array $failedRows Array of failed rows with error details
     * @param string $type Type of import (data_am or revenue_am)
     * @return string|null Public URL to error log file
     */
    private function generateErrorLog($failedRows, $type)
    {
        if (empty($failedRows)) {
            return null;
        }

        $filename = 'error_log_' . $type . '_' . time() . '.csv';
        $directory = public_path('storage/import_logs');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = $directory . '/' . $filename;
        $handle = fopen($filepath, 'w');

        if ($type === 'revenue_am') {
            fputcsv($handle, ['Baris', 'NIPNAS', 'NIK_AM', 'Error']);
            foreach ($failedRows as $row) {
                fputcsv($handle, [
                    $row['row_number'],
                    $row['nipnas'] ?? 'N/A',
                    $row['nik_am'] ?? 'N/A',
                    $row['error']
                ]);
            }
        } else {
            fputcsv($handle, ['Baris', 'NIK_AM', 'Error']);
            foreach ($failedRows as $row) {
                fputcsv($handle, [
                    $row['row_number'],
                    $row['nik'] ?? 'N/A',
                    $row['error']
                ]);
            }
        }

        fclose($handle);
        return asset('storage/import_logs/' . $filename);
    }
}
