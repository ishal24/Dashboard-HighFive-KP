/**
 * revenue.js
 * Main JavaScript untuk halaman Revenue Data
 * Terintegrasi penuh dengan RevenueDataController
 */

// ===== GLOBAL VARIABLES =====
let currentTab = 'tab-cc-revenue';
let currentRevType = 'REGULER'; // untuk Revenue CC
let currentAMMode = 'AM'; // untuk Revenue AM
let currentPage = 1;
let perPage = 25;
let currentFilters = {
    search: '',
    witel_id: '',
    divisi_id: '',
    segment_id: '',
    periode: '',
    tipe_revenue: 'REGULER',
    role: 'AM'
};

// Store untuk delete operations
let deleteContext = {
    type: '', // 'single', 'select', 'bulk'
    target: '', // 'cc-revenue', 'am-revenue', 'data-cc', 'data-am'
    ids: [],
    callback: null
};

// ===== DOCUMENT READY =====
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeTabs();
    initializeSegmentSelector();
    initializeDatePicker();
    initializeCheckboxes();
    initializeModals();

    // Load initial data
    loadFilterOptions();
    loadData();
});

// ===== INITIALIZATION FUNCTIONS =====

function initializeFilters() {
    // Search
    document.getElementById('btn-search').addEventListener('click', function() {
        currentFilters.search = document.getElementById('search-input').value;
        currentPage = 1;
        loadData();
    });

    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentFilters.search = this.value;
            currentPage = 1;
            loadData();
        }
    });

    // Filter dropdowns
    document.getElementById('filter-witel').addEventListener('change', function() {
        currentFilters.witel_id = this.value;
    });

    document.getElementById('filter-divisi').addEventListener('change', function() {
        currentFilters.divisi_id = this.value;
    });

    document.getElementById('filter-segment').addEventListener('change', function() {
        currentFilters.segment_id = this.value;
    });

    // Apply & Reset buttons
    document.getElementById('btn-apply-filter').addEventListener('click', function() {
        currentPage = 1;
        loadData();
    });

    document.getElementById('btn-reset-filter').addEventListener('click', function() {
        resetFilters();
    });
}

function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            switchTab(tabId);
        });
    });

    // Revenue Type buttons (Reguler/NGTMA/Kombinasi)
    const revTypeButtons = document.querySelectorAll('.seg-btn');
    revTypeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            revTypeButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentRevType = this.dataset.revtype;
            currentFilters.tipe_revenue = currentRevType;
            currentPage = 1;
            loadData();
        });
    });

    // AM Mode buttons (AM/HOTDA)
    const amModeButtons = document.querySelectorAll('.am-btn');
    amModeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            amModeButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentAMMode = this.dataset.mode;
            currentFilters.role = currentAMMode;
            currentPage = 1;
            loadData();
        });
    });
}

function initializeSegmentSelector() {
    // Custom segment selector logic (dari kode original)
    const segSelect = document.getElementById('segSelect');
    const segmentSelect = document.getElementById('filter-segment');
    const tabs = segSelect.querySelectorAll('.seg-tab');
    const panels = segSelect.querySelectorAll('.seg-panel');
    const options = segSelect.querySelectorAll('.seg-option');

    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;

            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });

            panels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            const targetPanel = segSelect.querySelector(`.seg-panel[data-panel="${tabName}"]`);
            if (targetPanel) targetPanel.classList.add('active');
        });
    });

    // Option selection
    options.forEach(opt => {
        opt.addEventListener('click', function() {
            const value = this.dataset.value;

            segmentSelect.value = value;
            segmentSelect.dispatchEvent(new Event('change'));

            options.forEach(o => o.removeAttribute('aria-selected'));
            this.setAttribute('aria-selected', 'true');
        });
    });

    // Sync dengan divisi filter
    document.getElementById('filter-divisi').addEventListener('change', function() {
        const divisi = this.value;
        if (divisi && divisi !== 'all') {
            const divisiMap = {
                '1': 'DPS',
                '2': 'DSS',
                '3': 'DGS'
            };
            const tabName = divisiMap[divisi];
            if (tabName) {
                const tab = segSelect.querySelector(`.seg-tab[data-tab="${tabName}"]`);
                if (tab) tab.click();
            }
        }
    });
}

