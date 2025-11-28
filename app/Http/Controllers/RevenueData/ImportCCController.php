<?php

namespace App\Http\Controllers\RevenueData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class ImportCCController extends Controller
{
    /**
     * ✅ MAINTAINED: Download Template CSV
     */
    public function downloadTemplate($type)
    {
        $templates = [
            // Data CC Template
            'data-cc' => [
                'filename' => 'template_data_cc.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME'],
                'sample' => [
                    ['76590001', 'BANK JATIM'],
                    ['76590002', 'PEMKOT SEMARANG']
                ]
            ],

            // DGS Real Revenue (Sold)
            'revenue-cc-dgs-real' => [
                'filename' => 'template_revenue_cc_dgs_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '195000000', 'HO']
                ]
            ],

            // DSS Real Revenue (Sold)
            'revenue-cc-dss-real' => [
                'filename' => 'template_revenue_cc_dss_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590010', 'PT TELKOM INDONESIA', 'DIGITAL SUSTAINABILITY SERVICE', 'SEMARANG JATENG UTARA', '250000000', 'HO']
                ]
            ],

            // DPS Real Revenue (Bill)
            'revenue-cc-dps-real' => [
                'filename' => 'template_revenue_cc_dps_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'],
                'sample' => [
                    ['76590021', 'PT TELKOMSEL', 'RETAIL & MEDIA SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '920000000', 'BILL']
                ]
            ],

            // DGS Target Revenue
            'revenue-cc-dgs-target' => [
                'filename' => 'template_revenue_cc_dgs_target.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '200000000', 'HO']
                ]
            ],

            // DSS Target Revenue
            'revenue-cc-dss-target' => [
                'filename' => 'template_revenue_cc_dss_target.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590010', 'PT TELKOM INDONESIA', 'DIGITAL SUSTAINABILITY SERVICE', 'SEMARANG JATENG UTARA', '270000000', 'HO']
                ]
            ],

            // DPS Target Revenue
            'revenue-cc-dps-target' => [
                'filename' => 'template_revenue_cc_dps_target.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590021', 'PT TELKOMSEL', 'RETAIL & MEDIA SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '950000000', 'BILL']
                ]
            ],

            // Aliases for backward compatibility
            'revenue-cc-dgs' => [
                'filename' => 'template_revenue_cc_dgs_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '195000000', 'HO']
                ]
            ],

            'revenue-cc-dss' => [
                'filename' => 'template_revenue_cc_dss_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'REVENUE_SOLD', 'SOURCE_DATA'],
                'sample' => [
                    ['76590010', 'PT TELKOM INDONESIA', 'DIGITAL SUSTAINABILITY SERVICE', 'SEMARANG JATENG UTARA', '250000000', 'HO']
                ]
            ],

            'revenue-cc-dps' => [
                'filename' => 'template_revenue_cc_dps_real.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'WITEL_BILL', 'REVENUE_BILL', 'SOURCE_DATA'],
                'sample' => [
                    ['76590021', 'PT TELKOMSEL', 'RETAIL & MEDIA SERVICE', 'SEMARANG JATENG UTARA', 'SEMARANG JATENG UTARA', '920000000', 'BILL']
                ]
            ],

            // Generic target template
            'revenue-cc-target' => [
                'filename' => 'template_revenue_cc_target.csv',
                'headers' => ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO', 'WITEL_HO', 'TARGET_REVENUE', 'SOURCE_DATA'],
                'sample' => [
                    ['76590002', 'PEMKOT SEMARANG', 'GOVERNMENT PUBLIC SERVICE', 'SEMARANG JATENG UTARA', '200000000', 'HO']
                ]
            ]
        ];

        if (!isset($templates[$type])) {
            Log::warning('Template not found', [
                'requested_type' => $type,
                'available_types' => array_keys($templates)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Template type not found',
                'available_types' => array_keys($templates)
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
     * ✅ MAINTAINED: Preview Data CC Import
     */
    public function previewDataCC($tempFilePath)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
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
                $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                if (empty($nipnas) || empty($standardName)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'STANDARD_NAME' => $standardName ?? 'N/A'
                        ],
                        'error' => 'NIPNAS atau STANDARD_NAME kosong'
                    ];
                    continue;
                }

                $existingCC = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();

                if ($existingCC) {
                    $updateCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'update',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'STANDARD_NAME' => $standardName
                        ],
                        'old_data' => [
                            'nama' => $existingCC->nama
                        ]
                    ];
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'STANDARD_NAME' => $standardName
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
            Log::error('Preview Data CC Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MAINTAINED: Execute Data CC Import
     */
    public function executeDataCC($request)
    {
        DB::beginTransaction();

        try {
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;

            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            $requiredColumns = ['NIPNAS', 'STANDARD_NAME'];
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
                'updated_count' => 0,
                'inserted_count' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);

                    if (empty($nipnas) || empty($standardName)) {
                        throw new \Exception('NIPNAS atau STANDARD_NAME kosong');
                    }

                    $existingCC = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();

                    if ($existingCC) {
                        DB::table('corporate_customers')
                            ->where('id', $existingCC->id)
                            ->update([
                                'nama' => $standardName,
                                'updated_at' => now()
                            ]);
                        $statistics['updated_count']++;
                    } else {
                        DB::table('corporate_customers')->insert([
                            'nipnas' => $nipnas,
                            'nama' => $standardName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $statistics['inserted_count']++;
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nipnas' => $nipnas ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'data_cc');
            }

            $message = 'Import Data CC selesai';
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
            Log::error('Import Data CC Error: ' . $e->getMessage());

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
     * ✅ MAINTAINED: Preview Revenue CC Import
     */
    public function previewRevenueCC($tempFilePath, $divisiId, $jenisData, $year = null, $month = null)
    {
        try {
            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // Get divisi info
            $divisi = DB::table('divisi')->where('id', $divisiId)->first();
            if (!$divisi) {
                return [
                    'success' => false,
                    'message' => 'Divisi tidak ditemukan'
                ];
            }

            // Required columns based on jenis_data
            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO'];

            if (strtolower($jenisData) === 'target') {
                $requiredColumns[] = 'TARGET_REVENUE';
                if ($divisi->kode === 'DPS') {
                    $requiredColumns[] = 'WITEL_BILL';
                } else {
                    $requiredColumns[] = 'WITEL_HO';
                }
            } else {
                if ($divisi->kode === 'DPS') {
                    $requiredColumns[] = 'WITEL_BILL';
                    $requiredColumns[] = 'REVENUE_BILL';
                } else {
                    $requiredColumns[] = 'WITEL_HO';
                    $requiredColumns[] = 'REVENUE_SOLD';
                }
            }

            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
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
                $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);

                if (empty($nipnas) || empty($lsegmentHO)) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas ?? 'N/A',
                            'LSEGMENT_HO' => $lsegmentHO ?? 'N/A'
                        ],
                        'error' => 'Data tidak lengkap'
                    ];
                    continue;
                }

                // Check if CC exists
                $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                if (!$cc) {
                    $errorCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'error',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'CUSTOMER' => 'Not found'
                        ],
                        'error' => 'Corporate Customer tidak ditemukan. Import Data CC terlebih dahulu.'
                    ];
                    continue;
                }

                // Check if revenue already exists (if year/month provided)
                if ($year && $month) {
                    $existingRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    if ($existingRevenue) {
                        $updateCount++;
                        $detailedRows[] = [
                            'row_number' => $rowNumber,
                            'status' => 'update',
                            'data' => [
                                'NIPNAS' => $nipnas,
                                'CUSTOMER' => $cc->nama,
                                'SEGMENT' => $lsegmentHO
                            ]
                        ];
                    } else {
                        $newCount++;
                        $detailedRows[] = [
                            'row_number' => $rowNumber,
                            'status' => 'new',
                            'data' => [
                                'NIPNAS' => $nipnas,
                                'CUSTOMER' => $cc->nama,
                                'SEGMENT' => $lsegmentHO
                            ]
                        ];
                    }
                } else {
                    $newCount++;
                    $detailedRows[] = [
                        'row_number' => $rowNumber,
                        'status' => 'new',
                        'data' => [
                            'NIPNAS' => $nipnas,
                            'CUSTOMER' => $cc->nama,
                            'SEGMENT' => $lsegmentHO
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
                        'total_rows' => count($detailedRows)
                    ],
                    'rows' => $detailedRows
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Preview Revenue CC Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ ENHANCED: Execute Revenue CC Import
     * Now calls recalculateAMRevenuesForCC with OLD and NEW values
     */
    public function executeRevenueCC($request)
    {
        Log::info('🚀 executeRevenueCC STARTED', [
            'request_type' => get_class($request),
            'has_temp_file' => $request instanceof Request ? $request->has('temp_file') : 'not a Request object'
        ]);

        DB::beginTransaction();

        try {
            // Extract parameters
            $tempFilePath = $request instanceof Request ? $request->input('temp_file') : $request;
            $divisiId = $request instanceof Request ? $request->input('divisi_id') : null;
            $jenisData = $request instanceof Request ? $request->input('jenis_data') : null;
            $year = $request instanceof Request ? $request->input('year') : null;
            $month = $request instanceof Request ? $request->input('month') : null;

            // Validation
            if (!$tempFilePath || !file_exists($tempFilePath)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File temporary tidak ditemukan'
                ];
            }

            if (!$divisiId || !$jenisData || !$year || !$month) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Parameter tidak lengkap: divisi_id, jenis_data, year, dan month diperlukan'
                ];
            }

            $csvData = $this->parseCsvFileFromPath($tempFilePath);

            // Get divisi info
            $divisi = DB::table('divisi')->where('id', $divisiId)->first();
            if (!$divisi) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Divisi tidak ditemukan'
                ];
            }

            // Required columns based on jenis_data
            $requiredColumns = ['NIPNAS', 'STANDARD_NAME', 'LSEGMENT_HO'];

            if (strtolower($jenisData) === 'target') {
                $requiredColumns[] = 'TARGET_REVENUE';
                $revenueColumn = 'TARGET_REVENUE';
            } else {
                if ($divisi->kode === 'DPS') {
                    $requiredColumns[] = 'REVENUE_BILL';
                    $revenueColumn = 'REVENUE_BILL';
                } else {
                    $requiredColumns[] = 'REVENUE_SOLD';
                    $revenueColumn = 'REVENUE_SOLD';
                }
            }

            // WITEL logic
            if ($divisi->kode === 'DPS') {
                $requiredColumns[] = 'WITEL_BILL';
                $witelColumn = 'WITEL_BILL';
                $revenueSource = 'BILL';
            } else {
                $requiredColumns[] = 'WITEL_HO';
                $witelColumn = 'WITEL_HO';
                $revenueSource = 'HO';
            }

            $headers = array_shift($csvData);

            if (!$this->validateHeaders($headers, $requiredColumns)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'File tidak memiliki kolom yang diperlukan: ' . implode(', ', $requiredColumns)
                ];
            }

            $columnIndices = $this->getColumnIndices($headers, $requiredColumns);

            Log::info('📋 CSV parsed successfully, starting to process rows', [
                'total_rows' => count($csvData),
                'divisi_id' => $divisiId,
                'jenis_data' => $jenisData,
                'year' => $year,
                'month' => $month
            ]);

            $statistics = [
                'total_rows' => count($csvData),
                'success_count' => 0,
                'failed_count' => 0,
                'updated_count' => 0,
                'inserted_count' => 0,
                'am_revenues_recalculated' => 0,
                'failed_rows' => []
            ];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $nipnas = $this->getColumnValue($row, $columnIndices['NIPNAS']);
                    $standardName = $this->getColumnValue($row, $columnIndices['STANDARD_NAME']);
                    $lsegmentHO = $this->getColumnValue($row, $columnIndices['LSEGMENT_HO']);
                    $witelName = $this->getColumnValue($row, $columnIndices[$witelColumn]);
                    $revenueValue = $this->getColumnValue($row, $columnIndices[$revenueColumn]);

                    if (empty($nipnas) || empty($lsegmentHO) || empty($witelName)) {
                        throw new \Exception('Data tidak lengkap (NIPNAS, LSEGMENT_HO, atau WITEL kosong)');
                    }

                    // Get or create CC
                    $cc = DB::table('corporate_customers')->where('nipnas', $nipnas)->first();
                    if (!$cc) {
                        $ccId = DB::table('corporate_customers')->insertGetId([
                            'nipnas' => $nipnas,
                            'nama' => $standardName ?? $nipnas,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $cc = DB::table('corporate_customers')->where('id', $ccId)->first();
                    }

                    // Get Witel by nama
                    $witel = DB::table('witel')
                        ->whereRaw('UPPER(TRIM(nama)) = ?', [strtoupper(trim($witelName))])
                        ->first();

                    if (!$witel) {
                        throw new \Exception("Witel '{$witelName}' tidak ditemukan di database");
                    }

                    // Get Segment by lsegment_ho
                    $segment = DB::table('segments')
                        ->whereRaw('UPPER(TRIM(lsegment_ho)) = ?', [strtoupper(trim($lsegmentHO))])
                        ->where('divisi_id', $divisiId)
                        ->first();

                    if (!$segment) {
                        throw new \Exception("Segment '{$lsegmentHO}' tidak ditemukan untuk divisi {$divisi->nama}");
                    }

                    // Parse revenue value
                    $revenue = (float) str_replace([',', '.00'], ['', ''], $revenueValue ?? 0);

                    // Prepare data
                    $dataToSave = [
                        'corporate_customer_id' => $cc->id,
                        'divisi_id' => $divisiId,
                        'segment_id' => $segment->id,
                        'nama_cc' => $cc->nama,
                        'nipnas' => $cc->nipnas,
                        'revenue_source' => $revenueSource,
                        'tipe_revenue' => 'REGULER',
                        'bulan' => $month,
                        'tahun' => $year,
                        'updated_at' => now()
                    ];

                    // WITEL logic
                    if ($divisi->kode === 'DPS') {
                        $dataToSave['witel_bill_id'] = $witel->id;
                        $dataToSave['witel_ho_id'] = null;
                    } else {
                        $dataToSave['witel_ho_id'] = $witel->id;
                        $dataToSave['witel_bill_id'] = null;
                    }

                    // Check if exists
                    $existingRevenue = DB::table('cc_revenues')
                        ->where('corporate_customer_id', $cc->id)
                        ->where('divisi_id', $divisiId)
                        ->where('tahun', $year)
                        ->where('bulan', $month)
                        ->first();

                    // ✅ CRITICAL: Store OLD values BEFORE update
                    $oldTargetRevenue = 0;
                    $oldRealRevenue = 0;

                    if ($existingRevenue) {
                        $oldTargetRevenue = $existingRevenue->target_revenue;
                        $oldRealRevenue = $existingRevenue->real_revenue;

                        Log::info('💾 Found existing CC Revenue, preparing to update', [
                            'cc_id' => $cc->id,
                            'cc_name' => $cc->nama,
                            'existing_revenue_id' => $existingRevenue->id,
                            'OLD_target' => $oldTargetRevenue,
                            'OLD_real' => $oldRealRevenue,
                            'NEW_revenue_from_csv' => $revenue,
                            'jenis_data' => $jenisData
                        ]);

                        // CONDITIONAL UPDATE based on jenis_data
                        if (strtolower($jenisData) === 'target') {
                            $dataToSave['target_revenue'] = $revenue;
                            $dataToSave['real_revenue'] = $existingRevenue->real_revenue;
                        } else {
                            $dataToSave['target_revenue'] = $existingRevenue->target_revenue;
                            $dataToSave['real_revenue'] = $revenue;
                        }

                        DB::table('cc_revenues')
                            ->where('id', $existingRevenue->id)
                            ->update($dataToSave);

                        $statistics['updated_count']++;

                        // ✅ DEBUG: Log parameters before calling recalculation
                        Log::info('📞 About to call recalculateAMRevenuesForCC', [
                            'cc_id' => $cc->id,
                            'divisi_id' => $divisiId,
                            'month' => $month,
                            'year' => $year,
                            'old_target' => $oldTargetRevenue,
                            'old_real' => $oldRealRevenue,
                            'new_target' => $dataToSave['target_revenue'],
                            'new_real' => $dataToSave['real_revenue'],
                            'jenis_data' => $jenisData
                        ]);

                        // ✅ CRITICAL FIX: Pass OLD and NEW values for smart comparison
                        $amRecalculated = $this->recalculateAMRevenuesForCC(
                            $cc->id,
                            $divisiId,
                            $month,
                            $year,
                            $oldTargetRevenue,  // OLD target
                            $oldRealRevenue,    // OLD real
                            $dataToSave['target_revenue'],  // NEW target
                            $dataToSave['real_revenue']     // NEW real
                        );

                        Log::info('📞 recalculateAMRevenuesForCC returned', [
                            'am_recalculated' => $amRecalculated
                        ]);

                        $statistics['am_revenues_recalculated'] += $amRecalculated;

                    } else {
                        // Insert new
                        if (strtolower($jenisData) === 'target') {
                            $dataToSave['target_revenue'] = $revenue;
                            $dataToSave['real_revenue'] = 0;
                        } else {
                            $dataToSave['real_revenue'] = $revenue;
                            $dataToSave['target_revenue'] = 0;
                        }

                        $dataToSave['created_at'] = now();
                        DB::table('cc_revenues')->insert($dataToSave);

                        $statistics['inserted_count']++;

                        // ✅ DEBUG: Log parameters before calling recalculation
                        Log::info('📞 About to call recalculateAMRevenuesForCC (NEW DATA)', [
                            'cc_id' => $cc->id,
                            'divisi_id' => $divisiId,
                            'month' => $month,
                            'year' => $year,
                            'old_target' => 0,
                            'old_real' => 0,
                            'new_target' => $dataToSave['target_revenue'],
                            'new_real' => $dataToSave['real_revenue'],
                            'jenis_data' => $jenisData
                        ]);

                        // Recalculate for new data (old values = 0)
                        $amRecalculated = $this->recalculateAMRevenuesForCC(
                            $cc->id,
                            $divisiId,
                            $month,
                            $year,
                            0,  // OLD target = 0
                            0,  // OLD real = 0
                            $dataToSave['target_revenue'],  // NEW target
                            $dataToSave['real_revenue']     // NEW real
                        );

                        Log::info('📞 recalculateAMRevenuesForCC returned (NEW DATA)', [
                            'am_recalculated' => $amRecalculated
                        ]);

                        $statistics['am_revenues_recalculated'] += $amRecalculated;
                    }

                    $statistics['success_count']++;

                } catch (\Exception $e) {
                    $statistics['failed_count']++;
                    $statistics['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'nipnas' => $nipnas ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            $errorLogPath = null;
            if (count($statistics['failed_rows']) > 0) {
                $errorLogPath = $this->generateErrorLog($statistics['failed_rows'], 'revenue_cc');
            }

            // ENHANCED MESSAGE with AM recalculation info
            $message = 'Import Revenue CC selesai';
            $messageParts = [];

            if ($statistics['updated_count'] > 0) {
                $messageParts[] = "{$statistics['updated_count']} data di-update";
            }
            if ($statistics['inserted_count'] > 0) {
                $messageParts[] = "{$statistics['inserted_count']} data baru";
            }
            if ($statistics['am_revenues_recalculated'] > 0) {
                $messageParts[] = "{$statistics['am_revenues_recalculated']} AM revenues recalculated";
            }

            if (!empty($messageParts)) {
                $message .= " (" . implode(', ', $messageParts) . ")";
            }

            Log::info('Import Revenue CC Completed', [
                'statistics' => $statistics,
                'divisi_id' => $divisiId,
                'jenis_data' => $jenisData,
                'periode' => "{$year}-{$month}",
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => $message,
                'statistics' => [
                    'total_rows' => $statistics['total_rows'],
                    'success_count' => $statistics['success_count'],
                    'failed_count' => $statistics['failed_count'],
                    'updated_count' => $statistics['updated_count'],
                    'inserted_count' => $statistics['inserted_count'],
                    'am_revenues_recalculated' => $statistics['am_revenues_recalculated']
                ],
                'error_log_path' => $errorLogPath
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Revenue CC Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

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

    // ==================== HELPER METHODS ====================

    /**
     * ✅ MAINTAINED: Parse CSV file from file path
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
     * ✅ MAINTAINED: Validate CSV headers
     */
    private function validateHeaders($headers, $requiredColumns)
    {
        $cleanHeaders = array_map(function($h) {
            return strtoupper(str_replace([' ', '_', '.'], '', trim($h)));
        }, $headers);

        foreach ($requiredColumns as $column) {
            $cleanColumn = strtoupper(str_replace([' ', '_', '.'], '', trim($column)));

            if (!in_array($cleanColumn, $cleanHeaders)) {
                return false;
            }
        }
        return true;
    }

    /**
     * ✅ MAINTAINED: Get column indices mapping
     */
    private function getColumnIndices($headers, $columns)
    {
        $indices = [];

        $normalizedMap = [];
        foreach ($headers as $index => $header) {
            $normalized = strtoupper(str_replace([' ', '_', '.'], '', trim($header)));
            $normalizedMap[$normalized] = $index;
        }

        foreach ($columns as $column) {
            $normalized = strtoupper(str_replace([' ', '_', '.'], '', trim($column)));
            $indices[$column] = $normalizedMap[$normalized] ?? null;
        }

        return $indices;
    }

    /**
     * ✅ MAINTAINED: Get column value safely
     */
    private function getColumnValue($row, $index)
    {
        return $index !== null && isset($row[$index]) ? trim($row[$index]) : null;
    }

    /**
     * ✅ MAINTAINED: Generate error log CSV
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

        fputcsv($handle, ['Baris', 'NIPNAS', 'Error']);
        foreach ($failedRows as $row) {
            fputcsv($handle, [
                $row['row_number'],
                $row['nipnas'] ?? 'N/A',
                $row['error']
            ]);
        }

        fclose($handle);
        return asset('storage/import_logs/' . $filename);
    }

        /**
     * ✅ ENHANCED: Recalculate AM Revenues - ALWAYS UPDATE
     *
     * SIMPLE LOGIC: Always recalculate ALL fields with NEW values
     * - No complex detection needed
     * - Always sync AM with CC values
     * - Proportional calculation applied to both target and real
     *
     * @param int $ccId Corporate Customer ID
     * @param int $divisiId Divisi ID
     * @param int $bulan Month
     * @param int $tahun Year
     * @param float $oldTargetRevenue OLD target revenue (for logging only)
     * @param float $oldRealRevenue OLD real revenue (for logging only)
     * @param float $newTargetRevenue NEW target revenue (will be applied)
     * @param float $newRealRevenue NEW real revenue (will be applied)
     * @return int Number of AM revenues recalculated
     */
    private function recalculateAMRevenuesForCC($ccId, $divisiId, $bulan, $tahun,
        $oldTargetRevenue, $oldRealRevenue, $newTargetRevenue, $newRealRevenue)
    {
        try {
            Log::info('🔍 recalculateAMRevenuesForCC CALLED', [
                'cc_id' => $ccId,
                'divisi_id' => $divisiId,
                'periode' => "{$tahun}-{$bulan}",
                'new_target' => $newTargetRevenue,
                'new_real' => $newRealRevenue
            ]);

            // Get all AM revenues for this CC, divisi, and period
            $amRevenues = DB::table('am_revenues')
                ->where('corporate_customer_id', $ccId)
                ->where('divisi_id', $divisiId)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            if ($amRevenues->isEmpty()) {
                Log::warning('⚠️ NO AM REVENUES FOUND', [
                    'cc_id' => $ccId,
                    'divisi_id' => $divisiId,
                    'periode' => "{$tahun}-{$bulan}"
                ]);
                return 0;
            }

            Log::info('✅ Found AM Revenues', ['count' => $amRevenues->count()]);

            $updatedCount = 0;

            foreach ($amRevenues as $amRevenue) {
                // Normalize proporsi
                $proporsi = $amRevenue->proporsi;
                if ($proporsi > 1) {
                    $proporsi = $proporsi / 100;
                }

                // ALWAYS recalculate dengan nilai CC terbaru
                $newTargetAM = $newTargetRevenue * $proporsi;
                $newRealAM = $newRealRevenue * $proporsi;
                $achievementRate = $newTargetAM > 0 ? ($newRealAM / $newTargetAM) * 100 : 0;

                $updateData = [
                    'target_revenue' => $newTargetAM,
                    'real_revenue' => $newRealAM,
                    'achievement_rate' => round($achievementRate, 2),
                    'updated_at' => now()
                ];

                try {
                    DB::table('am_revenues')
                        ->where('id', $amRevenue->id)
                        ->update($updateData);

                    $updatedCount++;

                    Log::info('✅ AM Updated', [
                        'am_id' => $amRevenue->id,
                        'proporsi' => $proporsi,
                        'new_target' => $newTargetAM,
                        'new_real' => $newRealAM
                    ]);
                } catch (\Exception $updateEx) {
                    Log::error('❌ Failed to update AM', [
                        'am_id' => $amRevenue->id,
                        'error' => $updateEx->getMessage()
                    ]);
                }
            }

            Log::info('✅ Recalculation Complete', [
                'cc_id' => $ccId,
                'updated_count' => $updatedCount
            ]);

            return $updatedCount;

        } catch (\Exception $e) {
            Log::error('❌ Recalculate Error: ' . $e->getMessage(), [
                'cc_id' => $ccId,
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

/**
 * ============================================================
 * END OF METHOD
 * ============================================================
 *
 * SETELAH PASTE:
 * 1. Save file
 * 2. Run: php artisan config:clear
 * 3. Run: php artisan cache:clear
 * 4. Test import Revenue CC
 *
 * Expected result:
 * "Import Revenue CC selesai (1 data di-update, 2 AM revenues recalculated)"
 *
 * ============================================================
 */

}