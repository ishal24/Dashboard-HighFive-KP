@extends('layouts.cc-witel-performance-layout')

@section('styles')
    {{-- <link rel="stylesheet" href="{{ asset('css/inertia.css') }}"> --}}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    {{-- NOTE: change cards ui components to match with the react inertia ones in other repo --}}
    <link rel="stylesheet" href="{{ asset('css/ccwitel.css') }}">
@endsection

@section('content')
  <div className="mx-auto px-16 py-20 space-y-8">
      <div class="header-block box-shadow">
          <div class="header-content">
              {{-- Left side with title and subtitle --}}
              <div class="text-content">
                  <h1 class="header-title">Dashboard Performansi CC & Witel</h1>
                  <p class="header-subtitle">Monitoring dan Analisis Performa Revenue CC dan Witel</p>
              </div>
          </div>
      </div>

      <div id="trend-revenue" class="ccw-component">
          @include('cc_witel.partials.trend-revenue-ccw')
      </div>

      <div id="witel-performance" class="ccw-component">
          @include('cc_witel.partials.witel-performance')
      </div>

      <div id="top-customers" class="ccw-component">
          @include('cc_witel.partials.customers-leaderboard')
      </div>

      <div class="ccw-component">
          {{-- @include('cc_witel.partials.division-overview', ['_placeholder' => true]) --}}
      </div>

      <div class="ccw-component">
          {{-- @include('cc_witel.partials.cc-performance', ['_placeholder' => true]) --}}
      </div>

      {{-- NOTE: this one is probably not needed --}}
      {{-- @include('cc_witel.partials.revenue-overview', ['_placeholder' => true]) --}}

  </div>
@endsection
