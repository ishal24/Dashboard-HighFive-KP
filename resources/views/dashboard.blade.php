@extends('layouts.main')

@section('title', 'Dashboard RLEGS')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/overview.css') }}">
<style>
/* Responsive Table with Horizontal Scroll */
.table-container {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    margin: 0;
    padding: 0;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0;
}

.table-modern {
    min-width: 1000px; /* Minimum width agar tidak terlalu sempit */
    white-space: nowrap;
}

.table-modern th,
.table-modern td {
    white-space: nowrap;
    padding: 12px 16px;
}

/* Styling untuk row tanpa revenue */
.row-no-revenue {
    background-color: #f8f9fa !important;
    opacity: 0.75;
}

.row-no-revenue:hover {
    opacity: 1;
    background-color: #e9ecef !important;
}

/* Custom scrollbar untuk table container */
.table-container::-webkit-scrollbar {
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Scroll indicator shadow */
.table-container {
    background:
        linear-gradient(to right, white 30%, rgba(255,255,255,0)),
        linear-gradient(to right, rgba(255,255,255,0), white 70%) 0 100%,
        radial-gradient(farthest-side at 0% 50%, rgba(0,0,0,.2), rgba(0,0,0,0)),
        radial-gradient(farthest-side at 100% 50%, rgba(0,0,0,.2), rgba(0,0,0,0)) 0 100%;
    background-repeat: no-repeat;
    background-color: white;
    background-size: 40px 100%, 40px 100%, 14px 100%, 14px 100%;
    background-attachment: local, local, scroll, scroll;
}
</style>
@endsection

@section('content')
<div class="main-content">
    <!-- Enhanced Header dengan YTD/MTD Filter -->
    <div class="header-dashboard">
        <div class="header-content">
            <div class="header-text">
                <h1 class="header-title">Overview Data</h1>
                <p class="header-subtitle">
                    Monitoring Pendapatan RLEGS
                    @if(isset($cardData['period_text']))
                        <span class="period-text">{{ $cardData['period_text'] }}</span>
                    @endif
                </p>
            </div>
            <div class="header-actions">
                <!-- Filter Group -->
                <div class="filter-group">
                    <!-- Period Type Filter (YTD/MTD) -->
                    <select id="periodTypeFilter" class="form-select filter-select js-enhance">
                        @foreach($filterOptions['period_types'] ?? ['YTD' => 'Year to Date', 'MTD' => 'Month to Date'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['period_type'] ?? 'YTD') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Divisi Filter dengan Kode -->
                    <select id="divisiFilter" class="form-select filter-select js-enhance">
                        <option value="">Semua Divisi</option>
                        @foreach($filterOptions['divisis'] ?? [] as $divisi)
                        <option value="{{ $divisi->id }}" {{ $divisi->id == ($filters['divisi_id'] ?? '') ? 'selected' : '' }}>
                            {{ $divisi->kode ?? $divisi->nama }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Sort Indicator Filter -->
                    <select id="sortIndicatorFilter" class="form-select filter-select js-enhance">
                        @foreach($filterOptions['sort_indicators'] ?? ['total_revenue' => 'Total Revenue Tertinggi', 'achievement_rate' => 'Achievement Rate Tertinggi', 'semua' => 'Semua'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['sort_indicator'] ?? 'total_revenue') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Tipe Revenue Filter -->
                    <select id="tipeRevenueFilter" class="form-select filter-select js-enhance">
                        @foreach($filterOptions['tipe_revenues'] ?? ['all' => 'Semua Tipe'] as $key => $label)
                        <option value="{{ $key }}" {{ $key == ($filters['tipe_revenue'] ?? 'all') ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <button class="export-btn" onclick="exportData()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Section -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <p class="mb-0">{{ session('error') }}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($error))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <p class="mb-0">{{ $error }}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 1. CARD GROUP SECTION - DENGAN FORMAT SHORT -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Total Revenue</div>
                <div class="stats-value">Rp {{ $cardData['total_revenue_formatted'] ?? '0' }}</div>
                <div class="stats-period">Keseluruhan Real Revenue RLEGS</div>
                <div class="stats-icon icon-revenue">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Target Revenue</div>
                <div class="stats-value">Rp {{ $cardData['total_target_formatted'] ?? '0' }}</div>
                <div class="stats-period">Keseluruhan Target Revenue RLEGS</div>
                <div class="stats-icon icon-target">
                    <i class="fas fa-bullseye"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card is-achievement">
                <div class="stats-title">Achievement Rate</div>
                <div class="stats-value">{{ number_format($cardData['achievement_rate'] ?? 0, 2) }}%<span class="achievement-indicator achievement-{{ $cardData['achievement_color'] ?? 'poor' }}"></span></div>
                <div class="stats-period">Persentase Pencapaian Target Pendapatan</div>
                <div class="stats-icon icon-achievement">
                    <i class="fas fa-medal"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PERFORMANCE SECTION - Corporate Customer First, Account Manager Second -->
    <div class="performance-section">
        <div class="card-header">
            <div class="card-header-content">
                <h5 class="card-title">Performance Section</h5>
                <p class="text-muted mb-0">Top performers berdasarkan indikator terpilih</p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs performance-tabs" id="performanceTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-corporate-customer" data-bs-toggle="tab"
                        data-bs-target="#content-corporate-customer" type="button" role="tab"
                        data-tab="corporate_customer">
                    Top Revenue Corporate Customer
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-account-manager" data-bs-toggle="tab"
                        data-bs-target="#content-account-manager" type="button" role="tab"
                        data-tab="account_manager">
                    Top Account Manager
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-witel" data-bs-toggle="tab"
                        data-bs-target="#content-witel" type="button" role="tab"
                        data-tab="witel">
                    Top Witel
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-segment" data-bs-toggle="tab"
                        data-bs-target="#content-segment" type="button" role="tab"
                        data-tab="segment">
                    Top LSegment
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="performanceTabContent">
            <!-- Corporate Customer Tab - DEFAULT ACTIVE -->
            <div class="tab-pane active" id="content-corporate-customer" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['corporate_customer']) && $performanceData['corporate_customer']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama Customer</th>
                                    <th>Divisi</th>
                                    <th>LSegment</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Target Revenue</th>
                                    <th class="text-end">Achievement</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceData['corporate_customer'] as $index => $customer)
                                <tr class="clickable-row {{ $customer->has_revenue ?? true ? '' : 'row-no-revenue' }}" data-url="{{ $customer->detail_url ?? route('corporate-customer.show', $customer->id) }}">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">{{ $customer->nama ?? '-' }}</div>
                                            @if(!empty($customer->nipnas))
                                                <small class="text-muted">{{ $customer->nipnas }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ ($customer->divisi_nama && $customer->divisi_nama !== 'N/A') ? $customer->divisi_nama : '-' }}</td>
                                    <td>{{ ($customer->segment_nama && $customer->segment_nama !== 'N/A') ? $customer->segment_nama : '-' }}</td>
                                    <td class="text-end">
                                        @if(($customer->total_revenue ?? 0) > 0)
                                            Rp {{ number_format($customer->total_revenue, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(($customer->total_target ?? 0) > 0)
                                            Rp {{ number_format($customer->total_target, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $rate = $customer->achievement_rate ?? 0;
                                            if ($rate >= 100) {
                                                $color = 'success';
                                                $tooltip = 'Excellent: Target tercapai dengan baik!';
                                            } elseif ($rate >= 80) {
                                                $color = 'warning';
                                                $tooltip = 'Good: Mendekati target, perlu sedikit peningkatan';
                                            } elseif ($rate > 0) {
                                                $color = 'danger';
                                                $tooltip = 'Poor: Perlu peningkatan signifikan';
                                            } else {
                                                $color = 'secondary';
                                                $tooltip = 'Belum ada data revenue';
                                            }
                                        @endphp
                                        <span class="status-badge bg-{{ $color }}-soft" data-tooltip="{{ $tooltip }}">
                                            {{ $rate > 0 ? number_format($rate, 2) . '%' : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ $customer->detail_url ?? route('corporate-customer.show', $customer->id) }}"
                                           class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="empty-state-enhanced">
                        <i class="fas fa-building-user"></i>
                        <h5>Belum Ada Corporate Customer</h5>
                        <p>Tidak ada Corporate Customer yang memiliki data pendapatan pada periode dan filter yang dipilih.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Account Manager Tab - SERVER-SIDE DATA WITH TOP 10 PRIORITY -->
            <div class="tab-pane" id="content-account-manager" role="tabpanel">
                <div class="table-container">
                    @if(isset($performanceData['account_manager']) && $performanceData['account_manager']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern m-0">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama</th>
                                    <th>Witel</th>
                                    <th>Divisi</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Target Revenue</th>
                                    <th class="text-end">Achievement</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceData['account_manager'] as $index => $am)
                                <tr class="clickable-row {{ $am->has_revenue ?? true ? '' : 'row-no-revenue' }}" data-url="{{ $am->detail_url ?? route('account-manager.show', $am->id) }}">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('img/profile.png') }}" class="am-profile-pic" alt="{{ $am->nama }}">
                                            <span class="ms-2 clickable-name">{{ $am->nama }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $am->witel->nama ?? '-' }}</td>
                                    <td>
                                        <div class="divisi-pills">
                                            @if(!empty($am->divisi_list) && $am->divisi_list !== 'N/A' && $am->divisi_list !== '-')
                                            @php
                                                $divs = preg_split('/\s*,\s*/', $am->divisi_list, -1, PREG_SPLIT_NO_EMPTY);
                                                $alias = [
                                                'government-service' => 'dgs',
                                                'govt-service'       => 'dgs',
                                                'gs'                 => 'dgs',
                                                'dgs'                => 'dgs',
                                                'digital-platform-service' => 'dps',
                                                'platform-service'         => 'dps',
                                                'dps'                      => 'dps',
                                                'digital-solution-service' => 'dss',
                                                'solution-service'         => 'dss',
                                                'dss'                      => 'dss',
                                                'digital-enterprise-service' => 'des',
                                                'enterprise-service'         => 'des',
                                                'des'                        => 'des',
                                                ];
                                            @endphp
                                            @foreach($divs as $divisi)
                                                @php
                                                $key = \Illuminate\Support\Str::slug($divisi);
                                                $code = $alias[$key] ?? 'all';
                                                @endphp
                                                <span class="divisi-pill badge-{{ $code }}">{{ $divisi }}</span>
                                            @endforeach
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if(($am->total_revenue ?? 0) > 0)
                                            Rp {{ number_format($am->total_revenue, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(($am->total_target ?? 0) > 0)
                                            Rp {{ number_format($am->total_target, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $rate = $am->achievement_rate ?? 0;
                                            if ($rate >= 100) {
                                                $color = 'success';
                                            } elseif ($rate >= 80) {
                                                $color = 'warning';
                                            } elseif ($rate > 0) {
                                                $color = 'danger';
                                            } else {
                                                $color = 'secondary';
                                            }
                                        @endphp
                                        <span class="status-badge bg-{{ $color }}-soft">
                                            {{ $rate > 0 ? number_format($rate, 2) . '%' : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ $am->detail_url ?? route('account-manager.show', $am->id) }}"
                                           class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="empty-state-enhanced">
                        <i class="fas fa-users"></i>
                        <h5>Belum Ada Account Manager</h5>
                        <p>Tidak ada Account Manager yang memiliki data pendapatan pada periode dan filter yang dipilih.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Witel Tab - AJAX LOADED (ALL WITELS) -->
            <div class="tab-pane" id="content-witel" role="tabpanel">
                <!-- Content loaded via AJAX -->
            </div>

            <!-- Segment Tab - AJAX LOADED (ALL SEGMENTS) -->
            <div class="tab-pane" id="content-segment" role="tabpanel">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- 3. VISUALISASI PENDAPATAN BULANAN -->
    <div class="row mt-4 equal-cards">
        <!-- Line Chart Total Revenue Bulanan - DENGAN FORMAT LABEL Y-AXIS -->
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-header-content">
                        <h5 class="card-title">Perkembangan Revenue Bulanan</h5>
                        <p class="text-muted mb-0">Total pendapatan RLEGS per bulan ({{ date('Y') }})</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bar Chart Performance Distribution -->
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-header-content">
                        <h5 class="card-title">Distribusi Pencapaian Target AM</h5>
                        <p class="text-muted mb-0">Kuantitas performa Account Manager per bulan</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="amDistributionChart" style="max-height:350px"></canvas>
                    </div>
                    <div id="amDistributionLegend" class="am-legend-grid mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. TABEL TOTAL PENDAPATAN BULANAN -->
    <div class="dashboard-card mt-4">
        <div class="card-header">
            <div class="card-header-content">
                <h5 class="card-title">Total Pendapatan Bulanan RLEGS ({{ date('Y') }})</h5>
                <p class="text-muted mb-0">Ringkasan bulanan Target, Realisasi, Achievement dengan filter {{ $filters['period_type'] ?? 'YTD' }}</p>
            </div>
        </div>
        <div class="card-body p-0">
            @if(isset($revenueTable) && count($revenueTable) > 0)
            <div class="table-responsive">
                <table class="table table-modern m-0">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">Target</th>
                            <th class="text-end">Realisasi</th>
                            <th class="text-end">Achievement</th>
                            <th class="text-end">Gap</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenueTable as $row)
                        <tr>
                            <td>{{ $row['bulan'] }}</td>
                            <td class="text-end">Rp {{ number_format($row['target'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['realisasi'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <span class="status-badge bg-{{ $row['achievement_color'] ?? 'secondary' }}-soft">
                                    {{ number_format($row['achievement'] ?? 0, 2) }}%
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="{{ ($row['realisasi'] - $row['target']) >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format(($row['realisasi'] ?? 0) - ($row['target'] ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state-enhanced">
                <i class="fas fa-table"></i>
                <h5>Belum Ada Data Revenue Bulanan</h5>
                <p>Data revenue bulanan tidak tersedia untuk periode {{ $filters['period_type'] ?? 'YTD' }} dengan filter yang dipilih.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Memuat data dashboard...</p>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<!-- Custom Select Enhancement -->
<script>
(function(){
  function enhanceSelect(sel){
    const wrapper = document.createElement('div');
    wrapper.className = 'select-pill';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'select-pill__btn';
    const menu = document.createElement('div');
    menu.className = 'select-menu';
    btn.textContent = sel.options[sel.selectedIndex]?.text || sel.options[0]?.text || 'Pilih';

    [...sel.options].forEach((opt,i)=>{
      const a = document.createElement('button');
      a.type='button';
      a.className='select-item';
      a.textContent = opt.text;
      a.setAttribute('role','option');
      if(opt.selected) a.setAttribute('aria-selected','true');
      a.addEventListener('click', (e)=>{
        e.stopPropagation();
        sel.selectedIndex = i;
        btn.textContent = opt.text;
        menu.querySelectorAll('.select-item').forEach(x=>x.removeAttribute('aria-selected'));
        a.setAttribute('aria-selected','true');
        sel.dispatchEvent(new Event('change', {bubbles:true}));
        menu.classList.remove('is-open');
      });
      menu.appendChild(a);
    });

    btn.addEventListener('click', (e)=>{
      e.stopPropagation();
      document.querySelectorAll('.select-menu.is-open').forEach(m=>m.classList.remove('is-open'));
      menu.classList.toggle('is-open');
    });

    document.addEventListener('click', ()=> menu.classList.remove('is-open'));

    sel.style.display='none';
    sel.parentNode.insertBefore(wrapper, sel);
    wrapper.appendChild(sel);
    wrapper.appendChild(btn);
    wrapper.appendChild(menu);
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('select.filter-select.js-enhance').forEach(enhanceSelect);
  });
})();
</script>

<!-- Monthly Revenue Chart - DENGAN FORMAT Y-AXIS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('monthlyRevenueChart');
  if (!el) return;

  // Data dari controller (sudah dalam format juta)
  let labels = @json($monthlyLabels ?? []);
  let real   = @json($monthlyReal ?? []);
  let target = @json($monthlyTarget ?? []);

  // Fallback ke tabel jika tidak ada data dari controller
  const fallback = @json($revenueTable ?? []);
  if ((!labels || !labels.length) && Array.isArray(fallback) && fallback.length) {
    labels = fallback.map(r => r.bulan ?? '');
    real   = fallback.map(r => Number(r.realisasi ?? 0) / 1000000);
    target = fallback.map(r => Number(r.target ?? 0) / 1000000);
  }

  const n = Math.min(labels.length, real.length, target.length);
  if (!n) return;

  labels = labels.slice(0, n);
  real   = real.slice(0, n);
  target = target.slice(0, n);

  const achieve = real.map((v, i) => (target[i] ? (v / target[i]) * 100 : 0));

  if (window._monthlyRevenueChart) window._monthlyRevenueChart.destroy();

  const CAT = 0.50;
  const BAR = 0.85;

  window._monthlyRevenueChart = new Chart(el.getContext('2d'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Real Revenue',
          type: 'bar',
          yAxisID: 'y',
          data: real,
          backgroundColor: 'rgba(84,167,248,0.95)',
          borderColor: 'rgba(84,167,248,1)',
          borderWidth: 1,
          borderRadius: 6,
          categoryPercentage: CAT,
          barPercentage: BAR,
          order: 2
        },
        {
          label: 'Target Revenue',
          type: 'bar',
          yAxisID: 'y',
          data: target,
          backgroundColor: 'rgba(217,221,231,0.95)',
          borderColor: 'rgba(217,221,231,1)',
          borderWidth: 1,
          borderRadius: 6,
          categoryPercentage: CAT,
          barPercentage: BAR,
          order: 2
        },
        {
          label: 'Achievement (%)',
          type: 'line',
          yAxisID: 'y1',
          data: achieve,
          borderColor: '#ff4d73',
          backgroundColor: '#ff4d73',
          borderWidth: 3,
          pointRadius: 4,
          pointHoverRadius: 5,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#ff4d73',
          pointBorderWidth: 2,
          tension: 0,
          fill: false,
          spanGaps: false,
          order: 1
        }
      ]
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: { usePointStyle: true, boxWidth: 10, boxHeight: 10, padding: 15 }
        },
        tooltip: {
          callbacks: {
            label(ctx) {
              if (ctx.dataset.yAxisID === 'y1') {
                return ` ${ctx.dataset.label}: ${ctx.formattedValue}%`;
              }
              const val = Number(ctx.raw || 0);
              let formatted;
              if (val >= 1000000) {
                formatted = (val / 1000000).toFixed(2) + ' Triliun';
              } else if (val >= 1000) {
                formatted = (val / 1000).toFixed(2) + ' Miliar';
              } else {
                formatted = val.toFixed(2) + ' Juta';
              }
              return ` ${ctx.dataset.label}: ${formatted}`;
            }
          }
        }
      },
      scales: {
        x: {
          offset: true,
          grid: { color: 'rgba(0,0,0,0.04)' },
          ticks: { color: '#6b7280', font: { size: 11 } }
        },
        y: {
          position: 'left',
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: {
            color: '#6b7280',
            callback: function(value) {
              if (value >= 1000000) {
                return (value / 1000000).toFixed(1) + ' Triliun';
              } else if (value >= 1000) {
                return (value / 1000).toFixed(1) + ' Miliar';
              } else if (value >= 1) {
                return value.toFixed(1) + ' Juta';
              } else {
                return value.toFixed(2);
              }
            },
            font: { size: 11 }
          },
          title: {
            display: true,
            text: 'Revenue (Juta Rp)',
            color: '#6b7280',
            font: { size: 12 }
          }
        },
        y1: {
          position: 'right',
          beginAtZero: true,
          suggestedMax: 120,
          grid: { drawOnChartArea: false },
          ticks: {
            color: '#6b7280',
            callback: v => `${v}%`,
            font: { size: 11 }
          },
          title: {
            display: true,
            text: 'Achievement (%)',
            color: '#6b7280',
            font: { size: 12 }
          }
        }
      },
      layout: { padding: { top: 10, right: 12, left: 4, bottom: 0 } }
    }
  });
});
</script>

<!-- AM Distribution Chart (Doughnut) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('amDistributionChart');
  if (!el) return;

  const distribution = @json($amPerformanceDistribution ?? ['Hijau' => 0, 'Oranye' => 0, 'Merah' => 0]);

  const rows = [
    { status:'Hijau',  count: distribution.Hijau || 0,  color:'#10b981', label:'≥ 100%' },
    { status:'Oranye', count: distribution.Oranye || 0, color:'#f59e0b', label:'80–99%' },
    { status:'Merah',  count: distribution.Merah || 0,  color:'#ef4444', label:'< 80%'  },
  ];

  const labels = rows.map(r=>r.status);
  const data   = rows.map(r=>r.count);
  const colors = rows.map(r=>r.color);
  const total  = data.reduce((a,b)=>a+b,0);

  if (window._amDistributionChart) window._amDistributionChart.destroy();

  const centerTextPlugin = {
    id:'centerText',
    beforeDraw(chart){
      const {ctx, chartArea} = chart;
      if(!chartArea) return;
      const {width, top, height} = chartArea;
      const cx = width/2, cy = top + height/2 - 6;
      ctx.save();
      ctx.textAlign='center';
      ctx.textBaseline='middle';
      ctx.fillStyle='#111827';
      ctx.font='700 30px Poppins,system-ui,sans-serif';
      ctx.fillText(String(total), cx, cy);
      ctx.fillStyle='#6b7280';
      ctx.font='500 12px Poppins,system-ui,sans-serif';
      ctx.fillText('Total AM', cx, cy+20);
      ctx.restore();
    }
  };

  window._amDistributionChart = new Chart(el.getContext('2d'), {
    type:'doughnut',
    data:{
      labels,
      datasets:[{
        data,
        backgroundColor:colors,
        borderColor:'#fff',
        borderWidth:4,
        hoverOffset:10,
        hoverBorderWidth:4
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      cutout:'70%',
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'rgba(17,24,39,.92)',
          padding:12,
          callbacks:{
            title:(items)=> `${rows[items[0].dataIndex].status} • ${rows[items[0].dataIndex].label}`,
            label:(ctx)=> {
              const cnt = ctx.parsed||0;
              const pct = total ? (cnt/total*100).toFixed(1) : '0.0';
              return ` ${cnt} AM (${pct}%)`;
            }
          }
        }
      }
    },
    plugins:[centerTextPlugin]
  });

  const legendEl = document.getElementById('amDistributionLegend');
  if (legendEl) {
    const toRGBA = (hex, a=0.15) => {
      const m = String(hex).replace('#','');
      const [r,g,b] = m.length===3
        ? m.split('').map(x=>parseInt(x+x,16))
        : [m.slice(0,2),m.slice(2,4),m.slice(4,6)].map(x=>parseInt(x,16));
      return `rgba(${r},${g},${b},${a})`;
    };

    legendEl.classList.add('am-legend-grid');
    legendEl.innerHTML = rows.map(r => {
      const pct = total ? (r.count/total*100).toFixed(1) : '0.0';
      return `
        <div class="am-legend-card2">
          <div class="am-legend-head">
            <span class="dot" style="background:${r.color}"></span>
            <span class="label">${r.status}</span>
            <span class="range">• ${r.label}</span>
          </div>
          <div class="am-legend-body">
            <div class="count"><strong>${r.count}</strong> AM</div>
            <div class="pct-chip" style="background:${toRGBA(r.color)}; color:${r.color}">
              ${pct}%
            </div>
          </div>
        </div>
      `;
    }).join('');
  }
});
</script>

<!-- Main Dashboard JavaScript -->
<script>
$(document).ready(function() {
    console.log('Dashboard RLEGS V2 - FINAL FIXED VERSION with N/A→- & Responsive Table');

    // =====================================
    // FILTER HANDLING
    // =====================================
    function applyFilters() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            sort_indicator: $('#sortIndicatorFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };

        console.log('Applying filters:', filters);
        showLoading();

        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        const newUrl = window.location.pathname + '?' + params.toString();
        console.log('Redirecting to:', newUrl);
        window.location.href = newUrl;
    }

    $('#periodTypeFilter, #divisiFilter, #sortIndicatorFilter, #tipeRevenueFilter').on('change', function() {
        console.log('Filter changed:', this.id, this.value);
        applyFilters();
    });

    // =====================================
    // TAB SWITCHING
    // =====================================
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tabType = $(e.target).attr('data-tab');
        console.log('Tab switched to:', tabType);

        if (tabType === 'witel' || tabType === 'segment') {
            loadTabData(tabType);
        }
    });

    // =====================================
    // AJAX LOAD TAB DATA - WITEL & SEGMENT
    // =====================================
    function loadTabData(tabType) {
        console.log('Loading AJAX data for:', tabType);

        const filters = {
            tab: tabType,
            period_type: $('#periodTypeFilter').val() || 'YTD',
            divisi_id: $('#divisiFilter').val() || '',
            sort_indicator: $('#sortIndicatorFilter').val() || 'total_revenue',
            tipe_revenue: $('#tipeRevenueFilter').val() || 'all'
        };

        const tabContent = $(`#content-${tabType}`);

        tabContent.html(`
            <div class="table-container">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data ${getTabDisplayName(tabType)}...</p>
                </div>
            </div>
        `);

        $.ajax({
            url: "{{ route('dashboard.tab-data') }}",
            method: 'GET',
            data: filters,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log(`AJAX success for ${tabType}:`, response);

                if (response.success && response.data) {
                    const dataArray = Array.isArray(response.data) ? response.data : Object.values(response.data);

                    if (dataArray.length > 0) {
                        console.log(`Rendering ${dataArray.length} items for ${tabType}`);
                        renderTabContent(tabContent, dataArray, tabType);
                        bindClickableRows();
                    } else {
                        console.log(`No data for ${tabType}`);
                        showEmptyState(tabContent, tabType);
                    }
                } else {
                    console.warn(`Invalid response for ${tabType}:`, response);
                    showEmptyState(tabContent, tabType);
                }
            },
            error: function(xhr, status, error) {
                console.error(`AJAX error for ${tabType}:`, {
                    status: xhr.status,
                    error: error,
                    response: xhr.responseText
                });
                showErrorState(tabContent, tabType, error);
            }
        });
    }

    // =====================================
    // RENDER TAB CONTENT
    // =====================================
    function renderTabContent(tabContent, data, tabType) {
        console.log(`Rendering ${tabType} with ${data.length} records`);

        let html = `
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-modern m-0">
                        <thead>
                            <tr>${getTableHeaders(tabType)}</tr>
                        </thead>
                        <tbody>
        `;

        data.forEach((item, index) => {
            html += buildTableRow(item, index + 1, tabType);
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        tabContent.html(html);
        console.log(`Content rendered for ${tabType}`);
    }

    // =====================================
    // TABLE BUILDERS
    // =====================================
    function getTableHeaders(tabType) {
        const headers = {
            'witel': '<th>Ranking</th><th>Nama Witel</th><th class="text-center">Total Pelanggan</th><th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th><th class="text-end">Achievement</th><th>Action</th>',
            'segment': '<th>Ranking</th><th>Nama Segmen</th><th>Divisi</th><th class="text-center">Total Pelanggan</th><th class="text-end">Total Revenue</th><th class="text-end">Target Revenue</th><th class="text-end">Achievement</th>'
        };
        return headers[tabType] || '';
    }

    function buildTableRow(item, ranking, tabType) {
        const detailUrl = item.detail_url || getDetailRoute(item.id, tabType);
        let row = `<tr class="clickable-row" data-url="${detailUrl}"><td><strong>${ranking}</strong></td>`;

        console.log(`Building row ${ranking} for ${tabType}:`, item);

        switch(tabType) {
            case 'witel':
                const witelRevenue = parseFloat(item.total_revenue) || 0;
                const witelTarget = parseFloat(item.total_target) || 0;
                row += `
                    <td>${item.nama || '-'}</td>
                    <td class="text-center">${formatNumber(item.total_customers || 0)}</td>
                    <td class="text-end">${witelRevenue > 0 ? 'Rp ' + formatCurrency(witelRevenue) : '<span class="text-muted">-</span>'}</td>
                    <td class="text-end">${witelTarget > 0 ? 'Rp ' + formatCurrency(witelTarget) : '<span class="text-muted">-</span>'}</td>
                    <td class="text-end">
                        <span class="status-badge bg-${item.achievement_color || 'secondary'}-soft">
                            ${(parseFloat(item.achievement_rate) || 0) > 0 ? (parseFloat(item.achievement_rate) || 0).toFixed(2) + '%' : '-'}
                        </span>
                    </td>
                    <td><a href="${detailUrl}" class="btn btn-sm btn-primary">Detail</a></td>
                `;
                break;

            case 'segment':
                const segRevenue = parseFloat(item.total_revenue) || 0;
                const segTarget = parseFloat(item.total_target) || 0;
                row += `
                    <td>${item.lsegment_ho || item.nama || '-'}</td>
                    <td>${(item.divisi && item.divisi.nama) || item.divisi_nama || '-'}</td>
                    <td class="text-center">${formatNumber(item.total_customers || 0)}</td>
                    <td class="text-end">${segRevenue > 0 ? 'Rp ' + formatCurrency(segRevenue) : '<span class="text-muted">-</span>'}</td>
                    <td class="text-end">${segTarget > 0 ? 'Rp ' + formatCurrency(segTarget) : '<span class="text-muted">-</span>'}</td>
                    <td class="text-end">
                        <span class="status-badge bg-${item.achievement_color || 'secondary'}-soft">
                            ${(parseFloat(item.achievement_rate) || 0) > 0 ? (parseFloat(item.achievement_rate) || 0).toFixed(2) + '%' : '-'}
                        </span>
                    </td>
                `;
                break;

            default:
                console.error('Unknown tab type:', tabType);
                return '';
        }

        row += '</tr>';
        return row;
    }

    // =====================================
    // HELPER FUNCTIONS
    // =====================================
    function getTabDisplayName(tabType) {
        const names = {
            'witel': 'Witel',
            'segment': 'Segment'
        };
        return names[tabType] || tabType;
    }

    function getDetailRoute(id, tabType) {
        const baseRoutes = {
            'witel': "{{ route('witel.show', '') }}",
            'segment': "{{ route('segment.show', '') }}"
        };
        return `${baseRoutes[tabType]}/${id}`;
    }

    function formatCurrency(amount) {
        const num = parseFloat(amount) || 0;
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(parseInt(num) || 0);
    }

    function showEmptyState(tabContent, tabType) {
        const icons = {
            'witel': 'fas fa-building',
            'segment': 'fas fa-chart-pie'
        };

        const messages = {
            'witel': 'Belum Ada Data Witel',
            'segment': 'Belum Ada Data Segmen'
        };

        const descriptions = {
            'witel': 'Tidak ada data Witel yang tersedia pada periode dan filter yang dipilih.',
            'segment': 'Tidak ada data Segmen yang tersedia pada periode dan filter yang dipilih.'
        };

        tabContent.html(`
            <div class="table-container">
                <div class="empty-state-enhanced">
                    <i class="${icons[tabType]}"></i>
                    <h5>${messages[tabType]}</h5>
                    <p>${descriptions[tabType]}</p>
                </div>
            </div>
        `);
    }

    function showErrorState(tabContent, tabType, error) {
        console.error(`Error loading ${tabType}:`, error);

        tabContent.html(`
            <div class="table-container">
                <div class="empty-state-enhanced">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    <h5>Gagal Memuat Data</h5>
                    <p>Terjadi kesalahan saat memuat data ${getTabDisplayName(tabType)}. Silakan coba lagi.</p>
                    <small class="text-muted">Error: ${error}</small>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary" onclick="loadTabData('${tabType}')">
                            <i class="fas fa-redo"></i> Coba Lagi
                        </button>
                    </div>
                </div>
            </div>
        `);
    }

    // =====================================
    // CLICKABLE ROWS
    // =====================================
    function bindClickableRows() {
        $('.clickable-row').off('click.dashboard').off('mouseenter.dashboard').off('mouseleave.dashboard');

        $('.clickable-row').on('click.dashboard', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a') || e.target.tagName === 'BUTTON') {
                return;
            }

            const url = $(this).attr('data-url');
            if (url && url !== '#') {
                console.log('Navigating to:', url);
                window.location.href = url;
            }
        });

        $('.clickable-row').on('mouseenter.dashboard', function() {
            $(this).addClass('table-hover-effect');
        });

        $('.clickable-row').on('mouseleave.dashboard', function() {
            $(this).removeClass('table-hover-effect');
        });

        console.log('Clickable rows bound:', $('.clickable-row').length, 'rows');
    }

    bindClickableRows();

    // =====================================
    // EXPORT FUNCTION
    // =====================================
    window.exportData = function() {
        const filters = {
            period_type: $('#periodTypeFilter').val(),
            divisi_id: $('#divisiFilter').val(),
            sort_indicator: $('#sortIndicatorFilter').val(),
            tipe_revenue: $('#tipeRevenueFilter').val()
        };

        console.log('Exporting with filters:', filters);

        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        const exportUrl = "{{ route('dashboard.export') }}?" + params.toString();
        console.log('Export URL:', exportUrl);

        window.open(exportUrl, '_blank');
    };

    // =====================================
    // LOADING FUNCTIONS
    // =====================================
    function showLoading() {
        $('#loadingOverlay').show();
    }

    function hideLoading() {
        $('#loadingOverlay').hide();
    }

    $(window).on('beforeunload', showLoading);
    $(window).on('load', hideLoading);

    // =====================================
    // TOOLTIPS
    // =====================================
    $(document).off('mouseenter.statusBadge mouseleave.statusBadge');

    $(document).on('mouseenter.statusBadge', '.status-badge', function () {
        const $el = $(this);
        const raw = ($el.text() || '').replace('%','');
        const val = parseFloat(raw);
        let msg = 'Achievement Rate';
        if (!isNaN(val)) {
            if (val >= 100) msg = 'Excellent: Target tercapai dengan baik!';
            else if (val >= 80) msg = 'Good: Mendekati target, perlu sedikit peningkatan';
            else if (val > 0) msg = 'Poor: Perlu peningkatan signifikan';
            else msg = 'Belum ada data revenue';
        }
        $el.attr('data-tooltip', msg);

        const rect = this.getBoundingClientRect();
        const table = this.closest('table');
        const thead = table ? table.querySelector('thead') : null;
        const headBottom = thead ? thead.getBoundingClientRect().bottom : 0;

        const nearTop = rect.top < 140;
        const underStickyHead = rect.top < (headBottom + 12);

        const tabPane = this.closest('.tab-pane');
        const forceBottomInAjax = tabPane && ['content-account-manager','content-witel','content-segment']
            .includes(tabPane.id);

        $el.toggleClass('tooltip-bottom', (nearTop || underStickyHead || forceBottomInAjax));

        const rightOverflow = (rect.left + 260) > window.innerWidth;
        $el.toggleClass('tooltip-left', rightOverflow);
    }).on('mouseleave.statusBadge', '.status-badge', function () {
        $(this).removeAttr('data-tooltip').removeClass('tooltip-bottom tooltip-left');
    });

    // =====================================
    // INITIALIZATION COMPLETE
    // =====================================
    console.log('✅ Dashboard RLEGS V2 - FINAL FIXED VERSION Ready');
    console.log('📊 Features: Format Currency ✓, N/A→- ✓, Responsive Table ✓, Top 10 Priority ✓');
    hideLoading();
});
</script>
@endsection