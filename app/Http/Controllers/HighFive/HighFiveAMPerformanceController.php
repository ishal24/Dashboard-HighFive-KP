<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\SpreadsheetSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

            // 2. Gabungkan data (Sorted by Improvement)
            $mergedData = $this->mergeAMData($amAvg1, $amAvg2);

            // 3. Analisis Witel (Square Metrics & Insights)
            $witelAnalysis = $this->calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2);

            // 4. Leaderboard
            $leaderboard = $this->generateLeaderboard($mergedData);

            // 5. ➕ NEW: Perhitungan Statistik Status Progres untuk Chart
            $statusStats = $this->calculateProgressStatusStats($data1, $data2);

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
                    'status_stats' => $statusStats, // Digunakan oleh Chart.js di frontend
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
     * ➕ NEW METHOD: Menghitung kuantitas data berdasarkan fase progres per Witel
     */
    private function calculateProgressStatusStats($data1, $data2)
    {
        $stats = ['ss1' => [], 'ss2' => []];

        $categorize = function($row) {
            $p = floatval($row['progress_percentage'] ?? 0);
            if ($p >= 100) return 'sph';
            if ($p >= 75)  return 'presentasi';
            if ($p >= 50)  return 'mytens';
            if ($p > 0)    return 'visit';
            return 'idle';
        };

        foreach (['ss1' => $data1, 'ss2' => $data2] as $key => $dataset) {
            foreach ($dataset as $row) {
                $witel = trim($row['witel'] ?? 'Unknown');
                $cat = $categorize($row);
                
                if ($cat === 'idle') continue;

                if (!isset($stats[$key][$witel])) {
                    $stats[$key][$witel] = ['visit' => 0, 'mytens' => 0, 'presentasi' => 0, 'sph' => 0];
                }
                if (!isset($stats[$key]['Total'])) {
                    $stats[$key]['Total'] = ['visit' => 0, 'mytens' => 0, 'presentasi' => 0, 'sph' => 0];
                }

                $stats[$key][$witel][$cat]++;
                $stats[$key]['Total'][$cat]++;
            }
        }
        return $stats;
    }

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
                    'stats' => [
                        'offerings' => 0,
                        'win' => 0,
                        'lose' => 0,
                        'cust_list' => [],
                        'visited_customers' => [],
                        'total_nilai_win' => 0 // ✅ NEW: Track total NILAI from wins
                    ]
                ];
            }

            $amGrouped[$key]['total_progress'] += $row['progress_percentage'];
            $amGrouped[$key]['total_result'] += $row['result_percentage'];
            $amGrouped[$key]['count']++;

            $stats = &$amGrouped[$key]['stats'];
            $stats['offerings']++;

            if (!empty($row['customer_name'])) {
                $custName = $row['customer_name'];
                $stats['cust_list'][$custName] = true;
                
                if (($row['progress_percentage'] ?? 0) > 0) {
                    $stats['visited_customers'][$custName] = true;
                }
            }

            $resText = strtolower($row['result'] ?? '');
            $resVal = $row['result_percentage'] ?? 0;

            if (strpos($resText, 'win') !== false || $resVal == 100) {
                $stats['win']++;
                $stats['total_nilai_win'] += floatval($row['nilai'] ?? 0); // ✅ Sum NILAI for wins
            } elseif (strpos($resText, 'lose') !== false) {
                $stats['lose']++;
            }
        }

        $amAverage = [];
        foreach ($amGrouped as $key => $data) {
            // ✅ FIX: No rounding here - keep full precision for accurate calculations
            $avgProgress = $data['count'] > 0 ? $data['total_progress'] / $data['count'] : 0;
            $avgResult = $data['count'] > 0 ? $data['total_result'] / $data['count'] : 0;

            $finalStats = [
                'offerings' => $data['stats']['offerings'],
                'total_customers' => count($data['stats']['cust_list']),
                'visited' => count($data['stats']['visited_customers']),
                'win' => $data['stats']['win'],
                'lose' => $data['stats']['lose'],
                'total_nilai_win' => $data['stats']['total_nilai_win'] // ✅ Pass NILAI to final stats
            ];

            $amAverage[$key] = [
                'am' => $data['am'],
                'witel' => $data['witel'],
                'avg_progress' => $avgProgress,
                'avg_result' => $avgResult,
                'stats' => $finalStats
            ];
        }

        return $amAverage;
    }

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

            $statsSource = $am2 ?? $am1;
            $stats = $statsSource['stats'] ?? [
                'offerings' => 0, 'total_customers' => 0, 'visited' => 0, 'win' => 0, 'lose' => 0, 'total_nilai_win' => 0
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
                // ✅ FIX: No rounding - keep full precision
                'change_avg' => (($progress2 - $progress1) + ($result2 - $result1)) / 2,
                'stats' => $stats
            ];
        }

        usort($merged, function($a, $b) {
            $witelCompare = strcmp($a['witel'], $b['witel']);
            if ($witelCompare !== 0) return $witelCompare;
            
            if ($a['change_avg'] == $b['change_avg']) return 0;
            return ($a['change_avg'] > $b['change_avg']) ? -1 : 1;
        });

        return $this->addWitelRowspan($merged);
    }

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
        $stats = [
            'total_ams' => 0,
            'sum_change_p' => 0, 'sum_change_r' => 0, 'sum_change_avg' => 0,
            'active_ams' => 0,
            'total_offerings' => 0,
            'total_customers' => 0,
            'total_visited' => 0,
            'total_wins' => 0,
            'total_loses' => 0
        ];

        $witelStats = [];
        $topAM = null;      
        $topWinAM = null;   

        foreach ($mergedData as $row) {
            $stats['total_ams']++;
            $stats['sum_change_p'] += $row['change_progress'];
            $stats['sum_change_r'] += $row['change_result'];
            $stats['sum_change_avg'] += $row['change_avg'];
            
            if ($row['progress_2'] > 0) $stats['active_ams']++;

            $amStats = $row['stats'] ?? [];
            $stats['total_offerings'] += $amStats['offerings'] ?? 0;
            $stats['total_customers'] += $amStats['total_customers'] ?? 0;
            $stats['total_visited'] += $amStats['visited'] ?? 0;
            $stats['total_wins'] += $amStats['win'] ?? 0;
            $stats['total_loses'] += $amStats['lose'] ?? 0;

            if (!$topAM || $row['change_avg'] > $topAM['change_avg']) {
                $topAM = $row;
            }

            $amWin = $amStats['win'] ?? 0;
            $currentTopWin = $topWinAM['stats']['win'] ?? 0;
            if (!$topWinAM || $amWin > $currentTopWin || ($amWin == $currentTopWin && $row['result_2'] > $topWinAM['result_2'])) {
                $topWinAM = $row;
            }

            $witel = $row['witel'];
            if (!isset($witelStats[$witel])) {
                $witelStats[$witel] = [
                    'name' => $witel, 
                    'sum_change_p' => 0, 
                    'sum_change_r' => 0, 
                    'sum_change_avg' => 0, 
                    'count' => 0,
                    'top_am' => $row,
                    'least_am' => $row
                ];
            }
            $witelStats[$witel]['sum_change_p'] += $row['change_progress'];
            $witelStats[$witel]['sum_change_r'] += $row['change_result'];
            $witelStats[$witel]['sum_change_avg'] += $row['change_avg'];
            $witelStats[$witel]['count']++;

            if ($row['change_avg'] > $witelStats[$witel]['top_am']['change_avg']) $witelStats[$witel]['top_am'] = $row;
            if ($row['change_avg'] < $witelStats[$witel]['least_am']['change_avg']) $witelStats[$witel]['least_am'] = $row;
        }

        $total = $stats['total_ams'] ?: 1;
        $TREG3Imp = $stats['sum_change_avg'] / $total;
        $TREG3ImpP = $stats['sum_change_p'] / $total;
        $TREG3ImpR = $stats['sum_change_r'] / $total;

        $witelFinal = [];
        foreach ($witelStats as $w => $d) {
            $wCount = $d['count'] ?: 1;
            $witelFinal[] = [
                'name' => $w,
                'avg_imp' => $d['sum_change_avg'] / $wCount,
                'avg_p' => $d['sum_change_p'] / $wCount,
                'avg_r' => $d['sum_change_r'] / $wCount,
                'top_am' => $d['top_am'],
                'least_am' => $d['least_am']
            ];
        }
        usort($witelFinal, fn($a, $b) => $b['avg_imp'] <=> $a['avg_imp']);
        $mostWitel = $witelFinal[0];
        $leastWitel = end($witelFinal);

        $fSign = fn($v) => ($v > 0 ? '+' : '') . number_format($v, 1) . '%';
        $fSign2 = fn($v) => ($v > 0 ? '+' : '') . number_format($v, 2) . '%';

        $metrics = [
            'TREG3' => [
                'label' => 'TREG3 Pulse',
                'value' => $fSign($TREG3Imp),
                'sub_label' => 'Avg Improvement',
                'trend' => $TREG3Imp,
                'trend_text' => 'Seluruh AM',
                'color' => $TREG3Imp >= 0 ? 'success' : 'danger',
                'offerings' => number_format($stats['total_offerings']),
                'total_customers' => number_format($stats['total_customers']),
                'visited' => number_format($stats['total_visited']),
                'wins' => number_format($stats['total_wins']),
                'loses' => number_format($stats['total_loses'])
            ],
            'most_witel' => [
                'label' => 'Witel Champion',
                'value' => $mostWitel['name'],
                'sub_label' => 'Highest Improvement',
                'main_stat' => $fSign($mostWitel['avg_imp']) . ' Avg Improvement',
            ],
            'least_witel' => [
                'label' => 'Focus Area',
                'value' => $leastWitel['name'],
                'sub_label' => 'Lowest Improvement',
                'main_stat' => $fSign($leastWitel['avg_imp']) . ' Avg Improvement',
            ],
            'top_am' => [
                'label' => 'MVP Improver',
                'value' => $topAM['am'] ?? '-',
                'sub_label' => $topAM['witel'] ?? '-',
                'main_stat' => $fSign($topAM['change_avg']) . ' Improvement',
            ],
            'am_most_win' => [
                'label' => 'Top Sales AM',
                'value' => $topWinAM['am'] ?? '-',
                'sub_label' => $topWinAM['witel'] ?? '-',
                'main_stat' => ($topWinAM['stats']['win'] ?? 0) . ' Wins',
            ]
        ];

        // --- KONTEN INSIGHTS MODAL (BAWAAN LAMA) ---
        $insightTREG3 = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>TREG3 Overview</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>IMPROVEMENT Progress</span><span class='insight-metric-value'>{$fSign2($TREG3ImpP)}</span><span class='insight-metric-sub'> </span></div>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>IMPROVEMENT Result</span><span class='insight-metric-value'>{$fSign2($TREG3ImpR)}</span><span class='insight-metric-sub'> </span></div>
                <div class='insight-metric-item'><span class='insight-metric-label'>Participation</span><span class='insight-metric-value'>" . number_format(($stats['active_ams'] / $total) * 100, 0) . "%</span><span class='insight-metric-sub'>AM Berprogres</span></div>
            </div>
            <div class='insight-narrative-box blue-theme'>
                <div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Angka rata-rata improvement TREG3 sebesar <strong>{$fSign($TREG3Imp)}</strong> diperoleh dari agregasi peningkatan progres (<strong>{$fSign2($TREG3ImpP)}</strong>) dan peningkatan result (<strong>{$fSign2($TREG3ImpR)}</strong>). 
                    Data ini mencerminkan dinamika <strong>" . number_format($stats['total_offerings']) . "</strong> offerings yang sedang berjalan di seluruh wilayah.
                </p>
            </div>";

        $gapMost = $mostWitel['avg_imp'] - $TREG3Imp;
        $insightMost = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Witel {$mostWitel['name']}</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>IMPROVEMENT Progress</span><span class='insight-metric-value'>{$fSign2($mostWitel['avg_p'])}</span><span class='insight-metric-sub'> </span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>IMPROVEMENT Result</span><span class='insight-metric-value'>{$fSign2($mostWitel['avg_r'])}</span><span class='insight-metric-sub'> </span></div>
                <div class='insight-metric-item'><span class='insight-metric-label'>Top Improvement AM</span><span class='insight-metric-value' style='font-size:14px;'>" . $mostWitel['top_am']['am'] . "</span></div>
            </div>
            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-chart-line'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Witel {$mostWitel['name']} mencatatkan improvement tertinggi sebesar <strong>{$fSign($mostWitel['avg_imp'])}</strong>, yang didapatkan dari rata-rata peningkatan progres (<strong>{$fSign2($mostWitel['avg_p'])}</strong>) dan result (<strong>{$fSign2($mostWitel['avg_r'])}</strong>). 
                    Wilayah ini memiliki gap positif sebesar <strong>" . $fSign($gapMost) . "</strong> dari rata-rata seluruh witel, dipicu oleh akselerasi AM <strong>" . $mostWitel['top_am']['am'] . "</strong>.
                </p>
            </div>";

        $gapLeast = $leastWitel['avg_imp'] - $TREG3Imp;
        $insightLeast = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Witel {$leastWitel['name']}</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-danger'><span class='insight-metric-label'>IMPROVEMENT Progress</span><span class='insight-metric-value'>{$fSign2($leastWitel['avg_p'])}</span><span class='insight-metric-sub'> </span></div>
                <div class='insight-metric-item im-warning'><span class='insight-metric-label'>IMPROVEMENT Result</span><span class='insight-metric-value'>{$fSign2($leastWitel['avg_r'])}</span><span class='insight-metric-sub'> </span></div>
                <div class='insight-metric-item'><span class='insight-metric-label'>Least Improver</span><span class='insight-metric-value' style='font-size:14px;'>" . $leastWitel['least_am']['am'] . "</span></div>
            </div>
            <div class='insight-narrative-box'>
                <div class='insight-narrative-title'><i class='fas fa-exclamation-circle'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    Witel {$leastWitel['name']} berada di posisi terbawah dalam hal performa dengan nilai improvement sebesar <strong>{$fSign($leastWitel['avg_imp'])}</strong> (Progres: <strong>{$fSign2($leastWitel['avg_p'])}</strong>, Result: <strong>{$fSign2($leastWitel['avg_r'])}</strong>). 
                    Angka ini menghasilkan gap negatif sebesar <strong>" . number_format($gapLeast, 1) . "%</strong> dari rata-rata seluruh witel, dipengaruhi oleh performa AM <strong>" . $leastWitel['least_am']['am'] . "</strong> yang memerlukan supervisi tambahan.
                </p>
            </div>";

        $insightAM = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>{$topAM['am']}</h4></div>
            <div class='insight-metrics-grid' style='grid-template-columns: repeat(2, 1fr);'>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>AVG Improvement</span><span class='insight-metric-value'>{$fSign($topAM['change_avg'])}</span></div>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Improvement Result</span><span class='insight-metric-value'>{$fSign($topAM['change_result'])}</span><span class='insight-metric-sub'> Periode Lalu</span></div>
            </div>
            <div class='insight-narrative-box blue-theme'>
                <div class='insight-narrative-title'><i class='fas fa-rocket'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    AM <strong>{$topAM['am']}</strong> mencapai skor rata-rata improvement tertinggi sebesar <strong>{$fSign($topAM['change_avg'])}</strong>. 
                    Hal ini menunjukkan efektivitas eksekusi yang sangat progresif dalam mengonversi peluang menjadi capaian nyata.
                </p>
            </div>";

        $totalWins = $stats['total_wins'] ?: 1;
        $topWinNilai = $topWinAM['stats']['total_nilai_win'] ?? 0; // ✅ Get NILAI from top win AM
        $insightTopSales = "
            <div style='margin-bottom: 16px;'><h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>{$topWinAM['am']} ({$topWinAM['witel']})</h4></div>
            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'><span class='insight-metric-label'>Total Wins</span><span class='insight-metric-value'>" . ($topWinAM['stats']['win'] ?? 0) . "</span><span class='insight-metric-sub'>Deals Closed</span></div>
                <div class='insight-metric-item'><span class='insight-metric-label'>Customer Handled</span><span class='insight-metric-value'>" . ($topWinAM['stats']['total_customers'] ?? 0) . "</span><span class='insight-metric-sub'>Total CC</span></div>
                <div class='insight-metric-item im-primary'><span class='insight-metric-label'>Nilai Offerings</span><span class='insight-metric-value'>Rp " . number_format($topWinNilai, 0, ',', '.') . "</span><span class='insight-metric-sub'>Total Nilai Win</span></div>
            </div>
            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-medal'></i> Analisis Insight</div>
                <p class='insight-narrative-text'>
                    AM <strong>{$topWinAM['am']}</strong> berhasil memenangkan <strong>" . ($topWinAM['stats']['win'] ?? 0) . "</strong> wins dengan total nilai <strong>Rp " . number_format($topWinNilai, 0, ',', '.') . "</strong>. 
                    Angka ini menunjukkan AM ini adalah penyumbang kemenangan terbesar bagi TREG3 saat ini.
                </p>
            </div>";

        return [
            'metrics' => $metrics,
            'insights_data' => [
                'TREG3' => $insightTREG3,
                'most_witel' => $insightMost,
                'least_witel' => $insightLeast,
                'top_am' => $insightAM,
                'am_most_win' => $insightTopSales
            ]
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