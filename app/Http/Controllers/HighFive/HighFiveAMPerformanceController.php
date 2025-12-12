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
                        'cust_list' => []
                    ]
                ];
            }

            $amGrouped[$key]['total_progress'] += $row['progress_percentage'];
            $amGrouped[$key]['total_result'] += $row['result_percentage'];
            $amGrouped[$key]['count']++;

            $stats = &$amGrouped[$key]['stats'];
            $stats['offerings']++;

            if (!empty($row['customer_name'])) {
                $stats['cust_list'][$row['customer_name']] = true;
            }

            $resText = strtolower($row['result'] ?? '');
            $resVal = $row['result_percentage'] ?? 0;

            if (strpos($resText, 'win') !== false || $resVal == 100) {
                $stats['win']++;
            } elseif (strpos($resText, 'lose') !== false) {
                $stats['lose']++;
            }
        }

        $amAverage = [];
        foreach ($amGrouped as $key => $data) {
            $avgProgress = $data['count'] > 0 ? round($data['total_progress'] / $data['count'], 2) : 0;
            $avgResult = $data['count'] > 0 ? round($data['total_result'] / $data['count'], 2) : 0;

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
                'stats' => $stats
            ];
        }

        // Sort by Witel (A-Z), then Improvement (Top to Least)
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

    /**
     * Metric Calculation for Square Cards
     */
    private function calculateWitelAnalysis($mergedData, $snapshot1, $snapshot2)
    {
        // 1. Inisialisasi
        $stats = [
            'total_ams' => 0,
            'sum_progress_1' => 0, 'sum_progress_2' => 0,
            'sum_result_2' => 0,
            'active_ams' => 0,
            // New Stats
            'total_offerings' => 0,
            'total_visited' => 0,
            'total_wins' => 0,
            'total_loses' => 0
        ];

        $witelStats = [];
        $topAM = null;      // Improvement Tertinggi
        $topWinAM = null;   // Jumlah Win Terbanyak

        // 2. Looping Data
        foreach ($mergedData as $row) {
            $stats['total_ams']++;
            $stats['sum_progress_1'] += $row['progress_1'];
            $stats['sum_progress_2'] += $row['progress_2'];
            $stats['sum_result_2'] += $row['result_2'];
            if ($row['progress_2'] > 0) $stats['active_ams']++;

            // Accumulate National Stats
            $amStats = $row['stats'] ?? [];
            $stats['total_offerings'] += $amStats['offerings'] ?? 0;
            $stats['total_visited'] += $amStats['total_customers'] ?? 0; // CC Visited
            $stats['total_wins'] += $amStats['win'] ?? 0;
            $stats['total_loses'] += $amStats['lose'] ?? 0;

            // A. Cari MVP Improvement
            if (!$topAM || $row['change_avg'] > $topAM['change_avg']) {
                $topAM = $row;
            }

            // B. Cari Top Sales (Most Win)
            $amWin = $amStats['win'] ?? 0;
            $currentTopWin = $topWinAM['stats']['win'] ?? 0;
            // Jika win lebih banyak, ATAU win sama tapi result % lebih tinggi
            if (!$topWinAM || $amWin > $currentTopWin || ($amWin == $currentTopWin && $row['result_2'] > $topWinAM['result_2'])) {
                $topWinAM = $row;
            }

            // Grouping Witel
            $witel = $row['witel'];
            if (!isset($witelStats[$witel])) {
                $witelStats[$witel] = ['name' => $witel, 'sum_p1' => 0, 'sum_p2' => 0, 'count' => 0];
            }
            $witelStats[$witel]['sum_p1'] += $row['progress_1'];
            $witelStats[$witel]['sum_p2'] += $row['progress_2'];
            $witelStats[$witel]['count']++;
        }

        // 3. Agregasi Witel
        $witelFinal = [];
        foreach ($witelStats as $w => $d) {
            $avgP1 = $d['count'] > 0 ? $d['sum_p1'] / $d['count'] : 0;
            $avgP2 = $d['count'] > 0 ? $d['sum_p2'] / $d['count'] : 0;
            $witelFinal[] = [
                'name' => $w,
                'avg_progress' => $avgP2,
                'growth' => $avgP2 - $avgP1,
                'am_count' => $d['count']
            ];
        }

        // Sort Witel
        usort($witelFinal, fn($a, $b) => $b['avg_progress'] <=> $a['avg_progress']);
        $mostWitel = $witelFinal[0] ?? ['name' => '-', 'avg_progress' => 0, 'growth' => 0];
        $leastWitel = end($witelFinal) ?: ['name' => '-', 'avg_progress' => 0, 'growth' => 0];

        // Global Stats
        $total = $stats['total_ams'] ?: 1;
        $avgProg2 = $stats['sum_progress_2'] / $total;
        $deltaProg = $avgProg2 - ($stats['sum_progress_1'] / $total);

        // 4. Siapkan 5 Metriks
        $metrics = [
            'national' => [
                'label' => 'National Pulse',
                'value' => number_format($avgProg2, 1) . '%',
                'sub_label' => 'Avg Progress',
                'trend' => $deltaProg,
                'trend_text' => ($deltaProg >= 0 ? '+' : '') . number_format($deltaProg, 1) . '% vs last period',
                'color' => $deltaProg >= 0 ? 'success' : 'danger',
                // New Detailed Stats for Frontend
                'offerings' => number_format($stats['total_offerings']),
                'visited' => number_format($stats['total_visited']),
                'wins' => number_format($stats['total_wins']),
                'loses' => number_format($stats['total_loses'])
            ],
            'most_witel' => [
                'label' => 'Witel Champion',
                'value' => $mostWitel['name'],
                'sub_label' => 'Highest Progress',
                'main_stat' => number_format($mostWitel['avg_progress'], 1) . '%',
            ],
            'least_witel' => [
                'label' => 'Focus Area',
                'value' => $leastWitel['name'],
                'sub_label' => 'Lowest Progress',
                'main_stat' => number_format($leastWitel['avg_progress'], 1) . '%',
            ],
            'top_am' => [
                'label' => 'MVP Improver',
                'value' => $topAM ? Str::limit($topAM['am'], 15) : '-',
                'sub_label' => $topAM ? $topAM['witel'] : '-',
                'main_stat' => $topAM ? '+' . number_format($topAM['change_avg'], 1) . '%' : '0%',
            ],
            'am_most_win' => [
                'label' => 'Top Sales AM',
                'value' => $topWinAM ? Str::limit($topWinAM['am'], 15) : '-',
                'sub_label' => $topWinAM ? $topWinAM['witel'] : '-',
                'main_stat' => ($topWinAM['stats']['win'] ?? 0) . ' Wins',
            ]
        ];

        // 5. Generate Detailed Insights HTML
        $winRate = $stats['total_offerings'] > 0 ? ($stats['total_wins'] / $stats['total_offerings']) * 100 : 0;
        $conversionRate = ($stats['total_wins'] + $stats['total_loses']) > 0 
            ? ($stats['total_wins'] / ($stats['total_wins'] + $stats['total_loses'])) * 100 
            : 0;

        $insightNational = "
            <h5 class='text-blue-600'><i class='fas fa-globe-asia'></i> Overview Nasional</h5>
            <p>Performa High Five secara nasional menunjukkan tren <strong>" . ($deltaProg >= 0 ? "positif" : "negatif") . "</strong> dengan rata-rata progress <strong>" . number_format($avgProg2, 2) . "%</strong>.</p>
            
            <div class='insight-grid' style='display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px; margin-bottom:10px;'>
                <div style='background:#f8fafc; padding:8px; border-radius:6px;'>
                    <div style='font-size:11px; color:#64748b;'>FUNNEL ACTIVITY</div>
                    <div style='font-weight:600; font-size:13px;'>
                        🎯 " . number_format($stats['total_offerings']) . " Offerings<br>
                        👥 " . number_format($stats['total_visited']) . " CC Visited
                    </div>
                </div>
                <div style='background:#f8fafc; padding:8px; border-radius:6px;'>
                    <div style='font-size:11px; color:#64748b;'>SALES CONVERSION</div>
                    <div style='font-weight:600; font-size:13px;'>
                        🏆 " . number_format($stats['total_wins']) . " Wins<br>
                        ❌ " . number_format($stats['total_loses']) . " Loses
                    </div>
                </div>
            </div>

            <ul class='insight-list'>
                <li><strong>Win Rate Global:</strong> " . number_format($winRate, 1) . "% dari total offerings berhasil dikonversi menjadi Win.</li>
                <li><strong>Participation:</strong> Dari total {$total} AM, sebanyak <strong>" . number_format(($stats['active_ams'] / $total) * 100, 1) . "%</strong> aktif melakukan update progress.</li>
            </ul>";

        $insightMost = "
            <h5 class='text-primary'><i class='fas fa-crown'></i> Witel Champion: {$mostWitel['name']}</h5>
            <p>Witel {$mostWitel['name']} konsisten memimpin leaderboard dengan rata-rata progress <strong>" . number_format($mostWitel['avg_progress'], 2) . "%</strong>.</p>
            <ul class='insight-list'>
                <li>Pertumbuhan (Growth) dari periode lalu: <strong>" . ($mostWitel['growth'] >= 0 ? "+" : "") . number_format($mostWitel['growth'], 2) . "%</strong>.</li>
                <li>Witel ini memiliki <strong>{$mostWitel['am_count']} AM</strong> yang berkontribusi aktif.</li>
            </ul>
            <p class='text-sm text-gray-500 mt-2'>Kunci sukses: Monitoring harian dan disiplin input LOP/MyTens.</p>";

        $insightLeast = "
            <h5 class='text-yellow-600'><i class='fas fa-exclamation-triangle'></i> Focus Area: Witel {$leastWitel['name']}</h5>
            <p>Witel {$leastWitel['name']} memerlukan atensi khusus karena rata-rata progressnya (<strong>" . number_format($leastWitel['avg_progress'], 2) . "%</strong>) berada di bawah rata-rata nasional.</p>
            <ul class='insight-list'>
                <li>Gap dari Nasional: <strong>" . number_format($avgProg2 - $leastWitel['avg_progress'], 2) . "%</strong>.</li>
                <li>Potensi improvement sangat besar jika dilakukan intervensi/coaching kepada AM yang masih belum bergerak.</li>
            </ul>";

        $topAmScore = $topAM ? number_format($topAM['change_avg'], 1) : 0;
        $topAmProg = $topAM ? number_format($topAM['progress_2'], 1) : 0;
        $insightAM = "
            <h5 class='text-purple-600'><i class='fas fa-user-astronaut'></i> MVP: " . ($topAM['am'] ?? '-') . "</h5>
            <p>Diberikan kepada AM dengan <strong>lonjakan performa (Improvement)</strong> tertinggi minggu ini.</p>
            <ul class='insight-list'>
                <li>Witel: <strong>" . ($topAM['witel'] ?? '-') . "</strong></li>
                <li>Improvement Score: <span class='badge-up'>+{$topAmScore}%</span></li>
                <li>Progress Akhir: <strong>{$topAmProg}%</strong></li>
            </ul>
            <p class='text-sm text-gray-500 mt-2'>Bukti nyata bahwa akselerasi performa bisa dilakukan dalam waktu singkat.</p>";

        $winCount = $topWinAM['stats']['win'] ?? 0;
        $offerCount = $topWinAM['stats']['offerings'] ?? 0;
        $insightTopSales = "
            <h5 class='text-green-600'><i class='fas fa-trophy'></i> Top Sales: " . ($topWinAM['am'] ?? '-') . "</h5>
            <p>AM paling produktif dalam mencetak angka <strong>WIN (Closing)</strong>.</p>
            <ul class='insight-list'>
                <li>Witel: <strong>" . ($topWinAM['witel'] ?? '-') . "</strong></li>
                <li>Total Wins: <strong>{$winCount}</strong> dari <strong>{$offerCount}</strong> offerings.</li>
                <li>Efektivitas tinggi dalam mengawal peluang menjadi revenue.</li>
            </ul>";

        $insightsData = [
            'national' => $insightNational,
            'most_witel' => $insightMost,
            'least_witel' => $insightLeast,
            'top_am' => $insightAM,
            'am_most_win' => $insightTopSales
        ];

        return [
            'metrics' => $metrics,
            'insights_data' => $insightsData
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