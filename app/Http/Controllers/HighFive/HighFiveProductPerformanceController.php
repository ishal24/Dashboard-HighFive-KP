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
        // 📄 CHANGED: Validation input
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
            // 📄 CHANGED: Get snapshots instead of datasets
            $snapshot1 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_1_id);
            $snapshot2 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_2_id);

            // Validate same divisi
            if ($snapshot1->divisi_id !== $snapshot2->divisi_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot harus dari divisi yang sama'
                ], 422);
            }

            // Validate both snapshots are successful
            if ($snapshot1->fetch_status !== 'success' || $snapshot2->fetch_status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya snapshot dengan status success yang bisa digunakan'
                ], 422);
            }

            // 📄 CHANGED: Parse JSON data from database instead of fetching from Google Sheets
            $data1 = $snapshot1->parsed_data;
            $data2 = $snapshot2->parsed_data;

            // ✅ PRESERVED: All calculation logic below remains the same

            // Process product performance data
            $productData = $this->calculateProductPerformance($data1, $data2);

            // Calculate statistics
            $stats = $this->calculateStatistics($productData, $snapshot1, $snapshot2);

            // Generate product leaderboard
            $productLeaderboard = $this->generateProductLeaderboard($productData);

            // Generate improvement leaderboard
            $improvementLeaderboard = $this->generateImprovementLeaderboard($productData);

            // 📄 CHANGED: Response structure with snapshot info
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
                    'statistics' => $stats,
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

    /**
     * ✅ PRESERVED: Calculate statistics (unchanged)
     *
     * 📄 MINOR CHANGE: Use $snapshot instead of $dataset for divisi name
     */
    private function calculateStatistics($productData, $snapshot1, $snapshot2)
    {
        $uniqueAMs = [];
        $uniqueCustomers = [];
        $visitedCustomers = [];
        $amProgress = [];
        $totalProducts = count($productData);

        foreach ($productData as $row) {
            $am = $row['am'];
            $customer = $row['customer'] ?? '__EMPTY__'; // Handle null customer
            $progress2 = $row['progress_2'];

            $uniqueAMs[$am] = true;

            // Only count non-empty customers
            if ($customer !== '__EMPTY__') {
                $uniqueCustomers[$customer] = true;

                if ($progress2 >= 25) {
                    $visitedCustomers[$customer] = true;
                }
            }

            if (!isset($amProgress[$am])) {
                $amProgress[$am] = false;
            }
            if ($progress2 > 0) {
                $amProgress[$am] = true;
            }
        }

        $amNoProgress = array_filter($amProgress, function($hasProgress) {
            return $hasProgress === false;
        });

        $divisiName = $snapshot2->divisi->kode ?? 'Unknown';

        return [
            'total_ams' => count($uniqueAMs),
            'total_customers' => count($uniqueCustomers),
            'total_products' => $totalProducts,
            'visited_customers' => count($visitedCustomers),
            'am_no_progress' => count($amNoProgress),
            'visited_text' => count($visitedCustomers) . '/' . count($uniqueCustomers) . " CC {$divisiName} telah divisit dan dipropose produk High Five",
            'no_progress_text' => count($amNoProgress) . ' AM belum berprogress',
            'visited_percentage' => count($uniqueCustomers) > 0
                ? round((count($visitedCustomers) / count($uniqueCustomers)) * 100, 2)
                : 0,
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
                ];
            }

            $productGrouped[$product]['total_progress'] += $row['progress_2'];
            $productGrouped[$product]['total_result'] += $row['result_2'];
            $productGrouped[$product]['count']++;
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
            ];
        }

        usort($leaderboard, function($a, $b) {
            return $b['avg_total'] <=> $a['avg_total'];
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