function initializeDatePicker() {
    flatpickr("#filter-date", {
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "F Y",
                altFormat: "F Y"
            })
        ],
        locale: "id",
        defaultDate: new Date(),
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates[0]) {
                const date = selectedDates[0];
                document.getElementById('filter-month').value = String(date.getMonth() + 1).padStart(2, '0');
                document.getElementById('filter-year').value = date.getFullYear();

                currentFilters.periode = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-01`;
            }
        }
    });
}

function initializeCheckboxes() {
    // Check all functionality untuk setiap tabel
    const checkAllBoxes = {
        'check-all-cc-revenue': 'table-cc-revenue',
        'check-all-am-revenue': 'table-am-revenue',
        'check-all-data-cc': 'table-data-cc',
        'check-all-data-am': 'table-data-am'
    };

    Object.keys(checkAllBoxes).forEach(checkAllId => {
        const checkAll = document.getElementById(checkAllId);
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const tableId = checkAllBoxes[checkAllId];
                const table = document.getElementById(tableId);
                const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');

                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });

                updateDeleteButtons(tableId);
            });
        }
    });

    // Individual checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.matches('tbody input[type="checkbox"]')) {
            const table = e.target.closest('table');
            updateDeleteButtons(table.id);
        }
    });
}

function initializeModals() {
    // Modal Data AM - Toggle tabs berdasarkan status registrasi
    const modalDataAM = document.getElementById('modalDataAM');
    if (modalDataAM) {
        modalDataAM.addEventListener('show.bs.modal', function(e) {
            const button = e.relatedTarget;
            const isEdit = button && button.classList.contains('edit');

            if (isEdit) {
                // Akan di-set dari loadEditDataAM()
            } else {
                // Mode tambah - sembunyikan tab password
                document.getElementById('tabsDataAM').style.display = 'none';
                document.getElementById('btn-save-password').style.display = 'none';
            }
        });
    }

    // Initialize button handlers
    initializeButtonHandlers();
}

function initializeButtonHandlers() {
    // Delete buttons
    const deleteButtons = {
        'btn-select-delete-cc-revenue': { target: 'cc-revenue', type: 'select' },
        'btn-bulk-delete-cc-revenue': { target: 'cc-revenue', type: 'bulk' },
        'btn-select-delete-am-revenue': { target: 'am-revenue', type: 'select' },
        'btn-bulk-delete-am-revenue': { target: 'am-revenue', type: 'bulk' },
        'btn-select-delete-data-cc': { target: 'data-cc', type: 'select' },
        'btn-bulk-delete-data-cc': { target: 'data-cc', type: 'bulk' },
        'btn-select-delete-data-am': { target: 'data-am', type: 'select' },
        'btn-bulk-delete-data-am': { target: 'data-am', type: 'bulk' }
    };

    Object.keys(deleteButtons).forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.addEventListener('click', function() {
                const { target, type } = deleteButtons[btnId];
                handleDelete(target, type);
            });
        }
    });

    // Save buttons
    document.getElementById('btn-save-edit-cc-revenue')?.addEventListener('click', saveEditCCRevenue);
    document.getElementById('btn-save-edit-am-revenue')?.addEventListener('click', saveEditAMRevenue);
    document.getElementById('btn-save-data-cc')?.addEventListener('click', saveDataCC);
    document.getElementById('btn-save-data-am')?.addEventListener('click', saveDataAM);
    document.getElementById('btn-save-password')?.addEventListener('click', saveAMPassword);

    // Add buttons
    document.getElementById('btn-add-cc')?.addEventListener('click', () => openModalDataCC());
    document.getElementById('btn-add-am')?.addEventListener('click', () => openModalDataAM());

    // Confirm delete
    document.getElementById('btn-confirm-delete')?.addEventListener('click', confirmDelete);

    // Divisi checkboxes change handler
    document.addEventListener('change', function(e) {
        if (e.target.matches('#data-am-divisi-checkboxes input[type="checkbox"]')) {
            updatePrimaryDivisiDropdown();
        }
    });
}

// ===== TAB SWITCHING =====

function switchTab(tabId) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabId);
    });

    // Update tab panels
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.toggle('active', panel.id === tabId);
    });

    currentTab = tabId;
    currentPage = 1;

    // Reset filters sesuai tab
    resetFiltersForTab(tabId);

    loadData();
}

function resetFiltersForTab(tabId) {
    // Reset specific filters based on tab
    if (tabId === 'tab-data-cc' || tabId === 'tab-data-am') {
        // Data tabs tidak butuh filter periode, segment
        currentFilters.periode = '';
        currentFilters.segment_id = 'all';
    }
}

// ===== LOAD DATA FUNCTIONS =====

async function loadFilterOptions() {
    try {
        const response = await fetch('/revenue-data/filter-options', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            populateFilterOptions(result);
        }
    } catch (error) {
        console.error('Error loading filter options:', error);
        showNotification('Gagal memuat filter options', 'error');
    }
}

function populateFilterOptions(data) {
    // Populate Witel
    const witelSelect = document.getElementById('filter-witel');
    witelSelect.innerHTML = '<option value="all">Semua Witel</option>';
    data.witels.forEach(witel => {
        witelSelect.innerHTML += `<option value="${witel.id}">${witel.nama}</option>`;
    });

    // Populate Divisi
    const divisiSelect = document.getElementById('filter-divisi');
    divisiSelect.innerHTML = '<option value="all">Semua Divisi</option>';
    data.divisions.forEach(div => {
        divisiSelect.innerHTML += `<option value="${div.id}">${div.nama}</option>`;
    });

    // Populate Segment (akan dihandle by custom selector)
    // Populate untuk modal Data AM
    const dataAMWitel = document.getElementById('data-am-witel');
    if (dataAMWitel) {
        dataAMWitel.innerHTML = '<option value="">Pilih Witel</option>';
        data.witels.forEach(witel => {
            dataAMWitel.innerHTML += `<option value="${witel.id}">${witel.nama}</option>`;
        });
    }

    // Populate divisi checkboxes untuk Data AM
    const divisiCheckboxes = document.getElementById('data-am-divisi-checkboxes');
    if (divisiCheckboxes) {
        divisiCheckboxes.innerHTML = '';
        data.divisions.forEach(div => {
            divisiCheckboxes.innerHTML += `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${div.id}" id="div-${div.id}">
                    <label class="form-check-label" for="div-${div.id}">
                        ${div.nama} (${div.kode})
                    </label>
                </div>
            `;
        });
    }
}

async function loadData() {
    const endpoints = {
        'tab-cc-revenue': '/revenue-data/cc-revenue',
        'tab-am-revenue': '/revenue-data/am-revenue',
        'tab-data-cc': '/revenue-data/data-cc',
        'tab-data-am': '/revenue-data/data-am'
    };

    const endpoint = endpoints[currentTab];
    if (!endpoint) return;

    showLoading(currentTab);

    try {
        // Build params - remove empty values
        const cleanFilters = {};
        Object.keys(currentFilters).forEach(key => {
            const value = currentFilters[key];
            // Only add if value is not empty and not default 'all'
            if (value !== '' && value !== 'all' && value !== null && value !== undefined) {
                cleanFilters[key] = value;
            }
        });

        const params = new URLSearchParams({
            page: currentPage,
            per_page: getPerPage(),
            ...cleanFilters
        });

        const response = await fetch(`${endpoint}?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            renderTable(result);
            renderPagination(result);
            updateBadge(result.total);
        } else {
            showNotification(result.message || 'Gagal memuat data', 'error');
        }
    } catch (error) {
        console.error('Error loading data:', error);
        showNotification('Gagal memuat data', 'error');
    } finally {
        hideLoading(currentTab);
    }
}

