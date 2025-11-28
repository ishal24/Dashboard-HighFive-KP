<?php

// FIX: THIS FILE SHOULD BE TEMPORARY, IT'S IN THE WRONG DIRECTORY TOO

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AmRevenue;
use App\Models\Witel;
use App\Models\Divisi;
use App\Models\AccountManager;
// use App\Models\Regional;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WitelPerformController extends Controller
{
    /**
     * Default divisions if none exist in the database
     */
    protected $defaultDivisions = ['DSS', 'DPS', 'DGS', 'RLEGS'];

    /**
     * Default witels if none exist in the database
     */
    protected $defaultWitels = [
        'Suramadu',
        'Nusa Tenggara',
        'Jatim Barat',
        'Yogya Jateng Selatan',
        'Bali',
        'Semarang Jateng Utara',
        'Solo Jateng Timur',
        'Jatim Timur'
    ];

    public function index(Request $request)
    {
        try {
            // Default filter parameters
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            $selectedWitel = $request->input('witel', 'all');
            $selectedRegional = $request->input('regional', 'all'); // this shouldn't exist anymore
            $selectedDivisi = $request->input('divisi', 'all');

            Log::info("Entering CC & Witel Performance. Give it up everyone");
            Log::info("Here damn for now:", [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'selectedWitel' => $selectedWitel,
                'selectedRegional' => $selectedRegional,
                'selectedDivisi' => $selectedDivisi,
            ]);

            // ✅ NEW: Handle array witel from multi-select
            if (is_string($selectedWitel) && $selectedWitel !== 'all') {
                $selectedWitel = [$selectedWitel];
            }

            // Get all witel with fallback to default if empty
            $witels = Witel::pluck('nama')->toArray();
            if (empty($witels)) {
                $witels = $this->defaultWitels;
            }

            // Get divisions with fallback to defaults if table is empty
            $divisis = Divisi::pluck('nama')->toArray();
            if (empty($divisis)) {
                $divisis = $this->defaultDivisions;
            }

            // ✅ FIXED: Get revenue summary data with proper filter application
            $summaryData = $this->getRevenueSummary($startDate, $endDate, $selectedWitel, $selectedRegional, $selectedDivisi);

            // ✅ FIXED: Prepare Chart.js compatible data with proper filters
            $chartData = $this->prepareChartJsData($selectedWitel, $selectedRegional, $selectedDivisi, $startDate, $endDate);

            // Set regions for view
            $regions = $witels;

            return view('witelPerform', compact(
                'witels',
                'divisis',
                'selectedWitel',
                'selectedRegional',
                'selectedDivisi',
                'startDate',
                'endDate',
                'summaryData',
                'chartData',
                'regions'
            ));
        } catch (\Exception $e) {
            Log::error('Error in WitelPerformController: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());

            return view('error', ['message' => 'Terjadi kesalahan dalam memproses data: ' . $e->getMessage()]);
        }
    }

    /**
     * Format numbers for card summary (full format: milyar/juta/ribu)
     */
    private function formatNumberFull($number, $decimals = 2)
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, $decimals) . ' milyar';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, $decimals) . ' juta';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, $decimals) . ' ribu';
        } else {
            return number_format($number, $decimals);
        }
    }

    /**
     * Format numbers for charts (short format: B/M/K)
     */
    private function formatNumberShort($number, $decimals = 2)
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, $decimals) . ' B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, $decimals) . ' M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, $decimals) . ' K';
        } else {
            return number_format($number, $decimals);
        }
    }

    /**
     * ✅ FIXED: Generate proper period label for date ranges
     */
    private function generatePeriodLabel($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $startMonth = $start->format('M');
        $endMonth = $end->format('M');
        $startYear = $start->format('Y');
        $endYear = $end->format('Y');

        // Same month and year
        if ($start->isSameMonth($end)) {
            return $startMonth . ' ' . $startYear;
        }
        // Same year, different months
        elseif ($startYear === $endYear) {
            return $startMonth . ' - ' . $endMonth . ' ' . $startYear;
        }
        // Different years
        else {
            return $startMonth . ' ' . $startYear . ' - ' . $endMonth . ' ' . $endYear;
        }
    }

    /**
     * ✅ FIXED: Get monthly time series data for line chart - properly generate ALL months in range
     */
    private function getMonthlyTimeSeriesData($witel, $regional, $divisi, $startDate, $endDate)
    {
        try {
            // Get account manager IDs based on filters
            $accountManagerIds = $this->getAccountManagerIdsByFilters($witel, $regional, $divisi);

            if (empty($accountManagerIds)) {
                return [
                    'labels' => [],
                    'targetData' => [],
                    'realData' => [],
                    'achievementData' => []
                ];
            }

            // ✅ FIXED: Proper month iteration to ensure ALL months are included
            $start = Carbon::parse($startDate)->startOfMonth();
            $end = Carbon::parse($endDate)->endOfMonth();

            $labels = [];
            $targetData = [];
            $realData = [];
            $achievementData = [];

            // ✅ FIXED: Use proper Carbon iteration to avoid skipping months
            $current = $start->copy();

            Log::info('🔄 Generating monthly data from: ' . $current->format('Y-m-d') . ' to: ' . $end->format('Y-m-d'));

            while ($current->lte($end)) {
                $monthStart = $current->copy()->startOfMonth()->format('Y-m-d');
                $monthEnd = $current->copy()->endOfMonth()->format('Y-m-d');

                Log::info('📅 Processing month: ' . $current->format('M Y') . ' (Range: ' . $monthStart . ' to ' . $monthEnd . ')');

                // Get revenue data for this specific month
                $monthlyRevenue = $this->getRevenueForPeriod($accountManagerIds, $monthStart, $monthEnd, $divisi);

                $monthLabel = $current->format('M Y');
                $targetAmount = round($monthlyRevenue['total_target'] / 1000000, 2); // Convert to millions
                $realAmount = round($monthlyRevenue['total_real'] / 1000000, 2); // Convert to millions

                // Calculate achievement percentage
                $achievement = 0;
                if ($monthlyRevenue['total_target'] > 0) {
                    $achievement = ($monthlyRevenue['total_real'] / $monthlyRevenue['total_target']) * 100;
                }

                $labels[] = $monthLabel;
                $targetData[] = $targetAmount;
                $realData[] = $realAmount;
                $achievementData[] = round($achievement, 2);

                Log::info('💰 ' . $monthLabel . ' - Target: ' . $targetAmount . 'M, Real: ' . $realAmount . 'M, Achievement: ' . round($achievement, 2) . '%');

                // ✅ FIXED: Move to next month WITHOUT calling startOfMonth() which was causing issues
                $current->addMonth();
            }

            // Final logging for debugging
            Log::info('✅ Monthly time series data generated for period: ' . $startDate . ' to ' . $endDate);
            Log::info('📊 Total months generated: ' . count($labels));
            Log::info('📋 Labels: ' . implode(', ', $labels));
            Log::info('💵 Target data: ' . implode(', ', $targetData));
            Log::info('💰 Real data: ' . implode(', ', $realData));

            return [
                'labels' => $labels,
                'targetData' => $targetData,
                'realData' => $realData,
                'achievementData' => $achievementData
            ];
        } catch (\Exception $e) {
            Log::error('❌ Error in getMonthlyTimeSeriesData: ' . $e->getMessage());
            Log::error('🔍 Error trace: ' . $e->getTraceAsString());
            return [
                'labels' => [],
                'targetData' => [],
                'realData' => [],
                'achievementData' => []
            ];
        }
    }

    /**
     * ✅ UPDATED: Prepare Chart.js compatible data structure (supports multi-witel + time series)
     */
    private function prepareChartJsData($witel, $regional, $divisi, $startDate, $endDate)
    {
        try {
            // ✅ UPDATED: Get account manager IDs based on ALL filters INCLUDING divisi
            $accountManagerIds = $this->getAccountManagerIdsByFilters($witel, $regional, $divisi);

            if (empty($accountManagerIds)) {
                return [
                    'isEmpty' => true,
                    'periodPerformance' => null,
                    'stackedDivision' => null,
                    'timeSeriesData' => null,
                    'periodLabel' => $this->generatePeriodLabel($startDate, $endDate)
                ];
            }

            // Get period performance data
            $periodData = $this->getRevenueForPeriod($accountManagerIds, $startDate, $endDate, $divisi);

            // Calculate achievement
            $achievement = $periodData['total_target'] > 0
                ? ($periodData['total_real'] / $periodData['total_target']) * 100
                : 0;

            // Get stacked division data (by witel) - exclude RLEGS
            $stackedData = $this->getStackedDivisionData($witel, $regional, $divisi, $startDate, $endDate);

            // ✅ NEW: Get monthly time series data for line chart
            $timeSeriesData = $this->getMonthlyTimeSeriesData($witel, $regional, $divisi, $startDate, $endDate);

            return [
                'isEmpty' => false,
                'periodPerformance' => [
                    'target_revenue' => $periodData['total_target'],
                    'real_revenue' => $periodData['total_real'],
                    'achievement' => round($achievement, 2)
                ],
                'stackedDivision' => $stackedData,
                'timeSeriesData' => $timeSeriesData,
                'periodLabel' => $this->generatePeriodLabel($startDate, $endDate)
            ];
        } catch (\Exception $e) {
            Log::error('Error in prepareChartJsData: ' . $e->getMessage());

            return [
                'isEmpty' => true,
                'periodPerformance' => null,
                'stackedDivision' => null,
                'timeSeriesData' => null,
                'periodLabel' => $this->generatePeriodLabel($startDate, $endDate)
            ];
        }
    }

    /**
     * ✅ UPDATED: Get stacked division data for Chart.js (supports multi-witel, shows 7 bars for "all")
     */
    private function getStackedDivisionData($witel, $regional, $divisi, $startDate, $endDate)
    {
        try {
            // ✅ UPDATED: Get witels to show on chart (ALWAYS show individual witels)
            $witelsToShow = [];
            if ($witel !== 'all') {
                if (is_array($witel)) {
                    $witelsToShow = $witel;
                } else {
                    $witelsToShow = [$witel];
                }
            } else {
                // ✅ FIXED: When "all" selected, show 7 individual witels (not "all")
                $witels = Witel::pluck('nama')->toArray();
                $witelsToShow = !empty($witels) ? array_slice($witels, 0, 7) : array_slice($this->defaultWitels, 0, 7);

                // ✅ NEW: Log for debugging
                Log::info('Semua Witel selected, showing witels: ' . implode(', ', $witelsToShow));
            }

            // ✅ FIXED: Only show DSS, DPS, DGS (exclude RLEGS)
            $divisionsToShow = ['DSS', 'DPS', 'DGS'];

            // ✅ FIXED: If specific division selected, only show that one
            if ($divisi !== 'all' && is_array($divisi)) {
                $divisionsToShow = array_intersect($divisi, ['DSS', 'DPS', 'DGS']);
            } elseif ($divisi !== 'all' && !is_array($divisi) && in_array($divisi, ['DSS', 'DPS', 'DGS'])) {
                $divisionsToShow = [$divisi];
            }

            $datasets = [];
            $colors = [
                'DSS' => ['bg' => 'rgba(187, 247, 208, 0.8)', 'border' => '#10b981'],
                'DPS' => ['bg' => 'rgba(191, 219, 254, 0.8)', 'border' => '#3b82f6'],
                'DGS' => ['bg' => 'rgba(253, 230, 138, 0.8)', 'border' => '#f59e0b']
            ];

            foreach ($divisionsToShow as $division) {
                $data = [];

                foreach ($witelsToShow as $witelName) {
                    // ✅ UPDATED: Get account managers for this specific witel and division
                    if ($witel === 'all') {
                        // For "Semua Witel", get AMs for each individual witel
                        $witelAMs = $this->getAccountManagersByWitelAndDivision($witelName, $division);

                        // Apply regional filter if needed
                        if ($regional !== 'all') {
                            $regionalAMs = $this->getAccountManagerIdsByFilters('all', $regional, 'all');
                            $witelAMs = array_intersect($witelAMs, $regionalAMs);
                        }
                    } else {
                        // For specific witels, use existing logic
                        $witelAMs = $this->getAccountManagersByWitelAndDivision($witelName, $division);

                        // Apply regional filter if needed
                        if ($regional !== 'all') {
                            $regionalAMs = $this->getAccountManagerIdsByFilters('all', $regional, 'all');
                            $witelAMs = array_intersect($witelAMs, $regionalAMs);
                        }
                    }

                    $revenue = $this->getRevenueForPeriod($witelAMs, $startDate, $endDate, $division);
                    $revenueInMillions = round($revenue['total_real'] / 1000000, 2); // Convert to millions

                    // ✅ NEW: Log revenue data for debugging
                    Log::info("Revenue for {$witelName} - {$division}: {$revenueInMillions}M (from {$revenue['total_real']})");

                    $data[] = $revenueInMillions;
                }

                $datasets[] = [
                    'label' => $division,
                    'data' => $data,
                    'backgroundColor' => $colors[$division]['bg'],
                    'borderColor' => $colors[$division]['border'],
                    'borderWidth' => 1
                ];
            }

            // ✅ NEW: Log final chart data
            Log::info('Stacked chart labels: ' . implode(', ', $witelsToShow));
            Log::info('Stacked chart datasets count: ' . count($datasets));

            return [
                'labels' => $witelsToShow,
                'datasets' => $datasets
            ];
        } catch (\Exception $e) {
            Log::error('Error in getStackedDivisionData: ' . $e->getMessage());
            return [
                'labels' => [],
                'datasets' => []
            ];
        }
    }

    /**
     * ✅ UPDATED: Get account manager IDs with ALL filters working properly (supports multi-witel)
     */
    private function getAccountManagerIdsByFilters($witel = 'all', $regional = 'all', $divisi = 'all')
    {
        $query = AccountManager::query();

        // ✅ UPDATED: Apply witel filter (supports array)
        if ($witel !== 'all') {
            if (is_array($witel)) {
                // Multiple witels selected
                $witelIds = Witel::whereIn('nama', $witel)->pluck('id')->toArray();
                if (!empty($witelIds)) {
                    $query->whereIn('witel_id', $witelIds);
                }
            } else {
                // Single witel selected
                $witelId = Witel::where('nama', $witel)->first()?->id;
                if ($witelId) {
                    $query->where('witel_id', $witelId);
                }
            }
            Log::info("getAMIdByFilter: Got me some witel:", ['witelId' => $witelId]);
        }

        $accountManagerIds = $query->pluck('id')->toArray();
        Log::info("Got me some AM ids in GAMIBF:", ['accountManagerIds' => $accountManagerIds]);

        // ✅ FIXED: Apply division filter using many-to-many relationship
        if ($divisi !== 'all' && !empty($accountManagerIds)) {
            if (is_array($divisi)) {
                $divisionIds = Divisi::whereIn('nama', $divisi)->pluck('id')->toArray();
            } else {
                $divisionIds = Divisi::where('nama', $divisi)->pluck('id')->toArray();
            }

            if (!empty($divisionIds)) {
                $accountManagerIds = DB::table('account_manager_divisi')
                    ->whereIn('account_manager_id', $accountManagerIds)
                    ->whereIn('divisi_id', $divisionIds)
                    ->pluck('account_manager_id')
                    ->unique()
                    ->toArray();
            }
        }

        return $accountManagerIds;
    }

    /**
     * ✅ UPDATED: Get account managers by witel (supports multiple witels)
     */
    private function getAccountManagersByWitel($witel)
    {
        if ($witel === 'all') {
            return AccountManager::pluck('id')->toArray();
        }

        if (is_array($witel)) {
            $witelIds = Witel::whereIn('nama', $witel)->pluck('id')->toArray();
            return AccountManager::whereIn('witel_id', $witelIds)->pluck('id')->toArray();
        } else {
            $witelId = Witel::where('nama', $witel)->first()?->id;
            if (!$witelId) {
                return [];
            }
            return AccountManager::where('witel_id', $witelId)->pluck('id')->toArray();
        }
    }

    /**
     * Get account managers by witel and division
     */
    private function getAccountManagersByWitelAndDivision($witelName, $divisionName)
    {
        $witelId = Witel::where('nama', $witelName)->first()?->id;
        $divisionId = Divisi::where('nama', $divisionName)->first()?->id;

        if (!$witelId || !$divisionId) {
            return [];
        }

        return DB::table('account_manager_divisi')
            ->join('account_managers', 'account_manager_divisi.account_manager_id', '=', 'account_managers.id')
            ->where('account_managers.witel_id', $witelId)
            ->where('account_manager_divisi.divisi_id', $divisionId)
            ->pluck('account_managers.id')
            ->toArray();
    }

    /**
     * ✅ UPDATED: Revenue summary with proper division filtering logic (supports multi-witel)
     */
    private function getRevenueSummary($startDate, $endDate, $witel = 'all', $regional = 'all', $divisi = 'all')
    {
        $data = [];

        try {
            // ✅ FIXED: Determine which divisions to show based on filter
            $divisionsToShow = $this->defaultDivisions;

            if ($divisi !== 'all') {
                if (is_array($divisi)) {
                    $divisionsToShow = $divisi;
                    // Add RLEGS if multiple divisions selected (as it represents total)
                    if (count($divisi) > 1 && !in_array('RLEGS', $divisi)) {
                        $divisionsToShow[] = 'RLEGS';
                    }
                } else {
                    $divisionsToShow = [$divisi];
                }
            }

            foreach ($divisionsToShow as $division) {
                // ✅ FIXED: Get account manager IDs based on ACTUAL division
                if ($division === 'RLEGS') {
                    // RLEGS = ALL account managers (ignoring division filter for RLEGS calculation)
                    $accountManagerIds = $this->getAccountManagerIdsByFilters($witel, $regional, 'all');
                } else {
                    // ✅ FIXED: For individual divisions, get ONLY that division's AMs
                    $accountManagerIds = $this->getAccountManagerIdsByFilters($witel, $regional, $division);
                }

                // Skip if no account managers found
                if (empty($accountManagerIds)) {
                    $data[$division] = [
                        'total_real' => 0,
                        'total_real_formatted' => '0',
                        'total_target' => 0,
                        'total_target_formatted' => '0',
                        'percentage_change' => 0,
                        'achievement' => 0
                    ];
                    continue;
                }

                // Calculate current and previous period data
                $currentPeriodStart = Carbon::parse($startDate);
                $currentPeriodEnd = Carbon::parse($endDate);
                $daysDifference = $currentPeriodEnd->diffInDays($currentPeriodStart) ?: 30;

                $previousPeriodEnd = $currentPeriodStart->copy()->subDay();
                $previousPeriodStart = $previousPeriodEnd->copy()->subDays($daysDifference);

                $currentPeriodData = $this->getRevenueForPeriod(
                    $accountManagerIds,
                    $currentPeriodStart->format('Y-m-d'),
                    $currentPeriodEnd->format('Y-m-d'),
                    $division  // ✅ PASS division name to filter revenues
                );

                $previousPeriodData = $this->getRevenueForPeriod(
                    $accountManagerIds,
                    $previousPeriodStart->format('Y-m-d'),
                    $previousPeriodEnd->format('Y-m-d'),
                    $division  // ✅ PASS division name to filter revenues
                );

                // Calculate percentage change
                $percentageChange = 0;
                if ($previousPeriodData['total_real'] > 0) {
                    $percentageChange = (($currentPeriodData['total_real'] - $previousPeriodData['total_real']) / $previousPeriodData['total_real']) * 100;
                }

                // Calculate achievement percentage
                $achievement = 0;
                if ($currentPeriodData['total_target'] > 0) {
                    $achievement = ($currentPeriodData['total_real'] / $currentPeriodData['total_target']) * 100;
                }

                Log::info("WitelPerform: Revenue Summary Got");

                // ✅ FIXED: Format numbers properly for card display
                $data[$division] = [
                    'total_real' => $currentPeriodData['total_real'], // Keep raw number
                    'total_real_formatted' => $this->formatNumberFull($currentPeriodData['total_real']), // Formatted string
                    'total_target' => $currentPeriodData['total_target'], // Keep raw number
                    'total_target_formatted' => $this->formatNumberFull($currentPeriodData['total_target']), // Formatted string
                    'percentage_change' => round($percentageChange, 2),
                    'achievement' => round($achievement, 2)
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error in getRevenueSummary: ' . $e->getMessage());

            // Return default data if error occurs
            foreach ($this->defaultDivisions as $division) {
                $data[$division] = [
                    'total_real' => 0,
                    'total_real_formatted' => '0',
                    'total_target' => 0,
                    'total_target_formatted' => '0',
                    'percentage_change' => 0,
                    'achievement' => 0
                ];
            }

            return $data;
        }
    }

    /**
     * Get account managers by division
     */
    private function getAccountManagersByDivision($divisionName, $accountManagerIds = [])
    {
        $divisionId = Divisi::where('nama', $divisionName)->first()?->id;
        if (!$divisionId) {
            return [];
        }

        $query = DB::table('account_manager_divisi')
            ->where('divisi_id', $divisionId);

        if (!empty($accountManagerIds)) {
            $query->whereIn('account_manager_id', $accountManagerIds);
        }

        return $query->pluck('account_manager_id')->toArray();
    }

    /**
     * ✅ FIXED: Get revenue for specific period with division filter
     */
    private function getRevenueForPeriod($accountManagerIds, $startDate, $endDate, $divisionName = null)
    {
        if (empty($accountManagerIds)) {
            Log::info('WitelPerform: empty accountManagerIds');
            return [
                'total_target' => 0,
                'total_real' => 0
            ];
        }

        Log::info("WitelPerform: Getting Revenue for Period whatever");

        $query = AmRevenue::whereIn('account_manager_id', $accountManagerIds)
            ->whereBetween('bulan', [$startDate, $endDate]);

        // ✅ MAJOR FIX: Add division filter to revenue query
        if (!empty($divisionName) && $divisionName !== 'RLEGS') {
            $divisionId = Divisi::where('nama', $divisionName)->first()?->id;
            if ($divisionId) {
                $query->where('divisi_id', $divisionId);
            }
        }

        $result = $query->select(
            DB::raw('COALESCE(SUM(target_revenue), 0) as total_target'),
            DB::raw('COALESCE(SUM(real_revenue), 0) as total_real')
        )->first();

        return [
            'total_target' => $result ? $result->total_target : 0,
            'total_real' => $result ? $result->total_real : 0
        ];
    }

    /**
     * ✅ UPDATED: Update charts via AJAX with proper Chart.js data (supports multi-witel)
     */
    public function updateCharts(Request $request)
    {
        try {
            $witel = $request->input('witel', 'all');
            $regional = $request->input('regional', 'all');
            $divisi = $request->input('divisi', 'all');
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            // ✅ NEW: Handle array witel from frontend
            if (is_array($witel) && count($witel) == 1) {
                $witel = $witel[0]; // Convert single-item array to string
            }

            // Generate updated Chart.js data
            $chartData = $this->prepareChartJsData($witel, $regional, $divisi, $startDate, $endDate);

            // Get updated summary data
            $summaryData = $this->getRevenueSummary($startDate, $endDate, $witel, $regional, $divisi);

            return response()->json([
                'chartData' => $chartData,
                'summaryData' => $summaryData
            ]);
        } catch (\Exception $e) {
            Log::error('Error in updateCharts: ' . $e->getMessage());

            return response()->json([
                'error' => 'Terjadi kesalahan dalam memproses data: ' . $e->getMessage(),
                'chartData' => ['isEmpty' => true],
                'summaryData' => $this->getDefaultSummaryData()
            ], 500);
        }
    }

    /**
     * ✅ UPDATED: Filter by witel with proper data (supports multi-witel)
     */
    public function filterByWitel(Request $request)
    {
        try {
            $witel = $request->input('witel', 'all');
            $regional = $request->input('regional', 'all');
            $divisi = $request->input('divisi', 'all');
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            // ✅ NEW: Log multi-witel request for debugging
            if (is_array($witel)) {
                Log::info('Multi-witel filter request: ' . implode(', ', $witel));
            }

            $chartData = $this->prepareChartJsData($witel, $regional, $divisi, $startDate, $endDate);
            $summaryData = $this->getRevenueSummary($startDate, $endDate, $witel, $regional, $divisi);

            return response()->json([
                'chartData' => $chartData,
                'summaryData' => $summaryData
            ]);
        } catch (\Exception $e) {
            Log::error('Error in filterByWitel: ' . $e->getMessage());

            return response()->json([
                'error' => 'Terjadi kesalahan dalam memproses filter: ' . $e->getMessage(),
                'chartData' => ['isEmpty' => true],
                'summaryData' => $this->getDefaultSummaryData()
            ], 500);
        }
    }

    /*
        NOTE: Filter by regional with proper data (unused)
     */
    public function filterByRegional(Request $request)
    {
        try {
            $regional = $request->input('regional', 'all');
            $witel = $request->input('witel', 'all');
            $divisi = $request->input('divisi', 'all');
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            $chartData = $this->prepareChartJsData($witel, $regional, $divisi, $startDate, $endDate);
            $summaryData = $this->getRevenueSummary($startDate, $endDate, $witel, $regional, $divisi);

            return response()->json([
                'chartData' => $chartData,
                'summaryData' => $summaryData
            ]);
        } catch (\Exception $e) {
            Log::error('Error in filterByRegional: ' . $e->getMessage());

            return response()->json([
                'error' => 'Terjadi kesalahan dalam memproses filter: ' . $e->getMessage(),
                'chartData' => ['isEmpty' => true],
                'summaryData' => $this->getDefaultSummaryData()
            ], 500);
        }
    }

    /**
     * ✅ UPDATED: Filter by division with proper data
     */
    public function filterByDivisi(Request $request)
    {
        try {
            $divisiList = $request->input('divisi', []);
            $witel = $request->input('witel', 'all');
            $regional = $request->input('regional', 'all');
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            if (empty($divisiList)) {
                $divisiList = 'all';
            }

            $chartData = $this->prepareChartJsData($witel, $regional, $divisiList, $startDate, $endDate);
            $summaryData = $this->getRevenueSummary($startDate, $endDate, $witel, $regional, $divisiList);

            return response()->json([
                'chartData' => $chartData,
                'summaryData' => $summaryData
            ]);
        } catch (\Exception $e) {
            Log::error('Error in filterByDivisi: ' . $e->getMessage());

            return response()->json([
                'error' => 'Terjadi kesalahan dalam memproses filter: ' . $e->getMessage(),
                'chartData' => ['isEmpty' => true],
                'summaryData' => $this->getDefaultSummaryData()
            ], 500);
        }
    }

    /**
     * Get default summary data for error handling
     */
    private function getDefaultSummaryData()
    {
        $defaultData = [];
        foreach ($this->defaultDivisions as $division) {
            $defaultData[$division] = [
                'total_real' => 0,
                'total_real_formatted' => '0',
                'total_target' => 0,
                'total_target_formatted' => '0',
                'percentage_change' => 0,
                'achievement' => 0
            ];
        }
        return $defaultData;
    }
}
