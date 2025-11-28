<?php

namespace App\Services;

use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\AccountManager;
use App\Models\Witel;
use App\Models\Divisi;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RankingService
{
    /**
     * Get global ranking for Account Manager with date range support - Fixed for many-to-many divisi
     */
    public function getGlobalRankingWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get all AMs with proper many-to-many divisi filtering
        $amQuery = AccountManager::where('role', 'AM');

        // Apply divisi filter using many-to-many relation
        if ($divisiId) {
            $amQuery->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });
        }

        $validAMIds = $amQuery->pluck('id');

        if ($validAMIds->isEmpty()) {
            return [
                'current_position' => null,
                'total_participants' => 0,
                'previous_position' => null,
                'status' => 'no_data',
                'status_icon' => 'fas fa-minus text-muted',
                'achievement_rate' => 0,
                'period_context' => $this->getPeriodContext($startDate, $endDate)
            ];
        }

        // Get AM performance data
        $allAMsQuery = AmRevenue::whereIn('account_manager_id', $validAMIds);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($allAMsQuery, $startDate, $endDate);
        } else {
            $allAMsQuery->where('tahun', $this->getCurrentDataYear());
        }

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($allAMsQuery, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        // Get aggregated revenue per AM
        $amPerformances = $allAMsQuery->selectRaw('
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
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Find target AM ranking
        $targetPosition = $amPerformances->search(function($am) use ($accountManagerId) {
            return $am->account_manager_id == $accountManagerId;
        });

        $totalAMs = $amPerformances->count();
        $currentPosition = $targetPosition !== false ? $targetPosition + 1 : null;

        // Get previous period ranking for status comparison
        $previousRanking = $this->getPreviousPeriodGlobalRanking(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $status = $this->determineRankingStatus($currentPosition, $previousRanking['position']);

        return [
            'current_position' => $currentPosition,
            'total_participants' => $totalAMs,
            'previous_position' => $previousRanking['position'],
            'status' => $status,
            'status_icon' => $this->getRankingStatusIcon($status),
            'achievement_rate' => $targetPosition !== false ? round($amPerformances[$targetPosition]->achievement_rate, 2) : 0,
            'period_context' => $this->getPeriodContext($startDate, $endDate),
            'percentile' => $currentPosition && $totalAMs > 0 ? round((($totalAMs - $currentPosition + 1) / $totalAMs) * 100, 1) : 0
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getGlobalRanking($accountManagerId, $tahun = null, $bulan = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getGlobalRankingWithDateRange(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get witel ranking for Account Manager with date range support - Fixed for many-to-many divisi
     */
    public function getWitelRankingWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get AM's witel
        $targetAM = AccountManager::with(['witel', 'divisis'])->find($accountManagerId);
        if (!$targetAM) {
            return null;
        }

        $witelId = $targetAM->witel_id;

        // Get all AMs in the same witel with proper divisi filtering
        $amQuery = AccountManager::where('role', 'AM')
            ->where('witel_id', $witelId);

        // Apply divisi filter using many-to-many relation
        if ($divisiId) {
            $amQuery->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });
        }

        $validAMIds = $amQuery->pluck('id');

        if ($validAMIds->isEmpty()) {
            return [
                'current_position' => null,
                'total_participants' => 0,
                'previous_position' => null,
                'status' => 'no_data',
                'status_icon' => 'fas fa-minus text-muted',
                'achievement_rate' => 0,
                'witel_name' => $targetAM->witel->nama ?? 'Unknown',
                'period_context' => $this->getPeriodContext($startDate, $endDate)
            ];
        }

        $witelAMsQuery = AmRevenue::whereIn('account_manager_id', $validAMIds);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($witelAMsQuery, $startDate, $endDate);
        } else {
            $witelAMsQuery->where('tahun', $this->getCurrentDataYear());
        }

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($witelAMsQuery, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        // Get aggregated performance
        $witelPerformances = $witelAMsQuery->selectRaw('
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
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Find target AM ranking
        $targetPosition = $witelPerformances->search(function($am) use ($accountManagerId) {
            return $am->account_manager_id == $accountManagerId;
        });

        $totalWitelAMs = $witelPerformances->count();
        $currentPosition = $targetPosition !== false ? $targetPosition + 1 : null;

        // Get previous period ranking
        $previousRanking = $this->getPreviousPeriodWitelRanking(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $status = $this->determineRankingStatus($currentPosition, $previousRanking['position']);

        return [
            'current_position' => $currentPosition,
            'total_participants' => $totalWitelAMs,
            'previous_position' => $previousRanking['position'],
            'status' => $status,
            'status_icon' => $this->getRankingStatusIcon($status),
            'achievement_rate' => $targetPosition !== false ? round($witelPerformances[$targetPosition]->achievement_rate, 2) : 0,
            'witel_name' => $targetAM->witel->nama ?? 'Unknown',
            'period_context' => $this->getPeriodContext($startDate, $endDate),
            'percentile' => $currentPosition && $totalWitelAMs > 0 ? round((($totalWitelAMs - $currentPosition + 1) / $totalWitelAMs) * 100, 1) : 0
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getWitelRanking($accountManagerId, $tahun = null, $bulan = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getWitelRankingWithDateRange(
            $accountManagerId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get divisi ranking for Account Manager with date range support - Fixed for many-to-many divisi
     */
    public function getDivisiRankingWithDateRange($accountManagerId, $divisiId, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Verify AM has access to this divisi
        $targetAM = AccountManager::with('divisis')->find($accountManagerId);
        if (!$targetAM || !$targetAM->divisis->contains('id', $divisiId)) {
            return null;
        }

        // Get all AMs with access to the same divisi
        $amQuery = AccountManager::where('role', 'AM')
            ->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });

        $validAMIds = $amQuery->pluck('id');

        if ($validAMIds->isEmpty()) {
            return [
                'current_position' => null,
                'total_participants' => 0,
                'previous_position' => null,
                'status' => 'no_data',
                'status_icon' => 'fas fa-minus text-muted',
                'achievement_rate' => 0,
                'divisi_name' => Divisi::find($divisiId)?->nama ?? 'Unknown',
                'divisi_code' => Divisi::find($divisiId)?->kode ?? 'N/A',
                'period_context' => $this->getPeriodContext($startDate, $endDate)
            ];
        }

        // Get revenue data for AMs in this divisi
        $divisiAMsQuery = AmRevenue::whereIn('account_manager_id', $validAMIds);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($divisiAMsQuery, $startDate, $endDate);
        } else {
            $divisiAMsQuery->where('tahun', $this->getCurrentDataYear());
        }

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($divisiAMsQuery, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        // Get aggregated performance
        $divisiPerformances = $divisiAMsQuery->selectRaw('
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
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Find target AM ranking
        $targetPosition = $divisiPerformances->search(function($am) use ($accountManagerId) {
            return $am->account_manager_id == $accountManagerId;
        });

        $totalDivisiAMs = $divisiPerformances->count();
        $currentPosition = $targetPosition !== false ? $targetPosition + 1 : null;

        // Get divisi information
        $divisi = Divisi::find($divisiId);

        // Get previous period ranking
        $previousRanking = $this->getPreviousPeriodDivisiRanking(
            $accountManagerId, $divisiId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );

        $status = $this->determineRankingStatus($currentPosition, $previousRanking['position']);

        return [
            'current_position' => $currentPosition,
            'total_participants' => $totalDivisiAMs,
            'previous_position' => $previousRanking['position'],
            'status' => $status,
            'status_icon' => $this->getRankingStatusIcon($status),
            'achievement_rate' => $targetPosition !== false ? round($divisiPerformances[$targetPosition]->achievement_rate, 2) : 0,
            'divisi_name' => $divisi->nama ?? 'Unknown',
            'divisi_code' => $divisi->kode ?? 'N/A',
            'period_context' => $this->getPeriodContext($startDate, $endDate),
            'percentile' => $currentPosition && $totalDivisiAMs > 0 ? round((($totalDivisiAMs - $currentPosition + 1) / $totalDivisiAMs) * 100, 1) : 0
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getDivisiRanking($accountManagerId, $divisiId, $tahun = null, $bulan = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getDivisiRankingWithDateRange(
            $accountManagerId, $divisiId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get all divisi rankings for multi-divisi AM - Fixed for many-to-many
     */
    public function getAllDivisiRankingsWithDateRange($accountManagerId, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        $am = AccountManager::with('divisis')->find($accountManagerId);

        if (!$am || $am->divisis->isEmpty()) {
            return [];
        }

        $rankings = [];

        foreach ($am->divisis as $divisi) {
            $ranking = $this->getDivisiRankingWithDateRange(
                $accountManagerId, $divisi->id, $startDate, $endDate, $revenueSource, $tipeRevenue
            );

            if ($ranking) {
                $rankings[] = array_merge($ranking, [
                    'is_primary' => $divisi->pivot->is_primary,
                    'divisi_priority' => $divisi->pivot->is_primary ? 1 : 2
                ]);
            }
        }

        // Sort by primary first, then by current position
        usort($rankings, function($a, $b) {
            if ($a['divisi_priority'] !== $b['divisi_priority']) {
                return $a['divisi_priority'] <=> $b['divisi_priority'];
            }
            return ($a['current_position'] ?? 999) <=> ($b['current_position'] ?? 999);
        });

        return $rankings;
    }

    /**
     * Backward compatibility method
     */
    public function getAllDivisiRankings($accountManagerId, $tahun = null, $bulan = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        if ($bulan) {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1);
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($tahun, 1, 1);
            $endDate = Carbon::createFromDate($tahun, 12, 31);
        }

        return $this->getAllDivisiRankingsWithDateRange(
            $accountManagerId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get AMs by category for Witel dashboard with date range support - Fixed for many-to-many divisi
     */
    public function getAMsByCategoryWithDateRange($witelId, $category = null, $limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get all AMs in the witel with proper divisi filtering
        $amQuery = AccountManager::where('role', 'AM')
            ->where('witel_id', $witelId);

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

        $query = AmRevenue::whereIn('account_manager_id', $validAMIds)
            ->with(['accountManager.witel', 'accountManager.divisis']);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        // Get aggregated data
        $amPerformances = $query->selectRaw('
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
            ->get()
            ->map(function($item) {
                $am = AccountManager::with(['witel', 'divisis'])->find($item->account_manager_id);

                $item->nama = $am->nama ?? 'Unknown';
                $item->nik = $am->nik ?? 'Unknown';
                $item->witel_nama = $am->witel->nama ?? 'Unknown';
                $item->divisi_list = $am->divisis && $am->divisis->count() > 0
                    ? $am->divisis->pluck('nama')->join(', ')
                    : 'N/A';
                $item->primary_divisi = $am->divisis->where('pivot.is_primary', 1)->first()?->nama ?? 'N/A';
                $item->achievement_color = $this->getAchievementColor($item->achievement_rate);
                $item->category = $this->categorizeAM($item->achievement_rate);

                return $item;
            });

        // Filter by category if specified
        if ($category && $category !== 'All') {
            $amPerformances = $amPerformances->filter(function($am) use ($category) {
                return $am->category === $category;
            });
        }

        // Sort and limit
        return $amPerformances->sortByDesc('total_revenue')->take($limit)->values();
    }

    /**
     * Backward compatibility method
     */
    public function getAMsByCategory($witelId, $category = null, $limit = 20, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getAMsByCategoryWithDateRange(
            $witelId, $category, $limit, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get category distribution for Witel dashboard - Fixed for many-to-many divisi
     */
    public function getCategoryDistributionWithDateRange($witelId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $amPerformances = $this->getAMsByCategoryWithDateRange(
            $witelId, null, 1000, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $distribution = [
            'Excellent' => $amPerformances->filter(fn($am) => $am->category === 'Excellent')->count(),
            'Good' => $amPerformances->filter(fn($am) => $am->category === 'Good')->count(),
            'Poor' => $amPerformances->filter(fn($am) => $am->category === 'Poor')->count(),
            'total' => $amPerformances->count()
        ];

        // Calculate percentages
        $total = $distribution['total'];
        if ($total > 0) {
            $distribution['excellent_percentage'] = round(($distribution['Excellent'] / $total) * 100, 1);
            $distribution['good_percentage'] = round(($distribution['Good'] / $total) * 100, 1);
            $distribution['poor_percentage'] = round(($distribution['Poor'] / $total) * 100, 1);
        } else {
            $distribution['excellent_percentage'] = 0;
            $distribution['good_percentage'] = 0;
            $distribution['poor_percentage'] = 0;
        }

        return $distribution;
    }

    /**
     * Backward compatibility method
     */
    public function getCategoryDistribution($witelId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getCategoryDistributionWithDateRange(
            $witelId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get category performance summary - Enhanced version
     */
    public function getCategoryPerformanceWithDateRange($witelId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $amPerformances = $this->getAMsByCategoryWithDateRange(
            $witelId, null, 1000, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );

        $categoryStats = [];

        foreach (['Excellent', 'Good', 'Poor'] as $category) {
            $categoryAMs = $amPerformances->filter(fn($am) => $am->category === $category);

            $categoryStats[$category] = [
                'count' => $categoryAMs->count(),
                'total_revenue' => $categoryAMs->sum('total_revenue'),
                'total_target' => $categoryAMs->sum('total_target'),
                'avg_achievement' => $categoryAMs->count() > 0 ? round($categoryAMs->avg('achievement_rate'), 2) : 0,
                'color' => $this->getCategoryColor($category),
                'top_performers' => $categoryAMs->sortByDesc('total_revenue')->take(3)->values(),
                'contribution_percentage' => $amPerformances->sum('total_revenue') > 0
                    ? round(($categoryAMs->sum('total_revenue') / $amPerformances->sum('total_revenue')) * 100, 1)
                    : 0
            ];
        }

        return $categoryStats;
    }

    /**
     * Backward compatibility method
     */
    public function getCategoryPerformance($witelId, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getCategoryPerformanceWithDateRange(
            $witelId, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get category options for dropdown
     */
    public function getCategoryOptions()
    {
        return [
            'All' => 'Semua Kategori',
            'Excellent' => 'Excellent (≥100%)',
            'Good' => 'Good (80-99%)',
            'Poor' => 'Poor (<80%)'
        ];
    }

    /**
     * Get comprehensive ranking summary for AM
     */
    public function getComprehensiveRankingSummary($accountManagerId, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        $am = AccountManager::with(['witel', 'divisis'])->find($accountManagerId);

        if (!$am) {
            return null;
        }

        $summary = [];

        // Global ranking
        $globalRanking = $this->getGlobalRankingWithDateRange(
            $accountManagerId, $startDate, $endDate, null, $revenueSource, $tipeRevenue
        );
        $summary['global'] = $globalRanking;

        // Witel ranking
        $witelRanking = $this->getWitelRankingWithDateRange(
            $accountManagerId, $startDate, $endDate, null, $revenueSource, $tipeRevenue
        );
        $summary['witel'] = $witelRanking;

        // All divisi rankings
        $divisiRankings = $this->getAllDivisiRankingsWithDateRange(
            $accountManagerId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );
        $summary['divisi'] = $divisiRankings;

        // Calculate overall ranking score
        $summary['overall_score'] = $this->calculateOverallRankingScore($summary);

        // Generate ranking insights
        $summary['insights'] = $this->generateRankingInsights($summary, $am);

        return $summary;
    }

    /**
     * Get ranking trends over time
     */
    public function getRankingTrends($accountManagerId, $months = 6, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $currentDate = Carbon::now();
        $trends = [];

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $currentDate->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $currentDate->copy()->subMonths($i)->endOfMonth();

            $globalRanking = $this->getGlobalRankingWithDateRange(
                $accountManagerId, $monthStart, $monthEnd, $divisiId, $revenueSource, $tipeRevenue
            );

            $witelRanking = $this->getWitelRankingWithDateRange(
                $accountManagerId, $monthStart, $monthEnd, $divisiId, $revenueSource, $tipeRevenue
            );

            $trends[] = [
                'month' => $monthStart->format('Y-m'),
                'month_name' => $monthStart->format('F Y'),
                'global_position' => $globalRanking['current_position'],
                'global_total' => $globalRanking['total_participants'],
                'witel_position' => $witelRanking['current_position'],
                'witel_total' => $witelRanking['total_participants'],
                'achievement_rate' => $globalRanking['achievement_rate']
            ];
        }

        // Reverse to get chronological order
        $trends = array_reverse($trends);

        // Calculate trend direction
        $trendAnalysis = $this->analyzeTrendDirection($trends);

        return [
            'trends' => $trends,
            'analysis' => $trendAnalysis,
            'summary' => [
                'best_month' => $this->getBestMonth($trends),
                'worst_month' => $this->getWorstMonth($trends),
                'average_global_position' => $this->calculateAveragePosition($trends, 'global_position'),
                'average_witel_position' => $this->calculateAveragePosition($trends, 'witel_position')
            ]
        ];
    }

    /**
     * Get performance leaderboard for comparison
     */
    public function getPerformanceLeaderboard($scope = 'witel', $scopeId = null, $limit = 10, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $amQuery = AccountManager::where('role', 'AM')
            ->with(['witel', 'divisis']);

        // Apply scope filter
        switch ($scope) {
            case 'witel':
                if ($scopeId) {
                    $amQuery->where('witel_id', $scopeId);
                }
                break;
            case 'divisi':
                if ($scopeId) {
                    $amQuery->whereHas('divisis', function($q) use ($scopeId) {
                        $q->where('divisi.id', $scopeId);
                    });
                }
                break;
            case 'global':
                // No additional filter needed
                break;
        }

        // Apply divisi filter if specified separately
        if ($divisiId && $scope !== 'divisi') {
            $amQuery->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });
        }

        $validAMIds = $amQuery->pluck('id');

        if ($validAMIds->isEmpty()) {
            return collect([]);
        }

        // Get revenue data
        $revenueQuery = AmRevenue::whereIn('account_manager_id', $validAMIds);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($revenueQuery, $startDate, $endDate);
        } else {
            $revenueQuery->where('tahun', $this->getCurrentDataYear());
        }

        // Apply revenue source and tipe revenue filtering
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($revenueQuery, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        // Get leaderboard
        $leaderboard = $revenueQuery->selectRaw('
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
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item, $index) {
                $am = AccountManager::with(['witel', 'divisis'])->find($item->account_manager_id);

                return (object) [
                    'rank' => $index + 1,
                    'account_manager_id' => $item->account_manager_id,
                    'nama' => $am->nama ?? 'Unknown',
                    'nik' => $am->nik ?? 'Unknown',
                    'witel_nama' => $am->witel->nama ?? 'Unknown',
                    'divisi_list' => $am->divisis && $am->divisis->count() > 0
                        ? $am->divisis->pluck('nama')->join(', ')
                        : 'N/A',
                    'primary_divisi' => $am->divisis->where('pivot.is_primary', 1)->first()?->nama ?? 'N/A',
                    'total_revenue' => $item->total_revenue,
                    'total_target' => $item->total_target,
                    'achievement_rate' => round($item->achievement_rate, 2),
                    'achievement_color' => $this->getAchievementColor($item->achievement_rate)
                ];
            });

        return $leaderboard;
    }

    /**
     * Private Helper Methods
     */

    /**
     * Apply date range filter to query
     */
    private function applyDateRangeFilter($query, $startDate, $endDate)
    {
        $startYear = Carbon::parse($startDate)->year;
        $endYear = Carbon::parse($endDate)->year;
        $startMonth = Carbon::parse($startDate)->month;
        $endMonth = Carbon::parse($endDate)->month;

        if ($startYear === $endYear) {
            $query->where('tahun', $startYear)
                  ->whereBetween('bulan', [$startMonth, $endMonth]);
        } else {
            $query->where(function($q) use ($startYear, $endYear, $startMonth, $endMonth) {
                $q->where(function($q1) use ($startYear, $startMonth) {
                    $q1->where('tahun', $startYear)->where('bulan', '>=', $startMonth);
                })->orWhere(function($q2) use ($endYear, $endMonth) {
                    $q2->where('tahun', $endYear)->where('bulan', '<=', $endMonth);
                });
            });
        }
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
    private function applyCcRevenueFilter($query, $startDate, $endDate, $revenueSource, $tipeRevenue)
    {
        $query->whereExists(function($subquery) use ($startDate, $endDate, $revenueSource, $tipeRevenue) {
            $subquery->select(DB::raw(1))
                    ->from('cc_revenues')
                    ->whereColumn('cc_revenues.corporate_customer_id', 'am_revenues.corporate_customer_id');

            // Apply date range to subquery
            if ($startDate && $endDate) {
                $this->applyDateRangeFilter($subquery, $startDate, $endDate);
            } else {
                $subquery->where('tahun', $this->getCurrentDataYear());
            }

            if ($revenueSource && $revenueSource !== 'all') {
                $subquery->where('revenue_source', $revenueSource);
            }

            if ($tipeRevenue && $tipeRevenue !== 'all') {
                $subquery->where('tipe_revenue', $tipeRevenue);
            }
        });
    }

    private function getPreviousPeriodGlobalRanking($accountManagerId, $startDate, $endDate, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        if (!$startDate || !$endDate) {
            return ['position' => null];
        }

        // Calculate previous period (same duration)
        $currentStart = Carbon::parse($startDate);
        $currentEnd = Carbon::parse($endDate);
        $duration = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($duration + 1);
        $previousEnd = $currentStart->copy()->subDay();

        try {
            $previousRanking = $this->getGlobalRankingWithDateRange(
                $accountManagerId, $previousStart, $previousEnd, $divisiId, $revenueSource, $tipeRevenue
            );

            return ['position' => $previousRanking['current_position']];
        } catch (\Exception $e) {
            return ['position' => null];
        }
    }

    private function getPreviousPeriodWitelRanking($accountManagerId, $startDate, $endDate, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        if (!$startDate || !$endDate) {
            return ['position' => null];
        }

        $currentStart = Carbon::parse($startDate);
        $currentEnd = Carbon::parse($endDate);
        $duration = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($duration + 1);
        $previousEnd = $currentStart->copy()->subDay();

        try {
            $previousRanking = $this->getWitelRankingWithDateRange(
                $accountManagerId, $previousStart, $previousEnd, $divisiId, $revenueSource, $tipeRevenue
            );

            return ['position' => $previousRanking['current_position']];
        } catch (\Exception $e) {
            return ['position' => null];
        }
    }

    private function getPreviousPeriodDivisiRanking($accountManagerId, $divisiId, $startDate, $endDate, $revenueSource = null, $tipeRevenue = null)
    {
        if (!$startDate || !$endDate) {
            return ['position' => null];
        }

        $currentStart = Carbon::parse($startDate);
        $currentEnd = Carbon::parse($endDate);
        $duration = $currentStart->diffInDays($currentEnd);

        $previousStart = $currentStart->copy()->subDays($duration + 1);
        $previousEnd = $currentStart->copy()->subDay();

        try {
            $previousRanking = $this->getDivisiRankingWithDateRange(
                $accountManagerId, $divisiId, $previousStart, $previousEnd, $revenueSource, $tipeRevenue
            );

            return ['position' => $previousRanking['current_position']];
        } catch (\Exception $e) {
            return ['position' => null];
        }
    }

    private function determineRankingStatus($currentPosition, $previousPosition)
    {
        if (!$currentPosition || !$previousPosition) {
            return 'tetap';
        }

        if ($currentPosition < $previousPosition) {
            return 'naik';
        } elseif ($currentPosition > $previousPosition) {
            return 'turun';
        } else {
            return 'tetap';
        }
    }

    private function getRankingStatusIcon($status)
    {
        switch ($status) {
            case 'naik':
                return 'fas fa-arrow-up text-success';
            case 'turun':
                return 'fas fa-arrow-down text-danger';
            default:
                return 'fas fa-minus text-muted';
        }
    }

    private function categorizeAM($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'Excellent';
        } elseif ($achievementRate >= 80) {
            return 'Good';
        } else {
            return 'Poor';
        }
    }

    private function getCategoryColor($category)
    {
        switch ($category) {
            case 'Excellent':
                return 'success';
            case 'Good':
                return 'warning';
            case 'Poor':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    private function getPeriodContext($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return $this->getCurrentDataYear();
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->year === $end->year) {
            if ($start->month === $end->month) {
                return $start->format('M Y');
            } else {
                return $start->format('M') . ' - ' . $end->format('M Y');
            }
        } else {
            return $start->format('M Y') . ' - ' . $end->format('M Y');
        }
    }

    private function getCurrentDataYear()
    {
        static $currentYear = null;

        if ($currentYear === null) {
            $currentYear = CcRevenue::max('tahun') ?? 2025;
        }

        return $currentYear;
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

    private function calculateOverallRankingScore($summary)
    {
        $scores = [];

        if ($summary['global']['current_position'] && $summary['global']['total_participants']) {
            $scores[] = (($summary['global']['total_participants'] - $summary['global']['current_position'] + 1) / $summary['global']['total_participants']) * 100;
        }

        if ($summary['witel']['current_position'] && $summary['witel']['total_participants']) {
            $scores[] = (($summary['witel']['total_participants'] - $summary['witel']['current_position'] + 1) / $summary['witel']['total_participants']) * 100;
        }

        foreach ($summary['divisi'] as $divisiRanking) {
            if ($divisiRanking['current_position'] && $divisiRanking['total_participants']) {
                $weight = $divisiRanking['is_primary'] ? 1.5 : 1.0;
                $scores[] = ((($divisiRanking['total_participants'] - $divisiRanking['current_position'] + 1) / $divisiRanking['total_participants']) * 100) * $weight;
            }
        }

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
    }

    private function generateRankingInsights($summary, $am)
    {
        $insights = [];

        // Global performance insight
        if ($summary['global']['current_position']) {
            $percentile = $summary['global']['percentile'];
            if ($percentile >= 90) {
                $insights[] = [
                    'type' => 'success',
                    'message' => "Performance global sangat baik - top {$percentile}% dari semua AM",
                    'priority' => 'high'
                ];
            } elseif ($percentile >= 70) {
                $insights[] = [
                    'type' => 'info',
                    'message' => "Performance global cukup baik - top {$percentile}% dari semua AM",
                    'priority' => 'medium'
                ];
            } else {
                $insights[] = [
                    'type' => 'warning',
                    'message' => "Ada ruang untuk improvement - saat ini di posisi {$percentile}%",
                    'priority' => 'high'
                ];
            }
        }

        // Witel vs Global comparison
        if ($summary['global']['current_position'] && $summary['witel']['current_position']) {
            $globalPercentile = $summary['global']['percentile'];
            $witelPercentile = $summary['witel']['percentile'];

            if ($witelPercentile > $globalPercentile + 10) {
                $insights[] = [
                    'type' => 'success',
                    'message' => "Performance sangat kuat di witel - unggul dibanding rata-rata global",
                    'priority' => 'medium'
                ];
            } elseif ($witelPercentile < $globalPercentile - 10) {
                $insights[] = [
                    'type' => 'info',
                    'message' => "Witel memiliki kompetisi yang ketat - performance global lebih unggul",
                    'priority' => 'low'
                ];
            }
        }

        // Multi-divisi insights
        if (count($summary['divisi']) > 1) {
            $bestDivisi = collect($summary['divisi'])->sortBy('current_position')->first();
            $worstDivisi = collect($summary['divisi'])->sortByDesc('current_position')->first();

            if ($bestDivisi && $worstDivisi && $bestDivisi !== $worstDivisi) {
                $insights[] = [
                    'type' => 'info',
                    'message' => "Performance bervariasi antar divisi - {$bestDivisi['divisi_name']} terdepan",
                    'priority' => 'medium'
                ];
            }
        }

        // Status trend insights
        $statusCounts = collect([$summary['global'], $summary['witel']])
            ->merge($summary['divisi'])
            ->countBy('status');

        if (($statusCounts['naik'] ?? 0) > ($statusCounts['turun'] ?? 0)) {
            $insights[] = [
                'type' => 'success',
                'message' => "Trend ranking positif - menunjukkan improvement konsisten",
                'priority' => 'low'
            ];
        } elseif (($statusCounts['turun'] ?? 0) > ($statusCounts['naik'] ?? 0)) {
            $insights[] = [
                'type' => 'warning',
                'message' => "Perlu perhatian - trend ranking menunjukkan penurunan",
                'priority' => 'high'
            ];
        }

        return $insights;
    }

    private function analyzeTrendDirection($trends)
    {
        $globalPositions = array_filter(array_column($trends, 'global_position'));
        $witelPositions = array_filter(array_column($trends, 'witel_position'));

        $analysis = [
            'global_trend' => $this->calculatePositionTrend($globalPositions),
            'witel_trend' => $this->calculatePositionTrend($witelPositions),
            'consistency' => $this->calculateConsistency($globalPositions),
            'volatility' => $this->calculateVolatility($globalPositions)
        ];

        return $analysis;
    }

    private function calculatePositionTrend($positions)
    {
        if (count($positions) < 2) {
            return 'insufficient_data';
        }

        $first = reset($positions);
        $last = end($positions);

        if ($last < $first) {
            return 'improving'; // Lower position number = better ranking
        } elseif ($last > $first) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    private function calculateConsistency($positions)
    {
        if (count($positions) < 2) {
            return 0;
        }

        $mean = array_sum($positions) / count($positions);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $positions)) / count($positions);

        $stdDev = sqrt($variance);
        $coefficientVariation = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

        if ($coefficientVariation < 10) {
            return 'very_consistent';
        } elseif ($coefficientVariation < 20) {
            return 'consistent';
        } elseif ($coefficientVariation < 30) {
            return 'moderate';
        } else {
            return 'volatile';
        }
    }

    private function calculateVolatility($positions)
    {
        if (count($positions) < 2) {
            return 0;
        }

        $changes = [];
        for ($i = 1; $i < count($positions); $i++) {
            $changes[] = abs($positions[$i] - $positions[$i-1]);
        }

        return round(array_sum($changes) / count($changes), 2);
    }

    private function getBestMonth($trends)
    {
        $bestGlobal = collect($trends)
            ->filter(fn($t) => $t['global_position'] !== null)
            ->sortBy('global_position')
            ->first();

        return $bestGlobal ? [
            'month' => $bestGlobal['month_name'],
            'position' => $bestGlobal['global_position'],
            'achievement' => $bestGlobal['achievement_rate']
        ] : null;
    }

    private function getWorstMonth($trends)
    {
        $worstGlobal = collect($trends)
            ->filter(fn($t) => $t['global_position'] !== null)
            ->sortByDesc('global_position')
            ->first();

        return $worstGlobal ? [
            'month' => $worstGlobal['month_name'],
            'position' => $worstGlobal['global_position'],
            'achievement' => $worstGlobal['achievement_rate']
        ] : null;
    }

    private function calculateAveragePosition($trends, $positionKey)
    {
        $positions = array_filter(array_column($trends, $positionKey));
        return count($positions) > 0 ? round(array_sum($positions) / count($positions), 1) : 0;
    }

    /**
     * Export ranking data for reporting
     */
    public function exportRankingReport($accountManagerId, $startDate = null, $endDate = null, $format = 'array')
    {
        $summary = $this->getComprehensiveRankingSummary($accountManagerId, $startDate, $endDate);
        $trends = $this->getRankingTrends($accountManagerId, 6);

        $report = [
            'report_info' => [
                'account_manager_id' => $accountManagerId,
                'period' => $this->getPeriodContext($startDate, $endDate),
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'report_type' => 'ranking_analysis'
            ],
            'current_rankings' => $summary,
            'trend_analysis' => $trends,
            'recommendations' => $this->generateRankingRecommendations($summary, $trends)
        ];

        if ($format === 'json') {
            return json_encode($report, JSON_PRETTY_PRINT);
        }

        return $report;
    }

    /**
     * Generate ranking-based recommendations
     */
    private function generateRankingRecommendations($summary, $trends)
    {
        $recommendations = [];

        // Position-based recommendations
        if ($summary['global']['percentile'] < 50) {
            $recommendations[] = [
                'category' => 'performance',
                'priority' => 'high',
                'title' => 'Focus on Performance Improvement',
                'description' => 'Current global ranking needs significant improvement',
                'actions' => [
                    'Analyze top performers\' strategies',
                    'Identify key performance gaps',
                    'Implement targeted improvement plan'
                ]
            ];
        }

        // Trend-based recommendations
        if ($trends['analysis']['global_trend'] === 'declining') {
            $recommendations[] = [
                'category' => 'trend_reversal',
                'priority' => 'high',
                'title' => 'Address Declining Trend',
                'description' => 'Ranking shows consistent decline over recent months',
                'actions' => [
                    'Conduct root cause analysis',
                    'Review and adjust current strategies',
                    'Seek mentoring from higher-ranked peers'
                ]
            ];
        }

        // Consistency recommendations
        if ($trends['analysis']['consistency'] === 'volatile') {
            $recommendations[] = [
                'category' => 'stability',
                'priority' => 'medium',
                'title' => 'Improve Consistency',
                'description' => 'Ranking shows high volatility, indicating inconsistent performance',
                'actions' => [
                    'Develop more predictable revenue streams',
                    'Implement consistent monthly processes',
                    'Focus on customer retention strategies'
                ]
            ];
        }

        return $recommendations;
    }
}