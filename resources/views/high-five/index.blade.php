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
        <h4>
            <i class="fas fa-database"></i>
            Kelola Dataset High Five
        </h4>
        <div style="display: flex; align-items: center; gap: 12px;">
            <button type="button" class="btn-kelola-link" onclick="event.stopPropagation(); openLinkModal()">
                <i class="fas fa-cog"></i>
                Kelola Link Spreadsheet
            </button>
            <i class="fas fa-chevron-down toolkit-toggle" id="manualFetchToggle"></i>
        </div>
    </div>

    <p style="font-size: 12px; color: var(--gray-600); margin: 8px 0 0 0; padding-left: 28px;">
        Rekap link dan update data performa mingguan
    </p>

    <div class="toolkit-body" id="manualFetchBody">
        <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); padding: 14px 18px; border-radius: var(--radius-lg); border: 1px solid #fcd34d; margin-bottom: 16px;">
            <div style="display: flex; align-items: start; gap: 10px;">
                <i class="fas fa-info-circle" style="color: #f59e0b; font-size: 1.1rem; margin-top: 2px;"></i>
                <div style="flex: 1;">
                    <strong style="font-size: 13px; color: #92400e; display: block; margin-bottom: 4px;">💡 Kapan Pakai Fitur Ini?</strong>
                    <p style="font-size: 12px; color: #92400e; line-height: 1.5; margin: 0;">
                        Gunakan fitur ini untuk <strong>update data performa terbaru secara manual</strong>.
                        Pastikan tanggal yang dipilih sesuai jadwal dan belum tercatat di sistem sebelumnya.
                        <br><strong>Catatan:</strong> Data akan otomatis terupdate setiap Jumat jam 01:00 pagi.
                    </p>
                </div>
            </div>
        </div>

        <div class="toolkit-grid" style="grid-template-columns: 200px 160px 1fr 150px;">
            <div class="field-group">
                <label>
                    <i class="fas fa-link"></i>
                    Link Spreadsheet
                </label>
                <select id="manualLinkSelect" class="native-select">
                    <option value="">Pilih Link</option>
                </select>
            </div>

            <div class="field-group">
                <label>
                    <i class="fas fa-calendar"></i>
                    Tanggal Data
                </label>
                <input type="text" id="manualSnapshotDate" class="native-select" placeholder="Pilih tanggal" readonly>
            </div>

            <div class="field-group">
                <label style="color: var(--gray-500);">
                    <i class="fas fa-info-circle"></i>
                    Info Link
                </label>
                <div style="height: var(--ctrl-h); padding: 0 14px; border: 2px solid var(--gray-200); border-radius: var(--radius-lg); display: flex; align-items: center; background: var(--gray-50);">
                    <span id="manualLinkInfo" style="font-size: 12px; color: var(--gray-500); font-weight: 500;">
                        Pilih link untuk melihat info
                    </span>
                </div>
            </div>

            <div class="field-group">
                <label style="opacity: 0;">.</label>
                <button id="btnSaveManual" class="btn-save-dataset" onclick="saveManualData()">
                    <i class="fas fa-save"></i>
                    Simpan Data
                </button>
            </div>
        </div>
    </div>
</div>

