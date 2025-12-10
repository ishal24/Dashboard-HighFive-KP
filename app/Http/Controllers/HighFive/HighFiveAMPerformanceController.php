<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\SpreadsheetSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HighFiveAMPerformanceController extends Controller
{
    /**
     * Get AM Level Performance Benchmarking
     */
    public function getAMPerformance(Request $request)
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

            // Validate same divisi
            if ($snapshot1->divisi_id !== $snapshot2->divisi_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot harus dari divisi yang sama'
                ], 422);
            }

            // Validate status
            if ($snapshot1->fetch_status !== 'success' || $snapshot2->fetch_status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya snapshot dengan status success yang bisa digunakan'
                ], 422);
            }

            $data1 = $snapshot1->parsed_data;
            $data2 = $snapshot2->parsed_data;

            // 🔥 PERBAIKAN UTAMA:
            // Panggil calculateAMPerformance yang menggunakan logika statistik baru
            // Bukan lagi memanggil calculateAMAverage terpisah
            $mergedData = $this->calculateAMPerformance($data1, $data2);

            // Calculate Witel-level analysis
            $witelAnalysis = $this->calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2);

            // Generate leaderboard
            $leaderboard = $this->generateLeaderboard($mergedData);

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
                    'witel_analysis' => $witelAnalysis,
                    'benchmarking' => $mergedData,
                    'leaderboard' => $leaderboard,
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
     * 🔥 CORE LOGIC: Merge & Calculate Stats
     * Menggabungkan proses grouping dan merging menjadi satu alur
     */
    private function calculateAMPerformance($data1, $data2)
    {
        // 1. Grouping data menggunakan logika BARU yang menghitung stats
        $grouped1 = $this->groupByWitelAM($data1);
        $grouped2 = $this->groupByWitelAM($data2);

        $merged = [];
        $allKeys = array_unique(array_merge(
            array_keys($grouped1),
            array_keys($grouped2)
        ));

        foreach ($allKeys as $key) {
            $item1 = $grouped1[$key] ?? null;
            $item2 = $grouped2[$key] ?? null;

            // Ambil data statistik dari Snapshot TERBARU (Data 2)
            $stats = $item2['stats'] ?? [
                'offerings' => 0,
                'total_customers' => 0,
                'total_products' => 0,
                'win' => 0,
                'lose' => 0
            ];

            $progress1 = $item1['progress_percentage'] ?? 0;
            $progress2 = $item2['progress_percentage'] ?? 0;
            $result1 = $item1['result_percentage'] ?? 0;
            $result2 = $item2['result_percentage'] ?? 0;

            $merged[$key] = [
                'witel' => $item2['witel'] ?? $item1['witel'],
                'am' => $item2['am'] ?? $item1['am'],
                'progress_1' => $progress1,
                'progress_2' => $progress2,
                'result_1' => $result1,
                'result_2' => $result2,
                'change_progress' => $progress2 - $progress1,
                'change_result' => $result2 - $result1,
                'change_avg' => round((($progress2 - $progress1) + ($result2 - $result1)) / 2, 2),
                
                // 🔥 Stats dimasukkan ke hasil akhir agar terbaca di frontend
                'stats' => $stats 
            ];
        }

        // Sorting: Witel ASC, lalu AM ASC
        usort($merged, function($a, $b) {
            $witelCompare = strcmp($a['witel'], $b['witel']);
            if ($witelCompare !== 0) return $witelCompare;
            return strcmp($a['am'], $b['am']);
        });

        return $this->addRowspanInfo($merged);
    }

    /**
     * 🔥 GROUPING & STATS CALCULATION
     * Menghitung Win, Lose, Offering, dan Unique Customer per AM
     */
    private function groupByWitelAM($data)
    {
        $grouped = [];

        foreach ($data as $row) {
            $am = trim($row['am']);
            $witel = trim($row['witel']);

            if (empty($am)) continue;

            $key = $witel . '|' . $am;

            // Inisialisasi jika belum ada
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'witel' => $witel,
                    'am' => $am,
                    'progress_percentage' => 0,
                    'result_percentage' => 0,
                    // Init Stats Container
                    'stats' => [
                        'offerings' => 0,
                        'customers_list' => [], // Array sementara
                        'products_list' => [],
                        'win' => 0,
                        'lose' => 0
                    ]
                ];
            }

            // Logic "Best Row" (Untuk kolom % Progress agar ambil yang tertinggi)
            if ($row['progress_percentage'] > $grouped[$key]['progress_percentage'] || 
               ($row['progress_percentage'] == $grouped[$key]['progress_percentage'] && $row['result_percentage'] > $grouped[$key]['result_percentage'])) {
                $grouped[$key]['progress_percentage'] = $row['progress_percentage'];
                $grouped[$key]['result_percentage'] = $row['result_percentage'];
            }

            // Hitung Statistik
            $stats = &$grouped[$key]['stats'];
            
            // 1. Count Offerings (Total Baris)
            $stats['offerings']++;
            
            // 2. Collect Unique Names
            if (!empty($row['customer_name'])) {
                $stats['customers_list'][$row['customer_name']] = true;
            }
            if (!empty($row['product'])) {
                $stats['products_list'][$row['product']] = true;
            }

            // 3. Hitung Win/Lose
            $resultStatus = strtolower($row['result'] ?? ''); 
            $resultPercent = $row['result_percentage'] ?? 0;

            // Kriteria WIN: Ada kata "win" ATAU persentase 100%
            if (strpos($resultStatus, 'win') !== false || $resultPercent == 100) {
                $stats['win']++;
            } 
            // Kriteria LOSE: Ada kata "lose"
            elseif (strpos($resultStatus, 'lose') !== false) {
                $stats['lose']++;
            }
        }

        // Finalisasi count unique
        foreach ($grouped as &$item) {
            $item['stats']['total_customers'] = count($item['stats']['customers_list']);
            $item['stats']['total_products'] = count($item['stats']['products_list']);
            
            unset($item['stats']['customers_list']);
            unset($item['stats']['products_list']);
        }

        return $grouped;
    }

    private function addRowspanInfo($data)
    {
        $result = [];
        $currentWitel = null;
        $witelStartIndex = 0;

        foreach ($data as $index => $row) {
            if ($row['witel'] !== $currentWitel) {
                if ($currentWitel !== null) {
                    $rowspan = $index - $witelStartIndex;
                    $result[$witelStartIndex]['witel_rowspan'] = $rowspan;
                }
                $currentWitel = $row['witel'];
                $witelStartIndex = $index;
            }
            $row['witel_rowspan'] = 0; // Default
            $result[] = $row;
        }

        if (!empty($result)) {
            $rowspan = count($result) - $witelStartIndex;
            $result[$witelStartIndex]['witel_rowspan'] = $rowspan;
        }

        return $result;
    }

    private function calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2)
    {
        $witelData = [];
        foreach ($mergedData as $row) {
            $witel = $row['witel'];
            if (!isset($witelData[$witel])) {
                $witelData[$witel] = ['witel' => $witel, 'p1' => [], 'p2' => []];
            }
            if ($row['progress_1'] > 0 || $row['result_1'] > 0) $witelData[$witel]['p1'][] = $row['progress_1'];
            if ($row['progress_2'] > 0 || $row['result_2'] > 0) $witelData[$witel]['p2'][] = $row['progress_2'];
        }

        $avgs1 = [];
        $avgs2 = [];

        foreach ($witelData as $w => $d) {
            $avg1 = count($d['p1']) > 0 ? array_sum($d['p1']) / count($d['p1']) : 0;
            $avg2 = count($d['p2']) > 0 ? array_sum($d['p2']) / count($d['p2']) : 0;
            
            $avgs1[] = ['witel' => $w, 'avg_progress' => $avg1];
            $avgs2[] = ['witel' => $w, 'avg_progress' => $avg2];
        }

        usort($avgs1, fn($a, $b) => $b['avg_progress'] <=> $a['avg_progress']);
        usort($avgs2, fn($a, $b) => $b['avg_progress'] <=> $a['avg_progress']);

        $most1 = $avgs1[0] ?? null;
        $least1 = end($avgs1) ?: null;
        $most2 = $avgs2[0] ?? null;
        $least2 = end($avgs2) ?: null;

        $n1 = $most1 ? "Minggu lalu, Witel {$most1['witel']} memimpin dengan rata-rata progress ".number_format($most1['avg_progress'],1)."%." : "Data tidak tersedia.";
        $n2 = $most2 ? "Minggu ini, Witel {$most2['witel']} memimpin dengan rata-rata progress ".number_format($most2['avg_progress'],1)."%." : "Data tidak tersedia.";
        
        return [
            'cards' => [
                'dataset_1' => ['most_progress' => $most1, 'least_progress' => $least1],
                'dataset_2' => ['most_progress' => $most2, 'least_progress' => $least2],
            ],
            'narrative' => [
                'dataset_1_paragraph' => $n1,
                'dataset_2_paragraph' => $n2,
                'conclusion_paragraph' => "Analisis witel selesai.",
            ],
        ];
    }

    private function generateLeaderboard($mergedData)
    {
        $leaderboard = $mergedData;
        usort($leaderboard, fn($a, $b) => $b['change_avg'] <=> $a['change_avg']);
        
        $top10 = array_slice($leaderboard, 0, 10);
        foreach ($top10 as $i => $r) $top10[$i]['rank'] = $i + 1;
        
        return $top10;
    }
}