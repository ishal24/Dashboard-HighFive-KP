<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CCWitelPerformController extends Controller
{
    public function index()
    {
        $months = Carbon::now()->startOfYear()->toPeriod(Carbon::now()->endOfYear(), '1 month');

        Log::info("CCW Controller - Accessing CC W Page");

        return view('cc_witel.cc-witel-performance', [
            'months' => $months
        ]);
    }

    public function fetchTrendData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'source' => 'required|in:reguler,ngtma',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $revenueData = DB::table('cc_revenues')
                ->select('tahun', 'bulan', 'divisi_id', 'real_revenue', 'target_revenue') // in the future might want to fetch all
                ->where('tipe_revenue', strtoupper($request->source))
                ->where(DB::raw("CONCAT(tahun, '-', LPAD(bulan, 2, '0'), '-01')"), '>=', $request->start_date)
                ->where(DB::raw("CONCAT(tahun, '-', LPAD(bulan, 2, '0'), '-01')"), '<=', $request->end_date)
                ->get();

            Log::info("CCW Controller", ['fetched_trend_data' => $revenueData]);

            return response()->json($revenueData);
        } catch (\Exception $e) {
            Log::info("CCW Controller", ['error' => $e]);
            return response()->json(['error' => 'A server error occurred while fetching data.'], 500);
        }
    }

    private function applyDateFilters($query, $mode, $year, $month)
    {
        switch ($mode) {
            case 'ytd':
                // YTD: From Jan 1 to the end of the current month of the current year
                $query->where('tahun', $year)->where('bulan', '<=', $month);
                break;
            case 'monthly':
                // Monthly: For a specific year and month
                $query->where('tahun', $year)->where('bulan', $month);
                break;
            case 'annual':
                // Annual: For all 12 months of a specific year
                $query->where('tahun', $year);
                break;
        }
    }

    public function fetchWitelPerformanceData(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:ytd,monthly,annual',
            'year' => 'nullable|integer|min:2020',
            'month' => 'nullable|integer|min:1|max:12',
            'source' => 'required|in:reguler,ngtma',
        ]);
        if ($validator->fails()) return response()->json(['error' => $validator->errors()], 422);

        $dbSource = $request->source;
        $mode = $request->mode;

        $now = Carbon::now();
        $year = $request->input('year', $now->year);
        $month = $request->input('month', $now->month);

        if ($mode === 'ytd') {
            $year = $now->year;
            $month = $now->month;
        }

        // Get all Witels
        $witels = DB::table('witel')->select('id', 'nama')->get()->keyBy('id');
        Log::info('CCW Controller', ['witels_fetched' => $witels]);

        Log::info('CCW Controller - Get ready to fetch buddy');

        // Get Annual Target Revenue for all Witels
        $targetsSubquery = DB::table('cc_revenues')
            ->select(DB::raw('CASE WHEN divisi_id IN (1, 2) THEN witel_ho_id WHEN divisi_id = 3 THEN witel_bill_id END as witel_id'), DB::raw('SUM(target_revenue) as targetM'))
            ->where('tipe_revenue', $dbSource)
            ->groupBy('witel_id');
        $this->applyDateFilters($targetsSubquery, $mode, $year, $month);
        //$targets = DB::query()->fromSub($targetsSubquery, 't')->whereNotNull('witel_id')->pluck('targetM', 'witel_id');
        $targets = DB::query()->fromSub($targetsSubquery, 't')->whereNotNull('witel_id')->pluck('targetM', 'witel_id');

        Log::info('CCW Controller', ['wp_leaderboard_targets' => $targets]);

        // Get YTD Real Revenue for all Witels
        $revenueSubquery = DB::table('cc_revenues')
            ->select(DB::raw('CASE WHEN divisi_id IN (1, 2) THEN witel_ho_id WHEN divisi_id = 3 THEN witel_bill_id END as witel_id'), DB::raw('SUM(real_revenue) as revenueM'))
            ->where('tipe_revenue', $dbSource)
            ->groupBy('witel_id');
        $this->applyDateFilters($revenueSubquery, $mode, $year, $month);
        //$revenues = DB::query()->fromSub($revenueSubquery, 'r')->whereNotNull('witel_id')->pluck('revenueM', 'witel_id');
        $revenues = DB::query()->fromSub($revenueSubquery, 'r')->whereNotNull('witel_id')->pluck('revenueM', 'witel_id');

        Log::info('CCW Controller', ['wp_leaderboard_revenues' => $revenues]);

        // Get ALL Customers for ALL Witels for the selected MONTH
        //$allCustomers = DB::table('cc_revenues')
        //    ->select(
        //        'nama_cc',
        //        DB::raw('SUM(real_revenue) as total_revenue'),
        //        DB::raw('CASE
        //                    WHEN divisi_id IN (1, 2) THEN witel_ho_id
        //                    WHEN divisi_id = 3 THEN witel_bill_id
        //                END as witel_id')
        //    )
        //    ->where('tipe_revenue', $dbSource)
        //    ->where('tahun', $year)
        //    ->where('bulan', $month)
        //    ->whereNotNull(DB::raw('CASE WHEN divisi_id IN (1, 2) THEN witel_ho_id WHEN divisi_id = 3 THEN witel_bill_id END'))
        //    ->groupBy('witel_id', 'nama_cc')
        //    ->orderBy('witel_id')
        //    ->orderByDesc('total_revenue')
        //    ->get();

        $customersQuery = DB::table('cc_revenues')
            ->select('corporate_customer_id as cc_id', 'nama_cc', DB::raw('SUM(real_revenue) as total_revenue'), DB::raw('CASE WHEN divisi_id IN (1, 2) THEN witel_ho_id WHEN divisi_id = 3 THEN witel_bill_id END as witel_id'))
            ->where('tipe_revenue', $dbSource)
            ->groupBy('witel_id', 'cc_id', 'nama_cc')
            ->orderBy('witel_id')
            ->orderByDesc('total_revenue');
        $this->applyDateFilters($customersQuery, $mode, $year, $month);
        $allCustomers = DB::query()->fromSub($customersQuery, 't')->whereNotNull('witel_id')->get();

        Log::info('CCW Controller', ['wp_leaderboard_allCustomers' => $allCustomers]);

        // Process customers into a map for easy lookup
        $customerMap = [];
        foreach ($allCustomers as $row) {
            $customerMap[$row->witel_id][] = $row;
        }

        // Build, Calculate, and Sort the final combined data
        $leaderboard = [];
        foreach ($witels as $id => $witel) {
            $revenue = $revenues->get($id) ?? 0;
            $target = $targets->get($id) ?? 0;
            $achievement = ($target > 0) ? ($revenue / $target) * 100 : null;

            $leaderboard[] = [
                'id' => $id,
                'name' => $witel->nama,
                'revenueM' => $revenue,
                'targetM' => $target,
                'achievement' => $achievement,
                'customers' => $customerMap[$id] ?? [],
            ];
        }

        // Sort the final leaderboard
        usort($leaderboard, function ($a, $b) {
            $aAch = $a['achievement'] ?? -1;
            $bAch = $b['achievement'] ?? -1;
            if ($aAch !== $bAch) return $bAch <=> $aAch;
            return $b['revenueM'] <=> $a['revenueM'];
        });

        Log::info("CCW Controller", ['returned_leaderboard' => $leaderboard]);

        return response()->json($leaderboard);
    }

    public function fetchOverallCustomersLeaderboard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source' => 'required|in:reguler,ngtma',
            'division_id' => 'required|integer|in:1,2,3', // 1=DGS, 2=DSS, 3=DPS
            'mode' => 'required|in:ytd,monthly,annual',
            'year' => 'nullable|integer|min:2020',
            'month' => 'nullable|integer|min:1|max:12',
        ]);
        if ($validator->fails()) return response()->json(['error' => $validator->errors()], 422);

        $dbSource = $request->source;
        $divisionId = $request->division_id;
        $mode = $request->mode;

        $now = Carbon::now();
        $year = $request->input('year', $now->year);
        $month = $request->input('month', $now->month);

        if ($mode === 'ytd') {
            $year = $now->year;
            $month = $now->month;
        }

        $witelIdColumn = ($divisionId == 3) ? 'witel_bill_id' : 'witel_ho_id';

        try {
            // 4. Query Top Customers
            $topCustomers = DB::table('cc_revenues as cr')
                ->select(
                    'cr.corporate_customer_id',
                    'cr.nama_cc',
                    "cr.$witelIdColumn",
                    DB::raw('SUM(cr.real_revenue) as total_revenue'),
                    DB::raw('SUM(cr.target_revenue) as target_revenue'),
                    'w.nama as witel_name' // Select Witel name
                )
                ->leftJoin('witel as w', "cr.$witelIdColumn", '=', 'w.id') // Join with Witel table
                ->where('cr.tipe_revenue', $dbSource)
                ->where('cr.divisi_id', $divisionId)
                ->whereNotNull('cr.nama_cc')      // Ensure customer name exists
                ->whereNotNull('cr.corporate_customer_id')
                ->groupBy('cr.corporate_customer_id', 'cr.nama_cc', 'w.nama', "cr.$witelIdColumn") // Group by customer and witel name
                ->orderByDesc('total_revenue');
            $this->applyDateFilters($topCustomers, $mode, $year, $month);

            $allCustomers = $topCustomers->get()
                ->map(function ($customer) {
                    $customer->achievement = ($customer->target_revenue > 0)
                        ? ($customer->total_revenue / $customer->target_revenue) * 100
                        : null;
                    return $customer;
                });

            Log::info('CCW Controller - Top Customers Fetch:', ['fetched_customers_leaderboard' => $allCustomers]);

            return response()->json($allCustomers);
        } catch (\Exception $e) {
            Log::error('CCW Controller - Top Customers Fetch Error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error fetching top customers.'], 500);
        }
    }
}
