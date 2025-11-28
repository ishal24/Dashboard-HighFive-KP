@extends('layouts.main')

@section('title', 'Revenue RLEGS')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/revenue.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* ===== EXISTING STYLES - PRESERVED ===== */

        /* Additional styles for result modal */
        .result-modal-stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .result-modal-stats-container.four-cols {
            grid-template-columns: repeat(4, 1fr);
        }

        @media (max-width: 992px) {
            .result-modal-stats-container.four-cols {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .result-modal-stats-container.four-cols {
                grid-template-columns: 1fr;
            }
        }

        .result-modal-stat {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .result-modal-stat .icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .result-modal-stat .icon.success { background: #d4edda; color: #155724; }
        .result-modal-stat .icon.danger { background: #f8d7da; color: #721c24; }
        .result-modal-stat .icon.warning { background: #fff3cd; color: #856404; }
        .result-modal-stat .icon.info { background: #d1ecf1; color: #0c5460; }
        .result-modal-stat .content { flex: 1; }

        .result-modal-stat .content h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            white-space: nowrap;
            color: #212529;
        }
        .result-modal-stat .content p { margin: 0; color: #6c757d; font-size: 0.9rem; }

        .progress-bar-custom {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 1.5rem 0;
        }
        .progress-bar-fill-custom {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.5s ease;
        }

        .result-modal-info {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 4px;
        }

        .result-modal-info h6 {
            margin: 0 0 0.5rem 0;
            color: #0066cc;
            font-weight: 600;
        }

        .result-modal-info ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .result-modal-info li {
            color: #495057;
            margin-bottom: 0.25rem;
        }

        /* Better badge colors for Role & Status */
        .badge-role-am {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-role-hotda {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-status-registered {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-status-not-registered {
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }


        /* Checkbox column */
        .table thead th:first-child,
        .table tbody td:first-child {
            width: 48px !important;
            min-width: 48px !important;
            text-align: center !important;
            padding: 0.5rem !important;
        }

        .table thead th:first-child input[type="checkbox"],
        .table tbody td:first-child input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            cursor: pointer !important;
            display: inline-block !important;
            margin: 0 auto !important;
        }

        /* Aksi column wider for buttons */
        .table thead th:last-child,
        .table tbody td:last-child {
            width: 150px !important;
            min-width: 150px !important;
            text-align: center !important;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        /* Tab button active state - MERAH bukan biru */
        .tab-btn.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
            border-color: #dc3545 !important;
        }

        .tab-btn.active .badge {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }

        /* ==========================================
           ✨ MODERN IMPORT MODAL STYLES
           ========================================== */

        /* Import Modal Header */
        #importModal .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }

        #importModal .modal-header .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        #importModal .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        #importModal .modal-header .btn-close:hover {
            opacity: 1;
        }

        /* Import Modal Body */
        #importModal .modal-body {
            padding: 2rem;
            background: #f8f9fa;
        }

        /* ✨ TYPE SELECTOR - Modern Tabs Style (SAMA dengan tabs utama) */
        .type-selector {
            display: flex;
            gap: 0;
            background: white;
            padding: 6px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .type-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 8px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .type-btn:hover:not(.active) {
            background: rgba(220, 53, 69, 0.08);
            color: #dc3545;
            transform: translateY(-2px);
        }

        .type-btn.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.35);
            transform: scale(1.02);
        }

        .type-btn i {
            font-size: 1.1rem;
        }

        /* Import Panel (Form Container) */
        .imp-panel {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            animation: fadeInUp 0.4s ease;
        }

        .imp-panel.active {
            display: block;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Alert Boxes in Import Form */
        .imp-panel .alert {
            border: none;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .imp-panel .alert-info {
            background: linear-gradient(135deg, #e7f3ff 0%, #d4e9ff 100%);
            border-left-color: #0066cc;
            color: #004080;
        }

        .imp-panel .alert-warning {
            background: linear-gradient(135deg, #fff8e6 0%, #fff0cc 100%);
            border-left-color: #ff9800;
            color: #995c00;
        }

        .imp-panel .alert ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }

        .imp-panel .alert li {
            margin-bottom: 0.35rem;
            line-height: 1.6;
        }

        .imp-panel .alert strong {
            font-weight: 700;
            display: block;
            margin-bottom: 0.5rem;
        }

        /* Form Controls in Import Modal */
        .imp-panel .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .imp-panel .form-label .required {
            color: #dc3545;
            font-weight: 700;
        }

        .imp-panel .form-control,
        .imp-panel .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .imp-panel .form-control:focus,
        .imp-panel .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.12);
            background: white;
        }

        .imp-panel .form-control:hover,
        .imp-panel .form-select:hover {
            border-color: #dee2e6;
        }

        /* Month Picker in Import Form (SAMA dengan filter) */
        .imp-panel .datepicker-control {
            background: #f8f9fa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23dc3545' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") no-repeat right 1rem center;
            background-size: 20px;
            padding-right: 3rem;
            cursor: pointer;
            font-weight: 500;
        }

        /* Small Text (Template Link) */
        .imp-panel .text-muted {
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .imp-panel .text-muted a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .imp-panel .text-muted a:hover {
            color: #c82333;
            text-decoration: underline;
        }

        /* Submit Button in Import Form */
        .imp-panel .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .imp-panel .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .imp-panel .btn-primary:active {
            transform: translateY(0);
        }

        .imp-panel .btn-primary i {
            margin-right: 0.5rem;
        }

        /* Modal form styling */
        .modal-body .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .modal-body .form-control,
        .modal-body .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .modal-body .form-control:focus,
        .modal-body .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        }

        .modal-body .nav-tabs {
            border-bottom: 2px solid #e0e0e0;
        }

        .modal-body .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .modal-body .nav-tabs .nav-link.active {
            color: #dc3545;
            border-bottom: 3px solid #dc3545;
            background: transparent;
        }

        /* ✨ NEW: Divisi Button Group Styling */
        .divisi-button-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .divisi-toggle-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .divisi-toggle-btn:hover {
            border-color: #cbd5e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .divisi-toggle-btn.active {
            color: white;
            border-width: 2px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .divisi-toggle-btn.active::after {
            content: '✓';
            position: absolute;
            top: -8px;
            right: -8px;
            background: white;
            color: inherit;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            border: 2px solid currentColor;
        }

        .divisi-toggle-btn.dps.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }

        .divisi-toggle-btn.dss.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-color: #f093fb;
        }

        .divisi-toggle-btn.dgs.active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-color: #4facfe;
        }

        .divisi-toggle-btn.des.active {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            border-color: #fa709a;
        }

        /* Hidden helper for form submission */
        .divisi-hidden-container {
            display: none;
        }

        /* ✨ TELDA Field - Conditional Display */
        #editDataAMTeldaWrapper {
            transition: all 0.3s ease;
        }

        #editDataAMTeldaWrapper.hidden {
            display: none;
        }

        /* ==========================================
           ✨ PREVIEW MODAL - FIXED COLORS (MERAH SEMUA)
           ========================================== */

        #previewModal .modal-dialog {
            max-width: 90%;
            margin: 1.75rem auto;
        }

        /* ✅ HEADER MERAH dengan TEXT PUTIH */
        #previewModal .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }

        #previewModal .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
        }

        #previewModal .modal-title i {
            color: white;
        }

        #previewModal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        #previewModal .btn-close:hover {
            opacity: 1;
        }

        #previewModal .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* ✅ Preview Summary Cards - MERAH */
        .preview-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .preview-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .preview-card .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        /* ✅ SEMUA MERAH dengan intensitas berbeda */
        .preview-card.new .icon { color: #dc3545; }
        .preview-card.update .icon { color: #c82333; }
        .preview-card.conflict .icon { color: #a71d2a; }
        .preview-card.skip .icon { color: #6c757d; }

        .preview-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0 0.25rem 0;
            color: #212529;
        }

        .preview-card p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* ✅ Preview Actions - MERAH */
        .preview-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #fff5f5;
            border-radius: 8px;
            align-items: center;
            border-left: 4px solid #dc3545;
        }

        .preview-actions i {
            font-size: 1.25rem;
            color: #dc3545;
        }

        .preview-actions .btn-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* ✅ Button Group - MERAH */
        .preview-actions .btn {
            border: 2px solid #e9ecef;
            background: white;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .preview-actions .btn:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
            transform: translateY(-1px);
        }

        /* Preview Table */
        .preview-table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .preview-table {
            width: 100%;
            margin: 0;
        }

        /* ✅ Table Header MERAH */
        .preview-table thead {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .preview-table thead th {
            padding: 1rem;
            font-weight: 600;
            text-align: left;
            border: none;
            color: white;
        }

        .preview-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }

        .preview-table tbody tr:hover {
            background: #fff5f5;
        }

        .preview-table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        /* ✅ Status Badges - MERAH */
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.new {
            background: #ffd6d9;
            color: #a71d2a;
        }

        .status-badge.update {
            background: #ffe6e8;
            color: #c82333;
        }

        .status-badge.conflict {
            background: #dc3545;
            color: white;
        }

        .status-badge.skip {
            background: #e2e3e5;
            color: #383d41;
        }

        /* Comparison Display */
        .value-comparison {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .value-old {
            color: #dc3545;
            text-decoration: line-through;
            font-size: 0.875rem;
        }

        .value-new {
            color: #28a745;
            font-weight: 600;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
            color: #dc3545;
        }

        .loading-spinner p {
            margin-top: 1rem;
            color: #212529;
            font-weight: 600;
        }

        /* ✅ Preview Modal Footer - MERAH */
        #previewModal .modal-footer {
            border-top: 2px solid #e9ecef;
            padding: 1.5rem 2rem;
            background: #f8f9fa;
        }

        #previewModal .btn-execute {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            color: white;
            box-shadow: 0 4px 14px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
        }

        #previewModal .btn-execute:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            background: linear-gradient(135deg, #c82333 0%, #a71d2a 100%);
        }

        #previewModal .btn-execute:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        #previewModal .btn-light {
            border: 2px solid #e9ecef;
            background: white;
            color: #6c757d;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        #previewModal .btn-light:hover {
            border-color: #dc3545;
            color: #dc3545;
            background: #fff5f5;
        }
    </style>
@endsection