function getPerPage() {
    const perpageSelectors = {
        'tab-cc-revenue': 'perpage-cc-revenue',
        'tab-am-revenue': 'perpage-am-revenue',
        'tab-data-cc': 'perpage-data-cc',
        'tab-data-am': 'perpage-data-am'
    };

    const selector = document.getElementById(perpageSelectors[currentTab]);
    return selector ? parseInt(selector.value) : 25;
}

// ===== RENDER FUNCTIONS =====

function renderTable(result) {
    const renderers = {
        'tab-cc-revenue': renderCCRevenueTable,
        'tab-am-revenue': renderAMRevenueTable,
        'tab-data-cc': renderDataCCTable,
        'tab-data-am': renderDataAMTable
    };

    const renderer = renderers[currentTab];
    if (renderer) renderer(result.data);
}

function renderCCRevenueTable(data) {
    const tbody = document.querySelector('#table-cc-revenue tbody');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => {
        const divisiClass = item.divisi_kode ? item.divisi_kode.toLowerCase() : '';
        const divisiNama = item.divisi_nama || '-';

        return `
        <tr>
            <td><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
            <td>${item.nama_cc}</td>
            <td><span class="badge-div ${divisiClass}">${divisiNama}</span></td>
            <td>${item.segment || '-'}</td>
            <td class="text-end">${item.target_revenue_formatted}</td>
            <td class="text-end">
                <span class="rev-val" data-bs-toggle="tooltip" title="${item.revenue_type}">
                    ${item.real_revenue_formatted}
                </span>
            </td>
            <td>${item.bulan_display}</td>
            <td class="text-center">
                <button class="icon-btn edit" onclick="editCCRevenue(${item.id})" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="icon-btn delete" onclick="deleteSingle('cc-revenue', ${item.id})" title="Hapus">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        </tr>
        `;
    }).join('');

    // Initialize tooltips
    initializeTooltips();
}

