<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000000;
            line-height: 1.4;
            padding: 15mm;
        }
        @page { margin: 0; }
        .header-section {
            margin-bottom: 20px;
            border-bottom: 2px solid #ed1c24;
            padding-bottom: 10px;
        }
        .header-section table { width: 100%; border: none; }
        .header-section td { border: none; vertical-align: middle; }
        .header-title { font-size: 16pt; font-weight: bold; color: #333; }
        .logo-cell { text-align: right; width: 120px; }
        .logo-img { height: 45px; width: auto; }
        .metadata {
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 10px;
            border-radius: 5px;
        }
        .metrics-container { width: 100%; margin: 15px 0; display: block; }
        .metrics-column { width: 48%; float: left; margin-right: 2%; }
        .clearfix::after { content: ""; clear: both; display: table; }
        ul { margin: 5px 0; padding-left: 20px; list-style-type: disc; }
        ul li { margin-bottom: 4px; word-wrap: break-word; }
        h2 {
            font-size: 13pt;
            background: #ed1c24;
            color: white;
            padding: 5px 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            clear: both;
        }
        h3 {
            font-size: 11pt;
            border-left: 4px solid #ed1c24;
            padding-left: 8px;
            margin: 15px 0 10px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 20px;
        }
        .data-table th {
            background: #f3f4f6;
            color: #333;
            font-weight: bold;
            border: 0.5pt solid #000;
            padding: 6px 4px;
            font-size: 9pt;
            text-align: center;
        }
        .data-table td {
            padding: 5px 4px;
            border: 0.5pt solid #000;
            font-size: 9pt;
            word-wrap: break-word;
            vertical-align: middle;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .average-row { background: #fff5f5; font-weight: bold; }
        .positive { color: #059669; font-weight: bold; }
        .negative { color: #dc2626; font-weight: bold; }
        .page-break { page-break-before: always; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    {{-- HEADER & METADATA --}}
    <div class="header-section">
        <table>
            <tr>
                <td>
                    <div class="header-title">
                        Executive Summary:<br>
                        {{ $is_witel_specific ? 'High Five Performance ' . strtoupper($witel_name) : 'High Five Performance TREG 3' }}
                    </div>
                </td>
                <td class="logo-cell">
                    @php
                        $logoPath = public_path('images/telkom-logo.png');
                        $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
                    @endphp
                    @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Telkom Indonesia" class="logo-img">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="metadata">
        <p><strong>Periode</strong> : {{ $snapshot_1['date'] }} vs {{ $snapshot_2['date'] }}</p>
        <p><strong>Divisi</strong> : <span class="highlight">{{ $divisi }}</span></p>
        <p><strong>Scope Report</strong> : 
            <span class="highlight">
                {{ $is_witel_specific ? 'Witel ' . $witel_name : 'TREG 3 (All Witel)' }}
            </span>
        </p>
    </div>

    {{-- SECTION I: PERFORMANCE OVERVIEW --}}
    <h2>I. {{ $is_witel_specific ? strtoupper($witel_name) : 'TREG 3' }} Performance Overview</h2>
       
    @php
        // --- 1. AM METRICS CALCULATION ---
        $totalWins = 0; $totalLosses = 0;
        $allBenchmarking = $am_performance['benchmarking'] ?? [];
        $totalAMs = count($allBenchmarking);
        
        // Data 'benchmarking' dari controller sudah difilter sesuai witel (jika single witel)
        // Jadi aman untuk langsung di-loop
        foreach ($allBenchmarking as $am) {
            $totalWins += $am['stats']['win'] ?? 0;
            $totalLosses += $am['stats']['lose'] ?? 0;
        }
        $totalClosed = $totalWins + $totalLosses;
        $winRate = $totalClosed > 0 ? round(($totalWins / $totalClosed) * 100, 1) : 0;

        // --- 2. OFFERING & COVERAGE CALCULATION (PERBAIKAN DISINI) ---
        $totalOfferings = 0; // Reset ke 0, jangan hitung raw array dulu
        $uniqueCCs = []; 
        $visitedCCs = [];
        
        // Siapkan list AM valid untuk validasi produk
        $validAmNames = [];
        if ($is_witel_specific && isset($witel_am_list)) {
            $validAmNames = array_column($witel_am_list, 'am');
        }

        foreach ($product_performance['products'] ?? [] as $product) {
            // FILTER: Jika Single Witel, hanya hitung produk milik AM di witel tersebut
            if($is_witel_specific && !empty($validAmNames)) {
                 if (!in_array($product['am'], $validAmNames)) {
                     continue; // Skip produk dari witel lain
                 }
            }

            // HITUNG OFFERING SETELAH FILTER
            $totalOfferings++; 

            // Hitung CC Visited
            $customerName = $product['customer'] ?? null;
            $progress = $product['progress_2'] ?? 0;
            if (!empty($customerName)) {
                $uniqueCCs[$customerName] = true;
                if ($progress > 0) $visitedCCs[$customerName] = true;
            }
        }
        
        $totalCCs = count($uniqueCCs);
        $visitedCCCount = count($visitedCCs);
        $coverage = $totalCCs > 0 ? "$visitedCCCount/$totalCCs CC visited ($totalOfferings offerings)" : "-";

        // --- 3. AVG IMPROVEMENT CALCULATION ---
        $totalImprovementSum = 0;
        foreach ($allBenchmarking as $am) {
            $totalImprovementSum += $am['change_avg'] ?? 0;
        }
        $avgImprovement = $totalAMs > 0 ? round($totalImprovementSum / $totalAMs, 2) : 0;
        $scopeLabel = $is_witel_specific ? "Avg Improvement" : "TREG 3 Avg Improvement";
    @endphp

    <div class="metrics-container clearfix">
        <div class="metrics-column">
            <ul>
                <li><strong>Coverage Ratio:</strong> {{ $coverage }}</li>
                <li><strong>{{ $scopeLabel }}:</strong> <span class="{{ $avgImprovement > 0 ? 'positive' : 'negative' }}">{{ $avgImprovement > 0 ? '+' : '' }}{{ number_format($avgImprovement, 2) }}%</span></li>
            </ul>
        </div>
        <div class="metrics-column">
            <ul>
                <li><strong>Win Rate:</strong> {{ number_format($winRate, 1) }}%</li>
                <li><strong>Total Closed:</strong> {{ $totalWins }} Wins & {{ $totalLosses }} Losses</li>
            </ul>
        </div>
    </div>

    {{-- KONDISI TABEL SECTION I --}}
    @if(!$is_witel_specific)
        {{-- MODE TREG3: Tampilkan Tabel Perbandingan Witel --}}
        <h3>All Witel Improvement</h3>
        @php
            $witelData = []; $totalAllAMs = 0;
            foreach ($allBenchmarking as $am) {
                $w = $am['witel'];
                if (!isset($witelData[$w])) $witelData[$w] = ['sum_avg_1' => 0, 'sum_avg_2' => 0, 'count' => 0];
                $witelData[$w]['sum_avg_1'] += ($am['progress_1'] + $am['result_1']) / 2;
                $witelData[$w]['sum_avg_2'] += ($am['progress_2'] + $am['result_2']) / 2;
                $witelData[$w]['count']++;
                $totalAllAMs++;
            }
            $witelFinal = []; $g1 = 0; $g2 = 0;
            foreach ($witelData as $w => $d) {
                $a1 = $d['sum_avg_1'] / $d['count']; $a2 = $d['sum_avg_2'] / $d['count'];
                $g1 += $d['sum_avg_1']; $g2 += $d['sum_avg_2'];
                $witelFinal[$w] = ['a1' => $a1, 'a2' => $a2, 'imp' => $a2 - $a1];
            }
            uasort($witelFinal, fn($a, $b) => $b['imp'] <=> $a['imp']);
        @endphp
        <table class="data-table">
            <colgroup><col style="width: 40%;"><col style="width: 20%;"><col style="width: 20%;"><col style="width: 20%;"></colgroup>
            <thead>
                <tr>
                    <th>Witel</th>
                    <th class="center">{{ $snapshot_1['date'] }}</th>
                    <th class="center">{{ $snapshot_2['date'] }}</th>
                    <th class="center">Avg Improvement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($witelFinal as $w => $d)
                <tr>
                    <td>{{ $w }}</td>
                    <td class="center">{{ number_format($d['a1'], 2) }}%</td>
                    <td class="center">{{ number_format($d['a2'], 2) }}%</td>
                    <td class="center bold {{ $d['imp'] > 0 ? 'positive' : ($d['imp'] < 0 ? 'negative' : '') }}">
                        {{ $d['imp'] > 0 ? '+' : '' }}{{ number_format($d['imp'], 2) }}%
                    </td>
                </tr>
                @endforeach
                <tr class="average-row">
                    <td><strong>Grand Average</strong></td>
                    <td class="center">{{ number_format($g1/$totalAllAMs, 2) }}%</td>
                    <td class="center">{{ number_format($g2/$totalAllAMs, 2) }}%</td>
                    <td class="center">{{ number_format(($g2-$g1)/$totalAllAMs, 2) }}%</td>
                </tr>
            </tbody>
        </table>

    @else
        {{-- MODE SINGLE WITEL: GABUNG SECTION 2 KESINI (Tabel AM) --}}
        <h3>Account Manager Performance ({{ $witel_name }})</h3>
        @php
            $amList = $am_performance['benchmarking'] ?? [];
            usort($amList, fn($a, $b) => $b['change_avg'] <=> $a['change_avg']);
            $sumA1 = 0; $sumA2 = 0; $countAM = 0;
        @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th>Account Manager</th>
                    <th class="center" style="width: 20%;">{{ $snapshot_1['date'] }}</th>
                    <th class="center" style="width: 20%;">{{ $snapshot_2['date'] }}</th>
                    <th class="center" style="width: 20%;">Improvement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($amList as $index => $am)
                @php
                    $avg1 = ($am['progress_1'] + $am['result_1']) / 2;
                    $avg2 = ($am['progress_2'] + $am['result_2']) / 2;
                    $sumA1 += $avg1; $sumA2 += $avg2; $countAM++;
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $am['am'] }}</td>
                    <td class="center">{{ number_format($avg1, 1) }}%</td>
                    <td class="center">{{ number_format($avg2, 1) }}%</td>
                    <td class="center bold {{ $am['change_avg'] > 0 ? 'positive' : ($am['change_avg'] < 0 ? 'negative' : '') }}">
                        {{ $am['change_avg'] > 0 ? '+' : '' }}{{ number_format($am['change_avg'], 1) }}%
                    </td>
                </tr>
                @endforeach
                @if($countAM > 0)
                <tr class="average-row">
                    <td colspan="2"><strong>Witel Average</strong></td>
                    <td class="center">{{ number_format($sumA1/$countAM, 2) }}%</td>
                    <td class="center">{{ number_format($sumA2/$countAM, 2) }}%</td>
                    <td class="center">{{ number_format(($sumA2-$sumA1)/$countAM, 2) }}%</td>
                </tr>
                @endif
            </tbody>
        </table>
    @endif

    {{-- SECTION II: SALES FORCE IMPROVEMENT --}}
    {{-- HANYA MUNCUL JIKA TREG3 / GLOBAL --}}
    @if(!$is_witel_specific)
        <div class="page-break"></div>
        <h2>II. Sales Force Improvement</h2>
        
        @php
            $allAMs = $am_performance['benchmarking'] ?? [];
            usort($allAMs, fn($a, $b) => ($b['change_avg'] ?? 0) <=> ($a['change_avg'] ?? 0));
            $topAMObj = $allAMs[0] ?? null;
            $leastAMObj = !empty($allAMs) ? end($allAMs) : null;
        @endphp

        <div class="metrics-container clearfix">
            <div class="metrics-column">
                <ul>
                    @if(isset($am_performance['witel_analysis']['metrics']['most_witel']))
                    <li><strong>Top Imp Witel:</strong> {{ $am_performance['witel_analysis']['metrics']['most_witel']['value'] }}</li>
                    @endif
                    @if($topAMObj)
                    <li><strong>Top Imp AM:</strong> {{ $topAMObj['am'] }} ({{ $topAMObj['witel'] }}) (+{{ number_format($topAMObj['change_avg'], 1) }}%)</li>
                    @endif
                </ul>
            </div>
            <div class="metrics-column">
                <ul>
                    @if(isset($am_performance['witel_analysis']['metrics']['least_witel']))
                    <li><strong>Least Imp Witel:</strong> {{ $am_performance['witel_analysis']['metrics']['least_witel']['value'] }}</li>
                    @endif
                    @if($leastAMObj)
                    <li><strong>Least Imp AM:</strong> {{ $leastAMObj['am'] }} ({{ $leastAMObj['witel'] }}) ({{ number_format($leastAMObj['change_avg'], 1) }}%)</li>
                    @endif
                </ul>
            </div>
        </div>

        <h3>Top 10 Improvement AM</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30pt;">No</th>
                    <th>Account Manager</th>
                    <th>Witel</th>
                    <th class="center">Improvement</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($am_performance['leaderboard'] ?? [], 0, 10) as $index => $am)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $am['am'] }}</td>
                    <td>{{ $am['witel'] }}</td>
                    <td class="center positive">+{{ number_format($am['change_avg'], 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- SECTION III: PRODUCT PERFORMANCE --}}
    <div class="page-break"></div>
    <h2>{{ $is_witel_specific ? 'II. Product Performance' : 'III. Product Performance' }}</h2>
    
    @php
        $stagnantCount = 0; $closedCount = 0; $notOfferedCount = 0; $activeCount = 0;
        $totalSubmitSPH = 0; $sphNegotiation = 0; $sphClosed = 0;
        $stagnantByProduct = [];
        
        // Re-use $validAmNames yang didefinisikan di atas
        foreach ($product_performance['products'] ?? [] as $product) {
            // FILTER: Sama seperti di atas, pastikan hanya produk AM witel terkait
            if ($is_witel_specific && !empty($validAmNames)) {
                 if (!in_array($product['am'], $validAmNames)) continue;
            }

            $res = strtolower($product['result'] ?? '');
            $p2 = $product['progress_2'] ?? 0;
            $isClosed = (strpos($res, 'win') !== false || strpos($res, 'lose') !== false);
            
            if ($isClosed) $closedCount++;
            if ($p2 == 0) $notOfferedCount++; else if (!$isClosed) $activeCount++;
            if ($p2 >= 100) { $totalSubmitSPH++; if ($isClosed) $sphClosed++; else $sphNegotiation++; }
            
            $imp = (($product['change_progress'] ?? 0) + ($product['change_result'] ?? 0)) / 2;
            if ($imp == 0 && !$isClosed) {
                $stagnantCount++;
                $stagnantByProduct[$product['product']] = ($stagnantByProduct[$product['product']] ?? 0) + 1;
            }
        }
        arsort($stagnantByProduct);
        $topStag = count($stagnantByProduct) > 0 ? array_key_first($stagnantByProduct) : '-';
        $topStagCount = count($stagnantByProduct) > 0 ? current($stagnantByProduct) : 0;
    @endphp

    <div class="metrics-container clearfix">
        <div class="metrics-column">
            <ul>
                <li><strong>Total SPH:</strong> {{ $totalSubmitSPH }} ({{ $sphNegotiation }} dalam tahap Negosiasi)</li>
                <li><strong>Active Offerings :</strong> {{ $activeCount }} Offerings</li>
            </ul>
        </div>
        <div class="metrics-column">
            <ul>
                <li><strong>Stagnant Offerings:</strong> {{ $stagnantCount }}</li>
                <li><strong>Most Stagnant:</strong> {{ $topStag }} ({{ $topStagCount }} offerings)</li>
            </ul>
        </div>
    </div>

    <h3>Top 10 Best-Selling Products {{ $is_witel_specific ? "($witel_name)" : "" }}</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30pt;">No</th>
                <th>Product</th>
                <th class="center">Total Offerings</th>
                <th class="center">Total Wins</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($product_performance['product_leaderboard']['top_10'] ?? [], 0, 10) as $index => $p)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $p['product'] }}</td>
                <td class="center">{{ $p['total_offerings'] }}</td>
                <td class="center bold">{{ $p['wins'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SECTION IV: DETAIL DATA --}}
    <div class="page-break"></div>
    <h2>{{ $is_witel_specific ? 'III. Detail Data Offerings ' . $witel_name : 'IV. Detail Data AM-Customer' }}</h2>
    
    <table class="data-table">
        <colgroup>
            <col style="width: 18%;">
            <col style="width: 22%;">
            @if($is_witel_specific)<col style="width: 20%;">@endif
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">AM</th>
                <th rowspan="2">CUSTOMER</th>
                @if($is_witel_specific)<th rowspan="2">PRODUCT</th>@endif
                <th colspan="2" class="center">% PROGRESS</th>
                <th colspan="2" class="center">% RESULT</th>
                <th rowspan="2" class="center">IMPROVEMENT</th>
            </tr>
            <tr>
                <th class="center">{{ $snapshot_1['date'] }}</th>
                <th class="center">{{ $snapshot_2['date'] }}</th>
                <th class="center">{{ $snapshot_1['date'] }}</th>
                <th class="center">{{ $snapshot_2['date'] }}</th>
            </tr>
        </thead>
        <tbody>
            @php $prevAM = null; $prevCust = null; @endphp
            
            @if($is_witel_specific)
                @foreach($witel_product_details as $am => $customers)
                    @foreach($customers as $cust => $products)
                        @foreach($products as $idx => $p)
                        <tr>
                            <td style="background:#ffe6e6;">{{ $am !== $prevAM ? $am : '' }}</td>
                            <td style="background:#fff9e6;">{{ $cust !== $prevCust ? $cust : '' }}</td>
                            <td>{{ $p['product'] }}</td>
                            <td class="center">{{ number_format($p['progress_1'], 0) }}%</td>
                            <td class="center">{{ number_format($p['progress_2'], 0) }}%</td>
                            <td class="center">{{ number_format($p['result_1'], 0) }}%</td>
                            <td class="center">{{ number_format($p['result_2'], 0) }}%</td>
                            <td class="center bold {{ $p['change_avg'] > 0 ? 'positive' : ($p['change_avg'] < 0 ? 'negative' : '') }}">
                                {{ $p['change_avg'] > 0 ? '+' : '' }}{{ number_format($p['change_avg'], 1) }}%
                            </td>
                        </tr>
                        @php $prevAM = $am; $prevCust = $cust; @endphp
                        @endforeach
                    @endforeach
                @endforeach
            @else
                @foreach($overall_summary_data as $witel => $amData)
                    <tr style="background: #e2e8f0;"><td colspan="7" class="center bold">{{ $witel }}</td></tr>
                    @foreach($amData as $am => $custData)
                        @foreach($custData as $cName => $d)
                        <tr>
                            <td style="background:#ffe6e6;">{{ $am !== $prevAM ? $am : '' }}</td>
                            <td style="background:#fff9e6;">{{ $cName }}</td>
                            <td class="center">{{ number_format($d['avg_progress_1'], 1) }}%</td>
                            <td class="center">{{ number_format($d['avg_progress_2'], 1) }}%</td>
                            <td class="center">{{ number_format($d['avg_result_1'], 1) }}%</td>
                            <td class="center">{{ number_format($d['avg_result_2'], 1) }}%</td>
                            <td class="center bold {{ $d['avg_change'] > 0 ? 'positive' : ($d['avg_change'] < 0 ? 'negative' : '') }}">
                                {{ $d['avg_change'] > 0 ? '+' : '' }}{{ number_format($d['avg_change'], 1) }}%
                            </td>
                        </tr>
                        @php $prevAM = $am; @endphp
                        @endforeach
                    @endforeach
                @endforeach
            @endif
        </tbody>
    </table>

    <script type="text/php">
        if ( isset($pdf) ) {
            $x = 520; 
            $y = 800; 
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 9;
            $color = array(0,0,0);
            $pdf->page_text($x, $y, $text, $font, $size, $color, 0.0, 0.0, 0.0);
        }
    </script>
</body>
</html>