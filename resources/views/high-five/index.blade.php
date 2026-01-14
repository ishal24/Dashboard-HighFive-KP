@extends('layouts.main')

@section('title', 'High Five RLEGS TR3')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/css/bootstrap-select.min.css">
<link rel="stylesheet" href="{{ asset('css/highfive.css') }}">
<link rel="stylesheet" href="{{ asset('css/highfive-product-tabs.css') }}">
<style>
    /* Custom Toggle Switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        margin-bottom: 0;
    }
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    input:checked + .slider {
        background-color: #0ea5e9;
    }
    input:checked + .slider:before {
        transform: translateX(20px);
    }
    /* Kontainer utama grid */
    .highlight-grid-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 30px;
    }

    /* Baris 1: Chart (Lebar) dan Card Utama (Kecil) */
    .row-1-layout {
        display: grid;
        grid-template-columns: 1.8fr 1fr;
        gap: 20px;
        align-items: stretch; /* PENTING: Memaksa kolom kiri dan kanan sama tinggi */
    }

    /* Baris 2: 4 Card Kecil */
    .row-2-layout {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }

    .chart-card-wrapper {
        background: white;
        border-radius: 15px;
        padding: 24px;
        border: 1px solid var(--gray-200);
        display: flex;
        flex-direction: column;
        width: 100%; /* Pastikan lebar 100% */
        min-width: 0;   /* Penting agar chart bisa mengecil di dalam grid */
        overflow: hidden; 
    }
    /* Pastikan Wrapper Chart dan CardTall mengisi 100% tinggi kolom */
     
    .metric-sq-card.card-tall {
        height: 100%;
        margin-bottom: 0; /* Hapus margin bawah agar tidak pincang */
        display: flex;
        flex-direction: column;
    }

    /* 2. Overide class .card-tall agar patuh pada Row 1 saja */
    .metric-sq-card.card-tall {
        grid-row: auto !important; /* Batalkan span 2 dari file CSS eksternal */
        height: 100% !important;   /* Paksa isi 100% tinggi kolom */
        margin: 0 !important;       /* Hapus margin yang mengganggu */
        display: flex;
        flex-direction: column;
        justify-content: space-between; /* Sebar konten ke atas dan bawah agar rapi */
        padding: 24px;
        box-sizing: border-box;
    }
    
    /* Container canvas harus relatif dan fleksibel */
    .chart-container-inner {
        position: relative;
        flex-grow: 1;
        width: 100%;
        min-height: 250px; /* Gunakan min-height, jangan height tetap */
        max-height: 320px;
    }


    /* 3. Pastikan chart wrapper juga mengisi tinggi penuh */
    .chart-card-wrapper {
        height: 100%;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }

    /* 4. Pastikan canvas chart mengambil sisa ruang yang ada */
    .chart-card-wrapper canvas {
        flex-grow: 1;
        max-height: 320px; /* Batasi agar tidak mendorong row 2 terlalu jauh */
    }

    /* 5. Rapihkan isi dalam Card Tall */
    .metric-sq-card.card-tall .sq-icon {
        margin-bottom: 10px;
    }
    
    .metric-sq-card.card-tall .sq-stat {
        line-height: 1.2;
        margin: 10px 0;
    }
    /* Responsif untuk layar kecil */
    @media (max-width: 1024px) {
        .row-1-layout { grid-template-columns: 1fr; }
        .row-2-layout { grid-template-columns: repeat(2, 1fr); }
    }

</style>
@endsection

@section('content')
<div class="highfive-main-content">
<div class="header-leaderboard">
    <h1 class="header-title">
        <i class="fas fa-chart-line"></i>
        High Five RLEGS TR3
    </h1>
    <p class="header-subtitle">Monitoring Performa Mingguan Account Manager dan Produk High Five</p>
</div>

<div class="alert-container" id="alertContainer"></div>