function renderAMRevenueTable(data) {
    const tbody = document.querySelector('#table-am tbody');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => {
        const divisiClass = item.divisi_kode ? item.divisi_kode.toLowerCase() : '';
        const divisiNama = item.divisi_nama || '-';

        return `
        <tr>
            <td><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
            <td>${item.nama_am}</td>
            <td><span class="badge-div ${divisiClass}">${divisiNama}</span></td>
            <td>${item.nama_cc}</td>
            <td class="text-end">${item.target_revenue_formatted}</td>
            <td class="text-end">${item.real_revenue_formatted}</td>
            <td class="text-end">
                <span class="achv ${item.achievement_color}">${item.achievement_display}</span>
            </td>
            <td>${item.bulan_display}</td>
            <td class="hotda-col d-none">${item.telda_nama || '-'}</td>
            <td class="text-center">
                <button class="icon-btn edit" onclick="editAMRevenue(${item.id})" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="icon-btn delete" onclick="deleteSingle('am-revenue', ${item.id})" title="Hapus">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        </tr>
        `;
    }).join('');
}

function renderDataCCTable(data) {
    const tbody = document.querySelector('#table-data-cc tbody');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => `
        <tr>
            <td><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
            <td>${item.nama}</td>
            <td>${item.nipnas}</td>
            <td>${item.created_at_formatted || '-'}</td>
            <td class="text-center">
                <button class="icon-btn edit" onclick="editDataCC(${item.id})" title="Edit">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="icon-btn delete" onclick="deleteSingle('data-cc', ${item.id})" title="Hapus">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderDataAMTable(data) {
    const tbody = document.querySelector('#table-data-am tbody');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => {
        // Render divisi badges
        let divisiBadges = '-';
        if (item.divisi_list && item.divisi_list.length > 0) {
            divisiBadges = item.divisi_list.map(div =>
                `<span class="badge-div ${div.kode.toLowerCase()}">${div.nama}${div.is_primary ? ' ⭐' : ''}</span>`
            ).join(' ');
        }

        return `
            <tr>
                <td><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
                <td>${item.nama}</td>
                <td>${item.nik}</td>
                <td>${divisiBadges}</td>
                <td>${item.witel_nama || '-'}</td>
                <td><span class="badge ${item.role_badge}">${item.role}</span></td>
                <td><span class="badge ${item.status_badge}">${item.status_registrasi}</span></td>
                <td class="text-center">
                    <button class="icon-btn edit" onclick="editDataAM(${item.id})" title="Edit">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </button>
                    <button class="icon-btn delete" onclick="deleteSingle('data-am', ${item.id})" title="Hapus">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination(result) {
    const paginationIds = {
        'tab-cc-revenue': { info: 'info-cc-revenue', pages: 'pages-cc-revenue' },
        'tab-am-revenue': { info: 'info-am-revenue', pages: 'pages-am-revenue' },
        'tab-data-cc': { info: 'info-data-cc', pages: 'pages-data-cc' },
        'tab-data-am': { info: 'info-data-am', pages: 'pages-data-am' }
    };

    const ids = paginationIds[currentTab];
    if (!ids) return;

    // Update info
    const info = document.getElementById(ids.info);
    if (info) {
        info.textContent = `Menampilkan ${result.from || 0}–${result.to || 0} dari ${result.total || 0} hasil`;
    }

    // Update pages
    const pages = document.getElementById(ids.pages);
    if (!pages) return;

    let html = '';

    // Previous button
    html += `
        <button class="pager ${currentPage === 1 ? 'disabled' : ''}"
                onclick="changePage(${currentPage - 1})"
                ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    `;

    // Page numbers
    const lastPage = result.last_page || 1;
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(lastPage, startPage + maxVisible - 1);

    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button class="pager ${i === currentPage ? 'active' : ''}"
                    onclick="changePage(${i})">
                ${i}
            </button>
        `;
    }

    // Next button
    html += `
        <button class="pager ${currentPage === lastPage ? 'disabled' : ''}"
                onclick="changePage(${currentPage + 1})"
                ${currentPage === lastPage ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    `;

    pages.innerHTML = html;
}

function updateBadge(total) {
    const badgeIds = {
        'tab-cc-revenue': 'badge-cc-rev',
        'tab-am-revenue': 'badge-am-rev',
        'tab-data-cc': 'badge-cc',
        'tab-data-am': 'badge-am'
    };

    const badgeId = badgeIds[currentTab];
    const badge = document.getElementById(badgeId);
    if (badge) {
        badge.textContent = total || 0;
    }
}

// ===== PAGINATION =====

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadData();
}

// Initialize perpage change handlers
document.addEventListener('change', function(e) {
    if (e.target.matches('.perpage select')) {
        currentPage = 1;
        loadData();
    }
});

// ===== CHECKBOX & DELETE FUNCTIONS =====

function updateDeleteButtons(tableId) {
    const table = document.getElementById(tableId);
    const checkedBoxes = table.querySelectorAll('tbody input[type="checkbox"]:checked');
    const hasChecked = checkedBoxes.length > 0;

    // Get parent tab panel
    const tabPanel = table.closest('.tab-panel');
    const deleteActions = tabPanel.querySelector('.delete-actions');

    if (deleteActions) {
        deleteActions.style.display = hasChecked ? 'inline-block' : 'none';
    }
}

function handleDelete(target, type) {
    deleteContext.target = target;
    deleteContext.type = type;
    deleteContext.ids = [];

    let message = '';

    if (type === 'select') {
        // Get selected IDs
        const tableIds = {
            'cc-revenue': 'table-cc-revenue',
            'am-revenue': 'table-am-revenue',
            'data-cc': 'table-data-cc',
            'data-am': 'table-data-am'
        };

        const table = document.getElementById(tableIds[target]);
        const checkedBoxes = table.querySelectorAll('tbody input[type="checkbox"]:checked');

        if (checkedBoxes.length === 0) {
            showNotification('Pilih minimal 1 data untuk dihapus', 'warning');
            return;
        }

        deleteContext.ids = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
        message = `Apakah Anda yakin ingin menghapus ${deleteContext.ids.length} data yang dipilih?`;
    } else if (type === 'bulk') {
        message = 'Apakah Anda yakin ingin menghapus SEMUA data sesuai filter yang diterapkan? Tindakan ini akan menghapus data dalam jumlah besar!';
    }

    document.getElementById('delete-message').textContent = message;
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
    modal.show();
}

function deleteSingle(target, id) {
    deleteContext.target = target;
    deleteContext.type = 'single';
    deleteContext.ids = [id];

    const targetNames = {
        'cc-revenue': 'Revenue CC',
        'am-revenue': 'Revenue AM',
        'data-cc': 'Data CC',
        'data-am': 'Data AM'
    };

    const message = `Apakah Anda yakin ingin menghapus ${targetNames[target]} ini?`;
    document.getElementById('delete-message').textContent = message;

    const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
    modal.show();
}

async function confirmDelete() {
    const { target, type, ids } = deleteContext;

    const endpoints = {
        'cc-revenue': {
            single: id => `/revenue-data/cc-revenue/${id}`,
            select: '/revenue-data/cc-revenue/select-delete',
            bulk: '/revenue-data/cc-revenue/bulk-delete'
        },
        'am-revenue': {
            single: id => `/revenue-data/am-revenue/${id}`,
            select: '/revenue-data/am-revenue/select-delete',
            bulk: '/revenue-data/am-revenue/bulk-delete'
        },
        'data-cc': {
            single: id => `/revenue-data/data-cc/${id}`,
            select: '/revenue-data/data-cc/select-delete',
            bulk: '/revenue-data/data-cc/bulk-delete'
        },
        'data-am': {
            single: id => `/revenue-data/data-am/${id}`,
            select: '/revenue-data/data-am/select-delete',
            bulk: '/revenue-data/data-am/bulk-delete'
        }
    };

    try {
        let url, method, body;

        if (type === 'single') {
            url = endpoints[target].single(ids[0]);
            method = 'DELETE';
            body = null;
        } else if (type === 'select') {
            url = endpoints[target].select;
            method = 'POST';
            body = JSON.stringify({ ids });
        } else if (type === 'bulk') {
            url = endpoints[target].bulk;
            method = 'POST';
            body = JSON.stringify(currentFilters);
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: body
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmDelete'));
            modal.hide();

            // Reload data
            loadData();
        } else {
            showNotification(result.message || 'Gagal menghapus data', 'error');
        }
    } catch (error) {
        console.error('Error deleting:', error);
        showNotification('Gagal menghapus data', 'error');
    }
}

// Make delete functions globally accessible
window.deleteSingle = deleteSingle;

// ===== EDIT FUNCTIONS =====

async function editCCRevenue(id) {
    try {
        // Fetch semua data dulu
        const params = new URLSearchParams({
            page: 1,
            per_page: 1000,
            ...currentFilters
        });

        const response = await fetch(`/revenue-data/cc-revenue?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            const item = result.data.find(d => d.id === id);

            if (item) {
                document.getElementById('edit-cc-revenue-id').value = item.id;
                document.getElementById('edit-cc-revenue-nama').value = item.nama_cc;
                document.getElementById('edit-cc-revenue-target').value = item.target_revenue;
                document.getElementById('edit-cc-revenue-real').value = item.real_revenue;
                document.getElementById('edit-cc-revenue-tipe').value = item.tipe_revenue || 'REGULER';

                const modal = new bootstrap.Modal(document.getElementById('modalEditCCRevenue'));
                modal.show();
            } else {
                showNotification('Data tidak ditemukan', 'error');
            }
        } else {
            showNotification('Gagal memuat data', 'error');
        }
    } catch (error) {
        console.error('Error editing CC Revenue:', error);
        showNotification('Gagal memuat data', 'error');
    }
}

