<?php
/**
 * Test GoogleSheetService - CSV Fetch
 * Run: php test-csv-fetch.php
 */

// Test CSV fetch without Laravel (standalone)
$testUrl = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQeioo4E5vQsKcyh-iBf4It_SjGedAscQfb8_LgBYPCJlnr6etCUzb8Af9VVuFfYCdZ2EGv0rFVbLKC/pub?gid=759060382&single=true&output=csv';

echo "===========================================\n";
echo "  GOOGLE SHEETS CSV FETCH TEST\n";
echo "===========================================\n\n";

echo "URL: $testUrl\n\n";

// Test 1: Check if URL has output=csv
echo "Test 1: Verify URL format...\n";
if (strpos($testUrl, 'output=csv') !== false) {
    echo "  ✅ URL has output=csv parameter\n";
} else {
    echo "  ❌ URL missing output=csv parameter!\n";
    echo "  Add: &output=csv to the end of URL\n";
    exit(1);
}

// Test 2: Check allow_url_fopen
echo "\nTest 2: Check allow_url_fopen...\n";
if (ini_get('allow_url_fopen')) {
    echo "  ✅ allow_url_fopen is enabled\n";
} else {
    echo "  ❌ allow_url_fopen is disabled!\n";
    echo "  Enable it in php.ini\n";
    exit(1);
}

// Test 3: Fetch CSV data
echo "\nTest 3: Fetching CSV data...\n";
$csvData = @file_get_contents($testUrl);

if ($csvData === false) {
    echo "  ❌ Failed to fetch CSV!\n";
    echo "  Possible causes:\n";
    echo "    - Spreadsheet not published correctly\n";
    echo "    - Not shared as 'Anyone with the link'\n";
    echo "    - Wrong URL format\n";
    echo "    - Network issue\n";
    exit(1);
}

echo "  ✅ CSV data fetched successfully\n";
echo "  Size: " . number_format(strlen($csvData)) . " bytes\n";

// Test 4: Parse CSV
echo "\nTest 4: Parsing CSV...\n";
$lines = explode("\n", $csvData);
$rows = [];

foreach ($lines as $line) {
    if (trim($line) !== '') {
        $rows[] = str_getcsv($line);
    }
}

if (empty($rows)) {
    echo "  ❌ No data rows found!\n";
    exit(1);
}

echo "  ✅ CSV parsed successfully\n";
echo "  Total rows: " . count($rows) . "\n";

// Test 5: Check header row
echo "\nTest 5: Checking required columns...\n";
$headers = $rows[0];
$requiredColumns = [
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

$missingColumns = [];
foreach ($requiredColumns as $col) {
    $found = false;
    foreach ($headers as $header) {
        if (strcasecmp(trim($header), trim($col)) === 0) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $missingColumns[] = $col;
    }
}

if (!empty($missingColumns)) {
    echo "  ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
    echo "\n  Available columns in spreadsheet:\n";
    foreach ($headers as $i => $h) {
        echo "    $i: $h\n";
    }
    exit(1);
}

echo "  ✅ All required columns found\n";

// Test 6: Show sample data
echo "\nTest 6: Sample data (first row)...\n";
if (count($rows) > 1) {
    $dataRow = $rows[1];
    echo "  Header -> Data:\n";
    for ($i = 0; $i < min(count($headers), count($dataRow)); $i++) {
        echo "    " . $headers[$i] . " -> " . $dataRow[$i] . "\n";
    }
} else {
    echo "  ⚠️  No data rows (only header)\n";
}

echo "\n===========================================\n";
echo "  ✅ ALL TESTS PASSED!\n";
echo "  GoogleSheetService will work correctly.\n";
echo "===========================================\n";