<div class="toolkit-container">
    <div class="toolkit-header" onclick="toggleManualFetch()">
        <h4><i class="fas fa-database"></i> Kelola Dataset High Five</h4>
        <div style="display: flex; align-items: center; gap: 12px;">
            <button type="button" class="btn-kelola-link" onclick="openLinkModal()">
                <i class="fas fa-cog"></i> Kelola Link Spreadsheet
            </button>
            <i class="fas fa-chevron-down toolkit-toggle" id="manualFetchToggle"></i>
        </div>
    </div>
    <div class="toolkit-body" id="manualFetchBody">
        <!-- INFO BOX -->
        <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); padding: 14px 18px; border-radius: var(--radius-lg); border: 1px solid #fcd34d; margin-bottom: 16px;">
            <div style="display: flex; align-items: start; gap: 10px;">
                <i class="fas fa-info-circle" style="color: #f59e0b; font-size: 1.1rem; margin-top: 2px;"></i>
                <div style="flex: 1;">
                    <strong style="font-size: 13px; color: #92400e; display: block; margin-bottom: 4px;">💡 Info Update Data</strong>
                    <p style="font-size: 12px; color: #92400e; line-height: 1.5; margin: 0;">
                        Gunakan fitur update data manual untuk update data secara manual. Gunakan fitur update data otomatis untuk update data secara otomatis.
                    </p>
                </div>
            </div>
        </div>

        <!-- UPDATE CONTROLS WRAPPER (SIDE BY SIDE) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            
            <!-- COLUMN 1: MANUAL UPDATE -->
            <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; height: 100%;">
                
                <!-- HEADER -->
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                    <div style="background: #fef3c7; padding: 8px; border-radius: 50%; color: #d97706;">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #1e293b;">Update Data Manual</h5>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">Update data secara manual</p>
                    </div>
                </div>

                <!-- CONTROLS -->
                <div class="toolkit-grid" style="grid-template-columns: 2fr 1fr auto; gap: 12px;">
                    <div class="field-group">
                        <label><i class="fas fa-link"></i> LINK SPREADSHEET</label>
                        <select id="manualLinkSelect" class="native-select" style="width: 100%;"><option value="">Pilih Link</option></select>
                    </div>
                    <div class="field-group">
                        <label><i class="fas fa-calendar"></i> TANGGAL DATA</label>
                        <input type="text" id="manualSnapshotDate" class="native-select" placeholder="Pilih tanggal" readonly style="width: 100%;">
                    </div>
                    <div class="field-group">
                        <label style="opacity: 0;">.</label>
                        <button id="btnSaveManual" class="btn-save-dataset" onclick="saveManualData()" style="width: 120px;">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: AUTO UPDATE -->
            <div id="autoFetchControls" style="background: #f8fafc; padding: 20px; border-radius: var(--radius-lg); border: 1px solid #e2e8f0; height: 100%; transition: all 0.3s; position: relative;">
                
                <!-- HEADER (Inside Box) -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="background: #e0f2fe; padding: 8px; border-radius: 50%; color: #0284c7;">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h5 style="margin: 0; font-size: 14px; font-weight: 600; color: #1e293b;">Update Data Otomatis</h5>
                            <p style="margin: 0; font-size: 12px; color: #64748b;">Ambil data sesuai jadwal</p>
                        </div>
                    </div>
                    <!-- Toggle Switch -->
                    <label class="toggle-switch">
                        <input type="checkbox" id="autoFetchToggle">
                        <span class="slider"></span>
                    </label>
                </div>

                <!-- CONTROLS -->
                <div class="toolkit-grid" style="grid-template-columns: 1.5fr 1.5fr 1fr; gap: 10px;">
                    <div class="field-group">
                        <label><i class="fas fa-calendar-day"></i> HARI</label>
                        <select class="native-select" id="autoFetchDay" style="width: 100%;">
                            <option value="Monday">Senin</option>
                            <option value="Tuesday">Selasa</option>
                            <option value="Wednesday">Rabu</option>
                            <option value="Thursday">Kamis</option>
                            <option value="Friday">Jumat</option>
                            <option value="Saturday">Sabtu</option>
                            <option value="Sunday">Minggu</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label><i class="fas fa-clock"></i> JAM (WIB)</label>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="position: relative; flex: 1;">
                                <input type="text" id="autoFetchHour" class="native-select time-input" placeholder="HH" maxlength="2" style="padding-left: 0; text-align: center;">
                            </div>
                            <span style="font-weight: bold; color: #64748b;">:</span>
                            <div style="position: relative; flex: 1;">
                                <input type="text" id="autoFetchMinute" class="native-select time-input" placeholder="MM" maxlength="2" style="padding-left: 0; text-align: center;">
                            </div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label style="opacity: 0;">Act</label>
                        <button id="btnSaveAutoFetch" class="btn-save-dataset" onclick="saveAutoFetchSettings()" style="width: 100%;">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </div>
                
                <div style="margin-top: 12px; font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i>
                    <span>Jadwal berikutnya: <strong id="nextRunText">-</strong></span>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="performance-container">
    <div class="performance-header-wrapper">
        <div class="toolkit-header">
            <h4><i class="fas fa-chart-line"></i> Overview Data Performa High Five</h4>
        </div>
        <div class="selector-grid">
            <div class="field-group">
                <label><i class="fas fa-filter"></i> Filter Divisi</label>
                <select id="filterDivisi" class="native-select" title="Pilih Divisi">
                    @foreach($divisiList as $divisi)
                        <option value="{{ $divisi->id }}">{{ $divisi->kode }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group">
                <label><i class="fas fa-database"></i> Data Progres 1 (Periode Lama)</label>
                <select id="snapshot1" class="native-select" disabled><option value="">-- Pilih Data Progres 1 --</option></select>
            </div>
            <div class="field-group">
                <label><i class="fas fa-database"></i> Data Progres 2 (Periode Baru)</label>
                <select id="snapshot2" class="native-select" disabled><option value="">-- Pilih Data Progres 2 --</option></select>
            </div>
            <button type="button" id="loadBenchmarkBtn" class="btn-load-data" disabled>
                <i class="fas fa-sync-alt"></i> Load Data
            </button>
            <button type="button" id="downloadReportAM" class="btn-download-report" disabled>
                <i class="fas fa-file-pdf"></i> Unduh Laporan
            </button>
        </div>
        <div class="performance-tabs">
            <button class="tab-btn" data-tab="am-level"><i class="fas fa-user-tie"></i> Performa AM Level</button>
            <button class="tab-btn" data-tab="product-level"><i class="fas fa-box"></i> Performa Product Level</button>
        </div>
    </div>

    <div class="performance-body-wrapper">
        <div class="tab-content-area">
            <div id="emptyState" class="empty-state active">
                <i class="fas fa-chart-bar"></i>
                <h3>Belum Ada Data untuk Divisualisasikan</h3>
                <p>Pilih Filter Divisi dan 2 Data Progres untuk membandingkan performa</p>
            </div>
            <div id="loadingState" class="loading-state">
                <div class="spinner"></div>
                <p>Memproses data dari database...</p>
            </div>

            <div id="amLevelContent" class="tab-content">
                <div class="highlight-grid-container">
                    <div class="row-1-layout">
                        <div class="chart-card-wrapper">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="m-0 font-bold" style="color: #1e293b; font-size: 16px;">Distribusi Progres</h5>
                                    <p class="m-0 text-muted" style="font-size: 12px;">Periode Lama vs Periode Baru</p>
                                </div>
                                <select id="chartFilterWitelAM" class="native-select" style="width: auto; min-width: 160px; height: 35px; font-size: 13px;">
                                    <option value="Total">Seluruh TREG3</option>
                                </select>
                            </div>
                            <div style="height: 320px; position: relative;"><canvas id="progressChartAM"></canvas></div>
                        </div>

                        <div class="metric-sq-card card-tall theme-success clickable-card" id="cardTREG3" onclick="showMetricInsight('TREG3')">
                            <div class="sq-icon"><i class="fas fa-globe-asia"></i></div>
                            <div class="sq-label" style="font-size: 14px;">TREG3 Avg Improvement</div>
                            <div class="sq-stat" id="metricNatValue" style="margin-bottom: 0;">-</div>
                            <div class="sq-sub" id="metricNatTrend" style="margin-bottom: 8px;">-</div>
                            <div style="border-top: 1px dashed #e2e8f0; width: 100%; padding-top: 8px; display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 11px; background: #f8fafc; padding: 6px; border-radius: 6px;">
                                    <div style="text-align: left;"><span style="color: #64748b; display: block; font-size: 9px;">TOTAL OFFERINGS</span><span id="valOfferings" style="font-weight: 700; color: #1e293b;">-</span></div>
                                    <div style="text-align: right;"><span style="color: #64748b; display: block; font-size: 9px;">CC VISITED</span><span id="valVisited" style="font-weight: 700; color: #1e293b;">-</span></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 11px; background: #f8fafc; padding: 6px; border-radius: 6px;">
                                    <div style="text-align: left;"><span style="color: #059669; display: block; font-size: 9px; font-weight: 700;">TOTAL WINS</span><span id="valWins" style="font-weight: 700; color: #059669;">-</span></div>
                                    <div style="text-align: right;"><span style="color: #dc2626; display: block; font-size: 9px; font-weight: 700;">TOTAL LOSSES</span><span id="valLoses" style="font-weight: 700; color: #dc2626;">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row-2-layout">
                        <div class="metric-sq-card card-horizontal theme-primary clickable-card" id="cardMostWitel" onclick="showMetricInsight('most_witel')">
                            <div class="sq-icon"><i class="fas fa-crown"></i></div>
                            <div class="sq-label">Top Improvement Witel</div>
                            <div class="sq-value" id="metricMostName">-</div>
                            <div class="sq-sub" id="metricMostStat">-</div>
                        </div>
                        <div class="metric-sq-card card-horizontal theme-warning clickable-card" id="cardLeastWitel" onclick="showMetricInsight('least_witel')">
                            <div class="sq-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="sq-label">Least Improvement Witel</div>
                            <div class="sq-value" id="metricLeastName">-</div>
                            <div class="sq-sub" id="metricLeastStat">-</div>
                        </div>
                        <div class="metric-sq-card card-horizontal theme-purple clickable-card" id="cardTopAM" onclick="showMetricInsight('top_am')">
                            <div class="sq-icon"><i class="fas fa-user"></i></div>
                            <div class="sq-label">Top Improvement AM</div>
                            <div class="sq-value" id="metricTopAMName">-</div>
                            <div class="sq-sub" id="metricTopAMStat">-</div>
                        </div>
                        <div class="metric-sq-card card-horizontal theme-success clickable-card" id="cardAmWin" onclick="showMetricInsight('am_most_win')">
                            <div class="sq-icon"><i class="fas fa-trophy"></i></div>
                            <div class="sq-label">Top Win AM</div>
                            <div class="sq-value" id="metricAmWinName">-</div>
                            <div class="sq-sub" id="metricAmWinStat">-</div>
                        </div>
                    </div>
                </div>

                <div class="am-tabs-container">
                    <div class="am-tabs-navigation">
                        <button class="am-tab-btn active" data-am-tab="benchmarking"><i class="fas fa-table"></i> Benchmarking</button>
                        <button class="am-tab-btn" data-am-tab="leaderboard"><i class="fas fa-medal"></i> Leaderboard AM (Top Improvement)</button>
                    </div>

                    <div class="am-tab-content active" id="amBenchmarkingTab">
                        <div class="am-filter-container">
                            <div class="am-search-group">
                                <i class="fas fa-search"></i>
                                <input type="text" id="amSearchInput" placeholder="Cari Account Manager atau Witel..." autocomplete="off">
                            </div>
                            <div class="am-filter-group">
                                <select id="amStatusFilter" class="native-select">
                                    <option value="all">Semua Status</option>
                                    <option value="result_gt_50">Avg Result > 50% (Data Terbaru)</option>
                                    <option value="result_lt_50">Avg Result < 50% (Data Terbaru)</option>
                                    <option value="progress_0">Avg Progress 0% (Data Terbaru)</option>
                                    <option value="has_win">Has Win</option>
                                    <option value="has_lose">Has Lose</option>
                                </select>
                            </div>
                            <div class="am-sort-group">
                                <span class="sort-label">Sort by:</span>
                                <button class="btn-sort active" data-sort="improvement"><i class="fas fa-chart-line"></i> Improve</button>
                                <button class="btn-sort" data-sort="win"><i class="fas fa-trophy"></i> Wins</button>
                                <button class="btn-sort" data-sort="cc"><i class="fas fa-building"></i> CC</button>
                                <button class="btn-sort" data-sort="result"><i class="fas fa-percentage"></i> Result</button>
                            </div>
                        </div>
                        <div class="table-container">
                            <div class="table-header-fixed">
                                <table class="benchmark-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 200px; min-width: 200px;">Witel</th>
                                            <th style="width: 320px; min-width: 320px;">Account Manager</th>
                                            <th>Avg % Progress<br><small id="dataset1DateAM">-</small></th>
                                            <th>Avg % Progress<br><small id="dataset2DateAM">-</small></th>
                                            <th>Avg % Result<br><small id="dataset1ResultAM">-</small></th>
                                            <th>Avg % Result<br><small id="dataset2ResultAM">-</small></th>
                                            <th>Perubahan</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-scrollable-wrapper">
                                <div class="table-responsive">
                                    <table class="benchmark-table">
                                        <thead style="display: none;"></thead>
                                        <tbody id="amBenchmarkTableBody">
                                            <tr><td colspan="7" style="text-align: center; padding: 30px; color: var(--gray-500);">Pilih data progres untuk melihat data</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="am-tab-content" id="amLeaderboardTab">
                        <div class="table-container leaderboard-container">
                            <div class="table-responsive">
                                <table class="benchmark-table leaderboard-table">
                                    <thead>
                                        <tr>
                                            <th width="100">Rank</th>
                                            <th>Account Manager</th>
                                            <th>Witel</th>
                                            <th width="150">Improvement</th>
                                        </tr>
                                    </thead>
                                    <tbody id="amLeaderboardTableBody">
                                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--gray-500);">Belum ada data</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="amLeaderboardPagination" class="pagination-wrapper"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="productLevelContent" class="tab-content">
                <div class="highlight-grid-container">
                    <div class="row-1-layout">
                        <div class="chart-card-wrapper">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="m-0 font-bold" style="color: #1e293b; font-size: 16px;">Distribusi Progres</h5>
                                    <p class="m-0 text-muted" style="font-size: 12px;">Periode Lama vs Periode Baru</p>
                                </div>
                                <select id="chartFilterCategoryProd" class="native-select" style="width: auto; min-width: 180px; height: 35px; font-size: 13px;">
                                    <option value="Total">Semua Kategori</option>
                                    <option value="PDP">PDP</option>
                                    <option value="Connectivity Bandwidth">Connectivity Bandwidth</option>
                                    <option value="Digital Product">Digital Product</option>
                                    <option value="NEUCENTRIX">NEUCENTRIX</option>
                                    <option value="Cyber Security">Cyber Security</option>
                                </select>
                            </div>
                            <div style="height: 320px; position: relative;"><canvas id="progressChartProd"></canvas></div>
                        </div>

                        <div class="metric-sq-card card-tall theme-primary clickable-card" id="cardProdPulse" onclick="showMetricInsight('prod_pulse')">
                            <div class="sq-icon"><i class="fas fa-boxes"></i></div>
                            <div class="sq-label" style="font-size: 14px;">ACTIVE OFFERINGS RATE</div>
                            <div class="sq-stat" id="metricProdPulseValue">-</div>
                            <div class="sq-sub" id="metricProdPulseSub">-</div>
                            <div style="border-top: 1px dashed #e2e8f0; width: 100%; padding-top: 8px; display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 11px; background: #f8fafc; padding: 6px; border-radius: 6px;">
                                    <div style="text-align: left;"><span style="color: #64748b; display: block; font-size: 9px;">OFFERINGS</span><span id="valProdOfferings" style="font-weight: 700;">-</span></div>
                                    <div style="text-align: right;"><span style="color: #64748b; display: block; font-size: 9px;">ACTIVE</span><span id="valProdVisited" style="font-weight: 700;">-</span></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 11px; background: #f8fafc; padding: 6px; border-radius: 6px;">
                                    <div style="text-align: left;"><span style="color: #64748b; display: block; font-size: 9px;">TOTAL CC</span><span id="valUniqueCC" style="font-weight: 700;">-</span></div>
                                    <div style="text-align: right;"><span style="color: #64748b; display: block; font-size: 9px;">PRODUCTS</span><span id="valUniqueProd" style="font-weight: 700;">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row-2-layout">
                        <div class="metric-sq-card card-horizontal theme-warning clickable-card" id="cardStagnancy" onclick="showMetricInsight('stagnancy')">
                            <div class="sq-icon"><i class="fas fa-anchor"></i></div>
                            <div class="sq-label">Stagnant Offering</div>
                            <div class="sq-value" id="metricStagnantValue">-</div>
                            <div class="sq-sub" id="metricStagnantStat">-</div>
                        </div>
                        <div class="metric-sq-card card-horizontal theme-success clickable-card" id="cardwin" onclick="showMetricInsight('win')">
                            <div class="sq-icon"><i class="fas fa-percent"></i></div>
                            <div class="sq-label">Win Rate</div>
                            <div class="sq-value" id="metricwinValue">-</div>
                            <div class="sq-sub" id="metricwinStat">-</div>
                        </div>
                        <div class="metric-sq-card card-horizontal theme-purple clickable-card" id="cardWinOffer" onclick="showMetricInsight('win_offerings')">
                            <div class="sq-icon"><i class="fas fa-trophy"></i></div>
                            <div class="sq-label">Top Selling Product</div>
                            <div class="sq-value" id="metricWinOfferValue">-</div>
                            <div class="sq-sub" id="metricWinOfferStat">-</div>
                        </div>
                        <div class="metric-sq-card card-horizontal theme-primary clickable-card" id="cardCompleted" onclick="showMetricInsight('completed')">
                            <div class="sq-icon"><i class="fas fa-check-double"></i></div>
                            <div class="sq-label">Submit SPH</div>
                            <div class="sq-value" id="metricCompletedValue">-</div>
                            <div class="sq-sub" id="metricCompletedStat">-</div>
                        </div>
                    </div>
                </div>

                <div class="product-tabs-container">
                    <div class="product-tabs-navigation">
                        <button class="product-tab-btn active" data-product-tab="benchmarking">
                            <i class="fas fa-table"></i> Benchmarking Performa Per Produk
                        </button>
                        <button class="product-tab-btn" data-product-tab="improvement">
                            <i class="fas fa-medal"></i> Leaderboard Produk (Top Improvement)
                        </button>
                        <button class="product-tab-btn" data-product-tab="product">
                            <i class="fas fa-star"></i> Leaderboard Produk (Top Selling)
                        </button>
                    </div>
                    

                    <div class="product-tab-content active" id="productBenchmarkingTab">
                        <div class="am-filter-container">
                            <div class="am-search-group">
                                <i class="fas fa-search"></i>
                                <input type="text" id="productSearchInput" placeholder="Cari AM, Customer, atau Product..." autocomplete="off">
                            </div>
                            <div class="am-filter-group">
                                <select id="witelFilter" class="native-select">
                                    <option value="">Semua Witel</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-container">
                            <div class="table-header-fixed">
                                <table class="benchmark-table">
                                    <thead>
                                        <tr>
                                            <th>AM</th>
                                            <th>Customer</th>
                                            <th>Product</th>
                                            <th>% Progress<br><small id="dataset1DateProduct">-</small></th>
                                            <th>% Progress<br><small id="dataset2DateProduct">-</small></th>
                                            <th>% Result<br><small id="dataset1ResultProduct">-</small></th>
                                            <th>% Result<br><small id="dataset2ResultProduct">-</small></th>
                                            <th>Perubahan</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-scrollable-wrapper">
                                <div class="table-responsive">
                                    <table class="benchmark-table">
                                        <thead style="display: none;"></thead>
                                        <tbody id="productBenchmarkTableBody">
                                            <tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--gray-500);">Pilih data progres untuk melihat data</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="product-tab-content" id="productImprovementTab">
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="benchmark-table leaderboard-table">
                                    <thead>
                                        <tr>
                                            <th width="100">Rank</th>
                                            <th>AM</th>
                                            <th>Customer</th>
                                            <th>Product</th>
                                            <th width="150">Improvement</th>
                                        </tr>
                                    </thead>
                                    <tbody id="improvementLeaderboardTableBody">
                                        <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--gray-500);">Belum ada data</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="improvementLeaderboardPagination" class="pagination-wrapper"></div>
                        </div>
                    </div>

                    <div class="product-tab-content" id="productLeaderboardTab">
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="benchmark-table leaderboard-table">
                                    <thead>
                                        <tr>
                                            <th width="100">Rank</th>
                                            <th>Produk</th>
                                            <th>Total Offerings</th>
                                            <th>Total Win</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productLeaderboardTableBody">
                                        <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--gray-500);">Belum ada data</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="report-actions">
                    <button type="button" id="downloadReportProduct" class="btn-download-report" disabled>
                        <i class="fas fa-file-pdf"></i> Unduh Laporan PDF
                    </button>
                </div> --}}
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Kelola Link Spreadsheet (NEW) -->
<div id="linkModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) closeLinkModal();">
    <div class="modal-container" style="display: flex; flex-direction: column; max-height: 90vh;">
        <div class="modal-header" style="position: sticky; top: 0; z-index: 10; background: white; border-bottom: 2px solid var(--gray-200);">
            <h3><i class="fas fa-cog"></i> Kelola Link Spreadsheet</h3>
            <button class="modal-close" onclick="closeLinkModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" style="overflow-y: auto; flex: 1;">
            <!-- Existing Links -->
            <div class="modal-section">
                <h4><i class="fas fa-link"></i> Link Tersedia</h4>
                <div id="existingLinksContainer">
                    <!-- Will be populated by JavaScript -->
                    <p style="text-align: center; color: var(--gray-500); padding: 20px;">Loading...</p>
                </div>
            </div>

            <!-- Add New Link Form -->
            <div class="modal-section">
                <h4><i class="fas fa-plus-circle"></i> Tambah Link Baru</h4>
                <form id="addLinkForm">
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 12px; align-items: end;">
                        <div class="field-group">
                            <label>Divisi</label>
                            <select id="newLinkDivisi" class="native-select" required>
                                <option value="">Pilih Divisi</option>
                                @foreach($divisiList as $divisi)
                                    <option value="{{ $divisi->id }}">{{ $divisi->kode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field-group">
                            <label>Link Google Spreadsheet</label>
                            <input type="url" id="newLinkUrl" class="native-select" placeholder="https://docs.google.com/spreadsheets/..." required>
                        </div>
                    </div>
                    <button type="submit" class="btn-modal-save" style="margin-top: 12px;">
                        <i class="fas fa-save"></i> Simpan Link
                    </button>
                </form>
            </div>

            <div class="modal-note">
                <i class="fas fa-info-circle"></i>
                <span>Setiap divisi hanya boleh memiliki 1 link spreadsheet aktif</span>
            </div>
        </div>
    </div>
</div>

<div id="insightModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 600px;">
        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #eff6ff; padding: 10px; border-radius: 50%; color: #2563eb;">
                    <i class="fas fa-lightbulb fa-lg"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; color: #1e293b;">Performance Insights</h3>
                    <p style="margin: 0; font-size: 13px; color: #64748b;">Analisis mendalam & rekomendasi aksi</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeInsightModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="insightModalBody" style="padding: 24px; font-size: 14px; line-height: 1.6; color: #334155;">
            <div style="text-align: center; color: var(--gray-500);"><i class="fas fa-spinner fa-spin"></i> Loading data...</div>
        </div>
        <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc; border-radius: 0 0 12px 12px;">
            <button class="btn-save-dataset" onclick="closeInsightModal()" style="background: white; border: 1px solid #cbd5e1; color: #475569;">Tutup</button>
        </div>
    </div>
</div>

<div id="linkModal" class="modal-overlay" style="display: none;">
    <div class="modal-container"><div class="modal-header"><h3>Kelola Link</h3><button class="modal-close" onclick="closeLinkModal()">X</button></div><div class="modal-body" id="existingLinksContainer"></div></div>
</div>

{{-- Modal Pilih Witel untuk Download Report --}}
<div id="reportWitelModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) closeReportWitelModal();">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-download"></i> Pilih Scope Laporan</h3>
            <button class="modal-close" onclick="closeReportWitelModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 20px; color: #64748b; font-size: 14px;">
                Pilih wilayah untuk laporan yang akan diunduh:
            </p>
            
            <div class="field-group">
                <label><i class="fas fa-map-marked-alt"></i> WILAYAH</label>
                <select class="native-select" id="reportWitelSelect" style="width: 100%;">
                    <option value="all">📊 Laporan Overall (Semua Witel)</option>
                </select>
            </div>
        </div>
        <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; padding: 16px; border-top: 1px solid #e2e8f0;">
            <button class="btn-save-dataset" onclick="closeReportWitelModal()" style="background: white; border: 1px solid #cbd5e1; color: #475569;">
                Batal
            </button>
            <button class="btn-save-dataset" onclick="downloadReportWithWitel()" style="background: var(--telkom-red); color: white;">
                <i class="fas fa-download"></i> Unduh Laporan
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {
    // ================================
    // INITIALIZATION
    // ================================
    let amProgressChart = null;
    let prodProgressChart = null;
    let globalStatusStatsAM = null;
    let globalStatusStatsProd = null;

    // Initialize Flatpickr for manual fetch
    let datePickerInstance = flatpickr("#manualSnapshotDate", {
        dateFormat: "Y-m-d",
        defaultDate: null,
        locale: {
            firstDayOfWeek: 1,
            weekdays: {
                shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            },
            months: {
                shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            },
        },
    });

    // Time Picker for Auto Fetch
    flatpickr("#autoFetchTime", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        defaultDate: "01:00",
        disableMobile: "true"
    });

    // Global variables
    let selectedSnapshot1 = null;
    let selectedSnapshot2 = null;
    let currentAMLeaderboardPage = 1;
    let currentImprovementLeaderboardPage = 1;
    let amLeaderboardData = [];
    let improvementLeaderboardData = [];
    let allProductData = []; // For witel filtering
    let fullAMData = []; // Menyimpan data asli dari backend
    let currentAMSort = 'improvement'; // Default sort  
    let globalInsightsData = {};
    const ITEMS_PER_PAGE = 10;

    // Load available links on page load
    loadAvailableLinks();

    // ================================
    // FIX: TAB INACTIVE ON EMPTY STATE
    // ================================
    if ($('#emptyState').hasClass('active')) {
        $('.tab-btn').removeClass('active');
    }

    // ================================
    // COLLAPSIBLE SECTIONS
    // ================================

    window.toggleManualFetch = function() {
        const body = $('#manualFetchBody');
        const toggle = $('#manualFetchToggle');
        body.slideToggle(300);
        body.toggleClass('active');
        toggle.toggleClass('active');
    };

    window.toggleAnalysisCards = function() {
        const body = $('#analysisCardsBody');
        const toggle = $('#analysisCardsToggle');
        body.slideToggle(300);
        toggle.toggleClass('active');
    };

    window.toggleNarrative = function() {
        const body = $('#narrativeBody');
        const toggle = $('#narrativeToggle');
        body.slideToggle(300);
        body.toggleClass('active');
        toggle.toggleClass('active');
    };

    window.toggleProductNarrative = function() {
        const body = $('#productNarrativeBody');
        const toggle = $('#productNarrativeToggle');
        body.slideToggle(300);
        body.toggleClass('active');
        toggle.toggleClass('active');
    };

    // NEW: Leaderboard Accordion Toggle
    window.toggleLeaderboard = function(type) {
        const content = $(`#${type}Leaderboard`);
        const icon = $(`#${type}AccordionIcon`);

        content.slideToggle(300);
        icon.toggleClass('rotated');
    };

    // ================================
    // ALERT SYSTEM (SOLID WHITE BG)
    // ================================

    function showAlert(type, title, message) {
        const iconMap = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-times-circle',
            'info': 'fas fa-info-circle',
            'warning': 'fas fa-exclamation-triangle'
        };

        const borderMap = {
            'success': 'var(--success)',
            'error': 'var(--error)',
            'info': '#3b82f6',
            'warning': 'var(--warning)'
        };

        const alertBox = $(`
            <div class="alert-box alert-${type}" style="background: var(--white); border: 2px solid ${borderMap[type]}; border-left: 4px solid ${borderMap[type]};">
                <i class="alert-icon ${iconMap[type]}"></i>
                <div class="alert-content">
                    <strong>${title}</strong>
                    <p>${message}</p>
                </div>
                <button class="alert-close">&times;</button>
            </div>
        `);

        $('#alertContainer').append(alertBox);

        setTimeout(() => {
            alertBox.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);

        alertBox.find('.alert-close').on('click', function() {
            alertBox.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }

    // ================================
    // MODAL MANAGEMENT (NEW)
    // ================================

    window.openLinkModal = function() {
        $('#linkModal').fadeIn(300);
        loadExistingLinks();
    };

    window.closeLinkModal = function() {
        $('#linkModal').fadeOut(300);
    };

    function loadExistingLinks() {
        $.get('/high-five/available-links', function(response) {
            if (response.success) {
                const links = response.data;
                let html = '';

                if (links.length === 0) {
                    html = '<p style="text-align: center; color: var(--gray-500); padding: 20px;">Belum ada link tersimpan</p>';
                } else {
                    links.forEach(link => {
                        // Backend returns 'link' field according to API response
                        const linkUrl = link.link || link.link_url || link.spreadsheet_url || link.url || 'URL tidak tersedia';
                        const displayUrl = linkUrl.length > 50 ? linkUrl.substring(0, 50) + '...' : linkUrl;

                        html += `
                            <div class="link-item">
                                <div class="link-info">
                                    <span class="link-divisi">${link.divisi_name || link.divisi || 'Unknown'}</span>
                                    <span class="link-url">${displayUrl}</span>
                                    <span class="link-meta">${link.total_snapshots || 0} snapshots | Last: ${link.last_fetched || 'Never'}</span>
                                </div>
                                <div class="link-actions">
                                    <button class="btn-link-edit" onclick="editLink(${link.id}, '${linkUrl.replace(/'/g, "\\'")}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-link-delete" onclick="deleteLink(${link.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }

                $('#existingLinksContainer').html(html);
            }
        }).fail(function(xhr) {
            $('#existingLinksContainer').html('<p style="text-align: center; color: var(--error); padding: 20px;">Gagal memuat data link</p>');
            console.error('Error loading links:', xhr.responseText);
        });
    }

    // Add Link Form Submit
    $('#addLinkForm').submit(function(e) {
        e.preventDefault();

        const formData = {
            divisi_id: $('#newLinkDivisi').val(),
            link_spreadsheet: $('#newLinkUrl').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '/high-five/settings/store',
            method: 'POST',
            data: formData,
            success: function(response) {
                showAlert('success', 'Berhasil!', response.message);
                $('#addLinkForm')[0].reset();
                loadExistingLinks();
                loadAvailableLinks();
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal menyimpan link';
                showAlert('error', 'Error!', message);
            }
        });
    });

    window.editLink = function(linkId, currentUrl) {
        const newUrl = prompt('Edit Link Spreadsheet:', currentUrl);
        if (newUrl && newUrl !== currentUrl) {
            $.ajax({
                url: `/high-five/settings/update/${linkId}`,
                method: 'POST',
                data: {
                    link_spreadsheet: newUrl,
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT'
                },
                success: function(response) {
                    showAlert('success', 'Berhasil!', response.message);
                    loadExistingLinks();
                    loadAvailableLinks();
                },
                error: function(xhr) {
                    showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal update link');
                }
            });
        }
    };

    window.deleteLink = function(linkId) {
        if (confirm('Hapus link ini? Semua snapshot terkait akan ikut terhapus!')) {
            $.ajax({
                url: `/high-five/settings/delete/${linkId}`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    showAlert('success', 'Berhasil!', response.message);
                    loadExistingLinks();
                    loadAvailableLinks();
                },
                error: function(xhr) {
                    showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal menghapus link');
                }
            });
        }
    };

    // ================================
    // MANUAL FETCH SECTION
    // ================================

    function loadAvailableLinks() {
        $.get('/high-five/available-links', function(response) {
            if (response.success) {
                const links = response.data;
                let options = '<option value="">Pilih Link</option>';

                links.forEach(link => {
                    options += `<option value="${link.id}"
                        data-divisi="${link.divisi_name}"
                        data-last-fetched="${link.last_fetched}"
                        data-snapshots="${link.total_snapshots}">
                        ${link.divisi_name} (${link.total_snapshots} snapshots)
                    </option>`;
                });

                $('#manualLinkSelect').html(options);
            }
        });
    }

    $('#manualLinkSelect').change(function() {
        updateManualLinkInfo();
    });

    function updateManualLinkInfo() {
        const selectedOption = $('#manualLinkSelect option:selected');
        const linkId = selectedOption.val();

        if (!linkId) {
            $('#manualLinkInfo').html('<span style="color: var(--gray-500);">Pilih link untuk melihat info</span>');
            return;
        }

        const divisi = selectedOption.data('divisi');
        const lastFetched = selectedOption.data('last-fetched');

        // UPDATED FORMAT: "DPS terakhir diupdate pada hari Selasa, 26 Nov 2025 pukul 03:58"
        const dateObj = new Date(lastFetched);
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        const dayName = days[dateObj.getDay()];
        const formattedDate = `${dateObj.getDate()} ${months[dateObj.getMonth()]} ${dateObj.getFullYear()} pukul ${dateObj.getHours().toString().padStart(2, '0')}:${dateObj.getMinutes().toString().padStart(2, '0')}`;

        $('#manualLinkInfo').html(`
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; background: var(--telkom-red-soft); color: var(--telkom-red); border-radius: 6px; font-size: 11px; font-weight: 600;">
                    <i class="fas fa-building" style="font-size: 9px;"></i>
                    ${divisi}
                </span>
                <span style="font-size: 11px; color: var(--gray-600);">
                    terakhir diupdate pada hari ${dayName}, ${formattedDate}
                </span>
            </div>
        `);
    }

    window.saveManualData = function() {
        const linkId = $('#manualLinkSelect').val();
        const snapshotDate = $('#manualSnapshotDate').val();

        if (!linkId) {
            showAlert('error', 'Error', 'Pilih link spreadsheet terlebih dahulu');
            return;
        }

        if (!snapshotDate) {
            showAlert('error', 'Error', 'Pilih tanggal snapshot');
            return;
        }

        const divisi = $('#manualLinkSelect option:selected').data('divisi');
        if (!confirm(`Simpan data ${divisi} dengan tanggal ${snapshotDate}?`)) return;

        $.ajax({
            url: '/high-five/fetch-manual',
            method: 'POST',
            data: {
                link_id: linkId,
                snapshot_date: snapshotDate,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                $('#btnSaveManual').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            },
            success: function(response) {
                showAlert('success', 'Berhasil!', `${response.message} (${response.data.total_rows} rows, ${response.data.total_ams} AMs)`);
                $('#manualLinkSelect').val('');
                datePickerInstance.clear();
                updateManualLinkInfo();
                loadAvailableLinks();

                // Reload snapshot options if same divisi
                const currentDivisi = $('#filterDivisi').val();
                if (currentDivisi) {
                    loadSnapshotOptions(currentDivisi);
                }

                $('#btnSaveManual').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Data');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal menyimpan data';
                showAlert('error', 'Error!', message);
                $('#btnSaveManual').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Data');
            }
        });
    };

    // ================================
    // SNAPSHOT DROPDOWN LOADING
    // ================================

    // Cari function loadSnapshotOptions yang lama, dan GANTI dengan yang ini:
    function loadSnapshotOptions(divisiId, callback = null) {
        $.ajax({
            url: "{{ route('high-five.snapshots') }}",
            method: 'GET',
            data: { divisi_id: divisiId },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    $('#snapshot1, #snapshot2').empty();
                    $('#snapshot1').append('<option value="">-- Pilih Data Progres 1 --</option>');
                    $('#snapshot2').append('<option value="">-- Pilih Data Progres 2 --</option>');

                    response.data.forEach(s => {
                        const option1 = $('<option></option>')
                            .attr('value', s.id)
                            .attr('data-full-label', s.label)
                            .text(s.label);

                        const option2 = $('<option></option>')
                            .attr('value', s.id)
                            .attr('data-full-label', s.label)
                            .text(s.label);

                        $('#snapshot1').append(option1);
                        $('#snapshot2').append(option2);
                    });

                    $('#snapshot1, #snapshot2').prop('disabled', false);
                    selectedSnapshot1 = null;
                    selectedSnapshot2 = null;
                    $('#loadBenchmarkBtn').prop('disabled', true);

                    // --- TAMBAHAN: Jalankan callback jika ada (untuk restore state) ---
                    if (callback) callback(); 

                } else {
                    $('#snapshot1, #snapshot2').empty().prop('disabled', true).append('<option value="">-- Tidak ada data progres --</option>');
                    showAlert('info', 'Info', 'Belum ada data progres untuk divisi ini.');
                }
            },
            error: function() {
                $('#snapshot1, #snapshot2').empty().prop('disabled', true).append('<option value="">-- Error loading --</option>');
                showAlert('error', 'Error!', 'Gagal memuat data progres');
            }
        });
    }

    // ==========================================
    // 1. AUTO-RESTORE STATE DARI LOCAL STORAGE
    // ==========================================
    const savedDivisi = localStorage.getItem('hf_divisi');
    
    if (savedDivisi) {
        // Set nilai divisi
        $('#filterDivisi').val(savedDivisi);
        
        // Panggil fungsi load options dengan CALLBACK restore snapshot
        loadSnapshotOptions(savedDivisi, function() {
            const savedSn1 = localStorage.getItem('hf_sn1');
            const savedSn2 = localStorage.getItem('hf_sn2');
            const isLoaded = localStorage.getItem('hf_loaded');

            if (savedSn1) {
                // Restore Snapshot 1
                $('#snapshot1').val(savedSn1);
                selectedSnapshot1 = savedSn1; // Update variabel global
                
                // Generate opsi untuk Snapshot 2 (filter opsi yg sama)
                updateSnapshot2Options();

                if (savedSn2) {
                    // Restore Snapshot 2
                    $('#snapshot2').val(savedSn2);
                    selectedSnapshot2 = savedSn2; // Update variabel global
                }

                // Cek tombol load
                checkCanLoad();

                // Jika sebelumnya user sudah klik Load, otomatis load datanya
                if (isLoaded === 'true' && savedSn1 && savedSn2) {
                    loadBenchmarkingData();
                }
            }
        });
    }

    // ==========================================
    // 2. SIMPAN STATE SAAT USER BERINTERAKSI
    // ==========================================

    $('#filterDivisi').on('change', function() {
        const val = $(this).val();
        // Simpan Divisi
        localStorage.setItem('hf_divisi', val);
        
        // Reset state snapshot & loaded karena divisi berubah
        localStorage.removeItem('hf_sn1');
        localStorage.removeItem('hf_sn2');
        localStorage.removeItem('hf_loaded');

        if (val) {
            loadSnapshotOptions(val);
            $('#snapshot1, #snapshot2').prop('disabled', false);
        } else {
            $('#snapshot1, #snapshot2').empty().prop('disabled', true);
            $('#loadBenchmarkBtn').prop('disabled', true);
        }
    });

    $('#snapshot1').on('change', function() {
        selectedSnapshot1 = $(this).val();
        localStorage.setItem('hf_sn1', selectedSnapshot1); // Simpan
        
        updateSnapshot2Options();
        checkCanLoad();
        
        // Kalau user ganti snapshot, anggap data belum ter-load (reset tampilan)
        localStorage.removeItem('hf_loaded'); 
    });

    $('#snapshot2').on('change', function() {
        selectedSnapshot2 = $(this).val();
        localStorage.setItem('hf_sn2', selectedSnapshot2); // Simpan
        
        checkCanLoad();
        localStorage.removeItem('hf_loaded'); 
    });

    // ... sisa kode lainnya (loadBenchmarkingData, render functions, dll) ...

    function updateSnapshot2Options() {
        const snapshot1Val = $('#snapshot1').val();
        const currentSnapshot2Val = $('#snapshot2').val();

        const allOptions = [];
        $('#snapshot1 option').each(function() {
            if ($(this).val() !== '') {
                allOptions.push({
                    value: $(this).val(),
                    label: $(this).text(),
                    fullLabel: $(this).data('full-label')
                });
            }
        });

        $('#snapshot2').empty().append('<option value="">-- Pilih Data Progres 2 --</option>');

        allOptions.forEach(opt => {
            if (opt.value !== snapshot1Val) {
                const option = $('<option></option>')
                    .attr('value', opt.value)
                    .attr('data-full-label', opt.fullLabel || opt.label)
                    .text(opt.label);
                $('#snapshot2').append(option);
            }
        });

        if (currentSnapshot2Val && currentSnapshot2Val !== snapshot1Val) {
            $('#snapshot2').val(currentSnapshot2Val);
        } else {
            $('#snapshot2').val('');
            selectedSnapshot2 = null;
        }
    }

    function checkCanLoad() {
        selectedSnapshot1 = $('#snapshot1').val();
        selectedSnapshot2 = $('#snapshot2').val();

        const canLoad = selectedSnapshot1 && selectedSnapshot2 &&
                       selectedSnapshot1 !== '' && selectedSnapshot2 !== '' &&
                       selectedSnapshot1 !== selectedSnapshot2;

        $('#loadBenchmarkBtn').prop('disabled', !canLoad);
    }

    // ================================
    // LOAD BENCHMARKING DATA
    // ================================

    $('#loadBenchmarkBtn').on('click', function() {
        localStorage.setItem('hf_loaded', 'true'); // Tandai data sudah diload
        loadBenchmarkingData();
    });
    // Modifikasi listener tombol load yang sudah ada
    function loadBenchmarkingData() {
        $('#emptyState').removeClass('active');
        $('#loadingState').addClass('active');
        $('#amLevelContent, #productLevelContent').removeClass('active');

        // Remove all active tabs
        $('.tab-btn').removeClass('active');

        // ==========================================
        // 1. LOAD AM PERFORMANCE (Disini Perbaikannya)
        // ==========================================
        $.ajax({
            url: "{{ route('high-five.am-performance') }}",
            method: 'GET',
            data: {
                snapshot_1_id: selectedSnapshot1,
                snapshot_2_id: selectedSnapshot2
            },
            success: function(response) {
                if (response.success) {
                    // BENAR: Simpan data AM disini
                    fullAMData = response.data.benchmarking; 
                    
                    // BENAR: Render komponen AM
                    renderAMPerformance(response.data);
                    
                    // BENAR: Terapkan filter default
                    applyAMFilters();
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal memuat data AM';
                showAlert('error', 'Error!', message);
                // Jangan matikan loading state disini kalau Product belum selesai, 
                // tapi untuk UX sederhana tidak apa-apa alert error dulu.
            }
        });

        // ==========================================
        // 2. LOAD PRODUCT PERFORMANCE (Kembalikan ke Asal)
        // ==========================================
        $.ajax({
            url: "{{ route('high-five.product-performance') }}",
            method: 'GET',
            data: {
                snapshot_1_id: selectedSnapshot1,
                snapshot_2_id: selectedSnapshot2
            },
            success: function(response) {
                if (response.success) {
                    // BENAR: Render Product Performance (JANGAN render AM disini)
                    renderProductPerformance(response.data);
                    
                    $('#loadingState').removeClass('active');

                    // Activate first tab AFTER data is loaded
                    $('.tab-btn[data-tab="am-level"]').addClass('active');
                    $('#amLevelContent').addClass('active');

                    $('#downloadReportAM, #downloadReportProduct').prop('disabled', false);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal memuat data Product';
                showAlert('error', 'Error!', message);
                $('#loadingState').removeClass('active');
                $('#emptyState').addClass('active');
            }
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    $('#amSearchInput').on('keyup', debounce(function() {
        applyAMFilters();
    }, 300));

    // Untuk dropdown dan button sort tidak perlu debounce karena klik-nya jarang
    $('#amStatusFilter').on('change', function() {
        applyAMFilters();
    });

    $('.btn-sort').on('click', function() {
        $('.btn-sort').removeClass('active');
        $(this).addClass('active');
        currentAMSort = $(this).data('sort');
        applyAMFilters();
    });

    // ✅ Delta Plugin Factory - Define once, reuse for each chart update
    function createDeltaPlugin(deltas, chartType) {
        return {
            id: 'deltaLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                const meta = chart.getDatasetMeta(1); // Dataset index 1 = Snapshot 2
                
                ctx.save();
                ctx.font = 'bold 14px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                
                // Use chart color instead of green/red
                const deltaColor = chartType === 'am' ? '#ef4444' : '#3b82f6';
                
                meta.data.forEach((bar, index) => {
                    const delta = Object.values(deltas)[index];
                    if (delta === 0) return; // Don't show if no change
                    
                    const text = delta > 0 ? `+${delta}` : `${delta}`;
                    
                    ctx.fillStyle = deltaColor;
                    ctx.fillText(text, bar.x, bar.y - 5);
                });
                
                ctx.restore();
            }
        };
    }

    // FUNGSI RENDER CHART.JS
    function updateProgressChart(canvasId, statsData, filterValue, type) {
        if (!statsData) return;

        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        const ss1 = statsData.ss1[filterValue] || { visit: 0, mytens: 0, presentasi: 0, sph: 0 };
        const ss2 = statsData.ss2[filterValue] || { visit: 0, mytens: 0, presentasi: 0, sph: 0 };

        // Calculate delta for each category
        const deltas = {
            visit: ss2.visit - ss1.visit,
            mytens: ss2.mytens - ss1.mytens,
            presentasi: ss2.presentasi - ss1.presentasi,
            sph: ss2.sph - ss1.sph
        };

        const chartData = {
            labels: ['Visit', 'Input MyTens', 'Presentasi', 'Submit SPH'],
            datasets: [
                {
                    label: 'Snapshot 1 (Lama)',
                    data: [ss1.visit, ss1.mytens, ss1.presentasi, ss1.sph],
                    backgroundColor: '#cbd5e1',
                    borderRadius: 6,
                },
                {
                    label: 'Snapshot 2 (Baru)',
                    data: [ss2.visit, ss2.mytens, ss2.presentasi, ss2.sph],
                    backgroundColor: type === 'am' ? '#ef4444' : '#3b82f6', 
                    borderRadius: 6,
                }
            ]
        };

        if (type === 'am' && amProgressChart) amProgressChart.destroy();
        if (type === 'prod' && prodProgressChart) prodProgressChart.destroy();

        const chartConfig = {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 200,
                layout: {
                    padding: {
                        top: 25
                    }
                },
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { boxWidth: 12, usePointStyle: true } 
                    },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        ticks: { stepSize: 1 } 
                    }
                }
            },
            plugins: [createDeltaPlugin(deltas, type)] // ✅ Use factory to create fresh plugin
        };

        if (type === 'am') amProgressChart = new Chart(ctx, chartConfig);
        if (type === 'prod') prodProgressChart = new Chart(ctx, chartConfig);
    }

    // EVENT LISTENER UNTUK DROPDOWN FILTER
    $(document).on('change', '#chartFilterWitelAM', function() {
        updateProgressChart('progressChartAM', globalStatusStatsAM, $(this).val(), 'am');
    });

    $(document).on('change', '#chartFilterCategoryProd', function() {
        updateProgressChart('progressChartProd', globalStatusStatsProd, $(this).val(), 'prod');
    });

    // ==========================================
    // RENDER AM PERFORMANCE (FIXED MAPPING)
    // ==========================================
    function renderAMPerformance(data) {
        // ... (label dataset update sama) ...
        $('#dataset1NameCard').text(`Data ${data.snapshot_1.label}`);
        $('#dataset2NameCard').text(`Data ${data.snapshot_2.label}`);
        $('#dataset1DateAM, #dataset1ResultAM').text(data.snapshot_1.tanggal_formatted);
        $('#dataset2DateAM, #dataset2ResultAM').text(data.snapshot_2.tanggal_formatted);

        const m = data.witel_analysis.metrics;

        // 1. TREG3 Pulse (TALL CARD)
        if(m.TREG3) {
            $('#metricNatValue').text(m.TREG3.value);
            $('#metricNatTrend').html(m.TREG3.trend_text);
            
            // Update warna kartu
            $('#cardTREG3').removeClass('theme-success theme-danger')
                .addClass(m.TREG3.trend >= 0 ? 'theme-success' : 'theme-danger');
            
            // UPDATE: Isi data statistik detail baru
            $('#valOfferings').text(m.TREG3.offerings);
            $('#valVisited').text(`${m.TREG3.visited}/${m.TREG3.total_customers}`);
            $('#valWins').text(m.TREG3.wins);
            $('#valLoses').text(m.TREG3.loses);
        }

        // 2. Witel Champion
        if(m.most_witel) {
            $('#metricMostName').text(m.most_witel.value);
            $('#metricMostStat').text(m.most_witel.main_stat);
        }

        // 3. Focus Area
        if(m.least_witel) {
            $('#metricLeastName').text(m.least_witel.value);
            $('#metricLeastStat').text(m.least_witel.main_stat);
        }

        // 4. MVP AM
        if(m.top_am) {
            $('#metricTopAMName').text(m.top_am.value);
            $('#metricTopAMStat').text(m.top_am.main_stat);
        }

        // 5. Top Sales AM
        if(m.am_most_win) {
            $('#metricAmWinName').text(m.am_most_win.value);
            $('#metricAmWinStat').text(m.am_most_win.main_stat);
        }

        // === TAMBAHKAN KODE INI ===
        globalStatusStatsAM = data.status_stats; 
        
        // Update daftar Witel di dropdown filter chart
        let filterOptions = '<option value="Total">Seluruh TREG3</option>';
        if(globalStatusStatsAM && globalStatusStatsAM.ss2) {
            const witels = Object.keys(globalStatusStatsAM.ss2)
                .filter(w => w !== 'Total')
                .sort();
            witels.forEach(w => {
                filterOptions += `<option value="${w}">${w}</option>`;
            });
        }
        $('#chartFilterWitelAM').html(filterOptions);

        // Jalankan render chart pertama kali
        updateProgressChart('progressChartAM', globalStatusStatsAM, 'Total', 'am');

        // ... (Simpan Insight & Render Table sama) ...
        globalInsightsData = data.witel_analysis.insights_data; 
        currentBenchmarkingData = data.benchmarking; // ✅ Store for Witel modal
        const tableHTML = renderAMTable(data.benchmarking);
        $('#amBenchmarkTableBody').html(tableHTML);
        // ... (Leaderboard render) ...
        amLeaderboardData = data.leaderboard;
        currentAMLeaderboardPage = 1;
        const leaderboardResult = renderLeaderboard(amLeaderboardData, 1, ITEMS_PER_PAGE, 'am');
        $('#amLeaderboardTableBody').html(leaderboardResult.html);
        $('#amLeaderboardPagination').html(leaderboardResult.pagination);
    }
    
    // ==========================================
    // FUNGSI MODAL INSIGHTS (PER ITEM)
    // ==========================================
    
    // Dipanggil saat tombol (?) kecil di kartu diklik
    window.showMetricInsight = function(type) {
        // Ambil konten HTML spesifik berdasarkan type (progress/result/active_am/top_mover)
        const content = globalInsightsData ? globalInsightsData[type] : null;

        if (content) {
            $('#insightModalBody').html(content);
        } else {
            $('#insightModalBody').html('<p class="text-center text-gray-500">Data insight belum tersedia. Silakan muat data terlebih dahulu.</p>');
        }

        // Tampilkan Modal
        $('#insightModal').fadeIn(200);
    };

    // Dipanggil saat tombol Tutup / X diklik
    window.closeInsightModal = function() {
        $('#insightModal').fadeOut(200);
    };

    // Close modal when clicking outside (on overlay)
    $('#insightModal').on('click', function(e) {
        // Check if click is on the overlay itself, not the modal container
        if (e.target.id === 'insightModal') {
            closeInsightModal();
        }
    });

    // Close modal when pressing ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if ($('#insightModal').is(':visible')) {
                closeInsightModal();
            }
        }
    });

    // NEW: Format narrative with bold for numbers
    function formatNarrativeWithBold(text) {
        // Bold percentages: 37.38% -> <strong>37.38%</strong>
        text = text.replace(/(\d+\.?\d*%)/g, '<strong>$1</strong>');

        // Bold Witel names (uppercase words)
        text = text.replace(/Witel ([A-Z\s]+)/g, 'Witel <strong>$1</strong>');

        // Bold key phrases
        text = text.replace(/progress tertinggi/gi, '<strong>progress tertinggi</strong>');
        text = text.replace(/progress terendah/gi, '<strong>progress terendah</strong>');

        return text;
    }

    // ================================
    // RENDER PRODUCT PERFORMANCE
    // ================================

    function renderProductPerformance(data) {
        // 1. Set Labels Dataset
        $('#dataset1DateProduct, #dataset1ResultProduct').text(data.snapshot_1.tanggal_formatted);
        $('#dataset2DateProduct, #dataset2ResultProduct').text(data.snapshot_2.tanggal_formatted);

        // 2. Ambil data metrics baru dari Controller
        const m = data.product_analysis.metrics;
        
        // Simpan insights product ke variable global agar Modal bisa membacanya
        // Kita merge dengan insight AM agar tidak saling menimpa jika user switch tab
        if (data.product_analysis.insights_data) {
            globalInsightsData = { ...globalInsightsData, ...data.product_analysis.insights_data };
        }

        // --- RENDER KARTU 1: PRODUCTIVITY PULSE (TALL CARD) ---
        if(m.prod_pulse) {
            $('#metricProdPulseValue').text(m.prod_pulse.value);
            $('#metricProdPulseSub').text(m.prod_pulse.trend_text);
            
            // Detail statistik kecil di dalam kartu
            $('#valProdOfferings').text(m.prod_pulse.total_offerings);
            $('#valProdVisited').text(m.prod_pulse.active_count); // Ambil dari active_count
            $('#valUniqueCC').text(m.prod_pulse.unique_cc);
            $('#valUniqueProd').text(m.prod_pulse.unique_products);
        }

        // --- RENDER KARTU 2: STAGNANCY ---
        if(m.stagnancy) {
            $('#metricStagnantValue').text(m.stagnancy.value);
            $('#metricStagnantStat').text(m.stagnancy.main_stat);
            
            // Warna warning jika stagnant tinggi
            $('#cardStagnancy').removeClass('theme-success theme-warning theme-danger')
                .addClass(m.stagnancy.trend < 0 ? 'theme-danger' : 'theme-warning');
        }

        // --- RENDER KARTU 3: win RATE ---
        if(m.win) {
            $('#metricwinValue').text(m.win.value);
            $('#metricwinStat').text(m.win.main_stat);
        }

        // --- RENDER KARTU 4: WIN / OFFERINGS ---
        if(m.win_offerings) {
            $('#metricWinOfferValue').text(m.win_offerings.value);
            $('#metricWinOfferStat').text(m.win_offerings.main_stat);
        }

        // --- RENDER KARTU 5: COMPLETED ---
        if(m.completed) {
            $('#metricCompletedValue').text(m.completed.value);
            $('#metricCompletedStat').text(m.completed.main_stat);
        }

        // --- UPDATE NARRATIVE (Opsional, sesuaikan dengan data baru jika perlu) ---
        // Gunakan data dari m.prod_pulse atau m.stagnancy untuk narrative text
        const visitedText = `Dari <strong>${m.prod_pulse.total_offerings}</strong> offerings, sebanyak <strong>${m.prod_pulse.visited_count}</strong> (<strong>${m.prod_pulse.value}</strong>) telah divisit.`;
        const progressText = `Terdapat <strong>${m.stagnancy.main_stat}</strong> (${m.stagnancy.value}) yang tidak mengalami pergerakan sama sekali.`;
        
        $('#productNarrativeVisited').html(visitedText);
        $('#productNarrativeProgress').html(progressText);

        // --- SISA LOGIKA RENDER TABLE (Tetap Sama) ---
        allProductData = data.products;
        populateWitelFilter(data.products);
        applyProductFilters();

        const productLeaderboard = data.product_leaderboard.top_10;
        const productLeaderboardHTML = renderProductLeaderboard(productLeaderboard);
        $('#productLeaderboardTableBody').html(productLeaderboardHTML);

        improvementLeaderboardData = data.improvement_leaderboard;
        currentImprovementLeaderboardPage = 1;
        const improvementResult = renderLeaderboard(improvementLeaderboardData, 1, ITEMS_PER_PAGE, 'improvement');
        $('#improvementLeaderboardTableBody').html(improvementResult.html);
        $('#improvementLeaderboardPagination').html(improvementResult.pagination);

        globalStatusStatsProd = data.status_stats; 
        
        // Langsung render chart (filter kategori sudah hardcode di HTML)
        updateProgressChart('progressChartProd', globalStatusStatsProd, 'Total', 'prod');
    }

    // ================================
    // WITEL FILTER (FIX UNDEFINED)
    // ================================

    function populateWitelFilter(products) {
        // Extract unique witels and filter out undefined/null
        const witels = [...new Set(products.map(p => p.witel).filter(w => w))].sort();

        let options = '<option value="">Semua Witel</option>';
        witels.forEach(witel => {
            options += `<option value="${witel}">${witel}</option>`;
        });
        $('#witelFilter').html(options);
    }

    $('#witelFilter').on('change', function() {
        applyProductFilters();
    });

    $('#productSearchInput').on('keyup', debounce(function() {
        applyProductFilters();
    }, 300));

    $('#productStatusFilter').on('change', function() {
        applyProductFilters();
    });

    // ================================
    // RECALCULATE ROWSPAN FOR FILTERED DATA
    // ================================
    function recalculateProductRowspans(data) {
        if (!data || data.length === 0) return data;

        // Create a deep copy to avoid mutating original data
        const processedData = data.map(row => ({...row}));

        // Group by AM first
        const amGroups = {};
        processedData.forEach(row => {
            if (!amGroups[row.am]) {
                amGroups[row.am] = [];
            }
            amGroups[row.am].push(row);
        });

        // For each AM group, calculate rowspan
        Object.keys(amGroups).forEach(am => {
            const amRows = amGroups[am];
            
            // Set AM rowspan on first row only
            amRows[0].am_rowspan = amRows.length;
            for (let i = 1; i < amRows.length; i++) {
                amRows[i].am_rowspan = 0;
            }

            // Within each AM, group by Customer
            const customerGroups = {};
            amRows.forEach(row => {
                const customerKey = row.customer || 'NO_CUSTOMER';
                if (!customerGroups[customerKey]) {
                    customerGroups[customerKey] = [];
                }
                customerGroups[customerKey].push(row);
            });

            // Calculate customer rowspan
            let customerIndex = 0;
            Object.keys(customerGroups).forEach(customer => {
                const customerRows = customerGroups[customer];
                
                // Find the first row for this customer in the AM group
                const firstCustomerRow = amRows[customerIndex];
                firstCustomerRow.customer_rowspan = customerRows.length;
                
                // Set rowspan to 0 for subsequent rows of the same customer
                for (let i = 1; i < customerRows.length; i++) {
                    amRows[customerIndex + i].customer_rowspan = 0;
                }
                
                customerIndex += customerRows.length;
            });
        });

        return processedData;
    }

    function applyProductFilters() {
        let filteredData = [...allProductData]; // Clone array
        const searchValue = $('#productSearchInput').val().toLowerCase();
        const statusValue = $('#productStatusFilter').val();
        const selectedWitel = $('#witelFilter').val();

        // A. FILTERING
        filteredData = filteredData.filter(row => {
            // Filter by Search (AM, Customer, Product)
            const searchMatch = row.am.toLowerCase().includes(searchValue) ||
                               row.customer.toLowerCase().includes(searchValue) ||
                               row.product.toLowerCase().includes(searchValue);

            // Filter by Witel
            const witelMatch = !selectedWitel || row.witel === selectedWitel;

            // Filter by Status
            let statusMatch = true;
            const resultVal = parseFloat(row.result_2 || 0);
            const progressVal = parseFloat(row.progress_2 || 0);
            const hasWin = (row.result_2 || 0) == 100;
            const hasLose = (row.result_2 || 0) < 100 && (row.progress_2 || 0) > 0;

            if (statusValue === 'has_win') {
                statusMatch = hasWin;
            } else if (statusValue === 'has_lose') {
                statusMatch = hasLose;
            } else if (statusValue === 'result_gt_50') {
                statusMatch = resultVal > 50;
            } else if (statusValue === 'result_lt_50') {
                statusMatch = resultVal < 50;
            } else if (statusValue === 'progress_0') {
                statusMatch = progressVal === 0;
            }

            return searchMatch && witelMatch && statusMatch;
        });

        // B. RECALCULATE ROWSPANS FOR FILTERED DATA
        filteredData = recalculateProductRowspans(filteredData);

        // C. RENDER FILTERED DATA
        const tableHTML = renderProductTable(filteredData);
        $('#productBenchmarkTableBody').html(tableHTML);
    }

    // ================================
    // TABLE RENDERERS WITH PROGRESS BARS
    // ================================
    // Fungsi Filtering & Sorting
    function applyAMFilters() {
        let filteredData = [...fullAMData]; // Clone array agar data asli aman
        const searchValue = $('#amSearchInput').val().toLowerCase();
        const statusValue = $('#amStatusFilter').val();

        // A. FILTERING
        filteredData = filteredData.filter(row => {
            // Filter by Name or Witel
            const nameMatch = row.am.toLowerCase().includes(searchValue) ||
                             row.witel.toLowerCase().includes(searchValue);
            
            // Filter by Status
            let statusMatch = true;
            const stats = row.stats || { win: 0, lose: 0 };
            
            // Mengambil nilai result dan progress terbaru (snapshot 2)
            const resultVal = parseFloat(row.result_2 || 0);
            const progressVal = parseFloat(row.progress_2 || 0);

            if (statusValue === 'has_win') {
                statusMatch = stats.win > 0;
            } else if (statusValue === 'has_lose') {
                statusMatch = stats.lose > 0;
            } else if (statusValue === 'result_gt_50') {
                statusMatch = resultVal > 50; // Filter > 50% Result
            } else if (statusValue === 'result_lt_50') {
                statusMatch = resultVal < 50; // Filter < 50% Result
            } else if (statusValue === 'progress_0') {
                statusMatch = progressVal === 0; // Filter 0% Progress (Need Attention)
            }

            return nameMatch && statusMatch;
        });

        // B. SORTING (Tidak Berubah - Tetap Witel First)
        filteredData.sort((a, b) => {
            // 1. Primary Sort: Witel (A-Z)
            const witelCompare = a.witel.localeCompare(b.witel);
            if (witelCompare !== 0) return witelCompare;

            // 2. Secondary Sort: Sesuai tombol sort
            let valA, valB;
            switch (currentAMSort) {
                case 'win': valA = a.stats?.win || 0; valB = b.stats?.win || 0; break;
                case 'cc': valA = a.stats?.visited || 0; valB = b.stats?.visited || 0; break;
                case 'result': valA = a.result_2 || 0; valB = b.result_2 || 0; break;
                case 'improvement': default: valA = a.change_avg || 0; valB = b.change_avg || 0; break;
            }
            return valB - valA;
        });

        // C. RENDER ULANG TABEL
        const tableHTML = renderAMTable(filteredData);
        $('#amBenchmarkTableBody').html(tableHTML);
    }

    function renderAMTable(data) {
        if (!data || data.length === 0) {
            return '<tr><td colspan="7" style="text-align: center; padding: 30px; color: var(--gray-500);">Tidak ada data</td></tr>';
        }

        const containerStyle = 'display: flex; gap: 6px; width: 100%; margin-top: 6px;';
        // flex: 1 1 0px -> Kunci agar lebar selalu dibagi rata (25% per blok jika ada 4 blok)
        const badgeStyle = 'display: flex; flex: 1 1 0px; justify-content: center; align-items: center; padding: 4px 2px; border-radius: 4px; font-size: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;';
        const iconStyle = 'font-size: 9px; margin-right: 4px;';
        
        const grayBadge = 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;';
        const winBadge = 'background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 600;';
        const loseBadge = 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 600;';

        const grouped = {};
        const witelOrder = [];

        data.forEach(row => {
            if (!grouped[row.witel]) {
                grouped[row.witel] = [];
                witelOrder.push(row.witel);
            }
            grouped[row.witel].push(row);
        });

        let html = '';

        witelOrder.forEach(witel => {
            const rows = grouped[witel];

            // Hitung Rata-rata
            const avgProgress1 = rows.reduce((a, b) => a + b.progress_1, 0) / rows.length;
            const avgProgress2 = rows.reduce((a, b) => a + b.progress_2, 0) / rows.length;
            const avgResult1 = rows.reduce((a, b) => a + b.result_1, 0) / rows.length;
            const avgResult2 = rows.reduce((a, b) => a + b.result_2, 0) / rows.length;
            const avgChange = rows.reduce((a, b) => a + b.change_avg, 0) / rows.length;
            
            const avgChangeClass = avgChange > 0 ? 'positive' : (avgChange < 0 ? 'negative' : 'neutral');
            const avgChangeIcon = avgChange > 0 ? 'fa-arrow-up' : (avgChange < 0 ? 'fa-arrow-down' : 'fa-minus');

            // --- 1. RENDER BARIS RATA-RATA ---
            html += '<tr style="background-color: var(--gray-100); font-weight: 700;">'; // Pakai hex f8fafc agar aman/konsisten
            
            html += `<td rowspan="${rows.length + 1}" style="vertical-align: top; padding-top: 14px; width: 200px; min-width: 200px;">
                ${witel}
            </td>`;

            html += `<td style="padding: 12px; color: var(--telkom-red); letter-spacing: 0.5px; width: 320px; min-width: 320px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: 800; font-style: italic;">RATA-RATA</span>
                </div>
            </td>`;

            html += renderProgressCell(avgProgress1, true);
            html += renderProgressCell(avgProgress2, true, avgProgress1);
            html += renderProgressCell(avgResult1, true);
            html += renderProgressCell(avgResult2, true, avgResult1);

            html += `<td style="text-align: center;">
                <span class="change-indicator ${avgChangeClass}">
                    <i class="fas ${avgChangeIcon}"></i>
                    ${avgChange > 0 ? '+' : ''}${avgChange.toFixed(2)}%
                </span>
            </td>`;
            html += '</tr>';

            // --- 2. RENDER BARIS AM ---
            rows.forEach(row => {
                const s = row.stats || { offerings: 0, total_customers: 0, win: 0, lose: 0 };

                // --- LOGIKA BADGE 4 BLOK RATA ---
                let badges = [];

                // Blok 1: CC Visited (Selalu ada)
                badges.push(`<span style="${badgeStyle} ${grayBadge}" title="${s.visited}/${s.total_customers} Corporate Customers Visited">${s.visited}/${s.total_customers} CC visited</span>`);

                // Blok 2 & 3: Win & Lose (Dinamis)
                // Jika Win ada, masukkan.
                if (s.win > 0) {
                    badges.push(`<span style="${badgeStyle} ${winBadge}" title="${s.win} Win">${s.win} Win</span>`);
                }
                
                // Jika Lose ada, masukkan.
                if (s.lose > 0) {
                    badges.push(`<span style="${badgeStyle} ${loseBadge}" title="${s.lose} Lose">${s.lose} Lose</span>`);
                }

                // Padding: Isi sisa slot dengan elemen kosong agar total tetap 3 blok (karena offerings dihapus)
                while (badges.length < 3) {
                    badges.push(`<span style="flex: 1 1 0px;"></span>`);
                }

                html += '<tr>';
                html += `<td style="padding: 8px 12px; width: 320px; min-width: 320px;">
                    <div style="font-weight: 600; color: #1e293b;">${row.am}</div>
                    <div style="${containerStyle}">
                        ${badges.join('')}
                    </div>
                </td>`;

                html += renderProgressCell(row.progress_1);
                html += renderProgressCell(row.progress_2, false, row.progress_1);
                html += renderProgressCell(row.result_1);
                html += renderProgressCell(row.result_2, false, row.result_1);

                const change = row.change_avg;
                const changeClass = change > 0 ? 'positive' : (change < 0 ? 'negative' : 'neutral');
                const changeIcon = change > 0 ? 'fa-arrow-up' : (change < 0 ? 'fa-arrow-down' : 'fa-minus');
                
                html += `<td style="text-align: center;">
                    <span class="change-indicator ${changeClass}">
                        <i class="fas ${changeIcon}"></i>
                        ${change > 0 ? '+' : ''}${change.toFixed(2)}%
                    </span>
                </td>`;
                html += '</tr>';
            });
        });

        return html;
    }

    // NEW: Render witel summary row
    function renderWitelSummary(rows, witelName) {
        const avgProgress1 = rows.reduce((sum, r) => sum + r.progress_1, 0) / rows.length;
        const avgProgress2 = rows.reduce((sum, r) => sum + r.progress_2, 0) / rows.length;
        const avgResult1 = rows.reduce((sum, r) => sum + r.result_1, 0) / rows.length;
        const avgResult2 = rows.reduce((sum, r) => sum + r.result_2, 0) / rows.length;
        const avgChange = rows.reduce((sum, r) => sum + r.change_avg, 0) / rows.length;

        return `
            <tr class="witel-summary">
                <td colspan="2"><strong>Rerata ${witelName}</strong></td>
                ${renderProgressCell(avgProgress1)}
                ${renderProgressCell(avgProgress2, false, avgProgress1)}
                ${renderProgressCell(avgResult1)}
                ${renderProgressCell(avgResult2, false, avgResult1)}
                <td style="text-align: center;">
                    <span class="change-indicator ${avgChange > 0 ? 'positive' : (avgChange < 0 ? 'negative' : 'neutral')}">
                        <i class="fas fa-${avgChange > 0 ? 'arrow-up' : (avgChange < 0 ? 'arrow-down' : 'minus')}"></i>
                        ${avgChange > 0 ? '+' : ''}${avgChange.toFixed(2)}%
                    </span>
                </td>
            </tr>
        `;
    }

    // Render progress cell with bar
    // Helper Function untuk Render Cell Progress
    function renderProgressCell(value, isSummary = false, previousValue = null) {
        const percentage = typeof value === 'number' ? value : 0;
        
        // 1. Setting Style Background Cell (Optional: pakai var gray-50)
        const bgStyle = isSummary ? 'background-color: var(--gray-100);' : '';
        
        // Cek apakah ini data minggu 2 dan ada perubahan
        const hasChange = previousValue !== null && percentage !== previousValue;
        
        // 2. Setting Style Text
        let valueStyle;
        if (percentage === 0) {
            // Abu-abu untuk 0%
            valueStyle = isSummary 
                ? 'font-size: 13px; font-weight: 700; color: #9ca3af;' 
                : 'font-size: 13px; font-weight: 600; color: #9ca3af;';
        } else if (hasChange) {
            // Hijau untuk minggu 2 dengan perubahan
            valueStyle = isSummary 
                ? 'font-size: 13px; font-weight: 700; color: #10b981;' 
                : 'font-size: 13px; font-weight: 600; color: #10b981;';
        } else {
            // Merah untuk minggu 1 atau minggu 2 tanpa perubahan
            valueStyle = isSummary 
                ? 'font-size: 13px; font-weight: 700; color: var(--telkom-red);' 
                : 'font-size: 13px; font-weight: 600; color: var(--telkom-red);';
        }
        
        // 3. SETTING WARNA BAR
        let barColor;
        
        if (percentage === 0) {
            // Abu-abu untuk 0%
            barColor = '#9ca3af';
        } else if (hasChange) {
            // Hijau untuk minggu 2 dengan perubahan
            barColor = isSummary 
                ? '#10b981' 
                : 'linear-gradient(90deg, #6ee7b7, #10b981)';
        } else if (isSummary) {
            // Merah untuk rata-rata
            barColor = 'var(--telkom-red)';
        } else {
            // Merah untuk minggu 1 atau minggu 2 tanpa perubahan
            barColor = 'linear-gradient(90deg, #ffb3b3ff, var(--telkom-red))';
        }

        return `
            <td style="text-align: center; ${bgStyle}">
                <div class="progress-cell">
                    <span style="${valueStyle}">${percentage.toFixed(2)}%</span>
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar-fill" style="width: ${percentage}%; background: ${barColor};"></div>
                    </div>
                </div>
            </td>
        `;
    }

    function renderProductTable(data) {
        if (!data || data.length === 0) {
            return '<tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--gray-500);">Tidak ada data</td></tr>';
        }

        let html = '';
        data.forEach((row) => {
            html += '<tr>';

            if (row.am_rowspan > 0) {
                html += `<td class="am-cell" rowspan="${row.am_rowspan}"><strong>${row.am}</strong></td>`;
            }

            if (row.customer_rowspan > 0) {
                // HANDLE EMPTY CUSTOMER
                const customerDisplay = row.customer
                    ? row.customer
                    : '<span class="no-customer-data"><i class="fas fa-exclamation-circle"></i> DATA CC TIDAK DITEMUKAN</span>';
                html += `<td class="customer-cell" rowspan="${row.customer_rowspan}">${customerDisplay}</td>`;
            }

            html += `<td>${row.product}</td>`;

            // Progress columns WITH PROGRESS BARS
            html += renderProgressCell(row.progress_1);
            html += renderProgressCell(row.progress_2, false, row.progress_1);
            html += renderProgressCell(row.result_1);
            html += renderProgressCell(row.result_2, false, row.result_1);

            const change = row.change_avg;
            const changeClass = change > 0 ? 'positive' : (change < 0 ? 'negative' : 'neutral');
            const changeIcon = change > 0 ? 'fa-arrow-up' : (change < 0 ? 'fa-arrow-down' : 'fa-minus');
            html += `<td style="text-align: center;">
                <span class="change-indicator ${changeClass}">
                    <i class="fas ${changeIcon}"></i>
                    ${change > 0 ? '+' : ''}${change.toFixed(2)}%
                </span>
            </td>`;

            html += '</tr>';
        });

        return html;
    }

    function renderProductLeaderboard(data) {
        if (!data || data.length === 0) {
            return '<tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--gray-500);">Tidak ada data</td></tr>';
        }

        let html = '';
        data.forEach((row) => {
            const rankClass = row.rank <= 3 ? `rank-row-${row.rank}` : '';
            const badgeClass = row.rank <= 3 ? `rank-badge rank-${row.rank}` : 'rank-badge';

            html += `<tr class="${rankClass}">`;
            html += `<td style="text-align: center;"><span class="${badgeClass}">#${row.rank}</span></td>`;
            html += `<td><strong>${row.product}</strong></td>`;
            html += `<td style="text-align: center;">${row.total_offerings}</td>`;
            html += `<td style="text-align: center;">${row.wins}</td>`;
            html += '</tr>';
        });

        return html;
    }

    function renderLeaderboard(data, currentPage, itemsPerPage, type) {
        if (!data || data.length === 0) {
            const colspan = type === 'am' ? 4 : 5;
            return {
                html: `<tr><td colspan="${colspan}" style="text-align: center; padding: 30px; color: var(--gray-500);">Tidak ada data</td></tr>`,
                pagination: ''
            };
        }

        const totalItems = data.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        const pageData = data.slice(startIndex, endIndex);

        let html = '';
        pageData.forEach((row) => {
            const rankClass = row.rank <= 3 ? `rank-row-${row.rank}` : '';
            const badgeClass = row.rank <= 3 ? `rank-badge rank-${row.rank}` : 'rank-badge';

            html += `<tr class="${rankClass}">`;
            html += `<td style="text-align: center;"><span class="${badgeClass}">#${row.rank}</span></td>`;

            if (type === 'am') {
                html += `<td><strong>${row.am}</strong></td>`;
                html += `<td>${row.witel}</td>`;
            } else {
                html += `<td><strong>${row.am}</strong></td>`;
                html += `<td>${row.customer || '<span class="no-customer-data">DATA CC TIDAK DITEMUKAN</span>'}</td>`;
                html += `<td>${row.product}</td>`;
            }

            const change = row.change_avg;
            const changeClass = change > 0 ? 'positive' : (change < 0 ? 'negative' : 'neutral');
            const changeIcon = change > 0 ? 'fa-arrow-up' : (change < 0 ? 'fa-arrow-down' : 'fa-minus');
            html += `<td style="text-align: center;">
                <span class="change-indicator ${changeClass}">
                    <i class="fas ${changeIcon}"></i>
                    ${change > 0 ? '+' : ''}${change.toFixed(2)}%
                </span>
            </td>`;

            html += '</tr>';
        });

        // Pagination
        let paginationHtml = '';
        if (totalPages > 1) {
            paginationHtml = '<div class="pagination-wrapper">';
            paginationHtml += `<div class="pagination-info">
                Showing <strong>${startIndex + 1}</strong> to <strong>${endIndex}</strong> of <strong>${totalItems}</strong> results
            </div>`;

            paginationHtml += '<div class="pagination-buttons">';

            if (currentPage > 1) {
                paginationHtml += `<button class="pagination-btn" onclick="changeLeaderboardPage(${currentPage - 1}, '${type}')">Previous</button>`;
            } else {
                paginationHtml += `<button class="pagination-btn" disabled>Previous</button>`;
            }

            const pageRange = getPageRange(currentPage, totalPages);
            pageRange.forEach(page => {
                if (page === '...') {
                    paginationHtml += '<span class="pagination-ellipsis">...</span>';
                } else {
                    const activeClass = page === currentPage ? 'active' : '';
                    paginationHtml += `<button class="pagination-btn ${activeClass}" onclick="changeLeaderboardPage(${page}, '${type}')">${page}</button>`;
                }
            });

            if (currentPage < totalPages) {
                paginationHtml += `<button class="pagination-btn" onclick="changeLeaderboardPage(${currentPage + 1}, '${type}')">Next</button>`;
            } else {
                paginationHtml += `<button class="pagination-btn" disabled>Next</button>`;
            }

            paginationHtml += '</div></div>';
        }

        return {
            html: html,
            pagination: paginationHtml
        };
    }

    function getPageRange(currentPage, totalPages) {
        if (totalPages <= 7) {
            return Array.from({length: totalPages}, (_, i) => i + 1);
        }

        if (currentPage <= 3) {
            return [1, 2, 3, 4, 5, '...', totalPages];
        }

        if (currentPage >= totalPages - 2) {
            return [1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
        }

        return [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
    }

    window.changeLeaderboardPage = function(page, type) {
        if (type === 'am') {
            currentAMLeaderboardPage = page;
            const result = renderLeaderboard(amLeaderboardData, page, ITEMS_PER_PAGE, 'am');
            $('#amLeaderboardTableBody').html(result.html);
            $('#amLeaderboardPagination').html(result.pagination);
        } else {
            currentImprovementLeaderboardPage = page;
            const result = renderLeaderboard(improvementLeaderboardData, page, ITEMS_PER_PAGE, 'improvement');
            $('#improvementLeaderboardTableBody').html(result.html);
            $('#improvementLeaderboardPagination').html(result.pagination);
        }
    };

    // ================================
    // TAB SWITCHING
    // ================================

    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');

        $('.tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.tab-content').removeClass('active');
        if (tab === 'am-level') {
            $('#amLevelContent').addClass('active');
        } else if (tab === 'product-level') {
            $('#productLevelContent').addClass('active');
        }
    });

    // ================================
    // AM LEVEL NESTED TABS SWITCHING
    // ================================

    $('.am-tab-btn').on('click', function() {
        const amTab = $(this).data('am-tab');

        $('.am-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.am-tab-content').removeClass('active');
        if (amTab === 'benchmarking') {
            $('#amBenchmarkingTab').addClass('active');
        } else if (amTab === 'leaderboard') {
            $('#amLeaderboardTab').addClass('active');
        }
    });

    // ================================
    // PRODUCT LEVEL NESTED TABS SWITCHING (delegated)
    // ================================

    $(document).on('click', '.product-tab-btn', function(e) {
        e.preventDefault();
        const tab = $(this).data('product-tab');

        $('.product-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.product-tab-content').removeClass('active');
        if (tab === 'benchmarking') {
            $('#productBenchmarkingTab').addClass('active');
        } else if (tab === 'improvement') {
            $('#productImprovementTab').addClass('active');
        } else if (tab === 'product') {
            $('#productLeaderboardTab').addClass('active');
        }

        return false;
    });

    // ================================
    // DOWNLOAD REPORT WITH WITEL SELECTION
    // ================================

    let currentBenchmarkingData = null; // Store benchmarking data for Witel options

    $('#downloadReportAM, #downloadReportProduct').on('click', function() {
        if (!selectedSnapshot1 || !selectedSnapshot2) {
            showAlert('warning', 'Peringatan', 'Pilih 2 data progres terlebih dahulu');
            return;
        }

        // Populate Witel options from current benchmarking data
        const witelSelect = $('#reportWitelSelect');
        witelSelect.html('<option value="all">📊 Laporan Overall (Semua Witel)</option>');
        
        if (currentBenchmarkingData && currentBenchmarkingData.length > 0) {
            const witels = [...new Set(currentBenchmarkingData.map(row => row.witel))].sort();
            witels.forEach(witel => {
                witelSelect.append(`<option value="${witel}">📍 ${witel}</option>`);
            });
        }

        // Open modal
        $('#reportWitelModal').fadeIn(300);
    });

    window.closeReportWitelModal = function() {
        $('#reportWitelModal').fadeOut(300);
    };

    window.downloadReportWithWitel = function() {
        const selectedWitel = $('#reportWitelSelect').val();
        let url = "{{ route('high-five.report.download') }}?snapshot_1_id=" + selectedSnapshot1 + "&snapshot_2_id=" + selectedSnapshot2;
        
        if (selectedWitel && selectedWitel !== 'all') {
            url += "&witel=" + encodeURIComponent(selectedWitel);
        }
        
        window.location.href = url;
        closeReportWitelModal();
    };

    // ================================
    // MODAL MANAGEMENT FOR SNAPSHOTS
    // ================================

    window.openLinkModal = function() {
        $('#linkModal').fadeIn(300);
        loadExistingLinks();
        
        // Add ESC key handler
        $(document).on('keydown.modal', function(e) {
            if (e.key === 'Escape') {
                closeLinkModal();
            }
        });
    };

    window.closeLinkModal = function() {
        $('#linkModal').fadeOut(300);
        // Remove ESC key handler
        $(document).off('keydown.modal');
    };

    function loadExistingLinks() {
        $.get('/high-five/available-links', function(response) {
            if (response.success) {
                const links = response.data;
                let html = '';

                if (links.length === 0) {
                    html = '<p style="text-align: center; color: var(--gray-500); padding: 20px;">Belum ada link tersimpan</p>';
                } else {
                    links.forEach(link => {
                        const linkUrl = link.link || 'URL tidak tersedia';
                        const displayUrl = linkUrl.length > 50 ? linkUrl.substring(0, 50) + '...' : linkUrl;

                        html += `
                            <div class="link-item-wrapper" id="link-wrapper-${link.id}" style="margin-bottom: 16px; background: var(--white); border: 2px solid var(--gray-200); border-radius: 12px; overflow: hidden;">
                                <div class="link-item" style="padding: 18px 22px; display: flex; justify-content: space-between; align-items: center;">
                                    <div class="link-info" style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                                        <span class="link-divisi" style="font-size: 15px; font-weight: 700; color: var(--telkom-red);">${link.divisi_name || 'Unknown'}</span>
                                        <span class="link-url" style="font-size: 12px; color: var(--gray-600); font-family: monospace;">${displayUrl}</span>
                                        <span class="link-meta" style="font-size: 11px; color: var(--gray-500);">${link.total_snapshots || 0} snapshots | Last: ${link.last_fetched || 'Never'}</span>
                                    </div>
                                    <div class="link-actions" style="display: flex; gap: 10px; align-items: center;">
                                        <button class="btn-action-icon btn-edit-link" onclick="editLink(${link.id}, '${linkUrl.replace(/'/g, "\\'")}')" title="Edit Link">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action-icon btn-toggle-link" onclick="toggleSnapshots(${link.id})" title="Lihat Snapshots">
                                            <i class="fas fa-chevron-down" id="toggle-icon-${link.id}"></i>
                                        </button>
                                        <button class="btn-action-icon btn-delete-link" onclick="deleteLink(${link.id})" title="Hapus Link">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="snapshots-container" id="snapshots-${link.id}" style="display: none; padding: 0 22px 18px 22px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-top: 2px solid var(--gray-200);">
                                    <div class="snapshots-loading" style="text-align: center; padding: 24px; color: var(--gray-500); font-size: 13px;">
                                        <i class="fas fa-spinner fa-spin" style="margin-right: 8px; color: var(--telkom-red);"></i> Loading snapshots...
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                $('#existingLinksContainer').html(html);
            }
        }).fail(function(xhr) {
            $('#existingLinksContainer').html('<p style="text-align: center; color: var(--error); padding: 20px;">Gagal memuat data link</p>');
        });
    }

    window.toggleSnapshots = function(linkId) {
        const container = $(`#snapshots-${linkId}`);
        const icon = $(`#toggle-icon-${linkId}`);
        
        if (container.is(':visible')) {
            container.slideUp(300);
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            if (container.find('.snapshots-loading').length > 0) {
                loadSnapshotsForLink(linkId);
            }
            container.slideDown(300);
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    };

    function loadSnapshotsForLink(linkId) {
        $.get(`/high-five/settings/snapshots/${linkId}`, function(response) {
            if (response.success) {
                const snapshots = response.data;
                let html = '';

                if (snapshots.length === 0) {
                    html = '<p style="text-align: center; color: var(--gray-500); padding: 12px;">Belum ada snapshot</p>';
                } else {
                    html = '<div class="snapshots-list" style="display: flex; flex-direction: column; gap: 12px;">';
                    snapshots.forEach(snapshot => {
                        const statusColor = snapshot.status_color === 'green' ? 'var(--success)' : 'var(--error)';
                        html += `
                            <div class="snapshot-item" style="background: var(--white); border: 2px solid var(--gray-200); border-radius: 8px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
                                <div class="snapshot-info" style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas ${snapshot.status_icon}" style="color: ${statusColor};"></i>
                                        <span class="snapshot-date" style="font-size: 14px; font-weight: 700; color: var(--gray-800);">${snapshot.snapshot_date_formatted}</span>
                                    </div>
                                    <div class="snapshot-stats" style="display: flex; gap: 10px; font-size: 11px; color: var(--gray-600); flex-wrap: wrap;">
                                        <span style="padding: 4px 10px; background: var(--gray-100); border-radius: 6px; font-weight: 600;">${snapshot.total_rows} rows</span>
                                        <span style="padding: 4px 10px; background: var(--gray-100); border-radius: 6px; font-weight: 600;">${snapshot.total_ams} AMs</span>
                                        <span style="padding: 4px 10px; background: var(--gray-100); border-radius: 6px; font-weight: 600;">${snapshot.total_customers} customers</span>
                                        <span style="padding: 4px 10px; background: var(--gray-100); border-radius: 6px; font-weight: 600;">${snapshot.total_products} products</span>
                                    </div>
                                    <span class="snapshot-fetched" style="font-size: 10px; color: var(--gray-500); font-style: italic;">Fetched: ${snapshot.fetched_at}</span>
                                </div>
                                <div class="snapshot-actions" style="display: flex; gap: 8px;">
                                    <button class="btn-snapshot-action btn-edit" onclick="editSnapshotDate(${snapshot.id}, '${snapshot.snapshot_date}', ${linkId})" title="Edit Tanggal">
                                        <i class="fas fa-calendar-alt"></i>
                                    </button>
                                    <button class="btn-snapshot-action btn-delete" onclick="deleteSnapshot(${snapshot.id}, ${linkId})" title="Hapus Snapshot">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                $(`#snapshots-${linkId}`).html(html);
            }
        }).fail(function(xhr) {
            $(`#snapshots-${linkId}`).html('<p style="text-align: center; color: var(--error); padding: 12px;">Gagal memuat snapshots</p>');
        });
    }

    window.editSnapshotDate = function(snapshotId, currentDate, linkId) {
        const newDate = prompt('Edit Tanggal Snapshot (YYYY-MM-DD):', currentDate);
        if (newDate && newDate !== currentDate) {
            $.ajax({
                url: `/high-five/settings/snapshot/${snapshotId}/update-date`,
                method: 'POST',
                data: {
                    snapshot_date: newDate,
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT'
                },
                success: function(response) {
                    showAlert('success', 'Berhasil!', response.message);
                    loadSnapshotsForLink(linkId);
                    loadAvailableLinks();
                },
                error: function(xhr) {
                    showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal update tanggal');
                }
            });
        }
    };

    window.deleteSnapshot = function(snapshotId, linkId) {
        if (confirm('Hapus snapshot ini?')) {
            $.ajax({
                url: `/high-five/settings/snapshot/${snapshotId}`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    showAlert('success', 'Berhasil!', response.message);
                    loadSnapshotsForLink(linkId);
                    loadAvailableLinks();
                },
                error: function(xhr) {
                    showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal menghapus snapshot');
                }
            });
        }
    };

    window.editLink = function(linkId, currentUrl) {
        const newUrl = prompt('Edit Link Spreadsheet:', currentUrl);
        if (newUrl && newUrl !== currentUrl) {
            $.ajax({
                url: `/high-five/settings/update/${linkId}`,
                method: 'POST',
                data: {
                    link_spreadsheet: newUrl,
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT'
                },
                success: function(response) {
                    showAlert('success', 'Berhasil!', response.message);
                    loadExistingLinks();
                    loadAvailableLinks();
                },
                error: function(xhr) {
                    showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal update link');
                }
            });
        }
    };

    window.deleteLink = function(linkId) {
        if (confirm('Hapus link ini? Snapshots akan tetap tersimpan.')) {
            $.ajax({
                url: `/high-five/settings/delete/${linkId}`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    showAlert('success', 'Berhasil!', response.message);
                    loadExistingLinks();
                    loadAvailableLinks();
                },
                error: function(xhr) {
                    showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal menghapus link');
                }
            });
        }
    };

    // ================================
    // AUTO FETCH SETTINGS LOGIC
    // ================================

    // Load settings on init
    loadAutoFetchSettings();

    function loadAutoFetchSettings() {
        $.get("{{ route('high-five.settings.auto-fetch.get') }}", function(response) {
            if (response.success) {
                const data = response.data;
                
                // Set values
                $('#autoFetchDay').val(data.day);
                $('#autoFetchTime').val(data.time);
                if (data.time) {
                    const parts = data.time.split(':');
                    if (parts.length === 2) {
                        $('#autoFetchHour').val(parts[0]);
                        $('#autoFetchMinute').val(parts[1]);
                    }
                }
                // $('#autoFetchTime').val(data.time).prop('disabled', !data.is_active); // OLD
                $('#nextRunText').text(data.next_run + ' (' + data.next_run_diff + ')');

                // Update UI state
                updateAutoFetchUI(data.is_active);
            }
        });
    }

    // Toggle Change Event
    $('#autoFetchToggle').on('change', function() {
        const isChecked = $(this).is(':checked');
        updateAutoFetchUI(isChecked);
        
        // Trigger Save immediately
        saveAutoFetchSettings();
    });

    function updateAutoFetchUI(isActive) {
        const inputs = $('#autoFetchDay, #autoFetchHour, #autoFetchMinute, #btnSaveAutoFetch');
        
        if (isActive) {
            inputs.prop('disabled', false);
            $('#autoFetchControls').css('opacity', '1');
        } else {
            inputs.prop('disabled', true);
            // Optionally dim the inputs container, but NOT the whole box to keep toggle active
            // Or just rely on the disabled state of inputs which usually grays them out
        }
    }

    // ================================
    // SCHEDULER SIMULATION (CLIENT-SIDE)
    // ================================
    // Run check every 30 seconds to simulate cron job if dashboard is open
    setInterval(function() {
        if ($('#autoFetchToggle').is(':checked')) {
            $.get("{{ route('high-five.settings.auto-fetch.check') }}", function(response) {
                // Only run if we actually attempted a fetch (response.results exists)
                if (response.success && response.results) {
                    console.log('Auto Fetch Executed:', response);
                    
                    const count = response.results.length;
                    if (count > 0) {
                        const successes = response.results.filter(r => r.status === 'success').length;
                        const failures = response.results.filter(r => r.status === 'failed').length;
                        
                        let msg = `Memproses ${count} link. Berhasil: ${successes}, Gagal: ${failures}.`;
                        if (failures > 0) {
                            msg += ' Cek log untuk detail.';
                        }
                        
                        showAlert(failures > 0 ? 'warning' : 'success', 'Auto Fetch Selesai', msg);
                        
                        // Reload data
                        loadAutoFetchSettings();
                        
                        // If current view might be affected, reload it
                        // e.g. currently viewing a link that was just updated
                        const currentDivisi = $('#filterDivisi').val();
                        if (currentDivisi) {
                            loadBranchSnapshotOptions(currentDivisi); // Reload dropdowns
                        }
                    } else {
                        // console.log('Auto fetch triggered but no active links found.');
                    }
                }
            });
        }
    }, 30000); // 30 seconds

    window.saveAutoFetchSettings = function() {
        const day = $('#autoFetchDay').val();
        // Gabungkan Hour & Minute
        const hour = $('#autoFetchHour').val().padStart(2, '0');
        const minute = $('#autoFetchMinute').val().padStart(2, '0');
        const time = `${hour}:${minute}`;
        const isActive = $('#autoFetchToggle').is(':checked');
        
        // DEBUG REMOVED
        
        $.ajax({
            url: "{{ route('high-five.settings.auto-fetch.save') }}",
            method: 'POST',
            data: {
                day: day,
                time: time,
                is_active: isActive ? 'true' : 'false', // Send explicit string
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                $('#btnSaveAutoFetch').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> menyimpan...');
            },
            success: function(response) {
                showAlert('success', 'Berhasil!', response.message);
                
                // Update Next Run text from response data
                if (response.data) {
                    $('#nextRunText').text(response.data.next_run + ' (' + response.data.next_run_diff + ')');
                }
                
                $('#btnSaveAutoFetch').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal menyimpan pengaturan';
                showAlert('error', 'Error!', message);
                $('#btnSaveAutoFetch').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    };

// START: TIME INPUT LOGIC
    const hourInput = $('#autoFetchHour');
    const minuteInput = $('#autoFetchMinute');

    // Auto-focus logic
    hourInput.on('input', function() {
        // Hapus karakter non-angka
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Auto pindah kalau sudah 2 digit
        if (this.value.length === 2) {
            minuteInput.focus();
        }
    });

    minuteInput.on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Validation logic (on blur)
    hourInput.on('blur', function() {
        let val = parseInt(this.value);
        if (isNaN(val)) val = 0;
        
        // Limit 0-23
        if (val > 23) val = 23;
        if (val < 0) val = 0;
        
        // Pad 0 if needed (e.g. 1 -> 01)
        this.value = val.toString().padStart(2, '0');
    });

    minuteInput.on('blur', function() {
        let val = parseInt(this.value);
        if (isNaN(val)) val = 0;
        
        // Limit 0-59
        if (val > 59) val = 59;
        if (val < 0) val = 0;
        
        this.value = val.toString().padStart(2, '0');
    });
    // END: TIME INPUT LOGIC

});
</script>

<style>
    /* CSS untuk Link Action Buttons (Top) */
    .btn-action-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Slight shadow */
    }

    .btn-edit-link {
        background: #f0f9ff;
        color: #0284c7; /* Sky blue */
        border: 1px solid #bae6fd;
    }
    .btn-edit-link:hover {
        background: #0284c7;
        color: #fff;
        border-color: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(2, 132, 199, 0.2);
    }

    .btn-toggle-link {
        background: #f8fafc;
        color: #475569; /* Slate gray */
        border: 1px solid #cbd5e1;
    }
    .btn-toggle-link:hover {
        background: #475569;
        color: #fff;
        border-color: #475569;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(71, 85, 105, 0.2);
    }

    .btn-delete-link {
        background: #fef2f2;
        color: #ef4444; /* Red */
        border: 1px solid #fecaca;
    }
    .btn-delete-link:hover {
        background: #ef4444;
        color: #fff;
        border-color: #ef4444;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
    }


    /* CSS untuk Snapshot Buttons */
    .btn-snapshot-action {
        width: 36px; 
        height: 36px; 
        border-radius: 8px; 
        border: none;
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .btn-edit {
        background: #e0f2fe;
        color: #0284c7;
    }
    .btn-edit:hover {
        background: #0284c7;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(2, 132, 199, 0.2);
    }

    .btn-delete {
        background: #fee2e2;
        color: #ef4444;
    }
    .btn-delete:hover {
        background: #ef4444;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
    }

    /* Time Input Styles */
    .time-input:focus {
        border-color: var(--telkom-red) !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(237, 28, 36, 0.1);
    }
</style>
</div><!-- End .highfive-main-content -->
@endsection