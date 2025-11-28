<?php

namespace App\Services;

use App\Models\CcRevenue;
use App\Models\AmRevenue;
use App\Models\AccountManager;
use App\Models\Witel;
use App\Models\Segment;
use App\Models\CorporateCustomer;
use App\Models\Divisi;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueCalculationService
{
    /**
     * Get total revenue data for card group section with date range
     */
    public function getTotalRevenueDataWithDateRange($witelId = null, $divisiId = null, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

        // Witel filtering
        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        // Divisi filtering - direct relation in cc_revenues
        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        // Date range filtering untuk YTD/MTD
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Revenue source filtering
        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        // Tipe revenue filtering
        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        $totals = $query->selectRaw('
            SUM(real_revenue) as total_revenue,
            SUM(target_revenue) as total_target
        ')->first();

        $achievement = $totals->total_target > 0
            ? ($totals->total_revenue / $totals->total_target) * 100
            : 0;

        return [
            'total_revenue' => $totals->total_revenue ?? 0,
            'total_target' => $totals->total_target ?? 0,
            'achievement_rate' => round($achievement, 2),
            'achievement_color' => $this->getAchievementColor($achievement)
        ];
    }

    /**
     * Backward compatibility method
     */
    public function getTotalRevenueData($witelId = null, $divisiId = null, $tahun = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getTotalRevenueDataWithDateRange(
            $witelId, $divisiId, $startDate, $endDate, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get top Account Managers with date range filtering - Fixed for many-to-many divisi
     */
    public function getTopAccountManagersWithDateRange($witelId = null, $limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Base query for Account Managers with many-to-many divisi relation
        $amQuery = AccountManager::where('role', 'AM')
            ->with(['witel', 'divisis']);

        // Apply witel filter
        if ($witelId) {
            $amQuery->where('witel_id', $witelId);
        }

        // Apply divisi filter using many-to-many relation
        if ($divisiId) {
            $amQuery->whereHas('divisis', function($q) use ($divisiId) {
                $q->where('divisi.id', $divisiId);
            });
        }

        $validAMIds = $amQuery->pluck('id');

        if ($validAMIds->isEmpty()) {
            return collect([]);
        }

        // Get revenue data for valid AMs
        $revenueQuery = AmRevenue::whereIn('account_manager_id', $validAMIds);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($revenueQuery, $startDate, $endDate);
        } else {
            $revenueQuery->where('tahun', $this->getCurrentDataYear());
        }

        // Apply revenue source and tipe revenue filtering through cc_revenues relationship
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($revenueQuery, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        // Get aggregated revenue data
        $revenueData = $revenueQuery->selectRaw('
                account_manager_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('account_manager_id')
            ->get()
            ->keyBy('account_manager_id');

        // Map AM data with revenue information
        $results = $amQuery->get()->map(function($am) use ($revenueData) {
            $revenue = $revenueData->get($am->id);
            $totalRevenue = $revenue->total_revenue ?? 0;
            $totalTarget = $revenue->total_target ?? 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $am->total_revenue = $totalRevenue;
            $am->total_target = $totalTarget;
            $am->achievement_rate = round($achievement, 2);
            $am->achievement_color = $this->getAchievementColor($achievement);

            // Handle many-to-many divisi relation
            $am->divisi_list = $am->divisis->isNotEmpty()
                ? $am->divisis->pluck('nama')->join(', ')
                : 'N/A';

            $am->primary_divisi = $am->divisis->where('pivot.is_primary', 1)->first();

            return $am;
        })
        ->filter(function($am) {
            // Only include AMs with revenue data
            return $am->total_revenue > 0 || $am->total_target > 0;
        })
        ->sortByDesc('total_revenue')
        ->take($limit);

        return $results;
    }

    /**
     * Backward compatibility method
     */
    public function getTopAccountManagers($witelId = null, $limit = 20, $tahun = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $startDate = Carbon::createFromDate($tahun, 1, 1);
        $endDate = Carbon::createFromDate($tahun, 12, 31);

        return $this->getTopAccountManagersWithDateRange(
            $witelId, $limit, $startDate, $endDate, $divisiId, $revenueSource, $tipeRevenue
        );
    }

    /**
     * Get top Witels with date range filtering
     */
    public function getTopWitelsWithDateRange($limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply filters
        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        // Aggregate by witel (prioritize HO over BILL)
        $revenueData = $query->selectRaw('
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

        // Map with Witel information
        $results = Witel::all()->map(function($witel) use ($revenueData) {
            $revenue = $revenueData->get($witel->id);
            $totalRevenue = $revenue->total_revenue ?? 0;
            $totalTarget = $revenue->total_target ?? 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $witel->total_customers = $revenue->total_customers ?? 0;
            $witel->total_revenue = $totalRevenue;
            $witel->total_target = $totalTarget;
            $witel->achievement_rate = round($achievement, 2);
            $witel->achievement_color = $this->getAchievementColor($achievement);

            return $witel;
        })
        ->filter(function($witel) {
            return $witel->total_revenue > 0 || $witel->total_target > 0;
        })
        ->sortByDesc('total_revenue')
        ->take($limit);

        return $results;
    }

    /**
     * Get top Segments with date range filtering
     */
    public function getTopSegmentsWithDateRange($limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply filters
        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        // Aggregate by segment
        $revenueData = $query->selectRaw('
                segment_id,
                COUNT(DISTINCT corporate_customer_id) as total_customers,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('segment_id')
            ->get()
            ->keyBy('segment_id');

        // Map with Segment information
        $results = Segment::with('divisi')->get()->map(function($segment) use ($revenueData) {
            $revenue = $revenueData->get($segment->id);
            $totalRevenue = $revenue->total_revenue ?? 0;
            $totalTarget = $revenue->total_target ?? 0;
            $achievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

            $segment->total_customers = $revenue->total_customers ?? 0;
            $segment->total_revenue = $totalRevenue;
            $segment->total_target = $totalTarget;
            $segment->achievement_rate = round($achievement, 2);
            $segment->achievement_color = $this->getAchievementColor($achievement);

            return $segment;
        })
        ->filter(function($segment) {
            return $segment->total_revenue > 0 || $segment->total_target > 0;
        })
        ->sortByDesc('total_revenue')
        ->take($limit);

        return $results;
    }

    /**
     * Get top Corporate Customers with date range filtering
     */
    public function getTopCorporateCustomersWithDateRange($witelId = null, $limit = 20, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::with(['corporateCustomer', 'divisi', 'segment']);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply witel filter
        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        // Apply other filters
        if ($divisiId && $divisiId !== 'all') {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        // Aggregate by corporate customer
        $results = $query->selectRaw('
                corporate_customer_id,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->with(['corporateCustomer', 'divisi', 'segment'])
            ->groupBy('corporate_customer_id')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($revenue) {
                $achievement = $revenue->total_target > 0
                    ? ($revenue->total_revenue / $revenue->total_target) * 100
                    : 0;

                return (object) [
                    'id' => $revenue->corporate_customer_id,
                    'nama' => $revenue->corporateCustomer->nama ?? 'Unknown',
                    'nipnas' => $revenue->corporateCustomer->nipnas ?? 'Unknown',
                    'divisi_nama' => $revenue->divisi->nama ?? 'Unknown',
                    'segment_nama' => $revenue->segment->lsegment_ho ?? 'Unknown',
                    'total_revenue' => $revenue->total_revenue,
                    'total_target' => $revenue->total_target,
                    'achievement_rate' => round($achievement, 2),
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];
            });

        return $results;
    }

    /**
     * Get monthly revenue data for charts with YTD/MTD context
     */
    public function getMonthlyRevenue($tahun = null, $witelId = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $tahun = $tahun ?? $this->getCurrentDataYear();

        $query = CcRevenue::where('tahun', $tahun);

        // Apply filters
        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        return $query->selectRaw('
                bulan,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) {
                $achievement = $item->total_target > 0
                    ? ($item->total_revenue / $item->total_target) * 100
                    : 0;

                return [
                    'month' => $item->bulan,
                    'month_name' => date('F', mktime(0, 0, 0, $item->bulan, 1)),
                    'real_revenue' => $item->total_revenue,
                    'target_revenue' => $item->total_target,
                    'achievement' => round($achievement, 2),
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];
            });
    }

    /**
     * Get revenue table data with date range
     */
    public function getRevenueTableDataWithDateRange($startDate = null, $endDate = null, $witelId = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::query();

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply filters
        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        return $query->selectRaw('
                bulan,
                tahun,
                SUM(real_revenue) as realisasi,
                SUM(target_revenue) as target,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement
            ')
            ->groupBy('bulan', 'tahun')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) {
                $achievement = round($item->achievement, 2);
                return [
                    'bulan' => date('F Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun)),
                    'target' => $item->target,
                    'realisasi' => $item->realisasi,
                    'achievement' => $achievement,
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];
            });
    }

    /**
     * Get AM specific card data with date range - Fixed for many-to-many divisi
     */
    public function getAMCardDataWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Handle divisi selection for multi-divisi AM
        $selectedDivisiId = $this->resolveAMDivisiId($accountManagerId, $divisiId);

        $query = AmRevenue::where('account_manager_id', $accountManagerId);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply CC revenue filters if needed
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        $revenueData = $query->selectRaw('
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target
            ')
            ->first();

        $totalRevenue = $revenueData->total_revenue ?? 0;
        $totalTarget = $revenueData->total_target ?? 0;
        $achievementRate = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_target' => $totalTarget,
            'achievement_rate' => round($achievementRate, 2),
            'achievement_color' => $this->getAchievementColor($achievementRate),
            'selected_divisi_id' => $selectedDivisiId,
            'available_divisi' => $this->getAMAvailableDivisi($accountManagerId)
        ];
    }

    /**
     * Get AM revenue data with date range - Fixed for many-to-many divisi
     */
    public function getAMRevenueDataWithDateRange($accountManagerId, $startDate = null, $endDate = null, $divisiId = null, $viewMode = 'detail', $revenueSource = null, $tipeRevenue = null)
    {
        $query = AmRevenue::where('account_manager_id', $accountManagerId);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply CC revenue filters if needed
        if ($this->needsCcRevenueFilter($revenueSource, $tipeRevenue)) {
            $this->applyCcRevenueFilter($query, $startDate, $endDate, $revenueSource, $tipeRevenue);
        }

        switch ($viewMode) {
            case 'agregat':
                return $this->getAMRevenueAgregat($query);

            case 'agregat_bulan':
                return $this->getAMRevenueAgregatBulan($query);

            default: // detail
                return $this->getAMRevenueDetail($query);
        }
    }

    /**
     * Get available years from actual data
     */
    public function getAvailableYears()
    {
        $years = CcRevenue::distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        if (empty($years)) {
            $years = [$this->getCurrentDataYear()];
        }

        return [
            'years' => $years,
            'current_year' => $this->getCurrentDataYear(),
            'use_year_picker' => count($years) > 10,
            'min_year' => min($years),
            'max_year' => max($years)
        ];
    }

    /**
     * Get revenue source options
     */
    public function getRevenueSourceOptions()
    {
        return [
            'all' => 'Semua Source',
            'HO' => 'HO Revenue',
            'BILL' => 'BILL Revenue'
        ];
    }

    /**
     * Get tipe revenue options
     */
    public function getTipeRevenueOptions()
    {
        return [
            'all' => 'Semua Tipe',
            'REGULER' => 'Revenue Reguler',
            'NGTMA' => 'Revenue NGTMA'
        ];
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

        // Fallback to first available divisi
        return $am->divisis->first()->id;
    }

    /**
     * Get available divisi for AM
     */
    private function getAMAvailableDivisi($accountManagerId)
    {
        $am = AccountManager::with('divisis')->find($accountManagerId);

        if (!$am || $am->divisis->isEmpty()) {
            return collect([]);
        }

        return $am->divisis->map(function($divisi) {
            return [
                'id' => $divisi->id,
                'nama' => $divisi->nama,
                'kode' => $divisi->kode,
                'is_primary' => $divisi->pivot->is_primary
            ];
        });
    }

    /**
     * Get AM revenue data - aggregated by customer
     */
    private function getAMRevenueAgregat($query)
    {
        return $query->with(['corporateCustomer', 'divisi'])
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
            ->groupBy('corporate_customer_id')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->map(function($item) {
                $item->achievement_color = $this->getAchievementColor($item->achievement);
                $item->customer_name = $item->corporateCustomer->nama ?? 'Unknown';
                $item->nipnas = $item->corporateCustomer->nipnas ?? 'Unknown';
                return $item;
            });
    }

    /**
     * Get AM revenue data - aggregated by month
     */
    private function getAMRevenueAgregatBulan($query)
    {
        return $query->selectRaw('
                bulan,
                tahun,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                CASE
                    WHEN SUM(target_revenue) > 0
                    THEN (SUM(real_revenue) / SUM(target_revenue)) * 100
                    ELSE 0
                END as achievement
            ')
            ->groupBy('bulan', 'tahun')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) {
                $item->achievement_color = $this->getAchievementColor($item->achievement);
                $item->month_name = date('F Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun));
                return $item;
            });
    }

    /**
     * Get AM revenue data - detailed view
     */
    private function getAMRevenueDetail($query)
    {
        return $query->with(['corporateCustomer', 'divisi'])
            ->selectRaw('
                *,
                CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END as achievement
            ')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get()
            ->map(function($item) {
                $item->achievement_color = $this->getAchievementColor($item->achievement);
                $item->month_name = date('F Y', mktime(0, 0, 0, $item->bulan, 1, $item->tahun));
                $item->customer_name = $item->corporateCustomer->nama ?? 'Unknown';
                $item->nipnas = $item->corporateCustomer->nipnas ?? 'Unknown';
                $item->divisi_nama = $item->divisi->nama ?? 'Unknown';
                return $item;
            });
    }

    /**
     * Get current data year (tahun terkini dari data aktual)
     */
    private function getCurrentDataYear()
    {
        static $currentYear = null;

        if ($currentYear === null) {
            $currentYear = CcRevenue::max('tahun') ?? 2025;
        }

        return $currentYear;
    }

    /**
     * Get achievement color based on rate
     */
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

    /**
     * Get AM divisi context for multi-divisi scenarios
     */
    public function getAMDivisiContext($accountManagerId)
    {
        $am = AccountManager::with('divisis')->find($accountManagerId);

        if (!$am || $am->divisis->isEmpty()) {
            return [
                'has_divisi' => false,
                'divisi_count' => 0,
                'primary_divisi' => null,
                'all_divisi' => collect([])
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
     * Get revenue summary for specific divisi
     */
    public function getDivisiRevenueSummary($divisiId, $startDate = null, $endDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        $query = CcRevenue::where('divisi_id', $divisiId);

        // Apply date range filtering
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        // Apply additional filters
        if ($revenueSource && $revenueSource !== 'all') {
            $query->where('revenue_source', $revenueSource);
        }

        if ($tipeRevenue && $tipeRevenue !== 'all') {
            $query->where('tipe_revenue', $tipeRevenue);
        }

        $summary = $query->selectRaw('
                COUNT(DISTINCT corporate_customer_id) as total_customers,
                COUNT(DISTINCT CASE WHEN witel_ho_id IS NOT NULL THEN witel_ho_id ELSE witel_bill_id END) as total_witels,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                AVG(CASE
                    WHEN target_revenue > 0
                    THEN (real_revenue / target_revenue) * 100
                    ELSE 0
                END) as avg_achievement
            ')
            ->first();

        $totalRevenue = $summary->total_revenue ?? 0;
        $totalTarget = $summary->total_target ?? 0;
        $overallAchievement = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

        return [
            'total_customers' => $summary->total_customers ?? 0,
            'total_witels' => $summary->total_witels ?? 0,
            'total_revenue' => $totalRevenue,
            'total_target' => $totalTarget,
            'overall_achievement' => round($overallAchievement, 2),
            'avg_achievement' => round($summary->avg_achievement ?? 0, 2),
            'achievement_color' => $this->getAchievementColor($overallAchievement),
            'divisi_info' => Divisi::find($divisiId)
        ];
    }

    /**
     * Get revenue comparison between periods
     */
    public function getRevenueComparison($witelId = null, $divisiId = null, $currentStartDate = null, $currentEndDate = null, $previousStartDate = null, $previousEndDate = null, $revenueSource = null, $tipeRevenue = null)
    {
        // Get current period data
        $currentData = $this->getTotalRevenueDataWithDateRange(
            $witelId, $divisiId, $currentStartDate, $currentEndDate, $revenueSource, $tipeRevenue
        );

        // Get previous period data
        $previousData = $this->getTotalRevenueDataWithDateRange(
            $witelId, $divisiId, $previousStartDate, $previousEndDate, $revenueSource, $tipeRevenue
        );

        // Calculate growth
        $revenueGrowth = $previousData['total_revenue'] > 0
            ? (($currentData['total_revenue'] - $previousData['total_revenue']) / $previousData['total_revenue']) * 100
            : 0;

        $targetGrowth = $previousData['total_target'] > 0
            ? (($currentData['total_target'] - $previousData['total_target']) / $previousData['total_target']) * 100
            : 0;

        $achievementDiff = $currentData['achievement_rate'] - $previousData['achievement_rate'];

        return [
            'current' => $currentData,
            'previous' => $previousData,
            'growth' => [
                'revenue' => round($revenueGrowth, 2),
                'target' => round($targetGrowth, 2),
                'achievement_diff' => round($achievementDiff, 2),
                'revenue_trend' => $this->getTrendDirection($revenueGrowth),
                'achievement_trend' => $this->getTrendDirection($achievementDiff)
            ]
        ];
    }

    /**
     * Get trend direction based on percentage change
     */
    private function getTrendDirection($percentage)
    {
        if ($percentage > 5) {
            return 'up';
        } elseif ($percentage < -5) {
            return 'down';
        } else {
            return 'stable';
        }
    }

    /**
     * Get revenue forecast based on current trends
     */
    public function getRevenueForecast($witelId = null, $divisiId = null, $months = 3, $revenueSource = null, $tipeRevenue = null)
    {
        $currentYear = $this->getCurrentDataYear();
        $currentMonth = date('n');

        // Get last 6 months of data for trend analysis
        $historicalData = CcRevenue::query()
            ->where('tahun', $currentYear)
            ->where('bulan', '<=', $currentMonth)
            ->when($witelId, function($q) use ($witelId) {
                $q->where(function($subq) use ($witelId) {
                    $subq->where('witel_ho_id', $witelId)
                         ->orWhere('witel_bill_id', $witelId);
                });
            })
            ->when($divisiId, function($q) use ($divisiId) {
                $q->where('divisi_id', $divisiId);
            })
            ->when($revenueSource && $revenueSource !== 'all', function($q) use ($revenueSource) {
                $q->where('revenue_source', $revenueSource);
            })
            ->when($tipeRevenue && $tipeRevenue !== 'all', function($q) use ($tipeRevenue) {
                $q->where('tipe_revenue', $tipeRevenue);
            })
            ->selectRaw('
                bulan,
                SUM(real_revenue) as monthly_revenue
            ')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        if ($historicalData->count() < 2) {
            return [
                'forecast' => [],
                'confidence' => 'low',
                'trend' => 'insufficient_data'
            ];
        }

        // Calculate trend
        $revenues = $historicalData->pluck('monthly_revenue')->toArray();
        $trend = $this->calculateLinearTrend($revenues);

        // Generate forecast
        $forecast = [];
        $lastRevenue = end($revenues);

        for ($i = 1; $i <= $months; $i++) {
            $forecastMonth = $currentMonth + $i;
            $forecastYear = $currentYear;

            if ($forecastMonth > 12) {
                $forecastMonth = $forecastMonth - 12;
                $forecastYear++;
            }

            $forecastRevenue = $lastRevenue + ($trend * $i);
            $forecastRevenue = max(0, $forecastRevenue); // Ensure non-negative

            $forecast[] = [
                'month' => $forecastMonth,
                'year' => $forecastYear,
                'month_name' => date('F Y', mktime(0, 0, 0, $forecastMonth, 1, $forecastYear)),
                'forecast_revenue' => $forecastRevenue,
                'confidence_level' => $this->calculateConfidenceLevel($i, $historicalData->count())
            ];
        }

        return [
            'forecast' => $forecast,
            'trend_slope' => $trend,
            'confidence' => $this->getOverallConfidence($historicalData->count()),
            'historical_data' => $historicalData->map(function($item) use ($currentYear) {
                return [
                    'month_name' => date('F Y', mktime(0, 0, 0, $item->bulan, 1, $currentYear)),
                    'revenue' => $item->monthly_revenue
                ];
            })
        ];
    }

    /**
     * Calculate linear trend from data points
     */
    private function calculateLinearTrend($data)
    {
        $n = count($data);
        if ($n < 2) return 0;

        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $data[$i];
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        // Calculate slope (trend)
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);

        return $slope;
    }

    /**
     * Calculate confidence level for forecast
     */
    private function calculateConfidenceLevel($monthsAhead, $historicalCount)
    {
        $baseConfidence = min(90, $historicalCount * 15); // Max 90% confidence
        $decayFactor = pow(0.9, $monthsAhead - 1); // Confidence decreases with time

        return round($baseConfidence * $decayFactor);
    }

    /**
     * Get overall forecast confidence
     */
    private function getOverallConfidence($historicalCount)
    {
        if ($historicalCount >= 6) {
            return 'high';
        } elseif ($historicalCount >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get revenue breakdown by category
     */
    public function getRevenueBreakdown($startDate = null, $endDate = null, $witelId = null, $divisiId = null)
    {
        $query = CcRevenue::query();

        // Apply filters
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        $breakdown = $query->selectRaw('
                revenue_source,
                tipe_revenue,
                SUM(real_revenue) as total_revenue,
                SUM(target_revenue) as total_target,
                COUNT(DISTINCT corporate_customer_id) as customer_count
            ')
            ->groupBy('revenue_source', 'tipe_revenue')
            ->get()
            ->map(function($item) {
                $achievement = $item->total_target > 0
                    ? ($item->total_revenue / $item->total_target) * 100
                    : 0;

                return [
                    'revenue_source' => $item->revenue_source,
                    'tipe_revenue' => $item->tipe_revenue,
                    'total_revenue' => $item->total_revenue,
                    'total_target' => $item->total_target,
                    'customer_count' => $item->customer_count,
                    'achievement_rate' => round($achievement, 2),
                    'achievement_color' => $this->getAchievementColor($achievement)
                ];
            });

        // Calculate totals
        $totals = [
            'total_revenue' => $breakdown->sum('total_revenue'),
            'total_target' => $breakdown->sum('total_target'),
            'total_customers' => $breakdown->sum('customer_count')
        ];

        $totals['overall_achievement'] = $totals['total_target'] > 0
            ? ($totals['total_revenue'] / $totals['total_target']) * 100
            : 0;

        return [
            'breakdown' => $breakdown,
            'totals' => $totals,
            'categories' => $this->getRevenueCategories()
        ];
    }

    /**
     * Get revenue categories for breakdown
     */
    private function getRevenueCategories()
    {
        return [
            'revenue_sources' => [
                'HO' => 'Head Office Revenue',
                'BILL' => 'Billing Revenue'
            ],
            'tipe_revenues' => [
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ]
        ];
    }

    /**
     * Validate revenue data integrity
     */
    public function validateRevenueData($startDate = null, $endDate = null)
    {
        $ccQuery = CcRevenue::query();
        $amQuery = AmRevenue::query();

        // Apply date range
        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($ccQuery, $startDate, $endDate);
            $this->applyDateRangeFilter($amQuery, $startDate, $endDate);
        } else {
            $year = $this->getCurrentDataYear();
            $ccQuery->where('tahun', $year);
            $amQuery->where('tahun', $year);
        }

        // Get totals
        $ccTotals = $ccQuery->selectRaw('
                SUM(real_revenue) as cc_real_revenue,
                SUM(target_revenue) as cc_target_revenue,
                COUNT(*) as cc_record_count
            ')->first();

        $amTotals = $amQuery->selectRaw('
                SUM(real_revenue) as am_real_revenue,
                SUM(target_revenue) as am_target_revenue,
                COUNT(*) as am_record_count
            ')->first();

        // Calculate differences
        $revenueVariance = abs($ccTotals->cc_real_revenue - $amTotals->am_real_revenue);
        $targetVariance = abs($ccTotals->cc_target_revenue - $amTotals->am_target_revenue);

        $revenueVariancePercent = $ccTotals->cc_real_revenue > 0
            ? ($revenueVariance / $ccTotals->cc_real_revenue) * 100
            : 0;

        return [
            'cc_data' => [
                'real_revenue' => $ccTotals->cc_real_revenue ?? 0,
                'target_revenue' => $ccTotals->cc_target_revenue ?? 0,
                'record_count' => $ccTotals->cc_record_count ?? 0
            ],
            'am_data' => [
                'real_revenue' => $amTotals->am_real_revenue ?? 0,
                'target_revenue' => $amTotals->am_target_revenue ?? 0,
                'record_count' => $amTotals->am_record_count ?? 0
            ],
            'variance' => [
                'revenue_diff' => $revenueVariance,
                'target_diff' => $targetVariance,
                'revenue_variance_percent' => round($revenueVariancePercent, 2)
            ],
            'data_quality' => [
                'is_consistent' => $revenueVariancePercent < 5, // Less than 5% variance
                'quality_score' => $this->calculateDataQualityScore($revenueVariancePercent),
                'recommendations' => $this->getDataQualityRecommendations($revenueVariancePercent)
            ]
        ];
    }

    /**
     * Calculate data quality score
     */
    private function calculateDataQualityScore($variancePercent)
    {
        if ($variancePercent < 1) {
            return 'excellent';
        } elseif ($variancePercent < 5) {
            return 'good';
        } elseif ($variancePercent < 10) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Get data quality recommendations
     */
    private function getDataQualityRecommendations($variancePercent)
    {
        if ($variancePercent < 1) {
            return ['Data consistency is excellent. No immediate action required.'];
        } elseif ($variancePercent < 5) {
            return [
                'Minor data inconsistencies detected.',
                'Consider reviewing data entry processes.',
                'Schedule periodic data validation checks.'
            ];
        } elseif ($variancePercent < 10) {
            return [
                'Moderate data inconsistencies found.',
                'Review data synchronization between CC and AM revenues.',
                'Implement automated data validation rules.',
                'Consider data reconciliation procedures.'
            ];
        } else {
            return [
                'Significant data inconsistencies detected.',
                'Immediate review of data sources required.',
                'Implement comprehensive data validation framework.',
                'Consider data audit and cleanup procedures.',
                'Review business rules for revenue allocation.'
            ];
        }
    }

    /**
     * Format currency amount
     */
    public function formatCurrency($amount, $includeSymbol = true, $precision = 0)
    {
        $formatted = number_format($amount, $precision, ',', '.');
        return $includeSymbol ? "Rp {$formatted}" : $formatted;
    }

    /**
     * Format currency in millions
     */
    public function formatCurrencyInMillions($amount, $includeSymbol = true)
    {
        $millions = $amount / 1000000;
        $formatted = number_format($millions, 2, ',', '.');
        return $includeSymbol ? "Rp {$formatted}M" : "{$formatted}M";
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats($startDate = null, $endDate = null, $witelId = null, $divisiId = null)
    {
        $query = CcRevenue::query();

        if ($startDate && $endDate) {
            $this->applyDateRangeFilter($query, $startDate, $endDate);
        } else {
            $query->where('tahun', $this->getCurrentDataYear());
        }

        if ($witelId) {
            $query->where(function($q) use ($witelId) {
                $q->where('witel_ho_id', $witelId)
                  ->orWhere('witel_bill_id', $witelId);
            });
        }

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        $stats = $query->selectRaw('
                MIN(real_revenue) as min_revenue,
                MAX(real_revenue) as max_revenue,
                AVG(real_revenue) as avg_revenue,
                STDDEV(real_revenue) as revenue_stddev,
                COUNT(*) as total_records,
                COUNT(DISTINCT corporate_customer_id) as unique_customers
            ')->first();

        return [
            'min_revenue' => $stats->min_revenue ?? 0,
            'max_revenue' => $stats->max_revenue ?? 0,
            'avg_revenue' => round($stats->avg_revenue ?? 0, 2),
            'revenue_stddev' => round($stats->revenue_stddev ?? 0, 2),
            'total_records' => $stats->total_records ?? 0,
            'unique_customers' => $stats->unique_customers ?? 0,
            'revenue_coefficient_variation' => $stats->avg_revenue > 0
                ? round(($stats->revenue_stddev / $stats->avg_revenue) * 100, 2)
                : 0
        ];
    }
}