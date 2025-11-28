/**
 * Revenue Data - Complete CRUD & Features
 * File: public/js/revenueData.js
 */

// Global variables
let currentTab = 'tab-cc-revenue';
let currentPage = {
    'cc-rev': 1,
    'am-rev': 1,
    'data-am': 1,
    'data-cc': 1
};
let currentFilters = {
    search: '',
    witel_id: 'all',
    divisi_id: 'all',
    segment_id: 'all',
    periode: '',
    role: 'all',
    tipe_revenue: 'reg'
};
let selectedItems = {
    'cc-rev': [],
    'am-rev': [],
    'data-am': [],
    'data-cc': []
};
let deleteCallback = null;
let bulkDeleteCallback = null;

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    initializeMonthPickers();
    initializeTabs();
    initializeFilters();
    initializeImportModal();
    initializeBulkOperations();
    initializeEditModals();

    // Load initial data
    loadFilterOptions();
    fetchRevenueCC();
});

// =====================================================
// MONTH PICKER INITIALIZATION
// =====================================================

function initializeMonthPickers() {
    // Filter Month Picker
    initFilterMonthPicker();

    // Import Revenue AM Month Picker
    initImportAMMonthPicker();

    // Edit Revenue CC Month Picker
    initEditCCRevMonthPicker();

    // Edit Revenue AM Month Picker
    initEditAMRevMonthPicker();
}

function initFilterMonthPicker() {
    const dateInput = document.getElementById('filter-date');
    const hiddenMonth = document.getElementById('filter-month');
    const hiddenYear = document.getElementById('filter-year');

    if (!dateInput) return;

    const currentYear = new Date().getFullYear();
    let selectedYear = currentYear;
    let selectedMonth = new Date().getMonth();

    const YEAR_FLOOR = 2020;

    function getYearWindow() {
        const nowY = new Date().getFullYear();
        const start = nowY;
        const end = Math.max(YEAR_FLOOR, nowY - 5);
        return { start, end };
    }

    function clampSelectedYear() {
        const { start, end } = getYearWindow();
        if (selectedYear > start) selectedYear = start;
        if (selectedYear < end) selectedYear = end;
    }

    let isYearView = false;
    let fpInstance = null;

    function getTriggerEl(instance) {
        return instance?.altInput || dateInput;
    }

    function syncCalendarWidth(instance) {
        try {
            const cal = instance.calendarContainer;
            const trigger = getTriggerEl(instance);
            if (!cal || !trigger) return;

            const rect = trigger.getBoundingClientRect();
            const w = Math.round(rect.width);

            cal.style.boxSizing = 'border-box';
            cal.style.width = w + 'px';
            cal.style.maxWidth = w + 'px';
        } catch (e) {
            // no-op
        }
    }

    const fp = flatpickr(dateInput, {
        plugins: [new monthSelectPlugin({
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
            selectedYear = d.getFullYear();
            selectedMonth = d.getMonth();

            clampSelectedYear();

            hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
            hiddenYear.value = selectedYear;

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

        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

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

    // Reset button handler
    const resetBtn = document.getElementById('btn-reset-filter');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            const now = new Date();
            selectedYear = now.getFullYear();
            selectedMonth = now.getMonth();
            clampSelectedYear();
            fp.setDate(now, true);
            hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
            hiddenYear.value = selectedYear;
        });
    }
}

function initImportAMMonthPicker() {
    // Similar implementation for import-am-date
    const dateInput = document.getElementById('import-am-date');
    if (!dateInput) return;

    flatpickr(dateInput, {
        plugins: [new monthSelectPlugin({
            shorthand: true,
            dateFormat: "Y-m",
            altFormat: "F Y",
            theme: "light"
        })],
        altInput: true,
        defaultDate: new Date(),
        onChange: function(selectedDates) {
            if (selectedDates[0]) {
                document.getElementById('import-am-month').value = selectedDates[0].getMonth() + 1;
                document.getElementById('import-am-year').value = selectedDates[0].getFullYear();
            }
        }
    });
}

function initEditCCRevMonthPicker() {
    const dateInput = document.getElementById('edit-cc-rev-date');
    if (!dateInput) return;

    flatpickr(dateInput, {
        plugins: [new monthSelectPlugin({
            shorthand: true,
            dateFormat: "Y-m",
            altFormat: "F Y",
            theme: "light"
        })],
        altInput: true,
        onChange: function(selectedDates) {
            if (selectedDates[0]) {
                document.getElementById('edit-cc-rev-month').value = selectedDates[0].getMonth() + 1;
                document.getElementById('edit-cc-rev-year').value = selectedDates[0].getFullYear();
            }
        }
    });
}

function initEditAMRevMonthPicker() {
    const dateInput = document.getElementById('edit-am-rev-date');
    if (!dateInput) return;

    flatpickr(dateInput, {
        plugins: [new monthSelectPlugin({
            shorthand: true,
            dateFormat: "Y-m",
            altFormat: "F Y",
            theme: "light"
        })],
        altInput: true,
        onChange: function(selectedDates) {
            if (selectedDates[0]) {
                document.getElementById('edit-am-rev-month').value = selectedDates[0].getMonth() + 1;
                document.getElementById('edit-am-rev-year').value = selectedDates[0].getFullYear();
            }
        }
    });
}

// =====================================================
// TAB MANAGEMENT
// =====================================================

function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');

            // Update active states
            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById(tabId).classList.add('active');

            currentTab = tabId;

            // Load data for active tab
            loadTabData(tabId);
        });
    });
}

