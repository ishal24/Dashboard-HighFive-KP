@extends('layouts.main')

@section('title', 'Revenue RLEGS')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/revenue.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Flatpickr styles (core) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
         <form class="searchbar" action="#" method="GET" onsubmit="return false;">
                <input type="search" class="search-input" placeholder="Cari data...">
                <button class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        <div class="filter-group">
            <label>Witel</label>
            <select class="form-select">
                <option value="">Semua Witel</option>
                <option>Witel Bali</option>
                <option>Witel Jatim Barat</option>
                <option>Witel Jatim Timur</option>
                <option>Witel Nusa Tenggara</option>
                <option>Witel Semarang Jateng Utara</option>
                <option>Witel Solo Jateng Timur</option>
                <option>Witel Suramadu</option>
                <option>Witel Yogya Jateng Selatan</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Divisi</label>
            <select class="form-select">
                <option value="">Semua Divisi</option>
                <option>DGS</option>
                <option>DPS</option>
                <option>DSS</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Segment</label>

            <!-- Select asli (tetap ada untuk submit & nilai) -->
            <select class="form-select" id="filter-segment" name="segment">
                <option value="">Semua Segment</option>
                <!-- nilai akan diisi via JS; opsi statis ini hanya fallback -->
            </select>

            <!-- UI custom bertab -->
            <div class="seg-select" id="segSelect">
                {{-- <button type="button" class="seg-select__btn" id="segBtn">
                <span id="segBtnLabel">Semua Segment</span>
                <i class="fa-solid fa-chevron-down seg-select__caret" aria-hidden="true"></i>
                </button> --}}

                <div class="seg-menu" id="segMenu" role="listbox" aria-labelledby="segBtn">
                <div class="seg-tabs" role="tablist" aria-label="Divisi">
                    <button class="seg-tab active" data-tab="DPS" role="tab" aria-selected="true">DPS</button>
                    <button class="seg-tab" data-tab="DSS" role="tab" aria-selected="false">DSS</button>
                    <button class="seg-tab" data-tab="DGS" role="tab" aria-selected="false">DGS</button>
                </div>

                <div class="seg-panels">
                    <div class="seg-panel active" data-panel="DPS" role="tabpanel">
                    <button class="seg-option all" data-value="">Semua Segment</button>
                    <button class="seg-option" data-value="FWS">FWS</button>
                    <button class="seg-option" data-value="LMS">LMS</button>
                    <button class="seg-option" data-value="PBS">PBS</button>
                    <button class="seg-option" data-value="RMS">RMS</button>
                    <button class="seg-option" data-value="PCS">PCS</button>
                    <button class="seg-option" data-value="PRS">PRS</button>
                    </div>

                    <div class="seg-panel" data-panel="DSS" role="tabpanel">
                    <button class="seg-option all" data-value="">Semua Segment</button>
                    <button class="seg-option" data-value="ERS">ERS</button>
                    <button class="seg-option" data-value="FRBS">FRBS</button>
                    <button class="seg-option" data-value="MIS">MIS</button>
                    <button class="seg-option" data-value="TWS">TWS</button>
                    <button class="seg-option" data-value="SBS">SBS</button>
                    </div>

                    <div class="seg-panel" data-panel="DGS" role="tabpanel">
                    <button class="seg-option all" data-value="">Semua Segment</button>
                    <button class="seg-option" data-value="GPS">GPS</button>
                    <button class="seg-option" data-value="GDS">GDS</button>
                    <button class="seg-option" data-value="GIS">GIS</button>
                    <button class="seg-option" data-value="GRS">GRS</button>
                    </div>
                </div>
                </div>
            </div>
        </div>


        <!-- === Periode: Datepicker (kalender harian) === -->
        <div class="filter-group">
            <label>Periode</label>
            <input type="text" id="filter-date" class="form-control datepicker-control" placeholder="Pilih bulan & tahun" autocomplete="off">
            <input type="hidden" id="filter-month" name="month" value="{{ date('m') }}">
            <input type="hidden" id="filter-year"  name="year"  value="{{ date('Y') }}">
        </div>


        <div class="filter-actions">
            <button class="btn btn-light" id="btn-reset-filter"><i class="fa-solid fa-rotate me-2"></i>Reset</button>
            <button class="btn btn-primary" id="btn-apply-filter"><i class="fa-solid fa-filter me-2"></i>Terapkan</button>
        </div>
    </div>

    <!-- ===== Tabs ===== -->
    <div class="tabs card-shadow">
        <button class="tab-btn active" data-tab="tab-cc-revenue">
            <i class="fa-solid fa-chart-line me-2"></i>Revenue CC
            <span class="badge neutral" id="badge-cc-rev">5</span>
        </button>
        <button class="tab-btn" data-tab="tab-am-revenue">
            <i class="fa-solid fa-user-tie me-2"></i>Revenue AM
            <span class="badge neutral" id="badge-am-rev">3</span>
        </button>
        <button class="tab-btn" data-tab="tab-data-cc">
            <i class="fa-solid fa-building me-2"></i>Data CC
            <span class="badge neutral" id="badge-cc">4</span>
        </button>
    </div>

    <!-- ===== Tab: Revenue CC ===== -->
    <div id="tab-cc-revenue" class="tab-panel active card-shadow">
        <div class="panel-header">
            <div class="left">
                <h3>Revenue Corporate Customer</h3>
                <p class="muted">Gunakan <i>option button</i> untuk melihat kategori Revenue CC</p>
            </div>
            <div class="btn-segmentation" role="group" aria-label="Revenue Type">
                <button class="seg-btn active" data-revtype="reg">Reguler</button>
                <button class="seg-btn" data-revtype="ngtma">NGTMA</button>
                <button class="seg-btn" data-revtype="komb">Kombinasi</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th>Nama CC</th>
                        <th>Divisi</th>
                        <th>Segment</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted"
                               data-bs-toggle="tooltip"
                               title="Nilai ini menampilkan Revenue sesuai kategori (Reguler/NGTMA/Kombinasi). Hover pada angka untuk detail: Revenue Sold/Bill."></i>
                        </th>
                        <th>Bulan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PT Telkom Indonesia</td>
                        <td><span class="badge-div dps">DPS</span></td>
                        <td>Government</td>
                        <td class="text-end">Rp 1.000.000.000</td>
                        <td class="text-end"><span class="rev-val" data-bs-toggle="tooltip" title="Revenue Bill">Rp 1.200.000.000</span></td>
                        <td>Jan 2025</td>
                        <td class="text-center">
                            <button class="icon-btn edit" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="icon-btn delete" title="Hapus"><i class="fa-regular fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>PT Indosat Tbk</td>
                        <td><span class="badge-div dgs">DGS</span></td>
                        <td>Enterprise</td>
                        <td class="text-end">Rp 800.000.000</td>
                        <td class="text-end"><span class="rev-val" data-bs-toggle="tooltip" title="Revenue Sold">Rp 650.000.000</span></td>
                        <td>Jan 2025</td>
                        <td class="text-center">
                            <button class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <div class="info">Menampilkan 1–5 dari 5 hasil</div>
            <div class="pages">
                <button class="pager disabled"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="pager active">1</button>
                <button class="pager disabled"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option selected>25</option>
                    <option>50</option>
                    <option>75</option>
                    <option>100</option>
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
            <div class="am-toggles">
                <div class="btn-toggle" data-role="amMode">
                    <button class="am-btn active" data-mode="am">AM</button>
                    <button class="am-btn" data-mode="hotda">HOTDA</button>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table modern" id="table-am">
                <thead>
                    <tr>
                        <th>Nama AM</th>
                        <th>Divisi</th>
                        <th>Corporate Customer</th>
                        <th class="text-end">Target Revenue</th>
                        <th class="text-end">
                            Revenue
                            <i class="fa-regular fa-circle-question ms-1 text-muted"
                               data-bs-toggle="tooltip"
                               title="Nilai revenue mengikuti mode (AM/HOTDA)."></i>
                        </th>
                        <th class="text-end">Achievement</th>
                        <th>Bulan</th>
                        <th class="hotda-col d-none">TELDA</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>John Doe</td>
                        <td><span class="badge-div dss">DSS</span></td>
                        <td>PT Example A</td>
                        <td class="text-end">Rp 500.000.000</td>
                        <td class="text-end"><span data-bs-toggle="tooltip" title="Revenue Bill">Rp 600.000.000</span></td>
                        <td class="text-end"><span class="achv good">120.0%</span></td>
                        <td>Jan 2025</td>
                        <td class="hotda-col d-none">TELDA-01</td>
                        <td class="text-center">
                            <button class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <div class="info">Menampilkan 1–3 dari 3 hasil</div>
            <div class="pages">
                <button class="pager disabled"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="pager active">1</button>
                <button class="pager disabled"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option>10</option>
                    <option selected>15</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
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
        </div>

        <div class="table-wrap">
            <table class="table modern">
                <thead>
                    <tr>
                        <th>Nama CC</th>
                        <th>NIPNAS</th>
                        <th>Divisi</th>
                        <th>Segment</th>
                        <th>Witel</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PT Telkom Indonesia</td>
                        <td>123456789</td>
                        <td><span class="badge-div dps">DPS</span></td>
                        <td>Government</td>
                        <td>Witel Surabaya</td>
                        <td class="text-center">
                            <button class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>PT Indosat Tbk</td>
                        <td>987654321</td>
                        <td><span class="badge-div dgs">DGS</span></td>
                        <td>Enterprise</td>
                        <td>Witel Malang</td>
                        <td class="text-center">
                            <button class="icon-btn edit"><i class="fa-regular fa-pen-to-square"></i></button>
                            <button class="icon-btn delete"><i class="fa-regular fa-trash-can"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <div class="info">Menampilkan 1–4 dari 4 hasil</div>
            <div class="pages">
                <button class="pager disabled"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="pager active">1</button>
                <button class="pager disabled"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="perpage">
                <label>Baris</label>
                <select class="form-select small">
                    <option>10</option>
                    <option selected>15</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </div>
        </div>
    </div>

