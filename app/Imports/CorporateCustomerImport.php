<?php

namespace App\Imports;

use App\Models\CorporateCustomer;
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

class CorporateCustomerImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures, RemembersRowNumber;

    private $importedCount = 0;
    private $updatedCount = 0;
    private $duplicateCount = 0;
    private $errorCount = 0;
    private $skippedCount = 0;

    // ✅ IMPROVED: Detailed tracking seperti pattern lainnya
    private $errorDetails = [];
    private $warningDetails = [];
    private $successDetails = [];
    private $processedRows = 0;

    // ✅ IMPROVED: Master data caching dengan normalisasi
    private $existingCustomers = [];
    private $chunkSize = 100;

    // ✅ EXPANDED: Alternative column names dengan lebih banyak variasi
    private $alternativeColumns = [
        'standard_name' => [
            'standard name', 'STANDARD NAME', 'standard_name', 'Standard Name',
            'nama customer', 'NAMA CUSTOMER', 'nama_customer', 'Nama Customer',
            'corporate customer', 'CORPORATE CUSTOMER', 'corporate_customer',
            'customer_name', 'Customer Name', 'CUSTOMER NAME', 'nama_corporate',
            'nama', 'NAMA', 'Nama', 'name', 'Name', 'NAME', 'company_name',
            'Company Name', 'COMPANY NAME', 'company', 'Company', 'COMPANY'
        ],
        'nipnas' => [
            'nipnas', 'NIPNAS', 'Nipnas', 'customer_id', 'CUSTOMER_ID', 'customer id',
            'cust_id', 'CUST_ID', 'id_customer', 'ID_CUSTOMER', 'id customer',
            'customer code', 'CUSTOMER CODE', 'customer_code', 'code', 'Code', 'CODE'
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
            // Load existing Corporate Customers dengan caching yang efisien
            $existingCustomers = CorporateCustomer::all();
            foreach ($existingCustomers as $customer) {
                // Cache berdasarkan nama (normalized)
                $this->existingCustomers['nama:' . $this->normalizeString($customer->nama)] = $customer;

                // Cache berdasarkan NIPNAS
                if (!empty($customer->nipnas)) {
                    $this->existingCustomers['nipnas:' . trim($customer->nipnas)] = $customer;
                }

                // Cache berdasarkan ID untuk referensi cepat
                $this->existingCustomers['id:' . $customer->id] = $customer;
            }

            Log::info('✅ Master data loaded for CorporateCustomer import', [
                'existing_customers' => count($existingCustomers)
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
        // Remove extra spaces, convert to lowercase, remove special chars
        $normalized = strtolower(trim($string));
        $normalized = preg_replace('/\s+/', ' ', $normalized); // Multiple spaces to single space
        return $normalized;
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

        Log::info('📊 Starting CorporateCustomer import', [
            'total_rows' => $rows->count(),
            'columns_found' => array_keys($columnMap)
        ]);

        // Process data dengan chunking untuk performance
        $rows->slice(1)->chunk($this->chunkSize)->each(function ($chunk, $chunkIndex) use ($columnMap) {
            $this->processChunk($chunk, $chunkIndex, $columnMap);
        });

        Log::info('✅ CorporateCustomer import completed', [
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
        $requiredColumns = ['standard_name', 'nipnas'];
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
     * ✅ IMPROVED: Process chunk dengan comprehensive transaction handling
     */
    private function processChunk($chunk, $chunkIndex, $columnMap)
    {
        DB::beginTransaction();
        try {
            foreach ($chunk as $rowIndex => $row) {
                $actualRowIndex = $chunkIndex * $this->chunkSize + $rowIndex + 2; // +2 for Excel row number
                $this->processedRows++;

                if ($this->isEmptyRow($row)) {
                    $this->skippedCount++;
                    continue;
                }

                $this->processRow($row, $columnMap, $actualRowIndex);
            }

            DB::commit();

            // ✅ Memory cleanup setiap chunk
            if ($chunkIndex % 10 === 0) {
                gc_collect_cycles();
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
     * ✅ IMPROVED: Process individual row dengan comprehensive validation
     */
    private function processRow($row, $columnMap, $rowNumber)
    {
        try {
            // ✅ Extract and validate data
            $rowData = $this->extractRowData($row, $columnMap, $rowNumber);

            if (!$rowData) {
                return; // Skip jika data tidak valid
            }

            // ✅ Check if customer already exists dengan advanced matching
            $existingCustomer = $this->findExistingCustomer($rowData['nama'], $rowData['nipnas'], $rowNumber);

            if ($existingCustomer) {
                // ✅ Update existing customer
                $updated = $this->updateExistingCustomer($existingCustomer, $rowData, $rowNumber);

                if ($updated) {
                    $this->updatedCount++;
                    $this->successDetails[] = "✅ Baris {$rowNumber}: Corporate Customer '{$rowData['nama']}' diperbarui (NIPNAS: {$rowData['nipnas']})";
                } else {
                    $this->duplicateCount++;
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Data sama, tidak ada perubahan - '{$rowData['nama']}' (NIPNAS: {$rowData['nipnas']})";
                }

            } else {
                // ✅ Create new customer
                $newCustomer = $this->createNewCustomer($rowData, $rowNumber);

                if ($newCustomer) {
                    $this->importedCount++;
                    $this->successDetails[] = "✅ Baris {$rowNumber}: Corporate Customer baru '{$rowData['nama']}' dibuat (NIPNAS: {$rowData['nipnas']})";
                }
            }

        } catch (\Exception $e) {
            $this->errorCount++;
            $errorMsg = "❌ Baris {$rowNumber}: " . $e->getMessage();
            $this->errorDetails[] = $errorMsg;
            Log::error($errorMsg, ['exception' => $e]);
        }
    }

    /**
     * ✅ ENHANCED: Extract and validate row data dengan NIPNAS validation 3-20 digit
     */
    private function extractRowData($row, $columnMap, $rowNumber)
    {
        $data = [
            'nama' => $this->extractValue($row, $columnMap, 'standard_name'),
            'nipnas' => $this->extractValue($row, $columnMap, 'nipnas')
        ];

        // ✅ Validate required fields
        if (empty($data['nama'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Corporate Customer kosong";
            return null;
        }

        if (empty($data['nipnas'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: NIPNAS kosong";
            return null;
        }

        // ✅ Clean and validate NIPNAS
        $originalNipnas = $data['nipnas'];
        $data['nipnas'] = preg_replace('/[^0-9]/', '', trim((string)$data['nipnas']));

        // Log if NIPNAS was cleaned
        if ($originalNipnas !== $data['nipnas']) {
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}: NIPNAS dibersihkan: '{$originalNipnas}' → '{$data['nipnas']}'";
        }

        // ✅ Validate NIPNAS format (should be numeric)
        if (!is_numeric($data['nipnas'])) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Format NIPNAS tidak valid: '{$data['nipnas']}' (harus berupa angka)";
            return null;
        }

        // ✅ FIXED: Validate NIPNAS range (3-20 digit: 100 to 99999999999999999999)
        $nipnasLength = strlen($data['nipnas']);
        if ($nipnasLength < 3 || $nipnasLength > 20) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Panjang NIPNAS tidak valid: '{$data['nipnas']}' (harus 3-20 digit)";
            return null;
        }

        // ✅ NEW: Additional numeric range validation using bccomp for large numbers
        if (bccomp($data['nipnas'], '100', 0) < 0) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: NIPNAS terlalu kecil: '{$data['nipnas']}' (minimal 100)";
            return null;
        }

        if (bccomp($data['nipnas'], '99999999999999999999', 0) > 0) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: NIPNAS terlalu besar: '{$data['nipnas']}' (maksimal 99999999999999999999)";
            return null;
        }

        // ✅ NEW: Check for leading zeros warning
        if (strlen($data['nipnas']) > 1 && $data['nipnas'][0] === '0') {
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}: NIPNAS dimulai dengan 0: '{$data['nipnas']}' - Pastikan ini benar";
        }

        // ✅ Clean nama
        $data['nama'] = trim($data['nama']);
        if (strlen($data['nama']) > 255) {
            $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Nama terlalu panjang, akan dipotong: '{$data['nama']}'";
            $data['nama'] = substr($data['nama'], 0, 255);
        }

        // ✅ Validate nama length
        if (strlen($data['nama']) < 3) {
            $this->errorDetails[] = "❌ Baris {$rowNumber}: Nama Corporate Customer minimal 3 karakter";
            return null;
        }

        return $data;
    }

    /**
     * ✅ IMPROVED: Find existing customer dengan advanced fuzzy matching
     */
    private function findExistingCustomer($nama, $nipnas, $rowNumber)
    {
        // Try by NIPNAS first (most accurate)
        $nipnasKey = 'nipnas:' . trim($nipnas);
        $existingCustomer = $this->existingCustomers[$nipnasKey] ?? null;

        if ($existingCustomer) {
            // Check jika nama berbeda
            if ($this->normalizeString($existingCustomer->nama) !== $this->normalizeString($nama)) {
                $this->warningDetails[] = "⚠️ Baris {$rowNumber}: NIPNAS {$nipnas} sudah ada dengan nama berbeda - Existing: '{$existingCustomer->nama}', Import: '{$nama}'";
            }
            return $existingCustomer;
        }

        // Try by name (secondary check)
        $namaKey = 'nama:' . $this->normalizeString($nama);
        $existingCustomer = $this->existingCustomers[$namaKey] ?? null;

        if ($existingCustomer) {
            // Check jika NIPNAS berbeda
            if ($existingCustomer->nipnas != $nipnas) {
                $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Nama '{$nama}' sudah ada dengan NIPNAS berbeda - Existing: '{$existingCustomer->nipnas}', Import: '{$nipnas}'";
                // Dalam kasus ini, kita anggap sebagai customer berbeda karena NIPNAS adalah unique identifier
                return null;
            }
            return $existingCustomer;
        }

        // ✅ Fuzzy matching untuk nama yang mirip
        foreach ($this->existingCustomers as $key => $customer) {
            if (strpos($key, 'nama:') === 0) {
                $existingNormalized = substr($key, 5); // Remove 'nama:' prefix
                $similarity = 0;
                similar_text($existingNormalized, $this->normalizeString($nama), $similarity);

                if ($similarity > 85) { // 85% similarity threshold
                    $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Nama mirip ditemukan (similarity: {$similarity}%): '{$nama}' → '{$customer->nama}'";

                    // Only return if NIPNAS also matches
                    if ($customer->nipnas == $nipnas) {
                        return $customer;
                    }
                }
            }
        }

        // ✅ Database fallback dengan LIKE search
        $existingCustomer = CorporateCustomer::where('nipnas', $nipnas)
            ->orWhere(function($query) use ($nama) {
                $query->where('nama', 'like', "%{$nama}%")
                      ->orWhere('nama', 'like', "%" . str_replace(' ', '%', $nama) . "%");
            })
            ->first();

        if ($existingCustomer) {
            // Add to cache untuk subsequent lookups
            $this->existingCustomers['nama:' . $this->normalizeString($existingCustomer->nama)] = $existingCustomer;
            $this->existingCustomers['nipnas:' . trim($existingCustomer->nipnas)] = $existingCustomer;

            if ($existingCustomer->nipnas == $nipnas) {
                $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Customer ditemukan di database dengan NIPNAS: {$nipnas}";
            } else {
                $this->warningDetails[] = "⚠️ Baris {$rowNumber}: Customer ditemukan dengan fuzzy search: '{$nama}' → '{$existingCustomer->nama}'";
            }
        }

        return $existingCustomer;
    }

    /**
     * ✅ NEW: Update existing customer dengan change detection
     */
    private function updateExistingCustomer($existingCustomer, $rowData, $rowNumber)
    {
        $needsUpdate = false;
        $changes = [];

        // Check for changes
        if ($existingCustomer->nama !== $rowData['nama']) {
            $changes[] = "Nama: '{$existingCustomer->nama}' → '{$rowData['nama']}'";
            $needsUpdate = true;
        }

        if ($existingCustomer->nipnas !== $rowData['nipnas']) {
            $changes[] = "NIPNAS: '{$existingCustomer->nipnas}' → '{$rowData['nipnas']}'";
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $existingCustomer->update([
                'nama' => $rowData['nama'],
                'nipnas' => $rowData['nipnas']
            ]);

            // Update cache
            $this->existingCustomers['nama:' . $this->normalizeString($rowData['nama'])] = $existingCustomer;
            $this->existingCustomers['nipnas:' . trim($rowData['nipnas'])] = $existingCustomer;

            $this->warningDetails[] = "ℹ️ Baris {$rowNumber}: Perubahan data - " . implode(', ', $changes);
            return true;
        }

        return false;
    }

    /**
     * ✅ NEW: Create new customer dengan cache update
     */
    private function createNewCustomer($rowData, $rowNumber)
    {
        try {
            $newCustomer = CorporateCustomer::create([
                'nama' => $rowData['nama'],
                'nipnas' => $rowData['nipnas']
            ]);

            // Add to cache untuk subsequent lookups
            $this->existingCustomers['nama:' . $this->normalizeString($newCustomer->nama)] = $newCustomer;
            $this->existingCustomers['nipnas:' . trim($newCustomer->nipnas)] = $newCustomer;
            $this->existingCustomers['id:' . $newCustomer->id] = $newCustomer;

            return $newCustomer;

        } catch (\Exception $e) {
            throw new \Exception("Gagal membuat Corporate Customer baru: " . $e->getMessage());
        }
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
                    break; // Stop at first match
                }
            }
        }

        Log::info('📋 CorporateCustomer column mapping', [
            'found_mappings' => $map,
            'available_columns' => $excelColumns
        ]);

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
     * Rules validasi (kosong karena menggunakan manual validation)
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
                'error_rate' => $this->processedRows > 0 ? round($this->errorCount / $this->processedRows * 100, 2) : 0,
                'duplicate_rate' => $this->processedRows > 0 ? round($this->duplicateCount / $this->processedRows * 100, 2) : 0
            ]
        ];
    }
}