function loadTabData(tabId) {
    switch(tabId) {
        case 'tab-cc-revenue':
            fetchRevenueCC();
            break;
        case 'tab-am-revenue':
            fetchRevenueAM();
            break;
        case 'tab-data-am':
            fetchDataAM();
            break;
        case 'tab-data-cc':
            fetchDataCC();
            break;
    }
}

// =====================================================
// FILTER MANAGEMENT
// =====================================================

function initializeFilters() {
    // Apply filter button
    document.getElementById('btn-apply-filter').addEventListener('click', applyFilters);

    // Reset filter button
    document.getElementById('btn-reset-filter').addEventListener('click', resetFilters);

    // Search input with debounce
    let searchTimeout;
    document.getElementById('global-search').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentFilters.search = e.target.value;
            applyFilters();
        }, 500);
    });

    // Revenue type buttons
    document.querySelectorAll('.seg-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilters.tipe_revenue = btn.getAttribute('data-revtype');
            fetchRevenueCC();
        });
    });

    // AM/HOTDA toggle
    document.querySelectorAll('.btn-toggle[data-role="amMode"] .am-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.btn-toggle[data-role="amMode"] .am-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilters.role = btn.getAttribute('data-mode');
            fetchRevenueAM();

            // Show/hide TELDA column
            const teldaCols = document.querySelectorAll('.hotda-col');
            if (currentFilters.role === 'hotda') {
                teldaCols.forEach(col => col.style.display = '');
            } else {
                teldaCols.forEach(col => col.style.display = 'none');
            }
        });
    });

    // Data AM role toggle
    document.querySelectorAll('.btn-toggle[data-role="amDataMode"] .am-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.btn-toggle[data-role="amDataMode"] .am-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilters.role = btn.getAttribute('data-mode');
            fetchDataAM();
        });
    });
}

function applyFilters() {
    currentFilters.witel_id = document.getElementById('filter-witel').value;
    currentFilters.divisi_id = document.getElementById('filter-divisi').value;
    currentFilters.segment_id = document.getElementById('filter-segment').value;

    const month = document.getElementById('filter-month').value;
    const year = document.getElementById('filter-year').value;
    currentFilters.periode = `${year}-${month}-01`;

    loadTabData(currentTab);
}