async function saveEditCCRevenue() {
    const id = document.getElementById('edit-cc-revenue-id').value;
    const data = {
        target_revenue: document.getElementById('edit-cc-revenue-target').value,
        real_revenue: document.getElementById('edit-cc-revenue-real').value,
        tipe_revenue: document.getElementById('edit-cc-revenue-tipe').value
    };

    try {
        const response = await fetch(`/revenue-data/cc-revenue/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditCCRevenue'));
            modal.hide();

            loadData();
        } else {
            showNotification(result.message || 'Gagal menyimpan data', 'error');
        }
    } catch (error) {
        console.error('Error saving CC Revenue:', error);
        showNotification('Gagal menyimpan data', 'error');
    }
}

async function editAMRevenue(id) {
    try {
        const params = new URLSearchParams({
            page: 1,
            per_page: 1000,
            ...currentFilters
        });

        const response = await fetch(`/revenue-data/am-revenue?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            const item = result.data.find(d => d.id === id);

            if (item) {
                document.getElementById('edit-am-revenue-id').value = item.id;
                document.getElementById('edit-am-revenue-nama').value = item.nama_am;
                document.getElementById('edit-am-revenue-cc').value = item.nama_cc;
                // Proporsi dari decimal ke persen
                document.getElementById('edit-am-revenue-proporsi').value = (item.proporsi * 100).toFixed(0);

                const modal = new bootstrap.Modal(document.getElementById('modalEditAMRevenue'));
                modal.show();
            } else {
                showNotification('Data tidak ditemukan', 'error');
            }
        } else {
            showNotification('Gagal memuat data', 'error');
        }
    } catch (error) {
        console.error('Error editing AM Revenue:', error);
        showNotification('Gagal memuat data', 'error');
    }
}