@section('content')
<div class="rlegs-container">

    <!-- ===== Page Header / Action Bar ===== -->
    <div class="page-header card-shadow">
        <div class="page-title">
            <h1>Data Revenue RLEGS</h1>
            <p>Kelola data Corporate Customer dan Account Manager RLEGS.</p>
        </div>

        <div class="page-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fa-solid fa-file-import me-2"></i>Import
            </button>
            <div class="export-group">
            <a href="/export/excel" class="btn btn-primary">
                <i class="fa-solid fa-file-export me-2"></i> Export
            </a>
            </div>

        </div>
    </div>

    <!-- ===== Filters Line ===== -->
    <div class="filters card-shadow">
         <form class="searchbar" action="#" method="GET" id="searchForm" onsubmit="return false;">
                <input type="search" class="search-input" id="searchInput" placeholder="Cari data...">
                <button class="search-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        <div class="filter-group">
            <label>Witel</label>
            <select class="form-select" id="filterWitel">
                <option value="all">Semua Witel</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Divisi</label>
            <select class="form-select" id="filterDivisi">
                <option value="all">Semua Divisi</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Segment</label>

            <!-- Select asli (hidden, untuk form submit) -->
            <select class="form-select" id="filter-segment" name="segment">
                <option value="all">Semua Segment</option>
                <!-- Options dari database akan diisi via JS -->
            </select>

            <!-- UI custom dengan tabs (akan di-generate via JS) -->
            <div class="seg-select" id="segSelect">
                <!-- Tombol trigger -->
                <button type="button" class="seg-select__btn" aria-haspopup="listbox">
                    <span class="seg-select__label">Semua Segment</span>
                    <span class="seg-select__caret"></span>
                </button>

                <!-- Menu dropdown (akan diisi via JS) -->
                <div class="seg-menu" id="segMenu" role="listbox">
                    <div class="seg-tabs" id="segTabs" role="tablist">
                        <!-- Tabs akan di-generate via JS -->
                    </div>
                    <div class="seg-panels" id="segPanels">
                        <!-- Panels akan di-generate via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- === Periode: MONTHPICKER (pilih bulan & tahun) === -->
        <div class="filter-group" id="filterPeriodeGroup">
            <label>Periode</label>
            <input type="text" id="filter-date" class="form-control datepicker-control" placeholder="Pilih bulan & tahun" autocomplete="off" readonly>
            <input type="hidden" id="filter-month" name="month" value="{{ date('m') }}">
            <input type="hidden" id="filter-year"  name="year"  value="{{ date('Y') }}">
        </div>

        <div class="filter-actions">
            <button class="btn btn-primary" id="btn-apply-filter">
                <i class="fa-solid fa-check me-1"></i>Terapkan
            </button>
            <button class="btn btn-secondary" id="btn-reset-filter">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset
            </button>
        </div>
    </div>

    <!-- ===== Tabs ===== -->
    <div class="tabs card-shadow">
        <button class="tab-btn active" data-tab="tab-cc-revenue">
            <i class="fa-solid fa-chart-line me-2"></i>Revenue CC
            <span class="badge neutral" id="badge-cc-rev">0</span>
        </button>
        <button class="tab-btn" data-tab="tab-am-revenue">
            <i class="fa-solid fa-user-tie me-2"></i>Revenue AM
            <span class="badge neutral" id="badge-am-rev">0</span>
        </button>
        <button class="tab-btn" data-tab="tab-data-am">
            <i class="fa-solid fa-users me-2"></i>Data AM
            <span class="badge neutral" id="badge-data-am">0</span>
        </button>
        <button class="tab-btn" data-tab="tab-data-cc">
            <i class="fa-solid fa-building me-2"></i>Data CC
            <span class="badge neutral" id="badge-cc">0</span>
        </button>
    </div>

    <!-- ===== Tab: Revenue CC ===== -->
    <div id="tab-cc-revenue" class="tab-panel card-shadow active">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Corporate Customer</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue CC</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedCC" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteCC">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
                <div class="btn-segmentation" role="group" aria-label="Revenue Type">
                    <button class="seg-btn active" data-revtype="REGULER">Reguler</button>
                    <button class="seg-btn" data-revtype="NGTMA">NGTMA</button>
                    <button class="seg-btn" data-revtype="KOMBINASI">Kombinasi</button>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllCC"></th>
                        <th>Nama CC</th>
                        <th>Segment</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted"
                               data-bs-toggle="tooltip"
                               title="Nilai ini menampilkan Revenue sesuai kategori (Reguler/NGTMA/Kombinasi). Hover pada angka untuk detail: Revenue Sold/Bill."></i>
                        </th>
                        <th>Bulan</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableRevenueCC">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationRevenueCC">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===== Tab: Revenue AM ===== -->
    <div id="tab-am-revenue" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Account Manager</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue AM</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedAM" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteAM">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
                <div class="am-toggles">
                    <div class="btn-toggle" data-role="amMode">
                        <button class="am-btn active" data-mode="all">Semua</button>
                        <button class="am-btn" data-mode="AM">AM</button>
                        <button class="am-btn" data-mode="HOTDA">HOTDA</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern" id="table-am">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllAM"></th>
                        <th style="width: auto;">Nama AM</th>
                        <th style="width: auto;">Corporate Customer</th>
                        <th class="text-end" style="width: 140px;">Target Revenue</th>
                        <th class="text-end" style="width: 140px;">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted"
                               data-bs-toggle="tooltip"
                               title="Nilai revenue mengikuti mode (AM/HOTDA)."></i>
                        </th>
                        <th class="text-end" style="width: 100px;">Achievement</th>
                        <th style="width: 100px;">Bulan</th>
                        <th class="telda-col" style="width: auto;">TELDA</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableRevenueAM">
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationRevenueAM">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===== Tab: Data AM ===== -->
    <div id="tab-data-am" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Account Manager</h3>
                <p class="muted">Daftar Account Manager yang terdaftar di sistem</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedDataAM" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteDataAM">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllDataAM"></th>
                        <th>Nama AM</th>
                        <th>Witel</th>
                        <th>Role</th>
                        <th>TELDA</th>
                        <th>Status Registrasi</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableDataAM">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationDataAM">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===== Tab: Data CC ===== -->
    <div id="tab-data-cc" class="tab-panel card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Data Corporate Customer</h3>
                <p class="muted">Detail Corporate Customer</p>
            </div>
            <div class="right" style="display: flex; gap: 1rem; align-items: center;">
                <button class="btn btn-danger btn-sm" id="btnDeleteSelectedDataCC" disabled>
                    <i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btnBulkDeleteDataCC">
                    <i class="fa-solid fa-trash-alt me-2"></i>Hapus Semua
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th style="width: 48px; min-width: 48px; text-align: center;"><input type="checkbox" id="selectAllDataCC"></th>
                        <th>Nama CC</th>
                        <th>NIPNAS</th>
                        <th class="text-center" style="width: 150px; min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableDataCC">
                    <tr>
                        <td colspan="4" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar" id="paginationDataCC">
            <div class="info">Menampilkan 0 dari 0 hasil</div>
            <div class="pages"></div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

</div>

<!-- ========================================
     ✨ MODERN IMPORT MODAL WITH MONTH PICKER
     ======================================== -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color: white;">
            <i class="fa-solid fa-file-import" style="color: white;"></i>
            Import Data Revenue
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <!-- ✨ Modern Type Selector (SAMA dengan tabs utama) -->
        <div class="type-selector">
          <button class="type-btn active" data-imp="imp-data-cc">
              <i class="fa-solid fa-building"></i>
              Data CC
          </button>
          <button class="type-btn" data-imp="imp-data-am">
              <i class="fa-solid fa-users"></i>
              Data AM
          </button>
          <button class="type-btn" data-imp="imp-rev-cc">
              <i class="fa-solid fa-chart-line"></i>
              Revenue CC
          </button>
          <button class="type-btn" data-imp="imp-rev-map">
              <i class="fa-solid fa-user-tie"></i>
              Revenue AM
          </button>
        </div>

        <!-- ✅ FORM 1: Data CC -->
        <div id="imp-data-cc" class="imp-panel active">
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-info-circle" style="font-size: 1.25rem; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 0.5rem;">Format CSV:</strong>
                        <div style="font-size: 0.9rem;">
                            <strong>Kolom yang diperlukan:</strong> NIPNAS, STANDARD_NAME &nbsp;|&nbsp;
                            <strong>Update data:</strong> Berlaku jika data revenue dari pelanggan pada periode yang sama sudah ada sebelumnya
                        </div>
                    </div>
                </div>
            </div>

            <form id="formDataCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_cc">

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fa-solid fa-file-csv"></i>
                        Upload File CSV <span class="required">*</span>
                    </label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                    <small class="text-muted">
                        <a href="{{ route('revenue.template', ['type' => 'data-cc']) }}">
                            <i class="fa-solid fa-download me-1"></i>Download Template
                        </a>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Data CC
                </button>
            </form>
        </div>

        <!-- ✅ FORM 2: Data AM -->
        <div id="imp-data-am" class="imp-panel">
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-info-circle" style="font-size: 1.25rem; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 0.5rem;">Format CSV:</strong>
                        <div style="font-size: 0.9rem; line-height: 1.6;">
                            <strong>Kolom:</strong> NIK, NAMA_AM, WITEL, ROLE, DIVISI, TELDA<br>
                            <strong>ROLE:</strong> AM atau HOTDA (TELDA wajib untuk HOTDA) &nbsp;|&nbsp;
                            <strong>Update:</strong> Jika NIK sudah ada → data di-update
                        </div>
                    </div>
                </div>
            </div>

            <form id="formDataAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="data_am">

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fa-solid fa-file-csv"></i>
                        Upload File CSV <span class="required">*</span>
                    </label>
                    <input type="file" class="form-control" name="file" accept=".csv" required>
                    <small class="text-muted">
                        <a href="{{ route('revenue.template', ['type' => 'data-am']) }}">
                            <i class="fa-solid fa-download me-1"></i>Download Template
                        </a>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Data AM
                </button>
            </form>
        </div>

        <!-- ✅ FORM 3: Revenue CC -->
        <div id="imp-rev-cc" class="imp-panel">
            <div class="alert alert-info" style="cursor: pointer; margin-bottom: 1rem;" data-bs-toggle="collapse" data-bs-target="#infoRevCC">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <strong>Instruksi Format CSV</strong>
                        <small class="ms-2 text-muted">(klik untuk penjelasan lebih lanjut)</small>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </div>

            <div class="collapse" id="infoRevCC">
                <div class="alert alert-warning" style="margin-bottom: 1rem;">
                    <strong>Penting:</strong>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>Pilih <strong>Periode</strong> terlebih dahulu</li>
                        <li>Jika periode + NIPNAS sudah ada → <strong>UPDATE</strong></li>
                        <li>AM revenues otomatis <strong>recalculated</strong></li>
                    </ul>
                </div>

                <div class="card mb-3" style="border: 2px solid #e7f3ff; background: #f8fcff;">
                    <div class="card-body" style="padding: 1rem;">
                        <h6 class="mb-2" style="color: #0066cc; font-weight: 600;">
                            <i class="fa-solid fa-file-lines me-1"></i> Format DGS/DSS:
                        </h6>
                        <small><strong>Kolom:</strong> NIPNAS, LSEGMENT_HO, WITEL_HO, REVENUE_SOLD</small>

                        <hr style="margin: 0.75rem 0;">

                        <h6 class="mb-2" style="color: #0066cc; font-weight: 600;">
                            <i class="fa-solid fa-file-lines me-1"></i> Format DPS:
                        </h6>
                        <small><strong>Kolom:</strong> NIPNAS, LSEGMENT_HO, WITEL_HO, WITEL_BILL, REVENUE_BILL</small>
                    </div>
                </div>
            </div>

            <form id="formRevenueCC" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_cc">

                <div class="row gx-3 gy-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-days"></i>
                            Periode <span class="required">*</span>
                        </label>
                        <input type="text" id="import-cc-periode" class="form-control datepicker-control" placeholder="Pilih Bulan & Tahun" autocomplete="off" readonly required>
                        <input type="hidden" name="month" id="import-cc-month">
                        <input type="hidden" name="year" id="import-cc-year">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-file-csv"></i>
                            Upload File CSV <span class="required">*</span>
                        </label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <small class="text-muted">
                            <a href="{{ route('revenue.template', ['type' => 'revenue-cc-dgs']) }}">
                                <i class="fa-solid fa-download me-1"></i>Template DGS/DSS
                            </a> |
                            <a href="{{ route('revenue.template', ['type' => 'revenue-cc-dps']) }}">
                                Template DPS
                            </a>
                        </small>
                    </div>

                    <div class="col-md-4">
                        <div class="filter-group">
                            <label class="form-label">
                                <i class="fa-solid fa-sitemap"></i>
                                Divisi <span class="required">*</span>
                            </label>
                            <select class="form-select" name="divisi_id" id="divisiImport" required>
                                <option value="">Pilih Divisi</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">
                            <i class="fa-solid fa-tag"></i>
                            Jenis Data <span class="required">*</span>
                        </label>
                        <select class="form-select" name="jenis_data" required>
                            <option value="">Pilih Jenis Data</option>
                            <option value="revenue">Revenue (Real)</option>
                            <option value="target">Target Revenue</option>
                        </select>
                        <small class="text-muted">
                            Pilih "Revenue" untuk REVENUE_SOLD/BILL, "Target" untuk TARGET_REVENUE
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Revenue CC
                </button>
            </form>
        </div>

        <!-- ✅ FORM 4: Revenue AM -->
        <div id="imp-rev-map" class="imp-panel">
            <div class="alert alert-info" style="cursor: pointer; margin-bottom: 1rem;" data-bs-toggle="collapse" data-bs-target="#infoRevAM">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <strong>Instruksi Format CSV</strong>
                        <small class="ms-2 text-muted">(klik untuk penjelasan lebih lanjut)</small>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </div>

            <div class="collapse" id="infoRevAM">
                <div class="alert alert-warning" style="margin-bottom: 1rem;">
                    <strong>Penting:</strong>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.25rem; font-size: 0.9rem;">
                        <li>Pilih <strong>Periode</strong> terlebih dahulu</li>
                        <li><strong>Revenue CC harus sudah ada</strong> untuk periode ini</li>
                        <li>PROPORSI disimpan untuk recalculation otomatis</li>
                    </ul>
                </div>

                <div class="card mb-3" style="border: 2px solid #e7f3ff; background: #f8fcff;">
                    <div class="card-body" style="padding: 1rem;">
                        <h6 class="mb-2" style="color: #0066cc; font-weight: 600;">
                            <i class="fa-solid fa-file-lines me-1"></i> Format CSV:
                        </h6>
                        <small><strong>Kolom:</strong> NIPNAS, NIK_AM, PROPORSI (0-100)</small>
                    </div>
                </div>
            </div>

            <form id="formRevenueAM" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" value="revenue_am">

                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-days"></i>
                            Periode <span class="required">*</span>
                        </label>
                        <input type="text" id="import-am-periode" class="form-control datepicker-control" placeholder="Pilih Bulan & Tahun" autocomplete="off" readonly required>
                        <input type="hidden" name="month" id="import-am-month">
                        <input type="hidden" name="year" id="import-am-year">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-file-csv"></i>
                            Upload File CSV <span class="required">*</span>
                        </label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                        <small class="text-muted">
                            <a href="{{ route('revenue.template', ['type' => 'revenue-am']) }}">
                                <i class="fa-solid fa-download me-1"></i>Download Template
                            </a>
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i>Import Revenue AM
                </button>
            </form>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     ✨ PREVIEW MODAL
     ========================================== -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-eye me-2"></i>
                    Preview Import Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="preview-summary" id="previewSummary"></div>

                <div class="preview-actions">
                    <i class="fa-solid fa-info-circle"></i>
                    <div style="flex: 1;">
                        <strong>Pilih data yang akan diimport:</strong>
                        <div class="btn-group mt-2">
                            <button class="btn btn-sm" id="btnSelectAll">
                                <i class="fa-solid fa-check-double me-1"></i>Pilih Semua
                            </button>
                            <button class="btn btn-sm" id="btnDeselectAll">
                                <i class="fa-solid fa-times me-1"></i>Batal Semua
                            </button>
                            <button class="btn btn-sm" id="btnSelectNew">
                                <i class="fa-solid fa-plus me-1"></i>Pilih Baru Saja
                            </button>
                            <button class="btn btn-sm" id="btnSelectUpdates">
                                <i class="fa-solid fa-edit me-1"></i>Pilih Update Saja
                            </button>
                        </div>
                    </div>
                </div>

                <div class="preview-table-container">
                    <table class="preview-table table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">
                                    <input type="checkbox" id="selectAllPreview">
                                </th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-execute" id="btnExecuteImport">
                    <i class="fa-solid fa-check me-2"></i>
                    Lanjutkan Import (<span id="selectedCount">0</span> dipilih)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner-border"></div>
        <p id="loadingText">Memproses...</p>
    </div>
