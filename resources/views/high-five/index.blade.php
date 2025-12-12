@extends('layouts.main')

@section('title', 'High Five RLEGS TR3')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/css/bootstrap-select.min.css">
<link rel="stylesheet" href="{{ asset('css/highfive.css') }}">
<link rel="stylesheet" href="{{ asset('css/highfive-product-tabs.css') }}">
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
        <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); padding: 14px 18px; border-radius: var(--radius-lg); border: 1px solid #fcd34d; margin-bottom: 16px;">
            <div style="display: flex; align-items: start; gap: 10px;">
                <i class="fas fa-info-circle" style="color: #f59e0b; font-size: 1.1rem; margin-top: 2px;"></i>
                <div style="flex: 1;">
                    <strong style="font-size: 13px; color: #92400e; display: block; margin-bottom: 4px;">💡 Info Update Data</strong>
                    <p style="font-size: 12px; color: #92400e; line-height: 1.5; margin: 0;">
                        Gunakan fitur ini untuk update data manual. Data otomatis terupdate setiap Jumat 01:00 pagi.
                    </p>
                </div>
            </div>
        </div>
        <div class="toolkit-grid" style="grid-template-columns: 200px 160px 1fr 150px;">
            <div class="field-group">
                <label><i class="fas fa-link"></i> Link Spreadsheet</label>
                <select id="manualLinkSelect" class="native-select"><option value="">Pilih Link</option></select>
            </div>
            <div class="field-group">
                <label><i class="fas fa-calendar"></i> Tanggal Data</label>
                <input type="text" id="manualSnapshotDate" class="native-select" placeholder="Pilih tanggal" readonly>
            </div>
            <div class="field-group">
                <label style="color: var(--gray-500);"><i class="fas fa-info-circle"></i> Info Link</label>
                <div style="height: var(--ctrl-h); padding: 0 14px; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); display: flex; align-items: center; background: var(--gray-50);">
                    <span id="manualLinkInfo" style="font-size: 12px; color: var(--gray-500); font-weight: 500;">Pilih link untuk melihat info</span>
                </div>
            </div>
            <div class="field-group">
                <label style="opacity: 0;">.</label>
                <button id="btnSaveManual" class="btn-save-dataset" onclick="saveManualData()">
                    <i class="fas fa-save"></i> Simpan Data
                </button>
            </div>
        </div>
    </div>
</div>