function resetFilters() {
    document.getElementById('global-search').value = '';
    document.getElementById('filter-witel').value = 'all';
    document.getElementById('filter-divisi').value = 'all';
    document.getElementById('filter-segment').value = 'all';

    currentFilters = {
        search: '',
        witel_id: 'all',
        divisi_id: 'all',
        segment_id: 'all',
        periode: '',
        role: currentFilters.role,
        tipe_revenue: currentFilters.tipe_revenue
    };

    loadTabData(currentTab);
}

function loadFilterOptions() {
    fetch('/revenue-data/filter-options')
        .then(response => response.json())
        .then(data => {
            // Populate Witel
            const witelSelect = document.getElementById('filter-witel');
            data.witels.forEach(witel => {
                const option = document.createElement('option');
                option.value = witel.id;
                option.textContent = witel.nama;
                witelSelect.appendChild(option);
            });

            // Populate Divisi
            const divisiSelects = document.querySelectorAll('#filter-divisi, #import-divisi, #edit-data-am-witel');
            data.divisions.forEach(divisi => {
                divisiSelects.forEach(select => {
                    const option = document.createElement('option');
                    option.value = divisi.id;
                    option.textContent = `${divisi.kode} - ${divisi.nama}`;
                    select.appendChild(option.cloneNode(true));
                });
            });

            // Populate Segment
            const segmentSelect = document.getElementById('filter-segment');
            data.segments.forEach(segment => {
                const option = document.createElement('option');
                option.value = segment.id;
                option.textContent = segment.lsegment_ho;
                segmentSelect.appendChild(option);
            });

            // Populate Witel for edit modals
            const editWitelSelect = document.getElementById('edit-data-am-witel');
            data.witels.forEach(witel => {
                const option = document.createElement('option');
                option.value = witel.id;
                option.textContent = witel.nama;
                editWitelSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading filter options:', error);
            showToast('Gagal memuat filter options', 'error');
        });
}

// =====================================================
// FETCH DATA FUNCTIONS
// =====================================================

function fetchRevenueCC(page = 1) {
    const perPage = document.getElementById('perpage-cc-rev').value;
    const params = new URLSearchParams({
        page: page,
        per_page: perPage,
        ...currentFilters
    });

    showLoading('table-cc-revenue');

    fetch(`/revenue-data/revenue-cc?${params}`)
        .then(response => response.json())
        .then(data => {
            renderRevenueCC(data);
            currentPage['cc-rev'] = data.current_page;
        })
        .catch(error => {
            console.error('Error fetching Revenue CC:', error);
            showError('table-cc-revenue', 'Gagal memuat data');
        });
}

function renderRevenueCC(data) {
    const tbody = document.querySelector('#table-cc-revenue tbody');
    tbody.innerHTML = '';

    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    data.data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input row-checkbox" data-id="${item.id}" data-table="cc-rev">
            </td>
            <td>${item.nama_cc}</td>
            <td><span class="badge-div ${item.divisi_kode.toLowerCase()}">${item.divisi_kode}</span></td>
            <td>${item.segment || '-'}</td>
            <td>${item.witel || '-'}</td>
            <td class="text-end">${item.target_revenue_formatted}</td>
            <td class="text-end">
                <span data-bs-toggle="tooltip" title="${item.revenue_type}">
                    ${item.real_revenue_formatted}
                </span>
            </td>
            <td>${item.bulan_display}</td>
            <td class="text-center">
                <button class="icon-btn edit" onclick="editRevenueCC(${item.id})" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="icon-btn delete" onclick="deleteRevenueCC(${item.id})" title="Hapus">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Update pagination
    updatePagination('cc-rev', data);

    // Update badge
    document.getElementById('badge-cc-rev').textContent = data.total;

    // Initialize checkboxes
    initializeCheckboxes('cc-rev');
}

function fetchRevenueAM(page = 1) {
    const perPage = document.getElementById('perpage-am-rev').value;
    const params = new URLSearchParams({
        page: page,
        per_page: perPage,
        ...currentFilters
    });

    showLoading('table-am-revenue');

    fetch(`/revenue-data/revenue-am?${params}`)
        .then(response => response.json())
        .then(data => {
            renderRevenueAM(data);
            currentPage['am-rev'] = data.current_page;
        })
        .catch(error => {
            console.error('Error fetching Revenue AM:', error);
            showError('table-am-revenue', 'Gagal memuat data');
        });
}

function renderRevenueAM(data) {
    const tbody = document.querySelector('#table-am-revenue tbody');
    tbody.innerHTML = '';

    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    data.data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input row-checkbox" data-id="${item.id}" data-table="am-rev">
            </td>
            <td>${item.nama_am}</td>
            <td><span class="badge-role">${item.role}</span></td>
            <td><span class="badge-div ${item.divisi_kode?.toLowerCase()}">${item.divisi_kode || '-'}</span></td>
            <td>${item.corporate_customer}</td>
            <td>${item.proporsi_display}</td>
            <td class="text-end">${item.target_revenue_formatted}</td>
            <td class="text-end">${item.real_revenue_formatted}</td>
            <td class="text-end">
                <span class="achv ${item.achievement_color}">${item.achievement_rate.toFixed(1)}%</span>
            </td>
            <td>${item.bulan_display}</td>
            <td class="hotda-col" style="${currentFilters.role === 'hotda' ? '' : 'display:none'}">${item.nama_telda || '-'}</td>
            <td class="text-center">
                <button class="icon-btn edit" onclick="editRevenueAM(${item.id})" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="icon-btn delete" onclick="deleteRevenueAM(${item.id})" title="Hapus">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    updatePagination('am-rev', data);
    document.getElementById('badge-am-rev').textContent = data.total;
    initializeCheckboxes('am-rev');
}

function fetchDataAM(page = 1) {
    const perPage = document.getElementById('perpage-data-am').value;
    const params = new URLSearchParams({
        page: page,
        per_page: perPage,
        search: currentFilters.search,
        witel_id: currentFilters.witel_id,
        divisi_id: currentFilters.divisi_id,
        role: currentFilters.role
    });

    showLoading('table-data-am');

    fetch(`/revenue-data/data-am?${params}`)
        .then(response => response.json())
        .then(data => {
            renderDataAM(data);
            currentPage['data-am'] = data.current_page;
        })
        .catch(error => {
            console.error('Error fetching Data AM:', error);
            showError('table-data-am', 'Gagal memuat data');
        });
}

function renderDataAM(data) {
    const tbody = document.querySelector('#table-data-am tbody');
    tbody.innerHTML = '';

    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    data.data.forEach(item => {
        const statusClass = item.is_registered ? 'registered' : 'unregistered';
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input row-checkbox" data-id="${item.id}" data-table="data-am">
            </td>
            <td>${item.nama}</td>
            <td>${item.nik}</td>
            <td><span class="badge-role">${item.role}</span></td>
            <td><span class="badge-div ${item.divisi_kode?.toLowerCase()}">${item.divisi_nama || '-'}</span></td>
            <td>${item.witel}</td>
            <td>${item.nama_telda || '-'}</td>
            <td><span class="badge-status ${statusClass}">${item.status_registrasi}</span></td>
            <td class="text-center">
                <button class="icon-btn edit" onclick="editDataAM(${item.id})" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="icon-btn delete" onclick="deleteDataAM(${item.id})" title="Hapus">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    updatePagination('data-am', data);
    document.getElementById('badge-am').textContent = data.total;
    initializeCheckboxes('data-am');
}

function fetchDataCC(page = 1) {
    const perPage = document.getElementById('perpage-data-cc').value;
    const params = new URLSearchParams({
        page: page,
        per_page: perPage,
        search: currentFilters.search
    });

    showLoading('table-data-cc');

    fetch(`/revenue-data/data-cc?${params}`)
        .then(response => response.json())
        .then(data => {
            renderDataCC(data);
            currentPage['data-cc'] = data.current_page;
        })
        .catch(error => {
            console.error('Error fetching Data CC:', error);
            showError('table-data-cc', 'Gagal memuat data');
        });
}

function renderDataCC(data) {
    const tbody = document.querySelector('#table-data-cc tbody');
    tbody.innerHTML = '';

    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    data.data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input row-checkbox" data-id="${item.id}" data-table="data-cc">
            </td>
            <td>${item.nama}</td>
            <td>${item.nipnas}</td>
            <td class="text-center">
                <button class="icon-btn edit" onclick="editDataCC(${item.id})" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="icon-btn delete" onclick="deleteDataCC(${item.id})" title="Hapus">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    updatePagination('data-cc', data);
    document.getElementById('badge-cc').textContent = data.total;
    initializeCheckboxes('data-cc');
}

// =====================================================
// PAGINATION
// =====================================================

function updatePagination(table, data) {
    const paginationDiv = document.getElementById(`pagination-${table}`);
    const infoDiv = document.getElementById(`info-${table}`);

    // Update info
    infoDiv.textContent = `Menampilkan ${data.from || 0}–${data.to || 0} dari ${data.total} hasil`;

    // Clear pagination
    paginationDiv.innerHTML = '';

    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pager' + (data.current_page === 1 ? ' disabled' : '');
    prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prevBtn.onclick = () => {
        if (data.current_page > 1) {
            fetchTableData(table, data.current_page - 1);
        }
    };
    paginationDiv.appendChild(prevBtn);

    // Page numbers (show max 5 pages)
    const maxPages = 5;
    let startPage = Math.max(1, data.current_page - Math.floor(maxPages / 2));
    let endPage = Math.min(data.last_page, startPage + maxPages - 1);

    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'pager' + (i === data.current_page ? ' active' : '');
        pageBtn.textContent = i;
        pageBtn.onclick = () => fetchTableData(table, i);
        paginationDiv.appendChild(pageBtn);
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pager' + (data.current_page === data.last_page ? ' disabled' : '');
    nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    nextBtn.onclick = () => {
        if (data.current_page < data.last_page) {
            fetchTableData(table, data.current_page + 1);
        }
    };
    paginationDiv.appendChild(nextBtn);
}

function fetchTableData(table, page) {
    switch(table) {
        case 'cc-rev':
            fetchRevenueCC(page);
            break;
        case 'am-rev':
            fetchRevenueAM(page);
            break;
        case 'data-am':
            fetchDataAM(page);
            break;
        case 'data-cc':
            fetchDataCC(page);
            break;
    }
}

// Per page change handlers
document.getElementById('perpage-cc-rev').addEventListener('change', () => fetchRevenueCC(1));
document.getElementById('perpage-am-rev').addEventListener('change', () => fetchRevenueAM(1));
document.getElementById('perpage-data-am').addEventListener('change', () => fetchDataAM(1));
document.getElementById('perpage-data-cc').addEventListener('change', () => fetchDataCC(1));

// =====================================================
// BULK OPERATIONS
// =====================================================

function initializeBulkOperations() {
    // Select all checkboxes
    document.getElementById('select-all-cc-rev').addEventListener('change', (e) => handleSelectAll('cc-rev', e.target.checked));
    document.getElementById('select-all-am-rev').addEventListener('change', (e) => handleSelectAll('am-rev', e.target.checked));
    document.getElementById('select-all-data-am').addEventListener('change', (e) => handleSelectAll('data-am', e.target.checked));
    document.getElementById('select-all-data-cc').addEventListener('change', (e) => handleSelectAll('data-cc', e.target.checked));

    // Delete selected buttons
    document.getElementById('btn-delete-selected-cc-rev').addEventListener('click', () => deleteSelected('cc-rev'));
    document.getElementById('btn-delete-selected-am-rev').addEventListener('click', () => deleteSelected('am-rev'));
    document.getElementById('btn-delete-selected-data-am').addEventListener('click', () => deleteSelected('data-am'));
    document.getElementById('btn-delete-selected-data-cc').addEventListener('click', () => deleteSelected('data-cc'));

    // Bulk delete buttons
    document.getElementById('btn-bulk-delete-cc-rev').addEventListener('click', () => bulkDelete('cc-rev'));
    document.getElementById('btn-bulk-delete-am-rev').addEventListener('click', () => bulkDelete('am-rev'));
    document.getElementById('btn-bulk-delete-data-am').addEventListener('click', () => bulkDelete('data-am'));
    document.getElementById('btn-bulk-delete-data-cc').addEventListener('click', () => bulkDelete('data-cc'));

    // Bulk delete confirmation input
    document.getElementById('bulk-delete-confirmation').addEventListener('input', (e) => {
        const btn = document.getElementById('btn-confirm-bulk-delete');
        btn.disabled = e.target.value !== 'HAPUS SEMUA';
    });

    // Confirm bulk delete button
    document.getElementById('btn-confirm-bulk-delete').addEventListener('click', confirmBulkDelete);
}

function initializeCheckboxes(table) {
    document.querySelectorAll(`.row-checkbox[data-table="${table}"]`).forEach(checkbox => {
        checkbox.addEventListener('change', () => updateBulkActions(table));
    });
    updateBulkActions(table);
}

function handleSelectAll(table, checked) {
    document.querySelectorAll(`.row-checkbox[data-table="${table}"]`).forEach(checkbox => {
        checkbox.checked = checked;
    });
    updateBulkActions(table);
}

function updateBulkActions(table) {
    const checkboxes = document.querySelectorAll(`.row-checkbox[data-table="${table}"]:checked`);
    selectedItems[table] = Array.from(checkboxes).map(cb => parseInt(cb.getAttribute('data-id')));

    const count = selectedItems[table].length;
    document.getElementById(`count-selected-${table}`).textContent = count;

    // Show/hide bulk actions
    const bulkActions = document.querySelector(`#tab-${table.replace('-', '-')} .bulk-actions, #tab-data-${table.split('-')[1]} .bulk-actions`);
    if (bulkActions) {
        bulkActions.style.display = count > 0 ? 'flex' : 'none';
    }
}

function deleteSelected(table) {
    if (selectedItems[table].length === 0) {
        showToast('Tidak ada item yang dipilih', 'warning');
        return;
    }

    const message = `Apakah Anda yakin ingin menghapus ${selectedItems[table].length} item yang dipilih?`;
    showDeleteConfirmation(message, () => {
        performDeleteSelected(table);
    });
}

function performDeleteSelected(table) {
    const endpoint = getEndpoint(table, 'delete-selected');

    showLoading(`table-${table}`);

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ ids: selectedItems[table] })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            selectedItems[table] = [];
            fetchTableData(table, currentPage[table]);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting selected:', error);
        showToast('Gagal menghapus data', 'error');
    });
}

