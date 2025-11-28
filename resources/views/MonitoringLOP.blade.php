@extends('layouts.main')

@section('title', 'Monitoring LOP')

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/witel.css') }}">
<style>
/* Main container styles */
.main-content {
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* Header styles */
.header-dashboard {
    background-color: #13294b;
    color: white;
    padding: 25px 35px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.header-dashboard::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 30%;
    height: 100%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
    z-index: 1;
}

.header-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.header-title i {
    margin-right: 12px;
    font-size: 24px;
    background-color: rgba(255, 255, 255, 0.15);
    padding: 10px;
    border-radius: 8px;
}

.header-subtitle {
    font-size: 16px;
    opacity: 0.8;
    margin: 0;
    padding-left: 46px;
}

/* Filter container styles */
.filter-wrapper {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin: 20px 0;
    padding: 25px;
    position: relative;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filter-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    position: relative;
    padding-left: 15px;
}

.filter-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 5px;
    height: 18px;
    background-color: #1e5bb0;
    border-radius: 3px;
}

.expand-all-btn {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #1e5bb0;
    border-radius: 6px;
    padding: 8px 15px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.expand-all-btn:hover {
    background-color: #e9ecef;
    border-color: #ced4da;
}

.expand-all-btn i {
    font-size: 14px;
}

/* Filter categories layout */
.filter-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.filter-dropdown {
    flex: 1;
    min-width: 180px;
    position: relative;
}

.filter-dropdown-header {
    border: 1px solid #dee2e6;
    background-color: white;
    border-radius: 8px;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-dropdown-header:hover {
    border-color: #adb5bd;
    background-color: #f8f9fa;
}

.filter-dropdown-title {
    font-weight: 500;
    font-size: 15px;
    color: #495057;
    margin: 0;
}

.filter-selections-container {
    display: flex;
    align-items: center;
    max-width: 60%;
}

.filter-selections {
    color: #1e5bb0;
    font-size: 13px;
    margin-right: 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.filter-dropdown-content {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    width: 100%;
    background-color: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, opacity 0.3s ease, padding 0.3s ease;
    opacity: 0;
    padding: 0;
}

.filter-dropdown-content.expanded {
    max-height: 300px;
    opacity: 1;
    padding: 12px 15px;
    overflow-y: auto;
}

/* Fixed checkbox styles */
.filter-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.filter-option {
    display: flex;
    align-items: center;
    position: relative;
}

/* Simple, working checkbox styling */
.filter-checkbox {
    margin-right: 10px;
    cursor: pointer;
    width: 16px;
    height: 16px;
    accent-color: #1e5bb0;
}

.filter-checkbox-label {
    cursor: pointer;
    font-size: 14px;
    color: #495057;
}

/* Action buttons */
.filter-actions {
    display: flex;
    gap: 12px;
    margin-top: 25px;
}

.reset-button {
    padding: 10px 20px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background-color: white;
    color: #6c757d;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.reset-button:hover {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #495057;
}

.apply-button {
    padding: 10px 25px;
    border: none;
    border-radius: 6px;
    background-color: #1e5bb0;
    color: white;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(30, 91, 176, 0.2);
}

.apply-button:hover {
    background-color: #174a8c;
    box-shadow: 0 4px 8px rgba(30, 91, 176, 0.3);
}

/* Sample table styles */
.data-table-container {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 30px;
}

.data-table-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.data-table-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0;
    position: relative;
    padding-left: 15px;
}

.data-table-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 5px;
    height: 16px;
    background-color: #1e5bb0;
    border-radius: 3px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background-color: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
    font-size: 14px;
}

.data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    color: #495057;
    font-size: 14px;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.data-table tr:hover td {
    background-color: #f8f9fa;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
}

.status-active {
    background-color: #e6f7ee;
    color: #0ca678;
}

.status-pending {
    background-color: #fff3e0;
    color: #fb8c00;
}

.status-completed {
    background-color: #e3f2fd;
    color: #1e88e5;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .filter-categories {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .filter-categories {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .header-dashboard {
        padding: 20px;
    }
    
    .header-title {
        font-size: 24px;
    }
    
    .filter-wrapper {
        padding: 20px;
    }
}