<div class="performance-container">
    <div class="performance-header-wrapper">
        <div class="toolkit-header">
            <h4><i class="fas fa-chart-line"></i> Overview Data Performa High Five</h4>
            <button type="button" id="downloadReportAM" class="btn-download-report" disabled>
                <i class="fas fa-file-pdf"></i> Unduh Laporan PDF
            </button>
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
                <div class="cards-section">
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Performance Highlights</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">Overview & Key Metrics</p>
                    </div>

                    <div class="cards-grid-3-cols">
                        
                        <div class="metric-sq-card card-tall theme-success" id="cardNational">
                            <button class="btn-sq-insight" onclick="showMetricInsight('national')"><i class="fas fa-info"></i></button>
                            <div class="sq-icon"><i class="fas fa-globe-asia"></i></div>
                            <div class="sq-label" style="font-size: 14px;">National Avg Progress</div>
                            <div class="sq-stat" id="metricNatValue" style="margin-bottom: 0;">-</div>
                            <div class="sq-sub" id="metricNatTrend" style="margin-bottom: 8px;">-</div>

                            <div style="border-top: 1px dashed #e2e8f0; width: 100%; padding-top: 8px; display: flex; flex-direction: column; gap: 12px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; background: #f8fafc; padding: 8px; border-radius: 6px;">
                                    <div style="text-align: left;">
                                        <span style="color: #64748b; display: block; font-size: 10px;">TOTAL OFFERINGS</span>
                                        <span id="valOfferings" style="font-weight: 700; color: #1e293b;">-</span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="color: #64748b; display: block; font-size: 10px;">TOTAL VISITED</span>
                                        <span id="valVisited" style="font-weight: 700; color: #1e293b;">-</span>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 12px; background: #f8fafc; padding: 8px; border-radius: 6px;">
                                    <div style="text-align: left;">
                                        <span style="color: #059669; display: block; font-size: 10px; font-weight: 700;">TOTAL WINS</span>
                                        <span id="valWins" style="font-weight: 700; color: #059669;">-</span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="color: #dc2626; display: block; font-size: 10px; font-weight: 700;">TOTAL LOSES</span>
                                        <span id="valLoses" style="font-weight: 700; color: #dc2626;">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="metric-sq-card theme-primary" id="cardMostWitel">
                            <button class="btn-sq-insight" onclick="showMetricInsight('most_witel')"><i class="fas fa-info"></i></button>
                            <div class="sq-icon"><i class="fas fa-crown"></i></div>
                            <div class="sq-label">Witel Champion</div>
                            <div class="sq-value" id="metricMostName">-</div>
                            <div class="sq-sub" id="metricMostStat">-</div>
                        </div>

                        <div class="metric-sq-card theme-warning" id="cardLeastWitel">
                            <button class="btn-sq-insight" onclick="showMetricInsight('least_witel')"><i class="fas fa-info"></i></button>
                            <div class="sq-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="sq-label">Focus Area</div>
                            <div class="sq-value" id="metricLeastName">-</div>
                            <div class="sq-sub" id="metricLeastStat">-</div>
                        </div>

                        <div class="metric-sq-card theme-purple" id="cardTopAM">
                            <button class="btn-sq-insight" onclick="showMetricInsight('top_am')"><i class="fas fa-info"></i></button>
                            <div class="sq-icon"><i class="fas fa-user-astronaut"></i></div>
                            <div class="sq-label">MVP Improver</div>
                            <div class="sq-value" id="metricTopAMName">-</div>
                            <div class="sq-sub" id="metricTopAMStat">-</div>
                        </div>

                        <div class="metric-sq-card theme-success" id="cardAmWin">
                            <button class="btn-sq-insight" onclick="showMetricInsight('am_most_win')"><i class="fas fa-info"></i></button>
                            <div class="sq-icon"><i class="fas fa-trophy"></i></div>
                            <div class="sq-label">Top Sales AM</div>
                            <div class="sq-value" id="metricAmWinName">-</div>
                            <div class="sq-sub" id="metricAmWinStat">-</div>
                        </div>

                    </div>
                </div>

                <div class="am-tabs-container">
                    <div class="am-tabs-navigation">
                        <button class="am-tab-btn active" data-am-tab="benchmarking"><i class="fas fa-table"></i> Benchmarking</button>
                        <button class="am-tab-btn" data-am-tab="leaderboard"><i class="fas fa-medal"></i> Leaderboard</button>
                    </div>

                    <div class="am-tab-content active" id="amBenchmarkingTab">
                        <div class="am-filter-container">
                            <div class="am-search-group">
                                <i class="fas fa-search"></i>
                                <input type="text" id="amSearchInput" placeholder="Cari Account Manager..." autocomplete="off">
                            </div>
                            <div class="am-filter-group">
                                <select id="amStatusFilter" class="native-select">
                                    <option value="all">Semua Status</option>
                                    <option value="result_gt_50">Result > 50%</option>
                                    <option value="result_lt_50">Result < 50%</option>
                                    <option value="progress_0">Progress 0%</option>
                                    <option value="has_win">Has Win</option>
                                    <option value="has_lose">Has Lose</option>
                                </select>
                            </div>
                            <div class="am-sort-group">
                                <span class="sort-label">Sort by:</span>
                                <button class="btn-sort active" data-sort="improvement"><i class="fas fa-chart-line"></i> Improve</button>
                                <button class="btn-sort" data-sort="win"><i class="fas fa-trophy"></i> Wins</button>
                                <button class="btn-sort" data-sort="offerings"><i class="fas fa-briefcase"></i> Offers</button>
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
                <div class="stats-grid">
                    <div class="stat-card visited">
                        <i class="fas fa-check-circle"></i>
                        <h4>CC Visited</h4>
                        <div class="stat-value" id="visitedValue">-</div>
                        <span class="stat-label" id="visitedPercentage">-</span>
                    </div>
                    <div class="stat-card no-progress">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>AM No Progress</h4>
                        <div class="stat-value" id="noProgressValue">-</div>
                        <span class="stat-label">Account Managers</span>
                    </div>
                    <div class="stat-card products">
                        <i class="fas fa-box-open"></i>
                        <h4>Total Products</h4>
                        <div class="stat-value" id="totalProductsValue">-</div>
                        <span class="stat-label">Produk ditawarkan</span>
                    </div>
                </div>

                <div class="narrative-section" style="margin-bottom: 20px;">
                    <div class="narrative-header" onclick="toggleProductNarrative()">
                        <h4><i class="fas fa-chart-pie"></i> Analisis Product Level</h4>
                        <i class="fas fa-chevron-down narrative-toggle" id="productNarrativeToggle"></i>
                    </div>
                    <div class="narrative-body" id="productNarrativeBody">
                        <div class="narrative-paragraph">
                            <h5>Statistik Kunjungan</h5>
                            <p id="productNarrativeVisited">-</p>
                        </div>
                        <div class="narrative-paragraph conclusion">
                            <h5>Progress Analysis</h5>
                            <p id="productNarrativeProgress">-</p>
                        </div>
                    </div>
                </div>

                <div class="product-filters">
                    <label><i class="fas fa-filter"></i> Filter Witel:</label>
                    <select id="witelFilter" class="native-select">
                        <option value="">Semua Witel</option>
                    </select>
                </div>

                <div class="product-tabs-container">
                    <div class="product-tabs-navigation">
                        <button class="product-tab-btn active" data-product-tab="benchmarking">
                            <i class="fas fa-table"></i> Benchmarking Performa Per Produk
                        </button>
                        <button class="product-tab-btn" data-product-tab="improvement">
                            <i class="fas fa-medal"></i> Leaderboard Improvement (Top 10)
                        </button>
                        <button class="product-tab-btn" data-product-tab="product">
                            <i class="fas fa-star"></i> Leaderboard Produk (Top 10)
                        </button>
                    </div>

                    <div class="product-tab-content active" id="productBenchmarkingTab">
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
                                            <th>Avg Progress</th>
                                            <th>Avg Result</th>
                                            <th>Total Offerings</th>
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

                <div class="report-actions">
                    <button type="button" id="downloadReportProduct" class="btn-download-report" disabled>
                        <i class="fas fa-file-pdf"></i> Unduh Laporan PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Kelola Link Spreadsheet (NEW) -->
