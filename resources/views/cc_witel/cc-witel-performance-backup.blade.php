@extends('layouts.main')

@section('content')
<div class="p-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">TREG 3 Dashboard</h1>

    <div class="flex items-center gap-2">
      <!-- Export dropdown -->
      <div x-data="{open:false}" class="relative">
        <button @click="open=!open" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 hover:bg-gray-50">
          <x-lucide-download class="w-4 h-4" />
          Export
        </button>
        <div x-show="open" @click.away="open=false" class="absolute right-0 mt-1 w-44 rounded-lg border bg-white shadow">
          <a href="{{ route('dashboard.treg3.export', request()->query()+['mode'=>'filtered']) }}" class="block px-3 py-2 hover:bg-gray-50">Export (Filtered)</a>
          <a href="{{ route('dashboard.treg3.export', request()->query()+['mode'=>'ytd']) }}" class="block px-3 py-2 hover:bg-gray-50">Export (YTD)</a>
        </div>
      </div>

      <!-- Refresh cache -->
      <a href="{{ route('dashboard.treg3.index', request()->query()+['refresh'=>1]) }}"
         class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 hover:bg-gray-50">
        <x-lucide-refresh-cw class="w-4 h-4" /> Refresh
      </a>
    </div>
  </div>

  <!-- Filters -->
  <form id="filters" method="GET" action="{{ route('dashboard.treg3.index') }}" x-data>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
      <div>
        <label class="block text-sm font-medium mb-1">Year</label>
        <input type="number" name="year" value="{{ $year }}" min="2000" max="2100"
               class="w-full rounded-lg border px-3 py-2"/>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Month (1–12)</label>
        <input type="number" name="month" value="{{ $month }}" min="1" max="12"
               class="w-full rounded-lg border px-3 py-2"/>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Division</label>
        <select name="division" class="w-full rounded-lg border px-3 py-2">
          <option {{ $division==='All'?'selected':'' }}>All</option>
          @foreach($divisions as $div)
            <option value="{{ $div }}" {{ $division===$div?'selected':'' }}>{{ $div }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Source</label>
        <select name="source" class="w-full rounded-lg border px-3 py-2">
          <option value="reguler" {{ $source==='reguler'?'selected':'' }}>reguler</option>
          <option value="ngtma" {{ $source==='ngtma'?'selected':'' }}>ngtma</option>
        </select>
      </div>
      <div class="md:col-span-4">
        <button class="rounded-lg bg-black text-white px-4 py-2">Apply</button>
      </div>
    </div>
  </form>

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach(($division==='All' ? $divisions : [$division]) as $div)
      @php $k = $kpis[$div]; @endphp
      <div class="rounded-2xl border p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="text-sm text-gray-500">{{ $div }}</div>
          <x-lucide-trending-up class="w-4 h-4" />
        </div>
        <div class="text-2xl font-semibold">
          {{ number_format($k['current']/1_000_000_000, 2) }} <span class="text-sm font-normal text-gray-500">B</span>
        </div>
        <div class="mt-1 text-sm text-gray-600">
          YTD: {{ number_format($k['sumToDate']/1_000_000_000, 2) }} B
        </div>
        <div class="mt-1 text-sm {{ $k['growthPct']>=0?'text-green-600':'text-red-600' }}">
          {{ $k['growthPct']>=0?'+':'' }}{{ number_format($k['growthPct'], 2) }}%
        </div>
      </div>
    @endforeach
  </div>

  <!-- SVG Chart (simple, legible; values are already in billions) -->
  @php
    $w = 900; $h = 320; $padL=60; $padR=20; $padT=20; $padB=40;
    $plotW = $w - $padL - $padR; $plotH = $h - $padT - $padB;
    $maxY = max(
      max($chart['dgs']), max($chart['dps']), max($chart['dss']),
      1 // avoid div by zero
    );
    $x = function($i) use($plotW,$padL){ return $padL + $i * ($plotW/11); };
    $y = function($v) use($plotH,$padT,$maxY){ return $padT + ($plotH * (1 - ($v/($maxY ?: 1)))); };
    $seriesDraw = function($arr) use($x,$y){
        $pts = [];
        foreach($arr as $i=>$v){ $pts[] = $x($i).','. $y($v); }
        return implode(' ', $pts);
    };
  @endphp

  <div class="rounded-2xl border p-4 overflow-x-auto">
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-auto">
      <!-- Axes -->
      <line x1="{{ $padL }}" y1="{{ $padT }}" x2="{{ $padL }}" y2="{{ $h-$padB }}" stroke="#e5e7eb"/>
      <line x1="{{ $padL }}" y1="{{ $h-$padB }}" x2="{{ $w-$padR }}" y2="{{ $h-$padB }}" stroke="#e5e7eb"/>

      <!-- Y labels (0, 25%, 50%, 75%, 100%) -->
      @for($i=0;$i<=4;$i++)
        @php $val = $maxY * $i/4; @endphp
        <text x="{{ $padL-8 }}" y="{{ $y($val)+4 }}" text-anchor="end" font-size="11" fill="#6b7280">
          {{ number_format($val, 2) }} B
        </text>
        <line x1="{{ $padL }}" y1="{{ $y($val) }}" x2="{{ $w-$padR }}" y2="{{ $y($val) }}" stroke="#f3f4f6"/>
      @endfor

      <!-- X labels -->
      @foreach($chart['months'] as $i=>$m)
        <text x="{{ $x($i) }}" y="{{ $h-$padB+16 }}" text-anchor="middle" font-size="11" fill="#6b7280">
          {{ \Carbon\Carbon::create()->month($m)->shortMonthName }}
        </text>
      @endforeach

      <!-- Lines (no explicit colors per your constraint: default stroke) -->
      <polyline points="{{ $seriesDraw($chart['dgs']) }}" fill="none" stroke-width="2"/>
      <polyline points="{{ $seriesDraw($chart['dps']) }}" fill="none" stroke-width="2"/>
      <polyline points="{{ $seriesDraw($chart['dss']) }}" fill="none" stroke-width="2"/>

      <!-- Legend -->
      <g transform="translate({{ $padL }}, {{ $padT }})">
        <rect width="130" height="54" fill="white" stroke="#e5e7eb" rx="8"/>
        <text x="10" y="16" font-size="12" fill="#111827">Legend</text>
        <text x="10" y="34" font-size="11" fill="#374151">DGS / DPS / DSS</text>
        <text x="10" y="50" font-size="11" fill="#6b7280">(Billions)</text>
      </g>
    </svg>
  </div>

  <!-- Placeholders for other blocks -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="rounded-2xl border p-4 text-sm text-gray-500">Revenue Overview (placeholder)</div>
    <div class="rounded-2xl border p-4 text-sm text-gray-500">Witel Performance (placeholder)</div>
    <div class="rounded-2xl border p-4 text-sm text-gray-500">Division Overview (placeholder)</div>
    <div class="rounded-2xl border p-4 text-sm text-gray-500">Top Customers (placeholder)</div>
    <div class="rounded-2xl border p-4 text-sm text-gray-500">CC Performance (placeholder)</div>
  </div>
</div>
@endsection