function bulkDelete(table) {
    // Show bulk delete modal with current filters
    const modal = new bootstrap.Modal(document.getElementById('bulkDeleteConfirmModal'));

    // Get total count from badge
    const totalCount = document.getElementById(`badge-${table}`).textContent;
    document.getElementById('bulk-delete-info').textContent = `Total data yang akan dihapus: ${totalCount}`;

    // Show active filters
    const filtersList = document.getElementById('bulk-delete-filters');
    filtersList.innerHTML = '';

    if (currentFilters.search) {
        filtersList.innerHTML += `<li>Pencarian: ${currentFilters.search}</li>`;
    }
    if (currentFilters.witel_id !== 'all') {
        filtersList.innerHTML += `<li>Witel: ${getFilterLabel('witel', currentFilters.witel_id)}</li>`;
    }
    if (currentFilters.divisi_id !== 'all') {
        filtersList.innerHTML += `<li>Divisi: ${getFilterLabel('divisi', currentFilters.divisi_id)}</li>`;
    }
    if (currentFilters.segment_id !== 'all') {
        filtersList.innerHTML += `<li>Segment: ${getFilterLabel('segment', currentFilters.segment_id)}</li>`;
    }
    if (currentFilters.periode) {
        filtersList.innerHTML += `<li>Periode: ${currentFilters.periode}</li>`;
    }
    if (filtersList.innerHTML === '') {
        filtersList.innerHTML = '<li class="text-muted">Tidak ada filter aktif (SEMUA DATA akan dihapus)</li>';
    }

    // Reset confirmation input
    document.getElementById('bulk-delete-confirmation').value = '';
    document.getElementById('btn-confirm-bulk-delete').disabled = true;

    // Store callback
    bulkDeleteCallback = () => performBulkDelete(table);

    modal.show();
}

