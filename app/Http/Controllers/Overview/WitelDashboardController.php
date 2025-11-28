<?php

namespace App\Http\Controllers\Overview;

use App\Http\Controllers\Controller;
use App\Models\Witel;
use App\Models\AccountManager;
use App\Models\Divisi;
use App\Models\CcRevenue;
use App\Models\AmRevenue;
use App\Models\CorporateCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WitelDashboardController extends Controller
{
    /**
     * Show Witel Detail Dashboard
     */
    public function show($id, Request $request)
    {
        try {
            $witel = Witel::findOrFail($id);
            $user = Auth::user();

            // Authorization check
            if ($user->role === 'witel_support' && $user->witel_id !== $id) {
                abort(403, 'Unauthorized access to this witel');
            }

            $filters = $this->extractFilters($request);

            // Profile Data
            $profileData = $this->getWitelProfile($id);

            // Summary Cards
            $cardData = $this->getCardGroupData($id, $filters);

            // Revenue Table Data
            $revenueData = $this->getRevenueTabData($id, $filters);

            // Revenue Analysis
            $revenueAnalysis = $this->getRevenueAnalysisData($id, $filters);

            // Filter Options
            $filterOptions = $this->getFilterOptions($id);

            Log::info('Witel dashboard loaded successfully', [
                'witel_id' => $id,
                'witel_name' => $witel->nama
            ]);

            return view('witel.detailWitel', compact(
                'witel',
                'profileData',
                'cardData',
                'revenueData',
                'revenueAnalysis',
                'filterOptions',
                'filters'
            ));
        } catch (\Exception $e) {
            Log::error('Witel dashboard rendering failed', [
                'witel_id' => $id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            Log::info("WitelDashboardController - redirect to dashboard about to begin");

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat detail Witel: ' . $e->getMessage());
        }
    }

    /**
     * Get Revenue Summary
     */
    private function getRevenueSummary($witelId, $filters)
    {
        try {
            // Get monthly achievements
            $monthlyAchievements = $this->getMonthlyAchievements($witelId);

            Log::info("WitelDashboardController", ['monthlyAchievements' => $monthlyAchievements]);

            if ($monthlyAchievements->isEmpty()) {
                return $this->getEmptySummary();
            }

            // Calculate all-time totals
            $totalRevenue = $monthlyAchievements->sum('total_revenue');
            $totalTarget = $monthlyAchievements->sum('total_target');
            $overallAchievement = $totalTarget > 0
                ? round(($totalRevenue / $totalTarget) * 100, 2)
                : 0;

            // Find highest achievement month
            $highestAchievement = $monthlyAchievements->sortByDesc('achievement_rate')->first();

            // Find highest revenue month
            $highestRevenue = $monthlyAchievements->sortByDesc('total_revenue')->first();

            // Calculate average achievement
            $avgAchievement = $monthlyAchievements->avg('achievement_rate');

            // Calculate trend
            $trend = $this->calculateTrend($witelId, 3, $filters);

            return [
                'total_revenue_all_time' => floatval($totalRevenue),
                'total_target_all_time' => floatval($totalTarget),
                'overall_achievement_rate' => $overallAchievement,
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
                    'value' => $highestRevenue ? floatval($highestRevenue->total_revenue) : 0
                ],
                'average_achievement' => round($avgAchievement, 2),
                'trend' => $trend['status'],
                'trend_percentage' => $trend['percentage'],
                'trend_description' => $trend['description']
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get revenue summary', [
                'witel_id' => $witelId,
                'error' => $e->getMessage()
            ]);
            return $this->getEmptySummary();
        }
    }

    /**
     * Get Witel Profile Data
     */
    private function getWitelProfile($witelId)
    {
        $witel = Witel::findOrFail($witelId);

        // Count Account Managers (AM + HOTDA)
        $totalAM = AccountManager::where('witel_id', $witelId)
            ->where('role', 'AM')
            ->count();

        $totalHOTDA = AccountManager::where('witel_id', $witelId)
            ->where('role', 'HOTDA')
            ->count();

        // Count unique customers from both cc_revenues and am_revenues
        $customersFromCC = DB::table('cc_revenues')
            ->where(function ($query) use ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    // DPS: use witel_bill_id
                    $q->where('divisi_id', 3)
                        ->where('witel_bill_id', $witelId);
                })->orWhere(function ($q) use ($witelId) {
                    // DGS & DSS: use witel_ho_id
                    $q->whereIn('divisi_id', [1, 2])
                        ->where('witel_ho_id', $witelId);
                });
            })
            ->distinct()
            ->pluck('corporate_customer_id');

        $customersFromAM = DB::table('am_revenues')
            ->where('witel_id', $witelId)
            ->distinct()
            ->pluck('corporate_customer_id');

        $totalCustomers = $customersFromCC->merge($customersFromAM)->unique()->count();

        return [
            'id' => $witel->id,
            'nama' => $witel->nama,
            'total_am' => $totalAM,
            'total_hotda' => $totalHOTDA,
            'total_account_managers' => $totalAM + $totalHOTDA,
            'total_customers' => $totalCustomers
        ];
    }

    /**
     * Extract filters from request
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
            'granularity' => $request->get('granularity', 'account_manager'),
            'role_filter' => $request->get('role_filter', 'all'),
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
    private function getCardGroupData($witelId, $filters)
    {
        // Revenue from cc_revenues (with DPS/DGS/DSS rule)
        $ccQuery = CcRevenue::where('tahun', $filters['tahun'])
            ->where(function ($query) use ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    // DPS: use witel_bill_id
                    $q->where('divisi_id', 3)
                        ->where('witel_bill_id', $witelId);
                })->orWhere(function ($q) use ($witelId) {
                    // DGS & DSS: use witel_ho_id
                    $q->whereIn('divisi_id', [1, 2])
                        ->where('witel_ho_id', $witelId);
                });
            });

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $ccQuery->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $ccQuery->where('revenue_source', $filters['revenue_source']);
        }

        if ($filters['period_type'] === 'MTD') {
            $ccQuery->where('bulan', $filters['bulan_end']);
        } else {
            $ccQuery->where('bulan', '<=', $filters['bulan_end']);
        }

        $ccAggregated = $ccQuery->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->first();

        // Revenue from am_revenues
        $amQuery = AmRevenue::where('witel_id', $witelId)
            ->where('tahun', $filters['tahun']);

        if ($filters['period_type'] === 'MTD') {
            $amQuery->where('bulan', $filters['bulan_end']);
        } else {
            $amQuery->where('bulan', '<=', $filters['bulan_end']);
        }

        $amAggregated = $amQuery->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->first();

        // Combine both sources
        $totalRevenue = ($ccAggregated->total_revenue ?? 0) + ($amAggregated->total_revenue ?? 0);
        $totalTarget = ($ccAggregated->total_target ?? 0) + ($amAggregated->total_target ?? 0);
        $achievementRate = $totalTarget > 0
            ? round(($totalRevenue / $totalTarget) * 100, 2)
            : 0;

        return [
            'total_revenue' => floatval($totalRevenue),
            'total_target' => floatval($totalTarget),
            'achievement_rate' => $achievementRate,
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'period_text' => $this->generatePeriodText($filters),
            'cc_revenue' => floatval($ccAggregated->total_revenue ?? 0),
            'am_revenue' => floatval($amAggregated->total_revenue ?? 0)
        ];
    }

    /**
     * Get Revenue Tab Data
     */
    private function getRevenueTabData($witelId, $filters)
    {
        $viewMode = $filters['revenue_view_mode'];
        $granularity = $filters['granularity'];

        $availableYears = $this->getAvailableYears($witelId);

        switch ($viewMode) {
            case 'agregat_bulan':
                if ($granularity === 'account_manager') {
                    $revenueData = $this->getRevenueDataAggregateByMonthAndAM($witelId, $filters);
                } elseif ($granularity === 'divisi') {
                    $revenueData = $this->getRevenueDataAggregateByMonthAndDivisi($witelId, $filters);
                } elseif ($granularity === 'corporate_customer') {
                    $revenueData = $this->getRevenueDataAggregateByMonthAndCustomer($witelId, $filters);
                } else {
                    $revenueData = collect([]);
                }
                break;
            case 'detail':
            default:
                if ($granularity === 'account_manager') {
                    $revenueData = $this->getRevenueDataDetailByAM($witelId, $filters);
                } elseif ($granularity === 'divisi') {
                    $revenueData = $this->getRevenueDataDetailByDivisi($witelId, $filters);
                } elseif ($granularity === 'corporate_customer') {
                    $revenueData = $this->getRevenueDataDetailByCustomer($witelId, $filters);
                } else {
                    $revenueData = collect([]);
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
            'revenue_source' => $filters['revenue_source'],
            'role_filter' => $filters['role_filter']
        ];
    }

    /**
     * Get Revenue Data Detail by Account Manager
     */
    private function getRevenueDataDetailByAM($witelId, $filters)
    {
        $query = AccountManager::where('witel_id', $witelId);

        if ($filters['role_filter'] && $filters['role_filter'] !== 'all') {
            $query->where('role', $filters['role_filter']);
        }

        $accountManagers = $query->with(['amRevenues' => function ($q) use ($filters) {
            $q->where('tahun', $filters['tahun'])
                ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']])
                ->with('divisi');
        }])->get();

        $results = collect([]);

        foreach ($accountManagers as $am) {
            foreach ($am->amRevenues as $revenue) {
                $achievementRate = $revenue->target_revenue > 0
                    ? round(($revenue->real_revenue / $revenue->target_revenue) * 100, 2)
                    : 0;

                $results->push((object)[
                    'bulan' => $revenue->bulan,
                    'bulan_name' => $this->getMonthName($revenue->bulan),
                    'account_manager' => $am->nama,
                    'nik' => $am->nik,
                    'role' => $am->role,
                    'divisi' => $revenue->divisi ? $revenue->divisi->nama : 'N/A',
                    'revenue' => floatval($revenue->real_revenue),
                    'target' => floatval($revenue->target_revenue),
                    'achievement_rate' => $achievementRate,
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }
        }

        return $results->sortBy('bulan')->values();
    }

    /**
     * Get Revenue Data Aggregate by Month and Account Manager
     */
    private function getRevenueDataAggregateByMonthAndAM($witelId, $filters)
    {
        $query = AccountManager::where('witel_id', $witelId);

        if ($filters['role_filter'] && $filters['role_filter'] !== 'all') {
            $query->where('role', $filters['role_filter']);
        }

        $accountManagers = $query->with(['amRevenues' => function ($q) use ($filters) {
            $q->where('tahun', $filters['tahun'])
                ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']]);
        }])->get();

        $results = collect([]);

        foreach ($accountManagers as $am) {
            $monthlyData = $am->amRevenues->groupBy('bulan');

            foreach ($monthlyData as $bulan => $revenues) {
                $totalRevenue = $revenues->sum('real_revenue');
                $totalTarget = $revenues->sum('target_revenue');
                $achievementRate = $totalTarget > 0
                    ? round(($totalRevenue / $totalTarget) * 100, 2)
                    : 0;

                $results->push((object)[
                    'bulan' => $bulan,
                    'bulan_name' => $this->getMonthName($bulan),
                    'account_manager' => $am->nama,
                    'nik' => $am->nik,
                    'role' => $am->role,
                    'total_revenue' => floatval($totalRevenue),
                    'total_target' => floatval($totalTarget),
                    'achievement_rate' => $achievementRate,
                    'achievement_color' => $this->getAchievementColor($achievementRate)
                ]);
            }
        }

        return $results->sortBy('bulan')->values();
    }

    /**
     * Get Revenue Data Detail by Divisi
     */
    private function getRevenueDataDetailByDivisi($witelId, $filters)
    {
        $ccQuery = CcRevenue::where('tahun', $filters['tahun'])
            ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']])
            ->where(function ($query) use ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    $q->where('divisi_id', 3)->where('witel_bill_id', $witelId);
                })->orWhere(function ($q) use ($witelId) {
                    $q->whereIn('divisi_id', [1, 2])->where('witel_ho_id', $witelId);
                });
            })
            ->with('divisi');

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $ccQuery->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $ccQuery->where('revenue_source', $filters['revenue_source']);
        }

        $ccData = $ccQuery->get();

        $amData = AmRevenue::where('witel_id', $witelId)
            ->where('tahun', $filters['tahun'])
            ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']])
            ->with('divisi')
            ->get();

        $results = collect([]);

        foreach ($ccData as $revenue) {
            $achievementRate = $revenue->target_revenue > 0
                ? round(($revenue->real_revenue / $revenue->target_revenue) * 100, 2)
                : 0;

            $results->push((object)[
                'bulan' => $revenue->bulan,
                'bulan_name' => $this->getMonthName($revenue->bulan),
                'divisi' => $revenue->divisi ? $revenue->divisi->nama : 'N/A',
                'source' => 'CC Revenue',
                'revenue' => floatval($revenue->real_revenue),
                'target' => floatval($revenue->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ]);
        }

        foreach ($amData as $revenue) {
            $achievementRate = $revenue->target_revenue > 0
                ? round(($revenue->real_revenue / $revenue->target_revenue) * 100, 2)
                : 0;

            $results->push((object)[
                'bulan' => $revenue->bulan,
                'bulan_name' => $this->getMonthName($revenue->bulan),
                'divisi' => $revenue->divisi ? $revenue->divisi->nama : 'N/A',
                'source' => 'AM Revenue',
                'revenue' => floatval($revenue->real_revenue),
                'target' => floatval($revenue->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ]);
        }

        return $results->sortBy('bulan')->values();
    }

    /**
     * Get Revenue Data Aggregate by Month and Divisi
     */
    private function getRevenueDataAggregateByMonthAndDivisi($witelId, $filters)
    {
        $divisis = Divisi::all();
        $results = collect([]);

        foreach ($divisis as $divisi) {
            $ccQuery = CcRevenue::where('tahun', $filters['tahun'])
                ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']])
                ->where('divisi_id', $divisi->id)
                ->where(function ($query) use ($witelId, $divisi) {
                    if ($divisi->id == 3) {
                        $query->where('witel_bill_id', $witelId);
                    } else {
                        $query->where('witel_ho_id', $witelId);
                    }
                });

            if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $ccQuery->where('tipe_revenue', $filters['tipe_revenue']);
            }

            if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $ccQuery->where('revenue_source', $filters['revenue_source']);
            }

            $ccMonthly = $ccQuery->selectRaw('
                    bulan,
                    SUM(real_revenue) as monthly_revenue,
                    SUM(target_revenue) as monthly_target
                ')
                ->groupBy('bulan')
                ->get();

            $amMonthly = AmRevenue::where('witel_id', $witelId)
                ->where('tahun', $filters['tahun'])
                ->where('divisi_id', $divisi->id)
                ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']])
                ->selectRaw('
                    bulan,
                    SUM(real_revenue) as monthly_revenue,
                    SUM(target_revenue) as monthly_target
                ')
                ->groupBy('bulan')
                ->get();

            $combinedData = $ccMonthly->concat($amMonthly)
                ->groupBy('bulan')
                ->map(function ($items, $bulan) use ($divisi) {
                    $totalRevenue = $items->sum('monthly_revenue');
                    $totalTarget = $items->sum('monthly_target');
                    $achievementRate = $totalTarget > 0
                        ? round(($totalRevenue / $totalTarget) * 100, 2)
                        : 0;

                    return (object)[
                        'bulan' => $bulan,
                        'bulan_name' => $this->getMonthName($bulan),
                        'divisi' => $divisi->nama,
                        'total_revenue' => floatval($totalRevenue),
                        'total_target' => floatval($totalTarget),
                        'achievement_rate' => $achievementRate,
                        'achievement_color' => $this->getAchievementColor($achievementRate)
                    ];
                });

            $results = $results->concat($combinedData->values());
        }

        return $results->sortBy('bulan')->values();
    }

    /**
     * Get Revenue Data Detail by Corporate Customer
     */
    private function getRevenueDataDetailByCustomer($witelId, $filters)
    {
        $query = CcRevenue::where('tahun', $filters['tahun'])
            ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']])
            ->where(function ($query) use ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    $q->where('divisi_id', 3)->where('witel_bill_id', $witelId);
                })->orWhere(function ($q) use ($witelId) {
                    $q->whereIn('divisi_id', [1, 2])->where('witel_ho_id', $witelId);
                });
            })
            ->with(['corporateCustomer', 'divisi']);

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $ccData = $query->get();

        return $ccData->map(function ($revenue) {
            $achievementRate = $revenue->target_revenue > 0
                ? round(($revenue->real_revenue / $revenue->target_revenue) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $revenue->bulan,
                'bulan_name' => $this->getMonthName($revenue->bulan),
                'customer_name' => $revenue->corporateCustomer ? $revenue->corporateCustomer->nama : 'N/A',
                'nipnas' => $revenue->corporateCustomer ? $revenue->corporateCustomer->nipnas : 'N/A',
                'divisi' => $revenue->divisi ? $revenue->divisi->nama : 'N/A',
                'revenue' => floatval($revenue->real_revenue),
                'target' => floatval($revenue->target_revenue),
                'achievement_rate' => $achievementRate,
                'achievement_color' => $this->getAchievementColor($achievementRate)
            ];
        })->sortBy('bulan')->values();
    }

    /**
     * Get Revenue Data Aggregate by Month and Customer
     */
    private function getRevenueDataAggregateByMonthAndCustomer($witelId, $filters)
    {
        $query = CcRevenue::where('tahun', $filters['tahun'])
            ->whereBetween('bulan', [$filters['bulan_start'], $filters['bulan_end']])
            ->where(function ($query) use ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    $q->where('divisi_id', 3)->where('witel_bill_id', $witelId);
                })->orWhere(function ($q) use ($witelId) {
                    $q->whereIn('divisi_id', [1, 2])->where('witel_ho_id', $witelId);
                });
            })
            ->with('corporateCustomer');

        if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
            $query->where('tipe_revenue', $filters['tipe_revenue']);
        }

        if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
            $query->where('revenue_source', $filters['revenue_source']);
        }

        $data = $query->selectRaw('
                bulan,
                corporate_customer_id,
                SUM(real_revenue) as monthly_revenue,
                SUM(target_revenue) as monthly_target
            ')
            ->groupBy('bulan', 'corporate_customer_id')
            ->orderBy('bulan')
            ->get();

        return $data->map(function ($item) {
            $customer = CorporateCustomer::find($item->corporate_customer_id);
            $achievementRate = $item->monthly_target > 0
                ? round(($item->monthly_revenue / $item->monthly_target) * 100, 2)
                : 0;

            return (object)[
                'bulan' => $item->bulan,
                'bulan_name' => $this->getMonthName($item->bulan),
                'customer_name' => $customer ? $customer->nama : 'N/A',
                'nipnas' => $customer ? $customer->nipnas : 'N/A',
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
    private function getRevenueAnalysisData($witelId, $filters)
    {
        $summary = $this->getRevenueSummary($witelId, $filters);
        $monthlyChart = $this->getMonthlyRevenueChart($witelId, $filters['chart_tahun'] ?? $filters['tahun'], $filters);

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
     * Get Monthly Achievements
     */
    private function getMonthlyAchievements($witelId)
    {
        $ccMonthly = DB::table('cc_revenues')
            ->where(function ($query) use ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    $q->where('divisi_id', 3)->where('witel_bill_id', $witelId);
                })->orWhere(function ($q) use ($witelId) {
                    $q->whereIn('divisi_id', [1, 2])->where('witel_ho_id', $witelId);
                });
            })
            ->selectRaw('
                tahun,
                bulan,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('tahun', 'bulan')
            ->get();

        Log::info("WitelDashboardController", ['ccMonthly' => $ccMonthly]);

        $amMonthly = AmRevenue::where('witel_id', $witelId)
            ->selectRaw('
                tahun,
                bulan,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('tahun', 'bulan')
            ->get();

        Log::info("WitelDashboardController", ['amMonthly' => $amMonthly]);

        $combined = collect([]);
        $allData = $ccMonthly->concat($amMonthly);

        $grouped = $allData->groupBy(function ($item) {
            return $item->tahun . '-' . $item->bulan;
        });

        foreach ($grouped as $key => $items) {
            $parts = explode('-', $key);
            $totalRevenue = $items->sum('total_revenue');
            $totalTarget = $items->sum('total_target');
            $achievementRate = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $combined->push((object)[
                'tahun' => $parts[0],
                'bulan' => $parts[1],
                'total_revenue' => $totalRevenue,
                'total_target' => $totalTarget,
                'achievement_rate' => round($achievementRate, 2)
            ]);
        }

        Log::info("WitelDashboardController", ['combined' => $combined]);

        return $combined->sortByDesc(function ($item) {
            return $item->tahun . str_pad($item->bulan, 2, '0', STR_PAD_LEFT);
        })->values();
    }

    /**
     * Calculate Revenue Trend
     */
    private function calculateTrend($witelId, $months = 3, $filters = [])
    {
        try {
            $monthlyData = $this->getMonthlyAchievements($witelId)->take($months);

            if ($monthlyData->count() < 2) {
                return [
                    'status' => 'insufficient_data',
                    'percentage' => 0,
                    'description' => 'Data tidak cukup untuk analisis tren'
                ];
            }

            $firstValue = $monthlyData->last()->achievement_rate;
            $lastValue = $monthlyData->first()->achievement_rate;
            $percentageChange = $firstValue != 0 ? (($lastValue - $firstValue) / $firstValue) * 100 : 0;

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
                'witel_id' => $witelId,
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
    private function getMonthlyRevenueChart($witelId, $tahun, $filters)
    {
        try {
            // CC Revenue
            $ccQuery = CcRevenue::where('tahun', $tahun)
                ->where(function ($query) use ($witelId) {
                    $query->where(function ($q) use ($witelId) {
                        $q->where('divisi_id', 3)->where('witel_bill_id', $witelId);
                    })->orWhere(function ($q) use ($witelId) {
                        $q->whereIn('divisi_id', [1, 2])->where('witel_ho_id', $witelId);
                    });
                });

            if ($filters['tipe_revenue'] && $filters['tipe_revenue'] !== 'all') {
                $ccQuery->where('tipe_revenue', $filters['tipe_revenue']);
            }

            if ($filters['revenue_source'] && $filters['revenue_source'] !== 'all') {
                $ccQuery->where('revenue_source', $filters['revenue_source']);
            }

            $ccMonthly = $ccQuery->selectRaw('
                    bulan,
                    SUM(real_revenue) as real_revenue,
                    SUM(target_revenue) as target_revenue
                ')
                ->groupBy('bulan')
                ->get()
                ->keyBy('bulan');

            // AM Revenue
            $amMonthly = AmRevenue::where('witel_id', $witelId)
                ->where('tahun', $tahun)
                ->selectRaw('
                    bulan,
                    SUM(real_revenue) as real_revenue,
                    SUM(target_revenue) as target_revenue
                ')
                ->groupBy('bulan')
                ->get()
                ->keyBy('bulan');

            $labels = [];
            $realRevenue = [];
            $targetRevenue = [];
            $achievementRate = [];

            for ($month = 1; $month <= 12; $month++) {
                $ccData = $ccMonthly->get($month);
                $amData = $amMonthly->get($month);

                $monthRevenue = ($ccData ? $ccData->real_revenue : 0) + ($amData ? $amData->real_revenue : 0);
                $monthTarget = ($ccData ? $ccData->target_revenue : 0) + ($amData ? $amData->target_revenue : 0);
                $monthAchievement = $monthTarget > 0 ? ($monthRevenue / $monthTarget) * 100 : 0;

                $labels[] = $this->getShortMonthName($month);
                $realRevenue[] = floatval($monthRevenue);
                $targetRevenue[] = floatval($monthTarget);
                $achievementRate[] = round($monthAchievement, 2);
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
                'witel_id' => $witelId,
                'error' => $e->getMessage()
            ]);
            return $this->getEmptyChartData();
        }
    }

    /**
     * Get Available Years
     */
    private function getAvailableYears($witelId)
    {
        $ccYears = DB::table('cc_revenues')
            ->where(function ($query) use ($witelId) {
                $query->where(function ($q) use ($witelId) {
                    $q->where('divisi_id', 3)->where('witel_bill_id', $witelId);
                })->orWhere(function ($q) use ($witelId) {
                    $q->whereIn('divisi_id', [1, 2])->where('witel_ho_id', $witelId);
                });
            })
            ->distinct()
            ->pluck('tahun');

        $amYears = AmRevenue::where('witel_id', $witelId)
            ->distinct()
            ->pluck('tahun');

        return $ccYears->merge($amYears)->unique()->sort()->values()->toArray();
    }

    /**
     * Get Filter Options
     */
    private function getFilterOptions($witelId)
    {
        $availableYears = $this->getAvailableYears($witelId);

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
                'account_manager' => 'Per Account Manager',
                'divisi' => 'Per Divisi',
                'corporate_customer' => 'Per Corporate Customer'
            ],
            'role_filters' => [
                'all' => 'Semua (AM + HOTDA)',
                'AM' => 'Account Manager (AM)',
                'HOTDA' => 'Head Office Telda (HOTDA)'
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
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $months[$monthNumber] ?? 'Unknown';
    }

    private function getShortMonthName($monthNumber)
    {
        $months = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agt',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des'
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

    private function getEmptySummary()
    {
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
