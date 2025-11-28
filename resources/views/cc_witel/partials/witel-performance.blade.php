<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Witel Achievement</title>

    {{-- Include Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    {{-- Your main CSS file (or add styles here) --}}
    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
</head>

@php
    $currentYear = date('Y');
    $currentMonth = date('n');
    $currentMonthLabel = date('F');
    $startYear = 2020;
@endphp

<body class="bg-gray-100 p-8">
    {{-- TODO: --}}
    {{-- make em responsive --}}
    {{-- so for some reason, changing one filter casues other filters event listeners to be triggered which makes fetching data happen on all event changes, in this case, 3 times fetching for changing 1 filter. absolute madness (design flaw tbh) --}}
    {{-- make it so user can click to a CC in the CC leaderboard list that will redirect them to the cc detail page --}}

    <div class="witel-performance-layout">
        {{-- Witel Leaderboard --}}
        <div class="witel-list-pane witel-layout-card border-gray-200">
            {{-- The card header now contains BOTH title and filters --}}
            <div class="witel-layout-card-header pb-4">
                <div class="header-top-row">
                    <div class="header-title-group flex items-center justify-between">
                        <i class="fas fa-building" id="witel-title-icon"></i>
                        <div>
                            <h2 class="header-title" id="header-title">
                                <p class="mb-0">Witel Achievement</p>
                            </h2>
                            <p id="wp-header-subtitle" class="header-subtitle">
                                Performansi Beserta Leaderboard Pelanggan Tiap Witel ({{ $currentYear }}) - REGULER
                            </p>
                        </div>
                    </div>

                    {{-- Horizontal Filters --}}
                    <div id="witel-filters" class="filters-form">
                        <div class="flex items-center gap-2">
                            <label for="witel-mode">Range:</label>
                            <div class="custom-select-wrapper inline-block" data-select-id="witel-mode">

                                <input type="hidden" class="custom-select-input" id="witel-mode"
                                       value="ytd">

                                <button type="button" class="custom-select-trigger" id="witel-mode">
                                    <span class="custom-select-label">YTD</span>
                                </button>

                                <div class="custom-select-panel">
                                    <div class="custom-select-option selected" data-value="ytd">YTD</div>
                                    <div class="custom-select-option" data-value="monthly">Monthly</div>
                                    <div class="custom-select-option" data-value="annual">Annual</div>
                                </div>
                            </div>
                        </div>

                        <div id="witel-year-filter" class="flex items-center gap-2" style="display: none;">
                            <label for="witel-year">Year:</label>
                            <div class="custom-select-wrapper inline-block" data-select-id="witel-year">

                                <input type="hidden" class="custom-select-input" id="witel-year"
                                       value="{{ $currentYear }}">

                                <button type="button" class="custom-select-trigger" id="witel-year">
                                    <span class="custom-select-label">{{ $currentYear }}</span>
                                </button>

                                <div class="custom-select-panel">
                                    @for ($y = $currentYear; $y >= $startYear; $y--)
                                        <div class="custom-select-option {{ $y == $currentYear ? 'selected' : '' }}"
                                            data-value="{{ $y }}">
                                            {{ $y }}
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div id="witel-month-filter" class="flex items-center gap-2" style="display: none;">
                            <label for="witel-month">Month:</label>
                            <div class="custom-select-wrapper inline-block" data-select-id="witel-month">

                                <input type="hidden" class="custom-select-input" id="witel-month"
                                       value="{{ $currentMonth }}">

                                <button type="button" class="custom-select-trigger" id="witel-month">
                                    <span class="custom-select-label">{{ $currentMonthLabel }}</span>
                                </button>

                                <div class="custom-select-panel">
                                    @foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                                        <div class="custom-select-option {{ ($i + 1) == $currentMonth ? 'selected' : '' }}"
                                            data-value="{{ $i + 1 }}">
                                            {{ $m }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="witel-source">Source:</label>
                            <div class="custom-select-wrapper inline-block" data-select-id="witel-source">

                                <input type="hidden" class="custom-select-input" id="witel-source"
                                       value="reguler">

                                <button type="button" class="custom-select-trigger" id="witel-source">
                                    <span class="custom-select-label">REGULER</span>
                                </button>

                                <div class="custom-select-panel">
                                    <div class="custom-select-option selected" data-value="reguler">REGULER</div>
                                    <div class="custom-select-option" data-value="ngtma">NGTMA</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="header-bottom-row">
                        <div id="witel-total-revenue" class="mt-3 text-sm text-gray-600">
                            <span class="font-semibold text-gray-900">: Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- The card content is now the scrollable list container --}}
            <div class="witel-layout-card-content">
                <div id="witel-loading" class="placeholder-text">Loading...</div>
                <div id="witel-error" class="placeholder-text error" style="display: none;"></div>
                <div id="witel-list-container" class="space-y-4">
                    {{-- Witel cards will be injected here --}}
                </div>
            </div>
        </div>

        {{-- Customer Detail --}}
        <div class="witel-detail-pane">
            <div class="witel-layout-card border-gray-200">
                <div class="customer-detail-header py-2 p-0 text-center">
                     <h2 id="witel-cc-leaderboard-loading" class="py-2 truncate">Loading...</h2>
                     <h2 class="py-2 truncate inline-block w-auto">
                            <span id="witel-detail-name" style="display: none;"></span>
                    </h2>
                     <p class="pt-2 m-0" id="witel-detail-subheading">Customer Leaderboard</p>
                </div>

                <div class="witel-customer-layout-card-content pt-2">
                    <div id="witel-detail-empty" class="placeholder-text">
                        Loading...
                    </div>
                    <div id="witel-detail-loading" class="placeholder-text" style="display: none;">
                        {{-- This will never be seen, a data is instant --}}
                    </div>
                    <div id="witel-detail-content"  style="display: none;">
                        <div class="customer-list-header pb-2">
                            <span class="customer-list-header-label customer-list-header-left">Nama CC</span>
                            <div class="customer-list-header-right">
                                <span class="customer-list-header-label">Share</span>
                                <span class="customer-list-header-label">Nominal</span>
                            </div>
                        </div>
                        <ol id="witel-customer-list" class="space-y-2" style="padding-left: 0px; margin-top: 0px; margin-bottom: 0px;"></ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- This is a 'template' for a single Witel row. JavaScript will clone this, fill in the data, and append it to the list. --}}
    <template id="witel-row-template">
        <div class="witel-card">
            {{-- New wrapper for all other content --}}
            <div class="witel-card-content">
                <div class="flex justify-between items-center mb-3">
                    <div class="witel-card-name-group">
                        <div data-el="status-dot" class="witel-status-dot"></div>
                        <span id="witel_name" data-el="witel-name" class="font-semibold text-gray-900"></span>
                    </div>
                    <div class="text-right">
                        <div data-el="revenue"></div>
                        <div data-el="target"></div>
                    </div>
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar-bg">
                        <div data-el="progress-bar" class="progress-bar" style="width: 0%;"></div>
                    </div>
                    <span data-el="badge" class="badge text-xs"></span>
                </div>
            </div>
        </div>
    </template>

    <template id="customer-row-template">
        <li class="customer-list-row">
            {{-- TODO: add on click listener for this --}}
            <div class="customer-info">
                <div data-el="rank-badge"></div>
                <span id="cc_name" data-el="customer-name" class="truncate"></span>
            </div>
            <div class="customer-stats">
                <span data-el="percentage"></span>
                <span data-el="revenue" class="tabular-nums"></span>
            </div>
        </li>
    </template>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const currentYear = new Date().getFullYear();
            const currentMonth = {{ $currentMonth }};

            let witelDataCache = [];

            // --- STATE & DOM ---
            const state = {
                mode: 'ytd',
                year: document.getElementById('witel-year').value,
                month: document.getElementById('witel-month').value,
                source: document.getElementById('witel-source').value,
                activeWitelId: null,
            };

            const dom = {
                // Left Pane
                subtitle: document.getElementById('wp-header-subtitle'),
                totalRevenue: document.getElementById('witel-total-revenue'),
                loading: document.getElementById('witel-loading'),
                error: document.getElementById('witel-error'),

                // list containers
                listContainer: document.getElementById('witel-list-container'),
                customerList: document.getElementById('witel-customer-list'),

                witelTemplate: document.getElementById('witel-row-template'),
                customerTemplate: document.getElementById('customer-row-template'),

                modeFilter: document.getElementById('witel-mode'),
                yearSelect: document.getElementById('witel-year'),
                monthSelect: document.getElementById('witel-month'),
                sourceFilter: document.getElementById('witel-source'),

                yearFilterContainer: document.getElementById('witel-year-filter'),
                monthFilterContainer: document.getElementById('witel-month-filter'),

                // Right Pane
                detailName: document.getElementById('witel-detail-name'),
                detailTitleLoading: document.getElementById('witel-cc-leaderboard-loading'),
                detailSubheading: document.getElementById('witel-detail-subheading'),
                detailEmpty: document.getElementById('witel-detail-empty'),
                detailContent: document.getElementById('witel-detail-content'),
            };

            // NOTE: JS for custom select elements
            function initializeAllCustomSelects() {
                const wrappers = document.querySelectorAll('.custom-select-wrapper');

                wrappers.forEach(wrapper => {
                    const hiddenInput = wrapper.querySelector('.custom-select-input');
                    const trigger = wrapper.querySelector('.custom-select-trigger');
                    const label = trigger.querySelector('.custom-select-label');
                    const panel = wrapper.querySelector('.custom-select-panel');
                    const options = panel.querySelectorAll('.custom-select-option');

                    let hiddenParent = wrapper.closest('[style*="display: none"]');
                    let originalParentStyle = null;
                    if (hiddenParent) {
                        originalParentStyle = hiddenParent.style.cssText;

                        // Temporarily make it "visible" but off-screen and invisible
                        // so we can measure its contents.
                        hiddenParent.style.cssText = 'display: block !important; visibility: hidden; position: absolute; top: -9999px; left: -9999px; z-index: -1;';
                    }

                    const originalPanelStyle = panel.style.cssText;
                    panel.style.cssText = 'display: block !important; visibility: hidden; opacity: 0;';

                    const panelWidth = panel.offsetWidth;

                    panel.style.cssText = originalPanelStyle;
                    if (hiddenParent) {
                        hiddenParent.style.cssText = originalParentStyle;
                    }

                    wrapper.style.minWidth = `${panelWidth}px`;

                    // Toggle panel when trigger is clicked
                    trigger.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevent click from bubbling to document
                        // Close all *other* open selects
                        closeAllSelects(wrapper);

                        wrapper.classList.toggle('open');
                    });

                    // Handle option selection
                    options.forEach(option => {
                        option.addEventListener('click', () => {
                            const selectedValue = option.dataset.value;

                            hiddenInput.value = selectedValue;

                            // *** THIS IS THE MOST IMPORTANT PART ***
                            // Fire a 'change' event on the hidden input.
                            // The existing filter logic (fetchData, etc.)
                            // will hear this event and run automatically.
                            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

                            wrapper.classList.remove('open');
                        });
                    });

                    hiddenInput.addEventListener('change', () => {
                        const newValue = hiddenInput.value;
                        let newLabel = '';

                        // Find the option text that matches the new value
                        options.forEach(option => {
                            if (option.dataset.value == newValue) {
                                option.classList.add('selected');
                                newLabel = option.textContent;
                            } else {
                                option.classList.remove('selected');
                            }
                        });

                        // Update the trigger's text
                        if (label && newLabel) {
                            label.textContent = newLabel;
                        }
                    });
                });

                document.addEventListener('click', () => {
                    closeAllSelects();
                });
            }

            /**
             * Helper to close all open selects, except for the one just clicked.
             */
            function closeAllSelects(exceptThisOne = null) {
                const allWrappers = document.querySelectorAll('.custom-select-wrapper');
                allWrappers.forEach(wrapper => {
                    if (wrapper !== exceptThisOne) {
                        wrapper.classList.remove('open');
                    }
                });
            }

            // --- HELPER FUNCTIONS (formatIDR, getBadgeStyle, getStatusDot) ---
            const formatIDRCompact = (num, flag="compact") => {
                if (num === null || num === undefined) return "—";
                const n = Number(num);
                const absN = Math.abs(n);
                const sign = n < 0 ? "-" : "";
                if (absN >= 1_000_000_000_000) return `${sign}${(n / 1_000_000_000_000).toFixed(2)} ${flag === "less" ? "Triliun" : "T"}`;
                if (absN >= 1_000_000_000) return `${sign}${(n / 1_000_000_000).toFixed(2)} ${flag === "less" ? "Milyar" : "M"}`;
                if (absN >= 1_000_000) return `${sign}${(n / 1_000_000).toFixed(2)} ${flag === "less" ? "Juta" : "Jt"}`;
                if (absN >= 1000) return `${sign}${(n / 1000).toFixed(2)} ${flag === "less" ? "Ribu" : "Rb"}`;
                return `${sign}${n.toFixed(0)}`;
            };

            // Determines badge style from achievement
            const getBadgeStyle = (ach) => {
                if (ach == null) return { class: 'badge-secondary', text: '—' };
                const text = `${ach.toFixed(1)}%`;
                if (ach >= 100) return { class: 'badge-success', text };
                if (ach >= 80) return { class: 'badge-warning', text };
                return { class: 'badge-danger', text };
            };

            // Determines status dot color
            const getStatusDot = (ach) => {
                if (ach == null) return 'bg-gray-400';
                if (ach >= 100) return 'bg-green-600';
                if (ach >= 80) return 'bg-amber-600';
                return 'bg-red-600';
            };

            function updateFilterVisibility() {
                const mode = state.mode;
                if (mode === 'ytd') {
                    dom.yearFilterContainer.style.display = 'none';
                    dom.monthFilterContainer.style.display = 'none';
                    // reset month to current month
                    const date = new Date();
                    state.month = date.getMonth() + 1;
                    state.year = date.getFullYear();
                } else if (mode === 'monthly') {
                    dom.yearFilterContainer.style.display = 'flex';
                    dom.monthFilterContainer.style.display = 'flex';
                } else if (mode === 'annual') {
                    dom.yearFilterContainer.style.display = 'flex';
                    dom.monthFilterContainer.style.display = 'none';
                }
            }

            function renderCustomerList(witelId, witelName) {
                // Find the data in our cache
                const witel = witelDataCache.find(w => w.id == witelId);
                if (!witel) return;

                const customers = witel.customers;

                // Update UI
                dom.detailTitleLoading.style.display = 'none';

                dom.detailName.style.display = 'inline-block'
                dom.detailName.textContent = witelName;
                dom.detailName.dataset.witelId = witelId;

                dom.customerList.innerHTML = ''; // Clear old list

                if (customers.length === 0) {
                    dom.detailEmpty.textContent = 'Tidak ada data customer untuk Witel ini pada bulan/tahun yang dipilih.';
                    dom.detailEmpty.style.display = 'block';
                    dom.detailContent.style.display = 'none';
                } else {
                    const total = customers.reduce((sum, row) => sum + (Number(row.total_revenue) || 0), 0);
                    const rankClasses = ['rank-gold', 'rank-silver', 'rank-bronze'];

                    customers.forEach((row, i) => {
                        const li = dom.customerTemplate.content.cloneNode(true).firstElementChild;
                        const val = Number(row.total_revenue) || 0;
                        const pct = total > 0 ? (val / total) * 100 : 0;
                        const rank = i + 1;
                        const rankClass = rankClasses[i] || 'rank-default';

                        const rankBadge = li.querySelector('[data-el="rank-badge"]');
                        rankBadge.classList.add(rankClass);
                        rankBadge.textContent = rank;

                        li.querySelector('[data-el="customer-name"]').textContent = row.nama_cc || 'N/A';
                        li.querySelector('[data-el="percentage"]').textContent = `${pct.toFixed(1)}%`;
                        li.querySelector('[data-el="revenue"]').textContent = `Rp${formatIDRCompact(val)}`;

                        const ccNameEl = li.querySelector('#cc_name');
                        // event listener dataset
                        ccNameEl.dataset.ccId = row.cc_id;
                        ccNameEl.dataset.ccName = row.nama_cc;

                        dom.customerList.appendChild(li);
                    });

                    dom.detailEmpty.style.display = 'none';
                    dom.detailContent.style.display = 'block';
                }
            }

            function renderLeaderboard(totalRevLabel) {
                dom.listContainer.innerHTML = ''; // Clear old list

                let totalRevenue = 0;
                witelDataCache.forEach(witel => {
                    totalRevenue += parseFloat(witel.revenueM);
                    const card = dom.witelTemplate.content.cloneNode(true).firstElementChild;

                    const ach = witel.achievement;
                    const badge = getBadgeStyle(ach);
                    const dot = getStatusDot(ach);
                    const progress = ach == null ? 0 : Math.min(ach, 100);

                    // temp cause I don't know how else would I do this and am too lazy to figure out a better way
                    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

                    let mode = 'YTD';
                    switch (state.mode) {
                        case 'ytd':
                            mode = 'YTD';
                            break;
                        case 'monthly':
                            mode = months[state.month - 1];
                            break;
                        case 'annual':
                            mode = state.year;
                            break;
                    }

                    // Populate Witel Summary
                    card.querySelector('[data-el="status-dot"]').classList.add(dot);
                    card.querySelector('[data-el="witel-name"]').textContent = witel.name;
                    card.querySelector('[data-el="badge"]').textContent = badge.text;
                    card.querySelector('[data-el="badge"]').classList.add(badge.class);
                    card.querySelector('[data-el="revenue"]').textContent = `Revenue ${mode}: Rp${formatIDRCompact(witel.revenueM)}`;
                    card.querySelector('[data-el="target"]').textContent = `Target ${mode}: Rp${formatIDRCompact(witel.targetM)}`;

                    const progressBar = card.querySelector('[data-el="progress-bar"]');
                    progressBar.style.width = `${progress}%`;
                    progressBar.classList.add(dot);

                    // Add click listener data
                    card.dataset.witelId = witel.id;
                    card.dataset.witelName = witel.name;

                    // redirect dataset
                    card.querySelector('#witel_name').dataset.witelId = witel.id;

                    dom.listContainer.appendChild(card);
                });

                const monthName = document.querySelector(`.custom-select-wrapper[data-select-id="witel-month"] .custom-select-option[data-value="${state.month}"]`).textContent.trim();
                dom.totalRevenue.innerHTML = `${totalRevLabel}: Rp<span class="font-semibold text-gray-900">${formatIDRCompact(totalRevenue, "less")}</span>`;
            }

            // --- DATA FETCHING ---
            async function fetchPerformanceData() {
                dom.loading.style.display = 'block';
                dom.error.style.display = 'none';
                dom.listContainer.innerHTML = '';
                witelDataCache = [];
                clearDetailPane();

                let url = `/dashboard/witel-performance-data?mode=${state.mode}&source=${state.source}`;
                if (state.mode === 'monthly') {
                    url += `&year=${state.year}&month=${state.month}`;
                } else if (state.mode === 'annual') {
                    url += `&year=${state.year}`;
                }

                const year = state.year;
                const monthName = document.querySelector(`.custom-select-wrapper[data-select-id="witel-month"] .custom-select-option[data-value="${state.month}"]`).textContent.trim();

                let subtitle = `Performansi beserta leaderboard pelanggan tiap witel (${year}) - ${(state.source === 'ngtma' ? 'NGTMA' : 'REGULER')}`
                let totalRevLabel = `Total Revenue (${year})`;
                if (state.mode === 'ytd') {
                    subtitle = `Performansi beserta leaderboard pelanggan tiap witel (YTD ${monthName} ${year}) - ${(state.source === 'ngtma' ? 'NGTMA' : 'REGULER')}`
                    totalRevLabel = `Total Revenue s/d ${monthName} ${year}`;
                } else if (state.mode === 'monthly') {
                    subtitle = `Performansi beserta leaderboard pelanggan tiap witel (${monthName} ${year}) - ${(state.source === 'ngtma' ? 'NGTMA' : 'REGULER')}`
                    totalRevLabel = `Total Revenue (${monthName} ${year})`;
                }

                dom.subtitle.textContent = `${subtitle}`;
                dom.totalRevenue.innerHTML = `${totalRevLabel}<span class="font-semibold text-gray-900">: Loading...</span>`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Failed to fetch leaderboard data.');

                    witelDataCache = await response.json(); // Store data in cache

                    if (witelDataCache.length === 0) {
                        dom.listContainer.innerHTML = '<p class="placeholder-text">No data found for these filters.</p>';
                        clearDetailPane();
                    } else {
                        renderLeaderboard(totalRevLabel); // Render the left pane

                        // pre-select top witel
                        const topWitel = witelDataCache[0];
                        if (topWitel) {
                            state.activeWitelId = topWitel.id;
                            // Visually select the card
                            const topCard = dom.listContainer.querySelector(`[data-witel-id="${topWitel.id}"]`);
                            topCard?.classList.add('selected');
                            // Render its details
                            renderCustomerList(topWitel.id, topWitel.name);
                        } else {
                            clearDetailPane(); // Just in case cache is empty after filtering
                        }
                    }
                } catch (err) {
                    dom.error.textContent = `Error: ${err.message}`;
                    dom.error.style.display = 'block';
                } finally {
                    dom.loading.style.display = 'none';
                }
            }

            function clearDetailPane() {
                state.activeWitelId = null;
                dom.detailName.textContent = 'Loading...';
                dom.detailSubheading.textContent = 'Customer Leaderboard';
                dom.detailEmpty.textContent = 'Loading...';
                dom.detailEmpty.style.display = 'block';
                dom.detailContent.style.display = 'none';

                // Remove 'selected' from all cards
                dom.listContainer.querySelectorAll('.witel-card.selected').forEach(c => c.classList.remove('selected'));
            }

            function setupEventListeners() {
                dom.modeFilter.addEventListener('change', (e) => {
                    state.mode = e.target.value;

                    if (state.mode === 'ytd') {
                        state.year = currentYear;
                        state.month = currentMonth;
                    }
                    else {
                        // idk either lmao
                        state.year = dom.yearSelect.value;
                        state.month = dom.monthSelect.value;
                    }

                    dom.yearSelect.value = state.year;
                    dom.monthSelect.value = state.month;

                    dom.yearSelect.dispatchEvent(new Event('change'));
                    dom.monthSelect.dispatchEvent(new Event('change'));

                    updateFilterVisibility();
                    clearDetailPane();
                    // console.log("fetch from modeFilter");
                    fetchPerformanceData();
                });

                dom.yearSelect.addEventListener('change', (e) => {
                    state.year = e.target.value;
                    // console.log("fetch from yearSelect");
                    fetchPerformanceData(); // Refetch
                });

                dom.monthSelect.addEventListener('change', (e) => {
                    state.month = e.target.value;
                    // console.log("fetch from monthSelect");
                    fetchPerformanceData(); // Refetch
                });

                dom.sourceFilter.addEventListener('change', (e) => {
                    state.source = e.target.value;
                    // console.log("fetch from sourceFilter");
                    fetchPerformanceData(); // Refetch
                });

                dom.listContainer.addEventListener('click', (e) => {
                    const card = e.target.closest('.witel-card');
                    if (!card) return;

                    const witelId = card.dataset.witelId;
                    const witelName = card.dataset.witelName;

                    // If already selected, do nothing
                    if (witelId == state.activeWitelId) return;

                    const witelDetailName = dom.witelDetailName

                    // Remove 'selected' from old card
                    const oldCard = dom.listContainer.querySelector('.witel-card.selected');
                    oldCard?.classList.remove('selected');

                    // Add 'selected' to new card
                    card.classList.add('selected');
                    state.activeWitelId = witelId;

                    // Fetch new data
                    renderCustomerList(witelId, witelName);
                });

                dom.listContainer.addEventListener('click', (e) => {
                    const card = e.target.closest('#witel_name');
                    if (!card) return;

                    const witelId = card.dataset.witelId;

                    window.location.href = "{{ url('/witel')}}" + "/" + witelId;
                });

                dom.customerList.addEventListener('click', (e) => {
                    const card = e.target.closest('#cc_name');
                    if (!card) return;

                    const ccId = card.dataset.ccId;

                    window.location.href = "{{ url('/corporate-customer')}}" + "/" + ccId;
                });

                dom.detailName.addEventListener('click', (e) => {
                    const el = e.target;
                    if (!el) return;

                    const witelId = el.dataset.witelId;

                    window.location.href = "{{ url('/witel')}}" + "/" + witelId;
                });
            }

            // --- INITIAL LOAD ---
            // initializeAllCustomSelects();
            updateFilterVisibility();
            setupEventListeners();
            // console.log("initial fetch");
            fetchPerformanceData();
        });
    </script>
    @endpush
</body>
</html>
