@extends('layouts.main')

@section('title', 'Detail Account Manager')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<link rel="stylesheet" href="{{ asset('css/detailAM.css') }}">
@endsection

@section('content')
<div class="main-content">
    <!-- Profile Overview -->
    <div class="profile-overview">
        <div class="profile-avatar-container">
            <img src="{{ asset($accountManager->user && $accountManager->user->profile_image ? 'storage/'.$accountManager->user->profile_image : 'img/profile.png') }}"
                 class="profile-avatar" alt="{{ $accountManager->nama }}">
        </div>
        <div class="profile-details">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="profile-name mb-0">{{ $accountManager->nama }}</h2>

                <!-- Category Badge -->
                @php
                    $divisiCount = $accountManager->divisis->count();
                    $hasGovernment = $accountManager->divisis->where('kode', 'DGS')->count() > 0;
                    $hasEnterprise = $accountManager->divisis->whereIn('kode', ['DPS', 'DSS'])->count() > 0;

                    $badgeClass = 'enterprise';
                    $badgeLabel = 'ENTERPRISE';
                    $badgeIcon = 'fa-building';

                    if ($divisiCount > 1 && $hasGovernment && $hasEnterprise) {
                        $badgeClass = 'multi';
                        $badgeLabel = 'MULTI DIVISION';
                        $badgeIcon = 'fa-layer-group';
                    } elseif ($hasGovernment) {
                        $badgeClass = 'government';
                        $badgeLabel = 'GOVERNMENT';
                        $badgeIcon = 'fa-university';
                    }
                @endphp
                <span class="category-badge {{ $badgeClass }}">
                    <i class="fas {{ $badgeIcon }}"></i>
                    {{ $badgeLabel }}
                </span>
            </div>
            <div class="profile-meta">
                <div class="meta-item">
                    <i class="lni lni-id-card"></i>
                    <span>NIK: {{ $accountManager->nik }}</span>
                </div>
                <div class="meta-item">
                    <i class="lni lni-map-marker"></i>
                    <span>WITEL: {{ $accountManager->witel->nama ?? 'N/A' }}</span>
                </div>
                <div class="meta-item divisi-item">
                    <i class="lni lni-network"></i>
                    <span>DIVISI:</span>
                    <div class="divisi-list">
                        @forelse($accountManager->divisis as $divisi)
                            @php
                                $divisiClass = strtolower($divisi->kode);
                            @endphp
                            <span class="divisi-pill {{ $divisiClass }}">{{ $divisi->kode }}</span>
                        @empty
                            <span class="text-muted">N/A</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Division Selector Section (for multi-divisi AM) -->
    @if($profileData['has_government'] && $profileData['has_enterprise'])
    <div class="division-selector-section">
        <div class="division-selector-label">
            <i class="fas fa-layer-group"></i>
            Pilih Divisi Umum untuk Peringkat:
        </div>
        <div class="division-selector">
            <select id="division-ranking-select" class="selectpicker" title="Pilih Divisi Umum" data-style="btn-outline-primary">
                @foreach($profileData['divisi_umum_list'] as $divisiUmum)
                    <option value="{{ $divisiUmum['code'] }}"
                            {{ $filters['divisi_umum'] == $divisiUmum['code'] ? 'selected' : '' }}>
                        {{ $divisiUmum['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    <!-- Rankings - 3 Cards -->
    <div class="rankings-container">
        @php
            $rankingData = $rankingData ?? [];
            $globalRanking = $rankingData['global'] ?? ['rank' => null, 'total' => 0, 'status' => 'unknown', 'change' => 0];
            $witelRanking = $rankingData['witel'] ?? ['rank' => null, 'total' => 0, 'status' => 'unknown', 'change' => 0];
            $divisiUmumRankings = $rankingData['divisi_umum'] ?? [];
            $activeDivisiUmum = $rankingData['active_divisi_umum'] ?? null;

            // Icon mapping
            $getIcon = function($rank) {
                if (!$rank) return 'up100.svg';
                if ($rank <= 10) return '1-10.svg';
                if ($rank <= 50) return '10-50.svg';
                return 'up100.svg';
            };

            // Badge mapping
            $getBadgeInfo = function($status, $change) {
                if ($status === 'naik' && $change > 0) {
                    return ['class' => 'up', 'icon' => 'lni-arrow-up', 'text' => 'Naik ' . $change];
                } elseif ($status === 'turun' && $change < 0) {
                    return ['class' => 'down', 'icon' => 'lni-arrow-down', 'text' => 'Turun ' . abs($change)];
                } else {
                    return ['class' => 'neutral', 'icon' => 'lni-minus', 'text' => 'Tetap'];
                }
            };

            // Determine category for leaderboard filter
            $getCategoryFilter = function() use ($hasGovernment, $hasEnterprise, $divisiCount) {
                if ($divisiCount > 1 && $hasGovernment && $hasEnterprise) {
                    // Multi divisi - use active divisi umum
                    return null; // Will be determined per card
                } elseif ($hasGovernment && !$hasEnterprise) {
                    return 'government';
                } elseif ($hasEnterprise && !$hasGovernment) {
                    return 'enterprise';
                }
                return null;
            };

            $categoryFilter = $getCategoryFilter();
        @endphp

        <div class="row">
            <!-- Card 1: Global Ranking - CLICKABLE -->
            <div class="col-md-4 mb-3">
                <a href="{{ route('leaderboard') }}" class="ranking-card global">
                    <div class="ranking-icon">
                        <img src="{{ asset('img/' . $getIcon($globalRanking['rank'])) }}" alt="Peringkat" width="36" height="36">
                    </div>
                    <div class="ranking-info">
                        <div class="ranking-title">Peringkat Global</div>
                        <div class="ranking-value">
                            {{ $globalRanking['rank'] ?? 'N/A' }} dari {{ $globalRanking['total'] }}
                        </div>
                        @php $badge = $getBadgeInfo($globalRanking['status'], $globalRanking['change']); @endphp
                        <span class="rank-change-detail {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                    </div>
                    @if($globalRanking['change'] != 0)
                        <div class="rank-badge {{ $badge['class'] }}">
                            <i class="lni {{ $badge['icon'] }}"></i>
                            {{ $badge['text'] }}
                        </div>
                    @endif
                </a>
            </div>

            <!-- Card 2: Witel Ranking - NOW CLICKABLE -->
            <div class="col-md-4 mb-3">
                <a href="{{ route('leaderboard') }}?witel_filter%5B%5D={{ $accountManager->witel_id }}" class="ranking-card witel">
                    <div class="ranking-icon">
                        <img src="{{ asset('img/' . $getIcon($witelRanking['rank'])) }}" alt="Peringkat" width="36" height="36">
                    </div>
                    <div class="ranking-info">
                        <div class="ranking-title">Peringkat Witel</div>
                        <div class="ranking-value">
                            {{ $witelRanking['rank'] ?? 'N/A' }} dari {{ $witelRanking['total'] }}
                        </div>
                        @php $badge = $getBadgeInfo($witelRanking['status'], $witelRanking['change']); @endphp
                        <span class="rank-change-detail {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                    </div>
                    @if($witelRanking['change'] != 0)
                        <div class="rank-badge {{ $badge['class'] }}">
                            <i class="lni {{ $badge['icon'] }}"></i>
                            {{ $badge['text'] }}
                        </div>
                    @endif
                </a>
            </div>

            <!-- Card 3: Division Ranking (Divisi Umum) - NOW CLICKABLE -->
            <div class="col-md-4 mb-3">
                <div class="division-rankings-container">
                    @if(count($divisiUmumRankings) > 0 && $activeDivisiUmum)
                        @php
                            $ranking = $divisiUmumRankings[$activeDivisiUmum] ?? null;
                            // Convert GOVERNMENT/ENTERPRISE to lowercase for leaderboard filter
                            $categoryForLeaderboard = strtolower($activeDivisiUmum);
                        @endphp

                        @if($ranking)
                            @php
                                $badge = $getBadgeInfo($ranking['status'] ?? 'tetap', $ranking['change'] ?? 0);
                            @endphp

                            <a href="{{ route('leaderboard') }}?category_filter%5B%5D={{ $categoryForLeaderboard }}" class="ranking-card division division-rank-card" data-divisi-umum="{{ $activeDivisiUmum }}">
                                <div class="ranking-icon">
                                    <img src="{{ asset('img/' . $getIcon($ranking['rank'])) }}" alt="Peringkat" width="36" height="36">
                                </div>
                                <div class="ranking-info">
                                    <div class="ranking-title">
                                        Peringkat Divisi
                                        <span class="divisi-badge {{ strtolower($activeDivisiUmum) }}">
                                            {{ $activeDivisiUmum }}
                                        </span>
                                    </div>
                                    <div class="ranking-value">
                                        {{ $ranking['rank'] ?? 'N/A' }} dari {{ $ranking['total'] ?? 0 }}
                                    </div>
                                    <span class="rank-change-detail {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                                </div>
                                @if(($ranking['change'] ?? 0) != 0)
                                    <div class="rank-badge {{ $badge['class'] }}">
                                        <i class="lni {{ $badge['icon'] }}"></i>
                                        {{ $badge['text'] }}
                                    </div>
                                @endif
                            </a>
                        @else
                            <div class="ranking-card division">
                                <div class="ranking-icon">
                                    <img src="{{ asset('img/up100.svg') }}" alt="Peringkat" width="36" height="36">
                                </div>
                                <div class="ranking-info">
                                    <div class="ranking-title">Peringkat Divisi</div>
                                    <div class="ranking-value">N/A</div>
                                    <span class="rank-change-detail neutral">Tidak ada data</span>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="ranking-card division">
                            <div class="ranking-icon">
                                <img src="{{ asset('img/up100.svg') }}" alt="Peringkat" width="36" height="36">
                            </div>
                            <div class="ranking-info">
                                <div class="ranking-title">Peringkat Divisi</div>
                                <div class="ranking-value">N/A</div>
                                <span class="rank-change-detail neutral">Belum ada data</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Content Tabs -->
    <div class="content-wrapper">
        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-button active" data-tab="customer-data">
                <i class="fas fa-users"></i> Data Pelanggan
            </button>
            <button class="tab-button" data-tab="performance-analysis">
                <i class="fas fa-chart-line"></i> Analisis Performa
            </button>
        </div>

        <!-- Tab Content - Customer Data -->
        <div id="customer-data" class="tab-content active">
            <div class="tab-content-header">
                <div class="tab-content-title">
                    <i class="fas fa-users"></i> Data Pelanggan & Revenue
                </div>

                <div class="filters-container">
                    <!-- View Mode Toggle -->
                    <div class="view-mode-toggle">
                        <button type="button" class="view-mode-btn {{ $filters['customer_view_mode'] == 'detail' ? 'active' : '' }}" data-mode="detail">
                            <i class="fas fa-list-ul"></i> Detail
                        </button>
                        <button type="button" class="view-mode-btn {{ $filters['customer_view_mode'] == 'agregat_cc' ? 'active' : '' }}" data-mode="agregat_cc">
                            <i class="fas fa-users"></i> Agregat CC
                        </button>
                        <button type="button" class="view-mode-btn {{ $filters['customer_view_mode'] == 'agregat_bulan' ? 'active' : '' }}" data-mode="agregat_bulan">
                            <i class="fas fa-calendar"></i> Agregat Bulan
                        </button>
                    </div>

                    <!-- Division Filter (for multi-divisi AM) -->
                    @if($accountManager->divisis->count() > 1)
                    <div class="filter-group">
                        <select id="customerDivisiFilter" class="selectpicker" title="Pilih Divisi">
                            <option value="">Semua Divisi</option>
                            @foreach($accountManager->divisis as $divisi)
                                <option value="{{ $divisi->id }}" {{ $filters['divisi_id'] == $divisi->id ? 'selected' : '' }}>
                                    {{ $divisi->kode }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="two-filters">
                        <div class="filter-group">
                            <select id="tipeRevenueFilter" class="selectpicker" title="Tipe Revenue">
                                <option value="all" {{ $filters['tipe_revenue']=='all'?'selected':'' }}>Semua Tipe</option>
                                <option value="REGULER" {{ $filters['tipe_revenue']=='REGULER'?'selected':'' }}>REGULER</option>
                                <option value="NGTMA" {{ $filters['tipe_revenue']=='NGTMA'?'selected':'' }}>NGTMA</option>
                            </select>
                        </div>

                        <div class="filter-group year-selector">
                            <select id="customerYearFilter" class="selectpicker" title="Tahun">
                                @foreach($filterOptions['available_years'] as $year)
                                    <option value="{{ $year }}" {{ $filters['tahun']==$year?'selected':'' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Table -->
            <div class="data-card">
                @if($customerData && $customerData['customers']->count() > 0)
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Customer</th>
                                    <th>NIPNAS</th>
                                    @if($filters['customer_view_mode'] != 'agregat_bulan')
                                        <th>Divisi</th>
                                        <th>Segment</th>
                                    @endif
                                    <th>Target Revenue</th>
                                    <th>Real Revenue</th>
                                    <th>Achievement</th>
                                    @if($filters['customer_view_mode'] == 'detail')
                                        <th>Bulan</th>
                                    @elseif($filters['customer_view_mode'] == 'agregat_bulan')
                                        <th>Bulan</th>
                                        <th>Jumlah CC</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customerData['customers'] as $index => $customer)
                                    <tr>
                                        <td class="row-number">{{ $index + 1 }}</td>
                                        @if($filters['customer_view_mode'] == 'agregat_bulan')
                                            <td colspan="2" class="customer-name">{{ $customer->bulan_name ?? 'N/A' }}</td>
                                        @else
                                            <td class="customer-name">{{ $customer->customer_name ?? $customer->nama ?? 'N/A' }}</td>
                                            <td class="nipnas">{{ $customer->nipnas ?? 'N/A' }}</td>
                                        @endif
                                        @if($filters['customer_view_mode'] != 'agregat_bulan')
                                            <td class="customer-divisi">{{ $customer->divisi ?? 'N/A' }}</td>
                                            <td class="customer-segment">{{ $customer->segment ?? 'N/A' }}</td>
                                        @endif
                                        <td class="revenue-value">Rp {{ number_format($customer->total_target ?? $customer->target ?? 0, 0, ',', '.') }}</td>
                                        <td class="revenue-value">Rp {{ number_format($customer->total_revenue ?? $customer->revenue ?? 0, 0, ',', '.') }}</td>
                                        <td>
                                            @php
                                                $achievement = $customer->achievement_rate ?? $customer->achievement ?? 0;
                                                $badgeClass = 'badge-danger';
                                                if ($achievement >= 100) {
                                                    $badgeClass = 'badge-success';
                                                } elseif ($achievement >= 80) {
                                                    $badgeClass = 'badge-warning';
                                                }
                                            @endphp
                                            <span class="achievement-badge {{ $badgeClass }}">
                                                {{ number_format($achievement, 2) }}%
                                            </span>
                                        </td>
                                        @if($filters['customer_view_mode'] == 'detail')
                                            <td>
                                                <span class="month-badge">{{ $customer->bulan_name ?? 'N/A' }}</span>
                                            </td>
                                        @elseif($filters['customer_view_mode'] == 'agregat_bulan')
                                            <td>
                                                <span class="month-badge">{{ $customer->bulan_name ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-center">{{ $customer->customer_count ?? 0 }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <p class="empty-text">Tidak ada data pelanggan untuk filter yang dipilih</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tab Content - Performance Analysis -->
        <div id="performance-analysis" class="tab-content">
            <div class="tab-content-header">
                <div class="tab-content-title">
                    <i class="fas fa-chart-line"></i> Analisis Performa
                </div>

                <!-- Division Filter for Performance (for multi-divisi AM) -->
                @if($accountManager->divisis->count() > 1)
                <div class="filters-container">
                    <div class="filter-group">
                        <select id="performanceDivisiFilter" class="selectpicker" title="Pilih Divisi">
                            <option value="">Semua Divisi</option>
                            @foreach($accountManager->divisis as $divisi)
                                <option value="{{ $divisi->id }}" {{ $filters['divisi_id'] == $divisi->id ? 'selected' : '' }}>
                                    {{ $divisi->kode }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
            </div>

            <!-- Revenue Summary Cards -->
            <div class="revenue-cards-group">
                <div class="revenue-summary-card total-revenue">
                    <div class="revenue-card-header">
                        <div class="revenue-card-title">Total Revenue</div>
                        <div class="revenue-card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="revenue-card-value">
                        Rp {{ number_format($cardData['total_revenue'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="revenue-card-period">{{ $cardData['period_text'] ?? 'Periode saat ini' }}</div>
                </div>

                <div class="revenue-summary-card total-target">
                    <div class="revenue-card-header">
                        <div class="revenue-card-title">Total Target</div>
                        <div class="revenue-card-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                    <div class="revenue-card-value">
                        Rp {{ number_format($cardData['total_target'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="revenue-card-period">{{ $cardData['period_text'] ?? 'Periode saat ini' }}</div>
                </div>

                <div class="revenue-summary-card achievement-rate">
                    <div class="revenue-card-header">
                        <div class="revenue-card-title">Achievement Rate</div>
                        <div class="revenue-card-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <div class="revenue-card-value">
                        {{ number_format($cardData['achievement_rate'] ?? 0, 2) }}%
                    </div>
                    <div class="revenue-card-period">{{ $cardData['period_text'] ?? 'Periode saat ini' }}</div>
                </div>
            </div>

            <!-- Performance Summary -->
            @if(isset($performanceAnalysis['summary']))
            <div class="insight-summary-card">
                <div class="insight-header">
                    <i class="fas fa-lightbulb"></i>
                    <h4>Ringkasan Performa</h4>
                </div>
                <div class="insight-body">
                    @php
                        $summary = $performanceAnalysis['summary'];
                        $avgAch = $summary['average_achievement'] ?? 0;
                        $trend = $summary['trend'] ?? 'stabil';
                    @endphp
                    <p><strong>{{ $accountManager->nama }}</strong> menunjukkan performa yang
                        @if($avgAch >= 90) <strong class="text-success">sangat baik</strong>
                        @elseif($avgAch >= 80) <strong class="text-warning">baik</strong>
                        @else <strong class="text-danger">perlu ditingkatkan</strong>
                        @endif
                        dengan rata-rata achievement <strong>{{ number_format($avgAch, 2) }}%</strong>.
                    </p>
                    <p>Total revenue sepanjang waktu mencapai <strong>Rp {{ number_format($summary['total_revenue_all_time'] ?? 0, 0, ',', '.') }}</strong>
                        dari target <strong>Rp {{ number_format($summary['total_target_all_time'] ?? 0, 0, ',', '.') }}</strong>.
                        Tren performa menunjukkan kondisi <strong class="{{ $trend == 'naik' ? 'text-success' : ($trend == 'turun' ? 'text-danger' : 'text-muted') }}">{{ $trend }}</strong>
                        dalam 3 bulan terakhir.</p>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="insight-metrics">
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">Achievement Tertinggi</div>
                        <div class="metric-value">{{ number_format($summary['highest_achievement']['value'] ?? 0, 2) }}%</div>
                        <div class="metric-period">{{ $summary['highest_achievement']['bulan'] ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">Revenue Tertinggi</div>
                        <div class="metric-value">Rp {{ number_format($summary['highest_revenue']['value'] ?? 0, 0, ',', '.') }}</div>
                        <div class="metric-period">{{ $summary['highest_revenue']['bulan'] ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">Rata-rata Achievement</div>
                        <div class="metric-value">{{ number_format($summary['average_achievement'] ?? 0, 2) }}%</div>
                        <div class="metric-period">{{ $summary['scope_description'] ?? 'Periode saat ini' }}</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">
                        @if($trend == 'naik')
                            <i class="fas fa-arrow-up text-success"></i>
                        @elseif($trend == 'turun')
                            <i class="fas fa-arrow-down text-danger"></i>
                        @else
                            <i class="fas fa-minus text-muted"></i>
                        @endif
                    </div>
                    <div class="metric-content">
                        <div class="metric-label">Tren Performa</div>
                        <div class="metric-value">
                            @if($trend == 'naik')
                                <span class="text-success">Meningkat</span>
                            @elseif($trend == 'turun')
                                <span class="text-danger">Menurun</span>
                            @else
                                <span class="text-muted">Stabil</span>
                            @endif
                        </div>
                        <div class="metric-period">{{ $summary['trend_description'] ?? '3 bulan terakhir' }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Performance Chart -->
            @if(isset($performanceAnalysis['monthly_chart']))
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Grafik Performa Bulanan
                    </h4>

                    <div class="chart-filters">
                        <div class="filter-group">
                            <select id="chartDisplayMode" class="selectpicker" title="Tampilan">
                                <option value="combination" {{ $filters['chart_display'] == 'combination' ? 'selected' : '' }}>Kombinasi</option>
                                <option value="revenue" {{ $filters['chart_display'] == 'revenue' ? 'selected' : '' }}>Revenue</option>
                                <option value="achievement" {{ $filters['chart_display'] == 'achievement' ? 'selected' : '' }}>Achievement</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <select id="chartYearFilter" class="selectpicker" title="Tahun">
                                @foreach($filterOptions['available_years'] as $year)
                                    <option value="{{ $year }}" {{ $filters['chart_tahun'] == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="chart-canvas-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@section('scripts')
<!-- Bootstrap Select -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/js/bootstrap-select.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Initialize Bootstrap Select
    $('.selectpicker').selectpicker({
        liveSearch: true,
        liveSearchPlaceholder: 'Cari...',
        size: 6,
        mobile: false
    });

    // Tab Navigation
    $('.tab-button').on('click', function() {
        $('.tab-button').removeClass('active');
        $('.tab-content').removeClass('active');

        $(this).addClass('active');
        const tabId = $(this).data('tab');
        $('#' + tabId).addClass('active');

        // Render chart when switching to performance tab
        if (tabId === 'performance-analysis') {
            setTimeout(renderPerformanceChart, 100);
        }
    });

    // View Mode Toggle
    $('.view-mode-btn').on('click', function() {
        const mode = $(this).data('mode');
        updateUrlParameter('customer_view_mode', mode);
    });

    // Division Ranking Selector (untuk multi-divisi dengan GOVERNMENT & ENTERPRISE)
    $('#division-ranking-select').on('changed.bs.select', function() {
        const divisiUmum = $(this).val();

        console.log('Selected Divisi Umum:', divisiUmum);

        // Update URL dengan divisi_umum parameter
        updateUrlParameter('divisi_umum', divisiUmum);
    });

    // Customer Divisi Filter
    $('#customerDivisiFilter').on('changed.bs.select', function() {
        const divisiId = $(this).val();
        updateUrlParameter('divisi_id', divisiId);
    });

    // Performance Divisi Filter
    $('#performanceDivisiFilter').on('changed.bs.select', function() {
        const divisiId = $(this).val();
        updateUrlParameter('divisi_id', divisiId);
    });

    // Tipe Revenue Filter
    $('#tipeRevenueFilter').on('changed.bs.select', function() {
        const tipeRevenue = $(this).val();
        updateUrlParameter('tipe_revenue', tipeRevenue);
    });

    // Customer Year Filter
    $('#customerYearFilter').on('changed.bs.select', function() {
        const year = $(this).val();
        updateUrlParameter('tahun', year);
    });

    // Chart Year Filter
    $('#chartYearFilter').on('changed.bs.select', function() {
        const year = $(this).val();
        updateUrlParameter('chart_tahun', year);
    });

    // Chart Display Mode
    $('#chartDisplayMode').on('changed.bs.select', function() {
        const mode = $(this).val();
        updateUrlParameter('chart_display', mode);
    });

    // Helper function to update URL parameters
    function updateUrlParameter(key, value) {
        const url = new URL(window.location.href);

        if (value && value !== '' && value !== 'all') {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }

        window.location.href = url.toString();
    }

    // Render Performance Chart
    function renderPerformanceChart() {
        const ctx = document.getElementById('performanceChart');
        if (!ctx) {
            console.warn('Performance chart canvas not found');
            return;
        }

        // Destroy existing chart if exists
        try {
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
        } catch (e) {
            console.warn('Error destroying existing chart:', e);
        }

        // Get chart data from backend
        const chartData = @json($performanceAnalysis['monthly_chart'] ?? null);

        if (!chartData || !chartData.labels || chartData.labels.length === 0) {
            $(ctx).parent().html(
                '<div class="empty-state">' +
                '<div class="empty-icon"><i class="fas fa-chart-bar"></i></div>' +
                '<p class="empty-text">Tidak ada data performa untuk ditampilkan</p>' +
                '</div>'
            );
            return;
        }

        const displayMode = '{{ $filters["chart_display"] ?? "combination" }}';
        const datasets = [];

        // Revenue datasets
        if (displayMode === 'combination' || displayMode === 'revenue') {
            datasets.push({
                label: 'Real Revenue',
                data: chartData.datasets.real_revenue,
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                yAxisID: 'y'
            });

            datasets.push({
                label: 'Target Revenue',
                data: chartData.datasets.target_revenue,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                yAxisID: 'y'
            });
        }

        // Achievement dataset
        if (displayMode === 'combination' || displayMode === 'achievement') {
            datasets.push({
                label: 'Achievement (%)',
                data: chartData.datasets.achievement_rate,
                type: 'line',
                backgroundColor: 'rgba(234, 29, 37, 0.1)',
                borderColor: 'rgba(234, 29, 37, 1)',
                borderWidth: 3,
                pointBackgroundColor: 'rgba(234, 29, 37, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(234, 29, 37, 1)',
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            });
        }

        // Configure scales
        const scales = {};

        if (displayMode === 'combination' || displayMode === 'revenue') {
            scales.y = {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (Rp)',
                    font: {
                        weight: 'bold',
                        size: 12
                    },
                    color: '#4b5563'
                },
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000000) {
                            return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
                        } else if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                        } else if (value >= 1000) {
                            return 'Rp ' + (value / 1000).toFixed(0) + ' K';
                        }
                        return 'Rp ' + value;
                    },
                    color: '#6b7280'
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            };
        }

        if (displayMode === 'combination' || displayMode === 'achievement') {
            scales.y1 = {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Achievement (%)',
                    font: {
                        weight: 'bold',
                        size: 12
                    },
                    color: '#4b5563'
                },
                grid: {
                    drawOnChartArea: displayMode !== 'combination',
                    color: 'rgba(234, 29, 37, 0.1)'
                },
                ticks: {
                    callback: function(value) {
                        return value.toFixed(0) + '%';
                    },
                    color: '#6b7280'
                }
            };
        }

        // Create chart
        try {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: scales,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 16,
                                font: {
                                    size: 12,
                                    weight: '600'
                                },
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.95)',
                            titleFont: {
                                weight: 'bold',
                                size: 13
                            },
                            bodyFont: {
                                size: 12
                            },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';

                                    if (label) {
                                        label += ': ';
                                    }

                                    if (context.dataset.yAxisID === 'y1') {
                                        // Achievement percentage
                                        label += context.parsed.y.toFixed(2) + '%';
                                    } else {
                                        // Revenue
                                        const value = context.parsed.y;
                                        if (value >= 1000000000) {
                                            label += 'Rp ' + (value / 1000000000).toFixed(2) + ' Miliar';
                                        } else if (value >= 1000000) {
                                            label += 'Rp ' + (value / 1000000).toFixed(2) + ' Juta';
                                        } else if (value >= 1000) {
                                            label += 'Rp ' + (value / 1000).toFixed(0) + ' Ribu';
                                        } else {
                                            label += 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                        }
                                    }

                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error creating chart:', e);
            $(ctx).parent().html(
                '<div class="alert alert-danger mt-3">' +
                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                'Terjadi kesalahan saat membuat grafik: ' + e.message +
                '</div>'
            );
        }
    }

    // Initialize chart if performance tab is active
    if ($('#performance-analysis').hasClass('active')) {
        setTimeout(renderPerformanceChart, 300);
    }

    // Enhance ranking cards with hover animation
    $('.ranking-card').hover(
        function() {
            $(this).find('.ranking-icon img').css('transform', 'scale(1.15)');
        },
        function() {
            $(this).find('.ranking-icon img').css('transform', 'scale(1)');
        }
    );

    // Smooth scroll animation for tables
    $('.table-responsive').on('scroll', function() {
        const scrollLeft = $(this).scrollLeft();
        if (scrollLeft > 0) {
            $(this).addClass('is-scrolling');
        } else {
            $(this).removeClass('is-scrolling');
        }
    });

    // Add visual feedback for filters
    $('.selectpicker').on('changed.bs.select', function() {
        $(this).closest('.filter-group').addClass('filter-active');
        setTimeout(() => {
            $(this).closest('.filter-group').removeClass('filter-active');
        }, 300);
    });

    // Keyboard navigation for tabs
    $(document).on('keydown', function(e) {
        if (e.altKey) {
            if (e.key === '1') {
                $('.tab-button[data-tab="customer-data"]').click();
                e.preventDefault();
            } else if (e.key === '2') {
                $('.tab-button[data-tab="performance-analysis"]').click();
                e.preventDefault();
            }
        }
    });

    // Add loading state for filter changes
    $('.selectpicker').on('show.bs.select', function() {
        $(this).closest('.filters-container').addClass('filters-loading');
    });

    $('.selectpicker').on('hidden.bs.select', function() {
        $(this).closest('.filters-container').removeClass('filters-loading');
    });

    // Enhance metric cards with number animation
    function animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;

        const timer = setInterval(function() {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }

            if (element.hasClass('revenue-card-value')) {
                element.text('Rp ' + Math.floor(current).toLocaleString('id-ID'));
            } else {
                element.text(current.toFixed(2) + '%');
            }
        }, 16);
    }

    // Trigger animations when performance tab becomes visible
    const performanceObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                $('.metric-value').each(function() {
                    const finalValue = parseFloat($(this).text().replace(/[^\d.]/g, ''));
                    if (!isNaN(finalValue)) {
                        animateValue($(this), 0, finalValue, 1000);
                    }
                });
                performanceObserver.disconnect();
            }
        });
    }, { threshold: 0.1 });

    const performanceTab = document.getElementById('performance-analysis');
    if (performanceTab) {
        performanceObserver.observe(performanceTab);
    }

    // Add tooltip for achievement badges
    $('.achievement-badge').each(function() {
        const achievement = parseFloat($(this).text());
        let tip = (achievement >= 100) ? 'Excellent! Mencapai target'
                : (achievement >= 80) ? 'Good! Mendekati target'
                : 'Perlu peningkatan';

        // gunakan atribut Bootstrap 5
        $(this)
            .attr('data-bs-toggle', 'tooltip')
            .attr('data-bs-title', tip)
            .removeAttr('title');
    });

    // Initialize tooltips if Bootstrap tooltip is available
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-bs-toggle="tooltip"]').tooltip({
            trigger: 'hover',
            placement: 'top'
        });
    }

    // Add search/filter functionality for customer table (optional enhancement)
    let customerTableSearch = '';

    $(document).on('keyup', function(e) {
        // Ctrl/Cmd + F for table search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f' && $('#customer-data').hasClass('active')) {
            e.preventDefault();

            if (!$('#table-search-input').length) {
                const searchHtml = `
                    <div class="table-search-container mb-3">
                        <input type="text" id="table-search-input" class="form-control" placeholder="Cari customer...">
                    </div>
                `;
                $('.data-table').before(searchHtml);
                $('#table-search-input').focus();
            } else {
                $('#table-search-input').focus();
            }
        }
    });

    // Table search functionality
    $(document).on('keyup', '#table-search-input', function() {
        const searchTerm = $(this).val().toLowerCase();

        $('.data-table tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            if (rowText.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Print functionality
    function printDashboard() {
        window.print();
    }

    // Add print button if needed (can be added to UI)
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + P for print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            printDashboard();
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        location.reload();
    });

    // Add loading indicator for async operations
    function showLoadingIndicator() {
        if (!$('#loading-indicator').length) {
            $('body').append(`
                <div id="loading-indicator" style="
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: rgba(255, 255, 255, 0.95);
                    padding: 20px 40px;
                    border-radius: 12px;
                    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
                    z-index: 9999;
                    display: none;
                ">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: #ea1d25;"></i>
                    <p style="margin-top: 10px; margin-bottom: 0; font-weight: 600;">Memuat data...</p>
                </div>
            `);
        }
        $('#loading-indicator').fadeIn(200);
    }

    function hideLoadingIndicator() {
        $('#loading-indicator').fadeOut(200);
    }

    // Show loading when navigating
    $('a').on('click', function(e) {
        const href = $(this).attr('href');
        if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
            showLoadingIndicator();
        }
    });

    // Auto-hide loading on page load
    $(window).on('load', function() {
        hideLoadingIndicator();
    });

    // Accessibility: Add ARIA labels
    $('.tab-button').attr('role', 'tab');
    $('.tab-content').attr('role', 'tabpanel');

    $('.tab-button').each(function(index) {
        $(this).attr('aria-controls', $(this).data('tab'));
        $(this).attr('aria-selected', $(this).hasClass('active'));
    });

    // Update ARIA attributes on tab change
    $('.tab-button').on('click', function() {
        $('.tab-button').attr('aria-selected', false);
        $(this).attr('aria-selected', true);
    });

    // Console log for debugging (remove in production)
    console.log('detailAM Dashboard initialized successfully');
    console.log('Current filters:', @json($filters));
    console.log('Ranking Data:', @json($rankingData));
    console.log('Profile Data:', @json($profileData));
});
</script>
@endsection
@endsection