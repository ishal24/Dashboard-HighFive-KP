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
<!-- Header -->
<div class="header-leaderboard">
    <h1 class="header-title">
        <i class="fas fa-chart-line"></i>
        High Five RLEGS TR3
    </h1>
    <p class="header-subtitle">Monitoring Performa Mingguan Account Manager dan Produk High Five</p>
</div>

<!-- Alert Container (Fixed Position) -->
<div class="alert-container" id="alertContainer"></div>

<!-- Section 1: Kelola Dataset High Five (UPDATED TITLE & BUTTON) -->
<div class="toolkit-container">
    <!-- Header -->
    <div class="toolkit-header">
        <h4>
            <i class="fas fa-database"></i>
            Kelola Dataset High Five
        </h4>
        <button type="button" class="btn-kelola-link" onclick="openLinkModal()">
            <i class="fas fa-cog"></i>
            Kelola Link Spreadsheet
        </button>
    </div>

    <!-- Subtitle -->
    <p style="font-size: 12px; color: var(--gray-600); margin: 8px 0 0 0; padding-left: 28px;">
        Rekap link dan update data performa mingguan
    </p>

    <!-- Body (Always Visible) -->
    <div class="toolkit-body" id="manualFetchBody" style="display: block;">
        <!-- Info Banner (UPDATED COPYWRITING) -->
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

        <!-- Manual Fetch Form -->
        <div class="toolkit-grid" style="grid-template-columns: 200px 160px 1fr 150px;">
            <!-- Link Dropdown -->
            <div class="field-group">
                <label>
                    <i class="fas fa-link"></i>
                    Link Spreadsheet
                </label>
                <select id="manualLinkSelect" class="native-select">
                    <option value="">Pilih Link</option>
                </select>
            </div>

            <!-- Date Picker -->
            <div class="field-group">
                <label>
                    <i class="fas fa-calendar"></i>
                    Tanggal Data
                </label>
                <input type="text" id="manualSnapshotDate" class="native-select" placeholder="Pilih tanggal" readonly>
            </div>

            <!-- Info Display (UPDATED FORMAT) -->
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

            <!-- Save Button -->
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

<!-- Section 2: Dataset Selector - Benchmarking (UPDATED LABELS) -->
<div class="selector-container">
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
            <label><i class="fas fa-database"></i> Data Progres 1 (Periode Lama)</label>
            <select id="snapshot1" class="native-select" disabled>
                <option value="">-- Pilih Data Progres 1 --</option>
            </select>
        </div>

        <div class="field-group">
            <label><i class="fas fa-database"></i> Data Progres 2 (Periode Baru)</label>
            <select id="snapshot2" class="native-select" disabled>
                <option value="">-- Pilih Data Progres 2 --</option>
            </select>
        </div>

        <button type="button" id="loadBenchmarkBtn" class="btn-load-data" disabled>
            <i class="fas fa-sync-alt"></i> Load Data
        </button>
    </div>
</div>

