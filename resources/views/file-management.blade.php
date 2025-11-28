@extends('layouts.main')

@section('title', 'File Management')

@section('styles')
  <link rel="stylesheet" href="{{ asset('css/file-management.css') }}">
  @vite(['resources/css/app.css'])
@endsection

@section('content')
<div class="flex-1">
  <div class="main-content">
    <div class="header-dashboard">
      <div class ="header-content">
        <div class="header-text">
          <h1 class="header-title">Upload & Update Database</h1>
        </div>
        <div class="header-actions">
          <button type="button" id="updateBtn"
            class="import-btn disabled:opacity-50 disabled:cursor-not-allowed"
            disabled>
            Update Database
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm animate-fade-in">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="card-title">Files</h2>
        <p id="resultText" class="text-sm"></p>
      </div>

      <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5" id="cards-grid">

          {{-- One card template per key --}}
          @php
            $cards = [
              'dps'    => ['label' => 'DPS',    'border' => 'border-sky-400',   'accent' => 'text-sky-700',   'bar' => 'bg-sky-700',   'bg' => 'bg-sky-50'],
              'dss'    => ['label' => 'DSS',    'border' => 'border-blue-400',  'accent' => 'text-blue-900',  'bar' => 'bg-blue-900',  'bg' => 'bg-blue-50'],
              'dgs'    => ['label' => 'DGS',    'border' => 'border-amber-400', 'accent' => 'text-amber-700', 'bar' => 'bg-amber-700', 'bg' => 'bg-amber-50'],
              'target' => ['label' => 'TARGET', 'border' => 'border-green-500', 'accent' => 'text-green-900', 'bar' => 'bg-green-900', 'bg' => 'bg-green-50'],
            ];
          @endphp

          @foreach($cards as $key => $cfg)
            <div class="h-full border-2 rounded-xl {{ $cfg['border'] }} {{ $cfg['bg'] }}" data-card="{{ $key }}">
              <div class="px-4 pt-4 pb-2">
                <label class="text-sm font-semibold {{ $cfg['accent'] }}">{{ $cfg['label'] }}</label>
              </div>

              <div class="px-4 pb-4 space-y-3">
                {{-- Dropzone --}}
                <div
                  class="rounded-md border-2 border-dashed border-gray-300 p-4 text-center cursor-pointer transition bg-white/30 hover:bg-white"
                  data-dropzone="{{ $key }}"
                >
                  <div class="flex flex-col items-center gap-1">
                    <i data-lucide="upload-cloud" class="w-6 h-6 text-gray-500"></i>
                    <div class="text-sm text-gray-700">
                      Drag & Drop or <span class="underline">Choose file</span>
                    </div>
                    <div class="text-xs text-gray-500">CSV only</div>
                  </div>
                </div>

                {{-- Hidden file input --}}
                <input
                  class="hidden"
                  type="file"
                  accept=".csv,.CSV"
                  id="file-{{ $key }}"
                  data-input="{{ $key }}"
                />

                {{-- File row + progress --}}
                <div class="hidden border rounded-md p-3 bg-white space-y-2" data-info="{{ $key }}">
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                      <i data-lucide="file-spreadsheet" class="w-4 h-4 text-gray-600 shrink-0"></i>
                      <div class="text-sm font-medium text-gray-800 truncate max-w-[180px]" data-name="{{ $key }}" title=""></div>
                    </div>
                    <button type="button" class="h-7 w-7 inline-flex items-center justify-center rounded-md hover:bg-gray-100" data-clear="{{ $key }}" title="Clear">
                      <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                  </div>

                  {{-- Progress bar --}}
                  <div class="hidden" data-progress-wrap="{{ $key }}">
                    <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                      <div class="h-2 rounded transition-[width] duration-300 {{ $cfg['bar'] }}" style="width: 0%" data-progress="{{ $key }}"></div>
                    </div>
                    <div class="mt-1 text-xs text-gray-600" data-progress-text="{{ $key }}">0%</div>
                  </div>

                  {{-- Success / Error --}}
                  <div class="hidden items-center gap-2 text-xs text-green-700" data-success="{{ $key }}">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    <span>Upload Completed</span>
                  </div>
                  <div class="hidden text-xs text-red-600" data-error="{{ $key }}">Upload failed. Please try again.</div>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        
      </div>
    </div>
  </div>
