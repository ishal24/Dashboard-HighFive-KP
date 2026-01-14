<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.5;
            padding: 25.4mm;  /* 2.54cm margin via padding since DomPDF ignores margin option */
        }

        .header-section {
            margin-bottom: 20px;
        }

        .header-section table {
            width: 100%;
            border: none;
            margin-bottom: 15px;
        }

        .header-section td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .header-title {
            font-size: 18pt;
            font-weight: bold;
            line-height: 1.3;
        }

        .logo-cell {
            text-align: right;
            width: 150px;
        }

        .logo-img {
            height: 60px;
            width: auto;
        }

        .metadata {
            margin-bottom: 25px;
            font-size: 11pt;
        }

        .metadata p {
            margin-bottom: 3px;
        }

        .highlight {
            background: #ffcccc;
            padding: 0 3px;
        }

        h2 {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        h3 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .key-metrics {
            margin-bottom: 15px;
        }

        .key-metrics p {
            margin-bottom: 5px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 20px 0;
            font-size: 10pt;
        }

        .data-table th {
            background: #f3f4f6;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
            font-size: 10pt;
        }

        .data-table td {
            padding: 8px 6px;
            border: 1px solid #000;
            font-size: 10pt;
            word-wrap: break-word;
        }

        .data-table .average-row {
            font-weight: bold;
            background: #f9fafb;
        }

        .data-table .center {
            text-align: center;
        }

        .data-table .no-col {
            width: 30px;
            text-align: center;
        }

        ul {
            margin: 10px 0;
            padding-left: 25px;
        }

        ul li {
            margin-bottom: 5px;
        }

        .page-break {
            page-break-before: always;
        }

        @page {
            margin: 0;
        }
    </style>
</head>
<body>
    {{-- PAGE 1: EXECUTIVE SUMMARY --}}
    
    {{-- Header with Logo --}}
    <div class="header-section">
        <table>
            <tr>
                <td>
                    <div class="header-title">
                        Executive Summary:<br>High Five Performance
                    </div>
                </td>
                <td class="logo-cell">
                    @php
                        $logoPath = public_path('images/telkom-logo.png');
                        if (file_exists($logoPath)) {
                            $logoData = base64_encode(file_get_contents($logoPath));
                            $logoSrc = 'data:image/png;base64,' . $logoData;
                        } else {
                            $logoSrc = null;
                        }
                    @endphp
                    @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Telkom Indonesia" class="logo-img">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Metadata --}}
    <div class="metadata">
        <p><strong>Periode</strong> : {{ $snapshot_1['date'] }} vs {{ $snapshot_2['date'] }}</p>
        <p><strong>Divisi</strong> : <span class="highlight">{{ $divisi }}</span></p>
    </div>

    {{-- SECTION I: REGIONAL PERFORMANCE OVERVIEW --}}
    <h2>I. Regional Performance Overview</h2>

    @php
        // Calculate metrics from actual data
        $totalWins = 0;
        $totalLosses = 0;
        $totalAMs = count($am_performance['benchmarking'] ?? []);
        
        // Count from AM benchmarking data
        foreach ($am_performance['benchmarking'] ?? [] as $am) {
            $totalWins += $am['stats']['win'] ?? 0;
            $totalLosses += $am['stats']['lose'] ?? 0;
        }
        
        // ✅ FIX: Count unique CCs and visited CCs separately
        $totalOfferings = count($product_performance['products'] ?? []);
        $uniqueCCs = [];         // All unique customers
        $visitedCCs = [];        // Customers that were actually visited (progress > 0)
        
        foreach ($product_performance['products'] ?? [] as $product) {
            $customerName = $product['customer'] ?? null;
            $progress = $product['progress_2'] ?? 0;
            
            if (!empty($customerName)) {
                // Track all unique customers
                $uniqueCCs[$customerName] = true;
                
                // Track visited customers (progress > 0)
                if ($progress > 0) {
                    $visitedCCs[$customerName] = true;
                }
            }
        }
        
        $totalCCs = count($uniqueCCs);
        $visitedCCCount = count($visitedCCs);
        
        $totalClosed = $totalWins + $totalLosses;
        $winRate = $totalClosed > 0 ? round(($totalWins / $totalClosed) * 100, 1) : 0;
        
        // Calculate TREG3 avg improvement from AM data (weighted by AM count)
        $totalImprovementSum = 0;
        foreach ($am_performance['benchmarking'] ?? [] as $am) {
            $totalImprovementSum += $am['change_avg'] ?? 0;
        }
        $avgImprovement = $totalAMs > 0 ? round($totalImprovementSum / $totalAMs, 2) : 0;
        
        // Calculate coverage
        $coverage = $totalCCs > 0 ? "$visitedCCCount/$totalCCs CC visited (total $totalOfferings product offerings)" : "-";
    @endphp

    <div class="key-metrics">
        <p><strong>Coverage Ratio</strong> : {{ $coverage }}</p>
        <p><strong>TREG 3 avg improvement</strong> : {{ $avgImprovement > 0 ? '+' : '' }}{{ number_format($avgImprovement, 2) }}%</p>
        <p><strong>Win Rate</strong> : {{ number_format($winRate, 1) }}% (Total {{ $totalWins }} wins and {{ $totalLosses }} loses)</p>
    </div>

    <h3>Witel Performance Movement (Progress and Result):</h3>
    
    @php
        // Group benchmarking data by Witel
        $witelData = [];
        $totalAllAMs = 0; // Track total AM count across all Witels
        
        foreach ($am_performance['benchmarking'] ?? [] as $am) {
            $witel = $am['witel'];
            if (!isset($witelData[$witel])) {
                $witelData[$witel] = [
                    'sum_avg_1' => 0,  // Sum of average (progress_1 + result_1)/2 for all AMs in this witel
                    'sum_avg_2' => 0,  // Sum of average (progress_2 + result_2)/2 for all AMs in this witel
                    'count' => 0
                ];
            }
            
            // Calculate average of progress and result for each AM at each date
            $avg1 = ($am['progress_1'] + $am['result_1']) / 2;
            $avg2 = ($am['progress_2'] + $am['result_2']) / 2;
            
            $witelData[$witel]['sum_avg_1'] += $avg1;
            $witelData[$witel]['sum_avg_2'] += $avg2;
            $witelData[$witel]['count']++;
            $totalAllAMs++;
        }
        
        // Calculate averages per Witel and improvement
        $witelDataWithAvg = [];
        $grandTotalAvg1 = 0; // Sum of ALL AM averages for date 1
        $grandTotalAvg2 = 0; // Sum of ALL AM averages for date 2
        $grandTotalImprovement = 0; // Sum of ALL AM improvements
        
        foreach ($witelData as $witel => $data) {
            $avgDate1 = $data['count'] > 0 ? $data['sum_avg_1'] / $data['count'] : 0;
            $avgDate2 = $data['count'] > 0 ? $data['sum_avg_2'] / $data['count'] : 0;
            $improvement = $avgDate2 - $avgDate1; // Improvement = kolom 3 - kolom 2
            
            // Accumulate TOTAL from all AMs (not just Witel averages)
            $grandTotalAvg1 += $data['sum_avg_1']; // Sum of individual AM values
            $grandTotalAvg2 += $data['sum_avg_2']; // Sum of individual AM values
            $grandTotalImprovement += ($data['sum_avg_2'] - $data['sum_avg_1']); // Total improvement
            
            $witelDataWithAvg[$witel] = [
                'avg_date_1' => $avgDate1,
                'avg_date_2' => $avgDate2,
                'improvement' => $improvement,
                'count' => $data['count']
            ];
        }
        
        // Sort by improvement descending
        uasort($witelDataWithAvg, function($a, $b) {
            return $b['improvement'] <=> $a['improvement'];
        });
        
        $witelData = $witelDataWithAvg;
        $witelCount = count($witelData);
    @endphp

    <table class="data-table">
        <thead>
            <tr>
                <th>Witel</th>
                <th class="center">{{ $snapshot_1['date'] }}</th>
                <th class="center">{{ $snapshot_2['date'] }}</th>
                <th class="center">Avg Improvement</th>
            </tr>
        </thead>
        <tbody>
            @foreach($witelData as $witel => $data)
            <tr>
                <td>{{ $witel }}</td>
                <td class="center">{{ $data['avg_date_1'] > 0 ? '+' : '' }}{{ number_format($data['avg_date_1'], 2) }}%</td>
                <td class="center">{{ $data['avg_date_2'] > 0 ? '+' : '' }}{{ number_format($data['avg_date_2'], 2) }}%</td>
                <td class="center">{{ $data['improvement'] > 0 ? '+' : '' }}{{ number_format($data['improvement'], 2) }}%</td>
            </tr>
            @endforeach
            <tr class="average-row">
                <td><strong>Average (from all AMs) :</strong></td>
                <td class="center">{{ $totalAllAMs > 0 ? '+' : '' }}{{ $totalAllAMs > 0 ? number_format($grandTotalAvg1 / $totalAllAMs, 2) : '0.00' }}%</td>
                <td class="center">{{ $totalAllAMs > 0 ? '+' : '' }}{{ $totalAllAMs > 0 ? number_format($grandTotalAvg2 / $totalAllAMs, 2) : '0.00' }}%</td>
                <td class="center">{{ $totalAllAMs > 0 ? '+' : '' }}{{ $totalAllAMs > 0 ? number_format($grandTotalImprovement / $totalAllAMs, 2) : '0.00' }}%</td>
            </tr>
        </tbody>
    </table>

    {{-- PAGE 2: SALES FORCE IMPROVEMENT --}}
    <div class="page-break"></div>

    <h2>II. Sales Force Improvement</h2>

    <h3>Top and Least Improvement Witel</h3>
    @php
        $metrics = $am_performance['witel_analysis']['metrics'] ?? [];
    @endphp
    <ul>
        @if(isset($metrics['most_witel']))
        <li>{{ $metrics['most_witel']['value'] }} : {{ $metrics['most_witel']['main_stat'] }}</li>
        @endif
        @if(isset($metrics['least_witel']))
        <li>{{ $metrics['least_witel']['value'] }} : {{ $metrics['least_witel']['main_stat'] }}</li>
        @endif
    </ul>

    <h3>Top 10 Improvement</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th class="no-col">No.</th>
                <th>Account Manager</th>
                <th>Witel</th>
                <th class="center">Improvement</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($am_performance['leaderboard'] ?? [], 0, 10) as $index => $am)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td style="max-width: 150px;">{{ $am['am'] }}</td>
                <td>{{ $am['witel'] }}</td>
                <td class="center">{{ $am['change_avg'] > 0 ? '+' : '' }}{{ number_format($am['change_avg'], 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $zeroMovementAMs = [];
        foreach ($am_performance['benchmarking'] ?? [] as $am) {
            if (($am['change_avg'] ?? 0) == 0) {
                $zeroMovementAMs[] = $am['am'];
            }
        }
    @endphp

    @if(count($zeroMovementAMs) > 0)
    <h3>Zero Movement (0% Improvement)</h3>
    <ul>
        @foreach(array_slice($zeroMovementAMs, 0, 10) as $am)
        <li>{{ $am }}</li>
        @endforeach
    </ul>
    @endif

    {{-- PAGE 3: PRODUCT MARKET FIT --}}
    <div class="page-break"></div>

    <h2>III. Product Market Fit</h2>

    @php
        $productMetrics = $product_performance['product_analysis']['metrics'] ?? [];
        $topProduct = $productMetrics['win_offerings']['value'] ?? '-';
        
        // Count stagnant, closed, not offered, and ACTIVE from product data
        $stagnantCount = 0;
        $closedCount = 0;
        $notOfferedCount = 0;
        $activeCount = 0;
        $totalProductOfferings = count($product_performance['products'] ?? []);
        
        // Track stagnant products by name for finding the top one
        $stagnantByProduct = [];
        
        // ✅ NEW: Track Submit SPH (progress_2 = 100%)
        $totalSubmitSPH = 0;
        $sphNegotiation = 0; // SPH but not closed yet
        $sphClosed = 0;      // SPH and closed (Win or Lose)
        
        foreach ($product_performance['products'] ?? [] as $product) {
            $result = strtolower($product['result'] ?? '');
            $progress2 = $product['progress_2'] ?? 0;
            $productName = $product['product'] ?? 'Unknown';
            
            // Detect Win/Lose status
            $isWin = strpos($result, 'win') !== false;
            $isLose = strpos($result, 'lose') !== false;
            $isClosed = $isWin || $isLose;
            
            // Count closed (win or lose)
            if ($isClosed) {
                $closedCount++;
            }
            
            // Count not offered (progress = 0)
            if ($progress2 == 0) {
                $notOfferedCount++;
            } else {
                // If offered and not closed = active
                if (!$isClosed) {
                    $activeCount++;
                }
            }
            
            // ✅ NEW: Count Submit SPH
            if ($progress2 >= 100) {
                $totalSubmitSPH++;
                if ($isClosed) {
                    $sphClosed++;
                } else {
                    $sphNegotiation++;
                }
            }
            
            // ✅ FIX: Count stagnant ONLY for non-closed offerings with zero improvement
            // Closed offerings (Win/Lose) should NOT be counted as stagnant
            $changeAvg = (($product['change_progress'] ?? 0) + ($product['change_result'] ?? 0)) / 2;
            if ($changeAvg == 0 && !$isClosed) {
                $stagnantCount++;
                if (!isset($stagnantByProduct[$productName])) {
                    $stagnantByProduct[$productName] = 0;
                }
                $stagnantByProduct[$productName]++;
            }
        }
        
        // Find top stagnant product
        arsort($stagnantByProduct);
        $topStagnantProduct = '';
        $topStagnantCount = 0;
        if (count($stagnantByProduct) > 0) {
            $topStagnantProduct = array_key_first($stagnantByProduct);
            $topStagnantCount = $stagnantByProduct[$topStagnantProduct];
        }
    @endphp

    <h3>Product Highlights</h3>
    <ul>
        <li>Total Submit SPH: {{ $totalSubmitSPH }} ({{ $sphNegotiation }} in negotiation, {{ $sphClosed }} closed)</li>
        <li>Total Offerings: {{ $activeCount }} active, {{ $notOfferedCount }} not offered, and {{ $closedCount }} closed</li>
        <li>Stagnant Offerings : {{ $stagnantCount }} offerings with 0% improvement</li>
        <li>Most Stagnant Product : {{ $topStagnantProduct }} ({{ $topStagnantCount }} offerings with 0% improvement)</li>
    </ul>

    <h3>Top 10 Best-Selling Products</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th class="no-col">No.</th>
                <th>Product</th>
                <th class="center">Total Offerings</th>
                <th class="center">Win</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($product_performance['product_leaderboard']['top_10'] ?? [], 0, 10) as $index => $product)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td style="max-width: 200px;">{{ $product['product'] }}</td>
                <td class="center">{{ $product['total_offerings'] ?? 0 }}</td>
                <td class="center">{{ $product['wins'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>