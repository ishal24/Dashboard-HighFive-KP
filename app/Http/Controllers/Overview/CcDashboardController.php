<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Models\CorporateCustomer;
use App\Models\CcRevenue;
use App\Models\Divisi;
use App\Models\Segment;
use App\Models\Witel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CcDashboardController extends Controller
{
    /**
     * Show Corporate Customer Detail Dashboard
     */
    public function show($id, Request $request)
    {
        try {
            // FIXED: Load dengan latestAmRevenue untuk eager loading
            $corporateCustomer = CorporateCustomer::with(['latestAmRevenue.accountManager'])
                                                   ->findOrFail($id);

            $filters = $this->extractFilters($request);

            // Get latest revenue data
            $latestRevenue = CcRevenue::where('corporate_customer_id', $id)
                ->with(['divisi', 'segment', 'witelHo', 'witelBill'])
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->first();

            // Profile Data - FIXED: Gunakan accessor
            $profileData = [
                'id' => $corporateCustomer->id,
                'nama' => $corporateCustomer->nama,
                'nipnas' => $corporateCustomer->nipnas,
                'divisi' => $latestRevenue ? $latestRevenue->divisi : null,
                'segment' => $latestRevenue ? $latestRevenue->segment : null,
                'account_manager' => $corporateCustomer->primary_account_manager, // FIXED: Gunakan accessor
                'witel' => $latestRevenue ? $latestRevenue->witelHo : null
            ];

            // Summary Cards
            $cardData = $this->getCardGroupData($id, $filters);

            // Revenue Table Data
            $revenueData = $this->getRevenueTabData($id, $filters);

            // Revenue Analysis
            $revenueAnalysis = $this->getRevenueAnalysisData($id, $filters);

            // Filter Options
            $filterOptions = $this->getFilterOptions($id);

            Log::info('CC dashboard loaded successfully', [
                'cc_id' => $id,
                'customer_name' => $corporateCustomer->nama
            ]);

            return view('cc.detailCC', compact(
                'corporateCustomer',
                'profileData',
                'cardData',
                'revenueData',
                'revenueAnalysis',
                'filterOptions',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('CC dashboard rendering failed', [
                'cc_id' => $id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat detail Corporate Customer: ' . $e->getMessage());
        }
    }

    /**
     * Extract filters
     */
    private function extractFilters(Request $request)
    {
        $defaultTahun = $request->get('tahun', date('Y'));
        $defaultBulanEnd = date('n');

        return [
            'period_type' => $request->get('period_type', 'YTD'),
            'tahun' => $defaultTahun,
            'tipe_revenue' => $request->get('tipe_revenue', 'all'),
            'revenue_source' => $request->get('revenue_source', 'all'),
            'revenue_view_mode' => $request->get('revenue_view_mode', 'detail'),
            'granularity' => $request->get('granularity', 'divisi'), // NEW: divisi, segment, account_manager
            'bulan_start' => $request->get('bulan_start', 1),
            'bulan_end' => $request->get('bulan_end', $defaultBulanEnd),
            'chart_tahun' => $request->get('chart_tahun', $defaultTahun),
            'chart_display' => $request->get('chart_display', 'combination'),
            'active_tab' => $request->get('active_tab', 'revenue')
        ];
    }

    /**
     * Get Card Summary Data
     */
    private function getCardGroupData($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        if ($filters['period_type'] === 'MTD') {
            $query->where('bulan', $filters['bulan_end']);
        } else {
            $query->where('bulan', '<=', $filters['bulan_end']);
        }

        $aggregated = $query->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                COUNT(DISTINCT bulan) as month_count
            ')
            ->first();

        $totalRevenue = $aggregated->total_revenue ?? 0;
        $totalTarget = $aggregated->total_target ?? 0;
        $achievementRate = $totalTarget > 0
            ? round(($totalRevenue / $totalTarget) * 100, 2)
            : 0;

        return [
            'total_revenue' => floatval($totalRevenue),
            'total_target' => floatval($totalTarget),
            'achievement_rate' => $achievementRate,
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'month_count' => intval($aggregated->month_count ?? 0),
            'period_text' => $this->generatePeriodText($filters)
        ];
    }

    /**
     * Get Revenue Tab Data
     */
    private function getRevenueTabData($ccId, $filters)
    {
        $viewMode = $filters['revenue_view_mode'];
        $granularity = $filters['granularity'];

        $availableYears = CcRevenue::where('corporate_customer_id', $ccId)
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        switch ($viewMode) {
            case 'agregat_bulan':
                if ($granularity === 'account_manager') {
                    $revenueData = $this->getRevenueDataAggregateByMonthAndAM($ccId, $filters);
                } elseif ($granularity === 'segment') {
                    $revenueData = $this->getRevenueDataAggregateByMonthAndSegment($ccId, $filters);
                } else {
                    $revenueData = $this->getRevenueDataAggregateByMonth($ccId, $filters);
                }
                break;
            case 'detail':
            default:
                if ($granularity === 'account_manager') {
                    $revenueData = $this->getRevenueDataDetailByAM($ccId, $filters);
                } elseif ($granularity === 'segment') {
                    $revenueData = $this->getRevenueDataDetailBySegment($ccId, $filters);
                } else {
                    $revenueData = $this->getRevenueDataDetail($ccId, $filters);
                }
                break;
        }

        return [
            'view_mode' => $viewMode,
            'granularity' => $granularity,
            'revenues' => $revenueData,
            'available_years' => $availableYears,
            'use_year_picker' => count($availableYears) > 10,
            'tahun' => $filters['tahun'],
            'tipe_revenue' => $filters['tipe_revenue'],
            'revenue_source' => $filters['revenue_source']
        ];
    }

    /**
     * Get Revenue Data Detail (Original - by Divisi)
     */
    private function getRevenueDataDetail($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun'])
            ->with(['divisi', 'segment', 'witelHo', 'witelBill']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $detailData = $query->orderBy('bulan')->get();

        return $detailData->map(function($item) {
            $achievementRate = $item->target_revenue > 0
                ? round(($item->real_revenue / $item->target_revenue) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'divisi' => $item->divisi ? $item->divisi->nama : 'N/A',
                'segment' => $item->segment ? $item->segment->lsegment_ho : 'N/A',
                'revenue_source' => $item->revenue_source,
                'tipe_revenue' => $item->tipe_revenue,
                'witel_ho' => $item->witelHo ? $item->witelHo->nama : 'N/A',
                'witel_bill' => $item->witelBill ? $item->witelBill->nama : 'N/A',
                'revenue' => floatval($item->real_revenue),
                'target' => floatval($item->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * NEW: Get Revenue Data Detail by Account Manager
     */
    private function getRevenueDataDetailByAM($ccId, $filters)
    {
        // FIXED: Load CC dengan latestAmRevenue
        $corporateCustomer = CorporateCustomer::with('latestAmRevenue.accountManager')->find($ccId);

        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun'])
            ->with(['divisi', 'segment', 'witelHo', 'witelBill']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $detailData = $query->orderBy('bulan')->get();

        return $detailData->map(function($item) use ($corporateCustomer) {
            $achievementRate = $item->target_revenue > 0
                ? round(($item->real_revenue / $item->target_revenue) * 100, 2)
                : 0;

            // FIXED: Gunakan accessor
            $primaryAM = $corporateCustomer->primary_account_manager;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'account_manager' => $primaryAM ? $primaryAM->nama : 'N/A',
                'nik' => $primaryAM ? $primaryAM->nik : 'N/A',
                'divisi' => $item->divisi ? $item->divisi->nama : 'N/A',
                'segment' => $item->segment ? $item->segment->lsegment_ho : 'N/A',
                'revenue_source' => $item->revenue_source,
                'tipe_revenue' => $item->tipe_revenue,
                'revenue' => floatval($item->real_revenue),
                'target' => floatval($item->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * NEW: Get Revenue Data Detail by Segment
     */
    private function getRevenueDataDetailBySegment($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun'])
            ->with(['segment', 'divisi', 'witelHo', 'witelBill']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $detailData = $query->orderBy('bulan')->get();

        return $detailData->map(function($item) {
            $achievementRate = $item->target_revenue > 0
                ? round(($item->real_revenue / $item->target_revenue) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'segment' => $item->segment ? $item->segment->lsegment_ho : 'N/A',
                'divisi' => $item->divisi ? $item->divisi->nama : 'N/A',
                'revenue_source' => $item->revenue_source,
                'tipe_revenue' => $item->tipe_revenue,
                'revenue' => floatval($item->real_revenue),
                'target' => floatval($item->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * Get Revenue Data Aggregate by Month (Original)
     */
    private function getRevenueDataAggregateByMonth($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $bulanStart = $filters['bulan_start'] ?? 1;
        $bulanEnd = $filters['bulan_end'] ?? 12;

        $query->whereBetween('bulan', [$bulanStart, $bulanEnd]);

        $monthlyData = $query->selectRaw('
                bulan,
                SUM(real_revenue) as monthly_revenue,
                SUM(target_revenue) as monthly_target
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return $monthlyData->map(function($item) {
            $achievementRate = $item->monthly_target > 0
                ? round(($item->monthly_revenue / $item->monthly_target) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'total_revenue' => floatval($item->monthly_revenue),
                'total_target' => floatval($item->monthly_target),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * NEW: Get Revenue Data Aggregate by Month and Account Manager
     */
    private function getRevenueDataAggregateByMonthAndAM($ccId, $filters)
    {
        // FIXED: Load CC dengan latestAmRevenue
        $corporateCustomer = CorporateCustomer::with('latestAmRevenue.accountManager')->find($ccId);

        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $bulanStart = $filters['bulan_start'] ?? 1;
        $bulanEnd = $filters['bulan_end'] ?? 12;

        $query->whereBetween('bulan', [$bulanStart, $bulanEnd]);

        $monthlyData = $query->selectRaw('
                bulan,
                SUM(real_revenue) as monthly_revenue,
                SUM(target_revenue) as monthly_target
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return $monthlyData->map(function($item) use ($corporateCustomer) {
            $achievementRate = $item->monthly_target > 0
                ? round(($item->monthly_revenue / $item->monthly_target) * 100, 2)
                : 0;

            // FIXED: Gunakan accessor
            $primaryAM = $corporateCustomer->primary_account_manager;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'account_manager' => $primaryAM ? $primaryAM->nama : 'N/A',
                'nik' => $primaryAM ? $primaryAM->nik : 'N/A',
                'total_revenue' => floatval($item->monthly_revenue),
                'total_target' => floatval($item->monthly_target),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * NEW: Get Revenue Data Aggregate by Month and Segment
     */
    private function getRevenueDataAggregateByMonthAndSegment($ccId, $filters)
    {
        $query = CcRevenue::where('corporate_customer_id', $ccId)
            ->where('tahun', $filters['tahun'])
            ->with('segment');

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $bulanStart = $filters['bulan_start'] ?? 1;
        $bulanEnd = $filters['bulan_end'] ?? 12;

        $query->whereBetween('bulan', [$bulanStart, $bulanEnd]);

        $monthlyData = $query->selectRaw('
                bulan,
                segment_id,
                SUM(real_revenue) as monthly_revenue,
                SUM(target_revenue) as monthly_target
            ')
            ->groupBy('bulan', 'segment_id')
            ->orderBy('bulan')
            ->get();

        return $monthlyData->map(function($item) {
            $achievementRate = $item->monthly_target > 0
                ? round(($item->monthly_revenue / $item->monthly_target) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'segment' => $item->segment ? $item->segment->lsegment_ho : 'N/A',
                'total_revenue' => floatval($item->monthly_revenue),
                'total_target' => floatval($item->monthly_target),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        });
    }

    /**
     * Get Revenue Analysis Data
     */
    private function getRevenueAnalysisData($ccId, $filters)
    {
        $summary = $this->getRevenueSummary($ccId, $filters);
        $monthlyChart = $this->getMonthlyRevenueChart($ccId, $filters['chart_tahun'] ?? $filters['tahun'], $filters);

        return [
            'summary' => $summary,
            'monthly_chart' => $monthlyChart,
            'chart_filters' => [
                'tahun' => $filters['chart_tahun'] ?? $filters['tahun'],
                'display_mode' => $filters['chart_display'] ?? 'combination'
            ]
        ];
    }

    /**
     * Get Revenue Summary
     */
    private function getRevenueSummary($ccId, $filters)
    {
        try {
            $baseQuery = CcRevenue::where('corporate_customer_id', $ccId);

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] !== 'all') {
                $baseQuery->where('tipe_revenue', $filters['tipe_revenue']);
            }

            if (isset($filters['revenue_source']) && $filters['revenue_source'] !== 'all') {
                $baseQuery->where('revenue_source', $filters['revenue_source']);
            }

            $allTimeData = (clone $baseQuery)
                ->selectRaw('
                    SUM(real_revenue) as total_revenue_all_time,
                    SUM(target_revenue) as total_target_all_time
                ')
                ->first();

            $highestAchievement = (clone $baseQuery)
                ->selectRaw('
                    tahun,
                    bulan,
                    SUM(real_revenue) as revenue,
                    SUM(target_revenue) as target,
                    (SUM(real_revenue) / SUM(target_revenue)) * 100 as achievement_rate
                ')
                ->groupBy('tahun', 'bulan')
                ->havingRaw('SUM(target_revenue) > 0')
                ->orderByDesc('achievement_rate')
                ->first();

            $highestRevenue = (clone $baseQuery)
                ->selectRaw('
                    tahun,
                    bulan,
                    SUM(real_revenue) as revenue
                ')
                ->groupBy('tahun', 'bulan')
                ->orderByDesc('revenue')
                ->first();

            $monthlyAchievements = (clone $baseQuery)
                ->selectRaw('
                    tahun,
                    bulan,
                    (SUM(real_revenue) / SUM(target_revenue)) * 100 as achievement_rate
                ')
                ->groupBy('tahun', 'bulan')
                ->havingRaw('SUM(target_revenue) > 0')
                ->get();

            $averageAchievement = $monthlyAchievements->avg('achievement_rate');

            $trend = $this->calculateTrend($ccId, 3, $filters);

            return [
                'total_revenue_all_time' => floatval($allTimeData->total_revenue_all_time ?? 0),
                'total_target_all_time' => floatval($allTimeData->total_target_all_time ?? 0),
                'overall_achievement_rate' => $allTimeData->total_target_all_time > 0
                    ? round(($allTimeData->total_revenue_all_time / $allTimeData->total_target_all_time) * 100, 2)
                    : 0,
                'highest_achievement' => [
                    'bulan' => $highestAchievement
                        ? $this->getMonthName($highestAchievement->bulan) . ' ' . $highestAchievement->tahun
                        : 'N/A',
                    'value' => $highestAchievement ? round($highestAchievement->achievement_rate, 2) : 0
                ],
                'highest_revenue' => [
                    'bulan' => $highestRevenue
                        ? $this->getMonthName($highestRevenue->bulan) . ' ' . $highestRevenue->tahun
                        : 'N/A',
                    'value' => $highestRevenue ? floatval($highestRevenue->revenue) : 0
                ],
                'average_achievement' => round($averageAchievement ?? 0, 2),
                'trend' => $trend['status'],
                'trend_percentage' => $trend['percentage'],
                'trend_description' => $trend['description']
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get revenue summary', [
                'cc_id' => $ccId,
                'error' => $e->getMessage()
            ]);

            return [
                'total_revenue_all_time' => 0,
                'total_target_all_time' => 0,
                'overall_achievement_rate' => 0,
                'highest_achievement' => ['bulan' => 'N/A', 'value' => 0],
                'highest_revenue' => ['bulan' => 'N/A', 'value' => 0],
                'average_achievement' => 0,
                'trend' => 'unknown',
                'trend_percentage' => 0,
                'trend_description' => 'Data tidak tersedia'
            ];
        }
    }

    /**
     * Calculate Revenue Trend
     */
    private function calculateTrend($ccId, $months = 3, $filters = [])
    {
        try {
            $query = CcRevenue::where('corporate_customer_id', $ccId);

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            $latestData = (clone $query)
                ->selectRaw('DISTINCT tahun, bulan')
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->limit($months)
                ->get();

            if ($latestData->count() < 2) {
                return [
                    'status' => 'insufficient_data',
                    'percentage' => 0,
                    'description' => 'Data tidak cukup untuk analisis tren'
                ];
            }

            $monthFilters = $latestData->map(function($item) {
                return ['tahun' => $item->tahun, 'bulan' => $item->bulan];
            });

            $monthlyData = $query->where(function($q) use ($monthFilters) {
                foreach ($monthFilters as $filter) {
                    $q->orWhere(function($subq) use ($filter) {
                        $subq->where('tahun', $filter['tahun'])
                             ->where('bulan', $filter['bulan']);
                    });
                }
            })
            ->selectRaw('
                tahun,
                bulan,
                (SUM(real_revenue) / SUM(target_revenue)) * 100 as achievement_rate
            ')
            ->groupBy('tahun', 'bulan')
            ->havingRaw('SUM(target_revenue) > 0')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

            if ($monthlyData->count() < 2) {
                return [
                    'status' => 'insufficient_data',
                    'percentage' => 0,
                    'description' => 'Data tidak cukup'
                ];
            }

            $firstValue = $monthlyData->first()->achievement_rate;
            $lastValue = $monthlyData->last()->achievement_rate;
            $percentageChange = $firstValue != 0
                ? (($lastValue - $firstValue) / $firstValue) * 100
                : 0;

            $status = 'stabil';
            $description = "Revenue relatif stabil dalam {$monthlyData->count()} bulan terakhir";

            if ($percentageChange > 2) {
                $status = 'naik';
                $description = sprintf('Tren meningkat %.1f%% dalam %d bulan terakhir', $percentageChange, $monthlyData->count());
            } elseif ($percentageChange < -2) {
                $status = 'turun';
                $description = sprintf('Tren menurun %.1f%% dalam %d bulan terakhir', abs($percentageChange), $monthlyData->count());
            }

            return [
                'status' => $status,
                'percentage' => round($percentageChange, 2),
                'description' => $description
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate trend', [
                'cc_id' => $ccId,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'percentage' => 0,
                'description' => 'Gagal menghitung tren'
            ];
        }
    }

    /**
     * Get Monthly Revenue Chart
     */
    private function getMonthlyRevenueChart($ccId, $tahun, $filters)
    {
        try {
            $query = CcRevenue::where('corporate_customer_id', $ccId)
                ->where('tahun', $tahun);

            if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $query->where('tipe_revenue', $filters['tipe_revenue']);
            }

            if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $query->where('revenue_source', $filters['revenue_source']);
            }

            $monthlyData = $query->selectRaw('
                    bulan,
                    SUM(real_revenue) as real_revenue,
                    SUM(target_revenue) as target_revenue,
                    CASE
                        WHEN SUM(target_revenue) > 0
                        THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                        ELSE 0
                    END as achievement_rate
                ')
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get();

            $labels = [];
            $realRevenue = [];
            $targetRevenue = [];
            $achievementRate = [];

            for ($month = 1; $month <= 12; $month++) {
                $monthData = $monthlyData->firstWhere('bulan', $month);

                $labels[] = $this->getShortMonthName($month);
                $realRevenue[] = $monthData ? floatval($monthData->real_revenue) : 0;
                $targetRevenue[] = $monthData ? floatval($monthData->target_revenue) : 0;
                $achievementRate[] = $monthData ? round($monthData->achievement_rate, 2) : 0;
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    'real_revenue' => $realRevenue,
                    'target_revenue' => $targetRevenue,
                    'achievement_rate' => $achievementRate
                ],
                'tahun' => $tahun,
                'display_mode' => $filters['chart_display'] ?? 'combination'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get monthly chart', [
                'cc_id' => $ccId,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyChartData();
        }
    }

    /**
     * Get Filter Options
     */
    private function getFilterOptions($ccId)
    {
        $availableYears = CcRevenue::where('corporate_customer_id', $ccId)
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        $bulanOptions = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanOptions[$i] = $this->getMonthName($i);
        }

        return [
            'period_types' => [
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date'
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
            ],
            'view_modes' => [
                'agregat_bulan' => 'Agregat per Bulan',
                'detail' => 'Detail (Per Bulan)'
            ],
            'granularities' => [
                'divisi' => 'Per Divisi',
                'segment' => 'Per Segment',
                'account_manager' => 'Per Account Manager'
            ],
            'chart_displays' => [
                'revenue' => 'Revenue Saja',
                'achievement' => 'Achievement Saja',
                'combination' => 'Kombinasi (Revenue + Achievement)'
            ],
            'available_years' => $availableYears,
            'use_year_picker' => count($availableYears) > 10,
            'bulan_options' => $bulanOptions,
            'current_month' => date('n')
        ];
    }

    /**
     * HELPER METHODS
     */
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

    private function generatePeriodText($filters)
    {
        $tahun = $filters['tahun'];
        $bulanEnd = $filters['bulan_end'];
        $monthName = $this->getMonthName($bulanEnd);

        if ($filters['period_type'] === 'MTD') {
            return "Bulan {$monthName} {$tahun}";
        } else {
            return "Januari - {$monthName} {$tahun}";
        }
    }

    private function getMonthName($monthNumber)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$monthNumber] ?? 'Unknown';
    }

    private function getShortMonthName($monthNumber)
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        return $months[$monthNumber] ?? 'N/A';
    }

    private function getEmptyChartData()
    {
        $labels = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $this->getShortMonthName($i);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'real_revenue' => array_fill(0, 12, 0),
                'target_revenue' => array_fill(0, 12, 0),
                'achievement_rate' => array_fill(0, 12, 0)
            ],
            'tahun' => date('Y'),
            'display_mode' => 'combination'
        ];
    }
}