</div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    // Init Lucide icons
    document.addEventListener('DOMContentLoaded', () => {
      if (window.lucide) window.lucide.createIcons();
    });

    const files = { dps: null, dss: null, dgs: null, target: null };
    const status = { dps: 'idle', dss: 'idle', dgs: 'idle', target: 'idle' }; // 'idle' | 'ready' | 'uploading' | 'success' | 'error'
    const progress = { dps: 0, dss: 0, dgs: 0, target: 0 };
    const uploadedName = { dps: null, dss: null, dgs: null, target: null };

    let isLoading = false;
    let timer = null;

    const qs = (sel) => document.querySelector(sel);
    const qsa = (sel) => Array.from(document.querySelectorAll(sel));

    const updateBtn = qs('#updateBtn');
    const resultText = qs('#resultText');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Helpers to update UI per key
    function setInfoVisible(key, show) {
      const box = qs(`[data-info="${key}"]`);
      if (!box) return;
      box.classList.toggle('hidden', !show);
    }
    function setProgressVisible(key, show) {
      const wrap = qs(`[data-progress-wrap="${key}"]`);
      if (!wrap) return;
      wrap.classList.toggle('hidden', !show);
    }
    function setSuccessVisible(key, show) {
      const el = qs(`[data-success="${key}"]`);
      if (!el) return;
      el.classList.toggle('hidden', !show);
    }
    function setErrorVisible(key, show) {
      const el = qs(`[data-error="${key}"]`);
      if (!el) return;
      el.classList.toggle('hidden', !show);
    }
    function setFileName(key, name) {
      const el = qs(`[data-name="${key}"]`);
      if (!el) return;
      el.textContent = name || 'file.csv';
      el.title = name || 'file.csv';
    }
    function setProgress(key, val) {
      progress[key] = Math.min(100, Math.max(0, Math.round(val)));
      const bar = qs(`[data-progress="${key}"]`);
      const txt = qs(`[data-progress-text="${key}"]`);
      if (bar) bar.style.width = progress[key] + '%';
      if (txt) txt.textContent = progress[key] + '%';
    }

    function refreshUpdateButton() {
      const any = !!(files.dps || files.dss || files.dgs || files.target);
      updateBtn.disabled = !any || isLoading;
    }

    function clearInput(key) {
      const input = qs(`[data-input="${key}"]`);
      if (input) input.value = '';
    }

    function chooseFile(key) {
      const input = qs(`[data-input="${key}"]`);
      input && input.click();
    }

    function onPicked(key, file) {
      if (file && !/\.csv$/i.test(file.name)) {
        resultText.textContent = 'Error: Only CSV files are accepted.';
        return;
      }
      files[key] = file || null;
      uploadedName[key] = file ? file.name : null;
      status[key] = file ? 'ready' : 'idle';
      setInfoVisible(key, !!file);
      if (file) {
        setFileName(key, file.name);
        setProgressVisible(key, false);
        setSuccessVisible(key, false);
        setErrorVisible(key, false);
        setProgress(key, 0);
      } else {
        setFileName(key, '');
      }
      refreshUpdateButton();
    }

    function deleteFile(key) {
      files[key] = null;
      status[key] = 'idle';
      setInfoVisible(key, false);
      setProgress(key, 0);
      clearInput(key);
      refreshUpdateButton();
    }

    // Hook up dropzones and inputs
    ['dps','dss','dgs','target'].forEach((key) => {
      const dz = qs(`[data-dropzone="${key}"]`);
      const input = qs(`[data-input="${key}"]`);
      const clearBtn = qs(`[data-clear="${key}"]`);

      if (dz) {
        dz.addEventListener('click', () => chooseFile(key));
        dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('bg-gray-50','border-gray-400'); });
        dz.addEventListener('dragleave', () => dz.classList.remove('bg-gray-50','border-gray-400'));
        dz.addEventListener('drop', (e) => {
          e.preventDefault();
          dz.classList.remove('bg-gray-50','border-gray-400');
          const f = e.dataTransfer.files?.[0] || null;
          onPicked(key, f);
        });
      }
      if (input) {
        input.addEventListener('change', (e) => {
          const f = e.target.files?.[0] || null;
          onPicked(key, f);
        });
      }
      if (clearBtn) {
        clearBtn.addEventListener('click', () => deleteFile(key));
      }
    });

    function startSimulated(keys) {
      if (timer) clearInterval(timer);
      timer = setInterval(() => {
        keys.forEach((k) => {
          if (status[k] === 'uploading') {
            setProgress(k, Math.min(90, (progress[k] || 0) + 5 + Math.random() * 10));
          }
        });
      }, 250);
    }
    function stopSimulated() {
      if (timer) clearInterval(timer);
      timer = null;
    }

    async function doSubmit() {
      if (isLoading) return;
      isLoading = true;
      refreshUpdateButton();
      resultText.textContent = '';

      // Mark selected as uploading
      const uploading = ['dps','dss','dgs','target'].filter((k) => !!files[k]);
      uploading.forEach((k) => {
        status[k] = 'uploading';
        setInfoVisible(k, true);
        setProgressVisible(k, true);
        setSuccessVisible(k, false);
        setErrorVisible(k, false);
        setProgress(k, 0);
      });
      startSimulated(uploading);

      try {
        const fd = new FormData();
        if (files.dps) fd.append('dps', files.dps);
        if (files.dss) fd.append('dss', files.dss);
        if (files.dgs) fd.append('dgs', files.dgs);
        if (files.target) fd.append('target', files.target);

        const res = await fetch('{{ route('file-management.update') }}', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
          body: fd,
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data?.error || 'Upload failed');

        stopSimulated();
        uploading.forEach((k) => {
          setProgress(k, 100);
          status[k] = 'success';
          setSuccessVisible(k, true);
          // Keep last uploaded name, clear input
          clearInput(k);
          files[k] = null;
        });

        const pieces = [];
        if (data?.counts?.dps != null) pieces.push(`dps: ${data.counts.dps} rows`);
        if (data?.counts?.dss != null) pieces.push(`dss: ${data.counts.dss} rows`);
        if (data?.counts?.dgs != null) pieces.push(`dgs: ${data.counts.dgs} rows`);
        if (data?.counts?.target != null) pieces.push(`target: ${data.counts.target} rows`);
        resultText.textContent = pieces.length ? pieces.join(', ') : 'Updated!';
      } catch (err) {
        stopSimulated();
        uploading.forEach((k) => {
          status[k] = 'error';
          setErrorVisible(k, true);
        });
        resultText.textContent = 'Error: ' + (err?.message || 'Upload failed');
      } finally {
        isLoading = false;
        refreshUpdateButton();
      }
    }

    updateBtn.addEventListener('click', doSubmit);
  </script>
@endsection
