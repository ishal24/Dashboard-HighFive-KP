@extends('layouts.main')

@section('title', 'Leaderboard AM')

@section('styles')
<!-- CSS untuk Bootstrap Select -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/leaderboardAM.css') }}">
@endsection

@section('content')
<div class="main-content">
    <!-- Header Leaderboard -->
    <div class="header-leaderboard">
        <h1 class="header-title">
            <i class="fas fa-trophy me-3"></i>
            Leaderboard Performa Account Manager
        </h1>
        <p class="header-subtitle">
            Dashboard Performa Revenue dan Achievement Account Manager RLEGS
        </p>
    </div>

    <!-- Modern Date & Period Filter -->
    <div class="date-period-container">
        <!-- Date Filter -->
        <div class="date-filter-container">
            <button type="button" id="datePickerButton" class="date-filter">
                <i class="far fa-calendar-alt"></i>
                <span id="dateRangeText">01 Jan 2025 - 02 Oct 2025</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <input type="text" id="dateRangeSelector" style="display: none;" />
        </div>

        <!-- Modern Period Tabs -->
        <div class="period-tabs">
            <button class="period-tab {{ request('period', 'year_to_date') == 'year_to_date' ? 'active' : '' }}" data-period="year_to_date">
                <i class="fas fa-calendar-day me-2"></i>Year to Date
            </button>
            <button class="period-tab {{ request('period') == 'current_month' ? 'active' : '' }}" data-period="current_month">
                <i class="fas fa-calendar-day me-2"></i>Month to Date
            </button>
        </div>

        <div class="period-display">
            Tampilan: <strong id="displayPeriodText">
                @if(request('period') == 'current_month')
                    Bulan Ini
                @elseif(request('period') == 'custom' && request('start_date') && request('end_date'))
                    Kustom ({{ date('d M Y', strtotime(request('start_date'))) }} - {{ date('d M Y', strtotime(request('end_date'))) }})
                @elseif(request('period') == 'custom')
                    Kustom
                @else
                    Year to Date
                @endif
            </strong>
        </div>
    </div>

    <!-- Search & Filter Area -->
    <form id="filterForm" method="GET" action="{{ route('leaderboard') }}">
        <div class="search-filter-container">
            <div class="search-box">
                <div class="search-input">
                    <input type="search" name="search" placeholder="Telusuri nama AM" value="{{ request('search') }}">
                    <button type="submit">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>
            </div>

            <div class="filter-area">
                <div class="filter-selects">
                    <!-- Witel Filter -->
                    <div class="filter-group">
                        <select class="selectpicker" id="filterSelect2" name="witel_filter[]" multiple data-live-search="true" title="Pilih Witel" data-width="100%">
                            @foreach($witels as $witel)
                                <option value="{{ $witel->id }}">{{ $witel->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Divisi Filter -->
                    <div class="filter-group">
                        <select class="selectpicker" id="filterSelect3" name="divisi_filter[]" multiple data-live-search="true" title="Pilih Divisi" data-width="100%">
                            @foreach($divisis as $divisi)
                                <option value="{{ $divisi->id }}">{{ $divisi->kode }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <select class="selectpicker" id="filterSelect4" name="category_filter[]" multiple data-live-search="true" title="Pilih Kategori" data-width="100%">
                            <option value="enterprise">Enterprise</option>
                            <option value="government">Government</option>
                            <option value="multi">Multi Divisi</option>
                        </select>
                    </div>

                    <!-- Jenis Revenue Filter -->
                    <div class="filter-group">
                        <select class="selectpicker" id="filterSelect1" name="revenue_type_filter[]" multiple data-live-search="true" title="Jenis Revenue" data-width="100%">
                            <option value="Reguler">Reguler</option>
                            <option value="NGTMA">NGTMA</option>
                            <option value="Kombinasi">Kombinasi</option>
                        </select>
                    </div>

                    <!-- NEW: Ranking Method Filter -->
                    <div class="filter-group">
                        <select
                        class="selectpicker"
                        id="filterSelect5"
                        name="ranking_method"
                        title="Metode Ranking"
                        data-width="100%"
                        data-live-search="true"
                        data-size="6"
                        >
                        <option value="revenue"     {{ request('ranking_method', 'revenue') == 'revenue' ? 'selected' : '' }}>Revenue Tertinggi</option>
                        <option value="achievement" {{ request('ranking_method') == 'achievement' ? 'selected' : '' }}>Achievement Tertinggi</option>
                        <option value="combined"    {{ request('ranking_method') == 'combined' ? 'selected' : '' }}>Kombinasi (50-50)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden inputs -->
        <input type="hidden" name="period" id="periodInput" value="{{ request('period', 'year_to_date') }}">
        <input type="hidden" name="start_date" id="startDateInput" value="{{ request('start_date') }}">
        <input type="hidden" name="end_date" id="endDateInput" value="{{ request('end_date') }}">
    </form>

    <!-- Enhanced Leaderboard AM Cards - REAL DATA -->
    @forelse($leaderboardData as $am)
    <div class="am-card" onclick="window.location.href='{{ route('account-manager.show', $am->id) }}'">
        <div class="am-card-body">
            <div class="am-rank {{ $am->rank == 1 ? 'text-gold' : ($am->rank == 2 ? 'text-silver' : ($am->rank == 3 ? 'text-bronze' : '')) }}">
                {{ $am->rank }}
            </div>

            {{-- FOTO PROFIL + MAHKOTA --}}
            <div class="am-avatar">
                <img
                    src="{{ $am->profile_image ? asset('storage/' . $am->profile_image) : asset('img/profile.png') }}"
                    class="am-profile-pic"
                    alt="{{ $am->nama }}"
                    onerror="this.src='{{ asset('img/profile.png') }}'"
                >
                @if($am->rank <= 3)
                <span class="rank-crown rank-{{ $am->rank }}">
                    <i class="fas fa-crown"></i>
                </span>
                @endif
            </div>


            {{-- Info AM --}}
            <div class="am-info">
                <div class="am-name">{{ $am->nama }}</div>

                <div class="am-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    AM {{ $am->witel_name }}
                </div>

                <div class="am-detail">
                    <i class="fas fa-layer-group"></i>
                    {{ $am->divisi_list ?: 'Tidak ada divisi' }}
                </div>

                @php
                    $divisiArray = explode(', ', $am->divisi_list);
                    $hasDGS = in_array('DGS', $divisiArray);
                    $hasDPS = in_array('DPS', $divisiArray);
                    $hasDSS = in_array('DSS', $divisiArray);

                    if ($hasDGS && ($hasDPS || $hasDSS)) {
                        $categoryClass = 'multi';
                        $categoryLabel = 'MULTI DIVISI';
                    } elseif ($hasDGS && count($divisiArray) == 1) {
                        $categoryClass = 'government';
                        $categoryLabel = 'GOVERNMENT';
                    } elseif (($hasDPS || $hasDSS) && !$hasDGS) {
                        $categoryClass = 'enterprise';
                        $categoryLabel = 'ENTERPRISE';
                    } else {
                        $categoryClass = 'other';
                        $categoryLabel = 'OTHER';
                    }
                @endphp

                <div class="am-category-badge {{ $categoryClass }}">
                    {{ $categoryLabel }}
                </div>
            </div>

            {{-- Stats --}}
            <div class="am-stats">
                <div class="revenue-stat">
                    <div class="revenue-label">Revenue</div>
                    <div class="revenue-value">Rp {{ number_format($am->total_revenue, 0, ',', '.') }}</div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-label">Achievement</div>
                    <div class="achievement-value {{ $am->achievement_rate >= 100 ? 'text-success' : 'text-danger' }}">
                        <div class="achievement-icon">
                            <i class="fas fa-arrow-{{ $am->achievement_rate >= 100 ? 'up' : 'down' }}"></i>
                            <span>{{ number_format($am->achievement_rate, 2) }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="alert alert-info text-center" style="margin: 40px 0; padding: 30px; border-radius: 12px;">
        <i class="fas fa-info-circle" style="font-size: 48px; color: #3498db; margin-bottom: 15px;"></i>
        <h4>Tidak ada data Account Manager</h4>
        <p>Silakan ubah filter atau periode untuk melihat data lainnya.</p>
    </div>
    @endforelse

    <!-- Pagination Section - NEW -->
    @if($leaderboardData->total() > 0)
    <div class="pagination-wrapper">
        <!-- Pagination Info -->
        <div class="pagination-info">
            Menampilkan {{ $leaderboardData->firstItem() }}-{{ $leaderboardData->lastItem() }} dari {{ $leaderboardData->total() }} hasil
        </div>

        <!-- Pagination Controls & Per Page -->
        <div style="display: flex; gap: 30px; align-items: center;">
            <!-- Per Page Selection -->
            <div class="per-page-section">
                <label for="perPage">Baris</label>
                <select id="perPage"
                        name="per_page"
                        form="filterForm"                     {{-- penting: asosiasikan ke form --}}
                        class="per-page-select"
                        onchange="document.getElementById('filterForm').submit()">  {{-- submit pasti --}}
                    <option value="10"  {{ (int)request('per_page', 10) === 10  ? 'selected' : '' }}>10</option>
                    <option value="25"  {{ (int)request('per_page') === 25      ? 'selected' : '' }}>25</option>
                    <option value="50"  {{ (int)request('per_page') === 50      ? 'selected' : '' }}>50</option>
                    <option value="75"  {{ (int)request('per_page') === 75      ? 'selected' : '' }}>75</option>
                    <option value="100" {{ (int)request('per_page') === 100     ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <!-- Pagination Controls -->
            <div class="pagination-controls">
                {{-- Previous Button --}}
                <a href="{{ $leaderboardData->previousPageUrl() }}"
                   class="page-link {{ $leaderboardData->onFirstPage() ? 'disabled' : '' }}">
                    ‹
                </a>

                {{-- Page Numbers --}}
                @foreach($leaderboardData->getUrlRange(1, $leaderboardData->lastPage()) as $page => $url)
                    @if($page == $leaderboardData->currentPage())
                        <span class="page-link active">{{ $page }}</span>
                    @elseif($page == 1 || $page == $leaderboardData->lastPage() || abs($page - $leaderboardData->currentPage()) <= 1)
                        <a href="{{ $url }}" class="page-link">{{ $page }}</a>
                    @elseif(abs($page - $leaderboardData->currentPage()) == 2)
                        <span class="page-link disabled">...</span>
                    @endif
                @endforeach

                {{-- Next Button --}}
                <a href="{{ $leaderboardData->nextPageUrl() }}"
                   class="page-link {{ !$leaderboardData->hasMorePages() ? 'disabled' : '' }}">
                    ›
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<!-- Script untuk Bootstrap Select -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>

$(document).ready(function() {
    // Inisialisasi Bootstrap Select
    $('.selectpicker').selectpicker({
        liveSearch: true,
        liveSearchPlaceholder: 'Cari opsi...',
        size: 6,
        actionsBox: false,
        dropupAuto: false,
        mobile: false,
        noneSelectedText: 'Pilih filter',
        style: '',
        styleBase: 'form-control'
    });

    // Filter form submissions - Auto submit when filter changes
    $('.selectpicker').on('changed.bs.select', function (e) {
        $('#filterForm').submit();
    });

    // Modern Period Tabs with better visual feedback
    $('.period-tab').click(function() {
        const period = $(this).data('period');

        // Update active state
        $('.period-tab').removeClass('active');
        $(this).addClass('active');

        // Update hidden input
        $('#periodInput').val(period);

        // Update display text
        let displayText = period === 'year_to_date' ? 'Year to Date' : 'Bulan Ini';
        $('#displayPeriodText').text(displayText);

        // Clear custom dates
        if (period !== 'custom') {
            $('#startDateInput').val('');
            $('#endDateInput').val('');
        }

        // Submit form
        $('#filterForm').submit();
    });

    // Date Picker Functionality
    const dateRangeInput = document.getElementById('dateRangeSelector');
    const datePickerButton = document.getElementById('datePickerButton');

    const fp = flatpickr(dateRangeInput, {
        mode: "range",
        dateFormat: "Y-m-d",
        appendTo: document.querySelector('.date-period-container'),
        positionElement: datePickerButton,
        position: "below",
        static: false,
        defaultDate: ["2025-01-01", "2025-10-02"],
        onChange: function(selectedDates, dateStr) {
            if (selectedDates.length === 2) {
                const startDate = formatDate(selectedDates[0]);
                const endDate = formatDate(selectedDates[1]);
                document.getElementById('dateRangeText').textContent = startDate + ' - ' + endDate;

                // Set custom period as active
                $('.period-tab').removeClass('active');
                $('#periodInput').val('custom');
                $('#startDateInput').val(formatDateISO(selectedDates[0]));
                $('#endDateInput').val(formatDateISO(selectedDates[1]));

                // Update display with date range
                const displayText = `Kustom (${startDate} - ${endDate})`;
                document.getElementById('displayPeriodText').textContent = displayText;

                // Submit form
                $('#filterForm').submit();
            }
        },
        onOpen: function() {
            setTimeout(() => {
                const calendar = document.querySelector('.flatpickr-calendar');
                if (calendar) {
                    const buttonRect = datePickerButton.getBoundingClientRect();
                    const containerRect = document.querySelector('.date-period-container').getBoundingClientRect();

                    calendar.style.position = 'absolute';
                    calendar.style.top = (buttonRect.bottom - containerRect.top + 5) + 'px';
                    calendar.style.left = (buttonRect.left - containerRect.left) + 'px';
                    calendar.style.zIndex = '9999';
                }
            }, 10);
        }
    });

    datePickerButton.addEventListener('click', function() {
        fp.open();
    });

    function formatDate(date) {
        const day = date.getDate();
        const month = date.toLocaleString('default', { month: 'short' });
        const year = date.getFullYear();
        return `${day} ${month} ${year}`;
    }

    function formatDateISO(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    $('.selectpicker').selectpicker('setStyle','', 'refresh');

});
</script>
@endsection