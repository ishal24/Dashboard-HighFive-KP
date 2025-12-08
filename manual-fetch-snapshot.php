<?php
/**
 * Manual Fetch Snapshot Script
 * Gunakan untuk langsung fetch data dari Google Sheets dan buat snapshot baru
 * 
 * Usage: php manual-fetch-snapshot.php
 */

// Bootstrap Laravel
require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\DatasetLink;
use App\Models\SpreadsheetSnapshot;
use App\Services\GoogleSheetService;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Color codes untuk terminal
$colors = [
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'reset' => "\033[0m"
];

try {
    echo $colors['blue'] . "=== Manual Fetch Snapshot ===" . $colors['reset'] . "\n\n";

    // 1. Get all active links
    $links = DatasetLink::where('is_active', true)->get();

    if ($links->isEmpty()) {
        echo $colors['yellow'] . "⚠️  Tidak ada link spreadsheet yang aktif\n\n" . $colors['reset'];
        exit(1);
    }

    echo "Daftar Link Spreadsheet:\n";
    foreach ($links as $idx => $link) {
        echo "  {$idx}) [{$link->id}] {$link->divisi->kode}\n";
    }
    echo "\n";

    // 2. Let user choose link
    echo "Masukkan nomor/ID link [0-" . ($links->count() - 1) . "]: ";
    $linkInput = trim(fgets(STDIN));
    
    $selectedLink = null;
    if (is_numeric($linkInput) && $linkInput < $links->count()) {
        $selectedLink = $links[$linkInput];
    } else {
        $selectedLink = $links->firstWhere('id', $linkInput);
    }

    if (!$selectedLink) {
        echo $colors['red'] . "❌ Link tidak ditemukan\n\n" . $colors['reset'];
        exit(1);
    }

    // 3. Ask for date
    echo "\nMasukkan tanggal snapshot (YYYY-MM-DD) [default: hari ini]: ";
    $dateInput = trim(fgets(STDIN));
    $snapshotDate = $dateInput ?: today()->toDateString();

    // Validate date
    try {
        $snapshotDate = Carbon::createFromFormat('Y-m-d', $snapshotDate)->toDateString();
    } catch (\Exception $e) {
        echo $colors['red'] . "❌ Format tanggal tidak valid\n\n" . $colors['reset'];
        exit(1);
    }

    echo "\n" . $colors['yellow'] . "⏳ Fetching data...\n" . $colors['reset'];

    // 4. Fetch data from Google Sheets
    $googleSheetService = app(GoogleSheetService::class);
    $parsedData = $googleSheetService->fetchSpreadsheetData($selectedLink->link_spreadsheet);

    if (empty($parsedData)) {
        throw new \Exception('Spreadsheet kosong atau tidak ada data');
    }

    echo $colors['green'] . "✓ Fetched " . count($parsedData) . " rows\n" . $colors['reset'];

    // 5. Check if snapshot exists
    $existingSnapshot = SpreadsheetSnapshot::where('dataset_link_id', $selectedLink->id)
        ->where('snapshot_date', $snapshotDate)
        ->first();

    if ($existingSnapshot) {
        echo $colors['yellow'] . "⚠️  Snapshot untuk tanggal ini sudah ada, akan di-update...\n" . $colors['reset'];
        $snapshot = $existingSnapshot;
        $isNew = false;
    } else {
        echo $colors['yellow'] . "📝 Creating new snapshot...\n" . $colors['reset'];
        $snapshot = SpreadsheetSnapshot::create([
            'dataset_link_id' => $selectedLink->id,
            'divisi_id' => $selectedLink->divisi_id,
            'snapshot_date' => $snapshotDate,
            'data_json' => json_encode([]),
            'fetched_at' => now(),
        ]);
        $isNew = true;
    }

    // 6. Store data
    $snapshot->storeSpreadsheetData($parsedData);
    $selectedLink->updateFetchStats('success');

    // 7. Display success
    echo "\n" . $colors['green'] . "✅ Success!" . $colors['reset'] . "\n";
    echo "Divisi    : {$selectedLink->divisi->kode}\n";
    echo "Tanggal   : {$snapshot->formatted_date}\n";
    echo "Status    : " . ($isNew ? 'New Snapshot' : 'Updated') . "\n";
    echo "Rows      : {$snapshot->total_rows}\n";
    echo "AMs       : {$snapshot->total_ams}\n";
    echo "Customers : {$snapshot->total_customers}\n";
    echo "Products  : {$snapshot->total_products}\n";
    echo "\n";

} catch (\Exception $e) {
    echo $colors['red'] . "❌ Error: " . $e->getMessage() . "\n\n" . $colors['reset'];
    exit(1);
}
