<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\SpreadsheetSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HighFiveProductPerformanceController extends Controller
{
    /**
     * 📄 REVISED: Get Product Level Performance Benchmarking
     */
    public function getProductPerformance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'snapshot_1_id' => 'required|exists:spreadsheet_snapshots,id',
            'snapshot_2_id' => 'required|exists:spreadsheet_snapshots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Snapshot tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $snapshot1 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_1_id);
            $snapshot2 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_2_id);

            if ($snapshot1->divisi_id !== $snapshot2->divisi_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot harus dari divisi yang sama'
                ], 422);
            }

            if ($snapshot1->fetch_status !== 'success' || $snapshot2->fetch_status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya snapshot dengan status success yang bisa digunakan'
                ], 422);
            }

            $data1 = $snapshot1->parsed_data;
            $data2 = $snapshot2->parsed_data;

            // 1. Process product performance data (row-by-row logic)
            $productData = $this->calculateProductPerformance($data1, $data2);

            // 2. Calculate NEW 5 Metrics & Detailed Insights (Grid Cards + Narrative)
            $metricsData = $this->calculateProductAnalysis($productData, $snapshot1, $snapshot2);

            // 3. Generate leaderboards
            $productLeaderboard = $this->generateProductLeaderboard($productData);
            $improvementLeaderboard = $this->generateImprovementLeaderboard($productData);

            return response()->json([
                'success' => true,
                'data' => [
                    'snapshot_1' => [
                        'id' => $snapshot1->id,
                        'label' => $snapshot1->display_name,
                        'tanggal' => $snapshot1->snapshot_date->format('Y-m-d'),
                        'tanggal_formatted' => $snapshot1->formatted_date,
                    ],
                    'snapshot_2' => [
                        'id' => $snapshot2->id,
                        'label' => $snapshot2->display_name,
                        'tanggal' => $snapshot2->snapshot_date->format('Y-m-d'),
                        'tanggal_formatted' => $snapshot2->formatted_date,
                    ],
                    'product_analysis' => $metricsData,
                    'products' => $productData,
                    'product_leaderboard' => $productLeaderboard,
                    'improvement_leaderboard' => $improvementLeaderboard,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ REVISED: Calculate product performance
     */
    private function calculateProductPerformance($data1, $data2)
    {
        $grouped1 = $this->groupByAMCustomerProduct($data1);
        $grouped2 = $this->groupByAMCustomerProduct($data2);

        $merged = [];
        $allKeys = array_unique(array_merge(
            array_keys($grouped1),
            array_keys($grouped2)
        ));

        foreach ($allKeys as $key) {
            $item1 = $grouped1[$key] ?? null;
            $item2 = $grouped2[$key] ?? null;

            $progress1 = $item1['progress_percentage'] ?? 0;
            $progress2 = $item2['progress_percentage'] ?? 0;
            $result1 = $item1['result_percentage'] ?? 0;
            $result2 = $item2['result_percentage'] ?? 0;

            $customerName = $item2['customer'] ?? $item1['customer'] ?? null;

            if ($customerName && isset($item2['product']) && $customerName === $item2['product']) {
                $customerName = null;
            }

            $merged[$key] = [
                'am' => $item2['am'] ?? $item1['am'],
                'customer' => $customerName,
                'product' => $item2['product'] ?? $item1['product'],
                'witel' => $item2['witel'] ?? $item1['witel'],
                'progress_1' => $progress1,
                'progress_2' => $progress2,
                'result_1' => $result1,
                'result_2' => $result2,
                'result' => $item2['result'] ?? $item1['result'] ?? '',
                'change_progress' => $progress2 - $progress1,
                'change_result' => $result2 - $result1,
                'change_avg' => round((($progress2 - $progress1) + ($result2 - $result1)) / 2, 2),
            ];
        }

        usort($merged, function($a, $b) {
            $amCompare = strcmp($a['am'], $b['am']);
            if ($amCompare !== 0) return $amCompare;

            $customerA = $a['customer'] ?? 'ZZZZ';
            $customerB = $b['customer'] ?? 'ZZZZ';
            $customerCompare = strcmp($customerA, $customerB);
            if ($customerCompare !== 0) return $customerCompare;

            return strcmp($a['product'], $b['product']);
        });

        return $this->addRowspanInfo($merged);
    }

    /**
     * ✅ PRESERVED: Group by AM → Customer → Product
     */
    private function groupByAMCustomerProduct($data)
    {
        $grouped = [];

        foreach ($data as $row) {
            $am = trim($row['am']);
            $customer = isset($row['customer_name']) ? trim($row['customer_name']) : null;
            $product = trim($row['product']);
            $witel = isset($row['witel']) ? trim($row['witel']) : null;

            if (empty($am) || empty($product)) {
                continue;
            }

            $customerKey = $customer ?: '__EMPTY__';
            $key = $am . '|' . $customerKey . '|' . $product;

            if (!isset($grouped[$key]) ||
                $row['progress_percentage'] > $grouped[$key]['progress_percentage'] ||
                $row['result_percentage'] > $grouped[$key]['result_percentage']) {

                $grouped[$key] = [
                    'am' => $am,
                    'customer' => $customer,
                    'product' => $product,
                    'witel' => $witel,
                    'progress_percentage' => $row['progress_percentage'],
                    'result_percentage' => $row['result_percentage'],
                    'result' => $row['result'] ?? '',
                ];
            }
        }

        return $grouped;
    }

    private function addRowspanInfo($data)
    {
        $result = [];
        $currentAM = null;
        $currentCustomer = null;
        $amStartIndex = 0;
        $customerStartIndex = 0;

        foreach ($data as $index => $row) {
            if ($row['am'] !== $currentAM) {
                if ($currentAM !== null) {
                    $this->finalizeAMGroup($result, $amStartIndex, $index);
                    $this->finalizeCustomerGroup($result, $customerStartIndex, $index);
                }
                
                $currentAM = $row['am'];
                $currentCustomer = $row['customer'];
                $amStartIndex = $index;
                $customerStartIndex = $index;
            }
            elseif ($row['customer'] !== $currentCustomer) {
                if ($currentCustomer !== null) {
                    $this->finalizeCustomerGroup($result, $customerStartIndex, $index);
                }
                $currentCustomer = $row['customer'];
                $customerStartIndex = $index;
            }

            $result[] = $row;
        }

        if (!empty($result)) {
            $this->finalizeCustomerGroup($result, $customerStartIndex, count($result));
            $this->finalizeAMGroup($result, $amStartIndex, count($result));
        }

        return $result;
    }

    private function finalizeAMGroup(&$result, $startIndex, $endIndex)
    {
        $rowspan = $endIndex - $startIndex;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $result[$i]['am_rowspan'] = ($i === $startIndex) ? $rowspan : 0;
        }
    }

    private function finalizeCustomerGroup(&$result, $startIndex, $endIndex)
    {
        $rowspan = $endIndex - $startIndex;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $result[$i]['customer_rowspan'] = ($i === $startIndex) ? $rowspan : 0;
        }
    }

    /**
     * 🔥 NEW: Calculate Analysis with Mini Cards HTML
     */
    /**
     * 🔥 REVISED: Calculate Analysis with:
     * 1. Smart Top Selling Product (Win Terbanyak -> Efisiensi Tertinggi)
     * 2. Stagnancy Metric Swapped (Main: %, Sub: Count)
     */
    private function calculateProductAnalysis($mergedData, $snapshot1, $snapshot2)
{
    // 1. Inisialisasi Variabel Statistik
    $stats = [
        'total_rows' => count($mergedData),
        'total_progress' => 0,
        'count_visited' => 0,      
        'count_stagnant' => 0,     
        'count_zero' => 0,         
        'count_win' => 0,          
        'count_lose' => 0,         
        'count_completed' => 0,    
        'unique_products' => [],
        'unique_cc' => [],
        'unique_cc_win' => [], // Tambahan untuk hitung CC unik dari win
        'unique_cc_completed' => [], // Tambahan untuk hitung CC unik dari SPH
    ];

    $productStats = [];

    // 2. Loop Data untuk Agregasi
    foreach ($mergedData as $row) {
        $stats['total_progress'] += $row['progress_2'];
        
        if (!empty($row['product'])) $stats['unique_products'][$row['product']] = true;
        if (!empty($row['customer'])) $stats['unique_cc'][$row['customer']] = true;

        if ($row['progress_2'] > 0) $stats['count_visited']++;
        else $stats['count_zero']++;

        if ($row['change_avg'] == 0) $stats['count_stagnant']++;

        $resText = strtolower($row['result'] ?? ''); 
        $resVal = $row['result_2'] ?? 0;

        $isWin = (strpos($resText, 'win') !== false || $resVal == 100);
        $isLose = (strpos($resText, 'lose') !== false);

        if ($isWin) {
            $stats['count_win']++;
            if (!empty($row['customer'])) $stats['unique_cc_win'][$row['customer']] = true;
        } elseif ($isLose) {
            $stats['count_lose']++;
        }

        if ($row['progress_2'] == 100) {
            $stats['count_completed']++;
            if (!empty($row['customer'])) $stats['unique_cc_completed'][$row['customer']] = true;
        }

        // Statistik Per Produk
        $pName = $row['product'] ?? 'Unknown';
        if (!isset($productStats[$pName])) {
            $productStats[$pName] = ['wins' => 0, 'total' => 0, 'stagnant' => 0, 'cc_wins' => []];
        }
        $productStats[$pName]['total']++;
        if ($isWin) {
            $productStats[$pName]['wins']++;
            if (!empty($row['customer'])) $productStats[$pName]['cc_wins'][$row['customer']] = true;
        }
        if ($row['change_avg'] == 0) $productStats[$pName]['stagnant']++;
    }

    // 3. Cari Top Selling Product & Most Stagnant Product
    $topProduct = ['name' => 'None', 'wins' => -1, 'total' => 999999, 'cc_count' => 0];
    $mostStagnantProduct = ['name' => null, 'count' => 0];

    foreach ($productStats as $name => $ps) {
        // Logic Top Product (Wins terbanyak, tie-breaker: offering terkecil)
        if ($ps['wins'] > $topProduct['wins'] || ($ps['wins'] == $topProduct['wins'] && $ps['total'] < $topProduct['total'])) {
            $topProduct = [
                'name' => $name,
                'wins' => $ps['wins'],
                'total' => $ps['total'],
                'cc_count' => count($ps['cc_wins'])
            ];
        }
        // Cari Produk Paling Stagnan
        if ($ps['stagnant'] > $mostStagnantProduct['count']) {
            $mostStagnantProduct = ['name' => $name, 'count' => $ps['stagnant']];
        }
    }

    // 4. Kalkulasi Metrik Global
    $total = $stats['total_rows'] ?: 1;
    $visitedRate = ($stats['count_visited'] / $total) * 100;
    $stagnantRate = ($stats['count_stagnant'] / $total) * 100;
    $completionRate = ($stats['count_completed'] / $total) * 100;
    $totalClosed = $stats['count_win'] + $stats['count_lose'];
    $winRate = $totalClosed > 0 ? ($stats['count_win'] / $totalClosed) * 100 : 0;
    $dominance = $stats['count_win'] > 0 ? ($topProduct['wins'] / $stats['count_win']) * 100 : 0;

    // 5. Generate Modal HTML Insights
    
    // --- INSIGHT 1: PRODUCT PROPOSED RATE (Tetap) ---
    $insightPulse = "
        <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Productivity Pulse</h4></div>
        <div class='insight-metrics-grid'>
            <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Total Offerings</span><span class='insight-metric-value'>" . number_format($stats['total_rows']) . "</span><span class='insight-metric-sub'>Pipeline Lines</span></div>
            <div class='insight-metric-item im-success'><span class='insight-metric-label'>Active (Visited)</span><span class='insight-metric-value'>" . number_format($stats['count_visited']) . "</span><span class='insight-metric-sub'>" . number_format($visitedRate, 1) . "% of total</span></div>
            <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Unvisited</span><span class='insight-metric-value'>" . number_format($stats['count_zero']) . "</span><span class='insight-metric-sub'>0% Progress</span></div>
        </div>
        <div class='insight-narrative-box blue-theme'><div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div><p class='insight-narrative-text'>Proposed Rate sebesar <strong>" . number_format($visitedRate, 1) . "%</strong> mencerminkan tingkat keaktifan tim dalam memproses <strong>" . number_format($stats['total_rows']) . "</strong> offerings yang masuk.</p></div>";

    // --- INSIGHT 2: STAGNANCY ANALYSIS ---
    $stagnantSubHTML = "";
    if ($mostStagnantProduct['name'] && $mostStagnantProduct['count'] > 0) {
        $stagnantSubHTML = "<div class='insight-metric-item im-warning'><span class='insight-metric-label'>Most Stagnant Product</span><span class='insight-metric-value' style='font-size:14px;'>{$mostStagnantProduct['name']}</span><span class='insight-metric-sub'>{$mostStagnantProduct['count']} stagnant items</span></div>";
    }

    $insightStagnant = "
        <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Stagnancy Analysis</h4></div>
        <div class='insight-metrics-grid'>
            <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Total Stagnant</span><span class='insight-metric-value'>" . number_format($stats['count_stagnant']) . "</span><span class='insight-metric-sub'>dari " . number_format($stats['total_rows']) . " offerings</span></div>
            <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Stagnant Rate</span><span class='insight-metric-value'>" . number_format($stagnantRate, 1) . "%</span><span class='insight-metric-sub'>Persentase Stagnansi</span></div>
            {$stagnantSubHTML}
        </div>
        <div class='insight-narrative-box'><div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div><p class='insight-narrative-text'>Tingkat stagnansi sebesar <strong>" . number_format($stagnantRate, 1) . "%</strong> didapat dari total <strong>" . number_format($stats['count_stagnant']) . "</strong> item yang tidak mengalami pergerakan nilai rata-rata dibanding periode sebelumnya.</p></div>";

    // --- INSIGHT 3: TOP SELLING PRODUCT ---
    $insightTopProduct = "
        <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Top Selling Product</h4></div>
        <div class='insight-metrics-grid'>
            <div class='insight-metric-item im-success'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . number_format($topProduct['wins']) . "</span><span class='insight-metric-sub'>Deals Secured</span></div>
            <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Dominance</span><span class='insight-metric-value'>" . number_format($dominance, 1) . "%</span><span class='insight-metric-sub'>Share of Total Win</span></div>
            <div class='insight-metric-item'><span class='insight-metric-label'>Total CC Win</span><span class='insight-metric-value'>" . number_format($topProduct['cc_count']) . "</span><span class='insight-metric-sub'>Unique Customers</span></div>
        </div>
        <div class='insight-narrative-box purple-theme'><div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div><p class='insight-narrative-text'>Produk <strong>{$topProduct['name']}</strong> menjadi market leader dengan kontribusi kemenangan sebesar <strong>" . number_format($dominance, 1) . "%</strong> dari seluruh win yang ada. Nilai ini didapatkan dari keberhasilan closing pada <strong>{$topProduct['cc_count']}</strong> customer berbeda.</p></div>";

    // --- INSIGHT 4: SUBMIT SPH (Completion) ---
    $insightCompleted = "
        <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Submit SPH Status</h4></div>
        <div class='insight-metrics-grid' style='grid-template-columns: repeat(2, 1fr);'>
            <div class='insight-metric-item im-success'><span class='insight-metric-label'>Submit SPH</span><span class='insight-metric-value'>" . number_format($stats['count_completed']) . "</span><span class='insight-metric-sub'>" . number_format($completionRate, 1) . "% of offerings</span></div>
            <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Total CC Done</span><span class='insight-metric-value'>" . count($stats['unique_cc_completed']) . "</span><span class='insight-metric-sub'>Customers Completed</span></div>
        </div>
        <div class='insight-narrative-box green-theme'><div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div><p class='insight-narrative-text'>Capaian Submit SPH sebesar <strong>" . number_format($stats['count_completed']) . "</strong> item menunjukkan bahwa <strong>" . number_format($completionRate, 1) . "%</strong> dari total pipeline telah mencapai tahap administratif akhir (100% progres).</p></div>";

    // --- INSIGHT 5: WIN RATE ---
    $insightWin = "
        <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Win Rate</h4></div>
        <div class='insight-metrics-grid'>
            <div class='insight-metric-item im-success'><span class='insight-metric-label'>Win Rate</span><span class='insight-metric-value'>" . number_format($winRate, 1) . "%</span><span class='insight-metric-sub'>Efficiency</span></div>
            <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . number_format($stats['count_win']) . "</span><span class='insight-metric-sub'>Wins</span></div>
            <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Total Loses</span><span class='insight-metric-value'>" . number_format($stats['count_lose']) . "</span><span class='insight-metric-sub'>Losses</span></div>
        </div>
        <div class='insight-narrative-box green-theme'><div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div><p class='insight-narrative-text'>Win Rate sebesar <strong>" . number_format($winRate, 1) . "%</strong> diperoleh dari perbandingan jumlah win (<strong>" . number_format($stats['count_win']) . "</strong>) terhadap total item yang sudah memiliki keputusan akhir (win/lose).</p></div>";

    // 6. Return Metrics (Keep all fields for UI cards)
    $metrics = [
        'prod_pulse' => [
            'value' => number_format($visitedRate, 1) . '%',
            'trend_text' => 'Visited Rate',
            'total_offerings' => number_format($stats['total_rows']),
            'visited_count' => number_format($stats['count_visited']),
            'unique_cc' => count($stats['unique_cc']),
            'unique_products' => count($stats['unique_products']),
        ],
        'stagnancy' => [
            'value' => number_format($stagnantRate, 1) . '%',
            'main_stat' => number_format($stats['count_stagnant']) . ' Items',
            'trend' => ($stagnantRate > 50) ? -1 : 1, 
        ],
        'win' => [
            'value' => number_format($winRate, 1) . '%',
            'main_stat' => 'Win / (Win + Lose)',
        ],
        'win_offerings' => [ 
            'value' => $topProduct['name'],      
            'main_stat' => $topProduct['wins'] . ' Wins', 
            'trend' => 1
        ],
        'completed' => [
            'value' => number_format($stats['count_completed']),
            'main_stat' => number_format($completionRate, 1) . '% Done',
        ]
    ];

    return [
        'metrics' => $metrics,
        'insights_data' => [
            'prod_pulse' => $insightPulse,
            'stagnancy' => $insightStagnant,
            'win' => $insightWin,
            'win_offerings' => $insightTopProduct, 
            'completed' => $insightCompleted,
        ]
    ];
}

    /**
     * ✅ PRESERVED: Generate product leaderboard
     */
    private function generateProductLeaderboard($productData)
    {
        $productGrouped = [];

        foreach ($productData as $row) {
            $product = $row['product'];

            if (!isset($productGrouped[$product])) {
                $productGrouped[$product] = [
                    'product' => $product,
                    'total_progress' => 0,
                    'total_result' => 0,
                    'count' => 0,
                    'wins' => 0,
                ];
            }

            $productGrouped[$product]['total_progress'] += $row['progress_2'];
            $productGrouped[$product]['total_result'] += $row['result_2'];
            $productGrouped[$product]['count']++;

            if ($row['result_2'] == 100) {
                $productGrouped[$product]['wins']++;
            }
        }

        $leaderboard = [];
        foreach ($productGrouped as $product => $data) {
            $avgProgress = $data['count'] > 0 ? round($data['total_progress'] / $data['count'], 2) : 0;
            $avgResult = $data['count'] > 0 ? round($data['total_result'] / $data['count'], 2) : 0;
            $avgTotal = round(($avgProgress + $avgResult) / 2, 2);

            $leaderboard[] = [
                'product' => $product,
                'avg_progress' => $avgProgress,
                'avg_result' => $avgResult,
                'avg_total' => $avgTotal,
                'total_offerings' => $data['count'],
                'wins' => $data['wins'],
            ];
        }

        usort($leaderboard, function($a, $b) {
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] <=> $a['wins'];
            }
            return $a['total_offerings'] <=> $b['total_offerings'];
        });

        $top10 = array_slice($leaderboard, 0, 10);
        foreach ($top10 as $index => $row) {
            $top10[$index]['rank'] = $index + 1;
        }

        return [
            'top_10' => $top10,
            'all_products' => $leaderboard,
        ];
    }

    /**
     * ✅ PRESERVED: Generate improvement leaderboard
     */
    private function generateImprovementLeaderboard($productData)
    {
        $leaderboard = $productData;
        usort($leaderboard, function($a, $b) {
            return $b['change_avg'] <=> $a['change_avg'];
        });

        $top10 = array_slice($leaderboard, 0, 10);
        foreach ($top10 as $index => $row) {
            $top10[$index]['rank'] = $index + 1;
        }

        return $top10;
    }
}