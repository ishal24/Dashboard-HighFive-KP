<?php

namespace App\Services;

use App\Models\AccountManager;
use App\Models\AmRevenue;
use App\Models\CcRevenue;
use App\Models\Divisi;
use App\Models\Witel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AmPerformanceService
{
    /**
     * Get Global Ranking (seluruh AM di sistem)
     */
    public function getGlobalRanking($amId, $period, $divisiId = null)
    {
        try {
            // Get current period performance
            $currentRanking = $this->calculateGlobalRanking($period['tahun'], $period['bulan'], $divisiId);
            $currentPosition = $currentRanking->search(function($item) use ($amId) {
                return $item['am_id'] === $amId;
            });

            // Get previous month for comparison
            $previousMonth = $period['bulan'] - 1;
            $previousYear = $period['tahun'];
            if ($previousMonth < 1) {
                $previousMonth = 12;
                $previousYear--;
            }

            $previousRanking = $this->calculateGlobalRanking($previousYear, $previousMonth, $divisiId);
            $previousPosition = $previousRanking->search(function($item) use ($amId) {
                return $item['am_id'] === $amId;
            });

            // Calculate status and change
            $status = $this->getRankingStatus($currentPosition, $previousPosition);
            $change = $previousPosition !== false ? ($previousPosition - $currentPosition) : 0;

            return [
                'rank' => $currentPosition !== false ? $currentPosition + 1 : null,
                'total' => $currentRanking->count(),
                'status' => $status,
                'change' => $change,
                'percentile' => $currentPosition !== false
                    ? round((1 - ($currentPosition / $currentRanking->count())) * 100, 1)
                    : 0
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate global ranking', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyRanking();
        }
    }

    /**
     * Get Witel Ranking
     */
    public function getWitelRanking($amId, $period, $divisiId = null)
    {
        try {
            $accountManager = AccountManager::find($amId);
            if (!$accountManager) {
                return $this->getEmptyRanking();
            }

            $witelId = $accountManager->witel_id;

            // Current period
            $currentRanking = $this->calculateWitelRanking($witelId, $period['tahun'], $period['bulan'], $divisiId);
            $currentPosition = $currentRanking->search(function($item) use ($amId) {
                return $item['am_id'] === $amId;
            });

            // Previous month
            $previousMonth = $period['bulan'] - 1;
            $previousYear = $period['tahun'];
            if ($previousMonth < 1) {
                $previousMonth = 12;
                $previousYear--;
            }

            $previousRanking = $this->calculateWitelRanking($witelId, $previousYear, $previousMonth, $divisiId);
            $previousPosition = $previousRanking->search(function($item) use ($amId) {
                return $item['am_id'] === $amId;
            });

            $status = $this->getRankingStatus($currentPosition, $previousPosition);
            $change = $previousPosition !== false ? ($previousPosition - $currentPosition) : 0;

            return [
                'rank' => $currentPosition !== false ? $currentPosition + 1 : null,
                'total' => $currentRanking->count(),
                'status' => $status,
                'change' => $change,
                'witel_name' => $accountManager->witel->nama ?? 'N/A',
                'percentile' => $currentPosition !== false
                    ? round((1 - ($currentPosition / $currentRanking->count())) * 100, 1)
                    : 0
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate witel ranking', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyRanking();
        }
    }

    /**
     * Get Divisi Ranking
     */
    public function getDivisiRanking($amId, $divisiId, $period)
    {
        try {
            // Current period
            $currentRanking = $this->calculateDivisiRanking($divisiId, $period['tahun'], $period['bulan']);
            $currentPosition = $currentRanking->search(function($item) use ($amId) {
                return $item['am_id'] === $amId;
            });

            // Previous month
            $previousMonth = $period['bulan'] - 1;
            $previousYear = $period['tahun'];
            if ($previousMonth < 1) {
                $previousMonth = 12;
                $previousYear--;
            }

            $previousRanking = $this->calculateDivisiRanking($divisiId, $previousYear, $previousMonth);
            $previousPosition = $previousRanking->search(function($item) use ($amId) {
                return $item['am_id'] === $amId;
            });

            $status = $this->getRankingStatus($currentPosition, $previousPosition);
            $change = $previousPosition !== false ? ($previousPosition - $currentPosition) : 0;

            $divisi = Divisi::find($divisiId);

            return [
                'rank' => $currentPosition !== false ? $currentPosition + 1 : null,
                'total' => $currentRanking->count(),
                'status' => $status,
                'change' => $change,
                'divisi_name' => $divisi->nama ?? 'N/A',
                'divisi_kode' => $divisi->kode ?? 'N/A',
                'percentile' => $currentPosition !== false
                    ? round((1 - ($currentPosition / $currentRanking->count())) * 100, 1)
                    : 0
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate divisi ranking', [
                'am_id' => $amId,
                'divisi_id' => $divisiId,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyRanking();
        }
    }

    /**
     * Calculate Global Ranking berdasarkan achievement rate
     */
    private function calculateGlobalRanking($tahun, $bulan, $divisiId = null)
    {
        $query = AmRevenue::where('tahun', $tahun)
            ->where('bulan', '<=', $bulan);

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        $rankings = $query->selectRaw('
                account_manager_id as am_id,
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
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function($item) {
                return [
                    'am_id' => $item->am_id,
                    'achievement_rate' => round($item->achievement_rate, 2),
                    'total_revenue' => $item->total_revenue
                ];
            });

        return $rankings;
    }

    /**
     * Calculate Witel Ranking
     */
    private function calculateWitelRanking($witelId, $tahun, $bulan, $divisiId = null)
    {
        $query = AmRevenue::whereHas('accountManager', function($q) use ($witelId) {
                $q->where('witel_id', $witelId);
            })
            ->where('tahun', $tahun)
            ->where('bulan', '<=', $bulan);

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        $rankings = $query->selectRaw('
                account_manager_id as am_id,
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
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function($item) {
                return [
                    'am_id' => $item->am_id,
                    'achievement_rate' => round($item->achievement_rate, 2),
                    'total_revenue' => $item->total_revenue
                ];
            });

        return $rankings;
    }

    /**
     * Calculate Divisi Ranking
     */
    private function calculateDivisiRanking($divisiId, $tahun, $bulan)
    {
        $rankings = AmRevenue::whereHas('accountManager.divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            })
            ->where('divisi_id', $divisiId)
            ->where('tahun', $tahun)
            ->where('bulan', '<=', $bulan)
            ->selectRaw('
                account_manager_id as am_id,
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
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function($item) {
                return [
                    'am_id' => $item->am_id,
                    'achievement_rate' => round($item->achievement_rate, 2),
                    'total_revenue' => $item->total_revenue
                ];
            });

        return $rankings;
    }

    /**
     * Determine ranking status (naik/turun/tetap)
     */
    public function getRankingStatus($currentRank, $previousRank)
    {
        if ($previousRank === false || $previousRank === null) {
            return 'baru'; // New entry
        }

        if ($currentRank === false || $currentRank === null) {
            return 'unknown';
        }

        if ($currentRank < $previousRank) {
            return 'naik';
        } elseif ($currentRank > $previousRank) {
            return 'turun';
        } else {
            return 'tetap';
        }
    }

    /**
     * Get Performance Summary
     * FIXED: Now properly implements summary_mode filtering
     */
    public function getPerformanceSummary($amId, $filters)
    {
        try {
            $query = AmRevenue::where('account_manager_id', $amId);

            // Apply divisi filter
            if (isset($filters['divisi_id']) && $filters['divisi_id']) {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            // Apply tipe_revenue filter
            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] !== 'all') {
                $query->whereHas('corporateCustomer.ccRevenues', function($q) use ($filters) {
                    $q->where('tipe_revenue', $filters['tipe_revenue']);
                });
            }

            // FIXED: Apply summary_mode filter
            $summaryMode = $filters['summary_mode'] ?? 'all_time';
            $scopeDescription = '';

            switch ($summaryMode) {
                case 'specific_year':
                    if (isset($filters['summary_year']) && $filters['summary_year']) {
                        $query->where('tahun', $filters['summary_year']);
                        $scopeDescription = "Tahun {$filters['summary_year']}";
                    }
                    break;

                case 'range_years':
                    if (isset($filters['summary_year_start']) && isset($filters['summary_year_end'])) {
                        $query->whereBetween('tahun', [
                            $filters['summary_year_start'],
                            $filters['summary_year_end']
                        ]);
                        $scopeDescription = "Tahun {$filters['summary_year_start']} - {$filters['summary_year_end']}";
                    }
                    break;

                case 'all_time':
                default:
                    // No additional filter - query all available data
                    $scopeDescription = 'Sepanjang Waktu';
                    break;
            }

            // Clone query for different calculations
            $baseQuery = clone $query;

            // All time/scoped summary
            $allTimeData = $query->selectRaw('
                    SUM(real_revenue) as total_revenue_all_time,
                    SUM(target_revenue) as total_target_all_time
                ')
                ->first();

            // Highest achievement month (within scope)
            $highestAchievement = (clone $baseQuery)->selectRaw('
                    tahun,
                    bulan,
                    SUM(real_revenue) as revenue,
                    SUM(target_revenue) as target,
                    (SUM(real_revenue) / SUM(target_revenue)) * 100 as achievement_rate
                ')
                ->groupBy('tahun', 'bulan')
                ->having('target', '>', 0)
                ->orderByDesc('achievement_rate')
                ->first();

            // Highest revenue month (within scope)
            $highestRevenue = (clone $baseQuery)->selectRaw('
                    tahun,
                    bulan,
                    SUM(real_revenue) as revenue
                ')
                ->groupBy('tahun', 'bulan')
                ->orderByDesc('revenue')
                ->first();

            // Average achievement (within scope)
            $monthlyAchievements = (clone $baseQuery)->selectRaw('
                    tahun,
                    bulan,
                    (SUM(real_revenue) / SUM(target_revenue)) * 100 as achievement_rate
                ')
                ->groupBy('tahun', 'bulan')
                ->havingRaw('SUM(target_revenue) > 0')
                ->get();

            $averageAchievement = $monthlyAchievements->avg('achievement_rate');

            // Trend calculation (last 3 months from latest data in scope)
            $trend = $this->calculateTrendWithinScope($amId, 3, $filters);

            return [
                'scope' => $summaryMode,
                'scope_description' => $scopeDescription,
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
                'trend_description' => $trend['description'],
                'data_points_count' => $monthlyAchievements->count()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate performance summary', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyPerformanceSummary();
        }
    }

    /**
     * Calculate trend within specific scope
     * NEW: Supports summary_mode filtering
     */
    private function calculateTrendWithinScope($amId, $months = 3, $filters = [])
    {
        try {
            $query = AmRevenue::where('account_manager_id', $amId);

            // Apply divisi filter
            if (isset($filters['divisi_id']) && $filters['divisi_id']) {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            // Apply summary_mode scope
            $summaryMode = $filters['summary_mode'] ?? 'all_time';

            switch ($summaryMode) {
                case 'specific_year':
                    if (isset($filters['summary_year'])) {
                        $query->where('tahun', $filters['summary_year']);
                    }
                    break;

                case 'range_years':
                    if (isset($filters['summary_year_start']) && isset($filters['summary_year_end'])) {
                        $query->whereBetween('tahun', [
                            $filters['summary_year_start'],
                            $filters['summary_year_end']
                        ]);
                    }
                    break;
            }

            // Get latest N months from available data in scope
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
                    'description' => 'Data tidak cukup untuk analisis tren (minimal 2 bulan)'
                ];
            }

            // Build month filter for trend calculation
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
                    'description' => 'Data tidak cukup untuk analisis tren'
                ];
            }

            // Calculate linear regression slope
            $x = range(1, $monthlyData->count());
            $y = $monthlyData->pluck('achievement_rate')->toArray();

            $n = count($x);
            $sumX = array_sum($x);
            $sumY = array_sum($y);
            $sumXY = 0;
            $sumX2 = 0;

            for ($i = 0; $i < $n; $i++) {
                $sumXY += $x[$i] * $y[$i];
                $sumX2 += $x[$i] * $x[$i];
            }

            $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

            // Calculate percentage change
            $firstValue = $y[0];
            $lastValue = $y[$n - 1];
            $percentageChange = $firstValue != 0
                ? (($lastValue - $firstValue) / $firstValue) * 100
                : 0;

            // Determine status with ±2% threshold
            $status = 'stabil';
            $description = "Performa relatif stabil dalam {$n} bulan terakhir";

            if ($percentageChange > 2) {
                $status = 'naik';
                $description = sprintf('Tren meningkat %.1f%% dalam %d bulan terakhir', $percentageChange, $n);
            } elseif ($percentageChange < -2) {
                $status = 'turun';
                $description = sprintf('Tren menurun %.1f%% dalam %d bulan terakhir', abs($percentageChange), $n);
            }

            return [
                'status' => $status,
                'percentage' => round($percentageChange, 2),
                'description' => $description,
                'slope' => round($slope, 4),
                'months_analyzed' => $n
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate trend within scope', [
                'am_id' => $amId,
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
     * Calculate trend (LEGACY - kept for backward compatibility)
     * Use calculateTrendWithinScope() for new implementations
     */
    public function calculateTrend($amId, $months = 3, $filters = [])
    {
        try {
            $endDate = Carbon::now();
            $startDate = $endDate->copy()->subMonths($months);

            $query = AmRevenue::where('account_manager_id', $amId)
                ->where('tahun', '>=', $startDate->year)
                ->where(function($q) use ($startDate, $endDate) {
                    $q->where(function($subq) use ($startDate) {
                        $subq->where('tahun', $startDate->year)
                            ->where('bulan', '>=', $startDate->month);
                    })
                    ->orWhere(function($subq) use ($endDate) {
                        $subq->where('tahun', $endDate->year)
                            ->where('bulan', '<=', $endDate->month);
                    });
                });

            if (isset($filters['divisi_id']) && $filters['divisi_id']) {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            $monthlyData = $query->selectRaw('
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
                    'description' => 'Data tidak cukup untuk analisis tren'
                ];
            }

            // Calculate linear regression slope
            $x = range(1, $monthlyData->count());
            $y = $monthlyData->pluck('achievement_rate')->toArray();

            $n = count($x);
            $sumX = array_sum($x);
            $sumY = array_sum($y);
            $sumXY = 0;
            $sumX2 = 0;

            for ($i = 0; $i < $n; $i++) {
                $sumXY += $x[$i] * $y[$i];
                $sumX2 += $x[$i] * $x[$i];
            }

            $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

            // Calculate percentage change
            $firstValue = $y[0];
            $lastValue = $y[$n - 1];
            $percentageChange = $firstValue != 0
                ? (($lastValue - $firstValue) / $firstValue) * 100
                : 0;

            // Determine status with ±2% threshold
            $status = 'stabil';
            $description = 'Performa relatif stabil dalam 3 bulan terakhir';

            if ($percentageChange > 2) {
                $status = 'naik';
                $description = sprintf('Tren meningkat %.1f%% dalam 3 bulan terakhir', $percentageChange);
            } elseif ($percentageChange < -2) {
                $status = 'turun';
                $description = sprintf('Tren menurun %.1f%% dalam 3 bulan terakhir', abs($percentageChange));
            }

            return [
                'status' => $status,
                'percentage' => round($percentageChange, 2),
                'description' => $description,
                'slope' => round($slope, 4)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to calculate trend', [
                'am_id' => $amId,
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
     * Get Monthly Performance Chart Data
     */
    public function getMonthlyPerformanceChart($amId, $tahun, $filters)
    {
        try {
            $query = AmRevenue::where('account_manager_id', $amId)
                ->where('tahun', $tahun);

            if (isset($filters['divisi_id']) && $filters['divisi_id']) {
                $query->where('divisi_id', $filters['divisi_id']);
            }

            if (isset($filters['tipe_revenue']) && $filters['tipe_revenue'] !== 'all') {
                $query->whereHas('corporateCustomer.ccRevenues', function($q) use ($filters, $tahun) {
                    $q->where('tipe_revenue', $filters['tipe_revenue'])
                      ->where('tahun', $tahun);
                });
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

            // Fill all 12 months (with 0 for missing months)
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
            Log::error('Failed to get monthly chart data', [
                'am_id' => $amId,
                'tahun' => $tahun,
                'error' => $e->getMessage()
            ]);

            return $this->getEmptyChartData();
        }
    }

    /**
     * Get comparison data with other AMs
     */
    public function getComparisonData($amId, $scope, $filters)
    {
        try {
            $accountManager = AccountManager::find($amId);
            if (!$accountManager) {
                throw new \Exception('Account Manager not found');
            }

            $query = AmRevenue::where('tahun', $filters['tahun']);

            // Apply scope filter
            switch ($scope) {
                case 'witel':
                    $query->whereHas('accountManager', function($q) use ($accountManager) {
                        $q->where('witel_id', $accountManager->witel_id);
                    });
                    break;

                case 'divisi':
                    if ($filters['divisi_id']) {
                        $query->where('divisi_id', $filters['divisi_id']);
                    }
                    break;

                case 'global':
                default:
                    // No additional filter
                    break;
            }

            // Get all AMs performance in scope
            $rankings = $query->selectRaw('
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
                ->get();

            // Find current AM position
            $myPosition = $rankings->search(function($item) use ($amId) {
                return $item->account_manager_id == $amId;
            });

            $myData = $rankings->get($myPosition);

            // Calculate statistics
            $avgAchievement = $rankings->avg('achievement_rate');
            $medianAchievement = $rankings->median('achievement_rate');
            $topQuartile = $rankings->take(ceil($rankings->count() / 4))->avg('achievement_rate');

            return [
                'scope' => $scope,
                'my_performance' => [
                    'rank' => $myPosition !== false ? $myPosition + 1 : null,
                    'achievement_rate' => $myData ? round($myData->achievement_rate, 2) : 0,
                    'total_revenue' => $myData ? floatval($myData->total_revenue) : 0
                ],
                'benchmarks' => [
                    'average' => round($avgAchievement, 2),
                    'median' => round($medianAchievement, 2),
                    'top_quartile' => round($topQuartile, 2),
                    'my_vs_average' => $myData ? round($myData->achievement_rate - $avgAchievement, 2) : 0,
                    'my_vs_median' => $myData ? round($myData->achievement_rate - $medianAchievement, 2) : 0
                ],
                'total_ams' => $rankings->count(),
                'percentile' => $myPosition !== false
                    ? round((1 - ($myPosition / $rankings->count())) * 100, 1)
                    : 0
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get comparison data', [
                'am_id' => $amId,
                'scope' => $scope,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get historical trend (multi-year)
     */
    public function getHistoricalTrend($amId, $years, $metric)
    {
        try {
            $currentYear = date('Y');
            $startYear = $currentYear - $years + 1;

            $query = AmRevenue::where('account_manager_id', $amId)
                ->where('tahun', '>=', $startYear)
                ->where('tahun', '<=', $currentYear);

            if ($metric === 'achievement') {
                $data = $query->selectRaw('
                        tahun,
                        bulan,
                        (SUM(real_revenue) / SUM(target_revenue)) * 100 as value
                    ')
                    ->groupBy('tahun', 'bulan')
                    ->havingRaw('SUM(target_revenue) > 0')
                    ->orderBy('tahun')
                    ->orderBy('bulan')
                    ->get();
            } else {
                $data = $query->selectRaw('
                        tahun,
                        bulan,
                        SUM(real_revenue) as value
                    ')
                    ->groupBy('tahun', 'bulan')
                    ->orderBy('tahun')
                    ->orderBy('bulan')
                    ->get();
            }

            return [
                'metric' => $metric,
                'years' => $years,
                'data' => $data->map(function($item) {
                    return [
                        'period' => $this->getMonthName($item->bulan) . ' ' . $item->tahun,
                        'value' => round($item->value, 2)
                    ];
                })
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get historical trend', [
                'am_id' => $amId,
                'error' => $e->getMessage()
            ]);

            return [
                'metric' => $metric,
                'years' => $years,
                'data' => []
            ];
        }
    }

    /**
     * Helper: Get empty ranking structure
     */
    private function getEmptyRanking()
    {
        return [
            'rank' => null,
            'total' => 0,
            'status' => 'unknown',
            'change' => 0,
            'percentile' => 0
        ];
    }

    /**
     * Helper: Get empty performance summary
     */
    private function getEmptyPerformanceSummary()
    {
        return [
            'scope' => 'all_time',
            'scope_description' => 'Sepanjang Waktu',
            'total_revenue_all_time' => 0,
            'total_target_all_time' => 0,
            'overall_achievement_rate' => 0,
            'highest_achievement' => ['bulan' => 'N/A', 'value' => 0],
            'highest_revenue' => ['bulan' => 'N/A', 'value' => 0],
            'average_achievement' => 0,
            'trend' => 'unknown',
            'trend_percentage' => 0,
            'trend_description' => 'Data tidak tersedia',
            'data_points_count' => 0
        ];
    }

    /**
     * Helper: Get empty chart data
     */
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

    /**
     * Helper: Get month name
     */
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

    /**
     * Helper: Get short month name
     */
    private function getShortMonthName($monthNumber)
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        return $months[$monthNumber] ?? 'N/A';
    }
}