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
                        'cust_list' => [],
                        'visited_customers' => []
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
                
                // Track if this customer has any progress
                if (($row['progress_percentage'] ?? 0) > 0) {
                    $stats['visited_customers'][$custName] = true;
                }
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
                'visited' => count($data['stats']['visited_customers']),
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
                'offerings' => 0, 'total_customers' => 0, 'visited' => 0, 'win' => 0, 'lose' => 0
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
            'total_customers' => 0,
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
            $stats['total_customers'] += $amStats['total_customers'] ?? 0;
            $stats['total_visited'] += $amStats['visited'] ?? 0; // CC Visited
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
                $witelStats[$witel] = [
                    'name' => $witel, 
                    'sum_p1' => 0, 
                    'sum_p2' => 0, 
                    'sum_r1' => 0, 
                    'sum_r2' => 0, 
                    'count' => 0
                ];
            }
            $witelStats[$witel]['sum_p1'] += $row['progress_1'];
            $witelStats[$witel]['sum_p2'] += $row['progress_2'];
            $witelStats[$witel]['sum_r1'] += $row['result_1'];
            $witelStats[$witel]['sum_r2'] += $row['result_2'];
            $witelStats[$witel]['count']++;
        }

        // 3. Agregasi Witel
        $witelFinal = [];
        foreach ($witelStats as $w => $d) {
            $avgP1 = $d['count'] > 0 ? $d['sum_p1'] / $d['count'] : 0;
            $avgP2 = $d['count'] > 0 ? $d['sum_p2'] / $d['count'] : 0;
            $avgR1 = $d['count'] > 0 ? $d['sum_r1'] / $d['count'] : 0;
            $avgR2 = $d['count'] > 0 ? $d['sum_r2'] / $d['count'] : 0;
            
            $progressChange = $avgP2 - $avgP1;
            $resultChange = $avgR2 - $avgR1;
            $avgImprovement = ($progressChange + $resultChange) / 2;
            
            $witelFinal[] = [
                'name' => $w,
                'avg_progress' => $avgP2,
                'avg_improvement' => $avgImprovement,
                'growth' => $avgP2 - $avgP1,
                'am_count' => $d['count']
            ];
        }

        // Sort Witel by average improvement
        usort($witelFinal, fn($a, $b) => $b['avg_improvement'] <=> $a['avg_improvement']);
        $mostWitel = $witelFinal[0] ?? ['name' => '-', 'avg_progress' => 0, 'avg_improvement' => 0, 'growth' => 0];
        $leastWitel = end($witelFinal) ?: ['name' => '-', 'avg_progress' => 0, 'avg_improvement' => 0, 'growth' => 0];

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
                'total_customers' => number_format($stats['total_customers']),
                'visited' => number_format($stats['total_visited']),
                'wins' => number_format($stats['total_wins']),
                'loses' => number_format($stats['total_loses'])
            ],
            'most_witel' => [
                'label' => 'Witel Champion',
                'value' => $mostWitel['name'],
                'sub_label' => 'Highest Improvement',
                'main_stat' => number_format($mostWitel['avg_improvement'], 1) . '% Avg Improvement',
            ],
            'least_witel' => [
                'label' => 'Focus Area',
                'value' => $leastWitel['name'],
                'sub_label' => 'Lowest Improvement',
                'main_stat' => number_format($leastWitel['avg_improvement'], 1) . '% Avg Improvement',
            ],
            'top_am' => [
                'label' => 'MVP Improver',
                'value' => $topAM ? $topAM['am'] : '-',
                'sub_label' => $topAM ? $topAM['witel'] : '-',
                'main_stat' => $topAM ? number_format($topAM['change_avg'], 1) . '% Avg Improvement' : '0%',
            ],
            'am_most_win' => [
                'label' => 'Top Sales AM',
                'value' => $topWinAM ? $topWinAM['am'] : '-',
                'sub_label' => $topWinAM ? $topWinAM['witel'] : '-',
                'main_stat' => ($topWinAM['stats']['win'] ?? 0) . ' Wins',
            ]
        ];

        // 5. Generate Detailed Insights HTML
        $winRate = $stats['total_offerings'] > 0 ? ($stats['total_wins'] / $stats['total_offerings']) * 100 : 0;
        $conversionRate = ($stats['total_wins'] + $stats['total_loses']) > 0 
            ? ($stats['total_wins'] / ($stats['total_wins'] + $stats['total_loses'])) * 100 
            : 0;

        // 1. INSIGHT NATIONAL
        $trendColor = $deltaProg >= 0 ? 'text-green-600' : 'text-red-600';
        $trendIcon = $deltaProg >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        $trendSign = $deltaProg >= 0 ? '+' : '';
        
        $insightNational = "
            <div style='margin-bottom: 16px;'>
                <h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0 0 4px 0;'>National Overview</h4>
                <p style='font-size:12px; color:#64748b; margin:0;'>Snapshot performa nasional saat ini.</p>
            </div>

            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-primary'>
                    <span class='insight-metric-label'>Avg Progress</span>
                    <span class='insight-metric-value'>" . number_format($avgProg2, 2) . "%</span>
                    <span class='insight-metric-sub'>Global Average</span>
                </div>
                <div class='insight-metric-item " . ($deltaProg >= 0 ? 'im-success' : 'im-danger') . "'>
                    <span class='insight-metric-label'>Growth</span>
                    <span class='insight-metric-value'>{$trendSign}" . number_format($deltaProg, 2) . "%</span>
                    <span class='insight-metric-sub'>vs Last Period</span>
                </div>
                <div class='insight-metric-item im-warning'>
                    <span class='insight-metric-label'>Win Rate</span>
                    <span class='insight-metric-value'>" . number_format($winRate, 1) . "%</span>
                    <span class='insight-metric-sub'>From Offerings</span>
                </div>
                <div class='insight-metric-item'>
                    <span class='insight-metric-label'>Total Wins</span>
                    <span class='insight-metric-value'>" . number_format($stats['total_wins']) . "</span>
                    <span class='insight-metric-sub'>Deals Closed</span>
                </div>
                 <div class='insight-metric-item'>
                    <span class='insight-metric-label'>Participation</span>
                    <span class='insight-metric-value'>" . number_format(($stats['active_ams'] / $total) * 100, 0) . "%</span>
                    <span class='insight-metric-sub'>Active AMs</span>
                </div>
            </div>

            <div class='insight-narrative-box blue-theme'>
                <div class='insight-narrative-title'><i class='fas fa-info-circle'></i> Analisis & Rekomendasi</div>
                <p class='insight-narrative-text'>
                    Secara nasional, tren performa bergerak <strong>" . ($deltaProg >= 0 ? "positif" : "negatif") . "</strong>. 
                    Tingkat konversi (Win Rate) berada di angka <strong>" . number_format($winRate, 1) . "%</strong>. 
                    Fokus utama minggu ini adalah meningkatkan partisipasi AM yang masih pasif dan mengawal " . number_format($stats['total_offerings']) . " offerings yang sedang berjalan.
                </p>
            </div>";

        // 2. INSIGHT WITEL CHAMPION
        $insightMost = "
             <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#dbeafe; padding:8px; border-radius:8px; color:#2563eb;'><i class='fas fa-crown fa-lg'></i></div>
                <div>
                    <h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Witel {$mostWitel['name']}</h4>
                    <p style='font-size:12px; color:#64748b; margin:0;'>Top Performer Witel</p>
                </div>
            </div>

            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'>
                    <span class='insight-metric-label'>Avg Progress</span>
                    <span class='insight-metric-value'>" . number_format($mostWitel['avg_progress'], 2) . "%</span>
                    <span class='insight-metric-sub'>Highest Rank</span>
                </div>
                <div class='insight-metric-item im-primary'>
                    <span class='insight-metric-label'>Growth</span>
                    <span class='insight-metric-value'>+" . number_format($mostWitel['growth'], 2) . "%</span>
                    <span class='insight-metric-sub'>Improvement</span>
                </div>
                <div class='insight-metric-item'>
                    <span class='insight-metric-label'>Sales Force</span>
                    <span class='insight-metric-value'>{$mostWitel['am_count']}</span>
                    <span class='insight-metric-sub'>Total AM</span>
                </div>
            </div>

            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-check-circle'></i> Key Success Factor</div>
                <p class='insight-narrative-text'>
                    Witel {$mostWitel['name']} berhasil memimpin dengan konsistensi input yang tinggi. 
                    Gap positif sebesar <strong>+" . number_format($mostWitel['avg_progress'] - $avgProg2, 1) . "%</strong> di atas rata-rata nasional menunjukkan manajemen pipeline yang sangat sehat.
                </p>
            </div>";

        // 3. INSIGHT FOCUS AREA
        $gapMinus = number_format($avgProg2 - $leastWitel['avg_progress'], 1);
        $insightLeast = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#fef3c7; padding:8px; border-radius:8px; color:#d97706;'><i class='fas fa-exclamation-triangle fa-lg'></i></div>
                <div>
                    <h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>Witel {$leastWitel['name']}</h4>
                    <p style='font-size:12px; color:#64748b; margin:0;'>Memerlukan Atensi Khusus</p>
                </div>
            </div>

            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-danger'>
                    <span class='insight-metric-label'>Avg Progress</span>
                    <span class='insight-metric-value'>" . number_format($leastWitel['avg_progress'], 2) . "%</span>
                    <span class='insight-metric-sub'>Lowest Rank</span>
                </div>
                <div class='insight-metric-item im-warning'>
                    <span class='insight-metric-label'>Gap to National</span>
                    <span class='insight-metric-value'>-{$gapMinus}%</span>
                    <span class='insight-metric-sub'>Difference</span>
                </div>
                <div class='insight-metric-item'>
                    <span class='insight-metric-label'>Sales Force</span>
                    <span class='insight-metric-value'>{$leastWitel['am_count']}</span>
                    <span class='insight-metric-sub'>Total AM</span>
                </div>
            </div>

            <div class='insight-narrative-box'>
                <div class='insight-narrative-title'><i class='fas fa-lightbulb'></i> Action Plan</div>
                <p class='insight-narrative-text'>
                    Performa Witel {$leastWitel['name']} tertinggal signifikan. 
                    Diperlukan <strong>coaching clinic</strong> intensif untuk AM yang belum update progress. 
                    Prioritaskan update status LOP/MyTens minggu ini untuk mengejar gap.
                </p>
            </div>";

        // 4. INSIGHT MVP AM
        $topAmScore = $topAM ? number_format($topAM['change_avg'], 1) : 0;
        $topAmProg = $topAM ? number_format($topAM['progress_2'], 1) : 0;
        
        $amName = $topAM['am'] ?? '-';
        $amWitel = $topAM['witel'] ?? '-';

        $insightAM = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#f3e8ff; padding:8px; border-radius:8px; color:#9333ea;'><i class='fas fa-rocket fa-lg'></i></div>
                <div>
                    <h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>{$amName}</h4>
                    <p style='font-size:12px; color:#64748b; margin:0;'>MVP Improver ({$amWitel})</p>
                </div>
            </div>

            <div class='insight-metrics-grid' style='grid-template-columns: repeat(2, 1fr);'>
                <div class='insight-metric-item im-primary'>
                    <span class='insight-metric-label'>Improvement Score</span>
                    <span class='insight-metric-value'>+{$topAmScore}%</span>
                    <span class='insight-metric-sub'>Minggu ini</span>
                </div>
                <div class='insight-metric-item im-success'>
                    <span class='insight-metric-label'>Current Progress</span>
                    <span class='insight-metric-value'>{$topAmProg}%</span>
                    <span class='insight-metric-sub'>Capaian Akhir</span>
                </div>
            </div>

            <div class='insight-narrative-box blue-theme'>
                <div class='insight-narrative-title'><i class='fas fa-star'></i> Achievement</div>
                <p class='insight-narrative-text'>
                    AM ini mencatatkan lonjakan performa (Improvement) tertinggi minggu ini sebesar <strong>+{$topAmScore}%</strong>. 
                    Progress saat ini telah mencapai angka <strong>{$topAmProg}%</strong>, menunjukkan akselerasi yang signifikan dibanding periode sebelumnya.
                </p>
            </div>";

        // 5. INSIGHT TOP SALES
        $salesName = $topWinAM['am'] ?? '-';
        $salesWitel = $topWinAM['witel'] ?? '-';
        $winCount = $topWinAM['stats']['win'] ?? 0;
        $offerCount = $topWinAM['stats']['offerings'] ?? 0;
        $conversionSales = $offerCount > 0 ? ($winCount/$offerCount)*100 : 0;

        $insightTopSales = "
            <div style='margin-bottom: 16px; display:flex; align-items:center; gap:10px;'>
                <div style='background:#ecfdf5; padding:8px; border-radius:8px; color:#059669;'><i class='fas fa-trophy fa-lg'></i></div>
                <div>
                    <h4 style='font-size:16px; font-weight:700; color:#1e293b; margin:0;'>{$salesName}</h4>
                    <p style='font-size:12px; color:#64748b; margin:0;'>Top Sales ({$salesWitel})</p>
                </div>
            </div>

            <div class='insight-metrics-grid'>
                <div class='insight-metric-item im-success'>
                    <span class='insight-metric-label'>Total Wins</span>
                    <span class='insight-metric-value'>{$winCount}</span>
                    <span class='insight-metric-sub'>Project Closed</span>
                </div>
                <div class='insight-metric-item'>
                    <span class='insight-metric-label'>Offerings</span>
                    <span class='insight-metric-value'>{$offerCount}</span>
                    <span class='insight-metric-sub'>Total Proposed</span>
                </div>
                <div class='insight-metric-item im-primary'>
                    <span class='insight-metric-label'>Conversion Rate</span>
                    <span class='insight-metric-value'>" . number_format($conversionSales, 0) . "%</span>
                    <span class='insight-metric-sub'>Win / Offerings</span>
                </div>
            </div>

            <div class='insight-narrative-box green-theme'>
                <div class='insight-narrative-title'><i class='fas fa-thumbs-up'></i> Sales Effectiveness</div>
                <p class='insight-narrative-text'>
                    Efektivitas closing yang luar biasa. AM ini berhasil mengonversi peluang menjadi revenue nyata. 
                    Strategi pendekatan customer yang dilakukan patut menjadi benchmark bagi AM lainnya.
                </p>
            </div>";

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