<div id="linkModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-cog"></i> Kelola Link Spreadsheet</h3>
            <button class="modal-close" onclick="closeLinkModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {
    // ================================
    // INITIALIZATION
    // ================================

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

    // ==========================================
    // RENDER AM PERFORMANCE (FIXED & OPTIMIZED)
    // ==========================================
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

        // 1. National Pulse (TALL CARD)
        if(m.national) {
            $('#metricNatValue').text(m.national.value);
            $('#metricNatTrend').html(m.national.trend_text);
            
            // Update warna kartu
            $('#cardNational').removeClass('theme-success theme-danger')
                .addClass(m.national.trend >= 0 ? 'theme-success' : 'theme-danger');
            
            // UPDATE: Isi data statistik detail baru
            $('#valOfferings').text(m.national.offerings);
            $('#valVisited').text(m.national.visited);
            $('#valWins').text(m.national.wins);
            $('#valLoses').text(m.national.loses);
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

        // ... (Simpan Insight & Render Table sama) ...
        globalInsightsData = data.witel_analysis.insights_data; 
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
        $('#dataset1DateProduct, #dataset1ResultProduct').text(data.snapshot_1.tanggal_formatted);
        $('#dataset2DateProduct, #dataset2ResultProduct').text(data.snapshot_2.tanggal_formatted);

        // Update statistics (COMPACT)
        const stats = data.statistics;
        const visitedCount = stats.visited_customers;
        const totalCount = stats.total_customers;
        $('#visitedValue').text(`${visitedCount}/${totalCount}`);
        $('#visitedPercentage').text(`${stats.visited_percentage.toFixed(1)}% dari total`);
        $('#noProgressValue').text(stats.am_no_progress);
        $('#totalProductsValue').text(stats.total_products);

        // Generate narrative WITH BOLD
        const visitedText = `Dari ${totalCount} Corporate Customer, sebanyak <strong>${visitedCount} CC</strong> (<strong>${stats.visited_percentage.toFixed(1)}%</strong>) telah divisit dan dipropose produk High Five.`;
        const progressText = `Terdapat <strong>${stats.am_no_progress} Account Manager</strong> yang belum menunjukkan progress sama sekali. Total <strong>${stats.total_products} produk</strong> ditawarkan kepada customer.`;
        $('#productNarrativeVisited').html(visitedText);
        $('#productNarrativeProgress').html(progressText);

        // Store all product data
        allProductData = data.products;

        // Populate Witel filter
        populateWitelFilter(data.products);

        // Render table WITH PROGRESS BARS
        const tableHTML = renderProductTable(data.products);
        $('#productBenchmarkTableBody').html(tableHTML);

        // Render leaderboards
        const productLeaderboard = data.product_leaderboard.top_10;
        const productLeaderboardHTML = renderProductLeaderboard(productLeaderboard);
        $('#productLeaderboardTableBody').html(productLeaderboardHTML);

        improvementLeaderboardData = data.improvement_leaderboard;
        currentImprovementLeaderboardPage = 1;
        const improvementResult = renderLeaderboard(improvementLeaderboardData, 1, ITEMS_PER_PAGE, 'improvement');
        $('#improvementLeaderboardTableBody').html(improvementResult.html);
        $('#improvementLeaderboardPagination').html(improvementResult.pagination);
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
        const selectedWitel = $(this).val();
        const filteredData = selectedWitel ? allProductData.filter(p => p.witel === selectedWitel) : allProductData;
        const tableHTML = renderProductTable(filteredData);
        $('#productBenchmarkTableBody').html(tableHTML);
    });

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
            // Filter by Name
            const nameMatch = row.am.toLowerCase().includes(searchValue);
            
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
                case 'offerings': valA = a.stats?.offerings || 0; valB = b.stats?.offerings || 0; break;
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
            html += renderProgressCell(avgProgress2, true);
            html += renderProgressCell(avgResult1, true);
            html += renderProgressCell(avgResult2, true);

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

                // Blok 1: Offerings (Selalu ada)
                badges.push(`<span style="${badgeStyle} ${grayBadge}" title="${s.offerings} Offerings">${s.offerings} Offerings</span>`);
                
                // Blok 2: CC (Selalu ada)
                badges.push(`<span style="${badgeStyle} ${grayBadge}" title="${s.total_customers} Corporate Customers">${s.total_customers} CC</span>`);

                // Blok 3 & 4: Win & Lose (Dinamis)
                // Jika Win ada, masukkan.
                if (s.win > 0) {
                    badges.push(`<span style="${badgeStyle} ${winBadge}" title="${s.win} Win">${s.win} Win</span>`);
                }
                
                // Jika Lose ada, masukkan. (Jika Win tidak ada tadi, Lose otomatis masuk ke index ke-3)
                if (s.lose > 0) {
                    badges.push(`<span style="${badgeStyle} ${loseBadge}" title="${s.lose} Lose">${s.lose} Lose</span>`);
                }

                // Padding: Isi sisa slot dengan elemen kosong agar total tetap 4 blok
                while (badges.length < 4) {
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
                html += renderProgressCell(row.progress_2);
                html += renderProgressCell(row.result_1);
                html += renderProgressCell(row.result_2);

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
                ${renderProgressCell(avgProgress2)}
                ${renderProgressCell(avgResult1)}
                ${renderProgressCell(avgResult2)}
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
    function renderProgressCell(value, isSummary = false) {
        const percentage = typeof value === 'number' ? value : 0;
        
        // 1. Setting Style Background Cell (Optional: pakai var gray-50)
        const bgStyle = isSummary ? 'background-color: var(--gray-100);' : '';
        
        // 2. Setting Style Text
        // Font size SAMA (13px), tapi warna teks menyesuaikan bar (Abu gelap vs Merah)
        const valueStyle = isSummary 
            ? 'font-size: 13px; font-weight: 700; color: var(--telkom-red);' 
            : 'font-size: 13px; font-weight: 600; color: var(--telkom-red);';
        
        // 3. SETTING WARNA BAR (Pakai Variable CSS yang ada)
        let barColor;
        
        if (isSummary) {
            // --- WARNA KHUSUS RATA-RATA (Abu-abu) ---
            // Menggunakan var(--gray-600) ke var(--gray-800)
            barColor = 'var(--telkom-red)';
        } else {
            // --- WARNA DEFAULT AM (Merah) ---
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
            html += renderProgressCell(row.progress_2);
            html += renderProgressCell(row.result_1);
            html += renderProgressCell(row.result_2);

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
            return '<tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--gray-500);">Tidak ada data</td></tr>';
        }

        let html = '';
        data.forEach((row) => {
            const rankClass = row.rank <= 3 ? `rank-row-${row.rank}` : '';
            const badgeClass = row.rank <= 3 ? `rank-badge rank-${row.rank}` : 'rank-badge';

            html += `<tr class="${rankClass}">`;
            html += `<td style="text-align: center;"><span class="${badgeClass}">#${row.rank}</span></td>`;
            html += `<td><strong>${row.product}</strong></td>`;
            html += `<td style="text-align: center;">${row.avg_progress.toFixed(2)}%</td>`;
            html += `<td style="text-align: center;">${row.avg_result.toFixed(2)}%</td>`;
            html += `<td style="text-align: center;">${row.total_offerings}</td>`;
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
    // DOWNLOAD REPORT
    // ================================

    $('#downloadReportAM, #downloadReportProduct').on('click', function() {
        if (!selectedSnapshot1 || !selectedSnapshot2) {
            showAlert('warning', 'Peringatan', 'Pilih 2 data progres terlebih dahulu');
            return;
        }

        window.location.href = "{{ route('high-five.report.download') }}?snapshot_1_id=" + selectedSnapshot1 + "&snapshot_2_id=" + selectedSnapshot2;
    });
});
</script>
</div><!-- End .highfive-main-content -->
@endsection