function confirmBulkDelete() {
    if (bulkDeleteCallback) {
        bulkDeleteCallback();
    }
}

function performBulkDelete(table) {
    const endpoint = getEndpoint(table, 'bulk-delete');

    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteConfirmModal'));
    modal.hide();

    showLoading(`table-${table}`);

    const body = {
        confirmation: 'HAPUS SEMUA',
        ...currentFilters
    };

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(body)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            fetchTableData(table, 1);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error bulk deleting:', error);
        showToast('Gagal menghapus data', 'error');
    });
}

function getEndpoint(table, action) {
    const baseUrls = {
        'cc-rev': '/revenue-data/revenue-cc',
        'am-rev': '/revenue-data/revenue-am',
        'data-am': '/revenue-data/data-am',
        'data-cc': '/revenue-data/data-cc'
    };

    return `${baseUrls[table]}/${action}`;
}

function getFilterLabel(type, value) {
    const select = document.getElementById(`filter-${type}`);
    const option = select.querySelector(`option[value="${value}"]`);
    return option ? option.textContent : value;
}

// =====================================================
// EDIT OPERATIONS
// =====================================================

function initializeEditModals() {
    // Edit Revenue CC
    document.getElementById('btn-update-cc-rev').addEventListener('click', updateRevenueCC);

    // Edit Revenue AM
    document.getElementById('btn-update-am-rev').addEventListener('click', updateRevenueAM);

    // Edit Data AM
    document.getElementById('btn-update-data-am').addEventListener('click', updateDataAM);
    document.getElementById('edit-data-am-role').addEventListener('change', toggleTeldaField);

    // Edit Data CC
    document.getElementById('btn-update-data-cc').addEventListener('click', updateDataCC);
}

