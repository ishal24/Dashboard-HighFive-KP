<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Trend</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
</head>

@php
    $defaultMonth = now()->month;
    $defaultLabel = now()->format('F');
@endphp

<body class="bg-gray-100 p-8">
    <div class="trend-revenue-card">
        <div class="trend-card-header">
            <div class="header-main-content">
                <div class="header-title-section">
                    <div class="header-icon-wrapper">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="header-title">
                            Revenue Trend
                        </h2>
                        <p id="header-subtitle" class="header-subtitle">
                            Revenue performance
                        </p>
                    </div>
                </div>
                <div class="header-date-range">
                    <i class="fas fa-calendar-alt text-gray-500"></i>
                    <span id="header-date-range"></span>
                </div>
            </div>

            <div class="filters-form">
                {{-- Time Range Selector --}}
                <div>
                    <label for="range">Range:</label>
                    <div class="custom-select-wrapper inline-block" data-select-id="time-range">

                        <input type="hidden" class="custom-select-input" id="time-range"
                               value="ytd">

                        <button type="button" class="custom-select-trigger" id="range-trigger">
                            <span class="custom-select-label">YTD</span>
                        </button>

                        <div class="custom-select-panel">
                            <div class="custom-select-option selected" data-value="ytd">YTD</div>
                            <div class="custom-select-option" data-value="n_year">N Year</div>
                        </div>
                    </div>
                </div>

                {{-- N-Year Stepper --}}
                <div id="n-year-controls" class="n-year-stepper" style="display: none;">
                    <button type="button" id="n-year-decrement">-</button>
                    <span id="n-year-label">1 Year</span>
                    <button type="button" id="n-year-increment">+</button>
                </div>

                {{-- Start Month Selector --}}
                <div id="start-month-controls" style="display: none;">
                    <label for="start-month-trigger">Start Month:</label>

                    <div class="custom-select-wrapper inline-block" data-select-id="start-month">

                        <input type="hidden" class="custom-select-input"
                               id="start-month"
                               value="{{ $defaultMonth }}">

                        <button type="button" class="custom-select-trigger" id="start-month-trigger">
                            <span class="custom-select-label">{{ $defaultLabel }}</span>
                        </button>

                        <div class="custom-select-panel">
                            @foreach ($months as $date)
                                <div class="custom-select-option {{ $date->month == $defaultMonth ? 'selected' : '' }}"
                                     data-value="{{ $date->month }}">
                                    {{ $date->format('F') }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Division Filter --}}
                <div>
                    <label for="division">Division:</label>
                    <div class="custom-select-wrapper inline-block" data-select-id="division">

                        <input type="hidden" class="custom-select-input" id="division"
                               value="All">

                        <button type="button" class="custom-select-trigger" id="division-trigger">
                            <span class="custom-select-label">All Division</span>
                        </button>

                        <div class="custom-select-panel">
                            <div class="custom-select-option selected" data-value="All">All Division</div>
                            <div class="custom-select-option" data-value="DPS">DPS</div>
                            <div class="custom-select-option" data-value="DSS">DSS</div>
                            <div class="custom-select-option" data-value="DGS">DGS</div>
                        </div>
                    </div>
                </div>

                {{-- Source Filter --}}
                <div>
                    <label for="source">Source:</label>
                    <div class="custom-select-wrapper inline-block" data-select-id="source">

                        <input type="hidden" class="custom-select-input" id="source"
                               value="reguler">

                        <button type="button" class="custom-select-trigger" id="trigger">
                            <span class="custom-select-label">REGULER</span>
                        </button>

                        <div class="custom-select-panel">
                            <div class="custom-select-option selected" data-value="reguler">REGULER</div>
                            <div class="custom-select-option" data-value="ngtma">NGTMA</div>
                        </div>
                    </div>
                </div>

                {{-- View Filter --}}
                <div>
                    <label for="view">View:</label>
                    <div class="custom-select-wrapper inline-block" data-select-id="view">

                        <input type="hidden" class="custom-select-input" id="view"
                               value="revenue_and_achievement">

                        <button type="button" class="custom-select-trigger" id="trigger">
                            <span class="custom-select-label">Revenue & Achievement</span>
                        </button>

                        <div class="custom-select-panel">
                            <div class="custom-select-option selected" data-value="revenue_and_achievement">Revenue & Achievement</div>
                            <div class="custom-select-option" data-value="revenue_only">Revenue Only</div>
                            <div class="custom-select-option" data-value="achievement_only">Achievement Only</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="trend-card-content">
            <div id="chart-error" class="error-message" style="display: none;"></div>
            <div class="chart-container">
                <div class="loading-overlay">
                    <div class="spinner"></div>
                </div>
                {{-- NOTE: `style="height: 400px"` should be temporary apparently, but if not put here, everything is messed up :(--}}
                <canvas id="revenueChart" style="height: 400px"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- STATE MANAGEMENT & CONSTANTS ---
            const state = {
                timeRange: 'ytd',
                nYears: 1,
                startMonth: new Date().getMonth() + 1,
                division: 'All',
                source: 'reguler',
                viewMode: 'revenue_and_achievement',
                isLoading: true,
            };
            const dataCache = {}; // Cache to store fetched revenue data by YYYY-MM
            const MIN_YEAR = 2020;
            let revenueChart = null; // To hold the Chart.js instance

            const dom = {
                timeRange: document.getElementById('time-range'),
                nYearControls: document.getElementById('n-year-controls'),
                nYearLabel: document.getElementById('n-year-label'),
                nYearDecrement: document.getElementById('n-year-decrement'),
                nYearIncrement: document.getElementById('n-year-increment'),
                startMonthControls: document.getElementById('start-month-controls'),
                startMonth: document.getElementById('start-month'),
                division: document.getElementById('division'),
                source: document.getElementById('source'),
                viewMode: document.getElementById('view'),
                chartError: document.getElementById('chart-error'),
                loadingOverlay: document.querySelector('.loading-overlay'),
                chartCanvas: document.getElementById('revenueChart'),
                headerSubtitle: document.getElementById('header-subtitle'),
                headerDateRange: document.getElementById('header-date-range'),
                chartCanvas: document.getElementById('revenueChart'),
            };

            // NOTE: JS for custome select elements
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

            // --- DATA FETCHING & CACHING ---
            async function fetchData(startDate, endDate) {
                const source = state.source;
                const apiUrl = `/dashboard/trend-data?source=${source}&start_date=${startDate.toISOString().split('T')[0]}&end_date=${endDate.toISOString().split('T')[0]}`;

                try {
                    const response = await fetch(apiUrl);
                    if (!response.ok) {
                        throw new Error(`Network response was not ok (${response.status})`);
                    }
                    const data = await response.json();

                    // Process and store fetched data in the cache
                    data.forEach(row => {
                        const key = `${row.tahun}-${row.bulan}`;
                        if (!dataCache[key]) {
                            dataCache[key] = {
                                dgs: { real: 0, target: 0 },
                                dss: { real: 0, target: 0 },
                                dps: { real: 0, target: 0 }
                            };
                        }
                        const DIVISOR = 1_000_000_000;
                        const real_revenue = (row.real_revenue || 0) / DIVISOR;
                        const target_revenue = (row.target_revenue || 0) / DIVISOR;

                        switch (row.divisi_id) {
                            case 1: dataCache[key].dgs.real += real_revenue; dataCache[key].dgs.target += target_revenue; break;
                            case 2: dataCache[key].dss.real += real_revenue; dataCache[key].dss.target += target_revenue; break;
                            case 3: dataCache[key].dps.real += real_revenue; dataCache[key].dps.target += target_revenue; break;
                        }
                    });

                    return true;

                } catch (error) {
                    console.error("Fetch Error:", error);
                    dom.chartError.textContent = `Error: Failed to fetch revenue data. ${error.message}`;
                    dom.chartError.style.display = 'block';
                    return false;
                }
            }

            // --- CHART RENDERING ---
            function renderChart(startDate, endDate) {
                const labels = [];
                const realData = [], targetData = [], achievementData = [];

                let current = new Date(startDate);
                while (current <= endDate) {
                    labels.push(current.toLocaleDateString('en-US', { month: 'short', year: '2-digit' }));

                    const key = `${current.getFullYear()}-${current.getMonth() + 1}`;
                    const monthData = dataCache[key] || {
                        dgs: { real: 0, target: 0 },
                        dss: { real: 0, target: 0 },
                        dps: { real: 0, target: 0 }
                    };

                    let totalReal = 0;
                    let totalTarget = 0;

                    // Aggregate data based on Division filter
                    if (state.division === 'All' || state.division === 'DPS') {
                        totalReal += monthData.dps.real;
                        totalTarget += monthData.dps.target;
                    }
                    if (state.division === 'All' || state.division === 'DSS') {
                        totalReal += monthData.dss.real;
                        totalTarget += monthData.dss.target;
                    }
                    if (state.division === 'All' || state.division === 'DGS') {
                        totalReal += monthData.dgs.real;
                        totalTarget += monthData.dgs.target;
                    }

                    realData.push(totalReal.toFixed(2));
                    targetData.push(totalTarget.toFixed(2));

                    const achievement = (totalTarget === 0) ? 0 : (totalReal / totalTarget) * 100;
                    achievementData.push(achievement.toFixed(2));

                    current.setMonth(current.getMonth() + 1);
                }

                // Define the datasets based on the viewMode filter
                const datasets = [];
                const view = state.viewMode;

                if (view === 'revenue_and_achievement' || view === 'revenue_only') {
                    datasets.push({
                        type: 'bar',
                        label: 'Real Revenue',
                        data: realData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)', // Blue
                        yAxisID: 'yRevenue', // Assign to left axis
                        order: 2
                    });
                    datasets.push({
                        type: 'bar',
                        label: 'Target Revenue',
                        data: targetData,
                        backgroundColor: 'rgba(201, 203, 207, 0.7)', // Grey
                        yAxisID: 'yRevenue', // Assign to left axis
                        order: 2
                    });
                }

                if (view === 'revenue_and_achievement' || view === 'achievement_only') {
                    datasets.push({
                        type: 'line',
                        label: 'Achievement (%)',
                        data: achievementData,
                        borderColor: 'rgba(255, 99, 132, 1)', // Red
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.4,
                        yAxisID: 'yAchievement', // Assign to right axis
                        order: 1
                    });
                }

                const chartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        // Left Y-Axis (Revenue)
                        yRevenue: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: { display: true, text: 'Revenue (in Billions)' },
                        },
                        // Right Y-Axis (Achievement %)
                        yAchievement: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            title: { display: true, text: 'Achievement (%)' },
                            grid: { drawOnChartArea: false },
                            ticks: {
                                callback: value => `${value}%`
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        },
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    }
                };

                // Conditionally show/hide axes based on viewMode
                if (state.viewMode === 'revenue_only') {
                    chartOptions.scales.yAchievement.display = false;
                    chartOptions.scales.yRevenue.display = true;
                } else if (state.viewMode === 'achievement_only') {
                    chartOptions.scales.yAchievement.display = true;
                    chartOptions.scales.yRevenue.display = false;
                } else { // 'revenue_and_achievement'
                    chartOptions.scales.yAchievement.display = true;
                    chartOptions.scales.yRevenue.display = true;
                }

                // Create or update the chart
                if (revenueChart) {
                    revenueChart.data.labels = labels;
                    revenueChart.data.datasets = datasets;
                    revenueChart.options.scales = chartOptions.scales; // IMPORTANT: Update scales
                    revenueChart.update();
                } else {
                    revenueChart = new Chart(dom.chartCanvas, {
                        // Default type is bar, but datasets will override
                        type: 'bar',
                        data: { labels, datasets },
                        options: chartOptions
                    });
                }
            }

            function updateHeader(startDate, endDate) {
                // Update the subtitle based on the division and time range
                const divisionText = state.division === 'All' ? 'all divisions' : state.division;
                const timeFrameText = state.timeRange === 'ytd' ? `(YTD ${new Date().getFullYear()})` : `(Custom Range)`;
                dom.headerSubtitle.textContent = `Revenue performance across ${divisionText} ${timeFrameText} - ${state.source === 'ngtma' ? 'NGTMA' : 'REGULER'}`;

                // Update the date range display
                const endText = endDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                let startText;
                if (startDate.getFullYear() === endDate.getFullYear()) {
                    // If same year, don't repeat the year (e.g., "Jan – Oct 2025")
                    startText = startDate.toLocaleDateString('en-US', { month: 'short' });
                } else {
                    // If different years, show both (e.g., "Oct 2023 – Oct 2025")
                    startText = startDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }
                dom.headerDateRange.textContent = `${startText} – ${endText}`;
            }

            // --- MAIN ORCHESTRATOR FUNCTION ---
            async function updateDashboard() {
                state.isLoading = true;
                dom.loadingOverlay.classList.remove('hidden');
                dom.chartError.style.display = 'none';

                const now = new Date();
                let startDate, endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);

                if (state.timeRange === 'ytd') {
                    startDate = new Date(now.getFullYear(), 0, 1);
                } else {
                    const startYear = now.getFullYear() - state.nYears;
                    startDate = new Date(startYear, state.startMonth - 1, 1);
                }

                // Determine which data is missing from cache
                let firstMissingDate = null;
                let current = new Date(startDate);
                while (current <= endDate) {
                    const key = `${current.getFullYear()}-${current.getMonth() + 1}`;
                    if (!dataCache[key]) {
                        firstMissingDate = new Date(current);
                        break;
                    }
                    current.setMonth(current.getMonth() + 1);
                }

                // Fetch missing data if necessary
                if (firstMissingDate) {
                    const success = await fetchData(firstMissingDate, endDate);
                    if (!success) {
                        state.isLoading = false;
                        dom.loadingOverlay.classList.add('hidden');
                        return;
                    }
                }

                // Render the chart with data from cache
                renderChart(startDate, endDate);
                updateHeader(startDate, endDate);
                state.isLoading = false;
                dom.loadingOverlay.classList.add('hidden');
            }

            // --- EVENT LISTENERS ---
            function setupEventListeners() {
                dom.timeRange.addEventListener('change', (e) => {
                    state.timeRange = e.target.value;
                    updateUiVisibility();
                    updateDashboard();
                });
                dom.division.addEventListener('change', (e) => {
                    state.division = e.target.value;
                    updateDashboard();
                });
                dom.source.addEventListener('change', (e) => {
                    state.source = e.target.value;
                    Object.keys(dataCache).forEach(key => delete dataCache[key]); // Clear cache on source change
                    updateDashboard();
                });
                dom.startMonth.addEventListener('change', (e) => {
                    state.startMonth = parseInt(e.target.value, 10);
                    updateDashboard();
                });
                dom.nYearDecrement.addEventListener('click', () => {
                    if (state.nYears > 1) {
                        state.nYears--;
                        updateNYearsLabel();
                        updateDashboard();
                    }
                });
                dom.nYearIncrement.addEventListener('click', () => {
                    const maxNYears = new Date().getFullYear() - MIN_YEAR;
                    if (state.nYears < maxNYears) {
                        state.nYears++;
                        updateNYearsLabel();
                        updateDashboard();
                    }
                });

                dom.viewMode.addEventListener('change', (e) => {
                    state.viewMode = e.target.value;
                    // Call updateDashboard() which will use cached data.
                    updateDashboard();
                });
            }

            // --- UI HELPER FUNCTIONS ---
            function updateUiVisibility() {
                const showNYear = state.timeRange === 'n_year';
                dom.nYearControls.style.display = showNYear ? 'flex' : 'none';
                dom.startMonthControls.style.display = showNYear ? 'flex' : 'none';
            }
            function updateNYearsLabel() {
                dom.nYearLabel.textContent = `${state.nYears} Year${state.nYears > 1 ? 's' : ''}`;
            }

            // --- INITIALIZATION ---
            dom.startMonth.value = state.startMonth;
            setupEventListeners();
            updateDashboard(); // Initial data load (YTD)
            initializeAllCustomSelects();
        });
    </script>
    @endpush
</body>
</html>