@media (max-width: 576px) {
    .filter-categories {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .reset-button, .apply-button {
        width: 100%;
    }
    
    .header-title {
        font-size: 20px;
    }
    
    .header-subtitle {
        padding-left: 35px;
        font-size: 14px;
    }
}
</style>
@endsection

@section('content')
<div class="main-content">
    <!-- Header Dashboard -->
    <div class="header-dashboard">
        <h1 class="header-title">
            <i class="fas fa-signal"></i> Monitoring LOP Prioritas
        </h1>
        <p class="header-subtitle">
            Monitoring Top List Of Project Divisi RLEGS
        </p>
    </div>

    <!-- Filter Container -->
    <div class="filter-wrapper">
        <div class="filter-header">
            <h5 class="filter-title">Filter</h5>
            <button type="button" class="expand-all-btn" id="expandAllBtn">
                <i class="fas fa-expand"></i> Expand All
            </button>
        </div>
        
        <div class="filter-categories">
            <!-- Witel Filter Section -->
            <div class="filter-dropdown">
                <div class="filter-dropdown-header" data-target="witel-content">
                    <h6 class="filter-dropdown-title">Witel</h6>
                    <div class="filter-selections-container">
                        <span class="filter-selections" id="witel-selections"></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="filter-dropdown-content" id="witel-content">
                    <div class="filter-options">
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-all" name="witel" value="all">
                            <label class="filter-checkbox-label" for="witel-all">Semua Witel</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-bali" name="witel" value="BALI">
                            <label class="filter-checkbox-label" for="witel-bali">BALI</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-jatim-barat" name="witel" value="JATIM BARAT">
                            <label class="filter-checkbox-label" for="witel-jatim-barat">JATIM BARAT</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-jatim-timur" name="witel" value="JATIM TIMUR">
                            <label class="filter-checkbox-label" for="witel-jatim-timur">JATIM TIMUR</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-nusa-tenggara" name="witel" value="NUSA TENGGARA">
                            <label class="filter-checkbox-label" for="witel-nusa-tenggara">NUSA TENGGARA</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-semarang" name="witel" value="SEMARANG JATENG UTARA">
                            <label class="filter-checkbox-label" for="witel-semarang">SEMARANG JATENG UTARA</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-solo" name="witel" value="SOLO JATENG TIMUR">
                            <label class="filter-checkbox-label" for="witel-solo">SOLO JATENG TIMUR</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-suramadu" name="witel" value="SURAMADU">
                            <label class="filter-checkbox-label" for="witel-suramadu">SURAMADU</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="witel-yogya" name="witel" value="YOGYA JATENG SELATAN">
                            <label class="filter-checkbox-label" for="witel-yogya">YOGYA JATENG SELATAN</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Divisi Filter Section -->
            <div class="filter-dropdown">
                <div class="filter-dropdown-header" data-target="divisi-content">
                    <h6 class="filter-dropdown-title">Divisi</h6>
                    <div class="filter-selections-container">
                        <span class="filter-selections" id="divisi-selections"></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="filter-dropdown-content" id="divisi-content">
                    <div class="filter-options">
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="divisi-all" name="divisi" value="all">
                            <label class="filter-checkbox-label" for="divisi-all">Semua Divisi</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="divisi-dss" name="divisi" value="DSS">
                            <label class="filter-checkbox-label" for="divisi-dss">DSS</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="divisi-dps" name="divisi" value="DPS">
                            <label class="filter-checkbox-label" for="divisi-dps">DPS</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="divisi-dgs" name="divisi" value="DGS">
                            <label class="filter-checkbox-label" for="divisi-dgs">DGS</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Project Filter Section -->
            <div class="filter-dropdown">
                <div class="filter-dropdown-header" data-target="project-content">
                    <h6 class="filter-dropdown-title">Project</h6>
                    <div class="filter-selections-container">
                        <span class="filter-selections" id="project-selections"></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="filter-dropdown-content" id="project-content">
                    <div class="filter-options">
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="project-all" name="project" value="all">
                            <label class="filter-checkbox-label" for="project-all">Semua Project</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="project-win" name="project" value="WIN">
                            <label class="filter-checkbox-label" for="project-win">Project WIN</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="project-billcomp" name="project" value="Billcomp">
                            <label class="filter-checkbox-label" for="project-billcomp">Project Billcomp</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Kategori Kontrak Filter Section -->
            <div class="filter-dropdown">
                <div class="filter-dropdown-header" data-target="kontrak-content">
                    <h6 class="filter-dropdown-title">Kategori Kontrak</h6>
                    <div class="filter-selections-container">
                        <span class="filter-selections" id="kontrak-selections"></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="filter-dropdown-content" id="kontrak-content">
                    <div class="filter-options">
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="kontrak-all" name="kontrak" value="all">
                            <label class="filter-checkbox-label" for="kontrak-all">Semua Kontrak</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="kontrak-a" name="kontrak" value="Kontrak A">
                            <label class="filter-checkbox-label" for="kontrak-a">Kontrak A</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="kontrak-b" name="kontrak" value="Kontrak B">
                            <label class="filter-checkbox-label" for="kontrak-b">Kontrak B</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mitra Filter Section -->
            <div class="filter-dropdown">
                <div class="filter-dropdown-header" data-target="mitra-content">
                    <h6 class="filter-dropdown-title">Mitra</h6>
                    <div class="filter-selections-container">
                        <span class="filter-selections" id="mitra-selections"></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="filter-dropdown-content" id="mitra-content">
                    <div class="filter-options">
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="mitra-all" name="mitra" value="all">
                            <label class="filter-checkbox-label" for="mitra-all">Semua Mitra</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="mitra-a" name="mitra" value="Mitra A">
                            <label class="filter-checkbox-label" for="mitra-a">Mitra A</label>
                        </div>
                        <div class="filter-option">
                            <input type="checkbox" class="filter-checkbox" id="mitra-b" name="mitra" value="Mitra B">
                            <label class="filter-checkbox-label" for="mitra-b">Mitra B</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Action Buttons -->
        <div class="filter-actions">
            <button type="button" class="reset-button" id="resetFilterBtn">Atur Ulang</button>
            <button type="button" class="apply-button" id="applyFilterBtn">Terapkan</button>
        </div>
    </div>
    
    <!-- Sample Table -->
    <div class="data-table-container">
        <div class="data-table-header">
            <h6 class="data-table-title">Data Project</h6>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Project Name</th>
                    <th>Witel</th>
                    <th>Divisi</th>
                    <th>Kategori Kontrak</th>
                    <th>Mitra</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Project A</td>
                    <td>SURAMADU</td>
                    <td>DSS</td>
                    <td>Kontrak A</td>
                    <td>Mitra A</td>
                    <td><span class="status-badge status-active">Active</span></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Project B</td>
                    <td>NUSA TENGGARA</td>
                    <td>DPS</td>
                    <td>Kontrak B</td>
                    <td>Mitra B</td>
                    <td><span class="status-badge status-pending">Pending</span></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Project C</td>
                    <td>JATIM BARAT</td>
                    <td>DGS</td>
                    <td>Kontrak A</td>
                    <td>Mitra A</td>
                    <td><span class="status-badge status-completed">Completed</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const filterDropdownHeaders = document.querySelectorAll('.filter-dropdown-header');
    const expandAllBtn = document.getElementById('expandAllBtn');
    const resetFilterBtn = document.getElementById('resetFilterBtn');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    
    // Filter options
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    
    // Toggle filter dropdowns
    filterDropdownHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            // Close all other dropdowns
            document.querySelectorAll('.filter-dropdown-content').forEach(dropdown => {
                if (dropdown.id !== targetId && dropdown.classList.contains('expanded')) {
                    dropdown.classList.remove('expanded');
                    dropdown.previousElementSibling.querySelector('i').classList.remove('fa-chevron-up');
                    dropdown.previousElementSibling.querySelector('i').classList.add('fa-chevron-down');
                }
            });
            
            // Toggle current dropdown
            content.classList.toggle('expanded');
            
            // Toggle icon rotation
            if (content.classList.contains('expanded')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
    });
    
    // Function to collapse all filter dropdowns
    function collapseAllDropdowns() {
        const filterContents = document.querySelectorAll('.filter-dropdown-content');
        const icons = document.querySelectorAll('.filter-dropdown-header i');
        
        // Collapse all sections
        filterContents.forEach(content => {
            content.classList.remove('expanded');
        });
        
        // Update all icons
        icons.forEach(icon => {
            if (icon.classList.contains('fa-chevron-up')) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
        
        // Update expand all button
        expandAllBtn.innerHTML = '<i class="fas fa-expand"></i> Expand All';
        allExpanded = false;
    }
    
    // Expand/Collapse all functionality
    let allExpanded = false;
    
    expandAllBtn.addEventListener('click', function() {
        const filterContents = document.querySelectorAll('.filter-dropdown-content');
        const icons = document.querySelectorAll('.filter-dropdown-header i');
        
        if (allExpanded) {
            // Collapse all
            collapseAllDropdowns();
        } else {
            // Expand all
            filterContents.forEach(content => {
                content.classList.add('expanded');
            });
            
            icons.forEach(icon => {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            });
            
            this.innerHTML = '<i class="fas fa-compress"></i> Collapse All';
            allExpanded = true;
        }
    });
    
    // Handle "all" checkbox logic and update selection indicators
    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const name = this.name;
            const isAll = this.value === 'all';
            
            if (isAll && this.checked) {
                // If "all" is checked, uncheck others
                document.querySelectorAll(`input[name="${name}"]:not([value="all"])`).forEach(cb => {
                    cb.checked = false;
                });
            } else if (!isAll && this.checked) {
                // If a specific option is checked, uncheck "all"
                document.querySelector(`input[name="${name}"][value="all"]`).checked = false;
            }
            
            // If no options are selected, check "all"
            const checkedOptions = document.querySelectorAll(`input[name="${name}"]:checked`).length;
            if (checkedOptions === 0) {
                document.querySelector(`input[name="${name}"][value="all"]`).checked = true;
            }
            
            // Update selection indicators
            updateSelectionIndicator(name);
        });
    });
    
    // Function to update selection indicators
    function updateSelectionIndicator(filterName) {
        const selectedCheckboxes = document.querySelectorAll(`input[name="${filterName}"]:checked`);
        const selectionIndicator = document.getElementById(`${filterName}-selections`);
        
        if (selectedCheckboxes.length === 0 || (selectedCheckboxes.length === 1 && selectedCheckboxes[0].value === 'all')) {
            selectionIndicator.textContent = '';
        } else {
            const selectedLabels = Array.from(selectedCheckboxes).map(checkbox => {
                const label = document.querySelector(`label[for="${checkbox.id}"]`);
                return label ? label.textContent.trim() : '';
            }).filter(Boolean);
            
            if (selectedLabels.length > 2) {
                selectionIndicator.textContent = `${selectedLabels.length} dipilih`;
            } else {
                selectionIndicator.textContent = selectedLabels.join(', ');
            }
        }
    }
    
    // Reset filters
    resetFilterBtn.addEventListener('click', function() {
        filterCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Update all selection indicators
        ['witel', 'divisi', 'project', 'kontrak', 'mitra'].forEach(category => {
            updateSelectionIndicator(category);
        });
    });
    
    // Apply filters and auto-collapse
    applyFilterBtn.addEventListener('click', function() {
        // Group checkboxes by name
        const filterGroups = {};
        filterCheckboxes.forEach(checkbox => {
            if (!filterGroups[checkbox.name]) {
                filterGroups[checkbox.name] = [];
            }
            if (checkbox.checked) {
                filterGroups[checkbox.name].push(checkbox.value);
            }
        });
        
        // Update all selection indicators
        ['witel', 'divisi', 'project', 'kontrak', 'mitra'].forEach(category => {
            updateSelectionIndicator(category);
        });
        
        // Auto-collapse all dropdowns
        collapseAllDropdowns();
        
        console.log('Selected filters:', filterGroups);
        alert('Filters applied!');
    });
    
    // Initialize all selection indicators
    ['witel', 'divisi', 'project', 'kontrak', 'mitra'].forEach(category => {
        updateSelectionIndicator(category);
    });
});
</script>
@endsection