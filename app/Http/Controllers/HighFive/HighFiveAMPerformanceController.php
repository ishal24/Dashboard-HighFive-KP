<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\SpreadsheetSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HighFiveAMPerformanceController extends Controller
{
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

            if ($snapshot1->divisi_id !== $snapshot2->divisi_id) {
                return response()->json(['success' => false, 'message' => 'Snapshot harus dari divisi yang sama'], 422);
            }

            if ($snapshot1->fetch_status !== 'success' || $snapshot2->fetch_status !== 'success') {
                return response()->json(['success' => false, 'message' => 'Hanya snapshot dengan status success yang bisa digunakan'], 422);
            }

            $data1 = $snapshot1->parsed_data;
            $data2 = $snapshot2->parsed_data;

            // 1. Hitung Average & Stats untuk masing-masing dataset
            $amAvg1 = $this->calculateAMAverage($data1);
            $amAvg2 = $this->calculateAMAverage($data2);

            // 2. Gabungkan data
            $mergedData = $this->mergeAMData($amAvg1, $amAvg2);

            // 3. Analisis Witel
            $witelAnalysis = $this->calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2);

            // 4. Leaderboard
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
     * ✅ REVISED: Calculate AM Average AND Stats (Offering, Win, Lose, CC)
     * Logic average dipertahankan, logic stats ditambahkan.
     */
    private function calculateAMAverage($data)
    {
        $amGrouped = [];

        foreach ($data as $row) {
            $am = trim($row['am']);
            $witel = trim($row['witel']);

            if (empty($am) || empty($witel)) {
                continue;
            }

            $key = $am . '|' . $witel;

            if (!isset($amGrouped[$key])) {
                $amGrouped[$key] = [
                    'am' => $am,
                    'witel' => $witel,
                    // Akumulator untuk Average (Logic Lama)
                    'total_progress' => 0,
                    'total_result' => 0,
                    'count' => 0,
                    // Akumulator untuk Stats (Logic Baru)
                    'stats' => [
                        'offerings' => 0,
                        'win' => 0,
                        'lose' => 0,
                        'cust_list' => [] // Temp array untuk unique count
                    ]
                ];
            }

            // --- 1. Logic Average (TIDAK DIUBAH) ---
            $amGrouped[$key]['total_progress'] += $row['progress_percentage'];
            $amGrouped[$key]['total_result'] += $row['result_percentage'];
            $amGrouped[$key]['count']++;

            // --- 2. Logic Stats (DITAMBAHKAN) ---
            $stats = &$amGrouped[$key]['stats'];
            
            // Offering = Jumlah baris data
            $stats['offerings']++;

            // Unique Customer
            if (!empty($row['customer_name'])) {
                $stats['cust_list'][$row['customer_name']] = true;
            }

            // Win/Lose Calculation
            // Gunakan kolom 'result' (teks) dan 'result_percentage' (angka)
            $resText = strtolower($row['result'] ?? '');
            $resVal = $row['result_percentage'] ?? 0;

            if (strpos($resText, 'win') !== false || $resVal == 100) {
                $stats['win']++;
            } elseif (strpos($resText, 'lose') !== false) {
                $stats['lose']++;
            }
        }

        // Finalisasi Data
        $amAverage = [];
        foreach ($amGrouped as $key => $data) {
            // Rumus Average Original
            $avgProgress = $data['count'] > 0 ? round($data['total_progress'] / $data['count'], 2) : 0;
            $avgResult = $data['count'] > 0 ? round($data['total_result'] / $data['count'], 2) : 0;

            // Finalisasi Stats
            $finalStats = [
                'offerings' => $data['stats']['offerings'],
                'total_customers' => count($data['stats']['cust_list']),
                'win' => $data['stats']['win'],
                'lose' => $data['stats']['lose']
            ];

            $amAverage[$key] = [
                'am' => $data['am'],
                'witel' => $data['witel'],
                'avg_progress' => $avgProgress,
                'avg_result' => $avgResult,
                'stats' => $finalStats // Masukkan stats ke array hasil
            ];
        }

        return $amAverage;
    }

    /**
     * ✅ REVISED: Merge Logic to include Stats
     */
    private function mergeAMData($amAvg1, $amAvg2)
    {
        $merged = [];
        $allKeys = array_unique(array_merge(
            array_keys($amAvg1),
            array_keys($amAvg2)
        ));

        foreach ($allKeys as $key) {
            $am1 = $amAvg1[$key] ?? null;
            $am2 = $amAvg2[$key] ?? null;

            $progress1 = $am1['avg_progress'] ?? 0;
            $progress2 = $am2['avg_progress'] ?? 0;
            $result1 = $am1['avg_result'] ?? 0;
            $result2 = $am2['avg_result'] ?? 0;

            // Ambil stats dari Snapshot 2 (Terbaru), jika tidak ada ambil Snapshot 1
            // Jika AM tidak ada di keduanya (tidak mungkin terjadi di loop ini), set 0
            $statsSource = $am2 ?? $am1;
            $stats = $statsSource['stats'] ?? [
                'offerings' => 0, 'total_customers' => 0, 'win' => 0, 'lose' => 0
            ];

            $merged[$key] = [
                'am' => $am2['am'] ?? $am1['am'],
                'witel' => $am2['witel'] ?? $am1['witel'],
                'progress_1' => $progress1,
                'progress_2' => $progress2,
                'result_1' => $result1,
                'result_2' => $result2,
                'change_progress' => $progress2 - $progress1,
                'change_result' => $result2 - $result1,
                'change_avg' => round((($progress2 - $progress1) + ($result2 - $result1)) / 2, 2),
                'stats' => $stats // Pass stats ke frontend
            ];
        }

        // Sort by Witel, then AM
        usort($merged, function($a, $b) {
            $witelCompare = strcmp($a['witel'], $b['witel']);
            if ($witelCompare !== 0) return $witelCompare;
            return strcmp($a['am'], $b['am']);
        });

        return $this->addWitelRowspan($merged);
    }

    // --- FUNGSI BAWAH INI TIDAK ADA PERUBAHAN LOGIKA, HANYA COPY-PASTE AGAR LENGKAP ---

    private function addWitelRowspan($data)
    {
        $result = [];
        $currentWitel = null;
        $witelStartIndex = 0;

        foreach ($data as $index => $row) {
            if ($row['witel'] !== $currentWitel) {
                if ($currentWitel !== null) {
                    $this->finalizeWitelRowspan($result, $witelStartIndex, $index);
                }
                $currentWitel = $row['witel'];
                $witelStartIndex = $index;
            }
            $result[] = $row;
        }

        if (!empty($result)) {
            $this->finalizeWitelRowspan($result, $witelStartIndex, count($result));
        }

        return $result;
    }

    private function finalizeWitelRowspan(&$result, $startIndex, $endIndex)
    {
        $rowspan = $endIndex - $startIndex;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $result[$i]['witel_rowspan'] = ($i === $startIndex) ? $rowspan : 0;
        }
    }

    private function calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2)
    {
        $witelData = [];
        foreach ($mergedData as $row) {
            $witel = $row['witel'];
            if (!isset($witelData[$witel])) {
                $witelData[$witel] = ['witel' => $witel, 'dataset_1' => ['total_progress' => 0,'total_result' => 0,'count' => 0],'dataset_2' => ['total_progress' => 0,'total_result' => 0,'count' => 0]];
            }
            $witelData[$witel]['dataset_1']['total_progress'] += $row['progress_1'];
            $witelData[$witel]['dataset_1']['total_result'] += $row['result_1'];
            $witelData[$witel]['dataset_1']['count']++;
            $witelData[$witel]['dataset_2']['total_progress'] += $row['progress_2'];
            $witelData[$witel]['dataset_2']['total_result'] += $row['result_2'];
            $witelData[$witel]['dataset_2']['count']++;
        }

        $witelAverages1 = [];
        $witelAverages2 = [];

        foreach ($witelData as $witel => $data) {
            $witelAverages1[] = [
                'witel' => $witel,
                'avg_progress' => $data['dataset_1']['count'] > 0 ? round($data['dataset_1']['total_progress'] / $data['dataset_1']['count'], 2) : 0,
            ];
            $witelAverages2[] = [
                'witel' => $witel,
                'avg_progress' => $data['dataset_2']['count'] > 0 ? round($data['dataset_2']['total_progress'] / $data['dataset_2']['count'], 2) : 0,
            ];
        }

        usort($witelAverages1, fn($a, $b) => $b['avg_progress'] <=> $a['avg_progress']);
        usort($witelAverages2, fn($a, $b) => $b['avg_progress'] <=> $a['avg_progress']);

        $most1 = $witelAverages1[0] ?? null;
        $least1 = end($witelAverages1) ?: null;
        $most2 = $witelAverages2[0] ?? null;
        $least2 = end($witelAverages2) ?: null;

        return [
            'cards' => [
                'dataset_1' => ['most_progress' => $most1, 'least_progress' => $least1],
                'dataset_2' => ['most_progress' => $most2, 'least_progress' => $least2],
            ],
            'narrative' => [
                'dataset_1_paragraph' => "Analisis tersedia.",
                'dataset_2_paragraph' => "Analisis tersedia.",
                'conclusion_paragraph' => "Data berhasil diolah.",
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