</div>

<!-- =================== IMPORT MODAL (4 opsi) =================== -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content import-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel"><i class="fa-solid fa-file-import me-2"></i>Import Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <!-- Opsi Button Group -->
        <div class="import-type-switch">
            <button class="type-btn active" data-imp="imp-cc">Data CC</button>
            <button class="type-btn" data-imp="imp-am">Data AM</button>
            <button class="type-btn" data-imp="imp-rev-cc">Revenue CC</button>
            <button class="type-btn" data-imp="imp-rev-map">Revenue AM (Mapping)</button>
        </div>

        <!-- ====== Form: Data CC ====== -->
        <div id="imp-cc" class="imp-panel active">
            <div class="subswitch">
                <span class="label">Jenis File:</span>
                <div class="btn-group">
                    <button class="sub-btn active" data-sub="rev">Data Revenue</button>
                    <button class="sub-btn" data-sub="target">Data Target</button>
                </div>
            </div>

            <form action="#" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                @csrf
                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" accept=".csv">
                    </div>
                </div>

                <div class="alert note mt-3">
                    <strong>Ketentuan file:</strong>
                    <ul class="mb-0">
                        <li>Jika <em>Data Revenue CC</em> → wajib terdapat kolom <strong>Divisi</strong>.</li>
                        <li>Jika <em>Data Target CC</em> → wajib terdapat kolom: <strong>Nama CC, NIPNAS, Divisi, Segment, Target</strong>.</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

        <!-- ====== Form: Data AM ====== -->
        <div id="imp-am" class="imp-panel">
            <form action="#" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                @csrf
                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" accept=".csv">
                        <small class="text-muted">Kolom wajib: <strong>Nama AM, NIK, Witel, Divisi</strong>.</small>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

        <!-- ====== Form: Revenue CC ====== -->
        <div id="imp-rev-cc" class="imp-panel">
            <form action="#" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                @csrf
                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" accept=".csv">
                    </div>

                    <!-- tambahkan wrapper kolom di sini -->
                    <div class="col-md-6">
                        <div class="filter-group">
                        <label>Divisi</label>
                        <select class="form-select">
                            <option value="">Semua Divisi</option>
                            <option>DGS</option>
                            <option>DPS</option>
                            <option>DSS</option>
                        </select>
                        </div>
                    </div>
                </div>


                <div class="mt-3">
                    <button class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

        <!-- ====== Form: Revenue AM (Mapping) ====== -->
        <div id="imp-rev-map" class="imp-panel">
            <form action="#" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                @csrf
                <div class="row gx-3 gy-3">
                    <div class="col-md-6">
                        <label class="form-label">Unggah File (.csv)</label>
                        <input type="file" class="form-control" accept=".csv">
                        <small class="text-muted">Kolom wajib: </br> <strong>NIPNAS CC, Nama CC, Divisi, Segmen, NIK AM, Witel HO, Divisi AM, Posisi, Telda, dan Proporsi</strong>.</small>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary"><i class="fa-solid fa-upload me-2"></i>Import</button>
                </div>
            </form>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const dateInput   = document.getElementById('filter-date');
  const hiddenMonth = document.getElementById('filter-month');
  const hiddenYear  = document.getElementById('filter-year');

  if (!dateInput) return;

  // Tahun dinamis - otomatis update setiap tahun
  const currentYear = new Date().getFullYear();
  let selectedYear  = currentYear;
  let selectedMonth = new Date().getMonth();

  // === WINDOW TAHUN DINAMIS (NOW ... NOW-5), MINIMAL 2020 ===
  const YEAR_FLOOR = 2020;
  function getYearWindow() {
    const nowY = new Date().getFullYear();
    const start = nowY;                         // tahun terbaru = tahun sekarang
    const end   = Math.max(YEAR_FLOOR, nowY - 5); // 5 tahun ke belakang, minimal 2020
    return { start, end };
  }
  function clampSelectedYear() {
    const { start, end } = getYearWindow();
    if (selectedYear > start) selectedYear = start;
    if (selectedYear < end)   selectedYear = end;
  }

  let isYearView = false; // Track current view
  let fpInstance = null;  // Store instance

  // === Tambahan: sinkronisasi lebar popup dengan input trigger ===
  function getTriggerEl(instance){
    // Saat altInput:true, elemen yang terlihat adalah instance.altInput
    return instance?.altInput || dateInput;
  }
  function syncCalendarWidth(instance){
    try{
      const cal = instance.calendarContainer;
      const trigger = getTriggerEl(instance);
      if (!cal || !trigger) return;

      const rect = trigger.getBoundingClientRect();
      const w = Math.round(rect.width);

      // Kunci lebar popup agar sama persis dengan input
      cal.style.boxSizing = 'border-box';
      cal.style.width     = w + 'px';
      cal.style.maxWidth  = w + 'px';
      // (opsional) pastikan tidak kepotong di viewport sangat kecil
      // cal.style.maxWidth = Math.min(window.innerWidth * 0.96, w) + 'px';
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

      // ⬇️ Samakan lebar popup saat siap
      syncCalendarWidth(instance);

      setupCustomUI(instance);
    },

    onOpen(selectedDates, value, instance) {
      fpInstance = instance;
      isYearView = false;

      clampSelectedYear();
      renderMonthView(instance);

      // ⬇️ Samakan lebar popup saat dibuka
      syncCalendarWidth(instance);

      setTimeout(() => {
        const activeMonth = instance.calendarContainer.querySelector('.fp-month-option.selected');
        if (activeMonth) {
          activeMonth.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 100);
    }
  });

  // Responsif: saat window di-resize & popup terbuka, lebar tetap mengikuti input
  window.addEventListener('resize', () => {
    if (fpInstance && fpInstance.isOpen) {
      syncCalendarWidth(fpInstance);
    }
  });

  function setupCustomUI(instance) {
    const cal = instance.calendarContainer;

    // Sembunyikan default month select
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

    // ⬇️ agar bisa fokus & scroll dengan keyboard/trackpad
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
        setTimeout(() => fp.close(), 150);
      });

      container.appendChild(btn);
    });

    // ⬇️ auto-scroll supaya bulan terpilih tampak di tengah grid
    const activeMonth = container.querySelector('.fp-month-option.selected');
    if (activeMonth) {
      activeMonth.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function renderYearView(instance) {
    const cal = instance.calendarContainer;
    const header = cal.querySelector('.flatpickr-current-month');

    if (header) {
      // Header untuk year view dengan tombol back
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

    // Buat year grid
    container.innerHTML = '';
    container.className = 'fp-year-grid';

    // === Gunakan window dinamis (NOW ... NOW-5, minimal 2020) ===
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

        // Update selected year
        selectedYear = y;

        // Update hidden input
        hiddenYear.value = selectedYear;

        // Kembali ke month view setelah pilih tahun
        isYearView = false;
        renderMonthView(instance);
      });

      container.appendChild(btn);
    }

    // Auto scroll ke tahun aktif
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
      selectedYear  = now.getFullYear();
      selectedMonth = now.getMonth();

      clampSelectedYear();

      fp.setDate(now, true);
      hiddenMonth.value = String(selectedMonth + 1).padStart(2, '0');
      hiddenYear.value  = selectedYear;
    });
  }

  /* -----------------------
   * 2) Tabs sederhana
   * ----------------------- */
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const panel = document.getElementById(btn.dataset.tab);
      if (panel) panel.classList.add('active');
    });
  });

  /* -------------------------------------
   * 3) Revenue type switch (UI only)
   * ------------------------------------- */
  document.querySelectorAll('.seg-btn').forEach(b => {
    b.addEventListener('click', () => {
      document.querySelectorAll('.seg-btn').forEach(x => x.classList.remove('active'));
      b.classList.add('active');
    });
  });

  /* ---------------------------------------
   * 4) AM / HOTDA toggle (show TELDA col)
   * --------------------------------------- */
  const amButtons = document.querySelectorAll('.am-btn');
  const hotdaColsHeader = document.querySelectorAll('#table-am thead .hotda-col');
  const hotdaColsBody   = document.querySelectorAll('#table-am tbody .hotda-col');
  amButtons.forEach(b => {
    b.addEventListener('click', () => {
      amButtons.forEach(x => x.classList.remove('active'));
      b.classList.add('active');
      const show = (b.dataset.mode === 'hotda');
      hotdaColsHeader.forEach(td => td.classList.toggle('d-none', !show));
      hotdaColsBody.forEach(td   => td.classList.toggle('d-none', !show));
    });
  });

  /* --------------------------------
   * 5) Import modal – tab switch
   * -------------------------------- */
  const typeButtons = document.querySelectorAll('.type-btn');
  const impPanels   = document.querySelectorAll('.imp-panel');
  typeButtons.forEach(tb => {
    tb.addEventListener('click', () => {
      typeButtons.forEach(x => x.classList.remove('active'));
      impPanels.forEach(p => p.classList.remove('active'));
      tb.classList.add('active');
      const target = document.getElementById(tb.dataset.imp);
      if (target) target.classList.add('active');
    });
  });

  /* ------------------------------------------------
   * 6) Data CC subswitch (Revenue / Target)
   * ------------------------------------------------ */
  const subBtns   = document.querySelectorAll('#imp-cc .sub-btn');
  const subTarget = document.querySelector('#imp-cc .sub-target');
  subBtns.forEach(sb => {
    sb.addEventListener('click', () => {
      subBtns.forEach(x => x.classList.remove('active'));
      sb.classList.add('active');
      const isTarget = sb.dataset.sub === 'target';
      if (subTarget) subTarget.classList.toggle('d-none', !isTarget);
    });
  });

  /* -----------------------------
   * 7) Bootstrap tooltips (opsi)
   * ----------------------------- */
  if (window.bootstrap?.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  /* ---------------------------------------
   * 8) Apply filter (UI-only console log)
   * --------------------------------------- */
  const applyBtn = document.getElementById('btn-apply-filter');
  if (applyBtn) {
    applyBtn.addEventListener('click', () => {
      console.log('Apply filter', {
        witel:   document.querySelectorAll('.filters .form-select')[0]?.value || '',
        divisi:  document.querySelectorAll('.filters .form-select')[1]?.value || '',
        segment: document.querySelectorAll('.filters .form-select')[2]?.value || '',
        month:   document.getElementById('filter-month')?.value || '',
        year:    document.getElementById('filter-year')?.value || '',
      });
    });
  }

  /* -----------------------------------------------------------------
   * 9) NEW: Segment dropdown dibagi 3 section + sinkron dengan Divisi
   * ----------------------------------------------------------------- */
  (function setupSegmentSections(){
    // Ambil select Divisi & Segment berdasarkan urutan di .filters
    const divisionSelect = document.querySelectorAll('.filters .form-select')[1];
    const segmentSelect  = document.querySelectorAll('.filters .form-select')[2];
    if (!divisionSelect || !segmentSelect) return;

    // Definisi mapping segment per divisi
    const SEGMENTS = {
      DPS: ['FWS','LMS','PBS','RMS','PCS','PRS'],
      DSS: ['ERS','FRBS','MIS','TWS','SBS'],
      DGS: ['GPS','GDS','GIS','GRS']
    };

    // Simpan pilihan awal (jika ada)
    let initialValue = segmentSelect.value || '';

    // Helper: buat option element
    const mkOpt = (v, label=v) => {
      const o = document.createElement('option');
      o.value = v; o.textContent = label;
      return o;
    };

    // Bangun ulang dropdown Segment menjadi <optgroup>
    function buildAllSections(){
      segmentSelect.innerHTML = '';
      // Opsi "Semua Segment" di atas
      segmentSelect.appendChild(mkOpt('', 'Semua Segment'));

      Object.keys(SEGMENTS).forEach(divKey => {
        const og = document.createElement('optgroup');
        og.label = divKey;
        SEGMENTS[divKey].forEach(seg => og.appendChild(mkOpt(seg)));
        segmentSelect.appendChild(og);
      });
    }

    // Tampilkan hanya section dari Divisi terpilih (jika ada)
    function buildForDivision(divKey){
      segmentSelect.innerHTML = '';
      segmentSelect.appendChild(mkOpt('', 'Semua Segment'));
      if (!divKey || !SEGMENTS[divKey]) {
        // tak ada divisi → tampilkan semua section
        buildAllSections();
        return;
      }
      const og = document.createElement('optgroup');
      og.label = divKey;
      SEGMENTS[divKey].forEach(seg => og.appendChild(mkOpt(seg)));
      segmentSelect.appendChild(og);
    }

    // Inisialisasi: selalu mulai dengan 3 section
    buildAllSections();

    // Jika initialValue ada dan masih valid, pertahankan
    if (initialValue) {
      const exists = Array.from(segmentSelect.querySelectorAll('option'))
        .some(o => o.value === initialValue);
      if (exists) segmentSelect.value = initialValue;
    }

    // Ketika Divisi berubah → rebuild Segment agar hanya tampil section terkait
    divisionSelect.addEventListener('change', () => {
      const chosen = divisionSelect.value;            // '' | 'DPS' | 'DSS' | 'DGS'
      const prev   = segmentSelect.value;

      buildForDivision(chosen);

      // Pertahankan nilai jika masih valid, kalau tidak reset ke ""
      const stillThere = Array.from(segmentSelect.querySelectorAll('option'))
        .some(o => o.value === prev);
      segmentSelect.value = stillThere ? prev : '';
    });
  })();

  /* -----------------------------------------------------------------
   * 10) NEW: Dropdown Segment bertab (DPS/DSS/DGS) — UI custom dinamis
   *      (tanpa ubah HTML; native <select> tetap untuk submit)
   * ----------------------------------------------------------------- */
  (function buildTabbedSegmentDropdown(){
    // Ambil native select Segment (urut ke-3 di .filters)
    const segmentSelect = document.querySelectorAll('.filters .form-select')[2];
    if (!segmentSelect) return;

    // Pastikan punya id agar mudah dirujuk
    if (!segmentSelect.id) segmentSelect.id = 'filter-segment';

    // Sembunyikan native select (tetap ada untuk form submit)
    segmentSelect.style.position = 'absolute';
    segmentSelect.style.inset = '0 auto auto 0';
    segmentSelect.style.width = '1px';
    segmentSelect.style.height = '1px';
    segmentSelect.style.opacity = '0';
    segmentSelect.style.pointerEvents = 'none';

    // Buat wrapper & tombol UI
    const wrap = document.createElement('div');
    wrap.className = 'seg-select';
    wrap.style.position = 'relative';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'seg-select__btn';
    btn.style.width = '100%';
    btn.innerHTML = `<span id="segBtnLabel">Semua Segment</span>
                     <i class="fa-solid fa-chevron-down seg-select__caret" aria-hidden="true"></i>`;

    const menu = document.createElement('div');
    menu.className = 'seg-menu';
    menu.style.position = 'absolute';
    menu.style.top = 'calc(100% + 6px)';
    menu.style.left = '0';
    menu.style.right = '0';
    menu.style.display = 'none';
    menu.style.zIndex = '40';
    menu.setAttribute('role','listbox');

    // Mapping segmen
    const SEGMENTS = {
      DPS: ['FWS','LMS','PBS','RMS','PCS','PRS'],
      DSS: ['ERS','FRBS','MIS','TWS','SBS'],
      DGS: ['GPS','GDS','GIS','GRS']
    };

    // Tabs header
    const tabs = document.createElement('div');
    tabs.className = 'seg-tabs';
    tabs.innerHTML = `
      <button class="seg-tab active" data-tab="DPS" role="tab" aria-selected="true">DPS</button>
      <button class="seg-tab" data-tab="DSS" role="tab" aria-selected="false">DSS</button>
      <button class="seg-tab" data-tab="DGS" role="tab" aria-selected="false">DGS</button>
    `;

    // Panels container
    const panels = document.createElement('div');
    panels.className = 'seg-panels';

    // Helper buat panel
    function panelHTML(key){
      const items = SEGMENTS[key].map(v => `<button class="seg-option" data-value="${v}">${v}</button>`).join('');
      return `
        <div class="seg-panel${key==='DPS' ? ' active':''}" data-panel="${key}" role="tabpanel">
          <button class="seg-option all" data-value="">Semua Segment</button>
          ${items}
        </div>
      `;
    }
    panels.innerHTML = panelHTML('DPS') + panelHTML('DSS') + panelHTML('DGS');

    // Rakit menu
    menu.appendChild(tabs);
    menu.appendChild(panels);

    // Sisipkan ke DOM: letakkan wrapper setelah native select
    segmentSelect.parentNode.insertBefore(wrap, segmentSelect.nextSibling);
    wrap.appendChild(btn);
    wrap.appendChild(menu);

    // Helpers
    const labelEl = btn.querySelector('#segBtnLabel');
    function open(){ wrap.classList.add('open'); menu.style.display = 'block'; syncMenuWidth(); }
    function close(){ wrap.classList.remove('open'); menu.style.display = 'none'; }
    function syncMenuWidth(){
      const w = btn.getBoundingClientRect().width;
      menu.style.width = w + 'px';
    }
    function activateTab(key){
      wrap.querySelectorAll('.seg-tab').forEach(t=>{
        const active = t.dataset.tab === key;
        t.classList.toggle('active', active);
        t.setAttribute('aria-selected', String(active));
      });
      wrap.querySelectorAll('.seg-panel').forEach(p=>{
        p.classList.toggle('active', p.dataset.panel === key);
      });
    }

    // Events
    btn.addEventListener('click', (e)=>{ e.stopPropagation(); (wrap.classList.contains('open')? close(): open()); });
    document.addEventListener('click', (e)=>{ if(!wrap.contains(e.target)) close(); });
    window.addEventListener('resize', ()=>{ if(wrap.classList.contains('open')) syncMenuWidth(); });
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });

    wrap.querySelectorAll('.seg-tab').forEach(t=>{
      t.addEventListener('click', ()=> activateTab(t.dataset.tab));
    });

    panels.addEventListener('click', (e)=>{
      const opt = e.target.closest('.seg-option');
      if(!opt) return;
      const val = opt.dataset.value ?? '';
      const label = val ? opt.textContent.trim() : 'Semua Segment';

      // set native select + event
      segmentSelect.value = val;
      segmentSelect.dispatchEvent(new Event('change', {bubbles:true}));

      // update UI
      labelEl.textContent = label;
      panels.querySelectorAll('.seg-option[aria-selected]').forEach(el=>el.removeAttribute('aria-selected'));
      opt.setAttribute('aria-selected','true');

      close();
    });

    // Sinkron dengan Divisi (jika ada)
    const divisionSelect = document.querySelectorAll('.filters .form-select')[1];
    if (divisionSelect) {
      divisionSelect.addEventListener('change', ()=>{
        const d = divisionSelect.value; // '', 'DPS','DSS','DGS'
        if (d) activateTab(d);
      });
    }

    // Inisialisasi label dari nilai native (jika ada)
    const initial = segmentSelect.value;
    if (initial) {
      labelEl.textContent = initial;
      wrap.classList.add('has-value');
      const map = {FWS:'DPS',LMS:'DPS',PBS:'DPS',RMS:'DPS',PCS:'DPS',PRS:'DPS',
                   ERS:'DSS',FRBS:'DSS',MIS:'DSS',TWS:'DSS',SBS:'DSS',
                   GPS:'DGS',GDS:'DGS',GIS:'DGS',GRS:'DGS'};
      const tabKey = map[initial];
      if (tabKey) activateTab(tabKey);
      panels.querySelectorAll(`.seg-option[data-value="${initial}"]`).forEach(el=>el.setAttribute('aria-selected','true'));
    } else {
      labelEl.textContent = 'Semua Segment';
      wrap.classList.remove('has-value');
    }

    panels.addEventListener('click', (e)=>{
        const opt = e.target.closest('.seg-option');
        if(!opt) return;
        const val   = opt.dataset.value ?? '';
        const label = val ? opt.textContent.trim() : 'Semua Segment';

        segmentSelect.value = val;
        segmentSelect.dispatchEvent(new Event('change', {bubbles:true}));

        labelEl.textContent = label;

        // === toggle state: bold hanya jika ada nilai ===
        if (val) wrap.classList.add('has-value');
        else     wrap.classList.remove('has-value');

        panels.querySelectorAll('.seg-option[aria-selected]').forEach(el=>el.removeAttribute('aria-selected'));
        opt.setAttribute('aria-selected','true');

        close();
        });

  })();

  /* -----------------------------------------------------------------
   * 11) ENHANCER GENERIK untuk Witel & Divisi (filter bar) + Divisi di modal
   * ----------------------------------------------------------------- */

  // Util: enhance satu <select> menjadi .cselect
  function enhanceNativeSelect(native, { inModal = false } = {}) {
    if (!native || native.dataset.enhanced === '1') return;

    const wrap = document.createElement('div');
    wrap.className = 'cselect';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'cselect__btn';
    btn.innerHTML = `
      <span class="cselect__label">${native.options[native.selectedIndex]?.text || 'Pilih'}</span>
      <i class="cselect__caret" aria-hidden="true"></i>
    `;

    const menu = document.createElement('div');
    menu.className = 'cselect__menu';

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

    // Sisipkan DOM setelah select
    native.insertAdjacentElement('afterend', wrap);
    wrap.appendChild(btn);
    wrap.appendChild(menu);

    // Sembunyikan select asli (tetap untuk submit)
    if (inModal) {
      native.classList.add('visually-hidden-cselect'); // kamu sudah punya CSS-nya
    } else {
      native.style.position = 'absolute';
      native.style.inset = '0 auto auto 0';
      native.style.width = '1px';
      native.style.height = '1px';
      native.style.opacity = '0';
      native.style.pointerEvents = 'none';
    }

    // Interaksi open/close
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

    // Sinkron jika nilai diubah dari luar
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

  // 11a) Enhance dua filter pertama (Witel & Divisi) di bar filter (seperti semula)
  (function enhanceFilterBar(){
    const selects = document.querySelectorAll('.filters .filter-group:nth-of-type(-n+2) .form-select');
    selects.forEach(sel => enhanceNativeSelect(sel, { inModal: false }));
  })();

  // 11b) Enhance select "Divisi" di modal imp-rev-cc
  (function enhanceModalDivisi(){
    const selModal = document.querySelector('#imp-rev-cc .filter-group .form-select');
    if (selModal) enhanceNativeSelect(selModal, { inModal: true });

    // Jika modal baru dirender saat dibuka, pastikan enhancement tetap dilakukan
    const modalEl = document.getElementById('importModal');
    if (modalEl) {
      modalEl.addEventListener('shown.bs.modal', () => {
        const sel = document.querySelector('#imp-rev-cc .filter-group .form-select');
        if (sel && sel.dataset.enhanced !== '1') {
          enhanceNativeSelect(sel, { inModal: true });
        }
      });
    }
  })();

});
</script>
@endpush
