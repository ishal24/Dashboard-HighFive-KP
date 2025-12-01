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
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #374151;
        }

        .header {
            background: linear-gradient(135deg, #ea1d25 0%, #c41e24 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 12px;
            opacity: 0.9;
        }

        .meta-info {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            font-weight: bold;
            color: #6b7280;
        }

        .meta-value {
            color: #374151;
            font-weight: 600;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #ea1d25;
            margin: 20px 0 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ea1d25;
        }

        .analysis-cards {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .analysis-card {
            flex: 1;
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .analysis-card h4 {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .analysis-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #ea1d25;
            margin-bottom: 4px;
        }

        .analysis-card .label {
            font-size: 10px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        table thead {
            background: #f3f4f6;
        }

        table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #374151;
            border: 1px solid #e5e7eb;
            font-size: 9px;
            text-transform: uppercase;
        }

        table td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }

        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .progress-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ea1d25, #f04851);
            border-radius: 3px;
        }

        .progress-fill.result {
            background: linear-gradient(90deg, #059669, #10b981);
        }

        .change-positive {
            color: #059669;
            font-weight: bold;
        }

        .change-negative {
            color: #dc2626;
            font-weight: bold;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .leaderboard-item.gold {
            background: #fef9e7;
            border-color: #f5c542;
        }

        .leaderboard-item.silver {
            background: #f3f4f6;
            border-color: #9fa6b2;
        }

        .leaderboard-item.bronze {
            background: #fef3c7;
            border-color: #c07a2b;
        }

        .leaderboard-rank {
            font-size: 16px;
            font-weight: bold;
            min-width: 40px;
            text-align: center;
            color: #6b7280;
        }

        .leaderboard-item.gold .leaderboard-rank {
            color: #f5c542;
        }

        .leaderboard-item.silver .leaderboard-rank {
            color: #9fa6b2;
        }

        .leaderboard-item.bronze .leaderboard-rank {
            color: #c07a2b;
        }

        .leaderboard-info {
            flex: 1;
            margin-left: 10px;
        }

        .leaderboard-name {
            font-weight: bold;
            color: #374151;
            font-size: 11px;
        }

        .leaderboard-detail {
            font-size: 9px;
            color: #6b7280;
        }

        .leaderboard-score {
            font-size: 14px;
            font-weight: bold;
            color: #ea1d25;
            min-width: 60px;
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }

        .page-break {
            page-break-after: always;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mb-10 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Page 1: Cover & AM Performance -->
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>

    <div class="meta-info">
        <div class="meta-row">
            <span class="meta-label">Tanggal Laporan:</span>
            <span class="meta-value">{{ $generated_at }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Divisi:</span>
            <span class="meta-value">{{ $divisi }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Dataset Pertama:</span>
            <span class="meta-value">{{ $snapshot_1['name'] }} ({{ $snapshot_1['date'] }})</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Dataset Kedua:</span>
            <span class="meta-value">{{ $snapshot_2['name'] }} ({{ $snapshot_2['date'] }})</span>
        </div>
    </div>

    <div class="section-title">PERFORMA ACCOUNT MANAGER (AM LEVEL)</div>

    <div class="analysis-cards">
        @if(isset($am_performance['witel_analysis']['cards']['dataset_2']['most_progress']))
        <div class="analysis-card">
            <h4>Most Progress</h4>
            <div class="value">{{ $am_performance['witel_analysis']['cards']['dataset_2']['most_progress']['witel'] }}</div>
            <div class="label">{{ number_format($am_performance['witel_analysis']['cards']['dataset_2']['most_progress']['avg_progress'], 2) }}%</div>
        </div>
        @endif

        @if(isset($am_performance['witel_analysis']['cards']['dataset_2']['least_progress']))
        <div class="analysis-card">
            <h4>Least Progress</h4>
            <div class="value">{{ $am_performance['witel_analysis']['cards']['dataset_2']['least_progress']['witel'] }}</div>
            <div class="label">{{ number_format($am_performance['witel_analysis']['cards']['dataset_2']['least_progress']['avg_progress'], 2) }}%</div>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Witel</th>
                <th>Account Manager</th>
                <th>Avg Progress<br>({{ $snapshot_1['date'] }})</th>
                <th>Avg Progress<br>({{ $snapshot_2['date'] }})</th>
                <th>Avg Result<br>({{ $snapshot_1['date'] }})</th>
                <th>Avg Result<br>({{ $snapshot_2['date'] }})</th>
                <th>Change</th>
            </tr>
        </thead>
        <tbody>
            @foreach($am_performance['benchmarking'] as $row)
            <tr>
                <td>{{ $row['witel'] }}</td>
                <td><strong>{{ $row['am'] }}</strong></td>
                <td>{{ number_format($row['progress_1'], 2) }}%</td>
                <td>{{ number_format($row['progress_2'], 2) }}%</td>
                <td>{{ number_format($row['result_1'], 2) }}%</td>
                <td>{{ number_format($row['result_2'], 2) }}%</td>
                <td class="{{ $row['change_progress'] >= 0 ? 'change-positive' : 'change-negative' }}">
                    {{ $row['change_progress'] >= 0 ? '+' : '' }}{{ number_format($row['change_progress'], 2) }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">LEADERBOARD ACCOUNT MANAGER (TOP 10)</div>

    @foreach(array_slice($am_performance['leaderboard'], 0, 10) as $index => $am)
    @php
        $rank = $index + 1;
        $class = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : ''));
    @endphp
    <div class="leaderboard-item {{ $class }}">
        <div class="leaderboard-rank">{{ $rank }}</div>
        <div class="leaderboard-info">
            <div class="leaderboard-name">{{ $am['am'] }}</div>
            <div class="leaderboard-detail">{{ $am['witel'] }}</div>
        </div>
        <div class="leaderboard-score">{{ number_format($am['progress_2'], 2) }}%</div>
    </div>
    @endforeach

    <div class="page-break"></div>

    <!-- Page 2: Product Performance -->
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>

    <div class="section-title">PERFORMA PER PRODUK (PRODUCT LEVEL)</div>

    <div class="analysis-cards">
        @if(isset($product_performance['statistics']['coverage']))
        <div class="analysis-card">
            <h4>Corporate Customer Visited</h4>
            <div class="value">{{ $product_performance['statistics']['coverage']['visited'] }}/{{ $product_performance['statistics']['coverage']['total'] }}</div>
            <div class="label">{{ number_format($product_performance['statistics']['coverage']['percentage'], 2) }}% Coverage</div>
        </div>
        @else
        <div class="analysis-card">
            <h4>Corporate Customer Visited</h4>
            <div class="value">-</div>
            <div class="label">-</div>
        </div>
        @endif

        @if(isset($product_performance['statistics']['am_without_progress']))
        <div class="analysis-card">
            <h4>AM Tanpa Progress</h4>
            <div class="value">{{ $product_performance['statistics']['am_without_progress'] }}</div>
            <div class="label">Account Manager</div>
        </div>
        @else
        <div class="analysis-card">
            <h4>AM Tanpa Progress</h4>
            <div class="value">-</div>
            <div class="label">Account Manager</div>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>AM</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Progress<br>({{ $snapshot_1['date'] }})</th>
                <th>Progress<br>({{ $snapshot_2['date'] }})</th>
                <th>Result<br>({{ $snapshot_1['date'] }})</th>
                <th>Result<br>({{ $snapshot_2['date'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($product_performance['products'], 0, 30) as $row)
            <tr>
                <td>{{ $row['am'] }}</td>
                <td>{{ $row['customer'] ?? '-' }}</td>
                <td>{{ $row['product'] }}</td>
                <td>{{ $row['progress_1'] }}%</td>
                <td>{{ $row['progress_2'] }}%</td>
                <td>{{ $row['result_1'] }}%</td>
                <td>{{ $row['result_2'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($product_performance['products']) > 30)
    <p class="text-center mt-10"><em>Menampilkan 30 dari {{ count($product_performance['products']) }} data. Data lengkap dapat diakses di dashboard.</em></p>
    @endif

    <div class="section-title mt-10">LEADERBOARD PRODUK (TOP 10)</div>

    @foreach(array_slice($product_performance['product_leaderboard']['top_10'] ?? [], 0, 10) as $index => $product)
    @php
        $rank = $index + 1;
        $class = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : ''));
    @endphp
    <div class="leaderboard-item {{ $class }}">
        <div class="leaderboard-rank">{{ $rank }}</div>
        <div class="leaderboard-info">
            <div class="leaderboard-name">{{ $product['product'] }}</div>
            <div class="leaderboard-detail">{{ $product['total_offerings'] ?? 0 }} penawaran</div>
        </div>
        <div class="leaderboard-score">{{ number_format($product['avg_progress'] ?? 0, 2) }}%</div>
    </div>
    @endforeach

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem High Five RLEGS TR3</p>
        <p>Telkom Regional 3 - {{ $generated_at }}</p>
    </div>
</body>
</html>