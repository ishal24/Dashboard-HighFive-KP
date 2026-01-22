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
        $categories = ['PDP', 'Cyber Security', 'Digital Product', 'NEUCENTRIX', 'Connectivity Bandwidth'];

        $categorizeStatus = function($row) {
            $p = floatval($row['progress_percentage'] ?? 0);
            if ($p >= 100) return 'sph';
            if ($p >= 75)  return 'presentasi';
            if ($p >= 50)  return 'mytens';
            if ($p > 0)    return 'visit';
            return 'idle';
        };

        // ✅ FIX: Proper product to category mapping based on exact product names
        $getProductCat = function($productName) {
            $name = trim($productName);
            
            // PDP Category
            $pdpProducts = [
                'FullGuided Comply PDP',
                'Guided Comply PDP',
                'Self Comply PDP',
                'Template dokumen PDP Alacarte',
                'Readyness Check PDP'
            ];
            
            // Cyber Security Category  
            $cyberProducts = [
                'DDOS over Astinet',
                'VAPT - Web',
                'SOC - Cybersecurity',
                'VAPT - Mobile',
                'DDOS over IP Transit'
            ];
            
            // Digital Product Category
            $digitalProducts = [
                'Antarez Easy',
                'Usecase Bigbox - Doc Management'
            ];
            
            // NEUCENTRIX Category
            $neucentrixProducts = [
                'NEUCENTRIX'
            ];
            
            // Connectivity Bandwidth Category
            $connectivityProducts = [
                'Astinet + DDoS',
                'Indibiz / Astinet',
                'Astinet & WMS'
            ];
            
            // Check exact match first
            if (in_array($name, $pdpProducts)) return 'PDP';
            if (in_array($name, $cyberProducts)) return 'Cyber Security';
            if (in_array($name, $digitalProducts)) return 'Digital Product';
            if (in_array($name, $neucentrixProducts)) return 'NEUCENTRIX';
            if (in_array($name, $connectivityProducts)) return 'Connectivity Bandwidth';
            
            // Fallback: check if product name contains category keywords
            $nameUpper = strtoupper($name);
            if (strpos($nameUpper, 'PDP') !== false) return 'PDP';
            if (strpos($nameUpper, 'CYBER') !== false || strpos($nameUpper, 'VAPT') !== false || strpos($nameUpper, 'DDOS') !== false || strpos($nameUpper, 'SOC') !== false) return 'Cyber Security';
            if (strpos($nameUpper, 'ANTAREZ') !== false || strpos($nameUpper, 'BIGBOX') !== false) return 'Digital Product';
            if (strpos($nameUpper, 'NEUCENTRIX') !== false) return 'NEUCENTRIX';
            if (strpos($nameUpper, 'ASTINET') !== false || strpos($nameUpper, 'INDIBIZ') !== false || strpos($nameUpper, 'WMS') !== false) return 'Connectivity Bandwidth';
            
            return 'Connectivity Bandwidth'; // Default fallback
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
                'nilai' => $item2['nilai'] ?? $item1['nilai'] ?? 0, // ✅ Tambahkan NILAI
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
                    'nilai' => floatval($row['nilai'] ?? 0), // ✅ Tambahkan NILAI
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
            'total_nilai_win' => 0, // ✅ NEW: Total NILAI dari semua win
        ];

        $productStats = [];

        foreach ($mergedData as $row) {
            $stats['total_progress'] += $row['progress_2'];
            
            if (!empty($row['product'])) $stats['unique_products'][$row['product']] = true;
            if (!empty($row['customer'])) $stats['unique_cc'][$row['customer']] = true;

            // Deteksi Win/Lose TERLEBIH DAHULU sebelum hitung stagnant
            $resText = strtolower($row['result'] ?? ''); 
            $resVal = $row['result_2'] ?? 0;

            $isWin = (strpos($resText, 'win') !== false || $resVal == 100);
            $isLose = (strpos($resText, 'lose') !== false);
            $isClosed = ($isWin || $isLose);
            $isCompleted = ($row['progress_2'] == 100);

            // ✅ FIX: Hitung stagnant HANYA untuk offering yang BUKAN Win/Lose
            // Offering yang sudah closed (Win/Lose) tidak boleh masuk hitungan stagnant
            if ($row['change_avg'] == 0 && !$isClosed) {
                $stats['count_stagnant']++;
            }

            if ($isClosed) {
                $stats['count_closed_global']++;
            } elseif ($row['progress_2'] > 0) {
                $stats['count_active']++;
            } else {
                $stats['count_inactive']++;
            }

            if ($isWin) {
                $stats['count_win']++;
                $stats['total_nilai_win'] += floatval($row['nilai'] ?? 0); // ✅ Tambahkan NILAI untuk win
            }
            elseif ($isLose) $stats['count_lose']++;

            if ($isCompleted) {
                $stats['count_completed']++;
                if ($isClosed) $stats['count_sph_closed']++;
                else $stats['count_sph_negotiation']++;
            }

            $pName = $row['product'] ?? 'Unknown';
            if (!isset($productStats[$pName])) {
                $productStats[$pName] = [
                    'wins' => 0, 
                    'total' => 0, 
                    'stagnant' => 0,
                    'total_nilai_win' => 0 // ✅ NEW: Total NILAI per product
                ];
            }
            $productStats[$pName]['total']++;
            
            if ($isWin) {
                $productStats[$pName]['wins']++;
                $productStats[$pName]['total_nilai_win'] += floatval($row['nilai'] ?? 0); // ✅ Tambahkan NILAI
            }
            
            // ✅ FIX: Per-product stagnant juga hanya hitung yang BUKAN Win/Lose
            if ($row['change_avg'] == 0 && !$isClosed) {
                $productStats[$pName]['stagnant']++;
            }
        }

        $topProduct = ['name' => 'None', 'wins' => -1, 'total' => 999999, 'total_nilai_win' => 0];
        $mostStagnantProduct = ['name' => null, 'count' => 0];
        foreach ($productStats as $name => $ps) {
            if ($ps['wins'] > $topProduct['wins'] || ($ps['wins'] == $topProduct['wins'] && $ps['total'] < $topProduct['total'])) {
                $topProduct = [
                    'name' => $name, 
                    'wins' => $ps['wins'], 
                    'total' => $ps['total'],
                    'total_nilai_win' => $ps['total_nilai_win'] // ✅ Simpan NILAI
                ];
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
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Status Offerings Overview</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Active Offerings</span><span class='insight-metric-value'>" . number_format($stats['count_active']) . "</span><span class='insight-metric-sub'>Sedang Berjalan</span></div>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Idle Offerings</span><span class='insight-metric-value'>" . number_format($stats['count_inactive']) . "</span><span class='insight-metric-sub'>Belum Ditawarkan</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Closed Offerings</span><span class='insight-metric-value'>" . number_format($stats['count_closed_global']) . "</span><span class='insight-metric-sub'>Win & Lose</span></div>
            </div>
            <div class='insight-narrative-box blue-theme'>
                <div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Tingkat produktivitas saat ini berada di angka <strong>" . number_format($activeRate, 1) . "% (". number_format($stats['count_active']) . ")</strong> dengan fokus pada pemrosesan offerings aktif. Perlu diperhatikan terdapat <strong>" . number_format($stats['count_inactive']) . "</strong> offerings yang masih berstatus <em>idle (belum ditawarkan)</em>, sementara <strong>" . number_format($stats['count_closed_global']) . "</strong> offerings telah berhasil difinalisasi.
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
                    Terdapat <strong>" . number_format($stats['count_stagnant']) . "</strong> offerings (yang belum closed) yang stagnan (<strong>" . number_format($stagnantRate, 1) . "%</strong>) tanpa adanya <em>improvement</em> dibanding periode sebelumnya. Perlu evaluasi mendalam pada produk <strong>{$mostStagnantProduct['name']}</strong> sebagai penyumbang stagnansi tertinggi.
                </p>
            </div>";

        $insightTopProduct = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Top Selling Product</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . number_format($topProduct['wins']) . "</span><span class='insight-metric-sub'>Offerings Secured</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Market Share</span><span class='insight-metric-value'>" . number_format($dominance, 1) . "%</span><span class='insight-metric-sub'>Kontribusi TREG3</span></div>
                <div class='insight-metric-item im-warning'><span class='insight-metric-label'>Total Nilai</span><span class='insight-metric-value'>Rp " . number_format($topProduct['total_nilai_win'], 0, ',', '.') . "</span><span class='insight-metric-sub'>Win Value</span></div>
            </div>
            <div class='insight-narrative-box purple-theme'>
                <div class='insight-narrative-title'><i class='fas fa-trophy'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Produk <strong>{$topProduct['name']}</strong> menjadi <em>market leader</em> periode ini karena meraih <strong>{$topProduct['wins']}</strong> wins dengan total nilai <strong>Rp " . number_format($topProduct['total_nilai_win'], 0, ',', '.') . "</strong>, menyumbang <strong>" . number_format($dominance, 1) . "%</strong> dari total wins seluruh offerings di wilayah TREG3.
                </p>
            </div>";

        $insightCompleted = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Submit SPH Status</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Total Submit SPH</span><span class='insight-metric-value'>" . number_format($stats['count_completed']) . "</span><span class='insight-metric-sub'>Progress 100%</span></div>
                <div class='insight-metric-item im-warning'><span class='insight-metric-label'>In-Progress SPH</span><span class='insight-metric-value'>" . number_format($stats['count_sph_negotiation']) . "</span><span class='insight-metric-sub'>In Negotiation</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Finalized SPH</span><span class='insight-metric-value'>" . number_format($stats['count_sph_closed']) . "</span><span class='insight-metric-sub'>Win/Lose</span></div>
            </div>
            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-file-invoice'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Dari total <strong>" . number_format($stats['count_completed']) . "</strong> offerings yang telah submit SPH, sebanyak <strong>" . number_format($stats['count_sph_negotiation']) . "</strong> masih dalam tahap negosiasi intensif, sementara <strong>" . number_format($stats['count_sph_closed']) . "</strong> lainnya telah mencapai keputusan final.
                </p>
            </div>";

        $insightWin = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Win Rate</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Total Nilai Win</span><span class='insight-metric-value'>Rp " . number_format($stats['total_nilai_win'], 0, ',', '.') . "</span><span class='insight-metric-sub'>Nilai Offerings</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . number_format($stats['count_win']) . "</span><span class='insight-metric-sub'>Offerings</span></div>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>Total Loses</span><span class='insight-metric-value'>" . number_format($stats['count_lose']) . "</span><span class='insight-metric-sub'>Offerings</span></div>
            </div>
            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-chart-line'></i> Analisis Insight</div>
                    <p class='insight-narrative-text'>
                        Total nilai dari offerings yang berhasil dimenangkan mencapai <strong>Rp " . number_format($stats['total_nilai_win'], 0, ',', '.') . "</strong> melalui <strong>" . number_format($stats['count_win']) . " wins</strong>. Win Rate sebesar <strong>" . ($totalClosedDecision > 0 ? number_format(($stats['count_win'] / $totalClosedDecision) * 100, 1) : 0) . "%</strong> mencerminkan performa kemenangan tim dari total <strong>" . $totalClosedDecision . "</strong> offerings yang telah mencapai tahap keputusan akhir (Closed).
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
                'win' => [
                    'value' => number_format($winRate, 1) . '%', // ✅ Card tetap tampilkan Win Rate %
                    'main_stat' => number_format($stats['count_win']) . ' Total Wins',
                    'total_nilai_win' => $stats['total_nilai_win'] // ✅ NILAI untuk modal insight
                ],
                'win_offerings' => [
                    'value' => $topProduct['name'], 
                    'main_stat' => $topProduct['wins'] . ' Wins',
                    'total_nilai_win' => 'Rp ' . number_format($topProduct['total_nilai_win'], 0, ',', '.') // ✅ NILAI untuk modal insight
                ],
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
            // ✅ FIX: No rounding - keep full precision
            $avgProgress = $data['count'] > 0 ? $data['total_progress'] / $data['count'] : 0;
            $avgResult = $data['count'] > 0 ? $data['total_result'] / $data['count'] : 0;
            $leaderboard[] = [
                'product' => $product, 'avg_progress' => $avgProgress, 'avg_result' => $avgResult,
                'avg_total' => ($avgProgress + $avgResult) / 2, 'total_offerings' => $data['count'], 'wins' => $data['wins'],
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