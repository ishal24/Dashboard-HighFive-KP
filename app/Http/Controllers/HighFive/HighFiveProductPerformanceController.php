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
        ];

        // Variabel Khusus Per-Produk
        $productStats = [];

        // 2. Loop Data untuk Agregasi
        foreach ($mergedData as $row) {
            $stats['total_progress'] += $row['progress_2'];
            
            // Global Counters
            if (!empty($row['product'])) $stats['unique_products'][$row['product']] = true;
            if (!empty($row['customer'])) $stats['unique_cc'][$row['customer']] = true;

            if ($row['progress_2'] > 0) $stats['count_visited']++;
            else $stats['count_zero']++;

            if ($row['change_avg'] == 0) $stats['count_stagnant']++;

            if ($row['result_2'] == 100) $stats['count_win']++;
            elseif ($row['progress_2'] == 100 && $row['result_2'] == 0) $stats['count_lose']++;

            if ($row['progress_2'] == 100) $stats['count_completed']++;

            // 🔥 Logic Accumulation Per Product
            $pName = $row['product'] ?? 'Unknown';
            if (!isset($productStats[$pName])) {
                $productStats[$pName] = [
                    'wins' => 0,
                    'total_offerings' => 0,
                    'active_pipeline' => 0
                ];
            }
            $productStats[$pName]['total_offerings']++;
            
            if ($row['result_2'] == 100) {
                $productStats[$pName]['wins']++;
            }
            if ($row['progress_2'] > 0 && $row['result_2'] < 100) {
                $productStats[$pName]['active_pipeline']++;
            }
        }

        // 3. Cari Top Selling Product (With Tie-Breaker Logic)
        $topProduct = [
            'name' => 'None',
            'wins' => -1,
            'total' => 999999,
            'dominance' => 0,
            'win_rate' => 0,
            'pipeline' => 0
        ];

        foreach ($productStats as $name => $data) {
            $updateChampion = false;

            // Prioritas 1: Jumlah Win Terbanyak
            if ($data['wins'] > $topProduct['wins']) {
                $updateChampion = true;
            } 
            // Prioritas 2: Jika Win SAMA, pilih yang Offering-nya LEBIH SEDIKIT (Efisiensi Tinggi)
            elseif ($data['wins'] == $topProduct['wins'] && $data['wins'] > 0) {
                if ($data['total_offerings'] < $topProduct['total']) {
                    $updateChampion = true;
                }
            }

            if ($updateChampion) {
                $topProduct['name'] = $name;
                $topProduct['wins'] = $data['wins'];
                $topProduct['total'] = $data['total_offerings'];
                $topProduct['pipeline'] = $data['active_pipeline'];
            }
        }
        
        if ($topProduct['wins'] == -1) {
            $topProduct['wins'] = 0;
            $topProduct['total'] = 0;
        }

        // Hitung Rasio Top Product
        if ($stats['count_win'] > 0) {
            $topProduct['dominance'] = ($topProduct['wins'] / $stats['count_win']) * 100;
        }
        if ($topProduct['total'] > 0) {
            $topProduct['win_rate'] = ($topProduct['wins'] / $topProduct['total']) * 100;
        }

        // 4. Kalkulasi Persentase Global Lainnya
        $total = $stats['total_rows'] ?: 1;
        $visitedRate = ($stats['count_visited'] / $total) * 100;
        $stagnantRate = ($stats['count_stagnant'] / $total) * 100;
        $completionRate = ($stats['count_completed'] / $total) * 100;
        $totalClosed = $stats['count_win'] + $stats['count_lose'];
        $winRate = $totalClosed > 0 ? ($stats['count_win'] / $totalClosed) * 100 : 0;

        // 5. Generate HTML Insights
        // RE-USE HTML LENGKAP DARI JAWABAN SEBELUMNYA:
        $insightPulse = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#dbeafe; padding:8px; border-radius:8px; color:#2563eb;'><i class='fas fa-boxes fa-lg'></i></div>
                <div><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Productivity Pulse</h4><p style='font-size:12px; color:#64748b; margin:0;'>Seberapa aktif produk ditawarkan?</p></div>
            </div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Total Offerings</span><span class='insight-metric-value'>" . number_format($stats['total_rows']) . "</span><span class='insight-metric-sub'>Pipeline Lines</span></div>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Active (Visited)</span><span class='insight-metric-value'>" . number_format($stats['count_visited']) . "</span><span class='insight-metric-sub'>" . number_format($visitedRate, 1) . "% of total</span></div>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Unvisited</span><span class='insight-metric-value'>" . number_format($stats['count_zero']) . "</span><span class='insight-metric-sub'>0% Progress</span></div>
            </div>
            <div class='insight-narrative-box blue-theme'><div class='insight-narrative-title'><i class='fas fa-info-circle'></i> Analisis</div><p class='insight-narrative-text'>Dari total <strong>" . number_format($stats['total_rows']) . "</strong> baris offerings, sebanyak <strong>" . number_format($visitedRate, 1) . "%</strong> sudah mulai dikerjakan.</p></div>";

        $insightStagnant = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#fef3c7; padding:8px; border-radius:8px; color:#d97706;'><i class='fas fa-anchor fa-lg'></i></div>
                <div><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Stagnancy Analysis</h4><p style='font-size:12px; color:#64748b; margin:0;'>Pipeline yang macet / tidak bergerak</p></div>
            </div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Total Stagnant</span><span class='insight-metric-value'>" . number_format($stats['count_stagnant']) . "</span><span class='insight-metric-sub'>No Movement</span></div>
                <div class='insight-metric-item im-warning'><span class='insight-metric-label'>Stagnant Rate</span><span class='insight-metric-value'>" . number_format($stagnantRate, 1) . "%</span><span class='insight-metric-sub'>of Pipeline</span></div>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Moving Data</span><span class='insight-metric-value'>" . number_format($stats['total_rows'] - $stats['count_stagnant']) . "</span><span class='insight-metric-sub'>Has Progress</span></div>
            </div>
            <div class='insight-narrative-box'><div class='insight-narrative-title'><i class='fas fa-exclamation-triangle'></i> Warning</div><p class='insight-narrative-text'>Terdapat <strong>" . number_format($stats['count_stagnant']) . "</strong> item yang tidak mengalami perubahan status dibanding periode lalu.</p></div>";

        $insightwin = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#ecfdf5; padding:8px; border-radius:8px; color:#059669;'><i class='fas fa-percent fa-lg'></i></div>
                <div><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Win Rate</h4><p style='font-size:12px; color:#64748b; margin:0;'>Efektivitas Closing (Win vs Lose)</p></div>
            </div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>win Rate</span><span class='insight-metric-value'>" . number_format($winRate, 1) . "%</span><span class='insight-metric-sub'>Win / (Win + Lose)</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . number_format($stats['count_win']) . "</span><span class='insight-metric-sub'>Deals Closed</span></div>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Total Loses</span><span class='insight-metric-value'>" . number_format($stats['count_lose']) . "</span><span class='insight-metric-sub'>Opportunities Lost</span></div>
            </div>
            <div class='insight-narrative-box green-theme'><div class='insight-narrative-title'><i class='fas fa-check-double'></i> Efektivitas</div><p class='insight-narrative-text'>Tim berhasil mengamankan <strong>" . number_format($winRate, 1) . "%</strong> kemenangan dari total deal yang sudah diputuskan.</p></div>";
        
        $insightCompleted = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#dcfce7; padding:8px; border-radius:8px; color:#166534;'><i class='fas fa-check-circle fa-lg'></i></div>
                <div><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Completion Status</h4><p style='font-size:12px; color:#64748b; margin:0;'>Progress Administratif (100%)</p></div>
            </div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Fully Completed</span><span class='insight-metric-value'>" . number_format($stats['count_completed']) . "</span><span class='insight-metric-sub'>Progress 100%</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Completion Rate</span><span class='insight-metric-value'>" . number_format($completionRate, 1) . "%</span><span class='insight-metric-sub'>Overall</span></div>
                <div class='insight-metric-item im-warning'><span class='insight-metric-label'>In Progress</span><span class='insight-metric-value'>" . number_format($stats['total_rows'] - $stats['count_completed']) . "</span><span class='insight-metric-sub'>< 100%</span></div>
            </div>
            <div class='insight-narrative-box green-theme'><div class='insight-narrative-title'><i class='fas fa-clipboard-check'></i> Overview</div><p class='insight-narrative-text'>Sebanyak <strong>" . number_format($stats['count_completed']) . "</strong> item telah mencapai tahap akhir (Submit SPH/Win).</p></div>";


        // --- 🔥 INSIGHT 4: TOP SELLING PRODUCT ---
        $insightTopProduct = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#f3e8ff; padding:8px; border-radius:8px; color:#9333ea;'><i class='fas fa-crown fa-lg'></i></div>
                <div>
                    <h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Top Selling Product</h4>
                    <p style='font-size:12px; color:#64748b; margin:0;'>Produk dengan jumlah WIN terbanyak</p>
                </div>
            </div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-primary'>
                    <span class='insight-metric-label'>Champion</span>
                    <span class='insight-metric-value' style='font-size:16px; line-height:1.2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;' title='{$topProduct['name']}'>" . $topProduct['name'] . "</span>
                    <span class='insight-metric-sub'>" . number_format($topProduct['wins']) . " Total Wins</span>
                </div>
                 <div class='insight-metric-item im-success'>
                    <span class='insight-metric-label'>Dominance</span>
                    <span class='insight-metric-value'>" . number_format($topProduct['dominance'], 1) . "%</span>
                    <span class='insight-metric-sub'>dari Semua Wins</span>
                </div>
            </div>
            <div class='insight-narrative-box blue-theme'>
                <div class='insight-narrative-title'><i class='fas fa-star'></i> Market Leader</div>
                <p class='insight-narrative-text'>
                    <strong>{$topProduct['name']}</strong> menjadi produk terlaris. 
                    Meskipun hanya diajukan sebanyak <strong>" . number_format($topProduct['total']) . "</strong> kali, produk ini berhasil deal sebanyak <strong>" . number_format($topProduct['wins']) . "</strong> kali (Win Rate: " . number_format($topProduct['win_rate'], 1) . "%).
                </p>
            </div>";

        // 6. Return Metrics & Insight Data
        $metrics = [
            'prod_pulse' => [
                'value' => number_format($visitedRate, 1) . '%',
                'trend_text' => 'Visited Rate',
                'total_offerings' => number_format($stats['total_rows']),
                'visited_count' => number_format($stats['count_visited']),
                'unique_cc' => count($stats['unique_cc']),
                'unique_products' => count($stats['unique_products']),
            ],
            // 🔥 UPDATED: SWAPPED VALUE & SUB-VALUE
            'stagnancy' => [
                'value' => number_format($stagnantRate, 1) . '%', // Main: Persentase
                'main_stat' => number_format($stats['count_stagnant']) . ' Items', // Sub: Count
                'trend' => ($stagnantRate > 50) ? -1 : 1, 
            ],
            'win' => [
                'value' => number_format($winRate, 1) . '%',
                'main_stat' => 'Win / (Win + Lose)',
            ],
            // 🔥 UPDATED: Top Selling Product
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

        $insightsData = [
            'prod_pulse' => $insightPulse,
            'stagnancy' => $insightStagnant,
            'win' => $insightwin,
            'win_offerings' => $insightTopProduct, 
            'completed' => $insightCompleted,
        ];

        return [
            'metrics' => $metrics,
            'insights_data' => $insightsData
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