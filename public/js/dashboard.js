// Dashboard.js - Sistem monitoring revenue
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    initializeBootstrapComponents();

    // Init snackbar if not exists
    if (!document.getElementById('snackbar')) {
        const snackbar = document.createElement('div');
        snackbar.id = 'snackbar';
        document.body.appendChild(snackbar);
    }

    // ===== DASHBOARD CHART =====
    if (document.getElementById('revenueChart')) {
        initializeRevenueChart();
    }

    // ===== YEAR FILTER =====
    const applyYearFilter = document.getElementById('applyYearFilter');
    const yearFilter = document.getElementById('yearFilter');

    if (applyYearFilter && yearFilter) {
        // Event handler untuk tombol filter tahun
        applyYearFilter.addEventListener('click', function() {
            const year = yearFilter.value;
            if (year && year >= 2000 && year <= 2100) {
                filterRevenueByYear(year);
            }
        });

        // Mendukung tombol Enter pada input filter tahun
        yearFilter.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                applyYearFilter.click();
            }
        });
    }

    // ===== MONTH PICKER IMPLEMENTATION =====
    const monthNames = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    const monthCodes = [
        "01", "02", "03", "04", "05", "06",
        "07", "08", "09", "10", "11", "12"
    ];

    // Get elements
    const monthYearInput = document.getElementById('month_year_picker');
    const openMonthPickerBtn = document.getElementById('open_month_picker');
    const hiddenMonthInput = document.getElementById('bulan_month');
    const hiddenYearInput = document.getElementById('bulan_year');
    const hiddenBulanInput = document.getElementById('bulan');
    const monthPicker = document.getElementById('global_month_picker');

    // Ensure month picker is positioned at the document level
    if (monthPicker) {
        // Move it to body to ensure it's not constrained by parent elements
        document.body.appendChild(monthPicker);

        // Set initial styles for proper display
        monthPicker.style.position = 'absolute'; // Changed from fixed to absolute
        monthPicker.style.zIndex = '9999';
        monthPicker.style.display = 'none';
    }

    if ((monthYearInput || openMonthPickerBtn) && monthPicker) {
        // Set current date
        const now = new Date();
        let currentYear = now.getFullYear();
        let selectedMonth = now.getMonth();
        let selectedYear = currentYear;
        let isMonthPickerOpen = false;

        // Initialize month grid
        const monthGrid = document.getElementById('month_grid');
        const currentYearElement = document.getElementById('current_year');
        const prevYearButton = document.getElementById('prev_year');
        const nextYearButton = document.getElementById('next_year');
        const cancelButton = document.getElementById('cancel_month');
        const applyButton = document.getElementById('apply_month');

        // Set current year on initial load
        if (currentYearElement) {
            currentYearElement.textContent = currentYear;
        }

        // Year input handler - NEW FEATURE
        const yearInput = document.getElementById('year_input');
        if (yearInput) {
            yearInput.addEventListener('change', function() {
                const year = parseInt(this.value);
                if (year && !isNaN(year) && year >= 1990 && year <= 2100) {
                    currentYear = year;
                    if (currentYearElement) {
                        currentYearElement.textContent = currentYear;
                    }
                    renderMonthGrid();
                }
            });
        }

        // Generate month grid with animation effect
        function renderMonthGrid() {
            if (!monthGrid) return;

            monthGrid.innerHTML = '';

            monthNames.forEach((month, index) => {
                const monthItem = document.createElement('div');
                monthItem.className = 'month-item';

                if (selectedMonth === index && selectedYear === currentYear) {
                    monthItem.classList.add('selected');
                    monthItem.classList.add('active');
                }

                monthItem.textContent = month;
                monthItem.dataset.month = index;

                // Add small animation delay based on index
                monthItem.style.opacity = '0';
                monthItem.style.transform = 'translateY(8px)';

                monthItem.addEventListener('click', function() {
                    // Remove active/selected class from all month items
                    document.querySelectorAll('.month-item').forEach(item => {
                        item.classList.remove('selected');
                        item.classList.remove('active');
                    });

                    // Add active/selected class to selected month
                    this.classList.add('selected');
                    this.classList.add('active');

                    // Update selected month
                    selectedMonth = index;
                });

                monthGrid.appendChild(monthItem);

                // Trigger staggered animation
                setTimeout(() => {
                    monthItem.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    monthItem.style.opacity = '1';
                    monthItem.style.transform = 'translateY(0)';
                }, index * 20);
            });
        }

        // Function to position the month picker correctly
        function positionMonthPicker() {
            if (!monthYearInput || !monthPicker) return;

            const inputRect = monthYearInput.getBoundingClientRect();

            // Calculate position - adjust the 5px offset to position it closer to the input
            const topPosition = inputRect.bottom + window.scrollY + 2; // Reduced from 5px to 2px
            const leftPosition = inputRect.left + window.scrollX;

            // Set positions
            monthPicker.style.top = topPosition + 'px';
            monthPicker.style.left = leftPosition + 'px';

            // Ensure the month picker is visible in the viewport
            const viewportHeight = window.innerHeight;
            const monthPickerHeight = monthPicker.offsetHeight;

            // If the picker would go off the bottom of the screen, position it above the input instead
            if (topPosition + monthPickerHeight > viewportHeight + window.scrollY) {
                monthPicker.style.top = (inputRect.top + window.scrollY - monthPickerHeight - 2) + 'px';
            }
        }

        // Show month picker with animation
        const openMonthPicker = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Position the picker correctly
            positionMonthPicker();

            monthPicker.style.display = 'block';
            monthPicker.style.opacity = '0';
            monthPicker.style.transform = 'translateY(-10px)';

            // Trigger animation
            setTimeout(() => {
                monthPicker.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                monthPicker.style.opacity = '1';
                monthPicker.style.transform = 'translateY(0)';
            }, 10);

            isMonthPickerOpen = true;
            renderMonthGrid();

            if (currentYearElement) {
                currentYearElement.textContent = currentYear;
            }
        };

        // Register click events for both month input and open button
        if (monthYearInput) {
            monthYearInput.addEventListener('click', openMonthPicker);
        }

        if (openMonthPickerBtn) {
            openMonthPickerBtn.addEventListener('click', openMonthPicker);
        }

        // Reposition on scroll and resize
        window.addEventListener('scroll', function() {
            if (isMonthPickerOpen) {
                positionMonthPicker();
            }
        });

        window.addEventListener('resize', function() {
            if (isMonthPickerOpen) {
                positionMonthPicker();
            }
        });

        // Year navigation
        if (prevYearButton) {
            prevYearButton.addEventListener('click', function(e) {
                e.preventDefault();
                currentYear--;
                currentYearElement.textContent = currentYear;
                renderMonthGrid();
            });
        }

        if (nextYearButton) {
            nextYearButton.addEventListener('click', function(e) {
                e.preventDefault();
                currentYear++;
                currentYearElement.textContent = currentYear;
                renderMonthGrid();
            });
        }

        // Cancel month selection
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                closeMonthPicker();
            });
        }

        // Apply month selection
        if (applyButton) {
            applyButton.addEventListener('click', function() {
                const formattedDate = `${monthNames[selectedMonth]} ${currentYear}`;
                monthYearInput.value = formattedDate;

                // Set hidden inputs
                if (hiddenMonthInput) hiddenMonthInput.value = monthCodes[selectedMonth];
                if (hiddenYearInput) hiddenYearInput.value = currentYear;
                if (hiddenBulanInput) hiddenBulanInput.value = `${currentYear}-${monthCodes[selectedMonth]}`;

                // Save selected values
                selectedYear = currentYear;

                closeMonthPicker();
            });
        }

        // Close month picker with animation
        function closeMonthPicker() {
            if (!monthPicker) return;

            monthPicker.style.opacity = '0';
            monthPicker.style.transform = 'translateY(-10px)';

            setTimeout(() => {
                monthPicker.style.display = 'none';
                isMonthPickerOpen = false;
            }, 200);
        }

        // Close month picker when clicking outside
        document.addEventListener('click', function(event) {
            if (monthPicker && isMonthPickerOpen &&
                !monthPicker.contains(event.target) &&
                (event.target !== monthYearInput && event.target !== openMonthPickerBtn) &&
                !event.target.closest('.month-picker-container')) {
                closeMonthPicker();
            }
        });

        // Initialize with current month and year
        selectedMonth = now.getMonth();
        selectedYear = now.getFullYear();
        currentYear = now.getFullYear();

        if (currentYearElement) {
            currentYearElement.textContent = currentYear;
        }

        // Set initial values for hidden inputs
        if (monthYearInput) monthYearInput.value = `${monthNames[selectedMonth]} ${currentYear}`;
        if (hiddenMonthInput) hiddenMonthInput.value = monthCodes[selectedMonth];
        if (hiddenYearInput) hiddenYearInput.value = currentYear;
        if (hiddenBulanInput) hiddenBulanInput.value = `${currentYear}-${monthCodes[selectedMonth]}`;

        // Generate month grid on initial load
        renderMonthGrid();
    }

    // ====== Account Manager search functionality ======
    const accountManagerInput = document.getElementById('account_manager');
    const accountManagerIdInput = document.getElementById('account_manager_id');
    const accountManagerSuggestions = document.getElementById('account_manager_suggestions');

    if (accountManagerInput) {
        accountManagerInput.addEventListener('input', function() {
            const search = this.value.trim();

            if (search.length < 2) {
                if (accountManagerSuggestions) {
                    accountManagerSuggestions.innerHTML = '';
                    accountManagerSuggestions.style.display = 'none';
                }
                return;
            }

            fetch('/search-am?search=' + encodeURIComponent(search))
                .then(response => response.json())
                .then(data => {
                    if (!accountManagerSuggestions) return;

                    accountManagerSuggestions.innerHTML = '';

                    if (data.length === 0) {
                        const noResult = document.createElement('div');
                        noResult.className = 'suggestion-item';
                        noResult.textContent = 'Tidak ada hasil yang ditemukan';
                        accountManagerSuggestions.appendChild(noResult);
                    } else {
                        data.forEach(am => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = `${am.nama} - ${am.nik || 'NIK tidak tersedia'}`;

                            item.addEventListener('click', () => {
                                accountManagerInput.value = am.nama;
                                if (accountManagerIdInput) {
                                    accountManagerIdInput.value = am.id;
                                }
                                accountManagerSuggestions.style.display = 'none';
                            });

                            accountManagerSuggestions.appendChild(item);
                        });
                    }

                    accountManagerSuggestions.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching account managers:', error);

                    if (accountManagerSuggestions) {
                        accountManagerSuggestions.innerHTML = '';
                        const errorItem = document.createElement('div');
                        errorItem.className = 'suggestion-item text-danger';
                        errorItem.textContent = 'Error: Tidak dapat memuat data';
                        accountManagerSuggestions.appendChild(errorItem);
                        accountManagerSuggestions.style.display = 'block';
                    }
                });
        });
    }

    // ====== Corporate Customer search functionality ======
    const corporateCustomerInput = document.getElementById('corporate_customer');
    const corporateCustomerIdInput = document.getElementById('corporate_customer_id');
    const corporateCustomerSuggestions = document.getElementById('corporate_customer_suggestions');

    if (corporateCustomerInput) {
        corporateCustomerInput.addEventListener('input', function() {
            const search = this.value.trim();

            if (search.length < 2) {
                if (corporateCustomerSuggestions) {
                    corporateCustomerSuggestions.innerHTML = '';
                    corporateCustomerSuggestions.style.display = 'none';
                }
                return;
            }

            fetch('/search-customer?search=' + encodeURIComponent(search))
                .then(response => response.json())
                .then(data => {
                    if (!corporateCustomerSuggestions) return;

                    corporateCustomerSuggestions.innerHTML = '';

                    if (data.length === 0) {
                        const noResult = document.createElement('div');
                        noResult.className = 'suggestion-item';
                        noResult.textContent = 'Tidak ada hasil yang ditemukan';
                        corporateCustomerSuggestions.appendChild(noResult);
                    } else {
                        data.forEach(cc => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = `${cc.nama} - NIPNAS: ${cc.nipnas || 'Tidak tersedia'}`;

                            item.addEventListener('click', () => {
                                corporateCustomerInput.value = cc.nama;
                                if (corporateCustomerIdInput) {
                                    corporateCustomerIdInput.value = cc.id;
                                }
                                corporateCustomerSuggestions.style.display = 'none';
                            });

                            corporateCustomerSuggestions.appendChild(item);
                        });
                    }

                    corporateCustomerSuggestions.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching corporate customers:', error);

                    if (corporateCustomerSuggestions) {
                        corporateCustomerSuggestions.innerHTML = '';
                        const errorItem = document.createElement('div');
                        errorItem.className = 'suggestion-item text-danger';
                        errorItem.textContent = 'Error: Tidak dapat memuat data';
                        corporateCustomerSuggestions.appendChild(errorItem);
                        corporateCustomerSuggestions.style.display = 'block';
                    }
                });
        });
    }

    // ====== Clear suggestions when clicking outside ======
    document.addEventListener('click', function(event) {
        const amSuggestions = document.getElementById('account_manager_suggestions');
        const ccSuggestions = document.getElementById('corporate_customer_suggestions');

        if (accountManagerInput && amSuggestions && !accountManagerInput.contains(event.target) && !amSuggestions.contains(event.target)) {
            amSuggestions.style.display = 'none';
        }

        if (corporateCustomerInput && ccSuggestions && !corporateCustomerInput.contains(event.target) && !ccSuggestions.contains(event.target)) {
            ccSuggestions.style.display = 'none';
        }
    });

    // ====== Tab switching functionality ======
    document.querySelectorAll('.tab-item').forEach(tab => {
        tab.addEventListener('click', function() {
            // Find the closest tab container
            const tabContainer = this.closest('.tab-menu-container');
            const parentContainer = tabContainer.parentElement;
            let contentContainer;

            // Check if parent is modal body, or use parent container
            if (parentContainer.classList.contains('modal-body')) {
                contentContainer = parentContainer;
            } else {
                contentContainer = tabContainer.parentElement;
            }

            // Remove active class from all tabs in this container
            tabContainer.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));

            // Add active class to clicked tab
            this.classList.add('active');

            // Hide all tab contents in this container
            contentContainer.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Show the selected tab content
            const targetId = this.getAttribute('data-tab');
            const targetContent = contentContainer.querySelector(`#${targetId}`);
            if (targetContent) {
                targetContent.classList.add('active');

                // Add a small animation
                targetContent.style.opacity = '0';
                targetContent.style.transform = 'translateY(10px)';

                setTimeout(() => {
                    targetContent.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    targetContent.style.opacity = '1';
                    targetContent.style.transform = 'translateY(0)';
                }, 10);
            }
        });
    });

    // ====== Delete confirmation ======
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                event.preventDefault();
            }
        });
    });

    // ====== Filter toggle ======
    const filterToggle = document.getElementById('filterToggle');
    const filterArea = document.getElementById('filterArea');

    if (filterToggle && filterArea) {
        filterToggle.addEventListener('click', function() {
            if (filterArea.style.display === 'none') {
                // Show with animation
                filterArea.style.display = 'block';
                filterArea.style.opacity = '0';
                filterArea.style.maxHeight = '0';

                setTimeout(() => {
                    filterArea.style.transition = 'opacity 0.3s ease, max-height 0.3s ease';
                    filterArea.style.opacity = '1';
                    filterArea.style.maxHeight = '500px'; // Adjust as needed
                }, 10);

                filterToggle.classList.add('active');
            } else {
                // Hide with animation
                filterArea.style.opacity = '0';
                filterArea.style.maxHeight = '0';

                setTimeout(() => {
                    filterArea.style.display = 'none';
                }, 300);

                filterToggle.classList.remove('active');
            }
        });
    }

    // ====== Function to show snackbar notifications ======
    window.showSnackbar = function(message, type = 'info') {
        const snackbar = document.getElementById('snackbar');
        if (!snackbar) return;

        snackbar.textContent = message;

        // Remove existing classes
        snackbar.classList.remove('hidden', 'show', 'success', 'error', 'info');

        // Add appropriate classes
        snackbar.classList.add('show', type);

        // Hide the snackbar after 3 seconds
        setTimeout(() => {
            snackbar.classList.remove('show');
            snackbar.classList.add('hidden');
        }, 3000);
    };

    // Show snackbar if URL has success parameter
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    if (successMsg) {
        window.showSnackbar(decodeURIComponent(successMsg), 'success');
    }

    // ====== AJAX Form submissions ======
    const setupFormSubmission = (formId, actionOnSuccess) => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSnackbar(data.message || 'Data berhasil disimpan!', 'success');
                        if (actionOnSuccess) actionOnSuccess(this, data);
                    } else {
                        showSnackbar(data.message || 'Gagal menyimpan data.', 'error');
                    }
                })
                .catch(error => {
                    showSnackbar('Terjadi kesalahan saat menyimpan data.', 'error');
                    console.error('Error:', error);
                });
            });
        }
    };

    // Setup form submissions
    setupFormSubmission('revenueForm', (form, data) => {
        setTimeout(() => window.location.reload(), 1500);
    });

    setupFormSubmission('amForm', (form, data) => {
        form.reset();
        document.querySelector('#addAccountManagerModal .btn-close')?.click();
        setTimeout(() => window.location.reload(), 1500);
    });

    setupFormSubmission('amImportForm', (form, data) => {
        form.reset();
        document.querySelector('#addAccountManagerModal .btn-close')?.click();
        setTimeout(() => window.location.reload(), 1500);
    });

    setupFormSubmission('ccForm', (form, data) => {
        form.reset();
        document.querySelector('#addCorporateCustomerModal .btn-close')?.click();
        setTimeout(() => window.location.reload(), 1500);
    });

    setupFormSubmission('ccImportForm', (form, data) => {
        form.reset();
        document.querySelector('#addCorporateCustomerModal .btn-close')?.click();
        setTimeout(() => window.location.reload(), 1500);
    });

    setupFormSubmission('revenueImportForm', (form, data) => {
        form.reset();
        document.querySelector('#importRevenueModal .btn-close')?.click();
        setTimeout(() => window.location.reload(), 1500);
    });

    // ====== Helper Functions ======

    // Fungsi untuk memfilter revenue berdasarkan tahun (di dashboard)
    function filterRevenueByYear(year) {
        const monthlyRevenueTable = document.getElementById('monthlyRevenueTable');
        if (!monthlyRevenueTable) return;

        // Tampilkan loading state
        monthlyRevenueTable.querySelector('tbody').innerHTML = '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin fs-4"></i> Loading data...</td></tr>';

        fetch('/dashboard/revenues?year=' + year)
            .then(response => response.json())
            .then(response => {
                updateRevenueTable(response.data, response.year);
            })
            .catch(error => {
                // Tampilkan pesan error
                monthlyRevenueTable.querySelector('tbody').innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle fs-4 mb-2"></i><br>Gagal memuat data. Silakan coba lagi.</td></tr>';
                console.error('Error fetching revenue data:', error);
            });
    }

    // Fungsi untuk mengupdate tabel revenue
    function updateRevenueTable(data, year) {
        const monthlyRevenueTable = document.getElementById('monthlyRevenueTable');
        if (!monthlyRevenueTable) return;

        const months = {
            1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
            5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
            9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember'
        };

        let tableHtml = '';

        if (data.length > 0) {
            data.forEach(function(revenue) {
                const achievement = revenue.target > 0
                    ? Math.round((revenue.realisasi / revenue.target) * 100 * 10) / 10
                    : 0;

                const statusClass = achievement >= 100
                    ? 'bg-success-soft'
                    : (achievement >= 80 ? 'bg-warning-soft' : 'bg-danger-soft');

                const statusIcon = achievement >= 100
                    ? 'check-circle'
                    : (achievement >= 80 ? 'clock' : 'times-circle');

                const iconColorClass = achievement >= 100
                    ? 'text-success'
                    : (achievement >= 80 ? 'text-warning' : 'text-danger');

                tableHtml += `
                <tr>
                    <td>${months[revenue.month] || 'Unknown'}</td>
                    <td class="text-end">Rp ${formatNumber(revenue.target)}</td>
                    <td class="text-end">Rp ${formatNumber(revenue.realisasi)}</td>
                    <td class="text-end">
                        <span class="status-badge ${statusClass}">${achievement}%</span>
                    </td>
                    <td class="text-center">
                        <i class="fas fa-${statusIcon} ${iconColorClass}"></i>
                    </td>
                </tr>
                `;
            });
        } else {
            tableHtml = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-chart-bar fs-4 d-block mb-2"></i>
                    Tidak ada data revenue untuk tahun ${year}
                </td>
            </tr>
            `;
        }

        monthlyRevenueTable.querySelector('tbody').innerHTML = tableHtml;

        // Update judul filter jika ada
        const yearFilterTitle = document.getElementById('yearFilterTitle');
        if (yearFilterTitle) {
            yearFilterTitle.textContent = yearFilterTitle.textContent.replace(/\(\d+\)/, `(${year})`);
        }
    }

    // Format angka dengan separator ribuan
    function formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    // Initialize Revenue Chart if exists
    function initializeRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        // Data untuk chart sudah tersedia sebagai variabel PHP yang di-inject ke JavaScript
        // Misalnya: const chartData = <?= json_encode($chartData) ?>;
        // Di sini kita asumsikan data sudah tersedia dari window.chartData

        if (typeof window.chartData !== 'undefined') {
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: window.chartData.labels,
                    datasets: [
                        {
                            label: 'Target Revenue',
                            data: window.chartData.targets,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Real Revenue',
                            data: window.chartData.realisasi,
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + formatNumber(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + formatNumber(context.raw);
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Initialize Bootstrap components
    function initializeBootstrapComponents() {
        // Initialize all dropdowns
        const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
        if (dropdownElementList.length > 0) {
            try {
                const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                    return new bootstrap.Dropdown(dropdownToggleEl);
                });
            } catch (e) {
                console.error('Bootstrap initialization error:', e);
            }
        }

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        if (tooltipTriggerList.length > 0) {
            try {
                const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            } catch (e) {
                console.error('Bootstrap tooltip initialization error:', e);
            }
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap dropdown for profile menu
    const profileDropdown = document.querySelector('#navbarDropdown');
    if (profileDropdown) {
        profileDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const dropdownMenu = this.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                // Toggle dropdown
                dropdownMenu.classList.toggle('show');
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.setAttribute('data-bs-popper', 'static');
                } else {
                    dropdownMenu.removeAttribute('data-bs-popper');
                }
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileDropdown.contains(e.target)) {
                const dropdownMenu = profileDropdown.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Custom handling for month picker positioning
    const monthYearInput = document.getElementById('month_year_picker');
    const openMonthPickerBtn = document.getElementById('open_month_picker');
    const monthPicker = document.getElementById('global_month_picker');

    // Ensure month picker is at document level for best positioning
    if (monthPicker && !document.body.contains(monthPicker)) {
        document.body.appendChild(monthPicker);
    }

    if (monthPicker) {
        // Set initial styles for proper positioning
        monthPicker.style.position = 'absolute'; // Changed from fixed to absolute for better positioning
        monthPicker.style.zIndex = '999999'; // Super high z-index to ensure it's on top
        monthPicker.style.display = 'none';
        // Add drop shadow for better visual appearance
        monthPicker.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.2)';
        // Round corners for better appearance
        monthPicker.style.borderRadius = '8px';
    }

    // Function to position the month picker relative to its input
    function positionMonthPicker() {
        if (!monthYearInput || !monthPicker) return;

        const inputRect = monthYearInput.getBoundingClientRect();

        // Position closer to the input field (adjust this value as needed)
        const topOffset = 2; // Reduced from 5px to 2px to appear closer

        // Calculate position
        const topPosition = inputRect.bottom + window.scrollY + topOffset;
        const leftPosition = inputRect.left + window.scrollX;

        // Set positions
        monthPicker.style.top = topPosition + 'px';
        monthPicker.style.left = leftPosition + 'px';

        // Check if the picker would go off the viewport at the bottom
        const viewportHeight = window.innerHeight;
        const pickerHeight = monthPicker.offsetHeight || 350; // Fallback height if not rendered yet

        // If it would go off the bottom, position it above the input instead
        if (topPosition + pickerHeight > viewportHeight + window.scrollY) {
            monthPicker.style.top = (inputRect.top + window.scrollY - pickerHeight - topOffset) + 'px';
        }

        // Ensure it's not positioned off-screen to the right
        const viewportWidth = window.innerWidth;
        const pickerWidth = monthPicker.offsetWidth || 350; // Fallback width

        if (leftPosition + pickerWidth > viewportWidth) {
            // Align with right edge of the input
            monthPicker.style.left = (inputRect.right - pickerWidth + window.scrollX) + 'px';
        }
    }

    // Override the month picker opening function
    if (monthYearInput && openMonthPickerBtn && monthPicker) {
        const customOpenMonthPicker = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Position the month picker properly
            positionMonthPicker();

            // Show with animation
            monthPicker.style.display = 'block';
            monthPicker.style.opacity = '0';
            monthPicker.style.transform = 'translateY(-5px)'; // Reduced from -10px for subtler animation

            // Apply animation
            setTimeout(() => {
                monthPicker.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                monthPicker.style.opacity = '1';
                monthPicker.style.transform = 'translateY(0)';
            }, 10);
        };

        // Add our improved event listeners
        monthYearInput.addEventListener('click', customOpenMonthPicker);
        openMonthPickerBtn.addEventListener('click', customOpenMonthPicker);

        // Handle window resize and scroll to reposition the picker
        window.addEventListener('resize', function() {
            if (monthPicker.style.display === 'block') {
                positionMonthPicker();
            }
        });

        window.addEventListener('scroll', function() {
            if (monthPicker.style.display === 'block') {
                positionMonthPicker();
            }
        });
    }


});




