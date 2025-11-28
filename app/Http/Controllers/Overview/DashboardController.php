<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Services\RevenueCalculationService;
use App\Services\RankingService;
use App\Services\PerformanceAnalysisService;
use App\Models\AccountManager;
use App\Models\Divisi;
use App\Models\Witel;
use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\Segment;
use App\Models\CorporateCustomer;
use App\Exports\AdminDashboardExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    protected $revenueService;
    protected $rankingService;
    protected $performanceService;

    public function __construct(
        RevenueCalculationService $revenueService,
        RankingService $rankingService,
        PerformanceAnalysisService $performanceService
    ) {
        $this->revenueService = $revenueService;
        $this->rankingService = $rankingService;
        $this->performanceService = $performanceService;
    }

    /**
     * ========================================
     * HELPER: FORMAT CURRENCY SHORT
     * ========================================
     * Fungsi untuk format angka menjadi Juta/Miliar/Triliun
     */
    private function formatCurrencyShort($value)
    {
        $absValue = abs($value);

        if ($absValue >= 1000000000000) { // >= 1 Triliun
            return number_format($absValue / 1000000000000, 2, ',', '.') . ' Triliun';
        } elseif ($absValue >= 1000000000) { // >= 1 Miliar
            return number_format($absValue / 1000000000, 2, ',', '.') . ' Miliar';
        } elseif ($absValue >= 1000000) { // >= 1 Juta
            return number_format($absValue / 1000000, 2, ',', '.') . ' Juta';
        } else {
            return number_format($absValue, 0, ',', '.');
        }
    }

    /**
     * ========================================
     * HELPER: FORMAT CHART VALUE
     * ========================================
     * Fungsi untuk format nilai chart (dalam juta)
     */
    private function formatChartValue($value)
    {
        // Konversi ke juta
        $valueInJuta = $value / 1000000;

        if ($valueInJuta >= 1000000) { // >= 1 Triliun (dalam juta)
            return number_format($valueInJuta / 1000000, 1, ',', '.') . ' Triliun';
        } elseif ($valueInJuta >= 1000) { // >= 1 Miliar (dalam juta)
            return number_format($valueInJuta / 1000, 1, ',', '.') . ' Miliar';
        } else {
            return number_format($valueInJuta, 1, ',', '.') . ' Juta';
        }
    }

    /**
     * Main dashboard entry point
     */
    public function index(Request $request)
    {
        Log::info("Accessing DashboardController");

        $user = Auth::user();

        try {
            switch ($user->role) {
                case 'admin':
                    Log::info("DashboardController - role is Admin so naturally");
                    return $this->handleAdminDashboard($request);
                case 'account_manager':
                    Log::info("DashboardController - role is AM so naturally");
                    return $this->handleAmDashboard($request);
                case 'witel':
                    Log::info("DashboardController - role is Witel so naturally");
                    return $this->handleWitelDashboard($request, $user->witel_id);
                default:
                    Log::info("DashboardController - ermm don't know what role so, bye");
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')
                        ->with('error', 'Role tidak memiliki akses ke dashboard.');
            }
        } catch (\Exception $e) {
            Log::error('Dashboard access error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    private function handleAmDashboard(Request $request)
    {
        $amController = app(AmDashboardController::class);
        return $amController->index($request);
    }

    private function handleWitelDashboard(Request $request, $witel_id)
    {
        $witelController = app(WitelDashboardController::class);
        return $witelController->show($witel_id, $request);
    }

    /**
     * ========================================
     * HANDLE ADMIN DASHBOARD - FIXED
     * ========================================
     */
    private function handleAdminDashboard(Request $request)
    {
        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            // 1. CARD GROUP - DENGAN FORMAT SHORT
            $cardData = $this->revenueService->getTotalRevenueDataWithDateRange(
                null,
                $filters['divisi_id'],
                $dateRange['start'],
                $dateRange['end'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            // Tambahkan format short untuk card
            $cardData['total_revenue_formatted'] = $this->formatCurrencyShort($cardData['total_revenue'] ?? 0);
            $cardData['total_target_formatted'] = $this->formatCurrencyShort($cardData['total_target'] ?? 0);
            $cardData['period_text'] = $this->generatePeriodText($filters['period_type'], $dateRange);

            // 2. PERFORMANCE SECTION - FIXED TOP 10 WITH PRIORITY
            $performanceData = [
                'account_manager' => $this->getTop10AccountManagersWithPriority(null, $dateRange, $filters),
                'corporate_customer' => $this->getTop10CorporateCustomersWithPriority(null, $dateRange, $filters)
            ];
            $this->addClickableUrls($performanceData);

            // 3. CHARTS
            $currentYear = date('Y');
            $monthlyRevenue = $this->revenueService->getMonthlyRevenue(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );
            $performanceDistribution = $this->performanceService->getPerformanceDistribution(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            // Prepare data untuk chart dengan format yang sudah diringkas
            $monthlyLabels = [];
            $monthlyReal = [];
            $monthlyTarget = [];

            if ($monthlyRevenue && !$monthlyRevenue->isEmpty()) {
                foreach ($monthlyRevenue as $data) {
                    $monthlyLabels[] = $data['month_name'] ?? 'Unknown';
                    // Konversi ke juta untuk chart
                    $monthlyReal[] = round(($data['real_revenue'] ?? 0) / 1000000, 2);
                    $monthlyTarget[] = round(($data['target_revenue'] ?? 0) / 1000000, 2);
                }
            }

            // Performance distribution untuk doughnut chart
            $amPerformanceDistribution = $this->formatPerformanceDistribution($performanceDistribution);

            // 4. REVENUE TABLE
            $revenueTable = $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'],
                $dateRange['end'],
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            $filterOptions = $this->getFilterOptionsForAdmin();

            return view('dashboard', compact(
                'cardData',
                'performanceData',
                'monthlyLabels',
                'monthlyReal',
                'monthlyTarget',
                'amPerformanceDistribution',
                'revenueTable',
                'filterOptions',
                'filters'
            ));
        } catch (\Exception $e) {
            Log::error('Admin dashboard failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return view('dashboard', [
                'error' => 'Gagal memuat dashboard.',
                'filters' => $this->getDefaultFilters(),
                'filterOptions' => $this->getFilterOptionsForAdmin(),
                'cardData' => $this->getEmptyCardData(),
                'performanceData' => $this->getEmptyPerformanceData(),
                'revenueTable' => collect([]),
                'monthlyLabels' => [],
                'monthlyReal' => [],
                'monthlyTarget' => [],
                'amPerformanceDistribution' => ['Hijau' => 0, 'Oranye' => 0, 'Merah' => 0]
            ]);
        }
    }

    /**
     * ========================================
     * FORMAT PERFORMANCE DISTRIBUTION
     * ========================================
     */
    private function formatPerformanceDistribution($performanceData)
    {
        $distribution = ['Hijau' => 0, 'Oranye' => 0, 'Merah' => 0];

        if (!$performanceData || $performanceData->isEmpty()) {
            return $distribution;
        }

        foreach ($performanceData as $data) {
            $distribution['Hijau'] += $data['excellent'] ?? 0;
            $distribution['Oranye'] += $data['good'] ?? 0;
            $distribution['Merah'] += $data['poor'] ?? 0;
        }

        return $distribution;
    }

    /**
     * ========================================
     * GET TOP 10 ACCOUNT MANAGERS WITH PRIORITY - FIXED
     * ========================================
     * Priority:
     * 1. AM dengan revenue (sorted by achievement_rate DESC)
     * 2. AM tanpa revenue (sorted by nama ASC)
     * Total: maksimal 10 rows
     */
    private function getTop10AccountManagersWithPriority($witelId = null, $dateRange, $filters)
    {
        try {
            // Step 1: Get AM dengan revenue
            $amWithRevenueQuery = AmRevenue::query();

            if ($dateRange['start'] && $dateRange['end']) {
                $startYear = Carbon::parse($dateRange['start'])->year;
                $startMonth = Carbon::parse($dateRange['start'])->month;
                $endMonth = Carbon::parse($dateRange['end'])->month;

                $amWithRevenueQuery->where('tahun', $startYear)
                    ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $amWithRevenueQuery->where('tahun', $this->getCurrentDataYear());
            }

            if ($witelId) {
                $amWithRevenueQuery->whereHas('accountManager', function ($q) use ($witelId) {
                    $q->where('witel_id', $witelId);
                });
            }

            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $amWithRevenueQuery->where('divisi_id', $filters['divisi_id']);
            }

            $revenueData = $amWithRevenueQuery->selectRaw('
                    account_manager_id,
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target,
                    CASE
                        WHEN SUM(target_revenue) > 0
                        THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                        ELSE 0
                    END as achievement_rate
                ')
                ->groupBy('account_manager_id')
                ->orderByDesc('achievement_rate')
                ->get()
                ->keyBy('account_manager_id');

            // Step 2: Get AM objects dengan revenue
            $amIdsWithRevenue = $revenueData->keys()->toArray();

            $amWithRevenueQuery = AccountManager::where('role', 'AM')
                ->with(['witel', 'divisis'])
                ->whereIn('id', $amIdsWithRevenue);

            if ($witelId) {
                $amWithRevenueQuery->where('witel_id', $witelId);
            }

            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $amWithRevenueQuery->whereHas('divisis', function ($q) use ($filters) {
                    $q->where('divisi.id', $filters['divisi_id']);
                });
            }

            $amWithRevenue = $amWithRevenueQuery->get()->map(function ($am) use ($revenueData) {
                $revenue = $revenueData->get($am->id);
                $totalRevenue = $revenue ? $revenue->total_revenue : 0;
                $totalTarget = $revenue ? $revenue->total_target : 0;
                $achievement = $revenue ? $revenue->achievement_rate : 0;

                $am->total_revenue = $totalRevenue;
                $am->total_target = $totalTarget;
                $am->achievement_rate = round($achievement, 2);
                $am->achievement_color = $this->getAchievementColor($achievement);
                $am->divisi_list = $am->divisis && $am->divisis->count() > 0
                    ? $am->divisis->pluck('nama')->join(', ')
                    : 'N/A';
                $am->has_revenue = true;

                return $am;
            })->sortByDesc('achievement_rate')->values();

            // Step 3: Cek apakah sudah 10, jika belum ambil AM tanpa revenue
            $results = collect($amWithRevenue);
            $needed = 10 - $results->count();

            if ($needed > 0) {
                $amWithoutRevenueQuery = AccountManager::where('role', 'AM')
                    ->with(['witel', 'divisis'])
                    ->whereNotIn('id', $amIdsWithRevenue)
                    ->orderBy('nama', 'ASC');

                if ($witelId) {
                    $amWithoutRevenueQuery->where('witel_id', $witelId);
                }

                if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                    $amWithoutRevenueQuery->whereHas('divisis', function ($q) use ($filters) {
                        $q->where('divisi.id', $filters['divisi_id']);
                    });
                }

                $amWithoutRevenue = $amWithoutRevenueQuery->take($needed)->get()->map(function ($am) {
                    $am->total_revenue = 0;
                    $am->total_target = 0;
                    $am->achievement_rate = 0;
                    $am->achievement_color = 'secondary';
                    $am->divisi_list = $am->divisis && $am->divisis->count() > 0
                        ? $am->divisis->pluck('nama')->join(', ')
                        : 'N/A';
                    $am->has_revenue = false;

                    return $am;
                });

                $results = $results->concat($amWithoutRevenue);
            }

            return $results->take(10);
        } catch (\Exception $e) {
            Log::error('Failed to get top 10 account managers with priority', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * GET TOP 10 CORPORATE CUSTOMERS WITH PRIORITY - FIXED
     * ========================================
     * Priority:
     * 1. CC dengan revenue (sorted by total_revenue DESC)
     * 2. CC tanpa revenue (sorted by nama ASC)
     * Total: maksimal 10 rows
     */
    private function getTop10CorporateCustomersWithPriority($witelId = null, $dateRange, $filters)
    {
        try {
            // Step 1: Get CC dengan revenue
            $query = DB::table('cc_revenues');

            // Date filtering
            if ($dateRange['start'] && $dateRange['end']) {
                $startYear = Carbon::parse($dateRange['start'])->year;
                $startMonth = Carbon::parse($dateRange['start'])->month;
                $endMonth = Carbon::parse($dateRange['end'])->month;

                $query->where('tahun', $startYear)
                    ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $query->where('tahun', $this->getCurrentDataYear());
            }

            // Witel filtering
            if ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    $q->where('witel_ho_id', $witelId)
                        ->orWhere('witel_bill_id', $witelId);
                });
            }

            // Divisi filtering
            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            // Revenue source filtering
            if (isset($filters['revenue_source']) && $filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            // Tipe revenue filtering
            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            // Get aggregated data
            $revenueData = $query
                ->select('corporate_customer_id')
                ->selectRaw('SUM(real_revenue) as total_revenue')
                ->selectRaw('SUM(target_revenue) as total_target')
                ->whereNotNull('corporate_customer_id')
                ->groupBy('corporate_customer_id')
                ->orderByDesc('total_revenue')
                ->get()
                ->keyBy('corporate_customer_id');

            $ccIdsWithRevenue = $revenueData->keys()->toArray();

            // Step 2: Build CC dengan revenue
            $results = collect([]);

            foreach ($revenueData as $ccId => $revenue) {
                $customer = DB::table('corporate_customers')
                    ->where('id', $ccId)
                    ->first();

                if (!$customer) continue;

                // Get latest record untuk divisi dan segment
                $latestRecord = DB::table('cc_revenues')
                    ->where('corporate_customer_id', $ccId)
                    ->orderByDesc('tahun')
                    ->orderByDesc('bulan')
                    ->first();

                // Get divisi
                $divisiName = 'N/A';
                if ($latestRecord && $latestRecord->divisi_id) {
                    $divisi = DB::table('divisi')->where('id', $latestRecord->divisi_id)->first();
                    $divisiName = $divisi ? $divisi->nama : 'N/A';
                }

                // Get segment
                $segmentName = 'N/A';
                if ($latestRecord && $latestRecord->segment_id) {
                    $segment = DB::table('segments')->where('id', $latestRecord->segment_id)->first();
                    $segmentName = $segment ? $segment->lsegment_ho : 'N/A';
                }

                $achievementRate = $revenue->total_target > 0
                    ? ($revenue->total_revenue / $revenue->total_target) * 100
                    : 0;

                $results->push((object) [
                    'id' => $ccId,
                    'nama' => $customer->nama,
                    'nipnas' => $customer->nipnas,
                    'divisi_nama' => $divisiName,
                    'segment_nama' => $segmentName,
                    'total_revenue' => floatval($revenue->total_revenue),
                    'total_target' => floatval($revenue->total_target),
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate),
                    'has_revenue' => true
                ]);
            }

            // Step 3: Cek apakah sudah 10, jika belum ambil CC tanpa revenue
            $needed = 10 - $results->count();

            if ($needed > 0) {
                $ccWithoutRevenue = DB::table('corporate_customers')
                    ->whereNotIn('id', $ccIdsWithRevenue)
                    ->orderBy('nama', 'ASC')
                    ->take($needed)
                    ->get();

                foreach ($ccWithoutRevenue as $customer) {
                    $results->push((object) [
                        'id' => $customer->id,
                        'nama' => $customer->nama,
                        'nipnas' => $customer->nipnas ?? '-',
                        'divisi_nama' => 'N/A',
                        'segment_nama' => 'N/A',
                        'total_revenue' => 0,
                        'total_target' => 0,
                        'achievement_rate' => 0,
                        'achievement_color' => 'secondary',
                        'has_revenue' => false
                    ]);
                }
            }

            return $results->take(10);
        } catch (\Exception $e) {
            Log::error('Failed to get top 10 corporate customers with priority', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * GET ALL WITELS (NO LIMIT) - FIXED
     * ========================================
     */
    private function getAllWitels($dateRange, $filters)
    {
        try {
            // Get semua witel dari tabel witel
            $allWitels = DB::table('witel')
                ->select('id', 'nama')
                ->orderBy('nama', 'ASC')
                ->get()
                ->keyBy('id');

            // Get revenue data per witel
            $query = DB::table('cc_revenues');

            if ($dateRange['start'] && $dateRange['end']) {
                $startYear = Carbon::parse($dateRange['start'])->year;
                $startMonth = Carbon::parse($dateRange['start'])->month;
                $endMonth = Carbon::parse($dateRange['end'])->month;

                $query->where('tahun', $startYear)
                    ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $query->where('tahun', $this->getCurrentDataYear());
            }

            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            if (isset($filters['revenue_source']) && $filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            $revenueData = $query
                ->selectRaw('
                    CASE
                        WHEN witel_ho_id IS NOT NULL THEN witel_ho_id
                        ELSE witel_bill_id
                    END as witel_id,
                    COUNT(DISTINCT corporate_customer_id) as total_customers,
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target
                ')
                ->whereNotNull(DB::raw('CASE WHEN witel_ho_id IS NOT NULL THEN witel_ho_id ELSE witel_bill_id END'))
                ->groupBy(DB::raw('CASE WHEN witel_ho_id IS NOT NULL THEN witel_ho_id ELSE witel_bill_id END'))
                ->get()
                ->keyBy('witel_id');

            // Merge semua witel dengan revenue data
            $results = collect([]);

            foreach ($allWitels as $witelId => $witel) {
                $revenue = $revenueData->get($witelId);

                $totalRevenue = $revenue ? floatval($revenue->total_revenue) : 0;
                $totalTarget = $revenue ? floatval($revenue->total_target) : 0;
                $totalCustomers = $revenue ? intval($revenue->total_customers) : 0;

                $achievementRate = $totalTarget > 0
                    ? ($totalRevenue / $totalTarget) * 100
                    : 0;

                $results->push((object) [
                    'id' => $witelId,
                    'nama' => $witel->nama,
                    'total_customers' => $totalCustomers,
                    'total_revenue' => $totalRevenue,
                    'total_target' => $totalTarget,
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }

            // Sort by total_revenue DESC
            return $results->sortByDesc('total_revenue')->values();
        } catch (\Exception $e) {
            Log::error('Failed to get all witels', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * GET ALL SEGMENTS (NO LIMIT) - FIXED
     * ========================================
     */
    private function getAllSegments($dateRange, $filters)
    {
        try {
            // Get semua segment dari tabel segments
            $allSegments = DB::table('segments')
                ->select('id', 'lsegment_ho', 'divisi_id')
                ->orderBy('lsegment_ho', 'ASC')
                ->get()
                ->keyBy('id');

            // Get revenue data per segment
            $query = DB::table('cc_revenues');

            if ($dateRange['start'] && $dateRange['end']) {
                $startYear = Carbon::parse($dateRange['start'])->year;
                $startMonth = Carbon::parse($dateRange['start'])->month;
                $endMonth = Carbon::parse($dateRange['end'])->month;

                $query->where('tahun', $startYear)
                    ->whereBetween('bulan', [$startMonth, $endMonth]);
            } else {
                $query->where('tahun', $this->getCurrentDataYear());
            }

            if (isset($filters['divisi_id']) && $filters['divisi_id'] && $filters['divisi_id'] !== 'all') {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            if (isset($filters['revenue_source']) && $filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            $revenueData = $query
                ->select('segment_id')
                ->selectRaw('COUNT(DISTINCT corporate_customer_id) as total_customers')
                ->selectRaw('SUM(real_revenue) as total_revenue')
                ->selectRaw('SUM(target_revenue) as total_target')
                ->whereNotNull('segment_id')
                ->groupBy('segment_id')
                ->get()
                ->keyBy('segment_id');

            // Get divisi data
            $divisiData = DB::table('divisi')->get()->keyBy('id');

            // Merge semua segment dengan revenue data
            $results = collect([]);

            foreach ($allSegments as $segmentId => $segment) {
                $revenue = $revenueData->get($segmentId);
                $divisi = $divisiData->get($segment->divisi_id);

                $totalRevenue = $revenue ? floatval($revenue->total_revenue) : 0;
                $totalTarget = $revenue ? floatval($revenue->total_target) : 0;
                $totalCustomers = $revenue ? intval($revenue->total_customers) : 0;

                $achievementRate = $totalTarget > 0
                    ? ($totalRevenue / $totalTarget) * 100
                    : 0;

                $results->push((object) [
                    'id' => $segmentId,
                    'lsegment_ho' => $segment->lsegment_ho,
                    'nama' => $segment->lsegment_ho,
                    'divisi_nama' => $divisi ? $divisi->nama : 'N/A',
                    'total_customers' => $totalCustomers,
                    'total_revenue' => $totalRevenue,
                    'total_target' => $totalTarget,
                    'achievement_rate' => round($achievementRate, 2),
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }

            // Sort by total_revenue DESC
            return $results->sortByDesc('total_revenue')->values();
        } catch (\Exception $e) {
            Log::error('Failed to get all segments', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return collect([]);
        }
    }

    /**
     * ========================================
     * AJAX TAB DATA - FIXED
     * ========================================
     */
    public function getTabData(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);
            $tab = $request->get('tab');

            $data = $this->getAdminTabDataFixed($tab, $dateRange, $filters);

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => is_countable($data) ? count($data) : 0
            ]);
        } catch (\Exception $e) {
            Log::error('Tab data failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memuat data'], 500);
        }
    }

    private function getAdminTabDataFixed($tab, $dateRange, $filters)
    {
        switch ($tab) {
            case 'account_manager':
                return $this->getTop10AccountManagersWithPriority(null, $dateRange, $filters);
            case 'witel':
                return $this->getAllWitels($dateRange, $filters);
            case 'segment':
                return $this->getAllSegments($dateRange, $filters);
            case 'corporate_customer':
                return $this->getTop10CorporateCustomersWithPriority(null, $dateRange, $filters);
            default:
                throw new \InvalidArgumentException('Invalid tab');
        }
    }

    /**
     * ========================================
     * EXPORT
     * ========================================
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);
            $exportData = $this->prepareExportData($dateRange, $filters);
            $filename = $this->generateExportFilename($filters);

            return Excel::download(
                new AdminDashboardExport($exportData, $dateRange, $filters),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Export failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Export gagal');
        }
    }

    private function prepareExportData($dateRange, $filters)
    {
        return [
            'revenue_table' => $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'],
                $dateRange['end'],
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            ),
            'performance' => [
                'account_managers' => $this->getTop10AccountManagersWithPriority(null, $dateRange, $filters),
                'witels' => $this->getAllWitels($dateRange, $filters),
                'segments' => $this->getAllSegments($dateRange, $filters),
                'corporate_customers' => $this->getTop10CorporateCustomersWithPriority(null, $dateRange, $filters)
            ],
            'summary' => $this->revenueService->getTotalRevenueDataWithDateRange(
                null,
                $filters['divisi_id'],
                $dateRange['start'],
                $dateRange['end'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            )
        ];
    }

    private function generateExportFilename($filters)
    {
        $periodText = strtolower($filters['period_type']);
        $timestamp = date('Y-m-d_H-i-s');
        return "dashboard_export_{$periodText}_{$timestamp}.xlsx";
    }

    /**
     * ========================================
     * DETAIL PAGES
     * ========================================
     */
    public function showAccountManager($id)
    {
        try {
            $accountManager = AccountManager::with(['witel', 'divisis'])->findOrFail($id);
            $user = Auth::user();

            if ($user->role === 'account_manager' && $user->account_manager_id !== $id) {
                abort(403);
            }

            $currentYear = date('Y');
            $performanceData = $this->performanceService->getAMPerformanceSummary($id, $currentYear);
            $monthlyChart = $this->performanceService->getAMMonthlyChart($id, $currentYear);
            $customerPerformance = $this->performanceService->getAMCustomerPerformance($id, $currentYear);

            return view('am.detailAM', compact(
                'accountManager',
                'performanceData',
                'monthlyChart',
                'customerPerformance'
            ));
        } catch (\Exception $e) {
            Log::error('AM detail failed', ['error' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail AM');
        }
    }

    public function showWitel($id)
    {
        try {
            $witel = Witel::findOrFail($id);
            $user = Auth::user();

            if ($user->role === 'witel_support' && $user->witel_id !== $id) {
                abort(403);
            }

            $currentYear = date('Y');
            $witelData = $this->revenueService->getTotalRevenueData($id, null, $currentYear);
            $topAMs = $this->revenueService->getTopAccountManagers($id, 20, $currentYear);
            $categoryDistribution = $this->rankingService->getCategoryDistribution($id, $currentYear);

            return view('witel.detailWitel', compact('witel', 'witelData', 'topAMs', 'categoryDistribution'));
        } catch (\Exception $e) {
            Log::error('Witel detail failed', ['error' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail Witel');
        }
    }

    public function showCorporateCustomer($id)
    {
        try {
            $ccController = app(CcDashboardController::class);
            return $ccController->show($id, request());
        } catch (\Exception $e) {
            Log::error('CC detail failed', ['error' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail CC');
        }
    }

    public function showSegment($id)
    {
        try {
            $segment = Segment::with('divisi')->findOrFail($id);
            $currentYear = date('Y');

            $segmentData = CcRevenue::where('segment_id', $id)
                ->where('tahun', $currentYear)
                ->selectRaw('
                    COUNT(DISTINCT corporate_customer_id) as total_customers,
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target
                ')
                ->first();

            $topCustomers = CcRevenue::where('segment_id', $id)
                ->where('tahun', $currentYear)
                ->with('corporateCustomer')
                ->selectRaw('
                    corporate_customer_id,
                    SUM(real_revenue) as total_revenue,
                    SUM(target_revenue) as total_target
                ')
                ->groupBy('corporate_customer_id')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'segment' => [
                        'id' => $segment->id,
                        'nama' => $segment->lsegment_ho,
                        'divisi' => $segment->divisi->nama ?? 'N/A'
                    ],
                    'performance' => [
                        'total_customers' => $segmentData->total_customers ?? 0,
                        'total_revenue' => $segmentData->total_revenue ?? 0,
                        'total_target' => $segmentData->total_target ?? 0,
                        'achievement_rate' => $segmentData->total_target > 0
                            ? round(($segmentData->total_revenue / $segmentData->total_target) * 100, 2)
                            : 0
                    ],
                    'top_customers' => $topCustomers
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Segment detail failed', ['error' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Gagal memuat detail Segment');
        }
    }

    /**
     * ========================================
     * HELPER METHODS
     * ========================================
     */
    private function extractFiltersWithYtdMtd(Request $request)
    {
        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'divisi_id' => $request->get('divisi_id'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'tipe_revenue' => $request->get('tipe_revenue', 'all'),
            'sort_indicator' => $request->get('sort_indicator', 'total_revenue'),
            'active_tab' => $request->get('tab', 'account_manager')
        ];
    }

    private function calculateDateRange($periodType)
    {
        $now = Carbon::now();

        if ($periodType === 'MTD') {
            return [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfDay(),
                'type' => 'MTD'
            ];
        } else {
            return [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfDay(),
                'type' => 'YTD'
            ];
        }
    }

    private function generatePeriodText($periodType, $dateRange)
    {
        $startDate = $dateRange['start']->format('d M');
        $endDate = $dateRange['end']->format('d M Y');
        return "dari {$startDate} - {$endDate}";
    }

    private function getFilterOptionsForAdmin()
    {
        return [
            'period_types' => [
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
            ],
            'divisis' => Divisi::select('id', 'nama', 'kode')->orderBy('nama')->get(),
            'sort_indicators' => [
                'total_revenue' => 'Total Revenue Tertinggi',
                'achievement_rate' => 'Achievement Rate Tertinggi',
                'semua' => 'Semua (Revenue + Achievement)'
            ],
            'tipe_revenues' => [
                'all' => 'Semua Tipe',
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ],
            'revenue_sources' => [
                'all' => 'Semua Source',
                'HO' => 'HO Revenue',
                'BILL' => 'BILL Revenue'
            ]
        ];
    }

    private function getDefaultFilters()
    {
        return [
            'period_type' => 'YTD',
            'divisi_id' => null,
            'sort_indicator' => 'total_revenue',
            'tipe_revenue' => 'all',
            'revenue_source' => 'all',
            'active_tab' => 'account_manager'
        ];
    }

    private function getEmptyCardData()
    {
        return [
            'total_revenue' => 0,
            'total_target' => 0,
            'achievement_rate' => 0,
            'achievement_color' => 'secondary',
            'period_text' => 'Tidak ada data',
            'total_revenue_formatted' => '0',
            'total_target_formatted' => '0'
        ];
    }

    private function getEmptyPerformanceData()
    {
        return [
            'account_manager' => collect([]),
            'witel' => collect([]),
            'segment' => collect([]),
            'corporate_customer' => collect([])
        ];
    }

    private function addClickableUrls(&$performanceData)
    {
        if (isset($performanceData['account_manager'])) {
            $performanceData['account_manager']->each(function ($am) {
                $am->detail_url = route('account-manager.show', $am->id);
            });
        }

        if (isset($performanceData['witel'])) {
            $performanceData['witel']->each(function ($witel) {
                $witel->detail_url = route('witel.show', $witel->id);
            });
        }

        if (isset($performanceData['segment'])) {
            $performanceData['segment']->each(function ($segment) {
                $segment->detail_url = route('segment.show', $segment->id);
            });
        }

        if (isset($performanceData['corporate_customer'])) {
            $performanceData['corporate_customer']->each(function ($customer) {
                $customer->detail_url = route('corporate-customer.show', $customer->id);
            });
        }
    }

    private function getAchievementColor($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'success';
        } elseif ($achievementRate >= 80) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    private function getCurrentDataYear()
    {
        static $currentYear = null;

        if ($currentYear === null) {
            $currentYear = CcRevenue::max('tahun') ?? date('Y');
        }

        return $currentYear;
    }

    /**
     * ========================================
     * API METHODS
     * ========================================
     */
    public function getChartData(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $currentYear = date('Y');

            $monthlyRevenue = $this->revenueService->getMonthlyRevenue(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            $performanceDistribution = $this->performanceService->getPerformanceDistribution(
                $currentYear,
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            return response()->json([
                'success' => true,
                'monthly_data' => $monthlyRevenue,
                'performance_data' => $performanceDistribution
            ]);
        } catch (\Exception $e) {
            Log::error('Chart data failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load chart data'], 500);
        }
    }

    public function getRevenueTable(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $revenueTable = $this->revenueService->getRevenueTableDataWithDateRange(
                $dateRange['start'],
                $dateRange['end'],
                null,
                $filters['divisi_id'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            return response()->json([
                'success' => true,
                'data' => $revenueTable
            ]);
        } catch (\Exception $e) {
            Log::error('Revenue table failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load revenue table'], 500);
        }
    }

    public function getSummary(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $summary = $this->revenueService->getTotalRevenueDataWithDateRange(
                null,
                $filters['divisi_id'],
                $dateRange['start'],
                $dateRange['end'],
                $filters['revenue_source'],
                $filters['tipe_revenue']
            );

            $summary['period_text'] = $this->generatePeriodText($filters['period_type'], $dateRange);
            $summary['total_revenue_formatted'] = $this->formatCurrencyShort($summary['total_revenue'] ?? 0);
            $summary['total_target_formatted'] = $this->formatCurrencyShort($summary['total_target'] ?? 0);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Summary failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load summary'], 500);
        }
    }

    public function getPerformanceInsights(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $insights = [
                'total_insights' => 0,
                'insights' => [],
                'recommendations' => []
            ];

            return response()->json([
                'success' => true,
                'data' => $insights
            ]);
        } catch (\Exception $e) {
            Log::error('Performance insights failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load insights'], 500);
        }
    }

    /**
     * ========================================
     * AM DELEGATION METHODS
     * ========================================
     */
    public function getAmPerformance(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $amController = app(AmDashboardController::class);
            $accountManager = AccountManager::where('id', $user->account_manager_id)->first();

            if (!$accountManager) {
                return response()->json(['error' => 'Account Manager not found'], 404);
            }

            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $performanceSummary = $amController->getAmPerformanceSummary(
                $accountManager->id,
                $dateRange,
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $performanceSummary
            ]);
        } catch (\Exception $e) {
            Log::error('AM performance failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load AM performance'], 500);
        }
    }

    public function getAmCustomers(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $amController = app(AmDashboardController::class);
            $accountManager = AccountManager::where('id', $user->account_manager_id)->first();

            if (!$accountManager) {
                return response()->json(['error' => 'Account Manager not found'], 404);
            }

            $filters = $this->extractFiltersWithYtdMtd($request);
            $dateRange = $this->calculateDateRange($filters['period_type']);

            $corporateCustomers = $amController->getAmCorporateCustomers(
                $accountManager->id,
                $dateRange,
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $corporateCustomers
            ]);
        } catch (\Exception $e) {
            Log::error('AM customers failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load AM customers'], 500);
        }
    }

    public function exportAm(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'account_manager') {
            abort(403, 'Unauthorized export access');
        }

        try {
            $amController = app(AmDashboardController::class);
            return $amController->export($request);
        } catch (\Exception $e) {
            Log::error('AM export failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Export AM gagal');
        }
    }

    private function extractFilters(Request $request)
    {
        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'tipe_revenue' => $request->get('tipe_revenue', 'all')
        ];
    }
}