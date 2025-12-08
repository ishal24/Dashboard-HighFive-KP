<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GoogleSheetService
{
    /**
     * Required columns in the spreadsheet
     */
    const REQUIRED_COLUMNS = [
        'CUSTOMER_NAME',
        'WITEL',
        'AM',
        'PRODUCT',
        'NILAI',
        'Progress',
        'Result',
        'ID LOP MyTens',
        '% Progress',
        '% Results',
    ];

    /**
     * Mapping % Progress labels to percentage values
     */
    const PROGRESS_MAPPING = [
        '1. Visit' => 25,
        '2. Input MyTens' => 50,
        '3. Presentasi Layanan' => 75,
        '4. Submit SPH' => 100,
    ];

    /**
     * Mapping % Results labels to percentage values
     */
    const RESULT_MAPPING = [
        '1. Lose' => 0,
        '2. Prospect' => 0,
        '3. Negotiation' => 50,
        '4. Win' => 100,
    ];

    /**
     * Extract Spreadsheet ID from Google Sheets URL
     * Supports both regular and published CSV formats
     */
    public function extractSpreadsheetId($url)
    {
        // Pattern 1: Regular spreadsheet URL
        // Example: https://docs.google.com/spreadsheets/d/1abc.../edit
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 2: Published spreadsheet URL (CSV export)
        // Example: https://docs.google.com/spreadsheets/d/e/2PACX-1vQeioo.../pub?...
        if (preg_match('/\/d\/e\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return 'published_' . $matches[1];
        }

        throw new \Exception('Invalid Google Sheets URL format. Please use published CSV link.');
    }

    /**
     * Fetch data from Google Sheets (Published CSV only)
     *
     * @param string $spreadsheetUrl
     * @param string $range (optional, not used for CSV)
     * @return array
     */
    public function fetchSpreadsheetData($spreadsheetUrl, $range = 'Sheet1')
    {
        try {
            $spreadsheetId = $this->extractSpreadsheetId($spreadsheetUrl);

            // Fetch fresh data from Google Sheets every time (no caching)
            return $this->fetchPublishedSheet($spreadsheetUrl);

        } catch (\Exception $e) {
            throw new \Exception('Gagal mengambil data dari Google Sheets: ' . $e->getMessage());
        }
    }

    /**
     * Fetch data from published Google Sheet (CSV format)
     *
     * @param string $publishedUrl
     * @return array
     */
    private function fetchPublishedSheet($publishedUrl)
    {
        // Ensure URL has output=csv parameter
        if (strpos($publishedUrl, 'output=csv') === false) {
            // Try to add it
            $publishedUrl .= (strpos($publishedUrl, '?') !== false ? '&' : '?') . 'output=csv';
        }

        // Fetch CSV data using file_get_contents
        $csvData = @file_get_contents($publishedUrl);

        if ($csvData === false) {
            throw new \Exception('Tidak dapat mengakses published spreadsheet. Pastikan: 1) Spreadsheet sudah dipublikasikan sebagai CSV, 2) Link berakhiran &output=csv, 3) Sharing diset "Anyone with the link"');
        }

        // Parse CSV
        $lines = explode("\n", $csvData);
        $values = [];

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $values[] = str_getcsv($line);
            }
        }

        // Remove empty rows
        $values = array_filter($values, function($row) {
            return !empty(array_filter($row, function($cell) {
                return trim($cell) !== '';
            }));
        });

        if (empty($values)) {
            throw new \Exception('Spreadsheet kosong atau tidak ada data');
        }

        return $this->parseSpreadsheetData(array_values($values));
    }

    /**
     * Parse raw spreadsheet data into structured array
     *
     * @param array $values Raw values from CSV
     * @return array Parsed data with column mapping
     */
    private function parseSpreadsheetData($values)
    {
        // First row is header
        $headers = array_shift($values);

        // Find column indexes by name (case-insensitive)
        $columnIndexes = $this->findColumnIndexes($headers);

        // Validate all required columns exist
        $this->validateRequiredColumns($columnIndexes);

        $parsedData = [];

        foreach ($values as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $parsedData[] = [
                'customer_name' => $row[$columnIndexes['CUSTOMER_NAME']] ?? '',
                'witel' => $row[$columnIndexes['WITEL']] ?? '',
                'am' => $row[$columnIndexes['AM']] ?? '',
                'product' => $row[$columnIndexes['PRODUCT']] ?? '',
                'nilai' => $row[$columnIndexes['NILAI']] ?? 0,
                'progress' => $row[$columnIndexes['Progress']] ?? '',
                'result' => $row[$columnIndexes['Result']] ?? '',
                'id_lop_mytens' => $row[$columnIndexes['ID LOP MyTens']] ?? '',
                'progress_label' => $row[$columnIndexes['% Progress']] ?? '',
                'result_label' => $row[$columnIndexes['% Results']] ?? '',

                // Convert labels OR percentage strings to numeric percentage
                'progress_percentage' => $this->convertProgressToPercentage($row[$columnIndexes['% Progress']] ?? ''),
                'result_percentage' => $this->convertResultToPercentage($row[$columnIndexes['% Results']] ?? ''),
            ];
        }

        return $parsedData;
    }

    /**
     * Find column indexes by column names (case-insensitive)
     */
    private function findColumnIndexes($headers)
    {
        $indexes = [];

        foreach (self::REQUIRED_COLUMNS as $requiredColumn) {
            $found = false;

            foreach ($headers as $index => $header) {
                // Trim and case-insensitive comparison
                if (strcasecmp(trim($header), trim($requiredColumn)) === 0) {
                    $indexes[$requiredColumn] = $index;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $indexes[$requiredColumn] = null;
            }
        }

        return $indexes;
    }

    /**
     * Validate if all required columns exist
     */
    private function validateRequiredColumns($columnIndexes)
    {
        $missingColumns = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (!isset($columnIndexes[$column]) || $columnIndexes[$column] === null) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            throw new \Exception('Kolom tidak ditemukan di spreadsheet: ' . implode(', ', $missingColumns) . '. Pastikan spreadsheet memiliki semua kolom yang diperlukan.');
        }
    }

    /**
     * Convert progress label OR percentage string to percentage value
     *
     * Handles both formats:
     * - Label: "1. Visit" -> 25
     * - Percentage string: "25%" -> 25
     * - Numeric string: "25" -> 25
     * - Empty: "" -> 0
     */
    public function convertProgressToPercentage($label)
    {
        $label = trim($label);

        // If empty, return 0
        if (empty($label)) {
            return 0;
        }

        // Check if it's already a percentage string or number (e.g., "25%", "50", "75%")
        if (preg_match('/^(\d+(?:\.\d+)?)\s*%?$/', $label, $matches)) {
            return (float) $matches[1];
        }

        // Otherwise, try to match label
        foreach (self::PROGRESS_MAPPING as $key => $value) {
            if (stripos($label, $key) !== false) {
                return $value;
            }
        }

        return 0; // Default if no match
    }

    /**
     * Convert result label OR percentage string to percentage value
     *
     * Handles both formats:
     * - Label: "1. Lose" -> 0
     * - Percentage string: "0%" -> 0
     * - Numeric string: "50" -> 50
     * - Empty: "" -> 0
     */
    public function convertResultToPercentage($label)
    {
        $label = trim($label);

        // If empty, return 0
        if (empty($label)) {
            return 0;
        }

        // Check if it's already a percentage string or number (e.g., "0%", "50", "100%")
        if (preg_match('/^(\d+(?:\.\d+)?)\s*%?$/', $label, $matches)) {
            return (float) $matches[1];
        }

        // Otherwise, try to match label
        foreach (self::RESULT_MAPPING as $key => $value) {
            if (stripos($label, $key) !== false) {
                return $value;
            }
        }

        return 0; // Default if no match
    }

    /**
     * Clear cache for specific spreadsheet
     */
    public function clearCache($spreadsheetUrl)
    {
        $cacheKey = "spreadsheet_data_" . md5($spreadsheetUrl);
        Cache::forget($cacheKey);
    }
}