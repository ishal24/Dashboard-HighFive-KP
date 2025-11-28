<?php

namespace App\Services;

use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\AccountManager;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceAnalysisService
{
    /**
     * Get AM performance summary - Fixed for many-to-many divisi relation
     */
    public function getAMPerformanceSummary($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Handle divisi selection for multi-divisi AM
        $selectedDivisiId = $this->resolveAMDivisiId($accountManagerId, $divisiId);

        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        // Apply revenue source and tipe revenue filtering through cc_revenues relationship
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        // Get aggregated performance data
        $data = $query->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                MAX(real_revenue) as max_revenue,
                MIN(real_revenue) as min_revenue,
                AVG(real_revenue) as avg_revenue,
                COUNT(*) as total_months,
                MAX(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as max_achievement,
                AVG(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as avg_achievement
            ')
            ->first();

        // Get performance details
        $maxAchievementMonth = $this->getMaxAchievementMonth($accountManagerId, $tahun, $selectedDivisiId, $revenueSource, $tipeRevenue);
        $maxRevenueMonth = $this->getMaxRevenueMonth($accountManagerId, $tahun, $selectedDivisiId, $revenueSource, $tipeRevenue);

        // Calculate trend
        $trendData = $this->getTrendData($accountManagerId, $tahun, $selectedDivisiId, $revenueSource, $tipeRevenue);
        $trend = $this->calculateTrend($trendData);

        $achievementRate = $data->total_target > 0 ? ($data->total_revenue / $data->total_target) * 100 : 0;

        return [
            'total_revenue' => $data->total_revenue ?? 0,
            'total_target' => $data->total_target ?? 0,
            'achievement_rate' => round($achievementRate, 2),
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'max_achievement' => round($data->max_achievement ?? 0, 2),
            'max_achievement_month' => $maxAchievementMonth,
            'max_revenue' => $data->max_revenue ?? 0,
            'max_revenue_month' => $maxRevenueMonth,
            'avg_achievement' => round($data->avg_achievement ?? 0, 2),
            'avg_revenue' => round($data->avg_revenue ?? 0, 2),
            'trend' => $trend,
            'trend_icon' => $this->getTrendIcon($trend),
            'selected_divisi_id' => $selectedDivisiId,
            'divisi_context' => $this->getAMDivisiContext($accountManagerId)
        ];
    }

    /**
     * Get AM monthly chart data - Fixed for many-to-many divisi relation
     */
    public function getAMMonthlyChart($accountManagerId, $tahun = null, $chartMode = 'kombinasi', $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Handle divisi selection for multi-divisi AM
        $selectedDivisiId = $this->resolveAMDivisiId($accountManagerId, $divisiId);

        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        $data = $query->selectRaw('
                bulan,
                SUM(real_revenue) as real_revenue,
                SUM(target_revenue) as target_revenue,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) use ($chartMode) {
                $achievement = round($item->achievement, 2);
                $result = [
                    'month' => $item->bulan,
                    'month_name' => date('F', mktime(0, 0, 0, $item->bulan, 1)),
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];

                switch ($chartMode) {
                    case 'revenue':
                        $result['real_revenue'] = $item->real_revenue;
                        $result['target_revenue'] = $item->target_revenue;
                        break;

                    case 'achievement':
                        $result['achievement'] = $achievement;
                        break;

                    default: // kombinasi
                        $result['real_revenue'] = $item->real_revenue;
                        $result['target_revenue'] = $item->target_revenue;
                        $result['achievement'] = $achievement;
                        break;
                }

                return $result;
            });

        return $data;
    }

    /**
     * Get performance distribution chart for Admin/Witel - Fixed for many-to-many divisi
     */
    public function getPerformanceDistribution($tahun = null, $witelId = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Get valid AM IDs with proper many-to-many divisi filtering
        $amQuery = AccountManager::where('role', 'AM');

        if ($witelId) {
            $amQuery->where('witel_id', $witelId);
        }

        // Apply divisi filter using many-to-many relation
        if ($divisiId && $divisiId !== 'all') {
            $amQuery->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });
        }

        $validAMIds = $amQuery->pluck('id');

        if ($validAMIds->isEmpty()) {
            return collect([]);
        }

        // Get revenue data for valid AMs
        $revenueQuery = AmRevenue::where('tahun', $tahun)
            ->whereIn('account_manager_id', $validAMIds);

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($revenueQuery, $tahun, $revenueSource, $tipeRevenue);
        }

        $monthlyData = $revenueQuery->selectRaw('
                bulan,
                account_manager_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('bulan', 'account_manager_id')
            ->get()
            ->groupBy('bulan')
            ->map(function($monthData) {
                $achievements = $monthData->map(function($am) {
                    return $am->total_target > 0
                        ? ($am->total_revenue / $am->total_target) * 100
                        : 0;
                });

                return [
                    'excellent' => $achievements->filter(fn($a) => $a >= 100)->count(), // Hijau
                    'good' => $achievements->filter(fn($a) => $a >= 80 && $a < 100)->count(), // Oranye
                    'poor' => $achievements->filter(fn($a) => $a < 80)->count() // Merah
                ];
            });

        return $monthlyData;
    }

    /**
     * Get AM customer performance data - Fixed for many-to-many divisi
     */
    public function getAMCustomerPerformance($accountManagerId, $tahun = null, $mode = 'top', $limit = 10, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Handle divisi selection for multi-divisi AM
        $selectedDivisiId = $this->resolveAMDivisiId($accountManagerId, $divisiId);

        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        // Get customer data with aggregation
        $customerData = $query->with(['corporateCustomer', 'divisi'])
            ->selectRaw('
                corporate_customer_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement
            ')
            ->groupBy('corporate_customer_id');

        if ($mode === 'top') {
            $customerData = $customerData->orderBy('total_revenue', 'desc');
        } else {
            $customerData = $customerData->orderBy('achievement', 'asc');
        }

        return $customerData->limit($limit)->get()->map(function($item) {
            return (object) [
                'customer_name' => $item->corporateCustomer->nama ?? 'Unknown',
                'nipnas' => $item->corporateCustomer->nipnas ?? 'Unknown',
                'divisi_name' => $item->divisi->nama ?? 'Unknown',
                'total_revenue' => $item->total_revenue,
                'total_target' => $item->total_target,
                'achievement' => round($item->achievement, 2),
                'achievement_color' => $this->getAchievementColor($item->achievement)
            ];
        });
    }

    /**
     * Get comparative analysis - Fixed for many-to-many divisi
     */
    public function getComparativeAnalysis($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        $am = AccountManager::with(['witel', 'divisis'])->find($accountManagerId);
        if (!$am) {
            return null;
        }

        // AM performance
        $amPerformance = $this->getAMRevenueData($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        // Witel average - only include AMs with same divisi access
        $witelAverage = $this->getWitelAverage($am->witel_id, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        // Global average - only include AMs with same divisi access
        $globalAverage = $this->getGlobalAverage($tahun, $divisiId, $revenueSource, $tipeRevenue);

        $amAchievement = $amPerformance->total_target > 0
            ? ($amPerformance->total_revenue / $amPerformance->total_target) * 100
            : 0;

        return [
            'am' => [
                'total_revenue' => $amPerformance->total_revenue ?? 0,
                'total_target' => $amPerformance->total_target ?? 0,
                'achievement' => round($amAchievement, 2),
                'achievement_color' => $this->getAchievementColor($amAchievement)
            ],
            'witel_average' => $witelAverage,
            'global_average' => $globalAverage,
            'comparison' => [
                'vs_witel' => $this->getComparisonStatus($amAchievement, $witelAverage['avg_achievement']),
                'vs_global' => $this->getComparisonStatus($amAchievement, $globalAverage['avg_achievement'])
            ]
        ];
    }

    /**
     * Get year-over-year growth analysis - Fixed for many-to-many divisi
     */
    public function getYearOverYearGrowth($accountManagerId, $currentYear = null, $previousYear = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $currentYear = $currentYear ?? date('Y');
        $previousYear = $previousYear ?? ($currentYear - 1);

        $currentYearData = $this->getYearRevenueData($accountManagerId, $currentYear, $divisiId, $revenueSource, $tipeRevenue);
        $previousYearData = $this->getYearRevenueData($accountManagerId, $previousYear, $divisiId, $revenueSource, $tipeRevenue);

        $currentRevenue = $currentYearData->total_revenue ?? 0;
        $previousRevenue = $previousYearData->total_revenue ?? 0;

        $growth = $currentRevenue - $previousRevenue;
        $growthPercentage = $previousRevenue > 0 ? ($growth / $previousRevenue) * 100 : 0;

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'growth_amount' => $growth,
            'growth_percentage' => round($growthPercentage, 2),
            'trend' => $growth > 0 ? 'positive' : ($growth < 0 ? 'negative' : 'flat'),
            'trend_icon' => $this->getTrendIcon($growth > 0 ? 'naik' : ($growth < 0 ? 'turun' : 'stabil')),
            'growth_category' => $this->categorizeGrowth($growthPercentage)
        ];
    }

    /**
     * Get performance insights - Enhanced with multi-divisi context
     */
    public function getPerformanceInsights($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $summary = $this->getAMPerformanceSummary($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        $comparative = $this->getComparativeAnalysis($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        $divisiContext = $summary['divisi_context'];

        $insights = [];

        // Achievement analysis
        if ($summary['achievement_rate'] >= 100) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Target tercapai dengan baik! Achievement rate: ' . $summary['achievement_rate'] . '%',
                'icon' => 'fas fa-trophy',
                'priority' => 'high'
            ];
        } elseif ($summary['achievement_rate'] >= 80) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Mendekati target. Perlu sedikit peningkatan untuk mencapai 100%',
                'icon' => 'fas fa-chart-line',
                'priority' => 'medium'
            ];
        } else {
            $insights[] = [
                'type' => 'danger',
                'message' => 'Performa di bawah target. Diperlukan strategi peningkatan yang signifikan',
                'icon' => 'fas fa-exclamation-triangle',
                'priority' => 'high'
            ];
        }

        // Trend analysis
        switch ($summary['trend']) {
            case 'naik':
                $insights[] = [
                    'type' => 'info',
                    'message' => 'Trend positif! Performance menunjukkan peningkatan dalam 3 bulan terakhir',
                    'icon' => 'fas fa-arrow-up',
                    'priority' => 'low'
                ];
                break;
            case 'turun':
                $insights[] = [
                    'type' => 'warning',
                    'message' => 'Trend menurun. Perlu evaluasi strategi dan pendekatan',
                    'icon' => 'fas fa-arrow-down',
                    'priority' => 'high'
                ];
                break;
            default:
                $insights[] = [
                    'type' => 'info',
                    'message' => 'Performance stabil dalam 3 bulan terakhir',
                    'icon' => 'fas fa-minus',
                    'priority' => 'low'
                ];
                break;
        }

        // Multi-divisi insights
        if ($divisiContext['has_multiple_divisi']) {
            $insights[] = [
                'type' => 'info',
                'message' => 'AM mengelola ' . $divisiContext['divisi_count'] . ' divisi. Analisis ini berdasarkan ' .
                            ($summary['selected_divisi_id'] ? 'divisi terpilih' : 'semua divisi'),
                'icon' => 'fas fa-sitemap',
                'priority' => 'medium'
            ];
        }

        // Comparative analysis
        if ($comparative && isset($comparative['witel_average'])) {
            if ($summary['achievement_rate'] > $comparative['witel_average']['avg_achievement']) {
                $insights[] = [
                    'type' => 'success',
                    'message' => 'Performance di atas rata-rata Witel (' .
                                round($comparative['witel_average']['avg_achievement'], 1) . '%)',
                    'icon' => 'fas fa-star',
                    'priority' => 'medium'
                ];
            } else {
                $insights[] = [
                    'type' => 'info',
                    'message' => 'Performance di bawah rata-rata Witel. Ada potensi untuk improvement',
                    'icon' => 'fas fa-info-circle',
                    'priority' => 'medium'
                ];
            }
        }

        // Add revenue consistency insight
        $consistency = $this->analyzeRevenueConsistency($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        if ($consistency['coefficient_variation'] < 30) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Revenue konsisten sepanjang tahun (variasi ' . round($consistency['coefficient_variation'], 1) . '%)',
                'icon' => 'fas fa-chart-bar',
                'priority' => 'low'
            ];
        } elseif ($consistency['coefficient_variation'] > 50) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Revenue fluktuatif (variasi ' . round($consistency['coefficient_variation'], 1) . '%). Perlu strategi stabilisasi',
                'icon' => 'fas fa-chart-area',
                'priority' => 'medium'
            ];
        }

        return [
            'insights' => $insights,
            'summary_stats' => [
                'total_insights' => count($insights),
                'high_priority' => collect($insights)->where('priority', 'high')->count(),
                'positive_insights' => collect($insights)->whereIn('type', ['success', 'info'])->count()
            ],
            'action_items' => $this->generateActionItems($insights, $summary, $comparative)
        ];
    }

    /**
     * Get chart mode options
     */
    public function getChartModeOptions()
    {
        return [
            'kombinasi' => 'Revenue + Achievement',
            'revenue' => 'Revenue Saja',
            'achievement' => 'Achievement Saja'
        ];
    }

    /**
     * Analyze revenue consistency
     */
    public function analyzeRevenueConsistency($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        $monthlyRevenues = $query->selectRaw('
                bulan,
                SUM(real_revenue) as monthly_revenue
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('monthly_revenue')
            ->toArray();

        if (count($monthlyRevenues) < 2) {
            return [
                'coefficient_variation' => 0,
                'consistency_level' => 'insufficient_data',
                'recommendation' => 'Butuh lebih banyak data untuk analisis konsistensi'
            ];
        }

        $mean = array_sum($monthlyRevenues) / count($monthlyRevenues);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $monthlyRevenues)) / count($monthlyRevenues);

        $standardDeviation = sqrt($variance);
        $coefficientVariation = $mean > 0 ? ($standardDeviation / $mean) * 100 : 0;

        return [
            'coefficient_variation' => $coefficientVariation,
            'standard_deviation' => $standardDeviation,
            'mean_revenue' => $mean,
            'consistency_level' => $this->getConsistencyLevel($coefficientVariation),
            'recommendation' => $this->getConsistencyRecommendation($coefficientVariation)
        ];
    }

    /**
     * Private Helper Methods
     */

    /**
     * Resolve divisi ID for AM - handles many-to-many relation
     */
    private function resolveAMDivisiId($accountManagerId, $requestedDivisiId = null)
    {
        $am = AccountManager::with('divisis')->find($accountManagerId);

        if (!$am || $am->divisis->isEmpty()) {
            return null;
        }

        // If specific divisi requested and AM has access to it
        if ($requestedDivisiId && $am->divisis->contains('id', $requestedDivisiId)) {
            return $requestedDivisiId;
        }

        // Default to primary divisi
        $primaryDivisi = $am->divisis->where('pivot.is_primary', 1)->first();
        if ($primaryDivisi) {
            return $primaryDivisi->id;
        }

        // No default selection for multi-divisi AM - return null to include all
        return null;
    }

    /**
     * Get AM divisi context
     */
    private function getAMDivisiContext($accountManagerId)
    {
        $am = AccountManager::with('divisis')->find($accountManagerId);

        if (!$am || $am->divisis->isEmpty()) {
            return [
                'has_divisi' => false,
                'divisi_count' => 0,
                'primary_divisi' => null,
                'all_divisi' => collect([]),
                'has_multiple_divisi' => false
            ];
        }

        $primaryDivisi = $am->divisis->where('pivot.is_primary', 1)->first();

        return [
            'has_divisi' => true,
            'divisi_count' => $am->divisis->count(),
            'primary_divisi' => $primaryDivisi ? [
                'id' => $primaryDivisi->id,
                'nama' => $primaryDivisi->nama,
                'kode' => $primaryDivisi->kode
            ] : null,
            'all_divisi' => $am->divisis->map(function($divisi) {
                return [
                    'id' => $divisi->id,
                    'nama' => $divisi->nama,
                    'kode' => $divisi->kode,
                    'is_primary' => $divisi->pivot->is_primary
                ];
            }),
            'has_multiple_divisi' => $am->divisis->count() > 1
        ];
    }

    /**
     * Check if CC revenue filtering is needed
     */
    private function needsCcRevenueFilter($revenueSource, $tipeRevenue)
    {
        return ($revenueSource && $revenueSource !== 'all') ||
               ($tipeRevenue && $tipeRevenue !== 'all');
    }

    /**
     * Apply CC revenue filter using exists subquery
     */
    private function applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue)
    {
        $query->whereExists(function($subquery) use ($tahun, $revenueSource, $tipeRevenue) {
            $subquery->select(DB::raw(1))
                    ->from('cc_revenues')
                    ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id')
                    ->where('cc_revenues.tahun', $tahun);

            if ($revenueSource && $revenueSource !== 'all') {
                $subquery->where('cc_revenues.revenue_source', $revenueSource);
            }

            if ($tipeRevenue && $tipeRevenue !== 'all') {
                $subquery->where('cc_revenues.tipe_revenue', $tipeRevenue);
            }
        });
    }

    private function getMaxAchievementMonth($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun)
            ->where('target_revenue', '>', 0);

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        $result = $query->selectRaw('bulan, (real_revenue / target_revenue) * 100 as achievement')
            ->orderBy('achievement', 'desc')
            ->first();

        return $result ? date('F', mktime(0, 0, 0, $result->bulan, 1)) : null;
    }

    private function getMaxRevenueMonth($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        $result = $query->orderBy('real_revenue', 'desc')->first();
        return $result ? date('F', mktime(0, 0, 0, $result->bulan, 1)) : null;
    }

    private function getTrendData($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun)
            ->where('bulan', '>=', max(1, date('n') - 2));

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        return $query->orderBy('bulan')->pluck('real_revenue');
    }

    private function getAMRevenueData($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        return $query->selectRaw('SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target')->first();
    }

    private function getWitelAverage($witelId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get AMs in the same witel with proper divisi filtering
        $amQuery = AccountManager::where('role', 'AM')
            ->where('witel_id', $witelId);

        if ($divisiId && $divisiId !== 'all') {
            $amQuery->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });
        }

        $amIds = $amQuery->pluck('id');

        if ($amIds->isEmpty()) {
            return [
                'avg_revenue' => 0,
                'avg_achievement' => 0,
                'achievement_color' => 'secondary'
            ];
        }

        $query = AmRevenue::whereIn('account_manager_id', $amIds)
            ->where('tahun', $tahun);

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        $result = $query->selectRaw('
                AVG(real_revenue) as avg_revenue,
                AVG(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as avg_achievement
            ')
            ->first();

        return [
            'avg_revenue' => round($result->avg_revenue ?? 0, 2),
            'avg_achievement' => round($result->avg_achievement ?? 0, 2),
            'achievement_color' => $this->getAchievementColor($result->avg_achievement ?? 0)
        ];
    }

    private function getGlobalAverage($tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get all AMs with proper divisi filtering
        $amQuery = AccountManager::where('role', 'AM');

        if ($divisiId && $divisiId !== 'all') {
            $amQuery->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });
        }

        $amIds = $amQuery->pluck('id');

        if ($amIds->isEmpty()) {
            return [
                'avg_revenue' => 0,
                'avg_achievement' => 0,
                'achievement_color' => 'secondary'
            ];
        }

        $query = AmRevenue::whereIn('account_manager_id', $amIds)
            ->where('tahun', $tahun);

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        $result = $query->selectRaw('
                AVG(real_revenue) as avg_revenue,
                AVG(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as avg_achievement
            ')
            ->first();

        return [
            'avg_revenue' => round($result->avg_revenue ?? 0, 2),
            'avg_achievement' => round($result->avg_achievement ?? 0, 2),
            'achievement_color' => $this->getAchievementColor($result->avg_achievement ?? 0)
        ];
    }

    private function getYearRevenueData($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        return $this->getAMRevenueData($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
    }

    private function calculateTrend($dataPoints)
    {
        if ($dataPoints->count() < 2) {
            return 'stabil';
        }

        $first = $dataPoints->first();
        $last = $dataPoints->last();

        if ($last > $first * 1.1) {
            return 'naik';
        } elseif ($last < $first * 0.9) {
            return 'turun';
        } else {
            return 'stabil';
        }
    }

    private function getAchievementColor($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'success';  // Hijau: ≥100%
        } elseif ($achievementRate >= 80) {
            return 'warning';  // Oranye: 80-99%
        } else {
            return 'danger';   // Merah: <80%
        }
    }

    private function getTrendIcon($trend)
    {
        switch ($trend) {
            case 'naik':
                return 'fas fa-arrow-up text-success';
            case 'turun':
                return 'fas fa-arrow-down text-danger';
            default:
                return 'fas fa-minus text-muted';
        }
    }

    private function getComparisonStatus($amAchievement, $benchmarkAchievement)
    {
        $diff = $amAchievement - $benchmarkAchievement;

        if ($diff > 5) {
            return [
                'status' => 'above',
                'difference' => round($diff, 2),
                'icon' => 'fas fa-arrow-up text-success',
                'message' => 'Di atas rata-rata'
            ];
        } elseif ($diff < -5) {
            return [
                'status' => 'below',
                'difference' => round(abs($diff), 2),
                'icon' => 'fas fa-arrow-down text-danger',
                'message' => 'Di bawah rata-rata'
            ];
        } else {
            return [
                'status' => 'similar',
                'difference' => round(abs($diff), 2),
                'icon' => 'fas fa-minus text-muted',
                'message' => 'Sekitar rata-rata'
            ];
        }
    }

    private function categorizeGrowth($growthPercentage)
    {
        if ($growthPercentage > 20) {
            return 'excellent';
        } elseif ($growthPercentage > 10) {
            return 'good';
        } elseif ($growthPercentage > 0) {
            return 'moderate';
        } elseif ($growthPercentage > -10) {
            return 'slight_decline';
        } else {
            return 'significant_decline';
        }
    }

    private function getConsistencyLevel($coefficientVariation)
    {
        if ($coefficientVariation < 20) {
            return 'very_consistent';
        } elseif ($coefficientVariation < 30) {
            return 'consistent';
        } elseif ($coefficientVariation < 50) {
            return 'moderate';
        } else {
            return 'volatile';
        }
    }

    private function getConsistencyRecommendation($coefficientVariation)
    {
        if ($coefficientVariation < 20) {
            return 'Revenue sangat konsisten. Pertahankan strategi yang ada.';
        } elseif ($coefficientVariation < 30) {
            return 'Revenue cukup konsisten. Monitor secara berkala.';
        } elseif ($coefficientVariation < 50) {
            return 'Revenue cukup bervariasi. Pertimbangkan strategi diversifikasi customer.';
        } else {
            return 'Revenue sangat fluktuatif. Perlu strategi stabilisasi dan diversifikasi risiko.';
        }
    }

    private function generateActionItems($insights, $summary, $comparative)
    {
        $actionItems = [];

        // Achievement-based actions
        if ($summary['achievement_rate'] < 80) {
            $actionItems[] = [
                'priority' => 'high',
                'category' => 'performance',
                'title' => 'Tingkatkan Achievement Rate',
                'description' => 'Focus pada peningkatan revenue untuk mencapai target minimal 80%',
                'deadline' => 'Next month'
            ];
        }

        // Trend-based actions
        if ($summary['trend'] === 'turun') {
            $actionItems[] = [
                'priority' => 'high',
                'category' => 'strategy',
                'title' => 'Evaluasi Strategi',
                'description' => 'Tinjau ulang pendekatan sales dan customer engagement',
                'deadline' => 'Within 2 weeks'
            ];
        }

        // Consistency-based actions
        $highPriorityInsights = collect($insights)->where('priority', 'high')->count();
        if ($highPriorityInsights > 2) {
            $actionItems[] = [
                'priority' => 'medium',
                'category' => 'review',
                'title' => 'Performance Review Meeting',
                'description' => 'Schedule meeting dengan supervisor untuk membahas performance issues',
                'deadline' => 'Within 1 week'
            ];
        }

        // Comparative-based actions
        if ($comparative && isset($comparative['witel_average'])) {
            if ($summary['achievement_rate'] < $comparative['witel_average']['avg_achievement']) {
                $actionItems[] = [
                    'priority' => 'medium',
                    'category' => 'benchmarking',
                    'title' => 'Benchmark Best Practices',
                    'description' => 'Pelajari strategi dari AM top performer di witel yang sama',
                    'deadline' => 'Next month'
                ];
            }
        }

        return $actionItems;
    }

    /**
     * Get performance benchmarks for different achievement levels
     */
    public function getPerformanceBenchmarks()
    {
        return [
            'excellent' => [
                'achievement_min' => 120,
                'color' => 'success',
                'label' => 'Excellent (≥120%)',
                'description' => 'Performance sangat baik, melebihi ekspektasi'
            ],
            'good' => [
                'achievement_min' => 100,
                'achievement_max' => 119,
                'color' => 'success',
                'label' => 'Good (100-119%)',
                'description' => 'Target tercapai dengan baik'
            ],
            'satisfactory' => [
                'achievement_min' => 80,
                'achievement_max' => 99,
                'color' => 'warning',
                'label' => 'Satisfactory (80-99%)',
                'description' => 'Mendekati target, perlu sedikit peningkatan'
            ],
            'needs_improvement' => [
                'achievement_min' => 60,
                'achievement_max' => 79,
                'color' => 'danger',
                'label' => 'Needs Improvement (60-79%)',
                'description' => 'Perlu peningkatan signifikan'
            ],
            'poor' => [
                'achievement_max' => 59,
                'color' => 'danger',
                'label' => 'Poor (<60%)',
                'description' => 'Performance di bawah standar, perlu intervensi'
            ]
        ];
    }

    /**
     * Get performance rating based on achievement
     */
    public function getPerformanceRating($achievementRate)
    {
        $benchmarks = $this->getPerformanceBenchmarks();

        if ($achievementRate >= 120) {
            return $benchmarks['excellent'];
        } elseif ($achievementRate >= 100) {
            return $benchmarks['good'];
        } elseif ($achievementRate >= 80) {
            return $benchmarks['satisfactory'];
        } elseif ($achievementRate >= 60) {
            return $benchmarks['needs_improvement'];
        } else {
            return $benchmarks['poor'];
        }
    }

    /**
     * Get detailed performance metrics for reporting
     */
    public function getDetailedPerformanceMetrics($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? date('Y');

        // Get basic performance summary
        $summary = $this->getAMPerformanceSummary($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        // Get additional metrics
        $consistency = $this->analyzeRevenueConsistency($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        $yearOverYear = $this->getYearOverYearGrowth($accountManagerId, $tahun, $tahun - 1, $divisiId, $revenueSource, $tipeRevenue);
        $comparative = $this->getComparativeAnalysis($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);
        $rating = $this->getPerformanceRating($summary['achievement_rate']);

        // Get monthly breakdown
        $monthlyData = $this->getAMMonthlyChart($accountManagerId, $tahun, 'kombinasi', $divisiId, $revenueSource, $tipeRevenue);

        // Calculate additional KPIs
        $kpis = $this->calculateKPIs($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        return [
            'summary' => $summary,
            'rating' => $rating,
            'consistency' => $consistency,
            'year_over_year' => $yearOverYear,
            'comparative' => $comparative,
            'monthly_data' => $monthlyData,
            'kpis' => $kpis,
            'generated_at' => now(),
            'period' => [
                'year' => $tahun,
                'divisi_id' => $divisiId,
                'revenue_source' => $revenueSource,
                'tipe_revenue' => $tipeRevenue
            ]
        ];
    }

    /**
     * Calculate Key Performance Indicators (KPIs)
     */
    private function calculateKPIs($accountManagerId, $tahun, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId)
            ->where('tahun', $tahun);

        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $tahun, $revenueSource, $tipeRevenue);
        }

        // Get monthly data for calculations
        $monthlyData = $query->selectRaw('
                bulan,
                SUM(real_revenue) as monthly_revenue,
                SUM(target_revenue) as monthly_target,
                COUNT(DISTINCT corporate_customer_id) as monthly_customers
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        if ($monthlyData->isEmpty()) {
            return [
                'revenue_per_customer' => 0,
                'target_achievement_months' => 0,
                'consistency_score' => 0,
                'growth_trajectory' => 'no_data'
            ];
        }

        // Calculate KPIs
        $totalRevenue = $monthlyData->sum('monthly_revenue');
        $totalCustomers = $monthlyData->max('monthly_customers'); // Use max to avoid double counting

        $revenuePerCustomer = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

        $targetAchievementMonths = $monthlyData->filter(function($month) {
            return $month->monthly_target > 0 &&
                   ($month->monthly_revenue / $month->monthly_target) >= 1.0;
        })->count();

        // Calculate growth trajectory
        $revenues = $monthlyData->pluck('monthly_revenue')->toArray();
        $growthTrajectory = $this->calculateGrowthTrajectory($revenues);

        return [
            'revenue_per_customer' => round($revenuePerCustomer, 2),
            'target_achievement_months' => $targetAchievementMonths,
            'total_months_with_data' => $monthlyData->count(),
            'achievement_month_percentage' => $monthlyData->count() > 0
                ? round(($targetAchievementMonths / $monthlyData->count()) * 100, 2)
                : 0,
            'growth_trajectory' => $growthTrajectory,
            'best_month' => [
                'month' => $monthlyData->sortByDesc('monthly_revenue')->first()?->bulan,
                'revenue' => $monthlyData->max('monthly_revenue')
            ],
            'worst_month' => [
                'month' => $monthlyData->sortBy('monthly_revenue')->first()?->bulan,
                'revenue' => $monthlyData->min('monthly_revenue')
            ]
        ];
    }

    /**
     * Calculate growth trajectory from revenue data
     */
    private function calculateGrowthTrajectory($revenues)
    {
        if (count($revenues) < 3) {
            return 'insufficient_data';
        }

        $n = count($revenues);
        $increases = 0;
        $decreases = 0;

        for ($i = 1; $i < $n; $i++) {
            if ($revenues[$i] > $revenues[$i-1]) {
                $increases++;
            } elseif ($revenues[$i] < $revenues[$i-1]) {
                $decreases++;
            }
        }

        $increaseRatio = $increases / ($n - 1);

        if ($increaseRatio >= 0.7) {
            return 'strong_growth';
        } elseif ($increaseRatio >= 0.5) {
            return 'moderate_growth';
        } elseif ($increaseRatio >= 0.3) {
            return 'mixed';
        } elseif ($increaseRatio >= 0.1) {
            return 'moderate_decline';
        } else {
            return 'strong_decline';
        }
    }

    /**
     * Get performance recommendations based on analysis
     */
    public function getPerformanceRecommendations($accountManagerId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $metrics = $this->getDetailedPerformanceMetrics($accountManagerId, $tahun, $divisiId, $revenueSource, $tipeRevenue);

        $recommendations = [];

        // Achievement-based recommendations
        $achievementRate = $metrics['summary']['achievement_rate'];
        if ($achievementRate < 60) {
            $recommendations[] = [
                'category' => 'urgent',
                'title' => 'Performance Improvement Plan',
                'description' => 'Segera implementasikan rencana perbaikan performance dengan target jangka pendek',
                'actions' => [
                    'Review portfolio customer dan identifikasi peluang upselling',
                    'Analisis kompetitor dan adjust pricing strategy',
                    'Meeting mingguan dengan supervisor untuk monitoring progress'
                ]
            ];
        } elseif ($achievementRate < 80) {
            $recommendations[] = [
                'category' => 'improvement',
                'title' => 'Focus pada Gap Closing',
                'description' => 'Identifikasi dan tutup gap untuk mencapai target minimal 80%',
                'actions' => [
                    'Prioritaskan customer dengan potensi revenue tertinggi',
                    'Tingkatkan frequency customer visit dan engagement',
                    'Review dan optimasi sales process'
                ]
            ];
        }

        // Consistency-based recommendations
        if ($metrics['consistency']['consistency_level'] === 'volatile') {
            $recommendations[] = [
                'category' => 'stability',
                'title' => 'Stabilisasi Revenue',
                'description' => 'Fokus pada konsistensi performance untuk mengurangi volatilitas',
                'actions' => [
                    'Diversifikasi portfolio customer untuk mengurangi risiko',
                    'Develop long-term contract dengan key customers',
                    'Implementasi predictable revenue model'
                ]
            ];
        }

        // Growth trajectory recommendations
        switch ($metrics['kpis']['growth_trajectory']) {
            case 'strong_decline':
            case 'moderate_decline':
                $recommendations[] = [
                    'category' => 'turnaround',
                    'title' => 'Revenue Recovery Strategy',
                    'description' => 'Implement strategi pemulihan revenue dengan fokus pada root cause analysis',
                    'actions' => [
                        'Conduct deep-dive analysis untuk identifikasi penyebab penurunan',
                        'Reactive customer retention program',
                        'Explore new market segments atau product offerings'
                    ]
                ];
                break;

            case 'strong_growth':
                $recommendations[] = [
                    'category' => 'optimization',
                    'title' => 'Sustain Growth Momentum',
                    'description' => 'Pertahankan dan optimalkan growth trajectory yang positif',
                    'actions' => [
                        'Document dan replicate successful strategies',
                        'Scale up proven approaches ke customer segments lain',
                        'Consider target revision untuk challenge yang lebih tinggi'
                    ]
                ];
                break;
        }

        // Comparative recommendations
        if ($metrics['comparative'] &&
            $metrics['summary']['achievement_rate'] < $metrics['comparative']['witel_average']['avg_achievement']) {

            $recommendations[] = [
                'category' => 'benchmarking',
                'title' => 'Learn from Best Practices',
                'description' => 'Pelajari dan adopt best practices dari top performers',
                'actions' => [
                    'Identify top performers di witel untuk knowledge sharing session',
                    'Benchmark sales methodology dan customer approach',
                    'Join peer learning groups atau mentoring programs'
                ]
            ];
        }

        return [
            'recommendations' => $recommendations,
            'priority_count' => [
                'urgent' => collect($recommendations)->where('category', 'urgent')->count(),
                'high' => collect($recommendations)->whereIn('category', ['improvement', 'turnaround'])->count(),
                'medium' => collect($recommendations)->whereIn('category', ['stability', 'benchmarking'])->count(),
                'low' => collect($recommendations)->where('category', 'optimization')->count()
            ],
            'total_recommendations' => count($recommendations)
        ];
    }

    /**
     * Export performance data for reporting
     */
    public function exportPerformanceReport($accountManagerId, $tahun = null, $divisiId = null, $format = 'array')
    {
        $metrics = $this->getDetailedPerformanceMetrics($accountManagerId, $tahun, $divisiId);
        $insights = $this->getPerformanceInsights($accountManagerId, $tahun, $divisiId);
        $recommendations = $this->getPerformanceRecommendations($accountManagerId, $tahun, $divisiId);

        $report = [
            'report_info' => [
                'account_manager_id' => $accountManagerId,
                'year' => $tahun ?? date('Y'),
                'divisi_id' => $divisiId,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'report_type' => 'performance_analysis'
            ],
            'executive_summary' => [
                'achievement_rate' => $metrics['summary']['achievement_rate'],
                'rating' => $metrics['rating']['label'],
                'total_revenue' => $metrics['summary']['total_revenue'],
                'total_target' => $metrics['summary']['total_target'],
                'trend' => $metrics['summary']['trend']
            ],
            'detailed_metrics' => $metrics,
            'insights' => $insights,
            'recommendations' => $recommendations
        ];

        if ($format === 'json') {
            return json_encode($report, JSON_PRETTY_PRINT);
        }

        return $report;
    }
}