</div>

<!-- =================== ✅ RESULT MODAL =================== -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hasil Import</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" class="btn btn-primary" id="btnDownloadErrorLog" style="display: none;" target="_blank">
                    <i class="fa-solid fa-download me-2"></i>Download Error Log
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ========================================
     ✅ EDIT MODALS - IMPROVED
     ======================================== -->
<!-- Modal Edit Revenue CC -->
<div class="modal fade" id="modalEditRevenueCC" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Revenue CC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditRevenueCC">
                <div class="modal-body">
                    <input type="hidden" id="editCCRevenueId">
                    <div class="mb-3">
                        <label class="form-label">Nama CC</label>
                        <input type="text" class="form-control" id="editCCNamaCC" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Revenue</label>
                        <input type="number" class="form-control" id="editCCTargetRevenue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Real Revenue</label>
                        <input type="number" class="form-control" id="editCCRealRevenue" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Revenue AM -->
<div class="modal fade" id="modalEditRevenueAM" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Revenue AM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditRevenueAM">
                <div class="modal-body">
                    <input type="hidden" id="editAMRevenueId">
                    <div class="mb-3">
                        <label class="form-label">Nama AM</label>
                        <input type="text" class="form-control" id="editAMNamaAM" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proporsi (%)</label>
                        <input type="number" class="form-control" id="editAMProporsi" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Revenue</label>
                        <input type="number" class="form-control" id="editAMTargetRevenue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Real Revenue</label>
                        <input type="number" class="form-control" id="editAMRealRevenue" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ✅ IMPROVED: Modal Edit Data AM with Conditional Tabs & Fields -->
<div class="modal fade" id="modalEditDataAM" tabindex="-1" aria-labelledby="modalEditDataAMLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditDataAMLabel">Edit Data AM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- ✅ CONDITIONAL: Tabs only if registered -->
                <ul class="nav nav-tabs mb-3" role="tablist" id="editDataAMTabs" style="display: none;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                                id="tab-edit-data-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-edit-data"
                                type="button"
                                role="tab"
                                aria-controls="tab-edit-data"
                                aria-selected="true">
                            Data AM
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="tab-change-password-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-change-password"
                                type="button"
                                role="tab"
                                aria-controls="tab-change-password"
                                aria-selected="false">
                            Ganti Password
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="editDataAMTabContent">
                    <!-- Tab 1: Edit Data AM -->
                    <div class="tab-pane fade show active"
                         id="tab-edit-data"
                         role="tabpanel"
                         aria-labelledby="tab-edit-data-tab">
                        <form id="formEditDataAM">
                            <input type="hidden" id="editDataAMId">

                            <div class="mb-3">
                                <label class="form-label">Nama AM</label>
                                <input type="text" class="form-control" id="editDataAMNama" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">NIK</label>
                                <input type="text" class="form-control" id="editDataAMNik" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" id="editDataAMRole" required>
                                    <option value="">Pilih Role</option>
                                    <option value="AM">AM</option>
                                    <option value="HOTDA">HOTDA</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Witel</label>
                                <select class="form-select" id="editDataAMWitel" required>
                                    <option value="">Pilih Witel</option>
                                </select>
                            </div>

                            <!-- ✅ CONDITIONAL: TELDA field (only for HOTDA) -->
                            <div class="mb-3" id="editDataAMTeldaWrapper">
                                <label class="form-label">TELDA</label>
                                <select class="form-select" id="editDataAMTelda">
                                    <option value="">Pilih TELDA</option>
                                </select>
                            </div>

                            <!-- ✨ Divisi Button Group -->
                            <div class="mb-3">
                                <label class="form-label">Divisi</label>
                                <small class="text-muted d-block mb-2">
                                    <i class="fa-solid fa-info-circle me-1"></i>
                                    Klik button untuk memilih divisi (bisa pilih lebih dari satu)
                                </small>
                                <div class="divisi-button-group" id="divisiButtonGroup"></div>
                                <div class="divisi-hidden-container" id="divisiHiddenInputs"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </form>
                    </div>

                    <!-- Tab 2: Change Password (only if registered) -->
                    <div class="tab-pane fade"
                         id="tab-change-password"
                         role="tabpanel"
                         aria-labelledby="tab-change-password-tab">
                        <form id="formChangePasswordAM">
                            <input type="hidden" id="changePasswordAMId">

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="newPassword" required minlength="6">
                                <small class="text-muted">Minimal 6 karakter</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-key me-2"></i>Ganti Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Data CC -->
<div class="modal fade" id="modalEditDataCC" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data CC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditDataCC">
                <div class="modal-body">
                    <input type="hidden" id="editDataCCId">
                    <div class="mb-3">
                        <label class="form-label">Nama CC</label>
                        <input type="text" class="form-control" id="editDataCCNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIPNAS</label>
                        <input type="text" class="form-control" id="editDataCCNipnas" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
