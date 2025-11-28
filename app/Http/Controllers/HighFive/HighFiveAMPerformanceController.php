<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\SpreadsheetSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HighFiveAMPerformanceController extends Controller
{
    /**
     * 📄 REVISED: Get AM Level Performance Benchmarking
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
     * ✅ NEW: Added witel average calculation for summary rows
     */
    public function getAMPerformance(Request $request)
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

            // Calculate AM-level averages per dataset
            $amAvg1 = $this->calculateAMAverage($data1);
            $amAvg2 = $this->calculateAMAverage($data2);

            // Merge datasets for comparison
            $mergedData = $this->mergeAMData($amAvg1, $amAvg2);

            // Calculate Witel-level analysis
            $witelAnalysis = $this->calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2);

            // Generate leaderboard by improvement
            $leaderboard = $this->generateLeaderboard($mergedData);

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
     * ✅ PRESERVED: Calculate AM average performance (unchanged)
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
                    'total_progress' => 0,
                    'total_result' => 0,
                    'count' => 0,
                ];
            }

            $amGrouped[$key]['total_progress'] += $row['progress_percentage'];
            $amGrouped[$key]['total_result'] += $row['result_percentage'];
            $amGrouped[$key]['count']++;
        }

        $amAverage = [];
        foreach ($amGrouped as $key => $data) {
            $avgProgress = $data['count'] > 0 ? round($data['total_progress'] / $data['count'], 2) : 0;
            $avgResult = $data['count'] > 0 ? round($data['total_result'] / $data['count'], 2) : 0;

            $amAverage[$key] = [
                'am' => $data['am'],
                'witel' => $data['witel'],
                'avg_progress' => $avgProgress,
                'avg_result' => $avgResult,
            ];
        }

        return $amAverage;
    }

    /**
     * ✅ PRESERVED: Merge AM data from two datasets (unchanged)
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
            ];
        }

        // Sort by Witel, then AM
        usort($merged, function($a, $b) {
            $witelCompare = strcmp($a['witel'], $b['witel']);
            if ($witelCompare !== 0) return $witelCompare;

            return strcmp($a['am'], $b['am']);
        });

        // Add rowspan info for witel grouping
        return $this->addWitelRowspan($merged);
    }

    /**
     * ✅ PRESERVED: Add rowspan for witel grouping (unchanged)
     */
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

    /**
     * ✅ PRESERVED: Finalize witel rowspan (unchanged)
     */
    private function finalizeWitelRowspan(&$result, $startIndex, $endIndex)
    {
        $rowspan = $endIndex - $startIndex;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $result[$i]['witel_rowspan'] = ($i === $startIndex) ? $rowspan : 0;
        }
    }

    /**
     * ✅ REVISED: Calculate Witel analysis with narrative
     * 📝 UPDATED: Enhanced narrative with bold formatting hints
     */
    private function calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2)
    {
        // Group by Witel
        $witelData = [];
        foreach ($mergedData as $row) {
            $witel = $row['witel'];
            if (!isset($witelData[$witel])) {
                $witelData[$witel] = [
                    'witel' => $witel,
                    'dataset_1' => [
                        'total_progress' => 0,
                        'total_result' => 0,
                        'count' => 0,
                    ],
                    'dataset_2' => [
                        'total_progress' => 0,
                        'total_result' => 0,
                        'count' => 0,
                    ],
                ];
            }

            $witelData[$witel]['dataset_1']['total_progress'] += $row['progress_1'];
            $witelData[$witel]['dataset_1']['total_result'] += $row['result_1'];
            $witelData[$witel]['dataset_1']['count']++;

            $witelData[$witel]['dataset_2']['total_progress'] += $row['progress_2'];
            $witelData[$witel]['dataset_2']['total_result'] += $row['result_2'];
            $witelData[$witel]['dataset_2']['count']++;
        }

        // Calculate averages
        $witelAverages1 = [];
        $witelAverages2 = [];

        foreach ($witelData as $witel => $data) {
            $avg1 = [
                'witel' => $witel,
                'avg_progress' => $data['dataset_1']['count'] > 0
                    ? round($data['dataset_1']['total_progress'] / $data['dataset_1']['count'], 2)
                    : 0,
                'avg_result' => $data['dataset_1']['count'] > 0
                    ? round($data['dataset_1']['total_result'] / $data['dataset_1']['count'], 2)
                    : 0,
            ];

            $avg2 = [
                'witel' => $witel,
                'avg_progress' => $data['dataset_2']['count'] > 0
                    ? round($data['dataset_2']['total_progress'] / $data['dataset_2']['count'], 2)
                    : 0,
                'avg_result' => $data['dataset_2']['count'] > 0
                    ? round($data['dataset_2']['total_result'] / $data['dataset_2']['count'], 2)
                    : 0,
            ];

            $witelAverages1[] = $avg1;
            $witelAverages2[] = $avg2;
        }

        // Find most & least progress
        usort($witelAverages1, function($a, $b) {
            return $b['avg_progress'] <=> $a['avg_progress'];
        });

        usort($witelAverages2, function($a, $b) {
            return $b['avg_progress'] <=> $a['avg_progress'];
        });

        $mostProgress1 = $witelAverages1[0] ?? null;
        $leastProgress1 = end($witelAverages1) ?: null;
        $mostProgress2 = $witelAverages2[0] ?? null;
        $leastProgress2 = end($witelAverages2) ?: null;

        // Generate narrative paragraphs
        $date1 = $snapshot1->snapshot_date->format('d M Y');
        $date2 = $snapshot2->snapshot_date->format('d M Y');

        $narrative1 = $mostProgress1 && $leastProgress1
            ? sprintf(
                "Pada minggu %s, Witel %s menunjukkan progress tertinggi dengan rata-rata %.2f%%, sementara Witel %s memiliki progress terendah dengan rata-rata %.2f%%.",
                $date1,
                $mostProgress1['witel'],
                $mostProgress1['avg_progress'],
                $leastProgress1['witel'],
                $leastProgress1['avg_progress']
            )
            : "Data tidak tersedia untuk analisis periode lama.";

        $narrative2 = $mostProgress2 && $leastProgress2
            ? sprintf(
                "Pada minggu %s, Witel %s menunjukkan progress tertinggi dengan rata-rata %.2f%%, sementara Witel %s memiliki progress terendah dengan rata-rata %.2f%%.",
                $date2,
                $mostProgress2['witel'],
                $mostProgress2['avg_progress'],
                $leastProgress2['witel'],
                $leastProgress2['avg_progress']
            )
            : "Data tidak tersedia untuk analisis periode baru.";

        // Generate conclusion
        $improvement = $mostProgress2 && $mostProgress1
            ? ($mostProgress2['avg_progress'] - $mostProgress1['avg_progress'])
            : 0;

        $conclusion = $improvement > 0
            ? sprintf(
                "Terjadi peningkatan performa secara keseluruhan dengan Witel terbaik meningkat %.2f poin persentase dari periode sebelumnya.",
                $improvement
            )
            : "Performa relatif stabil dibandingkan periode sebelumnya.";

        return [
            'cards' => [
                'dataset_1' => [
                    'most_progress' => $mostProgress1,
                    'least_progress' => $leastProgress1,
                ],
                'dataset_2' => [
                    'most_progress' => $mostProgress2,
                    'least_progress' => $leastProgress2,
                ],
            ],
            'narrative' => [
                'dataset_1_paragraph' => $narrative1,
                'dataset_2_paragraph' => $narrative2,
                'conclusion_paragraph' => $conclusion,
            ],
        ];
    }

    /**
     * ✅ PRESERVED: Generate leaderboard (unchanged)
     */
    private function generateLeaderboard($mergedData)
    {
        $leaderboard = $mergedData;
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