<!-- Section 3: Performance Tabs -->
<div class="performance-container">
    <!-- Tabs Navigation (NO ACTIVE BY DEFAULT) -->
    <div class="performance-tabs">
        <button class="tab-btn" data-tab="am-level">
            <i class="fas fa-user-tie"></i> Performa AM Level
        </button>
        <button class="tab-btn" data-tab="product-level">
            <i class="fas fa-box"></i> Performa Product Level
        </button>
    </div>

    <!-- Tab Content Area -->
    <div class="tab-content-area">
        <!-- Empty State -->
        <div id="emptyState" class="empty-state active">
            <i class="fas fa-chart-bar"></i>
            <h3>Belum Ada Data untuk Divisualisasikan</h3>
            <p>Pilih Filter Divisi dan 2 Data Progres untuk membandingkan performa</p>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="loading-state">
            <div class="spinner"></div>
            <p>Memproses data dari database...</p>
        </div>

        <!-- AM Level Tab Content -->
        <div id="amLevelContent" class="tab-content">
            <!-- Analysis Cards Section - 4 HORIZONTAL (UPDATED LAYOUT) -->
            <div class="cards-section">
                <div class="cards-section-title" onclick="toggleAnalysisCards()" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <i class="fas fa-chart-bar"></i>
                        Analisis Performa by Dataset
                    </div>
                    <i class="fas fa-chevron-down toolkit-toggle active" id="analysisCardsToggle"></i>
                </div>

                <div id="analysisCardsBody" class="cards-grid-wrapper" style="display: block;">
                    <!-- Dataset Name Display (UPDATED FORMAT) - MOVED TO TOP -->
                    <div style="margin-bottom: 16px; text-align: center;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--gray-700);">
                            <i class="fas fa-calendar-alt" style="font-size: 0.85rem; margin-right: 6px;"></i>
                            <span id="dataset1NameCard">Data Progres 1</span>
                            <span style="margin: 0 10px; color: var(--gray-500);">vs</span>
                            <span id="dataset2NameCard">Data Progres 2</span>
                        </span>
                    </div>

                    <!-- 4 CARDS HORIZONTAL (UPDATED GRID) -->
                    <div class="cards-grid-horizontal">
                        <!-- Dataset 1 - Most Progress -->
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

                        <!-- Dataset 1 - Least Progress -->
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

                        <!-- Dataset 2 - Most Progress -->
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

                        <!-- Dataset 2 - Least Progress -->
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

            <!-- Narrative Analysis Section (UPDATED COPYWRITING) -->
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

            <!-- AM Sub-Tabs: Benchmarking & Leaderboard -->
            <div class="am-tabs-container">
                <!-- Sub-Tabs Navigation -->
                <div class="am-tabs-navigation">
                    <button class="am-tab-btn active" data-am-tab="benchmarking">
                        <i class="fas fa-table"></i> Benchmarking Performa Account Manager
                    </button>
                    <button class="am-tab-btn" data-am-tab="leaderboard">
                        <i class="fas fa-medal"></i> Leaderboard AM (Top Performers)
                    </button>
                </div>

                <!-- Sub-Tab Content: Benchmarking -->
                <div class="am-tab-content active" id="amBenchmarkingTab">
                    <div class="table-container">
                        <div class="table-header">
                            <h4><i class="fas fa-table"></i> Benchmarking Performa Account Manager</h4>
                        </div>
                        <div class="table-header-fixed">
                            <table class="benchmark-table">
                                <thead>
                                    <tr>
                                        <th>Witel</th>
                                        <th>Account Manager</th>
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

                <!-- Sub-Tab Content: Leaderboard -->
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

            <!-- Download Report Button -->
            <div class="report-actions">
                <button type="button" id="downloadReportAM" class="btn-download-report" disabled>
                    <i class="fas fa-file-pdf"></i> Unduh Laporan PDF
                </button>
            </div>
        </div>

        <!-- Product Level Tab Content -->
        <div id="productLevelContent" class="tab-content">
            <!-- Statistics Cards (COMPACT VERSION) -->
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

            <!-- Product Analysis Section (COLLAPSIBLE) -->
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

            <!-- Product Filter -->
            <div class="product-filters">
                <label><i class="fas fa-filter"></i> Filter Witel:</label>
                <select id="witelFilter" class="native-select">
                    <option value="">Semua Witel</option>
                </select>
            </div>

            <!-- Product Sub-Tabs: Benchmarking & Leaderboards -->
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

                <!-- Product Benchmarking Content -->
                <div class="product-tab-content active" id="productBenchmarkingTab">
                    <div class="table-container">
                        <div class="table-header">
                            <h4><i class="fas fa-table"></i> Benchmarking Performa Per Produk</h4>
                        </div>
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

                <!-- Product Improvement Leaderboard -->
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

                <!-- Product Leaderboard -->
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

            <!-- Download Report Button -->
            <div class="report-actions">
                <button type="button" id="downloadReportProduct" class="btn-download-report" disabled>
                    <i class="fas fa-file-pdf"></i> Unduh Laporan PDF
                </button>
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

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {
    // ================================
    // INITIALIZATION
    // ================================

    // Initialize Bootstrap Select (only for divisi dropdowns)
    $('#filterDivisi').selectpicker({
        liveSearch: false,
        size: 6,
        dropupAuto: false,
        container: 'body'
    });

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

    $('#snapshot1').on('change', function() {
        selectedSnapshot1 = $(this).val();
        updateSnapshot2Options();
        checkCanLoad();
    });

    $('#snapshot2').on('change', function() {
        selectedSnapshot2 = $(this).val();
        checkCanLoad();
    });

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
        loadBenchmarkingData();
    });

    function loadBenchmarkingData() {
        $('#emptyState').removeClass('active');
        $('#loadingState').addClass('active');
        $('#amLevelContent, #productLevelContent').removeClass('active');

        // Remove all active tabs
        $('.tab-btn').removeClass('active');

        // Load AM Performance
        $.ajax({
            url: "{{ route('high-five.am-performance') }}",
            method: 'GET',
            data: {
                snapshot_1_id: selectedSnapshot1,
                snapshot_2_id: selectedSnapshot2
            },
            success: function(response) {
                if (response.success) {
                    renderAMPerformance(response.data);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal memuat data AM';
                showAlert('error', 'Error!', message);
                $('#loadingState').removeClass('active');
                $('#emptyState').addClass('active');
            }
        });

        // Load Product Performance
        $.ajax({
            url: "{{ route('high-five.product-performance') }}",
            method: 'GET',
            data: {
                snapshot_1_id: selectedSnapshot1,
                snapshot_2_id: selectedSnapshot2
            },
            success: function(response) {
                if (response.success) {
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

    // ================================
    // RENDER AM PERFORMANCE
    // ================================

    function renderAMPerformance(data) {
        // Update dataset names - UPDATED FORMAT
        const dataset1Label = `Data Progres ${data.snapshot_1.label}`;
        const dataset2Label = `Data Progres ${data.snapshot_2.label}`;

        $('#dataset1NameCard').text(dataset1Label);
        $('#dataset2NameCard').text(dataset2Label);
        $('#dataset1DateAM, #dataset1ResultAM').text(data.snapshot_1.tanggal_formatted);
        $('#dataset2DateAM, #dataset2ResultAM').text(data.snapshot_2.tanggal_formatted);

        // Update narrative titles
        $('#narrativeDataset1Title').text(`Hasil Analisis Data Progres ${data.snapshot_1.tanggal_formatted} (Periode Lama)`);
        $('#narrativeDataset2Title').text(`Hasil Analisis Data Progres ${data.snapshot_2.tanggal_formatted} (Periode Baru)`);

        // Update card period labels with dates
        $('.cards-grid-horizontal .analysis-card').eq(0).find('.period-label').text(`Periode ${data.snapshot_1.tanggal_formatted}`);
        $('.cards-grid-horizontal .analysis-card').eq(1).find('.period-label').text(`Periode ${data.snapshot_1.tanggal_formatted}`);
        $('.cards-grid-horizontal .analysis-card').eq(2).find('.period-label').text(`Periode ${data.snapshot_2.tanggal_formatted}`);
        $('.cards-grid-horizontal .analysis-card').eq(3).find('.period-label').text(`Periode ${data.snapshot_2.tanggal_formatted}`);

        // Update cards
        const cards1 = data.witel_analysis.cards.dataset_1;
        $('#mostProgressWitel1').text(cards1.most_progress?.witel || 'N/A');
        $('#mostProgressValue1').text(cards1.most_progress?.avg_progress ? cards1.most_progress.avg_progress.toFixed(2) + '%' : '0%');
        $('#leastProgressWitel1').text(cards1.least_progress?.witel || 'N/A');
        $('#leastProgressValue1').text(cards1.least_progress?.avg_progress ? cards1.least_progress.avg_progress.toFixed(2) + '%' : '0%');

        const cards2 = data.witel_analysis.cards.dataset_2;
        $('#mostProgressWitel2').text(cards2.most_progress?.witel || 'N/A');
        $('#mostProgressValue2').text(cards2.most_progress?.avg_progress ? cards2.most_progress.avg_progress.toFixed(2) + '%' : '0%');
        $('#leastProgressWitel2').text(cards2.least_progress?.witel || 'N/A');
        $('#leastProgressValue2').text(cards2.least_progress?.avg_progress ? cards2.least_progress.avg_progress.toFixed(2) + '%' : '0%');

        // Update narrative - FORMAT WITH BOLD
        const narrative = data.witel_analysis.narrative;
        $('#narrativeDataset1').html(formatNarrativeWithBold(narrative.dataset_1_paragraph));
        $('#narrativeDataset2').html(formatNarrativeWithBold(narrative.dataset_2_paragraph));
        $('#narrativeConclusion').html(formatNarrativeWithBold(narrative.conclusion_paragraph));

        // Render table WITH PROGRESS BARS
        const tableHTML = renderAMTable(data.benchmarking);
        $('#amBenchmarkTableBody').html(tableHTML);

        // Render leaderboard
        amLeaderboardData = data.leaderboard;
        currentAMLeaderboardPage = 1;
        const leaderboardResult = renderLeaderboard(amLeaderboardData, 1, ITEMS_PER_PAGE, 'am');
        $('#amLeaderboardTableBody').html(leaderboardResult.html);
        $('#amLeaderboardPagination').html(leaderboardResult.pagination);
    }

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

    function renderAMTable(data) {
        if (!data || data.length === 0) {
            return '<tr><td colspan="7" style="text-align: center; padding: 30px; color: var(--gray-500);">Tidak ada data</td></tr>';
        }

        let html = '';
        let currentWitel = null;
        let witelRows = [];

        data.forEach((row, index) => {
            // Check if new witel group
            if (row.witel !== currentWitel) {
                // Render previous witel summary
                if (currentWitel && witelRows.length > 0) {
                    html += renderWitelSummary(witelRows, currentWitel);
                }
                currentWitel = row.witel;
                witelRows = [];
            }

            witelRows.push(row);

            html += '<tr>';

            if (row.witel_rowspan > 0) {
                html += `<td rowspan="${row.witel_rowspan}">${row.witel}</td>`;
            }

            html += `<td><strong>${row.am}</strong></td>`;

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

        // Render last witel summary
        if (currentWitel && witelRows.length > 0) {
            html += renderWitelSummary(witelRows, currentWitel);
        }

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

    // NEW: Render progress cell with bar
    function renderProgressCell(value) {
        const percentage = typeof value === 'number' ? value : 0;
        return `
            <td style="text-align: center;">
                <div class="progress-cell">
                    <span class="progress-value">${percentage.toFixed(2)}%</span>
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar-fill" style="width: ${percentage}%"></div>
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