$(document).ready(function() {
  // ========================================
  // STATE MANAGEMENT
  // ========================================
  let currentTab = 'tab-cc-revenue';
  let currentPage = 1;
  let perPage = 25;
  let currentFilters = {
    search: '',
    witel_id: 'all',
    divisi_id: 'all',
    segment_id: 'all',
    periode: '',
    tipe_revenue: 'REGULER',
    role: 'all'
  };

  // Store divisi data globally for modal
  let allDivisiData = [];

  // Store TELDA data globally for modal
  let allTeldaData = [];

  // ✨ NEW: Preview Import State
  let previewData = null;
  let currentImportType = null;
  let currentFormData = null;
  let currentSessionId = null;

  // ========================================
  // FLATPICKR MONTH YEAR PICKER
  // ========================================
  (function initMonthYearPicker() {
    const dateInput   = document.getElementById('filter-date');
    const hiddenMonth = document.getElementById('filter-month');
    const hiddenYear  = document.getElementById('filter-year');

    if (!dateInput) return;

    const currentYear = new Date().getFullYear();
    let selectedYear  = currentYear;
    let selectedMonth = new Date().getMonth();

    const YEAR_FLOOR = 2020;
    function getYearWindow() {
      const nowY = new Date().getFullYear();
      const start = nowY;
      const end   = Math.max(YEAR_FLOOR, nowY - 5);
      return { start, end };
    }
    function clampSelectedYear() {
      const { start, end } = getYearWindow();
      if (selectedYear > start) selectedYear = start;
      if (selectedYear < end)   selectedYear = end;
    }

    let isYearView = false;
    let fpInstance = null;

    function getTriggerEl(instance){
      return instance?.altInput || dateInput;
    }
    function syncCalendarWidth(instance){
      try{
        const cal = instance.calendarContainer;
        const trigger = getTriggerEl(instance);
        if (!cal || !trigger) return;

        const rect = trigger.getBoundingClientRect();
        const w = Math.round(rect.width);

        cal.style.boxSizing = 'border-box';
        cal.style.width     = w + 'px';
        cal.style.maxWidth  = w + 'px';
      }catch(e){
        // no-op
      }
    }

    const fp = flatpickr(dateInput, {
      plugins: [ new monthSelectPlugin({
        shorthand: true,
        dateFormat: "Y-m",
        altFormat: "F Y",
        theme: "light"
      })],
      altInput: true,
      defaultDate: new Date(),
      allowInput: false,
      monthSelectorType: 'static',

      onReady(selectedDates, value, instance) {
        fpInstance = instance;
        const d = selectedDates?.[0] || new Date();
        selectedYear  = d.getFullYear();
        selectedMonth = d.getMonth();

        clampSelectedYear();

        hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
        hiddenYear.value  = selectedYear;

        instance.calendarContainer.classList.add('fp-compact');

        syncCalendarWidth(instance);

        setupCustomUI(instance);
      },

      onOpen(selectedDates, value, instance) {
        fpInstance = instance;
        isYearView = false;

        clampSelectedYear();
        renderMonthView(instance);

        syncCalendarWidth(instance);

        setTimeout(() => {
          const activeMonth = instance.calendarContainer.querySelector('.fp-month-option.selected');
          if (activeMonth) {
            activeMonth.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        }, 100);
      }
    });

    window.addEventListener('resize', () => {
      if (fpInstance && fpInstance.isOpen) {
        syncCalendarWidth(fpInstance);
      }
    });

    function setupCustomUI(instance) {
      const cal = instance.calendarContainer;
      const monthsContainer = cal.querySelector('.flatpickr-monthSelect-months, .monthSelect-months');
      if (monthsContainer) {
        monthsContainer.style.display = 'none';
      }
    }

    function renderMonthView(instance) {
      const cal = instance.calendarContainer;
      const header = cal.querySelector('.flatpickr-current-month');

      if (header) {
        header.innerHTML = `
          <button type="button" class="fp-year-toggle" style="background:transparent;border:0;color:#fff;font-size:1.25rem;font-weight:700;cursor:pointer;padding:8px 16px;border-radius:8px;">
            ${selectedYear} <span style="font-size:0.875rem;margin-left:4px;">▼</span>
          </button>
        `;
        const yearToggle = header.querySelector('.fp-year-toggle');
        yearToggle.addEventListener('click', (e) => {
          e.preventDefault(); e.stopPropagation();
          isYearView = true;
          renderYearView(instance);
        });
      }

      let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-monthSelect-months, .monthSelect-months, .flatpickr-innerContainer');
      if (!container) return;

      container.innerHTML = '';
      container.className = 'fp-month-grid';
      container.setAttribute('tabindex', '0');

      const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

      monthNames.forEach((name, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fp-month-option';
        btn.textContent = name;

        const currentSelectedDate = fp.selectedDates[0] || new Date();
        if (idx === selectedMonth && selectedYear === currentSelectedDate.getFullYear()) {
          btn.classList.add('selected');
        }

        btn.addEventListener('click', (e) => {
          e.preventDefault(); e.stopPropagation();
          selectedMonth = idx;
          const newDate = new Date(selectedYear, selectedMonth, 1);
          fp.setDate(newDate, true);
          hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
          hiddenYear.value  = selectedYear;

          currentFilters.periode = `${selectedYear}-${String(selectedMonth + 1).padStart(2, '0')}`;
          currentPage = 1;
          loadData();

          setTimeout(() => fp.close(), 150);
        });

        container.appendChild(btn);
      });

      const activeMonth = container.querySelector('.fp-month-option.selected');
      if (activeMonth) {
        activeMonth.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    function renderYearView(instance) {
      const cal = instance.calendarContainer;
      const header = cal.querySelector('.flatpickr-current-month');

      if (header) {
        header.innerHTML = `
          <button type="button" class="fp-back-btn" style="background:transparent;border:0;color:#fff;font-size:1.5rem;cursor:pointer;position:absolute;left:16px;top:50%;transform:translateY(-50%);line-height:1;">
            ‹
          </button>
          <span style="color:#fff;font-weight:700;font-size:1.125rem;">Tahun</span>
        `;

        const backBtn = header.querySelector('.fp-back-btn');
        backBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          isYearView = false;
          renderMonthView(instance);
        });
      }

      let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-innerContainer');
      if (!container) return;

      container.innerHTML = '';
      container.className = 'fp-year-grid';

      const { start, end } = getYearWindow();
      for (let y = start; y >= end; y--) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fp-year-option';
        btn.textContent = y;

        if (y === selectedYear) {
          btn.classList.add('active');
        }

        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          selectedYear = y;
          hiddenYear.value = selectedYear;

          isYearView = false;
          renderMonthView(instance);
        });

        container.appendChild(btn);
      }

      setTimeout(() => {
        const activeYear = container.querySelector('.fp-year-option.active');
        if (activeYear) {
          activeYear.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, 100);
    }

    const resetBtn = document.getElementById('btn-reset-filter');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        const now = new Date();
        selectedYear  = now.getFullYear();
        selectedMonth = now.getMonth();

        clampSelectedYear();

        fp.setDate(now, true);
        hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
        hiddenYear.value  = selectedYear;
      });
    }
  })();

  // ========================================
  // ✨ FLATPICKR FOR IMPORT MODALS (Revenue CC & AM)
  // ========================================
  (function initImportMonthPickers() {
    const YEAR_FLOOR = 2020;

    function getYearWindow() {
      const nowY = new Date().getFullYear();
      const start = nowY;
      const end = Math.max(YEAR_FLOOR, nowY - 5);
      return { start, end };
    }

    function createMonthPicker(inputId, hiddenMonthId, hiddenYearId) {
      const dateInput = document.getElementById(inputId);
      const hiddenMonth = document.getElementById(hiddenMonthId);
      const hiddenYear = document.getElementById(hiddenYearId);

      if (!dateInput) return null;

      let selectedYear = new Date().getFullYear();
      let selectedMonth = new Date().getMonth();
      let isYearView = false;

      const fp = flatpickr(dateInput, {
        plugins: [ new monthSelectPlugin({
          shorthand: true,
          dateFormat: "Y-m",
          altFormat: "F Y",
          theme: "light"
        })],
        altInput: true,
        defaultDate: new Date(),
        allowInput: false,
        monthSelectorType: 'static',

        onReady(selectedDates, value, instance) {
          const d = selectedDates?.[0] || new Date();
          selectedYear = d.getFullYear();
          selectedMonth = d.getMonth();

          hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
          hiddenYear.value = selectedYear;

          instance.calendarContainer.classList.add('fp-compact');
          setupCustomUI(instance);
        },

        onOpen(selectedDates, value, instance) {
          isYearView = false;
          renderMonthView(instance);
        }
      });

      function setupCustomUI(instance) {
        const cal = instance.calendarContainer;
        const monthsContainer = cal.querySelector('.flatpickr-monthSelect-months, .monthSelect-months');
        if (monthsContainer) {
          monthsContainer.style.display = 'none';
        }
      }

      function renderMonthView(instance) {
        const cal = instance.calendarContainer;
        const header = cal.querySelector('.flatpickr-current-month');

        if (header) {
          header.innerHTML = `
            <button type="button" class="fp-year-toggle" style="background:transparent;border:0;color:#fff;font-size:1.25rem;font-weight:700;cursor:pointer;padding:8px 16px;border-radius:8px;">
              ${selectedYear} <span style="font-size:0.875rem;margin-left:4px;">▼</span>
            </button>
          `;
          const yearToggle = header.querySelector('.fp-year-toggle');
          yearToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            isYearView = true;
            renderYearView(instance);
          });
        }

        let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-monthSelect-months, .monthSelect-months, .flatpickr-innerContainer');
        if (!container) return;

        container.innerHTML = '';
        container.className = 'fp-month-grid';
        container.setAttribute('tabindex', '0');

        const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        monthNames.forEach((name, idx) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'fp-month-option';
          btn.textContent = name;

          if (idx === selectedMonth && selectedYear === fp.selectedDates[0].getFullYear()) {
            btn.classList.add('selected');
          }

          btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            selectedMonth = idx;
            const newDate = new Date(selectedYear, selectedMonth, 1);
            fp.setDate(newDate, true);
            hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
            hiddenYear.value = selectedYear;
            setTimeout(() => fp.close(), 150);
          });

          container.appendChild(btn);
        });
      }

      function renderYearView(instance) {
        const cal = instance.calendarContainer;
        const header = cal.querySelector('.flatpickr-current-month');

        if (header) {
          header.innerHTML = `
            <button type="button" class="fp-back-btn" style="background:transparent;border:0;color:#fff;font-size:1.5rem;cursor:pointer;position:absolute;left:16px;top:50%;transform:translateY(-50%);line-height:1;">
              ‹
            </button>
            <span style="color:#fff;font-weight:700;font-size:1.125rem;">Tahun</span>
          `;

          const backBtn = header.querySelector('.fp-back-btn');
          backBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            isYearView = false;
            renderMonthView(instance);
          });
        }

        let container = cal.querySelector('.fp-month-grid, .fp-year-grid, .flatpickr-innerContainer');
        if (!container) return;

        container.innerHTML = '';
        container.className = 'fp-year-grid';

        const { start, end } = getYearWindow();
        for (let y = start; y >= end; y--) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'fp-year-option';
          btn.textContent = y;

          if (y === selectedYear) {
            btn.classList.add('active');
          }

          btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            selectedYear = y;
            hiddenYear.value = selectedYear;
            isYearView = false;
            renderMonthView(instance);
          });

          container.appendChild(btn);
        }
      }

      return fp;
    }

    let importCCPicker = null;
    let importAMPicker = null;

    const importModal = document.getElementById('importModal');
    if (importModal) {
      importModal.addEventListener('shown.bs.modal', function() {
        setTimeout(() => {
          if (importCCPicker) {
            importCCPicker.destroy();
            importCCPicker = null;
          }
          if (importAMPicker) {
            importAMPicker.destroy();
            importAMPicker = null;
          }

          if (document.getElementById('import-cc-periode')) {
            importCCPicker = createMonthPicker('import-cc-periode', 'import-cc-month', 'import-cc-year');
          }

          if (document.getElementById('import-am-periode')) {
            importAMPicker = createMonthPicker('import-am-periode', 'import-am-month', 'import-am-year');
          }
        }, 100);
      });

      importModal.addEventListener('hidden.bs.modal', function() {
        if (importCCPicker) {
          importCCPicker.destroy();
          importCCPicker = null;
        }
        if (importAMPicker) {
          importAMPicker.destroy();
          importAMPicker = null;
        }

        document.querySelectorAll('.imp-panel form').forEach(form => {
          form.reset();
        });
      });
    }
  })();

  // ========================================
  // ✅ FIX #1: BUILD SEGMENT DROPDOWN UI + INTERACTIONS
  // ========================================
  function buildSegmentUI(segments) {
    const nativeSelect = document.getElementById('filter-segment');
    const segTabs = document.getElementById('segTabs');
    const segPanels = document.getElementById('segPanels');

    if (!nativeSelect || !segTabs || !segPanels) return;

    // Clear existing content
    segTabs.innerHTML = '';
    segPanels.innerHTML = '';

    // Group segments by divisi
    const groupedSegments = {};
    segments.forEach(segment => {
      const raw = (segment.divisi_kode || segment.divisi || '').toString().trim().toUpperCase();
      const divisiKode = raw || 'OTHER';

      if (!groupedSegments[divisiKode]) groupedSegments[divisiKode] = [];
      groupedSegments[divisiKode].push(segment);

      // Add to native select
      const option = document.createElement('option');
      option.value = segment.id;
      option.textContent = segment.lsegment_ho;
      nativeSelect.appendChild(option);
    });

    // Define tab order
    const ORDER = ['DPS', 'DSS', 'DGS', 'DES'];
    const keys = Object.keys(groupedSegments);
    const mainDivisi = keys.filter(k => k && k.toUpperCase() !== 'OTHER');
    const divisiList = [
      ...ORDER.filter(code => mainDivisi.includes(code)),
      ...mainDivisi.filter(code => !ORDER.includes(code)).sort()
    ];

    let firstTab = true;
    let firstDivisiName = null;

    // Handle case where only OTHER exists
    if (divisiList.length === 0 && groupedSegments['OTHER']?.length) {
      divisiList.push('SEGMENT');
      groupedSegments['SEGMENT'] = [];
    }

    // Build tabs and panels
    divisiList.forEach(divisi => {
      if (firstTab) firstDivisiName = divisi;

      // Create tab button
      const tabBtn = document.createElement('button');
      tabBtn.className = `seg-tab${firstTab ? ' active' : ''}`;
      tabBtn.dataset.tab = divisi;
      tabBtn.setAttribute('role', 'tab');
      tabBtn.setAttribute('aria-selected', firstTab ? 'true' : 'false');
      tabBtn.textContent = divisi;
      segTabs.appendChild(tabBtn);

      // Create panel
      const panel = document.createElement('div');
      panel.className = `seg-panel${firstTab ? ' active' : ''}`;
      panel.dataset.panel = divisi;
      panel.setAttribute('role', 'tabpanel');

      // "Semua Segment" option
      const allOption = document.createElement('button');
      allOption.className = 'seg-option all';
      allOption.dataset.value = 'all';
      allOption.textContent = 'Semua Segment';
      panel.appendChild(allOption);

      // Add segment options for this divisi
      (groupedSegments[divisi] || []).forEach(segment => {
        const optionBtn = document.createElement('button');
        optionBtn.className = 'seg-option';
        optionBtn.dataset.value = segment.id;
        optionBtn.textContent = segment.lsegment_ho;
        panel.appendChild(optionBtn);
      });

      segPanels.appendChild(panel);
      firstTab = false;
    });

    // Insert OTHER items into first panel (without creating OTHER tab)
    const otherItems = groupedSegments['OTHER'];
    if (firstDivisiName && Array.isArray(otherItems) && otherItems.length) {
      const firstPanel = segPanels.querySelector(`.seg-panel[data-panel="${firstDivisiName}"]`);
      if (firstPanel) {
        otherItems.forEach(segment => {
          const optionBtn = document.createElement('button');
          optionBtn.className = 'seg-option';
          optionBtn.dataset.value = segment.id;
          optionBtn.textContent = segment.lsegment_ho;
          firstPanel.appendChild(optionBtn);
        });
      }
    }

    // Initialize interactions
    initSegmentSelectInteractions();
  }

  function initSegmentSelectInteractions() {
    const segSelect = document.getElementById('segSelect');
    if (!segSelect) return;

    const nativeSelect = document.getElementById('filter-segment');
    const triggerBtn = segSelect.querySelector('.seg-select__btn');
    const labelSpan = segSelect.querySelector('.seg-select__label');

    // Get elements after UI is built
    const segTabs = segSelect.querySelectorAll('.seg-tab');
    const segPanels = segSelect.querySelectorAll('.seg-panel');
    const segOptions = segSelect.querySelectorAll('.seg-option');

    // Toggle menu
    triggerBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      segSelect.classList.toggle('open');
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!segSelect.contains(e.target)) {
        segSelect.classList.remove('open');
      }
    });

    // Tab switching
    segTabs.forEach(tab => {
      tab.addEventListener('click', (e) => {
        e.stopPropagation();
        const targetPanel = tab.dataset.tab;

        segTabs.forEach(t => {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');

        segPanels.forEach(panel => panel.classList.remove('active'));
        const activePanel = segSelect.querySelector(`.seg-panel[data-panel="${targetPanel}"]`);
        if (activePanel) activePanel.classList.add('active');
      });
    });

    // Option selection
    segOptions.forEach(option => {
      option.addEventListener('click', () => {
        const value = option.dataset.value;
        const label = option.textContent.trim();

        // Update native select
        nativeSelect.value = value;
        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

        // Update label
        labelSpan.textContent = label;

        // Update visual state
        segOptions.forEach(opt => opt.removeAttribute('aria-selected'));
        option.setAttribute('aria-selected', 'true');

        // Apply styling based on selection
        if (value === 'all') {
          segSelect.classList.add('is-all-selected');
          segSelect.classList.remove('has-value');
        } else {
          segSelect.classList.remove('is-all-selected');
          segSelect.classList.add('has-value');
        }

        // Close dropdown
        setTimeout(() => segSelect.classList.remove('open'), 150);
      });
    });
  }

  // ========================================
  // CUSTOM SELECT ENHANCEMENT
  // ========================================
  function enhanceNativeSelect(native, opts = {}) {
    if (!native || native.dataset.enhanced === '1') return;

    const inModal = opts.inModal || false;
    const wrap = document.createElement('div');
    wrap.className = 'cselect';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'cselect__btn';
    btn.setAttribute('aria-haspopup','listbox');

    const selectedOpt = native.options[native.selectedIndex];
    const labelSpan = document.createElement('span');
    labelSpan.className = 'cselect__label';
    labelSpan.textContent = selectedOpt ? selectedOpt.textContent.trim() : '';
    btn.appendChild(labelSpan);

    const arrow = document.createElement('span');
    arrow.className = 'cselect__arrow';
    arrow.innerHTML = '▼';
    btn.appendChild(arrow);

    const menu = document.createElement('div');
    menu.className = 'cselect__menu';
    menu.setAttribute('role','listbox');

    const list = document.createElement('div');
    list.className = 'cselect__list';

    Array.from(native.options).forEach((opt, idx) => {
      const item = document.createElement('div');
      item.className = 'cselect__option' + (idx === 0 ? ' is-all' : '');
      item.setAttribute('role','option');
      item.dataset.value = opt.value;
      item.textContent = opt.textContent.trim();
      if (opt.selected) item.setAttribute('aria-selected','true');

      item.addEventListener('click', () => {
        native.value = opt.value;
        native.dispatchEvent(new Event('change', { bubbles: true }));

        btn.querySelector('.cselect__label').textContent = opt.textContent.trim();
        list.querySelectorAll('.cselect__option[aria-selected]')
            .forEach(el => el.removeAttribute('aria-selected'));
        item.setAttribute('aria-selected','true');

        wrap.classList.remove('is-open');
      });

      list.appendChild(item);
    });

    menu.appendChild(list);

    native.insertAdjacentElement('afterend', wrap);
    wrap.appendChild(btn);
    wrap.appendChild(menu);

    if (inModal) {
      native.classList.add('visually-hidden-cselect');
    } else {
      native.style.position = 'absolute';
      native.style.inset = '0 auto auto 0';
      native.style.width = '1px';
      native.style.height = '1px';
      native.style.opacity = '0';
      native.style.pointerEvents = 'none';
    }

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (wrap.classList.contains('is-disabled')) return;
      wrap.classList.toggle('is-open');
    });
    document.addEventListener('click', (e) => {
      if (!wrap.contains(e.target)) wrap.classList.remove('is-open');
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') wrap.classList.remove('is-open');
    });

    native.addEventListener('change', () => {
      const v = native.value;
      const found = list.querySelector(`.cselect__option[data-value="${CSS.escape(v)}"]`);
      if (found) {
        btn.querySelector('.cselect__label').textContent = found.textContent;
        list.querySelectorAll('.cselect__option[aria-selected]').forEach(el => el.removeAttribute('aria-selected'));
        found.setAttribute('aria-selected','true');
      }
    });

    native.dataset.enhanced = '1';
  }

  function enhanceFilterBar(){
    const selects = document.querySelectorAll('.filters .filter-group:nth-of-type(-n+2) .form-select');
    selects.forEach(sel => enhanceNativeSelect(sel, { inModal: false }));
  }

  function enhanceModalDivisi(){
    const selModal = document.querySelector('#imp-rev-cc .filter-group .form-select');
    if (selModal) enhanceNativeSelect(selModal, { inModal: true });

    const modalEl = document.getElementById('importModal');
    if (modalEl) {
      modalEl.addEventListener('shown.bs.modal', () => {
        const sel = document.querySelector('#imp-rev-cc .filter-group .form-select');
        if (sel && sel.dataset.enhanced !== '1') {
          enhanceNativeSelect(sel, { inModal: true });
        }
      });
    }
  }

  // ========================================
  // ✨ DIVISI BUTTON GROUP HANDLER
  // ========================================
  function initDivisiButtonGroup() {
    const buttonGroup = document.getElementById('divisiButtonGroup');
    const hiddenContainer = document.getElementById('divisiHiddenInputs');

    if (!buttonGroup || !hiddenContainer) return;

    buttonGroup.innerHTML = '';
    hiddenContainer.innerHTML = '';

    allDivisiData.forEach(divisi => {
      const btn = document.createElement('button');
      btn.type = 'button';
      const kodeRingkas = divisi.kode.substring(0, 3).toUpperCase();
      btn.className = `divisi-toggle-btn ${kodeRingkas.toLowerCase()}`;
      btn.dataset.divisiId = divisi.id;
      btn.dataset.divisiKode = divisi.kode;
      btn.textContent = kodeRingkas;

      btn.addEventListener('click', function() {
        this.classList.toggle('active');
        updateHiddenInputs();
      });

      buttonGroup.appendChild(btn);
    });
  }

  function updateHiddenInputs() {
    const hiddenContainer = document.getElementById('divisiHiddenInputs');
    const activeButtons = document.querySelectorAll('.divisi-toggle-btn.active');

    hiddenContainer.innerHTML = '';

    activeButtons.forEach(btn => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'divisi_ids[]';
      input.value = btn.dataset.divisiId;
      hiddenContainer.appendChild(input);
    });
  }

  function setSelectedDivisi(divisiIds) {
    document.querySelectorAll('.divisi-toggle-btn').forEach(btn => {
      btn.classList.remove('active');
    });

    divisiIds.forEach(id => {
      const btn = document.querySelector(`.divisi-toggle-btn[data-divisi-id="${id}"]`);
      if (btn) {
        btn.classList.add('active');
      }
    });

    updateHiddenInputs();
  }

  // ========================================
  // CHECKBOX & BULK DELETE LOGIC
  // ========================================
  $('#selectAllCC').on('change', function() {
    $('.row-checkbox-cc').prop('checked', this.checked);
    updateBulkDeleteButton('CC');
  });

  $('#selectAllAM').on('change', function() {
    $('.row-checkbox-am').prop('checked', this.checked);
    updateBulkDeleteButton('AM');
  });

  $('#selectAllDataAM').on('change', function() {
    $('.row-checkbox-data-am').prop('checked', this.checked);
    updateBulkDeleteButton('DataAM');
  });

  $('#selectAllDataCC').on('change', function() {
    $('.row-checkbox-data-cc').prop('checked', this.checked);
    updateBulkDeleteButton('DataCC');
  });

  $(document).on('change', '.row-checkbox-cc, .row-checkbox-am, .row-checkbox-data-am, .row-checkbox-data-cc', function() {
    const type = $(this).hasClass('row-checkbox-cc') ? 'CC' :
                 $(this).hasClass('row-checkbox-am') ? 'AM' :
                 $(this).hasClass('row-checkbox-data-am') ? 'DataAM' : 'DataCC';
    updateBulkDeleteButton(type);
  });

  function updateBulkDeleteButton(type) {
    const checked = $(`.row-checkbox-${type === 'DataAM' ? 'data-am' : type === 'DataCC' ? 'data-cc' : type.toLowerCase()}:checked`).length;
    const btn = $(`#btnDeleteSelected${type}`);

    if (checked > 0) {
      btn.prop('disabled', false).html(`<i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih (${checked})`);
    } else {
      btn.prop('disabled', true).html('<i class="fa-solid fa-trash-can me-2"></i>Hapus Terpilih');
    }
  }

  $('#btnDeleteSelectedCC').click(function() {
    bulkDeleteSelected('cc-revenue', 'Revenue CC');
  });

  $('#btnDeleteSelectedAM').click(function() {
    bulkDeleteSelected('am-revenue', 'Revenue AM');
  });

  $('#btnDeleteSelectedDataAM').click(function() {
    bulkDeleteSelected('data-am', 'Data AM');
  });

  $('#btnDeleteSelectedDataCC').click(function() {
    bulkDeleteSelected('data-cc', 'Data CC');
  });

  function bulkDeleteSelected(endpoint, name) {
    const checkboxClass = endpoint === 'cc-revenue' ? '.row-checkbox-cc' :
                          endpoint === 'am-revenue' ? '.row-checkbox-am' :
                          endpoint === 'data-am' ? '.row-checkbox-data-am' : '.row-checkbox-data-cc';

    const ids = $(checkboxClass + ':checked').map(function() {
      return $(this).data('id');
    }).get();

    if (ids.length === 0) {
      alert('Pilih minimal 1 data untuk dihapus');
      return;
    }

    if (!confirm(`Hapus ${ids.length} ${name} terpilih?\n\nTindakan ini tidak dapat dibatalkan!`)) {
      return;
    }

    $.ajax({
      url: `/revenue-data/bulk-delete-${endpoint}`,
      method: 'POST',
      data: JSON.stringify({ ids: ids }),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  $('#btnBulkDeleteCC').click(function() {
    bulkDeleteAll('cc-revenue', 'Revenue CC');
  });

  $('#btnBulkDeleteAM').click(function() {
    bulkDeleteAll('am-revenue', 'Revenue AM');
  });

  $('#btnBulkDeleteDataAM').click(function() {
    bulkDeleteAll('data-am', 'Data AM');
  });

  $('#btnBulkDeleteDataCC').click(function() {
    bulkDeleteAll('data-cc', 'Data CC');
  });

  function bulkDeleteAll(endpoint, name) {
    if (!confirm(`Hapus SEMUA ${name}?\n\nTindakan ini tidak dapat dibatalkan!`)) {
      return;
    }

    $.ajax({
      url: `/revenue-data/bulk-delete-all-${endpoint}`,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  // ========================================
  // LOAD FILTER OPTIONS FROM BACKEND
  // ========================================
  function loadFilterOptions() {
    $.ajax({
      url: '{{ route("revenue.api.filter.options") }}',
      method: 'GET',
      success: function(response) {
        const witelSelect = $('#filterWitel');
        response.witels.forEach(function(witel) {
          witelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        const divisiSelect = $('#filterDivisi');
        const divisiImport = $('#divisiImport');
        response.divisions.forEach(function(divisi) {
          divisiSelect.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
          divisiImport.append(`<option value="${divisi.id}">${divisi.nama}</option>`);
        });

        // Store globally for modals
        allDivisiData = response.divisions;
        initDivisiButtonGroup();

        // ✅ FIX #1: Build Segment UI and init interactions
        if (response.segments && response.segments.length > 0) {
          buildSegmentUI(response.segments);
        }

        // Store TELDA data globally
        if (response.teldas) {
          allTeldaData = response.teldas;
        }

        // Populate edit modal selects
        const editWitelSelect = $('#editDataAMWitel');
        editWitelSelect.empty();
        editWitelSelect.append('<option value="">Pilih Witel</option>');
        response.witels.forEach(function(witel) {
          editWitelSelect.append(`<option value="${witel.id}">${witel.nama}</option>`);
        });

        const editTeldaSelect = $('#editDataAMTelda');
        editTeldaSelect.empty();
        editTeldaSelect.append('<option value="">Pilih TELDA</option>');
        if (response.teldas) {
          response.teldas.forEach(function(telda) {
            editTeldaSelect.append(`<option value="${telda.id}">${telda.nama}</option>`);
          });
        }

        enhanceFilterBar();
        enhanceModalDivisi();
      },
      error: function(xhr) {
        console.error('Error loading filters:', xhr);
      }
    });
  }

  // ========================================
  // LOAD DATA FROM BACKEND
  // ========================================
  function loadData() {
    let url = '';
    const params = {
      page: currentPage,
      per_page: perPage,
      search: currentFilters.search,
      witel_id: currentFilters.witel_id,
      divisi_id: currentFilters.divisi_id,
      segment_id: currentFilters.segment_id
    };

    if (currentTab === 'tab-cc-revenue') {
      url = '{{ route("revenue.api.cc") }}';
      params.periode = currentFilters.periode;
      params.tipe_revenue = currentFilters.tipe_revenue;
    } else if (currentTab === 'tab-am-revenue') {
      url = '{{ route("revenue.api.am") }}';
      params.periode = currentFilters.periode;
      params.role = currentFilters.role;
    } else if (currentTab === 'tab-data-am') {
      url = '{{ route("revenue.api.data.am") }}';
      params.role = currentFilters.role;
    } else if (currentTab === 'tab-data-cc') {
      url = '{{ route("revenue.api.data.cc") }}';
    }

    $.ajax({
      url: url,
      method: 'GET',
      data: params,
      success: function(response) {
        console.log('✅ Data loaded for tab:', currentTab, response);

        if (currentTab === 'tab-cc-revenue') {
          renderRevenueCC(response);
        } else if (currentTab === 'tab-am-revenue') {
          renderRevenueAM(response);
        } else if (currentTab === 'tab-data-am') {
          renderDataAM(response);
        } else if (currentTab === 'tab-data-cc') {
          renderDataCC(response);
        }

        renderPagination(response);
        updateBadge(currentTab, response.total || 0);

        $('[data-bs-toggle="tooltip"]').tooltip();
      },
      error: function(xhr) {
        console.error('❌ Error loading data for tab:', currentTab, xhr);
        showAlert('Gagal memuat data: ' + (xhr.responseJSON?.message || xhr.statusText), 'danger');
      }
    });
  }

  // ========================================
  // RENDER FUNCTIONS
  // ========================================
  function renderRevenueCC(response) {
    const tbody = $('#tableRevenueCC');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const divisiKode = item.divisi_kode || item.divisi || '-';
      const divisiDisplay = divisiKode !== '-' ? divisiKode.substring(0, 3).toUpperCase() : '-';
      const nipnas = item.nipnas || '-';
      const divisiClass = divisiDisplay !== '-' ? `badge-div ${divisiDisplay.toLowerCase()}` : '';

      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-cc" data-id="${item.id}"></td>
          <td>
            <strong style="font-size: 1rem; font-weight: 700;">${item.nama_cc}</strong><br>
            <small class="text-muted" style="font-size: 0.85rem;">
              ${divisiDisplay !== '-' ? `<span class="${divisiClass}">${divisiDisplay}</span> | ` : ''}${nipnas}
            </small>
          </td>
          <td>${item.segment || '-'}</td>
          <td class="text-end">${formatCurrency(item.target_revenue)}</td>
          <td class="text-end">
            <span data-bs-toggle="tooltip" title="${item.revenue_type || ''}">
              ${formatCurrency(item.real_revenue)}
            </span>
          </td>
          <td>${item.bulan_display}</td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editRevenueCC(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteRevenueCC(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });

    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function renderRevenueAM(response) {
    const tbody = $('#tableRevenueAM');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const role = item.role || 'AM';
      const roleClass = role === 'HOTDA' ? 'badge-role-hotda' : 'badge-role-am';
      const divisiKode = item.divisi_kode || item.divisi || '-';
      const divisiDisplay = divisiKode !== '-' ? divisiKode.substring(0, 3).toUpperCase() : '-';
      const divisiClass = divisiDisplay !== '-' ? `badge-div ${divisiDisplay.toLowerCase()}` : '';

      // ✅ FIX #2: TELDA display - show "-" for AM, actual value for HOTDA
      const teldaDisplay = role === 'HOTDA' ? (item.telda_nama || '-') : '-';
      const achievementPercent = item.achievement ? parseFloat(item.achievement).toFixed(2) : '0.00';

      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-am" data-id="${item.id}"></td>
          <td>
            <strong style="font-size: 1rem; font-weight: 700;">${item.nama_am}</strong><br>
            <small>
              <span class="${roleClass}">${role}</span>
              ${divisiDisplay !== '-' ? `<span class="${divisiClass}" style="margin-left: 4px;">${divisiDisplay}</span>` : ''}
            </small>
          </td>
          <td>${item.nama_cc}</td>
          <td class="text-end">${formatCurrency(item.target_revenue)}</td>
          <td class="text-end">${formatCurrency(item.real_revenue)}</td>
          <td class="text-end">${achievementPercent}%</td>
          <td>${item.bulan_display}</td>
          <td class="telda-col">${teldaDisplay}</td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editRevenueAM(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteRevenueAM(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });

    $('[data-bs-toggle="tooltip"]').tooltip();
  }

  function renderDataAM(response) {
    const tbody = $('#tableDataAM');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const roleClass = item.role === 'HOTDA' ? 'badge-role-hotda' : 'badge-role-am';
      const statusClass = item.is_registered ? 'badge-status-registered' : 'badge-status-not-registered';
      const statusText = item.is_registered ? 'Terdaftar' : 'Belum Terdaftar';
      const teldaDisplay = item.role === 'HOTDA' ? (item.telda_nama || '-') : '-';

      let divisiBadges = '';
      if (item.divisi && item.divisi.length > 0) {
        divisiBadges = '<br>';
        item.divisi.forEach((div) => {
          const kodeRingkas = div.kode.substring(0, 3).toUpperCase();
          divisiBadges += `<span class="badge-div ${kodeRingkas.toLowerCase()}">${kodeRingkas}</span> `;
        });
      }

      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-data-am" data-id="${item.id}"></td>
          <td>
            <strong style="font-size: 1rem; font-weight: 700;">${item.nama}</strong><br>
            <small class="text-muted">${item.nik}</small>
            ${divisiBadges}
          </td>
          <td>${item.witel_nama}</td>
          <td><span class="${roleClass}">${item.role}</span></td>
          <td>${teldaDisplay}</td>
          <td><span class="${statusClass}">${statusText}</span></td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editDataAM(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteDataAM(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderDataCC(response) {
    const tbody = $('#tableDataCC');
    tbody.empty();

    if (!response || !response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>');
      return;
    }

    response.data.forEach(function(item) {
      const row = `
        <tr>
          <td><input type="checkbox" class="row-checkbox-data-cc" data-id="${item.id}"></td>
          <td>${item.nama}</td>
          <td>${item.nipnas}</td>
          <td class="text-center">
            <div class="action-buttons">
              <button class="btn btn-sm btn-warning" onclick="editDataCC(${item.id})" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteDataCC(${item.id})" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  // ========================================
  // PAGINATION
  // ========================================
  function renderPagination(response) {
    const container = currentTab === 'tab-cc-revenue' ? $('#paginationRevenueCC') :
                      currentTab === 'tab-am-revenue' ? $('#paginationRevenueAM') :
                      currentTab === 'tab-data-am' ? $('#paginationDataAM') : $('#paginationDataCC');

    container.empty();

    const from = response.from || 0;
    const to = response.to || 0;
    const total = response.total || 0;
    const currentPageNum = response.current_page || 1;
    const lastPage = response.last_page || 1;

    const info = `<div class="info">Menampilkan ${from}-${to} dari ${total} hasil</div>`;

    let pages = '<div class="pages">';
    if (currentPageNum > 1) {
      pages += `<button class="pager" data-page="${currentPageNum - 1}">‹</button>`;
    }

    const startPage = Math.max(1, currentPageNum - 2);
    const endPage = Math.min(lastPage, currentPageNum + 2);

    for (let i = startPage; i <= endPage; i++) {
      const activeClass = i === currentPageNum ? 'active' : '';
      pages += `<button class="pager ${activeClass}" data-page="${i}">${i}</button>`;
    }

    if (currentPageNum < lastPage) {
      pages += `<button class="pager" data-page="${currentPageNum + 1}">›</button>`;
    }
    pages += '</div>';

    const perPageSelect = `
      <div class="perpage">
        <label>Baris</label>
        <select class="form-select small" id="perPageSelect">
          <option value="25" ${perPage === 25 ? 'selected' : ''}>25</option>
          <option value="50" ${perPage === 50 ? 'selected' : ''}>50</option>
          <option value="75" ${perPage === 75 ? 'selected' : ''}>75</option>
          <option value="100" ${perPage === 100 ? 'selected' : ''}>100</option>
        </select>
      </div>
    `;

    container.append(info + pages + perPageSelect);

    container.find('.pager[data-page]').click(function() {
      const page = parseInt($(this).data('page'));
      if (page > 0 && page <= response.last_page && page !== currentPageNum) {
        currentPage = page;
        loadData();
      }
    });

    container.find('#perPageSelect').change(function() {
      perPage = parseInt($(this).val());
      currentPage = 1;
      loadData();
    });
  }

  // ========================================
  // UPDATE BADGE COUNTER
  // ========================================
  function updateBadge(tabId, count) {
    const badgeMapping = {
      'tab-cc-revenue': 'badge-cc-rev',
      'tab-am-revenue': 'badge-am-rev',
      'tab-data-am': 'badge-data-am',
      'tab-data-cc': 'badge-cc'
    };

    const badgeId = badgeMapping[tabId];
    if (badgeId) {
      $('#' + badgeId).text(count);
    }
  }

  // ========================================
  // TAB SWITCHING
  // ========================================
  $('.tab-btn').click(function() {
    const tabId = $(this).data('tab');
    switchTab(tabId);
  });

  function switchTab(tabId) {
    $('.tab-btn').removeClass('active');
    $(`.tab-btn[data-tab="${tabId}"]`).addClass('active');
    $('.tab-panel').removeClass('active');
    $(`#${tabId}`).addClass('active');

    currentTab = tabId;
    currentPage = 1;

    if (tabId === 'tab-cc-revenue' || tabId === 'tab-am-revenue') {
      $('#filterPeriodeGroup').show();
    } else {
      $('#filterPeriodeGroup').hide();
    }

    loadData();
  }

  // ========================================
  // FILTER HANDLERS
  // ========================================
  $('#searchForm').submit(function(e) {
    e.preventDefault();
    currentFilters.search = $('#searchInput').val();
    currentPage = 1;
    loadData();
  });

  $('#btn-apply-filter').click(function() {
    currentFilters.witel_id = $('#filterWitel').val();
    currentFilters.divisi_id = $('#filterDivisi').val();
    currentFilters.segment_id = $('#filter-segment').val();
    currentPage = 1;
    loadData();
  });

  $('#btn-reset-filter').click(function() {
    $('#searchInput').val('');
    $('#filterWitel').val('all');
    $('#filterDivisi').val('all');
    $('#filter-segment').val('all');
    $('#filter-date').value = '';

    currentFilters = {
      search: '',
      witel_id: 'all',
      divisi_id: 'all',
      segment_id: 'all',
      periode: '',
      tipe_revenue: 'REGULER',
      role: 'all'
    };

    currentPage = 1;
    loadData();
  });

  $('.seg-btn[data-revtype]').click(function() {
    $('.seg-btn[data-revtype]').removeClass('active');
    $(this).addClass('active');
    currentFilters.tipe_revenue = $(this).data('revtype');
    currentPage = 1;
    loadData();
  });

  // ✅ FIX #2: AM Mode Toggle with TELDA Column Visibility
  $('.am-btn[data-mode]').click(function() {
    $('.am-btn[data-mode]').removeClass('active');
    $(this).addClass('active');
    const mode = $(this).data('mode');
    currentFilters.role = mode;

    // ✅ TELDA column always visible (data will show "-" for AM role)
    // No need to hide/show column anymore

    currentPage = 1;
    loadData();
  });

  // ========================================
  // ✅ FIXED: 2-STEP IMPORT WITH PREVIEW
  // ========================================

  // Type selector
  $('.type-btn').click(function() {
    $('.type-btn').removeClass('active');
    $(this).addClass('active');

    $('.imp-panel').removeClass('active');
    const target = $(this).data('imp');
    $(`#${target}`).addClass('active');
  });

  // Form submissions
  $('#formDataCC, #formDataAM').submit(function(e) {
    e.preventDefault();
    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    console.log('📤 Submitting', currentImportType);
    handleImportPreview(currentFormData, currentImportType);
  });

  $('#formRevenueCC').submit(function(e) {
    e.preventDefault();

    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    const year = $('#import-cc-year').val();
    const month = $('#import-cc-month').val();
    const divisi = $('#divisiImport').val();
    const jenisData = $('select[name="jenis_data"]', $(this)).val();

    if (!year || !month) {
      alert('❌ Pilih Periode terlebih dahulu!');
      return;
    }

    if (!divisi) {
      alert('❌ Pilih Divisi terlebih dahulu!');
      return;
    }

    if (!jenisData) {
      alert('❌ Pilih Jenis Data (Revenue/Target) terlebih dahulu!');
      return;
    }

    currentFormData.set('year', year);
    currentFormData.set('month', month);

    console.log('📤 Submitting Revenue CC with:', {
      year: year,
      month: month,
      divisi_id: divisi,
      jenis_data: jenisData,
      file: currentFormData.get('file')?.name
    });

    handleImportPreview(currentFormData, currentImportType);
  });

  $('#formRevenueAM').submit(function(e) {
    e.preventDefault();

    currentFormData = new FormData($(this)[0]);
    currentImportType = currentFormData.get('import_type');

    const year = $('#import-am-year').val();
    const month = $('#import-am-month').val();

    if (!year || !month) {
      alert('❌ Pilih Periode terlebih dahulu!');
      return;
    }

    currentFormData.set('year', year);
    currentFormData.set('month', month);

    console.log('📤 Submitting Revenue AM with:', {
      year: year,
      month: month,
      file: currentFormData.get('file')?.name
    });

    handleImportPreview(currentFormData, currentImportType);
  });

  function handleImportPreview(formData, importType) {
    console.log('📤 Sending to /import/preview:');
    for (let [key, value] of formData.entries()) {
      if (value instanceof File) {
        console.log(`  ${key}: ${value.name} (${value.size} bytes)`);
      } else {
        console.log(`  ${key}: ${value}`);
      }
    }

    showLoading('Memproses file...');

    $.ajax({
      url: '/revenue-data/import/preview',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        hideLoading();

        if (response.success) {
          previewData = response.data;
          currentSessionId = response.session_id;
          console.log('✅ Preview loaded, session_id:', currentSessionId);

          showPreviewModal(previewData, importType);

          bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        hideLoading();
        console.error('❌ Preview failed:', xhr.responseJSON);
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  function showPreviewModal(data, importType) {
    const summaryHTML = `
      <div class="preview-card new">
        <div class="icon"><i class="fa-solid fa-plus"></i></div>
        <h3>${data.summary.new_count || 0}</h3>
        <p>Data Baru</p>
      </div>
      <div class="preview-card update">
        <div class="icon"><i class="fa-solid fa-edit"></i></div>
        <h3>${data.summary.update_count || 0}</h3>
        <p>Akan Di-update</p>
      </div>
      <div class="preview-card conflict">
        <div class="icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
        <h3>${data.summary.error_count || 0}</h3>
        <p>Error/Konflik</p>
      </div>
    `;
    $('#previewSummary').html(summaryHTML);

    const tableBody = $('#previewTableBody');
    tableBody.empty();

    data.rows.forEach((row, index) => {
      const statusClass = row.status || 'new';
      const statusText = {
        'new': 'Baru',
        'update': 'Update',
        'error': 'Error',
        'skip': 'Skip'
      }[statusClass] || 'Baru';

      let dataDisplay = '';
      let valueDisplay = '';

      if (importType === 'data_cc') {
        dataDisplay = `<strong>${row.data.NIPNAS || '-'}</strong><br><small>${row.data.STANDARD_NAME || '-'}</small>`;
        valueDisplay = row.old_data ? `
          <div class="value-comparison">
            <span class="value-old">${row.old_data.nama || '-'}</span>
            <span class="value-new">${row.data.STANDARD_NAME || '-'}</span>
          </div>
        ` : row.data.STANDARD_NAME || '-';
      } else if (importType === 'data_am') {
        dataDisplay = `<strong>${row.data.NIK || '-'}</strong><br><small>${row.data.NAMA_AM || '-'}</small>`;
        valueDisplay = `${row.data.ROLE || '-'} | ${row.data.WITEL || '-'}`;
      } else if (importType === 'revenue_cc') {
        dataDisplay = `<strong>${row.data.NIPNAS || '-'}</strong><br><small>${row.data.LSEGMENT_HO || '-'}</small>`;
        valueDisplay = row.data.REVENUE_SOLD ? `Rp ${parseFloat(row.data.REVENUE_SOLD).toLocaleString('id-ID')}` :
                       row.data.REVENUE_BILL ? `Rp ${parseFloat(row.data.REVENUE_BILL).toLocaleString('id-ID')}` : '-';
      } else if (importType === 'revenue_am') {
        dataDisplay = `<strong>${row.data.NIK_AM || '-'}</strong><br><small>${row.data.NIPNAS || '-'}</small>`;
        valueDisplay = `${row.data.PROPORSI || 0}%`;
      }

      const rowHTML = `
        <tr data-row-index="${index}" data-status="${statusClass}" class="${statusClass === 'error' ? 'table-danger' : ''}">
          <td>
            <input type="checkbox" class="preview-row-checkbox"
                   data-index="${index}"
                   ${statusClass !== 'error' ? 'checked' : ''}
                   ${statusClass === 'error' ? 'disabled' : ''}>
          </td>
          <td><span class="status-badge ${statusClass}">${statusText}</span></td>
          <td>${dataDisplay}</td>
          <td>
            ${valueDisplay}
            ${row.error ? `<br><small class="text-danger"><i class="fa-solid fa-warning me-1"></i>${row.error}</small>` : ''}
          </td>
        </tr>
      `;
      tableBody.append(rowHTML);
    });

    updateSelectedCount();

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
  }

  $('#selectAllPreview').on('change', function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', this.checked);
    updateSelectedCount();
  });

  $(document).on('change', '.preview-row-checkbox', function() {
    updateSelectedCount();
  });

  $('#btnSelectAll').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', true);
    $('#selectAllPreview').prop('checked', true);
    updateSelectedCount();
  });

  $('#btnDeselectAll').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', false);
    $('#selectAllPreview').prop('checked', false);
    updateSelectedCount();
  });

  $('#btnSelectNew').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', false);
    $('tr[data-status="new"] .preview-row-checkbox').prop('checked', true);
    updateSelectedCount();
  });

  $('#btnSelectUpdates').click(function() {
    $('.preview-row-checkbox:not(:disabled)').prop('checked', false);
    $('tr[data-status="update"] .preview-row-checkbox').prop('checked', true);
    updateSelectedCount();
  });

  function updateSelectedCount() {
    const count = $('.preview-row-checkbox:checked').length;
    $('#selectedCount').text(count);
    $('#btnExecuteImport').prop('disabled', count === 0);
  }

  $('#btnExecuteImport').click(function() {
    const selectedIndexes = $('.preview-row-checkbox:checked').map(function() {
      return $(this).data('index');
    }).get();

    if (selectedIndexes.length === 0) {
      alert('Pilih minimal 1 data untuk diimport');
      return;
    }

    if (!confirm(`Import ${selectedIndexes.length} data terpilih?`)) {
      return;
    }

    executeImport(selectedIndexes);
  });

  function executeImport(selectedIndexes) {
    showLoading('Mengimport data...');

    const payload = {
      session_id: currentSessionId,
      selected_rows: selectedIndexes,
      import_type: currentImportType
    };

    console.log('✅ Executing import with payload:', payload);

    $.ajax({
      url: '/revenue-data/import/execute',
      method: 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        hideLoading();

        if (response.success) {
          console.log('✅ Import executed successfully');

          bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();

          showImportResult(response);

          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        hideLoading();
        console.error('❌ Import execution failed:', xhr);
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  function showLoading(text) {
    $('#loadingText').text(text || 'Memproses...');
    $('#loadingOverlay').addClass('active');
  }

  function hideLoading() {
    $('#loadingOverlay').removeClass('active');
  }

  function showImportResult(response) {
    const stats = response.statistics || {
      total_rows: 0,
      success_count: 0,
      failed_count: 0,
      skipped_count: 0
    };

    const totalRows = stats.total_rows || 0;
    const successCount = stats.success_count || 0;
    const failedCount = stats.failed_count || 0;
    const skippedCount = stats.skipped_count || 0;
    const updatedCount = stats.updated_count || 0;
    const recalculatedCount = stats.recalculated_am_count || 0;

    const successRate = totalRows > 0 ? ((successCount / totalRows) * 100).toFixed(1) : 0;

    let content = `
      <div class="result-modal-stats-container four-cols">
        <div class="result-modal-stat">
          <div class="icon info">
            <i class="fa-solid fa-file-lines"></i>
          </div>
          <div class="content">
            <h4>${totalRows}</h4>
            <p>Total Baris</p>
          </div>
        </div>

        <div class="result-modal-stat">
          <div class="icon success">
            <i class="fa-solid fa-check"></i>
          </div>
          <div class="content">
            <h4>${successCount}</h4>
            <p>Berhasil</p>
          </div>
        </div>

        <div class="result-modal-stat">
          <div class="icon danger">
            <i class="fa-solid fa-xmark"></i>
          </div>
          <div class="content">
            <h4>${failedCount}</h4>
            <p>Gagal</p>
          </div>
        </div>

        <div class="result-modal-stat">
          <div class="icon warning">
            <i class="fa-solid fa-exclamation"></i>
          </div>
          <div class="content">
            <h4>${skippedCount}</h4>
            <p>Diskip</p>
          </div>
        </div>
      </div>

      <div class="progress-bar-custom">
        <div class="progress-bar-fill-custom" style="width: ${successRate}%">
          ${successRate}% Success
        </div>
      </div>
    `;

    if (updatedCount > 0 || recalculatedCount > 0) {
      content += `
        <div class="result-modal-info">
          <h6><i class="fa-solid fa-info-circle me-2"></i>Informasi Tambahan</h6>
          <ul>
            ${updatedCount > 0 ? `<li><strong>${updatedCount}</strong> data existing di-update</li>` : ''}
            ${recalculatedCount > 0 ? `<li><strong>${recalculatedCount}</strong> AM revenues recalculated</li>` : ''}
          </ul>
        </div>
      `;
    }

    if (response.errors && response.errors.length > 0) {
      content += `
        <div class="alert alert-warning mt-3">
          <strong><i class="fa-solid fa-triangle-exclamation me-2"></i>Detail Error:</strong>
          <ul class="mb-0 mt-2">
      `;
      response.errors.slice(0, 10).forEach(err => {
        content += `<li>${err}</li>`;
      });
      if (response.errors.length > 10) {
        content += `<li><em>... dan ${response.errors.length - 10} error lainnya</em></li>`;
      }
      content += `</ul></div>`;
    }

    if (response.error_log_path) {
      $('#btnDownloadErrorLog').attr('href', response.error_log_path).show();
    } else {
      $('#btnDownloadErrorLog').hide();
    }

    $('#resultModalBody').html(content);
    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
    modal.show();
  }

  // ========================================
  // EDIT & DELETE FUNCTIONS
  // ========================================

  window.editRevenueCC = function(id) {
    $.ajax({
      url: `/revenue-data/revenue-cc/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;
          $('#editCCRevenueId').val(data.id);
          $('#editCCNamaCC').val(data.nama_cc);
          $('#editCCTargetRevenue').val(data.target_revenue);
          $('#editCCRealRevenue').val(data.real_revenue);

          const modal = new bootstrap.Modal(document.getElementById('modalEditRevenueCC'));
          modal.show();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.deleteRevenueCC = function(id) {
    if (!confirm('Hapus Revenue CC ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/revenue-cc/${id}`,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.editRevenueAM = function(id) {
    $.ajax({
      url: `/revenue-data/revenue-am/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;
          $('#editAMRevenueId').val(data.id);
          $('#editAMNamaAM').val(data.nama_am);
          $('#editAMProporsi').val(data.proporsi);
          $('#editAMTargetRevenue').val(data.target_revenue);
          $('#editAMRealRevenue').val(data.real_revenue);

          const modal = new bootstrap.Modal(document.getElementById('modalEditRevenueAM'));
          modal.show();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.deleteRevenueAM = function(id) {
    if (!confirm('Hapus Revenue AM ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/revenue-am/${id}`,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  // ✅ FIX #3: IMPROVED Edit Data AM with Conditional Logic
  window.editDataAM = function(id) {
    $.ajax({
      url: `/revenue-data/data-am/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;

          // Set basic fields
          $('#editDataAMId').val(data.id);
          $('#changePasswordAMId').val(data.id);
          $('#editDataAMNama').val(data.nama);
          $('#editDataAMNik').val(data.nik);
          $('#editDataAMRole').val(data.role);
          $('#editDataAMWitel').val(data.witel_id);
          $('#editDataAMTelda').val(data.telda_id || '');

          // Set divisi button group
          const divisiIds = data.divisi.map(d => d.id);
          setSelectedDivisi(divisiIds);

          // ✅ Show/hide TELDA field based on role
          toggleTeldaField(data.role);

          // ✅ Show/hide tabs based on registration status
          const tabsNav = document.getElementById('editDataAMTabs');
          if (data.is_registered) {
            tabsNav.style.display = 'flex';
          } else {
            tabsNav.style.display = 'none';
          }

          // Show modal
          const modal = new bootstrap.Modal(document.getElementById('modalEditDataAM'));
          modal.show();

          // ✅ Ensure first tab is active
          setTimeout(() => {
            const firstTab = document.querySelector('#tab-edit-data-tab');
            if (firstTab && data.is_registered) {
              const bsTab = new bootstrap.Tab(firstTab);
              bsTab.show();
            }
          }, 100);
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  // ✅ Helper: Toggle TELDA field visibility
  function toggleTeldaField(role) {
    const teldaWrapper = document.getElementById('editDataAMTeldaWrapper');
    if (role === 'HOTDA') {
      teldaWrapper.classList.remove('hidden');
      teldaWrapper.style.display = 'block';
    } else {
      teldaWrapper.classList.add('hidden');
      teldaWrapper.style.display = 'none';
    }
  }

  // ✅ Event listener: Role change triggers TELDA visibility
  $('#editDataAMRole').on('change', function() {
    const role = $(this).val();
    toggleTeldaField(role);
  });

  window.deleteDataAM = function(id) {
    if (!confirm('Hapus Data AM ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/data-am/${id}`,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.editDataCC = function(id) {
    $.ajax({
      url: `/revenue-data/data-cc/${id}`,
      method: 'GET',
      success: function(response) {
        if (response.success) {
          const data = response.data;
          $('#editDataCCId').val(data.id);
          $('#editDataCCNama').val(data.nama);
          $('#editDataCCNipnas').val(data.nipnas);

          const modal = new bootstrap.Modal(document.getElementById('modalEditDataCC'));
          modal.show();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  window.deleteDataCC = function(id) {
    if (!confirm('Hapus Data CC ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
      return;
    }

    $.ajax({
      url: `/revenue-data/data-cc/${id}`,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  };

  // ========================================
  // FORM SUBMIT HANDLERS
  // ========================================

  $('#formEditRevenueCC').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editCCRevenueId').val();
    const data = {
      target_revenue: $('#editCCTargetRevenue').val(),
      real_revenue: $('#editCCRealRevenue').val()
    };

    $.ajax({
      url: `/revenue-data/revenue-cc/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditRevenueCC')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formEditRevenueAM').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editAMRevenueId').val();
    const data = {
      proporsi: $('#editAMProporsi').val(),
      target_revenue: $('#editAMTargetRevenue').val(),
      real_revenue: $('#editAMRealRevenue').val()
    };

    $.ajax({
      url: `/revenue-data/revenue-am/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditRevenueAM')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formEditDataAM').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editDataAMId').val();

    const selectedDivisi = [];
    $('#divisiHiddenInputs input[name="divisi_ids[]"]').each(function() {
      selectedDivisi.push($(this).val());
    });

    const data = {
      nama: $('#editDataAMNama').val(),
      nik: $('#editDataAMNik').val(),
      role: $('#editDataAMRole').val(),
      witel_id: $('#editDataAMWitel').val(),
      telda_id: $('#editDataAMTelda').val() || null,
      divisi_ids: selectedDivisi
    };

    $.ajax({
      url: `/revenue-data/data-am/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditDataAM')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formChangePasswordAM').on('submit', function(e) {
    e.preventDefault();
    const id = $('#changePasswordAMId').val();
    const password = $('#newPassword').val();
    const confirmPassword = $('#confirmPassword').val();

    if (password !== confirmPassword) {
      alert('Password dan konfirmasi password tidak cocok!');
      return;
    }

    const data = {
      password: password,
      password_confirmation: confirmPassword
    };

    $.ajax({
      url: `/revenue-data/data-am/${id}/change-password`,
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          $('#formChangePasswordAM')[0].reset();
          bootstrap.Modal.getInstance(document.getElementById('modalEditDataAM')).hide();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  $('#formEditDataCC').on('submit', function(e) {
    e.preventDefault();
    const id = $('#editDataCCId').val();
    const data = {
      nama: $('#editDataCCNama').val(),
      nipnas: $('#editDataCCNipnas').val()
    };

    $.ajax({
      url: `/revenue-data/data-cc/${id}`,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          bootstrap.Modal.getInstance(document.getElementById('modalEditDataCC')).hide();
          loadData();
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  });

  // ========================================
  // UTILITY FUNCTIONS
  // ========================================
  function formatCurrency(value) {
    if (!value) return 'Rp 0';
    return 'Rp ' + parseFloat(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
  }

  function showAlert(message, type) {
    alert(message);
  }

  // ========================================
  // INITIALIZATION
  // ========================================
  loadFilterOptions();
  loadData();

});
</script>
@endpush