<div class="performance-container" style="padding-top: 25px;">
    
    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <div style="width: 4px; height: 24px; background: linear-gradient(to bottom, var(--telkom-red), var(--telkom-red-dark)); border-radius: 4px;"></div>
        <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--gray-800); margin: 0;">
            Visualisasi & Benchmarking Data
        </h4>
    </div>

    <div style="background: var(--gray-50); padding: 20px; border-radius: 12px; border: 1px solid var(--gray-200); margin-bottom: 30px;">
        <div class="selector-grid">
            <div class="field-group">
                <label><i class="fas fa-filter"></i> Filter Divisi</label>
                <select id="filterDivisi" class="selectpicker" title="Pilih Divisi">
                    @foreach($divisiList as $divisi)
                        <option value="{{ $divisi->id }}">{{ $divisi->kode }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field-group">
                <label><i class="fas fa-database"></i> Data Progres 1 (Lama)</label>
                <select id="snapshot1" class="native-select" disabled>
                    <option value="">-- Pilih Data Progres 1 --</option>
                </select>
            </div>

            <div class="field-group">
                <label><i class="fas fa-database"></i> Data Progres 2 (Baru)</label>
                <select id="snapshot2" class="native-select" disabled>
                    <option value="">-- Pilih Data Progres 2 --</option>
                </select>
            </div>

            <div class="field-group">
                <label style="opacity: 0;">Action</label>
                <button type="button" id="loadBenchmarkBtn" class="btn-load-data" disabled>
                    <i class="fas fa-sync-alt"></i> Load Data
                </button>
            </div>
        </div>
    </div>

    <div class="performance-tabs">
        <button class="tab-btn" data-tab="am-level">
            <i class="fas fa-user-tie"></i> Performa AM Level
        </button>
        <button class="tab-btn" data-tab="product-level">
            <i class="fas fa-box"></i> Performa Product Level
        </button>
    </div>

    <div class="tab-content-area">
        <div id="emptyState" class="empty-state active">
            <i class="fas fa-chart-bar"></i>
            <h3>Belum Ada Data untuk Divisualisasikan</h3>
            <p>Silakan pilih Divisi dan Snapshot pada filter di atas, lalu klik "Load Data"</p>
        </div>

        <div id="loadingState" class="loading-state">
            <div class="spinner"></div>
            <p>Memproses data dari database...</p>
        </div>

        <div id="amLevelContent" class="tab-content">
            <div class="cards-section">
                <div class="cards-section-title" onclick="toggleAnalysisCards()" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <i class="fas fa-chart-bar"></i>
                        Analisis Performa by Dataset
                    </div>
                    <i class="fas fa-chevron-down toolkit-toggle active" id="analysisCardsToggle"></i>
                </div>

                <div id="analysisCardsBody" class="cards-grid-wrapper" style="display: block;">
                    <div style="margin-bottom: 16px; text-align: center;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--gray-700);">
                            <i class="fas fa-calendar-alt" style="font-size: 0.85rem; margin-right: 6px;"></i>
                            <span id="dataset1NameCard">Data Progres 1</span>
                            <span style="margin: 0 10px; color: var(--gray-500);">vs</span>
                            <span id="dataset2NameCard">Data Progres 2</span>
                        </span>
                    </div>

                    <div class="cards-grid-horizontal">
                        <div class="analysis-card card-most">
                            <div class="card-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="card-info">
                                <h4>Most Progress</h4>
                                <p class="period-label" style="font-size: 10px; color: var(--gray-500); margin-bottom: 4px;">Periode Lama</p>
                                <p class="witel-name" id="mostProgressWitel1">-</p>
                                <span class="progress-value" id="mostProgressValue1">-</span>
                            </div>
                        </div>

                        <div class="analysis-card card-least">
                            <div class="card-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="card-info">
                                <h4>Least Progress</h4>
                                <p class="period-label" style="font-size: 10px; color: var(--gray-500); margin-bottom: 4px;">Periode Lama</p>
                                <p class="witel-name" id="leastProgressWitel1">-</p>
                                <span class="progress-value" id="leastProgressValue1">-</span>
                            </div>
                        </div>

                        <div class="analysis-card card-most">
                            <div class="card-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="card-info">
                                <h4>Most Progress</h4>
                                <p class="period-label" style="font-size: 10px; color: var(--gray-500); margin-bottom: 4px;">Periode Baru</p>
                                <p class="witel-name" id="mostProgressWitel2">-</p>
                                <span class="progress-value" id="mostProgressValue2">-</span>
                            </div>
                        </div>

                        <div class="analysis-card card-least">
                            <div class="card-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="card-info">
                                <h4>Least Progress</h4>
                                <p class="period-label" style="font-size: 10px; color: var(--gray-500); margin-bottom: 4px;">Periode Baru</p>
                                <p class="witel-name" id="leastProgressWitel2">-</p>
                                <span class="progress-value" id="leastProgressValue2">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="narrative-section">
                <div class="narrative-header" onclick="toggleNarrative()">
                    <h4><i class="fas fa-file-alt"></i> Analisis Performa</h4>
                    <i class="fas fa-chevron-down narrative-toggle" id="narrativeToggle"></i>
                </div>

                <div class="narrative-body" id="narrativeBody">
                    <div class="narrative-paragraph">
                        <h5 id="narrativeDataset1Title">Hasil Analisis Data Progres (Periode Lama)</h5>
                        <p id="narrativeDataset1">-</p>
                    </div>

                    <div class="narrative-paragraph">
                        <h5 id="narrativeDataset2Title">Hasil Analisis Data Progres (Periode Baru)</h5>
                        <p id="narrativeDataset2">-</p>
                    </div>

                    <div class="narrative-paragraph conclusion">
                        <h5>Kesimpulan</h5>
                        <p id="narrativeConclusion">-</p>
                    </div>
                </div>
            </div>

            <div class="am-tabs-container">
                <div class="am-tabs-navigation">
                    <button class="am-tab-btn active" data-am-tab="benchmarking">
                        <i class="fas fa-table"></i> Benchmarking Performa Account Manager
                    </button>
                    <button class="am-tab-btn" data-am-tab="leaderboard">
                        <i class="fas fa-medal"></i> Leaderboard AM (Top Performers)
                    </button>
                </div>

                <div class="am-tab-content active" id="amBenchmarkingTab">
                    <div class="table-container">
                        <div class="table-header">
                            <h4><i class="fas fa-table"></i> Benchmarking Performa Account Manager</h4>
                        </div>
                        
                        <div class="table-scrollable-wrapper">
                            <div class="table-responsive">
                                <table class="benchmark-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 120px; min-width: 120px;">Witel</th>
                                            <th style="width: 350px; min-width: 350px;">Account Manager</th>
                                            <th>Avg % Progress<br><small id="dataset1DateAM">-</small></th>
                                            <th>Avg % Progress<br><small id="dataset2DateAM">-</small></th>
                                            <th>Avg % Result<br><small id="dataset1ResultAM">-</small></th>
                                            <th>Avg % Result<br><small id="dataset2ResultAM">-</small></th>
                                            <th>Perubahan</th>
                                        </tr>
                                    </thead>
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
                        <div class="table-header">
                            <h4><i class="fas fa-medal"></i> Leaderboard AM (Top Performers)</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="benchmark-table leaderboard-table">
                                <thead>
                                    <tr>
                                        <th width="100">Rank</th>
                                        <th style="width: 250px;">Account Manager</th>
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

            <div class="report-actions">
                <button type="button" id="downloadReportAM" class="btn-download-report" disabled>
                    <i class="fas fa-file-pdf"></i> Unduh Laporan PDF
                </button>
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
                        <div class="table-header">
                            <h4><i class="fas fa-table"></i> Benchmarking Performa Per Produk</h4>
                        </div>
                        <div class="table-scrollable-wrapper">
                            <div class="table-responsive">
                                <table class="benchmark-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 200px; min-width: 200px;">AM</th>
                                            <th style="width: 250px; min-width: 250px;">Customer</th>
                                            <th style="width: 200px; min-width: 200px;">Product</th>
                                            <th>% Progress<br><small id="dataset1DateProduct">-</small></th>
                                            <th>% Progress<br><small id="dataset2DateProduct">-</small></th>
                                            <th>% Result<br><small id="dataset1ResultProduct">-</small></th>
                                            <th>% Result<br><small id="dataset2ResultProduct">-</small></th>
                                            <th>Perubahan</th>
                                        </tr>
                                    </thead>
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
                        <div class="table-header">
                            <h4><i class="fas fa-medal"></i> Leaderboard Improvement (Top 10)</h4>
                        </div>
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
                        <div class="table-header">
                            <h4><i class="fas fa-star"></i> Leaderboard Produk (Top 10)</h4>
                        </div>
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

<div id="linkModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-cog"></i> Kelola Link Spreadsheet</h3>
            <button class="modal-close" onclick="closeLinkModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="modal-section">
                <h4><i class="fas fa-link"></i> Link Tersedia</h4>
                <div id="existingLinksContainer">
                    <p style="text-align: center; color: var(--gray-500); padding: 20px;">Loading...</p>
                </div>
            </div>

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

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {
    // ================================
    // INITIALIZATION
    // ================================

    $('#filterDivisi').selectpicker({
        liveSearch: false,
        size: 6,
        dropupAuto: false,
        container: 'body'
    });

    let datePickerInstance = flatpickr("#manualSnapshotDate", {
        dateFormat: "Y-m-d",
        defaultDate: null,
        locale: {
            firstDayOfWeek: 1,
            weekdays: { shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'], longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'], },
            months: { shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], },
        },
    });

    let selectedSnapshot1 = null;
    let selectedSnapshot2 = null;
    let currentAMLeaderboardPage = 1;
    let currentImprovementLeaderboardPage = 1;
    let amLeaderboardData = [];
    let improvementLeaderboardData = [];
    let allProductData = [];
    const ITEMS_PER_PAGE = 10;

    loadAvailableLinks();

    if ($('#emptyState').hasClass('active')) {
        $('.tab-btn').removeClass('active');
    }

    // ================================
    // UI HANDLERS
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

    // ================================
    // ALERT SYSTEM
    // ================================

    function showAlert(type, title, message) {
        const iconMap = { 'success': 'fas fa-check-circle', 'error': 'fas fa-times-circle', 'info': 'fas fa-info-circle', 'warning': 'fas fa-exclamation-triangle' };
        const borderMap = { 'success': 'var(--success)', 'error': 'var(--error)', 'info': '#3b82f6', 'warning': 'var(--warning)' };

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
        setTimeout(() => { alertBox.fadeOut(300, function() { $(this).remove(); }); }, 5000);
        alertBox.find('.alert-close').on('click', function() { alertBox.fadeOut(300, function() { $(this).remove(); }); });
    }

    // ================================
    // MODAL & LINK MANAGEMENT
    // ================================

    window.openLinkModal = function() { $('#linkModal').fadeIn(300); loadExistingLinks(); };
    window.closeLinkModal = function() { $('#linkModal').fadeOut(300); };

    function loadExistingLinks() {
        $.get('/high-five/available-links', function(response) {
            if (response.success) {
                const links = response.data;
                let html = '';
                if (links.length === 0) {
                    html = '<p style="text-align: center; color: var(--gray-500); padding: 20px;">Belum ada link tersimpan</p>';
                } else {
                    links.forEach(link => {
                        const linkUrl = link.link || link.link_url || link.spreadsheet_url || 'URL tidak tersedia';
                        const displayUrl = linkUrl.length > 50 ? linkUrl.substring(0, 50) + '...' : linkUrl;
                        html += `
                            <div class="link-item">
                                <div class="link-info">
                                    <span class="link-divisi">${link.divisi_name}</span>
                                    <span class="link-url">${displayUrl}</span>
                                    <span class="link-meta">${link.total_snapshots} snapshots | Last: ${link.last_fetched}</span>
                                </div>
                                <div class="link-actions">
                                    <button class="btn-link-edit" onclick="editLink(${link.id}, '${linkUrl.replace(/'/g, "\\'")}')"><i class="fas fa-edit"></i></button>
                                    <button class="btn-link-delete" onclick="deleteLink(${link.id})"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>`;
                    });
                }
                $('#existingLinksContainer').html(html);
            }
        });
    }

    $('#addLinkForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '/high-five/settings/store', method: 'POST',
            data: { divisi_id: $('#newLinkDivisi').val(), link_spreadsheet: $('#newLinkUrl').val(), _token: '{{ csrf_token() }}' },
            success: function(response) { showAlert('success', 'Berhasil!', response.message); $('#addLinkForm')[0].reset(); loadExistingLinks(); loadAvailableLinks(); },
            error: function(xhr) { showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal menyimpan link'); }
        });
    });

    window.editLink = function(linkId, currentUrl) {
        const newUrl = prompt('Edit Link Spreadsheet:', currentUrl);
        if (newUrl && newUrl !== currentUrl) {
            $.ajax({
                url: `/high-five/settings/update/${linkId}`, method: 'POST',
                data: { link_spreadsheet: newUrl, _token: '{{ csrf_token() }}', _method: 'PUT' },
                success: function(response) { showAlert('success', 'Berhasil!', response.message); loadExistingLinks(); loadAvailableLinks(); },
                error: function(xhr) { showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal update link'); }
            });
        }
    };

    window.deleteLink = function(linkId) {
        if (confirm('Hapus link ini? Semua snapshot terkait akan ikut terhapus!')) {
            $.ajax({
                url: `/high-five/settings/delete/${linkId}`, method: 'POST',
                data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                success: function(response) { showAlert('success', 'Berhasil!', response.message); loadExistingLinks(); loadAvailableLinks(); },
                error: function(xhr) { showAlert('error', 'Error!', xhr.responseJSON?.message || 'Gagal menghapus link'); }
            });
        }
    };

    // ================================
    // MANUAL FETCH
    // ================================

    function loadAvailableLinks() {
        $.get('/high-five/available-links', function(response) {
            if (response.success) {
                let options = '<option value="">Pilih Link</option>';
                response.data.forEach(link => {
                    options += `<option value="${link.id}" data-divisi="${link.divisi_name}" data-last-fetched="${link.last_fetched}">${link.divisi_name} (${link.total_snapshots} snapshots)</option>`;
                });
                $('#manualLinkSelect').html(options);
            }
        });
    }

    $('#manualLinkSelect').change(function() {
        const opt = $('#manualLinkSelect option:selected');
        const linkId = opt.val();
        if (!linkId) { $('#manualLinkInfo').html('<span style="color: var(--gray-500);">Pilih link untuk melihat info</span>'); return; }
        $('#manualLinkInfo').html(`<span style="font-size: 11px; color: var(--gray-600); font-weight: 500;">${opt.data('divisi')} last update: ${opt.data('last-fetched')}</span>`);
    });

    window.saveManualData = function() {
        const linkId = $('#manualLinkSelect').val();
        const date = $('#manualSnapshotDate').val();
        if (!linkId || !date) { showAlert('error', 'Error', 'Lengkapi form'); return; }
        
        $.ajax({
            url: '/high-five/fetch-manual', method: 'POST',
            data: { link_id: linkId, snapshot_date: date, _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function() { $('#btnSaveManual').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...'); },
            success: function(r) { 
                showAlert('success', 'Berhasil!', r.message); 
                $('#btnSaveManual').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Data');
                if($('#filterDivisi').val()) loadSnapshotOptions($('#filterDivisi').val());
            },
            error: function(x) { 
                showAlert('error', 'Error!', x.responseJSON?.message || 'Gagal'); 
                $('#btnSaveManual').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Data');
            }
        });
    };

    // ================================
    // SNAPSHOT & DATA LOADING
    // ================================

    $('#filterDivisi').on('changed.bs.select', function() {
        const divisiId = $(this).val();
        if (divisiId) {
            loadSnapshotOptions(divisiId);
            $('#snapshot1, #snapshot2').prop('disabled', false);
        } else {
            $('#snapshot1, #snapshot2').empty().prop('disabled', true).append('<option value="">-- Pilih Data Progres --</option>');
            $('#loadBenchmarkBtn').prop('disabled', true);
        }
    });

    function loadSnapshotOptions(divisiId) {
        $.ajax({
            url: "{{ route('high-five.snapshots') }}", method: 'GET', data: { divisi_id: divisiId },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    $('#snapshot1, #snapshot2').empty();
                    $('#snapshot1').append('<option value="">-- Pilih Data Progres 1 --</option>');
                    $('#snapshot2').append('<option value="">-- Pilih Data Progres 2 --</option>');
                    response.data.forEach(s => {
                        $('#snapshot1, #snapshot2').append(`<option value="${s.id}" data-full-label="${s.label}">${s.label}</option>`);
                    });
                    $('#snapshot1, #snapshot2').prop('disabled', false);
                    selectedSnapshot1 = null; selectedSnapshot2 = null;
                    $('#loadBenchmarkBtn').prop('disabled', true);
                } else {
                    $('#snapshot1, #snapshot2').empty().prop('disabled', true).append('<option value="">-- Tidak ada data --</option>');
                }
            }
        });
    }

    $('#snapshot1').on('change', function() { selectedSnapshot1 = $(this).val(); updateSnapshot2Options(); checkCanLoad(); });
    $('#snapshot2').on('change', function() { selectedSnapshot2 = $(this).val(); checkCanLoad(); });

    function updateSnapshot2Options() {
        const val1 = $('#snapshot1').val();
        const val2 = $('#snapshot2').val();
        const opts = [];
        $('#snapshot1 option').each(function() { if($(this).val()) opts.push({v: $(this).val(), t: $(this).text()}); });
        
        $('#snapshot2').empty().append('<option value="">-- Pilih Data Progres 2 --</option>');
        opts.forEach(o => { if(o.v !== val1) $('#snapshot2').append(`<option value="${o.v}">${o.t}</option>`); });
        
        if (val2 && val2 !== val1) $('#snapshot2').val(val2); else { $('#snapshot2').val(''); selectedSnapshot2 = null; }
    }

    function checkCanLoad() {
        $('#loadBenchmarkBtn').prop('disabled', !selectedSnapshot1 || !selectedSnapshot2 || selectedSnapshot1 === selectedSnapshot2);
    }

    $('#loadBenchmarkBtn').on('click', loadBenchmarkingData);

    function loadBenchmarkingData() {
        $('#emptyState').removeClass('active');
        $('#loadingState').addClass('active');
        $('#amLevelContent, #productLevelContent').removeClass('active');
        $('.tab-btn').removeClass('active');

        // AM Request
        $.ajax({
            url: "{{ route('high-five.am-performance') }}", method: 'GET',
            data: { snapshot_1_id: selectedSnapshot1, snapshot_2_id: selectedSnapshot2 },
            success: function(response) { if (response.success) renderAMPerformance(response.data); },
            error: function(xhr) { showAlert('error', 'Error', 'Gagal memuat data AM'); }
        });

        // Product Request
        $.ajax({
            url: "{{ route('high-five.product-performance') }}", method: 'GET',
            data: { snapshot_1_id: selectedSnapshot1, snapshot_2_id: selectedSnapshot2 },
            success: function(response) { 
                if (response.success) {
                    renderProductPerformance(response.data);
                    $('#loadingState').removeClass('active');
                    $('.tab-btn[data-tab="am-level"]').addClass('active');
                    $('#amLevelContent').addClass('active');
                    $('#downloadReportAM, #downloadReportProduct').prop('disabled', false);
                }
            },
            error: function(xhr) { 
                showAlert('error', 'Error', 'Gagal memuat data Product'); 
                $('#loadingState').removeClass('active'); 
                $('#emptyState').addClass('active'); 
            }
        });
    }

    // ================================
    // RENDERING FUNCTIONS
    // ================================

    function renderAMPerformance(data) {
        // Labels
        const d1 = data.snapshot_1.tanggal_formatted;
        const d2 = data.snapshot_2.tanggal_formatted;
        $('#dataset1NameCard').text(`Data Progres ${data.snapshot_1.label}`);
        $('#dataset2NameCard').text(`Data Progres ${data.snapshot_2.label}`);
        $('#dataset1DateAM, #dataset1ResultAM').text(d1);
        $('#dataset2DateAM, #dataset2ResultAM').text(d2);
        
        $('.cards-grid-horizontal .analysis-card').eq(0).find('.period-label').text(`Periode ${d1}`);
        $('.cards-grid-horizontal .analysis-card').eq(1).find('.period-label').text(`Periode ${d1}`);
        $('.cards-grid-horizontal .analysis-card').eq(2).find('.period-label').text(`Periode ${d2}`);
        $('.cards-grid-horizontal .analysis-card').eq(3).find('.period-label').text(`Periode ${d2}`);

        // Cards
        const c1 = data.witel_analysis.cards.dataset_1;
        const c2 = data.witel_analysis.cards.dataset_2;
        $('#mostProgressWitel1').text(c1.most_progress?.witel || '-'); $('#mostProgressValue1').text(c1.most_progress ? c1.most_progress.avg_progress.toFixed(2)+'%' : '0%');
        $('#leastProgressWitel1').text(c1.least_progress?.witel || '-'); $('#leastProgressValue1').text(c1.least_progress ? c1.least_progress.avg_progress.toFixed(2)+'%' : '0%');
        $('#mostProgressWitel2').text(c2.most_progress?.witel || '-'); $('#mostProgressValue2').text(c2.most_progress ? c2.most_progress.avg_progress.toFixed(2)+'%' : '0%');
        $('#leastProgressWitel2').text(c2.least_progress?.witel || '-'); $('#leastProgressValue2').text(c2.least_progress ? c2.least_progress.avg_progress.toFixed(2)+'%' : '0%');

        // Narrative
        const n = data.witel_analysis.narrative;
        $('#narrativeDataset1').html(formatNarrative(n.dataset_1_paragraph));
        $('#narrativeDataset2').html(formatNarrative(n.dataset_2_paragraph));
        $('#narrativeConclusion').html(formatNarrative(n.conclusion_paragraph));

        // Table
        $('#amBenchmarkTableBody').html(renderAMTable(data.benchmarking));

        // Leaderboard
        amLeaderboardData = data.leaderboard;
        currentAMLeaderboardPage = 1;
        updateLeaderboard('am');
    }

    function renderProductPerformance(data) {
        $('#dataset1DateProduct, #dataset1ResultProduct').text(data.snapshot_1.tanggal_formatted);
        $('#dataset2DateProduct, #dataset2ResultProduct').text(data.snapshot_2.tanggal_formatted);

        // Stats
        const s = data.statistics;
        $('#visitedValue').text(`${s.visited_customers}/${s.total_customers}`);
        $('#visitedPercentage').text(`${s.visited_percentage.toFixed(1)}% dari total`);
        $('#noProgressValue').text(s.am_no_progress);
        $('#totalProductsValue').text(s.total_products);

        // Narrative
        $('#productNarrativeVisited').html(`Dari ${s.total_customers} Corporate Customer, sebanyak <strong>${s.visited_customers} CC</strong> (<strong>${s.visited_percentage.toFixed(1)}%</strong>) telah divisit.`);
        $('#productNarrativeProgress').html(`Terdapat <strong>${s.am_no_progress} Account Manager</strong> tanpa progress. Total <strong>${s.total_products} produk</strong> ditawarkan.`);

        // Table
        allProductData = data.products;
        populateWitelFilter(allProductData);
        $('#productBenchmarkTableBody').html(renderProductTable(allProductData));

        // Leaderboards
        $('#productLeaderboardTableBody').html(renderProductLeaderboard(data.product_leaderboard.top_10));
        improvementLeaderboardData = data.improvement_leaderboard;
        currentImprovementLeaderboardPage = 1;
        updateLeaderboard('improvement');
    }

    function formatNarrative(text) {
        return text.replace(/(\d+\.?\d*%)/g, '<strong>$1</strong>')
                   .replace(/Witel ([A-Z\s]+)/g, 'Witel <strong>$1</strong>')
                   .replace(/progress tertinggi/gi, '<strong>progress tertinggi</strong>')
                   .replace(/progress terendah/gi, '<strong>progress terendah</strong>');
    }

    // ================================
    // TABLE RENDERERS (AM with Stats)
    // ================================

    function renderAMTable(data) {
        if (!data || data.length === 0) return '<tr><td colspan="7" style="text-align: center; padding: 30px;">Tidak ada data</td></tr>';

        const witelGroups = {};
        data.forEach(row => { if (!witelGroups[row.witel]) witelGroups[row.witel] = []; witelGroups[row.witel].push(row); });

        let html = '';
        for (const [witelName, rows] of Object.entries(witelGroups)) {
            // Calculate Avg
            let sP1=0, sP2=0, sR1=0, sR2=0, sC=0;
            rows.forEach(r => { sP1+=r.progress_1; sP2+=r.progress_2; sR1+=r.result_1; sR2+=r.result_2; sC+=r.change_avg; });
            const cnt = rows.length;
            const aP1 = sP1/cnt, aP2 = sP2/cnt, aR1 = sR1/cnt, aR2 = sR2/cnt, aC = sC/cnt;

            // Summary Row
            html += '<tr class="witel-summary-row" style="background-color: #f1f5f9; font-weight: 700;">';
            html += `<td rowspan="${cnt + 1}" style="vertical-align: top; padding-top: 16px; background: #fff !important; border-bottom: 2px solid #e2e8f0; color: var(--gray-900); font-weight: 800;">${witelName}</td>`;
            html += `<td style="padding: 12px 14px; color: var(--telkom-red-dark); font-weight: bold; font-style: italic;">RATA-RATA</td>`;
            html += renderProgressCell(aP1) + renderProgressCell(aP2) + renderProgressCell(aR1) + renderProgressCell(aR2);
            html += `<td style="text-align: center;"><span class="change-indicator ${aC>0?'positive':(aC<0?'negative':'neutral')}"><i class="fas fa-${aC>0?'arrow-up':(aC<0?'arrow-down':'minus')}"></i> ${aC>0?'+':''}${aC.toFixed(2)}%</span></td></tr>`;

            // AM Rows
            const containerStyle = 'display: flex; gap: 4px; width: 100%;';
            const badgeStyle = 'flex: 0 0 calc(25% - 3px); display: inline-flex; align-items: center; justify-content: center; padding: 3px 0; border-radius: 4px; font-size: 10px; margin-bottom: 2px; white-space: nowrap; overflow: hidden;';
            const iconStyle = 'font-size: 9px; margin-right: 4px;';
            const grayBadge = 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;';
            const winBadge = 'background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 600;';
            const loseBadge = 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 600;';

            rows.forEach(row => {
                const s = row.stats || { offerings: 0, total_customers: 0, win: 0, lose: 0 };
                html += `<tr><td style="padding: 10px 14px;"><div style="font-weight: 600; color: #1e293b; margin-bottom: 6px;">${row.am}</div>`;
                html += `<div style="${containerStyle}">
                    <span style="${badgeStyle} ${grayBadge}" title="${s.offerings} Offering"><i class="fas fa-box-open" style="${iconStyle}"></i> ${s.offerings} Offering</span>
                    <span style="${badgeStyle} ${grayBadge}" title="${s.total_customers} CC"><i class="fas fa-users" style="${iconStyle}"></i> ${s.total_customers} CC</span>
                    ${s.win>0 ? `<span style="${badgeStyle} ${winBadge}" title="${s.win} Win"><i class="fas fa-check" style="${iconStyle}"></i> ${s.win} Win</span>` : (s.lose>0 ? `<span style="${badgeStyle} ${loseBadge}" title="${s.lose} Lose"><i class="fas fa-times" style="${iconStyle}"></i> ${s.lose} Lose</span>` : '')}
                    ${s.win>0 && s.lose>0 ? `<span style="${badgeStyle} ${loseBadge}" title="${s.lose} Lose"><i class="fas fa-times" style="${iconStyle}"></i> ${s.lose} Lose</span>` : ''}
                </div></td>`;
                html += renderProgressCell(row.progress_1) + renderProgressCell(row.progress_2) + renderProgressCell(row.result_1) + renderProgressCell(row.result_2);
                html += `<td style="text-align: center;"><span class="change-indicator ${row.change_avg>0?'positive':(row.change_avg<0?'negative':'neutral')}"><i class="fas fa-${row.change_avg>0?'arrow-up':(row.change_avg<0?'arrow-down':'minus')}"></i> ${row.change_avg>0?'+':''}${row.change_avg.toFixed(2)}%</span></td></tr>`;
            });
        }
        return html;
    }

    function renderProductTable(data) {
        if (!data || data.length === 0) return '<tr><td colspan="8" style="text-align: center; padding: 30px;">Tidak ada data</td></tr>';
        let html = '';
        data.forEach(row => {
            html += '<tr>';
            if (row.am_rowspan > 0) html += `<td class="am-cell" rowspan="${row.am_rowspan}"><strong>${row.am}</strong></td>`;
            if (row.customer_rowspan > 0) html += `<td class="customer-cell" rowspan="${row.customer_rowspan}">${row.customer || '<span class="no-customer-data">DATA CC TIDAK DITEMUKAN</span>'}</td>`;
            html += `<td>${row.product}</td>` + renderProgressCell(row.progress_1) + renderProgressCell(row.progress_2) + renderProgressCell(row.result_1) + renderProgressCell(row.result_2);
            html += `<td style="text-align: center;"><span class="change-indicator ${row.change_avg>0?'positive':(row.change_avg<0?'negative':'neutral')}"><i class="fas fa-${row.change_avg>0?'arrow-up':(row.change_avg<0?'arrow-down':'minus')}"></i> ${row.change_avg>0?'+':''}${row.change_avg.toFixed(2)}%</span></td></tr>`;
        });
        return html;
    }

    function renderProgressCell(val) {
        const p = typeof val === 'number' ? val : 0;
        return `<td style="text-align: center;"><div class="progress-cell"><span class="progress-value">${p.toFixed(2)}%</span><div class="progress-bar-wrapper"><div class="progress-bar-fill" style="width: ${p}%"></div></div></div></td>`;
    }

    // ================================
    // HELPERS & OTHERS
    // ================================

    function populateWitelFilter(products) {
        const witels = [...new Set(products.map(p => p.witel).filter(w => w))].sort();
        let options = '<option value="">Semua Witel</option>';
        witels.forEach(w => options += `<option value="${w}">${w}</option>`);
        $('#witelFilter').html(options);
    }

    $('#witelFilter').on('change', function() {
        const w = $(this).val();
        $('#productBenchmarkTableBody').html(renderProductTable(w ? allProductData.filter(p => p.witel === w) : allProductData));
    });

    function renderProductLeaderboard(data) {
        let html = '';
        data.forEach(r => {
            html += `<tr class="${r.rank<=3?'rank-row-'+r.rank:''}"><td style="text-align: center;"><span class="rank-badge ${r.rank<=3?'rank-'+r.rank:''}">#${r.rank}</span></td><td><strong>${r.product}</strong></td><td style="text-align: center;">${r.avg_progress.toFixed(2)}%</td><td style="text-align: center;">${r.avg_result.toFixed(2)}%</td><td style="text-align: center;">${r.total_offerings}</td></tr>`;
        });
        return html;
    }

    function updateLeaderboard(type) {
        const data = type === 'am' ? amLeaderboardData : improvementLeaderboardData;
        const page = type === 'am' ? currentAMLeaderboardPage : currentImprovementLeaderboardPage;
        const result = renderLeaderboardData(data, page, type);
        $(`#${type}LeaderboardTableBody`).html(result.html);
        $(`#${type}LeaderboardPagination`).html(result.pagination);
    }

    function renderLeaderboardData(data, page, type) {
        if (!data.length) return { html: `<tr><td colspan="${type==='am'?4:5}" style="text-align:center;padding:30px;">Tidak ada data</td></tr>`, pagination: '' };
        
        const start = (page - 1) * ITEMS_PER_PAGE;
        const paged = data.slice(start, start + ITEMS_PER_PAGE);
        let html = '';
        
        paged.forEach(r => {
            html += `<tr class="${r.rank<=3?'rank-row-'+r.rank:''}"><td style="text-align: center;"><span class="rank-badge ${r.rank<=3?'rank-'+r.rank:''}">#${r.rank}</span></td>`;
            if (type === 'am') { html += `<td><strong>${r.am}</strong></td><td>${r.witel}</td>`; }
            else { html += `<td><strong>${r.am}</strong></td><td>${r.customer||'DATA CC HILANG'}</td><td>${r.product}</td>`; }
            html += `<td style="text-align: center;"><span class="change-indicator ${r.change_avg>0?'positive':(r.change_avg<0?'negative':'neutral')}"><i class="fas fa-${r.change_avg>0?'arrow-up':(r.change_avg<0?'arrow-down':'minus')}"></i> ${r.change_avg>0?'+':''}${r.change_avg.toFixed(2)}%</span></td></tr>`;
        });

        // Pagination UI logic omitted for brevity, keeping standard
        let pag = `<div class="pagination-info">Showing <strong>${start+1}</strong> to <strong>${Math.min(start+ITEMS_PER_PAGE, data.length)}</strong> of <strong>${data.length}</strong></div>`;
        const total = Math.ceil(data.length / ITEMS_PER_PAGE);
        if (total > 1) {
            pag += '<div class="pagination-buttons">';
            pag += `<button class="pagination-btn" ${page===1?'disabled':''} onclick="changePage(${page-1}, '${type}')">Prev</button>`;
            pag += `<button class="pagination-btn" ${page===total?'disabled':''} onclick="changePage(${page+1}, '${type}')">Next</button>`;
            pag += '</div>';
        }
        return { html, pagination: pag };
    }

    window.changePage = function(p, type) {
        if (type === 'am') currentAMLeaderboardPage = p; else currentImprovementLeaderboardPage = p;
        updateLeaderboard(type);
    };

    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active'); $(this).addClass('active');
        $('.tab-content').removeClass('active'); $(`#${tab==='am-level'?'amLevelContent':'productLevelContent'}`).addClass('active');
    });

    $('.am-tab-btn').on('click', function() {
        const t = $(this).data('am-tab');
        $('.am-tab-btn').removeClass('active'); $(this).addClass('active');
        $('.am-tab-content').removeClass('active'); $(`#am${t.charAt(0).toUpperCase()+t.slice(1)}Tab`).addClass('active');
    });

    $(document).on('click', '.product-tab-btn', function() {
        const t = $(this).data('product-tab');
        $('.product-tab-btn').removeClass('active'); $(this).addClass('active');
        $('.product-tab-content').removeClass('active'); $(`#product${t.charAt(0).toUpperCase()+t.slice(1)}Tab`).addClass('active');
    });

    $('#downloadReportAM, #downloadReportProduct').on('click', function() {
        if (!selectedSnapshot1 || !selectedSnapshot2) return showAlert('warning', 'Peringatan', 'Pilih data dulu');
        window.location.href = "{{ route('high-five.report.download') }}?snapshot_1_id=" + selectedSnapshot1 + "&snapshot_2_id=" + selectedSnapshot2;
    });
});
</script>
</div>
@endsection