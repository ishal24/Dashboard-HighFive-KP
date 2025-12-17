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
     *
     * INPUT CHANGES:
     * - OLD: dataset_1_id, dataset_2_id
     * - NEW: snapshot_1_id, snapshot_2_id
     *
     * DATA SOURCE CHANGES:
     * - OLD: Fetch from Google Sheets API
     * - NEW: Parse JSON from database
     *
     * ✅ PRESERVED: All calculation logic remains the same
     * ✅ NEW: Handle empty customer name with default value
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

            // 2. Calculate NEW 5 Metrics & Insights
            $metricsData = $this->calculateProductMetrics($productData, $snapshot1, $snapshot2);

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
                    // New Metrics Structure
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
     * 📝 NEW: Include witel information for filtering
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

            // ✅ NEW: Handle empty customer with default value and ensure string type
            $customerName = $item2['customer'] ?? $item1['customer'] ?? null;

            // Defensive: Ensure customer is not accidentally set to product value
            if ($customerName && isset($item2['product']) && $customerName === $item2['product']) {
                $customerName = null; // Reset if customer accidentally equals product
            }

            $merged[$key] = [
                'am' => $item2['am'] ?? $item1['am'],
                'customer' => $customerName, // Can be null, will be handled in frontend
                'product' => $item2['product'] ?? $item1['product'],
                'witel' => $item2['witel'] ?? $item1['witel'], // Include witel for filtering
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

            // Handle null customer for sorting
            $customerA = $a['customer'] ?? 'ZZZZ'; // Put nulls at end
            $customerB = $b['customer'] ?? 'ZZZZ';
            $customerCompare = strcmp($customerA, $customerB);
            if ($customerCompare !== 0) return $customerCompare;

            return strcmp($a['product'], $b['product']);
        });

        return $this->addRowspanInfo($merged);
    }

    /**
     * ✅ PRESERVED: Group by AM → Customer → Product
     * 📝 NEW: Include witel information
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

            // Allow null customer, use empty string as key
            $customerKey = $customer ?: '__EMPTY__';
            $key = $am . '|' . $customerKey . '|' . $product;

            if (!isset($grouped[$key]) ||
                $row['progress_percentage'] > $grouped[$key]['progress_percentage'] ||
                $row['result_percentage'] > $grouped[$key]['result_percentage']) {

                $grouped[$key] = [
                    'am' => $am,
                    'customer' => $customer, // Can be null
                    'product' => $product,
                    'witel' => $witel,
                    'progress_percentage' => $row['progress_percentage'],
                    'result_percentage' => $row['result_percentage'],
                ];
            }
        }

        return $grouped;
    }

    /**
     * ✅ PRESERVED: Add rowspan info for hierarchy (unchanged)
     */
    private function addRowspanInfo($data)
    {
        $result = [];
        $currentAM = null;
        $currentCustomer = null;
        $amStartIndex = 0;
        $customerStartIndex = 0;

        foreach ($data as $index => $row) {
            // Cek apakah AM berubah
            if ($row['am'] !== $currentAM) {
                if ($currentAM !== null) {
                    // Finalize AM sebelumnya
                    $this->finalizeAMGroup($result, $amStartIndex, $index);
                    
                    // 🔥 FIX BUG DISINI: 
                    // Saat AM berubah, grup customer terakhir milik AM tersebut juga harus ditutup!
                    $this->finalizeCustomerGroup($result, $customerStartIndex, $index);
                }
                
                // Reset trackers untuk AM baru
                $currentAM = $row['am'];
                $currentCustomer = $row['customer'];
                $amStartIndex = $index;
                $customerStartIndex = $index;
            }
            // Jika AM sama, cek apakah Customer berubah
            elseif ($row['customer'] !== $currentCustomer) {
                if ($currentCustomer !== null) {
                    $this->finalizeCustomerGroup($result, $customerStartIndex, $index);
                }
                $currentCustomer = $row['customer'];
                $customerStartIndex = $index;
            }

            $result[] = $row;
        }

        // Handle item terakhir setelah loop selesai
        if (!empty($result)) {
            $this->finalizeCustomerGroup($result, $customerStartIndex, count($result));
            $this->finalizeAMGroup($result, $amStartIndex, count($result));
        }

        return $result;
    }

    /**
     * ✅ PRESERVED: Finalize AM group rowspan (unchanged)
     */
    private function finalizeAMGroup(&$result, $startIndex, $endIndex)
    {
        $rowspan = $endIndex - $startIndex;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $result[$i]['am_rowspan'] = ($i === $startIndex) ? $rowspan : 0;
        }
    }

    /**
     * ✅ PRESERVED: Finalize Customer group rowspan (unchanged)
     */
    private function finalizeCustomerGroup(&$result, $startIndex, $endIndex)
    {
        $rowspan = $endIndex - $startIndex;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $result[$i]['customer_rowspan'] = ($i === $startIndex) ? $rowspan : 0;
        }
    }

    // --- FUNGSI BARU: CALCULATE METRICS & INSIGHTS ---
    private function calculateProductMetrics($productData, $snapshot1, $snapshot2)
    {
        $totalOfferings = count($productData);
        $stagnantCount = 0;
        $visitedCount = 0;
        $winCount = 0;
        $completedCount = 0; // Submit SPH / Progress 100%
        
        $uniqueCustomers = [];
        $uniqueProducts = [];

        foreach ($productData as $row) {
            // 1. Stagnant
            if ($row['change_avg'] == 0) {
                $stagnantCount++;
            }

            // 2. Visited (Progress > 0)
            if ($row['progress_2'] > 0) {
                $visitedCount++;
            }

            // 3. Win (Result == 100)
            if ($row['result_2'] == 100) {
                $winCount++;
            }

            // 4. Completed / Submit SPH (Progress == 100 atau Result == 100)
            // Asumsi: Submit SPH adalah progress maksimal
            if ($row['progress_2'] == 100 || $row['result_2'] == 100) {
                $completedCount++;
            }

            if (!empty($row['customer'])) $uniqueCustomers[$row['customer']] = true;
            if (!empty($row['product'])) $uniqueProducts[$row['product']] = true;
        }

        // Kalkulasi Persentase
        $stagnantPct = $totalOfferings > 0 ? ($stagnantCount / $totalOfferings) * 100 : 0;
        $visitedPct = $totalOfferings > 0 ? ($visitedCount / $totalOfferings) * 100 : 0;
        $conversionRate = $totalOfferings > 0 ? ($winCount / $totalOfferings) * 100 : 0; // Win Rate
        
        // Data untuk 5 Kartu
        $metrics = [
            'prod_pulse' => [
                'value' => number_format($visitedPct, 1) . '%',
                'label' => 'Total Visited Rate',
                'trend_text' => 'Dari ' . number_format($totalOfferings) . ' total offerings',
                'trend' => 1, // Always positive context for visited
                // Sub-stats untuk Tall Card
                'total_offerings' => number_format($totalOfferings),
                'unique_cc' => number_format(count($uniqueCustomers)),
                'unique_products' => number_format(count($uniqueProducts)),
                'visited_count' => number_format($visitedCount)
            ],
            'stagnancy' => [
                'value' => number_format($stagnantPct, 1) . '%',
                'main_stat' => $stagnantCount . ' Stagnant Rows',
                'trend' => $stagnantPct > 50 ? -1 : 1 // High stagnancy is bad
            ],
            'conversion' => [
                'value' => number_format($conversionRate, 1) . '%',
                'main_stat' => 'Win Rate (Global)',
                'trend' => $conversionRate > 0 ? 1 : 0
            ],
            'win_offerings' => [
                'value' => $winCount . ' / ' . $totalOfferings,
                'main_stat' => 'Wins vs Offerings',
                'trend' => 1
            ],
            'completed' => [
                'value' => $completedCount,
                'main_stat' => 'Unique Completed (100%)',
                'trend' => 1
            ]
        ];

        // Generate HTML Insights
        $insights = $this->generateProductInsightsHTML($metrics, $snapshot2->divisi->kode ?? 'RLEGS');

        return [
            'metrics' => $metrics,
            'insights_data' => $insights
        ];
    }

    private function generateProductInsightsHTML($metrics, $divisiName)
    {
        return [
            'prod_pulse' => "
                <h4 class='text-lg font-bold text-gray-800 mb-2'>Productivity Pulse</h4>
                <p class='mb-3'>Metrik ini mengukur seberapa luas cakupan penetrasi produk terhadap customer yang ditargetkan.</p>
                <ul class='list-disc pl-5 space-y-1'>
                    <li>Total baris data unik (AM-CC-Product): <strong>{$metrics['prod_pulse']['total_offerings']}</strong>.</li>
                    <li>Jumlah yang sudah dikunjungi (Visited): <strong>{$metrics['prod_pulse']['visited_count']}</strong> row.</li>
                    <li>Mencakup <strong>{$metrics['prod_pulse']['unique_cc']}</strong> Corporate Customer unik.</li>
                </ul>
                <div class='mt-3 p-3 bg-blue-50 rounded border border-blue-100 text-sm text-blue-800'>
                    <strong>Insight:</strong> Semakin tinggi rate ini, semakin aktif AM melakukan penawaran produk ke customer.
                </div>
            ",
            'stagnancy' => "
                <h4 class='text-lg font-bold text-gray-800 mb-2'>Stagnancy Analysis</h4>
                <p class='mb-3'>Persentase data yang <strong>tidak mengalami perubahan sama sekali</strong> (Progress & Result tetap) dalam periode ini.</p>
                <div class='flex items-center gap-4 mb-3'>
                    <div class='text-3xl font-bold text-red-600'>{$metrics['stagnancy']['value']}</div>
                    <div class='text-sm text-gray-500'>Stagnant Rate</div>
                </div>
                <p>Terdapat <strong>{$metrics['stagnancy']['main_stat']}</strong> yang perlu di-follow up.</p>
                <div class='mt-3 p-3 bg-yellow-50 rounded border border-yellow-100 text-sm text-yellow-800'>
                    <strong>Rekomendasi:</strong> Cek tab 'Benchmarking' dan filter 'Avg Progress 0%' untuk menemukan item yang macet.
                </div>
            ",
            'conversion' => "
                <h4 class='text-lg font-bold text-gray-800 mb-2'>Conversion Rate</h4>
                <p class='mb-3'>Mengukur efektivitas penawaran produk hingga menjadi WON (Result 100%).</p>
                <div class='text-2xl font-bold text-green-600 mb-2'>{$metrics['conversion']['value']}</div>
                <p>Angka ini menunjukkan persentase keberhasilan dari total seluruh offering yang ada di database.</p>
            ",
            'win_offerings' => "
                <h4 class='text-lg font-bold text-gray-800 mb-2'>Win vs Offerings Ratio</h4>
                <p class='mb-3'>Perbandingan langsung antara jumlah Project WON dengan Total Project yang diajukan.</p>
                <ul class='list-disc pl-5 space-y-1'>
                    <li>Total Wins: <strong>" . explode(' / ', $metrics['win_offerings']['value'])[0] . "</strong></li>
                    <li>Total Offerings: <strong>" . explode(' / ', $metrics['win_offerings']['value'])[1] . "</strong></li>
                </ul>
                <div class='mt-3 p-3 bg-gray-50 rounded border border-gray-200 text-sm'>
                    Gunakan metrik ini untuk melihat volume keberhasilan secara absolut.
                </div>
            ",
            'completed' => "
                <h4 class='text-lg font-bold text-gray-800 mb-2'>Completed Progress</h4>
                <p class='mb-3'>Jumlah baris data unik yang telah mencapai tahap akhir (Submit SPH atau Progress 100% / Result 100%).</p>
                <div class='text-3xl font-bold text-purple-600 mb-2'>{$metrics['completed']['value']}</div>
                <p>Ini adalah indikator 'Pekerjaan Selesai' atau deal yang sudah closed.</p>
            "
        ];
    }

    /**
     * ✅ PRESERVED: Generate product leaderboard (unchanged)
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

            // Count wins: if result is 100%
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
            // Primary sort: wins descending
            if ($a['wins'] != $b['wins']) {
                return $b['wins'] <=> $a['wins'];
            }
            // Secondary sort: total_offerings ascending (lower offerings better if wins same)
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
     * ✅ PRESERVED: Generate improvement leaderboard (unchanged)
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