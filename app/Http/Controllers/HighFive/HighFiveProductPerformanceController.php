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

            // ➕ 4. NEW: Perhitungan Statistik Status untuk Chart (Kategori Produk)
            $statusStats = $this->calculateProductStatusStats($data1, $data2);

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
                    'status_stats' => $statusStats, // Data tambahan untuk Chart
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
     * ➕ NEW METHOD: Menghitung kuantitas status progres berdasarkan Kategori Produk
     */
    private function calculateProductStatusStats($data1, $data2)
    {
        $stats = ['ss1' => [], 'ss2' => []];
        $categories = ['PDP', 'Connectivity', 'Digital Product', 'NeuCentrix', 'Cyber Security'];

        $categorizeStatus = function($row) {
            $p = floatval($row['progress_percentage'] ?? 0);
            if ($p >= 100) return 'sph';
            if ($p >= 75)  return 'presentasi';
            if ($p >= 50)  return 'mytens';
            if ($p > 0)    return 'visit';
            return 'idle';
        };

        $getProductCat = function($productName) use ($categories) {
            $name = strtoupper($productName);
            foreach ($categories as $cat) {
                if (strpos($name, strtoupper($cat)) !== false) return $cat;
            }
            return 'Connectivity'; 
        };

        foreach (['ss1' => $data1, 'ss2' => $data2] as $key => $dataset) {
            foreach ($dataset as $row) {
                $catProduct = $getProductCat($row['product'] ?? '');
                $status = $categorizeStatus($row);

                if ($status === 'idle') continue;

                if (!isset($stats[$key][$catProduct])) {
                    $stats[$key][$catProduct] = ['visit' => 0, 'mytens' => 0, 'presentasi' => 0, 'sph' => 0];
                }
                if (!isset($stats[$key]['Total'])) {
                    $stats[$key]['Total'] = ['visit' => 0, 'mytens' => 0, 'presentasi' => 0, 'sph' => 0];
                }

                $stats[$key][$catProduct][$status]++;
                $stats[$key]['Total'][$status]++;
            }
        }
        return $stats;
    }

    /**
     * ✅ PRESERVED: Calculate product performance
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
     * ✅ PRESERVED: Calculate Analysis with Full HTML Modal Templates
     */
    private function calculateProductAnalysis($mergedData, $snapshot1, $snapshot2)
    {
        $stats = [
            'total_rows' => count($mergedData),
            'total_progress' => 0,
            'count_active' => 0, 
            'count_inactive' => 0,
            'count_closed_global' => 0,
            'count_stagnant' => 0, 
            'count_win' => 0, 
            'count_lose' => 0, 
            'count_completed' => 0, 
            'count_sph_negotiation' => 0,
            'count_sph_closed' => 0,
            'unique_products' => [],
            'unique_cc' => [],
        ];

        $productStats = [];

        foreach ($mergedData as $row) {
            $stats['total_progress'] += $row['progress_2'];
            
            if (!empty($row['product'])) $stats['unique_products'][$row['product']] = true;
            if (!empty($row['customer'])) $stats['unique_cc'][$row['customer']] = true;

            if ($row['change_avg'] == 0) $stats['count_stagnant']++;

            $resText = strtolower($row['result'] ?? ''); 
            $resVal = $row['result_2'] ?? 0;

            $isWin = (strpos($resText, 'win') !== false || $resVal == 100);
            $isLose = (strpos($resText, 'lose') !== false);
            $isClosed = ($isWin || $isLose);
            $isCompleted = ($row['progress_2'] == 100);

            if ($isClosed) {
                $stats['count_closed_global']++;
            } elseif ($row['progress_2'] > 0) {
                $stats['count_active']++;
            } else {
                $stats['count_inactive']++;
            }

            if ($isWin) $stats['count_win']++;
            elseif ($isLose) $stats['count_lose']++;

            if ($isCompleted) {
                $stats['count_completed']++;
                if ($isClosed) $stats['count_sph_closed']++;
                else $stats['count_sph_negotiation']++;
            }

            $pName = $row['product'] ?? 'Unknown';
            if (!isset($productStats[$pName])) {
                $productStats[$pName] = ['wins' => 0, 'total' => 0, 'stagnant' => 0];
            }
            $productStats[$pName]['total']++;
            if ($isWin) $productStats[$pName]['wins']++;
            if ($row['change_avg'] == 0) $productStats[$pName]['stagnant']++;
        }

        $topProduct = ['name' => 'None', 'wins' => -1, 'total' => 999999];
        $mostStagnantProduct = ['name' => null, 'count' => 0];
        foreach ($productStats as $name => $ps) {
            if ($ps['wins'] > $topProduct['wins'] || ($ps['wins'] == $topProduct['wins'] && $ps['total'] < $topProduct['total'])) {
                $topProduct = ['name' => $name, 'wins' => $ps['wins'], 'total' => $ps['total']];
            }
            if ($ps['stagnant'] > $mostStagnantProduct['count']) {
                $mostStagnantProduct = ['name' => $name, 'count' => $ps['stagnant']];
            }
        }

        $total = $stats['total_rows'] ?: 1;
        $activeRate = ($stats['count_active'] / $total) * 100;
        $stagnantRate = ($stats['count_stagnant'] / $total) * 100;
        $completionRate = ($stats['count_completed'] / $total) * 100;
        $totalClosedDecision = $stats['count_win'] + $stats['count_lose'];
        $winRate = $totalClosedDecision > 0 ? ($stats['count_win'] / $totalClosedDecision) * 100 : 0;
        $dominance = $stats['count_win'] > 0 ? ($topProduct['wins'] / $stats['count_win']) * 100 : 0;

        // --- FULL HTML INSIGHTS (KEINGINAN ANDA) ---
        $insightPulse = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Productivity Pulse</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Active Offerings</span><span class='insight-metric-value'>" . number_format($stats['count_active']) . "</span><span class='insight-metric-sub'>Sedang Berjalan</span></div>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Pending Prospects</span><span class='insight-metric-value'>" . number_format($stats['count_inactive']) . "</span><span class='insight-metric-sub'>Belum Ditawarkan</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Finalized Deals</span><span class='insight-metric-value'>" . number_format($stats['count_closed_global']) . "</span><span class='insight-metric-sub'>Win & Lose</span></div>
            </div>
            <div class='insight-narrative-box blue-theme'>
                <div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Tingkat produktivitas saat ini berada di angka <strong>" . number_format($activeRate, 1) . "%</strong> dengan fokus pada pemrosesan offerings aktif. Perlu diperhatikan terdapat <strong>" . number_format($stats['count_inactive']) . "</strong> offerings yang masih berstatus <em>idle (belum ditawarkan)</em>, sementara <strong>" . number_format($stats['count_closed_global']) . "</strong> offerings telah berhasil difinalisasi.
                </p>
            </div>";

        $insightStagnant = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Stagnancy Analysis</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Total Stagnant</span><span class='insight-metric-value'>" . number_format($stats['count_stagnant']) . "</span><span class='insight-metric-sub'>Tanpa Progress</span></div>
                <div class='insight-metric-item im-warning'><span class='insight-metric-label'>Stagnant Rate</span><span class='insight-metric-value'>" . number_format($stagnantRate, 1) . "%</span><span class='insight-metric-sub'>Rasio Hambatan</span></div>
                " . ($mostStagnantProduct['name'] ? "<div class='insight-metric-item im-warning'><span class='insight-metric-label'>Critical Focus</span><span class='insight-metric-value' style='font-size:14px;'>{$mostStagnantProduct['name']}</span><span class='insight-metric-sub'>{$mostStagnantProduct['count']} Zero Improvement</span></div>" : "") . "
            </div>
            <div class='insight-narrative-box'>
                <div class='insight-narrative-title'><i class='fas fa-exclamation-circle'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Terdapat <strong>" . number_format($stats['count_stagnant']) . "</strong> offerings yang stagnan (<strong>" . number_format($stagnantRate, 1) . "%</strong>) tanpa adanya <em>improvement</em> dibanding periode sebelumnya. Perlu evaluasi mendalam pada produk <strong>{$mostStagnantProduct['name']}</strong> sebagai penyumbang stagnansi tertinggi.
                </p>
            </div>";

        $insightTopProduct = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Top Selling Product</h4></div>
            <div class='insight-metrics-grid' style='grid-template-columns: repeat(2, 1fr);'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . number_format($topProduct['wins']) . "</span><span class='insight-metric-sub'>Offerings Secured</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Market Share</span><span class='insight-metric-value'>" . number_format($dominance, 1) . "%</span><span class='insight-metric-sub'>Kontribusi TREG3</span></div>
            </div>
            <div class='insight-narrative-box purple-theme'>
                <div class='insight-narrative-title'><i class='fas fa-trophy'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Produk <strong>{$topProduct['name']}</strong> mengukuhkan posisinya sebagai <em>market leader</em> periode ini dengan meraih <strong>{$topProduct['wins']}</strong> kemenangan, menyumbang <strong>" . number_format($dominance, 1) . "%</strong> dari total keberhasilan seluruh offerings di wilayah TREG3.
                </p>
            </div>";

        $insightCompleted = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Submit SPH Status</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Total Submit SPH</span><span class='insight-metric-value'>" . number_format($stats['count_completed']) . "</span><span class='insight-metric-sub'>Offerings 100%</span></div>
                <div class='insight-metric-item im-warning'><span class='insight-metric-label'>In-Progress SPH</span><span class='insight-metric-value'>" . number_format($stats['count_sph_negotiation']) . "</span><span class='insight-metric-sub'>Negotiation Phase</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Finalized SPH</span><span class='insight-metric-value'>" . number_format($stats['count_sph_closed']) . "</span><span class='insight-metric-sub'>Win/Lose Result</span></div>
            </div>
            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-file-invoice'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Dari total <strong>" . number_format($stats['count_completed']) . "</strong> offerings yang telah submit SPH, sebanyak <strong>" . number_format($stats['count_sph_negotiation']) . "</strong> masih dalam tahap negosiasi intensif, sementara <strong>" . number_format($stats['count_sph_closed']) . "</strong> lainnya telah mencapai keputusan final.
                </p>
            </div>";

        $insightWin = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Win Rate Efficiency</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Win Rate</span><span class='insight-metric-value'>" . number_format($winRate, 1) . "%</span><span class='insight-metric-sub'>Efficiency Ratio</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . number_format($stats['count_win']) . "</span><span class='insight-metric-sub'>Offerings</span></div>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Total Loses</span><span class='insight-metric-value'>" . number_format($stats['count_lose']) . "</span><span class='insight-metric-sub'>Offerings</span></div>
            </div>
            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-chart-line'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Efisiensi konversi berada pada tingkat <strong>" . number_format($winRate, 1) . "%</strong>. Angka ini mencerminkan rasio kemenangan tim dari total <strong>" . $totalClosedDecision . "</strong> offerings yang telah mencapai tahap keputusan akhir (Closed).
                </p>
            </div>";

        return [
            'metrics' => [
                'prod_pulse' => [
                    'value' => number_format($activeRate, 1) . '%', 'trend_text' => 'dari Semua Offerings',
                    'total_offerings' => number_format($stats['total_rows']), 'active_count' => number_format($stats['count_active']),
                    'unique_cc' => count($stats['unique_cc']), 'unique_products' => count($stats['unique_products']),
                    'wins' => number_format($stats['count_win']), 'loses' => number_format($stats['count_lose']),
                ],
                'stagnancy' => ['value' => number_format($stagnantRate, 1) . '%', 'main_stat' => number_format($stats['count_stagnant']) . ' Items'],
                'win' => ['value' => number_format($winRate, 1) . '%', 'main_stat' => number_format($stats['count_win']) . ' Total Wins'],
                'win_offerings' => ['value' => $topProduct['name'], 'main_stat' => $topProduct['wins'] . ' Wins'],
                'completed' => ['value' => number_format($stats['count_completed']), 'main_stat' => number_format($completionRate, 1) . '% SPH Submit']
            ],
            'insights_data' => [
                'prod_pulse' => $insightPulse, 'stagnancy' => $insightStagnant,
                'win' => $insightWin, 'win_offerings' => $insightTopProduct, 'completed' => $insightCompleted,
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
                $productGrouped[$product] = ['product' => $product, 'total_progress' => 0, 'total_result' => 0, 'count' => 0, 'wins' => 0];
            }
            $productGrouped[$product]['total_progress'] += $row['progress_2'];
            $productGrouped[$product]['total_result'] += $row['result_2'];
            $productGrouped[$product]['count']++;
            if ($row['result_2'] == 100) $productGrouped[$product]['wins']++;
        }

        $leaderboard = [];
        foreach ($productGrouped as $product => $data) {
            $avgProgress = $data['count'] > 0 ? round($data['total_progress'] / $data['count'], 2) : 0;
            $avgResult = $data['count'] > 0 ? round($data['total_result'] / $data['count'], 2) : 0;
            $leaderboard[] = [
                'product' => $product, 'avg_progress' => $avgProgress, 'avg_result' => $avgResult,
                'avg_total' => round(($avgProgress + $avgResult) / 2, 2), 'total_offerings' => $data['count'], 'wins' => $data['wins'],
            ];
        }

        usort($leaderboard, fn($a, $b) => $b['wins'] <=> $a['wins'] ?: $a['total_offerings'] <=> $b['total_offerings']);
        $top10 = array_slice($leaderboard, 0, 10);
        foreach ($top10 as $index => $row) $top10[$index]['rank'] = $index + 1;

        return ['top_10' => $top10, 'all_products' => $leaderboard];
    }

    /**
     * ✅ PRESERVED: Generate improvement leaderboard
     */
    private function generateImprovementLeaderboard($productData)
    {
        $leaderboard = $productData;
        usort($leaderboard, fn($a, $b) => $b['change_avg'] <=> $a['change_avg']);
        $top10 = array_slice($leaderboard, 0, 10);
        foreach ($top10 as $index => $row) $top10[$index]['rank'] = $index + 1;
        return $top10;
    }
}