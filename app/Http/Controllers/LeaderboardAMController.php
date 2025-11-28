<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardAMController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $search = $request->input('search', '');
        $witelFilter = $request->input('witel_filter', []);
        $divisiFilter = $request->input('divisi_filter', []);
        $categoryFilter = $request->input('category_filter', []);
        $revenueTypeFilter = $request->input('revenue_type_filter', []);
        $rankingMethod = $request->input('ranking_method', 'revenue');
        $period = $request->input('period', 'year_to_date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->input('per_page', 10);

        // Calculate date range based on period
        $dateRange = $this->calculateDateRange($period, $startDate, $endDate);

        // Build the main query
        $query = $this->buildLeaderboardQuery(
            $dateRange,
            $search,
            $witelFilter,
            $divisiFilter,
            $categoryFilter,
            $revenueTypeFilter
        );

        // Get all results first
        $allResults = $query->get();

        // Apply ranking based on selected method
        $rankedResults = $this->applyRanking($allResults, $rankingMethod);

        // Manual pagination
        $currentPage = $request->input('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = $rankedResults->slice($offset, $perPage)->values();

        // Create paginator instance
        $leaderboardData = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $rankedResults->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Get filter options for dropdowns
        $witels = DB::table('witel')->orderBy('nama')->get();
        $divisis = DB::table('divisi')->orderBy('nama')->get();

        return view('leaderboardAM', compact(
            'leaderboardData',
            'witels',
            'divisis',
            'period',
            'startDate',
            'endDate',
            'rankingMethod'
        ));
    }

    private function applyRanking($results, $method)
    {
        switch ($method) {
            case 'achievement':
                $sorted = $results->sortByDesc('achievement_rate')->values();
                break;

            case 'combined':
                $sorted = $results->map(function ($item) use ($results) {
                    $maxRevenue = $results->max('total_revenue');
                    $revenueScore = $maxRevenue > 0
                        ? ($item->total_revenue / $maxRevenue) * 100
                        : 0;

                    $achievementScore = $item->achievement_rate;
                    $item->combined_score = ($revenueScore * 0.5) + ($achievementScore * 0.5);

                    return $item;
                })->sortByDesc('combined_score')->values();
                break;

            case 'revenue':
            default:
                $sorted = $results->sortByDesc('total_revenue')->values();
                break;
        }

        return $sorted->map(function ($item, $index) {
            $item->rank = $index + 1;
            return $item;
        });
    }

    private function calculateDateRange($period, $startDate = null, $endDate = null)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'year_to_date':
                return [
                    'start_month' => 1,
                    'start_year' => $now->year,
                    'end_month' => $now->month,
                    'end_year' => $now->year
                ];

            case 'current_month':
                return [
                    'start_month' => $now->month,
                    'start_year' => $now->year,
                    'end_month' => $now->month,
                    'end_year' => $now->year
                ];

            case 'custom':
                if ($startDate && $endDate) {
                    $start = Carbon::parse($startDate);
                    $end = Carbon::parse($endDate);

                    return [
                        'start_month' => $start->month,
                        'start_year' => $start->year,
                        'end_month' => $end->month,
                        'end_year' => $end->year
                    ];
                }
                return $this->calculateDateRange('year_to_date');

            default:
                return $this->calculateDateRange('year_to_date');
        }
    }

    private function buildLeaderboardQuery($dateRange, $search, $witelFilter, $divisiFilter, $categoryFilter, $revenueTypeFilter)
    {
        $query = DB::table('account_managers as am')
            ->select(
                'am.id',
                'am.nama',
                'am.nik',
                'w.nama as witel_name',
                // ⭐ TAMBAHKAN SELECT UNTUK PROFILE_IMAGE
                'u.profile_image',
                DB::raw('GROUP_CONCAT(DISTINCT d.kode ORDER BY d.kode SEPARATOR ", ") as divisi_list'),
                DB::raw('COUNT(DISTINCT amd.divisi_id) as divisi_count'),
                DB::raw('COALESCE(SUM(ar.real_revenue), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(ar.target_revenue), 0) as total_target'),
                DB::raw('ROUND(COALESCE((SUM(ar.real_revenue) / NULLIF(SUM(ar.target_revenue), 0)) * 100, 0), 2) as achievement_rate')
            )
            ->join('witel as w', 'am.witel_id', '=', 'w.id')
            // ⭐ JOIN DENGAN TABEL USERS UNTUK AMBIL PROFILE_IMAGE
            ->leftJoin('users as u', function($join) {
                $join->on('am.id', '=', 'u.account_manager_id')
                     ->where('u.role', '=', 'account_manager');
            })
            ->leftJoin('account_manager_divisi as amd', 'am.id', '=', 'amd.account_manager_id')
            ->leftJoin('divisi as d', 'amd.divisi_id', '=', 'd.id')
            ->leftJoin('am_revenues as ar', function($join) use ($dateRange) {
                $join->on('am.id', '=', 'ar.account_manager_id')
                    ->where(function($query) use ($dateRange) {
                        if ($dateRange['start_year'] == $dateRange['end_year']) {
                            $query->where('ar.tahun', '=', $dateRange['start_year'])
                                  ->whereBetween('ar.bulan', [$dateRange['start_month'], $dateRange['end_month']]);
                        } else {
                            $query->where(function($q) use ($dateRange) {
                                $q->where('ar.tahun', '=', $dateRange['start_year'])
                                  ->where('ar.bulan', '>=', $dateRange['start_month']);
                            })->orWhere(function($q) use ($dateRange) {
                                $q->where('ar.tahun', '=', $dateRange['end_year'])
                                  ->where('ar.bulan', '<=', $dateRange['end_month']);
                            });
                        }
                    });
            })
            ->where('am.role', '=', 'AM')
            ->groupBy('am.id', 'am.nama', 'am.nik', 'w.nama', 'u.profile_image'); // ⭐ TAMBAHKAN u.profile_image di GROUP BY

        // Apply search filter
        if (!empty($search)) {
            $query->where('am.nama', 'LIKE', "%{$search}%");
        }

        // Apply witel filter
        if (!empty($witelFilter) && is_array($witelFilter)) {
            $query->whereIn('am.witel_id', $witelFilter);
        }

        // Apply divisi filter
        if (!empty($divisiFilter) && is_array($divisiFilter)) {
            $query->whereExists(function($q) use ($divisiFilter) {
                $q->select(DB::raw(1))
                  ->from('account_manager_divisi as amd2')
                  ->whereRaw('amd2.account_manager_id = am.id')
                  ->whereIn('amd2.divisi_id', $divisiFilter);
            });
        }

        // Apply category filter
        if (!empty($categoryFilter) && is_array($categoryFilter)) {
            $query->where(function($q) use ($categoryFilter) {
                foreach ($categoryFilter as $category) {
                    switch ($category) {
                        case 'enterprise':
                            $q->orWhereExists(function($subQ) {
                                $subQ->select(DB::raw(1))
                                     ->from('account_manager_divisi as amd_ent')
                                     ->whereRaw('amd_ent.account_manager_id = am.id')
                                     ->whereIn('amd_ent.divisi_id', [2, 3])
                                     ->whereNotExists(function($notDGS) {
                                         $notDGS->select(DB::raw(1))
                                                ->from('account_manager_divisi as amd_dgs')
                                                ->whereRaw('amd_dgs.account_manager_id = amd_ent.account_manager_id')
                                                ->where('amd_dgs.divisi_id', '=', 1);
                                     });
                            });
                            break;

                        case 'government':
                            $q->orWhereExists(function($subQ) {
                                $subQ->select(DB::raw(1))
                                     ->from('account_manager_divisi as amd_gov')
                                     ->whereRaw('amd_gov.account_manager_id = am.id')
                                     ->groupBy('amd_gov.account_manager_id')
                                     ->havingRaw('COUNT(DISTINCT amd_gov.divisi_id) = 1')
                                     ->havingRaw('SUM(CASE WHEN amd_gov.divisi_id = 1 THEN 1 ELSE 0 END) = 1');
                            });
                            break;

                        case 'multi':
                            $q->orWhereExists(function($subQ) {
                                $subQ->select(DB::raw(1))
                                     ->from('account_manager_divisi as amd_multi')
                                     ->whereRaw('amd_multi.account_manager_id = am.id')
                                     ->whereExists(function($hasDGS) {
                                         $hasDGS->select(DB::raw(1))
                                                ->from('account_manager_divisi as amd_dgs')
                                                ->whereRaw('amd_dgs.account_manager_id = amd_multi.account_manager_id')
                                                ->where('amd_dgs.divisi_id', '=', 1);
                                     })
                                     ->whereExists(function($hasEnterprise) {
                                         $hasEnterprise->select(DB::raw(1))
                                                       ->from('account_manager_divisi as amd_ent')
                                                       ->whereRaw('amd_ent.account_manager_id = amd_multi.account_manager_id')
                                                       ->whereIn('amd_ent.divisi_id', [2, 3]);
                                     });
                            });
                            break;
                    }
                }
            });
        }

        // Apply revenue type filter
        if (!empty($revenueTypeFilter) && is_array($revenueTypeFilter)) {
            $hasReguler = in_array('Reguler', $revenueTypeFilter);
            $hasNGTMA = in_array('NGTMA', $revenueTypeFilter);
            $hasKombinasi = in_array('Kombinasi', $revenueTypeFilter);

            if (!$hasKombinasi) {
                $query->whereExists(function($q) use ($dateRange, $hasReguler, $hasNGTMA) {
                    $q->select(DB::raw(1))
                      ->from('am_revenues as ar2')
                      ->join('corporate_customers as cc', 'ar2.corporate_customer_id', '=', 'cc.id')
                      ->join('cc_revenues as ccr', function($join) use ($dateRange) {
                          $join->on('cc.nipnas', '=', 'ccr.nipnas')
                               ->where(function($query) use ($dateRange) {
                                   if ($dateRange['start_year'] == $dateRange['end_year']) {
                                       $query->where('ccr.tahun', '=', $dateRange['start_year'])
                                             ->whereBetween('ccr.bulan', [$dateRange['start_month'], $dateRange['end_month']]);
                                   } else {
                                       $query->where(function($q) use ($dateRange) {
                                           $q->where('ccr.tahun', '=', $dateRange['start_year'])
                                             ->where('ccr.bulan', '>=', $dateRange['start_month']);
                                       })->orWhere(function($q) use ($dateRange) {
                                           $q->where('ccr.tahun', '=', $dateRange['end_year'])
                                             ->where('ccr.bulan', '<=', $dateRange['end_month']);
                                       });
                                   }
                               });
                      })
                      ->whereRaw('ar2.account_manager_id = am.id');

                    if ($hasReguler && !$hasNGTMA) {
                        $q->where('ccr.tipe_revenue', '=', 'REGULER');
                    } elseif ($hasNGTMA && !$hasReguler) {
                        $q->where('ccr.tipe_revenue', '=', 'NGTMA');
                    }
                });
            }
        }

        return $query;
    }

    public function getAMCategory($amId)
    {
        $divisiIds = DB::table('account_manager_divisi')
            ->where('account_manager_id', $amId)
            ->pluck('divisi_id')
            ->toArray();

        $hasDGS = in_array(1, $divisiIds);
        $hasDPS = in_array(3, $divisiIds);
        $hasDSS = in_array(2, $divisiIds);
        $divisiCount = count($divisiIds);

        if ($hasDGS && ($hasDPS || $hasDSS)) {
            return response()->json([
                'category' => 'multi',
                'label' => 'Multi Divisi'
            ]);
        }

        if ($hasDGS && $divisiCount == 1) {
            return response()->json([
                'category' => 'government',
                'label' => 'Government'
            ]);
        }

        if (($hasDPS || $hasDSS) && !$hasDGS) {
            return response()->json([
                'category' => 'enterprise',
                'label' => 'Enterprise'
            ]);
        }

        return response()->json([
            'category' => 'other',
            'label' => 'Other'
        ]);
    }
}