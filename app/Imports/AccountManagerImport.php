<?php

namespace App\Imports;

use App\Models\AccountManager;
use App\Models\Witel;
use App\Models\Divisi;
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

class AccountManagerImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures, RemembersRowNumber;

    private $importedCount = 0;
    private $updatedCount = 0;
    private $duplicateCount = 0;
    private $errorCount = 0;
    private $skippedCount = 0;

    // ✅ IMPROVED: Detailed tracking seperti RevenueImport
    private $errorDetails = [];
    private $warningDetails = [];
    private $successDetails = [];
    private $processedRows = 0;

    // ✅ IMPROVED: Master data caching dengan normalisasi
    private $witels = [];
    private $regionals = [];
    private $divisis = [];
    private $existingAccountManagers = [];

    private $chunkSize = 100;

    // ✅ EXPANDED: Alternative column names yang konsisten
    private $alternativeColumns = [
        'nik' => [
            'nik', 'NIK', 'Nik', 'employee_id', 'emp_id', 'id_karyawan', 'Employee ID', 'ID Karyawan'
        ],
        'nama_am' => [
            'nama am', 'NAMA AM', 'nama_am', 'Nama AM', 'account_manager', 'Account Manager',
            'ACCOUNT_MANAGER', 'AM Name', 'AM_Name', 'namaAM', 'Name', 'Nama Account Manager'
        ],
        'witel_ho' => [
            'witel ho', 'WITEL HO', 'witel_ho', 'Witel HO', 'witel', 'WITEL', 'Witel', 'Witel_HO'
        ],
        'regional' => [
            'regional', 'REGIONAL', 'Regional', 'treg', 'TREG', 'Treg', 'TREG Regional'
        ],
        'divisi' => [
            'divisi', 'DIVISI', 'Divisi', 'division', 'Division', 'DIVISION', 'Nama Divisi'
        ]
    ];

    public function __construct()
    {
        $this->loadMasterData();

        // ✅ Set memory dan timeout untuk file besar
        ini_set('memory_limit', '1024M');
        set_time_limit(300); // 5 minutes
    }

    /**
     * ✅ IMPROVED: Load master data dengan normalisasi string
     */
    private function loadMasterData()
    {
        try {
            // ✅ FIXED: Load witel dengan nama tabel yang benar
            $witels = Witel::all();
            foreach ($witels as $witel) {
                $this->witels['nama:' . $this->normalizeString($witel->nama)] = $witel;
                $this->witels['id:' . $witel->id] = $witel;
            }

            // ✅ FIXED: Load regional dengan nama tabel yang benar
            $regionals = Regional::all();
            foreach ($regionals as $regional) {
                $this->regionals['nama:' . $this->normalizeString($regional->nama)] = $regional;
                $this->regionals['id:' . $regional->id] = $regional;
            }

            // ✅ FIXED: Load divisi dengan nama tabel yang benar (divisi, bukan divisis)
            $divisis = Divisi::all();
            foreach ($divisis as $divisi) {
                $this->divisis['nama:' . $this->normalizeString($divisi->nama)] = $divisi;
                $this->divisis['id:' . $divisi->id] = $divisi;
            }

            // Load existing account managers
            $existingAMs = AccountManager::with(['divisis'])->get();
            foreach ($existingAMs as $am) {
                $this->existingAccountManagers['nik:' . trim($am->nik)] = $am;
                $this->existingAccountManagers['nama:' . $this->normalizeString($am->nama)] = $am;
            }

            Log::info('✅ Master data loaded for AccountManager import', [
                'witels' => count($witels),
                'regionals' => count($regionals),
                'divisis' => count($divisis),
                'existing_ams' => count($existingAMs)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error loading master data: ' . $e->getMessage());
            throw new \Exception('Gagal memuat master data: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Normalize string untuk konsistensi
     */
    private function normalizeString($string)
    {
        return strtolower(trim($string));
    }

    /**
     * ✅ IMPROVED: Collection processing dengan chunking dan better error handling
     */
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->errorDetails[] = "❌ File Excel kosong atau tidak memiliki data";
            return;
        }

        // ✅ IMPROVED: Column identification dengan validasi
        $firstRow = $rows->first();
        $columnMap = $this->identifyColumns($firstRow);

        // ✅ NEW: Validate required columns
        $this->validateRequiredColumns($columnMap);

        Log::info('📊 Starting AccountManager import', [
            'total_rows' => $rows->count(),
            'columns_found' => array_keys($columnMap)
        ]);

        // ✅ IMPROVED: Group data by NIK first, then process
        $accountManagerData = $this->groupDataByNIK($rows->slice(1), $columnMap);

        // Process grouped data
        $this->processGroupedData($accountManagerData);

        Log::info('✅ AccountManager import completed', [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'duplicates' => $this->duplicateCount,
            'errors' => $this->errorCount,
            'skipped' => $this->skippedCount
        ]);
    }

    /**
     * ✅ NEW: Validate required columns
     */
    private function validateRequiredColumns($columnMap)
    {
        $requiredColumns = ['nik', 'nama_am'];
        $missingColumns = [];

        foreach ($requiredColumns as $required) {
            if (!isset($columnMap[$required])) {
                $missingColumns[] = $required;
            }
        }

        if (!empty($missingColumns)) {
            $error = "❌ Kolom wajib tidak ditemukan: " . implode(', ', $missingColumns);
            $this->errorDetails[] = $error;
            throw new \Exception($error);
        }
    }

    /**
     * ✅ IMPROVED: Group data by NIK untuk handle multiple divisi per AM
     */
    private function groupDataByNIK($rows, $columnMap)
    {
        $accountManagerData = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 for Excel row number
            $this->processedRows++;

            try {
                if ($this->isEmptyRow($row)) {
                    $this->skippedCount++;
                    continue;
                }

                // ✅ Extract and validate row data
                $rowData = $this->extractRowData($row, $columnMap, $rowNumber);
                if (!$rowData) {
                    continue; // Skip jika data tidak valid
                }

                $nik = $rowData['nik'];

                // ✅ Find related entities
                $witel = $this->findWitel($rowData['witel_name'], $rowNumber);
                $regional = $this->findRegional($rowData['regional_name'], $rowNumber);
                $divisi = $this->findDivisi($rowData['divisi_name'], $rowNumber);

                if (!$witel || !$regional || !$divisi) {
                    continue; // Skip if any required entity not found
                }

                // ✅ Group by NIK
                if (!isset($accountManagerData[$nik])) {
                    $accountManagerData[$nik] = [
                        'nama' => $rowData['nama'],
                        'witel_id' => $witel->id,
                        'regional_id' => $regional->id,
                        'divisi_ids' => [],
                        'row_numbers' => []
                    ];
                }

                // ✅ Add divisi if not already present
                if (!in_array($divisi->id, $accountManagerData[$nik]['divisi_ids'])) {
                    $accountManagerData[$nik]['divisi_ids'][] = $divisi->id;
                    $accountManagerData[$nik]['row_numbers'][] = $rowNumber;
                } else {
                    $this->duplicateCount++;
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Duplikasi divisi '{$divisi->nama}' untuk NIK '{$nik}'";
                }

            } catch (\Exception $e) {
                $this->errorCount++;
                $errorMsg = "❌ Baris {$rowNumber}: " . $e->getMessage();
                $this->errorDetails[] = $errorMsg;
                Log::error($errorMsg, ['exception' => $e]);
            }
        }

        return $accountManagerData;
    }

    /**
     * ✅ FIXED: Extract and validate row data dengan NIK validation 4-10 digit
     */
    private function extractRowData($row, $columnMap, $rowNumber)
    {
        $data = [
            'nik' => $this->extractValue($row, $columnMap, 'nik'),
            'nama' => $this->extractValue($row, $columnMap, 'nama_am'),
            'witel_name' => $this->extractValue($row, $columnMap, 'witel_ho'),
            'regional_name' => $this->extractValue($row, $columnMap, 'regional'),
            'divisi_name' => $this->extractValue($row, $columnMap, 'divisi')
        ];

        // ✅ Validate required fields
        if (empty($data['nik'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: NIK kosong";
            return null;
        }

        if (empty($data['nama'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Account Manager kosong";
            return null;
        }

        // ✅ FIXED: Validate NIK format (4-10 digits) - konsisten dengan controller
        if (!preg_match('/^\d{4,10}$/', $data['nik'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Format NIK tidak valid: '{$data['nik']}' (harus 4-10 digit angka)";
            return null;
        }

        return $data;
    }

    /**
     * ✅ IMPROVED: Process grouped data dengan transaction
     */
    private function processGroupedData($accountManagerData)
    {
        foreach ($accountManagerData as $nik => $data) {
            DB::beginTransaction();
            try {
                // ✅ Check if AM already exists
                $existingAM = $this->findExistingAccountManager($nik, $data['nama']);

                if ($existingAM) {
                    // ✅ Update existing Account Manager
                    $existingAM->update([
                        'nama' => $data['nama'],
                        'witel_id' => $data['witel_id'],
                        'regional_id' => $data['regional_id'],
                    ]);

                    // ✅ Sync divisi (replace all existing relations)
                    $existingAM->divisis()->sync($data['divisi_ids']);

                    $this->updatedCount++;
                    $this->successDetails[] = "✅ NIK {$nik}: Account Manager '{$data['nama']}' diperbarui dengan " . count($data['divisi_ids']) . " divisi";

                } else {
                    // ✅ Create new Account Manager
                    $newAM = AccountManager::create([
                        'nama' => $data['nama'],
                        'nik' => $nik,
                        'witel_id' => $data['witel_id'],
                        'regional_id' => $data['regional_id'],
                    ]);

                    // ✅ Attach divisi
                    $newAM->divisis()->attach($data['divisi_ids']);

                    $this->importedCount++;
                    $this->successDetails[] = "✅ NIK {$nik}: Account Manager baru '{$data['nama']}' dibuat dengan " . count($data['divisi_ids']) . " divisi";
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                $this->errorCount++;
                $errorMsg = "❌ NIK {$nik}: Gagal menyimpan - " . $e->getMessage();
                $this->errorDetails[] = $errorMsg;
                Log::error($errorMsg, ['exception' => $e]);
            }
        }
    }

    /**
     * ✅ IMPROVED: Find existing Account Manager dengan fuzzy matching
     */
    private function findExistingAccountManager($nik, $nama)
    {
        // Try by NIK first
        $nikKey = 'nik:' . trim($nik);
        $existingAM = $this->existingAccountManagers[$nikKey] ?? null;

        if ($existingAM) {
            return $existingAM;
        }

        // Try by name
        $namaKey = 'nama:' . $this->normalizeString($nama);
        $existingAM = $this->existingAccountManagers[$namaKey] ?? null;

        if ($existingAM) {
            $this->warningDetails[] = "⚠️ NIK {$nik}: Account Manager ditemukan berdasarkan nama: '{$nama}'";
            return $existingAM;
        }

        // ✅ Fallback to database
        $existingAM = AccountManager::where('nik', $nik)
            ->orWhere('nama', 'like', "%{$nama}%")
            ->first();

        if ($existingAM) {
            // Add to cache
            $this->existingAccountManagers['nik:' . trim($existingAM->nik)] = $existingAM;
            $this->existingAccountManagers['nama:' . $this->normalizeString($existingAM->nama)] = $existingAM;
        }

        return $existingAM;
    }

    /**
     * ✅ IMPROVED: Find Witel dengan error reporting dan fuzzy matching
     */
    private function findWitel($witelName, $rowNumber)
    {
        if (empty($witelName)) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Witel kosong";
            return null;
        }

        $nameKey = 'nama:' . $this->normalizeString($witelName);
        $witel = $this->witels[$nameKey] ?? null;

        if ($witel) {
            return $witel;
        }

        // ✅ Enhanced fuzzy search
        foreach ($this->witels as $key => $storedWitel) {
            if (strpos($key, 'nama:') === 0) {
                $storedName = substr($key, 5); // Remove 'nama:' prefix
                // Check for partial match in both directions
                if (strpos($storedName, $this->normalizeString($witelName)) !== false ||
                    strpos($this->normalizeString($witelName), $storedName) !== false) {
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Witel ditemukan dengan fuzzy search: '{$witelName}' → '{$storedWitel->nama}'";
                    return $storedWitel;
                }
            }
        }

        // ✅ Database fallback dengan caching
        $witel = Witel::where('nama', 'like', "%{$witelName}%")->first();
        if ($witel) {
            $this->witels['nama:' . $this->normalizeString($witel->nama)] = $witel;
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Witel ditemukan di database: '{$witelName}' → '{$witel->nama}'";
            return $witel;
        }

        $this->errorDetails[] = "❌ Baris {$rowNumber}: Witel tidak ditemukan: '{$witelName}'";
        return null;
    }

    /**
     * ✅ IMPROVED: Find Regional dengan error reporting dan fuzzy matching
     */
    private function findRegional($regionalName, $rowNumber)
    {
        if (empty($regionalName)) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Regional kosong";
            return null;
        }

        $nameKey = 'nama:' . $this->normalizeString($regionalName);
        $regional = $this->regionals[$nameKey] ?? null;

        if ($regional) {
            return $regional;
        }

        // ✅ Enhanced fuzzy search
        foreach ($this->regionals as $key => $storedRegional) {
            if (strpos($key, 'nama:') === 0) {
                $storedName = substr($key, 5);
                if (strpos($storedName, $this->normalizeString($regionalName)) !== false ||
                    strpos($this->normalizeString($regionalName), $storedName) !== false) {
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Regional ditemukan dengan fuzzy search: '{$regionalName}' → '{$storedRegional->nama}'";
                    return $storedRegional;
                }
            }
        }

        // ✅ Database fallback dengan caching
        $regional = Regional::where('nama', 'like', "%{$regionalName}%")->first();
        if ($regional) {
            $this->regionals['nama:' . $this->normalizeString($regional->nama)] = $regional;
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Regional ditemukan di database: '{$regionalName}' → '{$regional->nama}'";
            return $regional;
        }

        $this->errorDetails[] = "❌ Baris {$rowNumber}: Regional tidak ditemukan: '{$regionalName}'";
        return null;
    }

    /**
     * ✅ IMPROVED: Find Divisi dengan error reporting dan fuzzy matching
     */
    private function findDivisi($divisiName, $rowNumber)
    {
        if (empty($divisiName)) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Divisi kosong";
            return null;
        }

        $nameKey = 'nama:' . $this->normalizeString($divisiName);
        $divisi = $this->divisis[$nameKey] ?? null;

        if ($divisi) {
            return $divisi;
        }

        // ✅ Enhanced fuzzy search
        foreach ($this->divisis as $key => $storedDivisi) {
            if (strpos($key, 'nama:') === 0) {
                $storedName = substr($key, 5);
                if (strpos($storedName, $this->normalizeString($divisiName)) !== false ||
                    strpos($this->normalizeString($divisiName), $storedName) !== false) {
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Divisi ditemukan dengan fuzzy search: '{$divisiName}' → '{$storedDivisi->nama}'";
                    return $storedDivisi;
                }
            }
        }

        // ✅ Database fallback dengan caching
        $divisi = Divisi::where('nama', 'like', "%{$divisiName}%")->first();
        if ($divisi) {
            $this->divisis['nama:' . $this->normalizeString($divisi->nama)] = $divisi;
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Divisi ditemukan di database: '{$divisiName}' → '{$divisi->nama}'";
            return $divisi;
        }

        $this->errorDetails[] = "❌ Baris {$rowNumber}: Divisi tidak ditemukan: '{$divisiName}'";
        return null;
    }

    /**
     * ✅ IMPROVED: Column identification dengan flexible matching
     */
    private function identifyColumns($firstRow)
    {
        $map = [];
        $excelColumns = array_keys($firstRow->toArray());

        foreach ($this->alternativeColumns as $standardKey => $alternatives) {
            foreach ($alternatives as $altName) {
                $foundColumn = collect($excelColumns)->first(function ($col) use ($altName) {
                    return strtolower(trim($col)) === strtolower(trim($altName));
                });

                if ($foundColumn) {
                    $map[$standardKey] = $foundColumn;
                    break;
                }
            }
        }

        Log::info('📋 AccountManager column mapping', $map);
        return $map;
    }

    /**
     * Extract value dari row
     */
    private function extractValue($row, $columnMap, $field)
    {
        $key = $columnMap[$field] ?? null;
        if ($key && isset($row[$key])) {
            return trim((string)$row[$key]);
        }
        return null;
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow($row)
    {
        foreach ($row as $value) {
            if (!empty($value) && $value !== null && trim($value) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Rules validasi
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * ✅ IMPROVED: Get comprehensive import results
     */
    public function getImportResults()
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'duplicates' => $this->duplicateCount,
            'errors' => $this->errorCount,
            'skipped' => $this->skippedCount,
            'processed' => $this->processedRows,
            'error_details' => $this->errorDetails,
            'warning_details' => $this->warningDetails,
            'success_details' => $this->successDetails,
            'summary' => [
                'total_processed' => $this->processedRows,
                'success_rate' => $this->processedRows > 0 ? round(($this->importedCount + $this->updatedCount) / $this->processedRows * 100, 2) : 0,
                'error_rate' => $this->processedRows > 0 ? round($this->errorCount / $this->processedRows * 100, 2) : 0
            ]
        ];
    }
}