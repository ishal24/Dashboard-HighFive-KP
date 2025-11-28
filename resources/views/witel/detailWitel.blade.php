@extends('layouts.main')

@section('title', 'Detail Witel RLEGS TR3')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<link rel="stylesheet" href="{{ asset('css/detailCC.css') }}">
@endsection

@section('content')
<div class="main-content">
    <!-- Profile Overview -->
    <div class="profile-overview">
        <div class="profile-avatar-container">
            <div class="cc-avatar">
                <i class="fas fa-map-marked-alt"></i>
            </div>
        </div>
        <div class="profile-details">
            <div class="profile-header">
                <h2 class="profile-name">{{ $profileData['nama'] }}</h2>
            </div>

            <!-- Profile Meta - All Horizontal -->
            <div class="profile-meta-grid">
                <!-- Total Account Managers -->
                <div class="meta-item-inline">
                    <i class="fas fa-users"></i>
                    <span class="meta-label">Total AM:</span>
                    <span class="meta-value">{{ $profileData['total_account_managers'] }} orang</span>
                </div>

                {{-- <!-- Total AM -->
                <div class="meta-item-inline">
                    <i class="fas fa-user-tie"></i>
                    <span class="meta-label">Account Manager:</span>
                    <span class="meta-value">{{ $profileData['total_am'] }} AM</span>
                </div> --}}

                <!-- Total HOTDA -->
                <div class="meta-item-inline">
                    <i class="fas fa-building"></i>
                    <span class="meta-label">HOTDA:</span>
                    <span class="meta-value">{{ $profileData['total_hotda'] }} HOTDA</span>
                </div>

                <!-- Total Customers -->
                <div class="meta-item-inline">
                    <i class="fas fa-briefcase"></i>
                    <span class="meta-label">Corporate Customers:</span>
                    <span class="meta-value">{{ $profileData['total_customers'] }} customers</span>
                </div>
            </div>
        </div>
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

    <!-- Content Tabs -->
    <div class="content-wrapper">
        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-button active" data-tab="revenue-data">
                <i class="fas fa-chart-bar"></i> Data Revenue
            </button>
            <button class="tab-button" data-tab="revenue-analysis">
                <i class="fas fa-chart-line"></i> Analisis Revenue
            </button>
        </div>

        <!-- Tab Content - Revenue Data -->
        <div id="revenue-data" class="tab-content active">
            <div class="tab-content-header">
                <div class="tab-content-title">
                    <i class="fas fa-chart-bar"></i> Data Revenue & Performance
                </div>

                <div class="filters-wrapper">
                    <!-- View Mode Toggle -->
                    <div class="view-mode-toggle">
                        <button type="button" class="view-mode-btn {{ $filters['revenue_view_mode'] == 'detail' ? 'active' : '' }}" data-mode="detail">
                            <i class="fas fa-list-ul"></i> Detail
                        </button>
                        <button type="button" class="view-mode-btn {{ $filters['revenue_view_mode'] == 'agregat_bulan' ? 'active' : '' }}" data-mode="agregat_bulan">
                            <i class="fas fa-calendar"></i> Agregat
                        </button>
                    </div>

                    <!-- Filter Dropdowns Row -->
                    <div class="filter-dropdowns-row">
                        <!-- Granularity Filter -->
                        <div class="filter-item">
                            <select id="granularityFilter" class="selectpicker">
                                <option value="account_manager" {{ $filters['granularity'] == 'account_manager' ? 'selected' : '' }}>Per Account Manager</option>
                                <option value="divisi" {{ $filters['granularity'] == 'divisi' ? 'selected' : '' }}>Per Divisi</option>
                                <option value="corporate_customer" {{ $filters['granularity'] == 'corporate_customer' ? 'selected' : '' }}>Per Corporate Customer</option>
                            </select>
                        </div>

                        <!-- Role Filter (only show when granularity is account_manager) -->
                        @if($filters['granularity'] == 'account_manager')
                        <div class="filter-item">
                            <select id="roleFilter" class="selectpicker">
                                <option value="all" {{ $filters['role_filter'] == 'all' ? 'selected' : '' }}>Semua (AM + HOTDA)</option>
                                <option value="AM" {{ $filters['role_filter'] == 'AM' ? 'selected' : '' }}>Account Manager</option>
                                <option value="HOTDA" {{ $filters['role_filter'] == 'HOTDA' ? 'selected' : '' }}>HOTDA</option>
                            </select>
                        </div>
                        @endif

                        <!-- Tipe Revenue Filter -->
                        <div class="filter-item">
                            <select id="tipeRevenueFilter" class="selectpicker">
                                <option value="all" {{ $filters['tipe_revenue'] == 'all' ? 'selected' : '' }}>Semua Tipe</option>
                                <option value="REGULER" {{ $filters['tipe_revenue'] == 'REGULER' ? 'selected' : '' }}>REGULER</option>
                                <option value="NGTMA" {{ $filters['tipe_revenue'] == 'NGTMA' ? 'selected' : '' }}>NGTMA</option>
                            </select>
                        </div>

                        <!-- Revenue Source Filter -->
                        <div class="filter-item">
                            <select id="revenueSourceFilter" class="selectpicker">
                                <option value="all" {{ $filters['revenue_source'] == 'all' ? 'selected' : '' }}>Semua Source</option>
                                <option value="HO" {{ $filters['revenue_source'] == 'HO' ? 'selected' : '' }}>HO</option>
                                <option value="BILL" {{ $filters['revenue_source'] == 'BILL' ? 'selected' : '' }}>BILL</option>
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="filter-item">
                            <select id="revenueYearFilter" class="selectpicker">
                                @foreach($filterOptions['available_years'] as $year)
                                    <option value="{{ $year }}" {{ $filters['tahun'] == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Table -->
            <div class="data-card">
                @if($revenueData && $revenueData['revenues']->count() > 0)
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Bulan</th>

                                    @if($filters['granularity'] == 'account_manager')
                                        <th>Nama AM</th>
                                        <th>NIK</th>
                                        <th>Role</th>
                                        @if($filters['revenue_view_mode'] == 'detail')
                                            <th>Divisi</th>
                                        @endif
                                    @elseif($filters['granularity'] == 'divisi')
                                        <th>Divisi</th>
                                        @if($filters['revenue_view_mode'] == 'detail')
                                            <th>Source</th>
                                        @endif
                                    @elseif($filters['granularity'] == 'corporate_customer')
                                        <th>Customer</th>
                                        <th>NIPNAS</th>
                                        @if($filters['revenue_view_mode'] == 'detail')
                                            <th>Divisi</th>
                                        @endif
                                    @endif

                                    <th>Target</th>
                                    <th>Revenue</th>
                                    <th>Achievement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($revenueData['revenues'] as $index => $revenue)
                                    <tr>
                                        <td class="row-number">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="month-badge">{{ $revenue->bulan_name ?? 'N/A' }}</span>
                                        </td>

                                        @if($filters['granularity'] == 'account_manager')
                                            <td class="customer-name">{{ $revenue->account_manager ?? 'N/A' }}</td>
                                            <td class="nipnas">{{ $revenue->nik ?? 'N/A' }}</td>
                                            <td>
                                                <span class="tipe-badge badge-{{ strtolower($revenue->role ?? '') }}">
                                                    {{ $revenue->role ?? 'N/A' }}
                                                </span>
                                            </td>
                                            @if($filters['revenue_view_mode'] == 'detail')
                                                <td>{{ $revenue->divisi ?? 'N/A' }}</td>
                                            @endif
                                        @elseif($filters['granularity'] == 'divisi')
                                            <td class="customer-name">{{ $revenue->divisi ?? 'N/A' }}</td>
                                            @if($filters['revenue_view_mode'] == 'detail')
                                                <td>
                                                    <span class="source-badge badge-{{ strtolower($revenue->source ?? '') }}">
                                                        {{ $revenue->source ?? 'N/A' }}
                                                    </span>
                                                </td>
                                            @endif
                                        @elseif($filters['granularity'] == 'corporate_customer')
                                            <td class="customer-name">{{ $revenue->customer_name ?? 'N/A' }}</td>
                                            <td class="nipnas">{{ $revenue->nipnas ?? 'N/A' }}</td>
                                            @if($filters['revenue_view_mode'] == 'detail')
                                                <td>{{ $revenue->divisi ?? 'N/A' }}</td>
                                            @endif
                                        @endif

                                        <td>Rp {{ number_format($revenue->total_target ?? $revenue->target ?? 0, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($revenue->total_revenue ?? $revenue->revenue ?? 0, 0, ',', '.') }}</td>
                                        <td>
                                            @php
                                                $achievement = $revenue->achievement_rate ?? 0;
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
                        <p class="empty-text">Tidak ada data revenue untuk filter yang dipilih</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tab Content - Revenue Analysis -->
        <div id="revenue-analysis" class="tab-content">
            <div class="tab-content-header">
                <div class="tab-content-title">
                    <i class="fas fa-chart-line"></i> Analisis Revenue
                </div>
            </div>

            <!-- Revenue Summary -->
            @if(isset($revenueAnalysis['summary']))
            <div class="insight-summary-card">
                <div class="insight-header">
                    <i class="fas fa-lightbulb"></i>
                    <h4>Ringkasan Revenue</h4>
                </div>
                <div class="insight-body">
                    @php
                        $summary = $revenueAnalysis['summary'];
                        $avgAch = $summary['average_achievement'] ?? 0;
                        $trend = $summary['trend'] ?? 'stabil';
                    @endphp
                    <p><strong>Witel {{ $profileData['nama'] }}</strong> menunjukkan performa yang
                        @if($avgAch >= 90) <strong class="text-success">sangat baik</strong>
                        @elseif($avgAch >= 80) <strong class="text-warning">baik</strong>
                        @else <strong class="text-danger">perlu ditingkatkan</strong>
                        @endif
                        dengan rata-rata achievement <strong>{{ number_format($avgAch, 2) }}%</strong>.
                    </p>
                    <p>Total revenue sepanjang waktu mencapai <strong>Rp {{ number_format($summary['total_revenue_all_time'] ?? 0, 0, ',', '.') }}</strong>
                        dari target <strong>Rp {{ number_format($summary['total_target_all_time'] ?? 0, 0, ',', '.') }}</strong>.
                        Tren revenue menunjukkan kondisi <strong class="{{ $trend == 'naik' ? 'text-success' : ($trend == 'turun' ? 'text-danger' : 'text-muted') }}">{{ $trend }}</strong>
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
                        <div class="metric-value">
                            @php
                                $highestRevenue = $summary['highest_revenue']['value'] ?? 0;
                                if ($highestRevenue >= 1000000000) {
                                    $formatted = number_format($highestRevenue / 1000000000, 2) . ' M';
                                } elseif ($highestRevenue >= 1000000) {
                                    $formatted = number_format($highestRevenue / 1000000, 2) . ' Jt';
                                } else {
                                    $formatted = number_format($highestRevenue, 0, ',', '.');
                                }
                            @endphp
                            Rp {{ $formatted }}
                        </div>
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
                        <div class="metric-period">Sepanjang Waktu</div>
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
                        <div class="metric-label">Tren Revenue</div>
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

            <!-- Revenue Chart -->
            @if(isset($revenueAnalysis['monthly_chart']))
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Grafik Revenue Bulanan
                    </h4>

                    <div class="chart-filters">
                        <div class="filter-item">
                            <select id="chartYearFilter" class="selectpicker">
                                @foreach($filterOptions['available_years'] as $year)
                                    <option value="{{ $year }}" {{ $filters['chart_tahun'] == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="filter-item">
                            <select id="chartDisplayMode" class="selectpicker">
                                <option value="combination" {{ $filters['chart_display'] == 'combination' ? 'selected' : '' }}>Kombinasi</option>
                                <option value="revenue" {{ $filters['chart_display'] == 'revenue' ? 'selected' : '' }}>Revenue</option>
                                <option value="achievement" {{ $filters['chart_display'] == 'achievement' ? 'selected' : '' }}>Achievement</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="chart-canvas-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Initialize selectpicker
    $('.selectpicker').selectpicker({
        liveSearch: false,
        size: 6,
        mobile: false
    });

    // Tab switching
    $('.tab-button').on('click', function() {
        $('.tab-button').removeClass('active');
        $('.tab-content').removeClass('active');
        $(this).addClass('active');
        const tabId = $(this).data('tab');
        $('#' + tabId).addClass('active');
        if (tabId === 'revenue-analysis') {
            setTimeout(renderRevenueChart, 100);
        }
    });

    // View mode toggle
    $('.view-mode-btn').on('click', function() {
        updateUrlParameter('revenue_view_mode', $(this).data('mode'));
    });

    // Filter changes
    $('#granularityFilter').on('changed.bs.select', function() {
        updateUrlParameter('granularity', $(this).val());
    });

    $('#roleFilter').on('changed.bs.select', function() {
        updateUrlParameter('role_filter', $(this).val());
    });

    $('#tipeRevenueFilter').on('changed.bs.select', function() {
        updateUrlParameter('tipe_revenue', $(this).val());
    });

    $('#revenueSourceFilter').on('changed.bs.select', function() {
        updateUrlParameter('revenue_source', $(this).val());
    });

    $('#revenueYearFilter').on('changed.bs.select', function() {
        updateUrlParameter('tahun', $(this).val());
    });

    $('#chartYearFilter').on('changed.bs.select', function() {
        updateUrlParameter('chart_tahun', $(this).val());
    });

    $('#chartDisplayMode').on('changed.bs.select', function() {
        updateUrlParameter('chart_display', $(this).val());
    });

    function updateUrlParameter(key, value) {
        const url = new URL(window.location.href);
        if (value && value !== '' && value !== 'all') {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        window.location.href = url.toString();
    }

    // Render chart
    function renderRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        try {
            const existingChart = Chart.getChart(ctx);
            if (existingChart) existingChart.destroy();
        } catch (e) {}

        const chartData = @json($revenueAnalysis['monthly_chart'] ?? null);
        if (!chartData || !chartData.labels || chartData.labels.length === 0) {
            $(ctx).parent().html(
                '<div class="empty-state">' +
                '<div class="empty-icon"><i class="fas fa-chart-bar"></i></div>' +
                '<p class="empty-text">Tidak ada data revenue</p>' +
                '</div>'
            );
            return;
        }

        const displayMode = '{{ $filters["chart_display"] ?? "combination" }}';
        const datasets = [];

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

        const scales = {};

        if (displayMode === 'combination' || displayMode === 'revenue') {
            scales.y = {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (Rp)',
                    font: { weight: 'bold', size: 12 },
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
                grid: { color: 'rgba(0, 0, 0, 0.05)' }
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
                    font: { weight: 'bold', size: 12 },
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
                        intersect: false
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
                                    weight: '600',
                                    family: 'Poppins'
                                },
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.95)',
                            titleFont: {
                                weight: 'bold',
                                size: 13,
                                family: 'Poppins'
                            },
                            bodyFont: {
                                size: 12,
                                family: 'Poppins'
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
                                        label += context.parsed.y.toFixed(2) + '%';
                                    } else {
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

    // Initialize chart if analysis tab is active
    if ($('#revenue-analysis').hasClass('active')) {
        setTimeout(renderRevenueChart, 300);
    }

    // Console log for debugging
    console.log('Witel Dashboard initialized successfully');
    console.log('Current filters:', @json($filters));
    console.log('Witel:', '{{ $witel->nama }}');
});
</script>
@endsection
@endsection

