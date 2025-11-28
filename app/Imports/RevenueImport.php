<?php

namespace App\Imports;

use App\Models\Revenue;
use App\Models\AccountManager;
use App\Models\CorporateCustomer;
use App\Models\Divisi;
use App\Models\Witel;
use App\Models\Regional;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures, RemembersRowNumber;

    private $importedCount = 0;
    private $updatedCount = 0;
    private $duplicateCount = 0;
    private $errorCount = 0;
    private $skippedCount = 0;
    private $conflictCount = 0;

    private $errorDetails = [];
    private $warningDetails = [];
    private $successDetails = [];
    private $conflictDetails = [];

    // Master data caching
    private $accountManagers = [];
    private $corporateCustomers = [];
    private $divisiList = [];
    private $witelList = [];
    private $regionalList = [];

    private $year;
    private $overwriteMode;
    private $chunkSize = 50;
    private $processedRows = 0;

    // Monthly pairs tracking
    private $monthlyPairs = [];
    private $detectedColumns = [];

    // Conflict tracking
    private $existingDataCache = [];

    // Alternative column names
    private $alternativeColumns = [
        'nama_am' => [
            'nama am', 'nama_am', 'account_manager', 'account manager', 'NAMA AM',
            'Nama AM', 'Name AM', 'AM Name', 'am_name', 'namaAM', 'nama account manager',
            'account_manager_name', 'AM_NAME', 'nama_account_manager'
        ],
        'nik' => [
            'nik', 'NIK', 'Nik', 'employee_id', 'emp_id', 'id_karyawan', 'NIK_AM',
            'nik_am', 'employee_number', 'emp_number'
        ],
        'standard_name' => [
            'standard_name', 'standard name', 'STANDARD NAME', 'Standard Name',
            'nama customer', 'nama_customer', 'corporate customer', 'corporate_customer',
            'customer_name', 'Customer Name', 'CUSTOMER NAME', 'nama_corporate',
            'nama', 'NAMA', 'Nama', 'name', 'Name', 'NAME', 'company_name',
            'Company Name', 'COMPANY NAME', 'company', 'Company', 'COMPANY',
            'customer', 'Customer', 'CUSTOMER'
        ],
        'nipnas' => [
            'nipnas', 'NIPNAS', 'Nipnas', 'customer_id', 'cust_id', 'id_customer',
            'CUSTOMER_ID', 'CUST_ID', 'ID_CUSTOMER', 'customer code', 'CUSTOMER CODE',
            'customer_code', 'code', 'Code', 'CODE', 'CUSTOMER_CODE'
        ],
        'divisi' => [
            'divisi', 'DIVISI', 'Divisi', 'divisi_id', 'division', 'Division', 'DIVISION',
            'nama_divisi', 'NAMA_DIVISI', 'nama divisi', 'NAMA DIVISI'
        ],
        'witel' => [
            'witel', 'WITEL', 'Witel', 'witel_ho', 'WITEL HO', 'Witel HO',
            'witel_name', 'WITEL_NAME', 'nama_witel', 'NAMA_WITEL'
        ],
        'regional' => [
            'regional', 'REGIONAL', 'Regional', 'treg', 'TREG', 'Treg',
            'nama_regional', 'NAMA_REGIONAL', 'regional_name', 'REGIONAL_NAME'
        ]
    ];

    /**
     * ✅ Safe property initialization in constructor
     */
    public function __construct($year = null, $overwriteMode = 'update')
    {
        // Initialize all arrays
        $this->errorDetails = [];
        $this->warningDetails = [];
        $this->successDetails = [];
        $this->conflictDetails = [];
        $this->accountManagers = [];
        $this->corporateCustomers = [];
        $this->divisiList = [];
        $this->witelList = [];
        $this->regionalList = [];
        $this->monthlyPairs = [];
        $this->detectedColumns = [];
        $this->existingDataCache = [];

        // Initialize counters
        $this->importedCount = 0;
        $this->updatedCount = 0;
        $this->duplicateCount = 0;
        $this->errorCount = 0;
        $this->skippedCount = 0;
        $this->conflictCount = 0;
        $this->processedRows = 0;

        // Smart year detection
        if ($year) {
            if (is_numeric($year) && $year >= 2020 && $year <= 2030) {
                $this->year = (int)$year;
            } else {
                Log::warning("Invalid year provided: {$year}, using current year");
                $this->year = (int)date('Y');
            }
        } else {
            $this->year = (int)date('Y');
        }

        // Set overwrite mode
        $this->overwriteMode = in_array($overwriteMode, ['update', 'skip', 'ask']) ? $overwriteMode : 'update';

        Log::info("RevenueImport initialized", [
            'year' => $this->year,
            'overwrite_mode' => $this->overwriteMode
        ]);

        try {
            $this->loadMasterData();
            $this->loadExistingDataCache();
        } catch (\Exception $e) {
            Log::error("Failed to initialize RevenueImport: " . $e->getMessage());
            throw $e;
        }

        // Set memory dan timeout untuk file besar
        ini_set('memory_limit', '2048M');
        set_time_limit(600);
    }

    /**
     * Load existing revenue data for conflict detection
     */
    private function loadExistingDataCache()
    {
        try {
            // Load existing revenue data untuk year ini
            $existingRevenues = Revenue::whereYear('bulan', $this->year)
                ->with(['accountManager', 'corporateCustomer', 'divisi'])
                ->get();

            foreach ($existingRevenues as $revenue) {
                $key = sprintf('%d_%d_%d_%s',
                    $revenue->account_manager_id,
                    $revenue->corporate_customer_id,
                    $revenue->divisi_id,
                    $revenue->bulan
                );

                $this->existingDataCache[$key] = [
                    'id' => $revenue->id,
                    'target_revenue' => $revenue->target_revenue,
                    'real_revenue' => $revenue->real_revenue,
                    'account_manager' => $revenue->accountManager->nama ?? 'Unknown',
                    'corporate_customer' => $revenue->corporateCustomer->nama ?? 'Unknown',
                    'divisi' => $revenue->divisi->nama ?? 'Unknown',
                    'bulan' => $revenue->bulan,
                    'created_at' => $revenue->created_at
                ];
            }

            Log::info('✅ Existing revenue data cache loaded', [
                'year' => $this->year,
                'existing_records' => count($existingRevenues)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error loading existing data cache: ' . $e->getMessage());
            $this->existingDataCache = [];
        }
    }

    /**
     * Load master data dengan caching yang lebih efisien
     */
    private function loadMasterData()
    {
        try {
            // Load Account Managers dengan semua relasi
            $accountManagers = AccountManager::with(['divisis', 'witel', 'regional'])->get();
            foreach ($accountManagers as $am) {
                $normalizedName = $this->normalizeString($am->nama);
                $this->accountManagers['nama:' . $normalizedName] = $am;
                $this->accountManagers['nik:' . trim($am->nik)] = $am;
                $this->accountManagers['id:' . $am->id] = $am;
            }

            // Load Corporate Customers
            $corporateCustomers = CorporateCustomer::all();
            foreach ($corporateCustomers as $cc) {
                $normalizedName = $this->normalizeString($cc->nama);
                $this->corporateCustomers['nama:' . $normalizedName] = $cc;
                if (!empty($cc->nipnas)) {
                    $this->corporateCustomers['nipnas:' . trim($cc->nipnas)] = $cc;
                }
                $this->corporateCustomers['id:' . $cc->id] = $cc;
            }

            // Load Divisi
            $divisiList = Divisi::all();
            foreach ($divisiList as $divisi) {
                $normalizedName = $this->normalizeString($divisi->nama);
                $this->divisiList['nama:' . $normalizedName] = $divisi;
                $this->divisiList['id:' . $divisi->id] = $divisi;
            }

            // Load Witel dan Regional untuk validasi
            $witelList = Witel::all();
            foreach ($witelList as $witel) {
                $normalizedName = $this->normalizeString($witel->nama);
                $this->witelList['nama:' . $normalizedName] = $witel;
                $this->witelList['id:' . $witel->id] = $witel;
            }

            $regionalList = Regional::all();
            foreach ($regionalList as $regional) {
                $normalizedName = $this->normalizeString($regional->nama);
                $this->regionalList['nama:' . $normalizedName] = $regional;
                $this->regionalList['id:' . $regional->id] = $regional;
            }

            Log::info('✅ Master data loaded successfully for Revenue Import', [
                'year' => $this->year,
                'account_managers' => count($accountManagers),
                'corporate_customers' => count($corporateCustomers),
                'divisi' => count($divisiList),
                'witel' => count($witelList),
                'regional' => count($regionalList)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error loading master data: ' . $e->getMessage());
            throw new \Exception('Gagal memuat master data: ' . $e->getMessage());
        }
    }

    /**
     * Normalize string untuk konsistensi pencarian
     */
    private function normalizeString($string)
    {
        if (empty($string)) return '';

        $normalized = strtolower(trim($string));
        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        // Remove special characters that might cause issues
        $normalized = preg_replace('/[^\w\s-]/', '', $normalized);

        return $normalized;
    }

    /**
     * Collection processing dengan better error handling
     */
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errorDetails[] = "❌ File Excel kosong atau tidak memiliki data";
            $this->errorCount++;
            return;
        }

        try {
            // Column identification dengan validasi
            $firstRow = $rows->first();
            $this->detectedColumns = array_keys($firstRow->toArray());
            $columnMap = $this->identifyColumns($firstRow);

            // Detect monthly column pairs dengan better logic
            $this->monthlyPairs = $this->detectMonthlyColumns($this->detectedColumns);

            // Validate required columns
            $this->validateRequiredColumns($columnMap);

            Log::info('📊 Starting Revenue import process', [
                'total_rows' => $rows->count(),
                'year' => $this->year,
                'overwrite_mode' => $this->overwriteMode,
                'columns_found' => array_keys($columnMap),
                'detected_columns' => $this->detectedColumns,
                'monthly_pairs' => count($this->monthlyPairs)
            ]);

            // Process data dengan chunking yang lebih smart
            $dataRows = $rows->slice(1); // Skip header row

            if ($dataRows->isEmpty()) {
                $this->warningDetails[] = "⚠️ File hanya berisi header, tidak ada data untuk diproses";
                return;
            }

            $dataRows->chunk($this->chunkSize)->each(function ($chunk, $chunkIndex) use ($columnMap) {
                $this->processChunk($chunk, $chunkIndex, $columnMap);
            });

            Log::info('✅ Revenue import process completed', [
                'year' => $this->year,
                'overwrite_mode' => $this->overwriteMode,
                'imported' => $this->importedCount,
                'updated' => $this->updatedCount,
                'duplicates' => $this->duplicateCount,
                'conflicts' => $this->conflictCount,
                'errors' => $this->errorCount,
                'skipped' => $this->skippedCount,
                'processed_rows' => $this->processedRows
            ]);

        } catch (\Exception $e) {
            $this->errorCount++;
            $this->errorDetails[] = "❌ Error processing file: " . $e->getMessage();
            Log::error('Revenue Import Processing Error: ' . $e->getMessage());
        }
    }

    /**
     * 🔧 NEW: Identify columns based on alternative names
     */
    private function identifyColumns($firstRow)
    {
        $columnMap = [];
        $headers = array_keys($firstRow->toArray());

        foreach ($this->alternativeColumns as $standardKey => $alternatives) {
            foreach ($headers as $header) {
                $normalizedHeader = $this->normalizeString($header);

                foreach ($alternatives as $alternative) {
                    $normalizedAlternative = $this->normalizeString($alternative);

                    if ($normalizedHeader === $normalizedAlternative ||
                        strpos($normalizedHeader, $normalizedAlternative) !== false) {
                        $columnMap[$standardKey] = $header;
                        break 2; // Break both loops
                    }
                }
            }
        }

        Log::info('Column mapping identified', [
            'column_map' => $columnMap,
            'headers' => $headers
        ]);

        return $columnMap;
    }

    /**
     * 🔧 NEW: Extract row data based on column mapping
     */
    private function extractRowData($row, $columnMap, $rowNumber)
    {
        $data = [];

        // Extract basic required fields
        $data['am_name'] = isset($columnMap['nama_am']) ? trim($row[$columnMap['nama_am']] ?? '') : '';
        $data['nik'] = isset($columnMap['nik']) ? trim($row[$columnMap['nik']] ?? '') : '';
        $data['cc_name'] = isset($columnMap['standard_name']) ? trim($row[$columnMap['standard_name']] ?? '') : '';
        $data['nipnas'] = isset($columnMap['nipnas']) ? trim($row[$columnMap['nipnas']] ?? '') : '';
        $data['divisi_name'] = isset($columnMap['divisi']) ? trim($row[$columnMap['divisi']] ?? '') : '';

        // Validate required data
        if (empty($data['am_name'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Account Manager tidak boleh kosong";
            $this->errorCount++;
            return null;
        }

        if (empty($data['cc_name'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Corporate Customer tidak boleh kosong";
            $this->errorCount++;
            return null;
        }

        return $data;
    }

    /**
     * 🔧 NEW: Check if row is empty
     */
    private function isEmptyRow($row)
    {
        $values = array_values($row->toArray());
        foreach ($values as $value) {
            if (!empty(trim($value))) {
                return false;
            }
        }
        return true;
    }

    /**
     * 🔧 NEW: Find Account Manager
     */
    private function findAccountManager($amName, $nik, $rowNumber)
    {
        $normalizedName = $this->normalizeString($amName);

        // Try exact match by name
        if (isset($this->accountManagers['nama:' . $normalizedName])) {
            return $this->accountManagers['nama:' . $normalizedName];
        }

        // Try by NIK if provided
        if (!empty($nik) && isset($this->accountManagers['nik:' . trim($nik)])) {
            return $this->accountManagers['nik:' . trim($nik)];
        }

        // Try fuzzy matching
        foreach ($this->accountManagers as $key => $am) {
            if (strpos($key, 'nama:') === 0) {
                $similarity = 0;
                similar_text($normalizedName, substr($key, 5), $similarity);
                if ($similarity >= 80) {
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Menggunakan fuzzy match untuk AM '{$amName}' → '{$am->nama}' (similarity: {$similarity}%)";
                    return $am;
                }
            }
        }

        $this->errorDetails[] = "❌ Baris {$rowNumber}: Account Manager '{$amName}' tidak ditemukan";
        $this->errorCount++;
        return null;
    }

    /**
     * 🔧 NEW: Find Corporate Customer
     */
    private function findCorporateCustomer($ccName, $nipnas, $rowNumber)
    {
        $normalizedName = $this->normalizeString($ccName);

        // Try exact match by name
        if (isset($this->corporateCustomers['nama:' . $normalizedName])) {
            return $this->corporateCustomers['nama:' . $normalizedName];
        }

        // Try by NIPNAS if provided
        if (!empty($nipnas) && isset($this->corporateCustomers['nipnas:' . trim($nipnas)])) {
            return $this->corporateCustomers['nipnas:' . trim($nipnas)];
        }

        // Try fuzzy matching
        foreach ($this->corporateCustomers as $key => $cc) {
            if (strpos($key, 'nama:') === 0) {
                $similarity = 0;
                similar_text($normalizedName, substr($key, 5), $similarity);
                if ($similarity >= 80) {
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Menggunakan fuzzy match untuk Customer '{$ccName}' → '{$cc->nama}' (similarity: {$similarity}%)";
                    return $cc;
                }
            }
        }

        $this->errorDetails[] = "❌ Baris {$rowNumber}: Corporate Customer '{$ccName}' tidak ditemukan";
        $this->errorCount++;
        return null;
    }

    /**
     * 🔧 NEW: Find and validate Divisi
     */
    private function findAndValidateDivisi($divisiName, $accountManager, $rowNumber)
    {
        // If divisi name is provided, try to find it
        if (!empty($divisiName)) {
            $normalizedName = $this->normalizeString($divisiName);

            if (isset($this->divisiList['nama:' . $normalizedName])) {
                $divisi = $this->divisiList['nama:' . $normalizedName];

                // Validate if AM has this divisi
                if ($accountManager->divisis->contains('id', $divisi->id)) {
                    return $divisi;
                } else {
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Divisi '{$divisiName}' tidak terkait dengan AM '{$accountManager->nama}', menggunakan divisi pertama AM";
                }
            } else {
                $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Divisi '{$divisiName}' tidak ditemukan, menggunakan divisi pertama AM";
            }
        }

        // Use first divisi from Account Manager
        $firstDivisi = $accountManager->divisis->first();
        if (!$firstDivisi) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Account Manager '{$accountManager->nama}' tidak memiliki divisi yang terkait";
            $this->errorCount++;
            return null;
        }

        return $firstDivisi;
    }

    /**
     * 🔧 FIXED: Parse numeric value dengan support zero dan negative
     */
    private function parseNumericValue($value)
    {
        // Handle null or empty values - return null (bukan 0) untuk distinguish empty vs zero
        if ($value === null || $value === '') {
            return null;
        }

        // Handle array values (from Excel import)
        if (is_array($value)) {
            $numericValues = array_filter($value, function($v) {
                return is_numeric($v) || (is_string($v) && preg_match('/^-?[\d,.]+$/', trim($v)));
            });

            if (empty($numericValues)) {
                return null;
            }

            $value = reset($numericValues);
        }

        // If already numeric, return as-is (supporting negative values and zero)
        if (is_numeric($value)) {
            return (float)$value;
        }

        // Handle string numeric values with thousands separators
        if (is_string($value)) {
            $value = trim($value);

            // Handle negative values
            $isNegative = (strpos($value, '-') === 0);
            if ($isNegative) {
                $value = substr($value, 1); // Remove minus sign temporarily
            }

            // Clean thousands separators and currency symbols
            $cleaned = preg_replace('/[^\d,.]/', '', $value);

            // Handle comma as thousand separator vs decimal separator
            if (substr_count($cleaned, ',') == 1 && substr_count($cleaned, '.') == 0) {
                $parts = explode(',', $cleaned);
                if (strlen($parts[1]) <= 2) {
                    // Comma as decimal separator
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    // Comma as thousand separator
                    $cleaned = str_replace(',', '', $cleaned);
                }
            } else {
                // Remove commas (thousand separators)
                $cleaned = str_replace(',', '', $cleaned);
            }

            if (!is_numeric($cleaned)) {
                return null;
            }

            $result = (float)$cleaned;

            // Apply negative sign if needed
            if ($isNegative) {
                $result = -$result;
            }

            return $result;
        }

        return null;
    }

    /**
     * 🔧 NEW: Handle existing data conflict
     */
    private function handleExistingDataConflict($existingData, $targetRevenue, $realRevenue, $monthName, $rowNumber)
    {
        $existingTarget = $existingData['target_revenue'];
        $existingReal = $existingData['real_revenue'];

        // Check if values are actually different
        $targetChanged = abs($existingTarget - $targetRevenue) > 0.01; // Allow small floating point differences
        $realChanged = abs($existingReal - $realRevenue) > 0.01;

        if (!$targetChanged && !$realChanged) {
            // No actual change - mark as duplicate
            $this->duplicateCount++;
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}, {$monthName}: Data sama dengan existing, tidak ada perubahan";
            return ['action' => 'duplicate'];
        }

        // Handle based on overwrite mode
        switch ($this->overwriteMode) {
            case 'skip':
                $this->skippedCount++;
                $this->warningDetails[] = "⚠️ Baris {$rowNumber}, {$monthName}: Data existing dilewati (skip mode)";
                return ['action' => 'skipped'];

            case 'ask':
                $this->conflictCount++;
                $this->conflictDetails[] = "🔄 Baris {$rowNumber}, {$monthName}: Konflik data - Target: {$existingTarget} → {$targetRevenue}, Real: {$existingReal} → {$realRevenue}";
                // Fall through to update

            case 'update':
            default:
                // Update existing record
                try {
                    Revenue::where('id', $existingData['id'])->update([
                        'target_revenue' => $targetRevenue,
                        'real_revenue' => $realRevenue,
                        'updated_at' => now()
                    ]);

                    // Update cache
                    $cacheKey = sprintf('%d_%d_%d_%s',
                        $existingData['account_manager_id'] ?? 0,
                        $existingData['corporate_customer_id'] ?? 0,
                        $existingData['divisi_id'] ?? 0,
                        $existingData['bulan']
                    );

                    if (isset($this->existingDataCache[$cacheKey])) {
                        $this->existingDataCache[$cacheKey]['target_revenue'] = $targetRevenue;
                        $this->existingDataCache[$cacheKey]['real_revenue'] = $realRevenue;
                    }

                    $changeInfo = [];
                    if ($targetChanged) {
                        $changeInfo[] = "Target: {$existingTarget} → {$targetRevenue}";
                    }
                    if ($realChanged) {
                        $changeInfo[] = "Real: {$existingReal} → {$realRevenue}";
                    }

                    $this->successDetails[] = "✅ Baris {$rowNumber}, {$monthName}: Data diupdate - " . implode(', ', $changeInfo);
                    return ['action' => 'updated'];

                } catch (\Exception $e) {
                    $this->errorDetails[] = "❌ Baris {$rowNumber}, {$monthName}: Gagal update - " . $e->getMessage();
                    $this->errorCount++;
                    return ['action' => 'error'];
                }
        }
    }

/**
     * Detect monthly column pairs dengan lebih fleksibel
     */
    private function detectMonthlyColumns($headers)
    {
        $monthlyPairs = [];

        // Month variations yang bisa muncul di Excel (Indonesia + English)
        $monthVariations = [
            1 => ['JAN', 'JANUARI', 'JANUARY', '01', 'JANUARY', 'Jan'],
            2 => ['FEB', 'FEBRUARI', 'FEBRUARY', '02', 'Feb'],
            3 => ['MAR', 'MARET', 'MARCH', '03', 'Mar'],
            4 => ['APR', 'APRIL', '04', 'Apr'],
            5 => ['MEI', 'MAY', '05', 'Mei'],
            6 => ['JUN', 'JUNI', 'JUNE', '06', 'Jun'],
            7 => ['JUL', 'JULI', 'JULY', '07', 'Jul'],
            8 => ['AGU', 'AGS', 'AGUSTUS', 'AUGUST', '08', 'Aug', 'Agus'],
            9 => ['SEP', 'SEPTEMBER', '09', 'Sep'],
            10 => ['OKT', 'OKTOBER', 'OCTOBER', '10', 'Oct'],
            11 => ['NOV', 'NOVEMBER', '11', 'Nov'],
            12 => ['DES', 'DESEMBER', 'DECEMBER', 'DEC', '12', 'Dec']
        ];

        // Look for Real-Target pairs dengan pattern yang lebih fleksibel
        foreach ($monthVariations as $monthNum => $monthNames) {
            $realColumn = null;
            $targetColumn = null;

            // Try multiple patterns untuk Real dan Target
            foreach ($headers as $header) {
                $normalizedHeader = strtoupper(trim($header));

                foreach ($monthNames as $monthName) {
                    // Pattern untuk Real columns
                    $realPatterns = [
                        '/^REAL[_\s]*' . $monthName . '$/i',
                        '/^' . $monthName . '[_\s]*REAL$/i',
                        '/^REVENUE[_\s]*' . $monthName . '$/i',
                        '/^' . $monthName . '[_\s]*REVENUE$/i',
                        '/^ACTUAL[_\s]*' . $monthName . '$/i',
                        '/^' . $monthName . '[_\s]*ACTUAL$/i'
                    ];

                    // Pattern untuk Target columns
                    $targetPatterns = [
                        '/^TARGET[_\s]*' . $monthName . '$/i',
                        '/^' . $monthName . '[_\s]*TARGET$/i',
                        '/^BUDGET[_\s]*' . $monthName . '$/i',
                        '/^' . $monthName . '[_\s]*BUDGET$/i',
                        '/^PLAN[_\s]*' . $monthName . '$/i',
                        '/^' . $monthName . '[_\s]*PLAN$/i'
                    ];

                    // Check Real patterns
                    foreach ($realPatterns as $pattern) {
                        if (preg_match($pattern, $normalizedHeader)) {
                            $realColumn = $header;
                            break 2;
                        }
                    }

                    // Check Target patterns
                    foreach ($targetPatterns as $pattern) {
                        if (preg_match($pattern, $normalizedHeader)) {
                            $targetColumn = $header;
                            break 2;
                        }
                    }
                }
            }

            // Add pair even if only one column found (with warning)
            if ($realColumn !== null || $targetColumn !== null) {
                $monthlyPairs[] = [
                    'month' => $monthNum,
                    'month_name' => $monthNames[0],
                    'real_column' => $realColumn,
                    'target_column' => $targetColumn
                ];

                // WARNING: Log if only one column found
                if ($realColumn === null) {
                    $this->warningDetails[] = "⚠️ Bulan {$monthNames[0]}: Hanya ditemukan kolom Target, kolom Real tidak ada";
                }
                if ($targetColumn === null) {
                    $this->warningDetails[] = "⚠️ Bulan {$monthNames[0]}: Hanya ditemukan kolom Real, kolom Target tidak ada";
                }
            }
        }

        // Sort by month number
        usort($monthlyPairs, function($a, $b) {
            return $a['month'] - $b['month'];
        });

        Log::info('📅 Monthly column pairs detected', [
            'year' => $this->year,
            'pairs_found' => count($monthlyPairs),
            'pairs' => array_map(function($pair) {
                $real = $pair['real_column'] ?? 'N/A';
                $target = $pair['target_column'] ?? 'N/A';
                return $pair['month_name'] . ': Real=' . $real . ', Target=' . $target;
            }, $monthlyPairs)
        ]);

        return $monthlyPairs;
    }

    /**
     * Validate required columns ada di Excel
     */
    private function validateRequiredColumns($columnMap)
    {
        $requiredColumns = ['nama_am', 'standard_name'];
        $missingColumns = [];

        foreach ($requiredColumns as $required) {
            if (!isset($columnMap[$required])) {
                $missingColumns[] = $required;
            }
        }

        if (!empty($missingColumns)) {
            $error = "❌ Kolom wajib tidak ditemukan: " . implode(', ', $missingColumns);
            $this->errorDetails[] = $error;
            $this->errorCount++;
            throw new \Exception($error);
        }

        // Check monthly pairs dengan warning yang lebih informatif
        if (empty($this->monthlyPairs)) {
            $warning = "⚠️ Tidak ditemukan pasangan kolom Real-Target bulanan yang valid";
            $this->warningDetails[] = $warning;
            $this->warningDetails[] = sprintf("Kolom yang terdeteksi: %s", implode(', ', $this->detectedColumns));
            $this->warningDetails[] = "Format yang diharapkan: Real_Jan + Target_Jan, Real_February + Target_February, dll.";
            throw new \Exception('Tidak ada pasangan Real-Target bulanan ditemukan. Periksa format kolom bulan dalam file Excel.');
        }

        // INFO: Log successful validation
        $this->successDetails[] = sprintf("✅ Validasi berhasil: %d kolom wajib dan %d pasangan bulan ditemukan",
            count($requiredColumns), count($this->monthlyPairs));
    }

    /**
     * Process chunk dengan better transaction handling dan error recovery
     */
    private function processChunk($chunk, $chunkIndex, $columnMap)
    {
        DB::beginTransaction();
        try {
            foreach ($chunk as $rowIndex => $row) {
                $actualRowIndex = $chunkIndex * $this->chunkSize + $rowIndex + 2; // +2 karena header dan 0-based
                $this->processedRows++;

                if ($this->isEmptyRow($row)) {
                    $this->skippedCount++;
                    continue;
                }

                $this->processRow($row, $columnMap, $actualRowIndex);
            }

            DB::commit();

            // Memory cleanup setiap chunk
            if ($chunkIndex % 5 === 0) {
                gc_collect_cycles();
                Log::info("Memory cleanup after chunk {$chunkIndex}, processed rows: {$this->processedRows}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->errorCount++;
            $errorMsg = "❌ Error chunk {$chunkIndex}: " . $e->getMessage();
            $this->errorDetails[] = $errorMsg;
            Log::error($errorMsg, ['exception' => $e]);
        }
    }

    /**
     * Process individual row dengan comprehensive validation
     */
    private function processRow($row, $columnMap, $rowNumber)
    {
        try {
            // Extract dan validate data
            $rowData = $this->extractRowData($row, $columnMap, $rowNumber);

            if (!$rowData) {
                return; // Skip jika data tidak valid
            }

            // Find entities dengan detailed error reporting
            $accountManager = $this->findAccountManager($rowData['am_name'], $rowData['nik'], $rowNumber);
            if (!$accountManager) return;

            $corporateCustomer = $this->findCorporateCustomer($rowData['cc_name'], $rowData['nipnas'], $rowNumber);
            if (!$corporateCustomer) return;

            $divisi = $this->findAndValidateDivisi($rowData['divisi_name'], $accountManager, $rowNumber);
            if (!$divisi) return;

            // Process monthly revenue dengan flexible pairs dan conflict resolution
            $processedMonths = $this->processMonthlyRevenue($row, $accountManager->id, $divisi->id, $corporateCustomer->id, $rowNumber);

            if ($processedMonths > 0) {
                $this->successDetails[] = "✅ Baris {$rowNumber}: Berhasil memproses {$processedMonths} bulan untuk '{$accountManager->nama}' - '{$corporateCustomer->nama}'";
            } else {
                $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Tidak ada data revenue bulanan yang diproses";
            }

        } catch (\Exception $e) {
            $this->errorCount++;
            $errorMsg = "❌ Baris {$rowNumber}: " . $e->getMessage();
            $this->errorDetails[] = $errorMsg;
            Log::error($errorMsg, ['exception' => $e]);
        }
    }

    /**
     * 🔧 FIXED: processMonthlyRevenue() - Support Zero & Negative Values
     */
    private function processMonthlyRevenue($row, $accountManagerId, $divisiId, $corporateCustomerId, $rowNumber)
    {
        $monthlyDataFound = 0;
        $processedMonths = 0;

        foreach ($this->monthlyPairs as $monthPair) {
            $month = $monthPair['month'];
            $realColumn = $monthPair['real_column'];
            $targetColumn = $monthPair['target_column'];

            // Safe array access for Excel data
            $realRevenue = null;
            $targetRevenue = null;

            if ($realColumn && isset($row[$realColumn])) {
                $realRevenue = $this->parseNumericValue($row[$realColumn]);
            }

            if ($targetColumn && isset($row[$targetColumn])) {
                $targetRevenue = $this->parseNumericValue($row[$targetColumn]);
            }

            // 🔧 CRITICAL FIX: Don't skip if values are 0, only skip if both are truly null/empty
            if ($realRevenue === null && $targetRevenue === null) {
                continue; // Skip only if both are null
            }

            // 🔧 ENHANCEMENT: Convert null to 0 for database storage
            if ($realRevenue === null) {
                $realRevenue = 0;
            }
            if ($targetRevenue === null) {
                $targetRevenue = 0;
            }

            $monthlyDataFound++;
            $bulan = sprintf('%s-%02d-01', $this->year, $month);

            try {
                // Safe cache key generation
                $cacheKey = sprintf('%s_%s_%s_%s',
                    (string)$accountManagerId,
                    (string)$corporateCustomerId,
                    (string)$divisiId,
                    (string)$bulan
                );

                $existingData = isset($this->existingDataCache[$cacheKey]) ? $this->existingDataCache[$cacheKey] : null;

                if ($existingData) {
                    $conflictResult = $this->handleExistingDataConflict(
                        $existingData,
                        $targetRevenue,
                        $realRevenue,
                        $monthPair['month_name'],
                        $rowNumber
                    );

                    if ($conflictResult['action'] === 'updated') {
                        $this->updatedCount++;
                        $processedMonths++;
                    } elseif ($conflictResult['action'] === 'skipped') {
                        $this->skippedCount++;
                    } elseif ($conflictResult['action'] === 'duplicate') {
                        $this->duplicateCount++;
                    }

                } else {
                    // Create new revenue record
                    $newRevenue = Revenue::create([
                        'account_manager_id' => $accountManagerId,
                        'corporate_customer_id' => $corporateCustomerId,
                        'divisi_id' => $divisiId,
                        'target_revenue' => $targetRevenue,
                        'real_revenue' => $realRevenue,
                        'bulan' => $bulan,
                    ]);

                    // Safe cache update
                    $this->existingDataCache[$cacheKey] = [
                        'id' => $newRevenue->id,
                        'target_revenue' => $targetRevenue,
                        'real_revenue' => $realRevenue,
                        'bulan' => $bulan,
                        'account_manager' => '',
                        'corporate_customer' => '',
                        'divisi' => '',
                        'created_at' => $newRevenue->created_at
                    ];

                    $this->importedCount++;
                    $processedMonths++;

                    // 🔧 ENHANCED: More informative success messages
                    $targetFormatted = $targetRevenue === 0 ? '0' : number_format($targetRevenue);
                    $realFormatted = $realRevenue === 0 ? '0' : number_format($realRevenue);

                    $message = "✅ Baris {$rowNumber}, {$monthPair['month_name']}: Data baru dibuat - Target: {$targetFormatted}, Real: {$realFormatted}";

                    if ($targetRevenue < 0 || $realRevenue < 0) {
                        $message .= " (Nilai negatif disimpan)";
                    }
                    if ($targetRevenue === 0 || $realRevenue === 0) {
                        $message .= " (Nilai zero disimpan)";
                    }

                    $this->successDetails[] = $message;
                }

            } catch (\Exception $e) {
                $this->errorCount++;
                $this->errorDetails[] = "❌ Baris {$rowNumber}, Bulan {$monthPair['month_name']}: Gagal menyimpan revenue - " . $e->getMessage();
                Log::error("Revenue save error", [
                    'row' => $rowNumber,
                    'month' => $monthPair['month_name'],
                    'error' => $e->getMessage(),
                    'account_manager_id' => $accountManagerId,
                    'corporate_customer_id' => $corporateCustomerId,
                    'divisi_id' => $divisiId,
                    'target' => $targetRevenue,
                    'real' => $realRevenue
                ]);
                continue;
            }
        }

        if ($monthlyDataFound === 0) {
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Tidak ada data revenue bulanan ditemukan";
        }

        return $processedMonths;
    }

    /**
     * 🔧 NEW: Get import summary for controller
     */
    public function getImportSummary()
    {
        $totalRows = $this->processedRows;
        $successRows = $this->importedCount + $this->updatedCount;
        $failedRows = $this->errorCount;
        $successPercentage = $totalRows > 0 ? round(($successRows / $totalRows) * 100, 2) : 0;

        return [
            'total_rows' => $totalRows,
            'success_rows' => $successRows,
            'failed_rows' => $failedRows,
            'success_percentage' => $successPercentage,
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'duplicates' => $this->duplicateCount,
            'conflicts' => $this->conflictCount,
            'errors' => $this->errorCount,
            'skipped' => $this->skippedCount,
            'monthly_pairs_found' => count($this->monthlyPairs),
            'detected_columns' => $this->detectedColumns,
            'error_details' => $this->errorDetails,
            'warning_details' => $this->warningDetails,
            'success_details' => $this->successDetails,
            'conflict_details' => $this->conflictDetails
        ];
    }

    /**
     * ✅ EXISTING: Validation rules (kept as is)
     */
    public function rules(): array
    {
        return [
            // Basic validation - will be handled by custom logic
        ];
    }
}