// NOTE: Individual edit/delete functions need to be in global scope for onclick handlers
// They will be defined below after this section

// =====================================================
// IMPORT OPERATIONS
// =====================================================

function initializeImportModal() {
    document.getElementById('import-type').addEventListener('change', handleImportTypeChange);
    document.getElementById('btn-submit-import').addEventListener('click', submitImport);
}

function handleImportTypeChange(e) {
    const type = e.target.value;
    document.getElementById('revenue-cc-fields').style.display = type === 'revenue_cc' ? 'block' : 'none';
    document.getElementById('revenue-am-fields').style.display = type === 'revenue_am' ? 'block' : 'none';
}

function submitImport() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);

    const btn = document.getElementById('btn-submit-import');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Importing...';

    fetch('/revenue-data/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
            modal.hide();
            form.reset();

            // Reload current tab data
            loadTabData(currentTab);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error importing:', error);
        showToast('Gagal import data', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-upload me-2"></i>Import';
    });
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function showLoading(tableId) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    const colspan = tbody.querySelector('tr')?.children.length || 5;
    tbody.innerHTML = `
        <tr>
            <td colspan="${colspan}" class="text-center">
                <div class="loading-state">
                    <i class="fa-solid fa-spinner fa-spin"></i> Memuat data...
                </div>
            </td>
        </tr>
    `;
}