async function saveEditAMRevenue() {
    const id = document.getElementById('edit-am-revenue-id').value;
    const proporsi = parseFloat(document.getElementById('edit-am-revenue-proporsi').value) / 100; // Convert % to decimal

    try {
        const response = await fetch(`/revenue-data/am-revenue/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ proporsi })
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditAMRevenue'));
            modal.hide();

            loadData();
        } else {
            showNotification(result.message || 'Gagal menyimpan data', 'error');
        }
    } catch (error) {
        console.error('Error saving AM Revenue:', error);
        showNotification('Gagal menyimpan data', 'error');
    }
}

// Make edit functions globally accessible
window.editCCRevenue = editCCRevenue;
window.editAMRevenue = editAMRevenue;
window.editDataCC = editDataCC;
window.editDataAM = editDataAM;

// ===== DATA CC FUNCTIONS =====

function openModalDataCC(id = null) {
    if (id) {
        document.getElementById('modalDataCCTitle').textContent = 'Edit Data CC';
        // Load data for edit
        loadDataCC(id);
    } else {
        document.getElementById('modalDataCCTitle').textContent = 'Tambah Data CC';
        document.getElementById('form-data-cc').reset();
        document.getElementById('data-cc-id').value = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalDataCC'));
    modal.show();
}

async function loadDataCC(id) {
    // Implementation to load CC data for edit
}

async function saveDataCC() {
    const id = document.getElementById('data-cc-id').value;
    const data = {
        nama: document.getElementById('data-cc-nama').value,
        nipnas: document.getElementById('data-cc-nipnas').value
    };

    const url = id ? `/revenue-data/data-cc/${id}` : '/revenue-data/data-cc';
    const method = id ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalDataCC'));
            modal.hide();

            loadData();
        } else {
            showNotification(result.message || 'Gagal menyimpan data', 'error');
        }
    } catch (error) {
        console.error('Error saving Data CC:', error);
        showNotification('Gagal menyimpan data', 'error');
    }
}

async function editDataCC(id) {
    openModalDataCC(id);
}

// ===== DATA AM FUNCTIONS =====

function openModalDataAM(id = null) {
    if (id) {
        document.getElementById('modalDataAMTitle').textContent = 'Edit Account Manager';
        loadDataAM(id);
    } else {
        document.getElementById('modalDataAMTitle').textContent = 'Tambah Account Manager';
        document.getElementById('form-data-am').reset();
        document.getElementById('data-am-id').value = '';
        document.getElementById('tabsDataAM').style.display = 'none';
        document.getElementById('btn-save-password').style.display = 'none';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalDataAM'));
    modal.show();
}

async function loadDataAM(id) {
    try {
        // Fetch data from API or from table
        // For now, we'll get from table since we have the data there
        const table = document.getElementById('table-data-am');
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const row = rows.find(r => {
            const checkbox = r.querySelector('input[type="checkbox"]');
            return checkbox && parseInt(checkbox.value) === id;
        });

        if (!row) {
            showNotification('Data tidak ditemukan', 'error');
            return;
        }

        // Set form values
        document.getElementById('data-am-id').value = id;

        // Check if user is registered (has user account)
        const statusBadge = row.querySelector('td:nth-child(7) .badge');
        const isRegistered = statusBadge && statusBadge.textContent.includes('Terdaftar');

        if (isRegistered) {
            // Show tabs for registered user
            document.getElementById('tabsDataAM').style.display = 'flex';
            document.getElementById('btn-save-password').style.display = 'inline-block';
        } else {
            // Hide tabs for unregistered user
            document.getElementById('tabsDataAM').style.display = 'none';
            document.getElementById('btn-save-password').style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading Data AM:', error);
        showNotification('Gagal memuat data', 'error');
    }
}

async function saveDataAM() {
    const id = document.getElementById('data-am-id').value;

    // Get divisi IDs from checkboxes
    const divisiCheckboxes = document.querySelectorAll('#data-am-divisi-checkboxes input:checked');
    const divisiIds = Array.from(divisiCheckboxes).map(cb => parseInt(cb.value));

    if (divisiIds.length === 0) {
        showNotification('Pilih minimal 1 divisi', 'warning');
        return;
    }

    const data = {
        nama: document.getElementById('data-am-nama').value,
        nik: document.getElementById('data-am-nik').value,
        role: document.getElementById('data-am-role').value,
        witel_id: document.getElementById('data-am-witel').value,
        telda_id: document.getElementById('data-am-telda').value || null,
        divisi_ids: divisiIds,
        primary_divisi_id: document.getElementById('data-am-primary-divisi').value
    };

    const url = id ? `/revenue-data/data-am/${id}` : '/revenue-data/data-am';
    const method = id ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalDataAM'));
            modal.hide();

            loadData();
        } else {
            showNotification(result.message || 'Gagal menyimpan data', 'error');
        }
    } catch (error) {
        console.error('Error saving Data AM:', error);
        showNotification('Gagal menyimpan data', 'error');
    }
}

async function saveAMPassword() {
    const id = document.getElementById('data-am-id').value;
    const password = document.getElementById('data-am-password').value;
    const passwordConfirm = document.getElementById('data-am-password-confirm').value;

    if (password !== passwordConfirm) {
        showNotification('Konfirmasi password tidak cocok', 'warning');
        return;
    }

    if (password.length < 8) {
        showNotification('Password minimal 8 karakter', 'warning');
        return;
    }

    try {
        const response = await fetch(`/revenue-data/data-am/${id}/password`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                password: password,
                password_confirmation: passwordConfirm
            })
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');

            // Clear password fields
            document.getElementById('data-am-password').value = '';
            document.getElementById('data-am-password-confirm').value = '';
        } else {
            showNotification(result.message || 'Gagal mengubah password', 'error');
        }
    } catch (error) {
        console.error('Error saving password:', error);
        showNotification('Gagal mengubah password', 'error');
    }
}

async function editDataAM(id) {
    openModalDataAM(id);
}

function updatePrimaryDivisiDropdown() {
    const checkboxes = document.querySelectorAll('#data-am-divisi-checkboxes input:checked');
    const dropdown = document.getElementById('data-am-primary-divisi');
    const group = document.getElementById('primary-divisi-group');

    if (checkboxes.length === 0) {
        group.style.display = 'none';
        return;
    }

    group.style.display = 'block';
    dropdown.innerHTML = '<option value="">Pilih Primary Divisi</option>';

    checkboxes.forEach(cb => {
        const label = document.querySelector(`label[for="${cb.id}"]`).textContent.trim();
        dropdown.innerHTML += `<option value="${cb.value}">${label}</option>`;
    });
}

// ===== UTILITY FUNCTIONS =====

function resetFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('filter-witel').value = '';
    document.getElementById('filter-divisi').value = '';
    document.getElementById('filter-segment').value = '';
    document.getElementById('filter-date').value = '';
    document.getElementById('filter-month').value = '';
    document.getElementById('filter-year').value = '';

    currentFilters = {
        search: '',
        witel_id: '',
        divisi_id: '',
        segment_id: '',
        periode: '',
        tipe_revenue: currentRevType,
        role: currentAMMode
    };

    currentPage = 1;
    loadData();
}

function showLoading(tabId) {
    const tableIds = {
        'tab-cc-revenue': 'table-cc-revenue',
        'tab-am-revenue': 'table-am-revenue',
        'tab-data-cc': 'table-data-cc',
        'tab-data-am': 'table-data-am'
    };

    const tbody = document.querySelector(`#${tableIds[tabId]} tbody`);
    if (tbody) {
        const colCount = tbody.closest('table').querySelectorAll('thead th').length;
        tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center"><i class="fa-solid fa-spinner fa-spin me-2"></i>Memuat data...</td></tr>`;
    }
}

function hideLoading(tabId) {
    // Loading akan dihapus saat renderTable dipanggil
}

function initializeTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => {
        new bootstrap.Tooltip(el);
    });
}

function showNotification(message, type = 'info') {
    // Simple notification implementation
    // Bisa diganti dengan library notification yang lebih baik
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };

    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 9999;
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);