<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DatasetLink;
use App\Models\SpreadsheetSnapshot;
use App\Services\GoogleSheetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HighFiveSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'high-five:fetch-weekly-data {--force : Force fetch even if not Friday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch weekly High Five data from Google Sheets and create snapshots (runs every Friday 1 AM)';

    protected $googleSheetService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GoogleSheetService $googleSheetService)
    {
        parent::__construct();
        $this->googleSheetService = $googleSheetService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if today is Friday (unless --force flag is used)
        if (!$this->option('force') && !Carbon::now()->isFriday()) {
            $this->error('❌ Today is not Friday. Use --force to fetch anyway.');
            Log::warning('[HighFive] Cron job triggered on non-Friday: ' . Carbon::now()->format('Y-m-d (l)'));
            return Command::FAILURE;
        }

        $this->info('🚀 Starting High Five weekly data fetch...');
        $this->info('📅 Date: ' . Carbon::now()->locale('id')->isoFormat('dddd, DD MMMM YYYY HH:mm'));
        $this->line('');

        // Get all active dataset links
        $links = DatasetLink::where('is_active', true)
            ->with('divisi')
            ->get();

        if ($links->isEmpty()) {
            $this->warn('⚠️  No active dataset links found.');
            Log::info('[HighFive] No active links to fetch.');
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$links->count()} active dataset link(s)");
        $this->line('');

        // Statistics
        $successCount = 0;
        $failureCount = 0;
        $snapshotDate = Carbon::now()->toDateString(); // Today's date (Friday)

        // Process each link
        foreach ($links as $index => $link) {
            $divisiName = $link->divisi->kode ?? 'Unknown';
            $linkNumber = $index + 1;

            $this->line("─────────────────────────────────────────");
            $this->info("[{$linkNumber}/{$links->count()}] Processing: {$divisiName}");
            $this->line("Link: " . substr($link->link_spreadsheet, 0, 60) . '...');

            try {
                // Fetch data from Google Sheets
                $this->line('⏳ Fetching data from Google Sheets...');
                $parsedData = $this->googleSheetService->fetchSpreadsheetData($link->link_spreadsheet);

                if (empty($parsedData)) {
                    throw new \Exception('Spreadsheet kosong atau tidak ada data');
                }

                $totalRows = count($parsedData);
                $this->info("✓ Fetched {$totalRows} rows");

                // Check if snapshot for this date already exists
                $existingSnapshot = SpreadsheetSnapshot::where('dataset_link_id', $link->id)
                    ->where('snapshot_date', $snapshotDate)
                    ->first();

                if ($existingSnapshot) {
                    $this->warn('⚠️  Snapshot for this date already exists, updating...');
                    $snapshot = $existingSnapshot;
                } else {
                    $this->line('📝 Creating new snapshot...');
                    $snapshot = SpreadsheetSnapshot::create([
                        'dataset_link_id' => $link->id,
                        'divisi_id' => $link->divisi_id,
                        'snapshot_date' => $snapshotDate,
                        'data_json' => json_encode([]),
                        'fetched_at' => now(),
                    ]);
                }

                // Store parsed data
                $snapshot->storeSpreadsheetData($parsedData);

                // Update link statistics
                $link->updateFetchStats('success');

                // Display statistics
                $this->info("✅ Success!");
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Total Rows', $snapshot->total_rows],
                        ['Account Managers', $snapshot->total_ams],
                        ['Customers', $snapshot->total_customers],
                        ['Products', $snapshot->total_products],
                        ['Snapshot Date', $snapshot->formatted_date],
                    ]
                );

                $successCount++;

                Log::info("[HighFive] Successfully fetched {$divisiName}", [
                    'link_id' => $link->id,
                    'snapshot_id' => $snapshot->id,
                    'total_rows' => $snapshot->total_rows,
                ]);

            } catch (\Exception $e) {
                $this->error("❌ Failed: " . $e->getMessage());

                // Mark as failed
                $link->updateFetchStats('failed', $e->getMessage());

                $failureCount++;

                Log::error("[HighFive] Failed to fetch {$divisiName}", [
                    'link_id' => $link->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->line('');
        }

        // Final summary
        $this->line("═════════════════════════════════════════");
        $this->info('📊 FETCH SUMMARY');
        $this->line("═════════════════════════════════════════");
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Success', $successCount],
                ['❌ Failed', $failureCount],
                ['📋 Total', $links->count()],
            ]
        );

        // Send notification (optional - implement as needed)
        if ($failureCount > 0) {
            $this->warn("⚠️  {$failureCount} fetch(es) failed. Check logs for details.");
            // TODO: Send email/Slack notification to admin
        }

        Log::info('[HighFive] Weekly fetch completed', [
            'success' => $successCount,
            'failed' => $failureCount,
            'total' => $links->count(),
        ]);

        return $successCount === $links->count() ? Command::SUCCESS : Command::FAILURE;
    }
}