function showError(tableId, message) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    const colspan = tbody.querySelector('tr')?.children.length || 5;
    tbody.innerHTML = `
        <tr>
            <td colspan="${colspan}" class="text-center text-danger">
                <i class="fa-solid fa-circle-exclamation me-2"></i>${message}
            </td>
        </tr>
    `;
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function showDeleteConfirmation(message, callback) {
    document.getElementById('delete-confirm-message').textContent = message;
    deleteCallback = callback;

    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

document.getElementById('btn-confirm-delete').addEventListener('click', () => {
    if (deleteCallback) {
        deleteCallback();
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        modal.hide();
    }
});

function toggleTeldaField() {
    const role = document.getElementById('edit-data-am-role').value;
    const teldaGroup = document.getElementById('edit-data-am-telda-group');
    teldaGroup.style.display = role === 'HOTDA' ? 'block' : 'none';
}

// =====================================================
// GLOBAL EDIT/DELETE FUNCTIONS (for onclick handlers)
// =====================================================

// Revenue CC
function editRevenueCC(id) {
    // Fetch data and populate modal
    fetch(`/revenue-data/revenue-cc/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit-cc-rev-id').value = data.id;
            document.getElementById('edit-cc-rev-nama').value = data.nama_cc;
            document.getElementById('edit-cc-rev-target').value = data.target_revenue;
            document.getElementById('edit-cc-rev-real').value = data.real_revenue;
            document.getElementById('edit-cc-rev-month').value = data.bulan;
            document.getElementById('edit-cc-rev-year').value = data.tahun;

            // Set date picker
            const date = new Date(data.tahun, data.bulan - 1, 1);
            const fp = document.getElementById('edit-cc-rev-date')._flatpickr;
            if (fp) fp.setDate(date);

            const modal = new bootstrap.Modal(document.getElementById('editRevenueCCModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching Revenue CC:', error);
            showToast('Gagal memuat data', 'error');
        });
}

function updateRevenueCC() {
    const id = document.getElementById('edit-cc-rev-id').value;
    const data = {
        bulan: document.getElementById('edit-cc-rev-month').value,
        tahun: document.getElementById('edit-cc-rev-year').value,
        target_revenue: document.getElementById('edit-cc-rev-target').value,
        real_revenue: document.getElementById('edit-cc-rev-real').value
    };

    fetch(`/revenue-data/revenue-cc/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast(result.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editRevenueCCModal'));
            modal.hide();
            fetchRevenueCC(currentPage['cc-rev']);
        } else {
            showToast(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating Revenue CC:', error);
        showToast('Gagal update data', 'error');
    });
}

function deleteRevenueCC(id) {
    showDeleteConfirmation('Apakah Anda yakin ingin menghapus data Revenue CC ini?', () => {
        fetch(`/revenue-data/revenue-cc/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showToast(result.message, 'success');
                fetchRevenueCC(currentPage['cc-rev']);
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting Revenue CC:', error);
            showToast('Gagal menghapus data', 'error');
        });
    });
}

// Revenue AM
function editRevenueAM(id) {
    // Similar to editRevenueCC - implement fetch and populate
    // ... implementation
}

function updateRevenueAM() {
    // Similar to updateRevenueCC
    // ... implementation
}

function deleteRevenueAM(id) {
    // Similar to deleteRevenueCC
    // ... implementation
}

// Data AM
function editDataAM(id) {
    // ... implementation
}

function updateDataAM() {
    // ... implementation
}

function deleteDataAM(id) {
    // ... implementation
}

// Data CC
function editDataCC(id) {
    // ... implementation
}

function updateDataCC() {
    // ... implementation
}

function deleteDataCC(id) {
    // ... implementation
}