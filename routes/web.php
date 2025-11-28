<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WitelPerformController;
use App\Http\Controllers\CCWitelPerformController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Overview\DashboardController;
use App\Http\Controllers\Overview\AmDashboardController;
use App\Http\Controllers\Overview\WitelDashboardController;
use App\Http\Controllers\Overview\CcDashboardController;
use App\Http\Controllers\RevenueData\RevenueDataController;
use App\Http\Controllers\RevenueData\RevenueImportController;
use App\Http\Controllers\RevenueData\ImportCCController;
use App\Http\Controllers\RevenueData\ImportAMController;
use App\Http\Controllers\LeaderboardAMController;
use App\Http\Controllers\HighFive\HighFiveController;
use App\Http\Controllers\HighFive\HighFiveAMPerformanceController;
use App\Http\Controllers\HighFive\HighFiveProductPerformanceController;
use App\Http\Controllers\HighFive\HighFiveReportController;
use App\Http\Controllers\HighFive\HighFiveSettingsController;

// Laravel Core
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// Models
use App\Models\Divisi;
use App\Models\Witel;
use App\Models\Segment;
use App\Models\CcRevenue;
use App\Models\AccountManager;
use App\Models\CorporateCustomer;

/*
|--------------------------------------------------------------------------
| Web Routes - RLEGS Dashboard V2
|--------------------------------------------------------------------------
|
| UPDATED: 2025-11-26
| ✅ REVISED: High Five RLEGS TR3 routes with snapshot architecture
| ✅ MAINTAINED: All existing routes
| ✅ ADDED: New modal management routes
*/

// ===== BASIC ROUTES =====
Route::get('/', function () {
    return view('auth.login');
});

Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('guest.logout');

// ===== AUTH ROUTES =====
Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::get('/search-account-managers', [RegisteredUserController::class, 'searchAccountManagers'])
    ->middleware('guest')
    ->name('search.account-managers');

// ===== AUTHENTICATED ROUTES =====
Route::middleware(['auth', 'verified'])->group(function () {

    // ===== MAIN DASHBOARD ROUTE =====
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== SIDEBAR ROUTES =====
    Route::view('/revenue', 'revenueData')->name('revenue.index');

    // Witel + CC Performance Routes
    Route::get('/witel-perform', [WitelPerformController::class, 'index'])->name('witel.perform');
    Route::get('/treg3', [CCWitelPerformController::class, 'index'])->name('witel-cc-index');

    // Leaderboard Route
    Route::get('/leaderboard', [LeaderboardAMController::class, 'index'])->name('leaderboard');

    // ===== HIGH FIVE RLEGS TR3 ROUTES (✅ UPDATED & ENHANCED) =====
    Route::prefix('high-five')->name('high-five.')->group(function () {

        // ===== MAIN DASHBOARD =====
        Route::get('/', [HighFiveController::class, 'index'])->name('index');

        // ===== SNAPSHOT MANAGEMENT (🆕 NEW ARCHITECTURE) =====
        Route::get('/snapshots', [HighFiveController::class, 'getSnapshots'])->name('snapshots');
        Route::get('/latest-snapshots', [HighFiveController::class, 'getLatestSnapshots'])->name('latest-snapshots');

        // ===== LINK MANAGEMENT (FOR MODAL) =====
        Route::get('/available-links', [HighFiveSettingsController::class, 'getAvailableLinks'])->name('available-links');
        Route::post('/fetch-manual', [HighFiveSettingsController::class, 'fetchWithCustomDate'])->name('fetch-manual');

        // ===== PERFORMANCE ANALYSIS (🔄 UPDATED: Now uses snapshot_1_id, snapshot_2_id) =====
        // Tab 1: AM Level Performance
        Route::get('/am-performance', [HighFiveAMPerformanceController::class, 'getAMPerformance'])->name('am-performance');

        // Tab 2: Product Level Performance
        Route::get('/product-performance', [HighFiveProductPerformanceController::class, 'getProductPerformance'])->name('product-performance');

        // ===== REPORT GENERATION (🔄 UPDATED: Now uses snapshot_1_id, snapshot_2_id) =====
        Route::get('/report/download', [HighFiveReportController::class, 'downloadReport'])->name('report.download');
        Route::get('/report/preview', [HighFiveReportController::class, 'previewReport'])->name('report.preview');

        // ===== SETTINGS ROUTES (ADMIN ONLY - 🆕 ENHANCED) =====
        Route::prefix('settings')->name('settings.')->group(function () {

            // Main settings page (if you have a settings view)
            Route::get('/', [HighFiveSettingsController::class, 'index'])->name('index');

            // CRUD Operations for Dataset Links
            Route::post('/store', [HighFiveSettingsController::class, 'store'])->name('store');
            Route::put('/update/{id}', [HighFiveSettingsController::class, 'update'])->name('update');
            Route::delete('/delete/{id}', [HighFiveSettingsController::class, 'destroy'])->name('delete');

            // Fetch Operations
            Route::post('/fetch-now/{id}', [HighFiveSettingsController::class, 'fetchNow'])->name('fetch-now');
            Route::get('/history/{id}', [HighFiveSettingsController::class, 'history'])->name('history');
            Route::post('/retry-snapshot/{id}', [HighFiveSettingsController::class, 'retrySnapshot'])->name('retry-snapshot');
        });

        // ===== DEPRECATED ROUTES (Kept for backward compatibility) =====
        Route::post('/dataset/store', [HighFiveController::class, 'storeDataset'])->name('dataset.store');
        Route::get('/dataset/by-divisi', [HighFiveController::class, 'getDatasetsByDivisi'])->name('dataset.by-divisi');
        Route::delete('/dataset/{id}', [HighFiveController::class, 'deleteDataset'])->name('dataset.delete');
    });

    // ===== DASHBOARD API ROUTES =====
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // CC + Witel data fetch
        Route::get('/trend-data', [CCWitelPerformController::class, 'fetchTrendData']);
        Route::get('/witel-performance-data', [CCWitelPerformController::class, 'fetchWitelPerformanceData']);
        Route::get('/customers-leaderboard', [CCWitelPerformController::class, 'fetchOverallCustomersLeaderboard']);
        Route::get('tab-data', [DashboardController::class, 'getTabData'])->name('tab-data');
        Route::get('export', [DashboardController::class, 'export'])->name('export');
        Route::get('chart-data', [DashboardController::class, 'getChartData'])->name('chart-data');
        Route::get('revenue-table', [DashboardController::class, 'getRevenueTable'])->name('revenue-table');
        Route::get('summary', [DashboardController::class, 'getSummary'])->name('summary');
        Route::get('insights', [DashboardController::class, 'getPerformanceInsights'])->name('insights');

        // AM specific endpoints
        Route::get('am-performance', [DashboardController::class, 'getAmPerformance'])->name('am-performance');
        Route::get('am-customers', [DashboardController::class, 'getAmCustomers'])->name('am-customers');
        Route::get('am-export', [DashboardController::class, 'exportAm'])->name('am-export');
    });

    // ===== LEADERBOARD AM ROUTES =====
    Route::get('leaderboard/am-category/{id}', [LeaderboardAMController::class, 'getAMCategory'])->name('leaderboard.am-category');

    // ===== ACCOUNT MANAGER ROUTES =====
    Route::prefix('account-manager')->name('account-manager.')->group(function () {
        Route::get('{id}', [AmDashboardController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        Route::get('{id}/tab-data', [AmDashboardController::class, 'getTabData'])
            ->name('tab-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/card-data', [AmDashboardController::class, 'getCardData'])
            ->name('card-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/ranking', [AmDashboardController::class, 'getRankingDataAjax'])
            ->name('ranking')
            ->where('id', '[0-9]+');

        Route::get('{id}/chart-data', [AmDashboardController::class, 'getChartData'])
            ->name('chart-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/performance-summary', [AmDashboardController::class, 'getPerformanceSummary'])
            ->name('performance-summary')
            ->where('id', '[0-9]+');

        Route::get('{id}/update-filters', [AmDashboardController::class, 'updateFilters'])
            ->name('update-filters')
            ->where('id', '[0-9]+');

        Route::get('{id}/export', [AmDashboardController::class, 'export'])
            ->name('export')
            ->where('id', '[0-9]+');

        Route::get('{id}/info', [AmDashboardController::class, 'getAmInfo'])
            ->name('info')
            ->where('id', '[0-9]+');

        Route::get('{id}/compare', [AmDashboardController::class, 'compareWithOthers'])
            ->name('compare')
            ->where('id', '[0-9]+');

        Route::get('{id}/trend', [AmDashboardController::class, 'getHistoricalTrend'])
            ->name('trend')
            ->where('id', '[0-9]+');

        Route::get('{id}/top-customers', [AmDashboardController::class, 'getTopCustomers'])
            ->name('top-customers')
            ->where('id', '[0-9]+');

        if (app()->environment('local')) {
            Route::get('{id}/debug', [AmDashboardController::class, 'debug'])
                ->name('debug')
                ->where('id', '[0-9]+');
        }
    });

    // ===== CORPORATE CUSTOMER ROUTES =====
    Route::prefix('corporate-customer')->name('corporate-customer.')->group(function () {
        Route::get('{id}', [CcDashboardController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        Route::get('{id}/tab-data', [CcDashboardController::class, 'getTabData'])
            ->name('tab-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/card-data', [CcDashboardController::class, 'getCardData'])
            ->name('card-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/chart-data', [CcDashboardController::class, 'getChartData'])
            ->name('chart-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/export', [CcDashboardController::class, 'export'])
            ->name('export')
            ->where('id', '[0-9]+');

        Route::get('{id}/info', function ($id) {
            $customer = CorporateCustomer::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'nama' => $customer->nama,
                    'nipnas' => $customer->nipnas
                ]
            ]);
        })->name('info')->where('id', '[0-9]+');

        Route::get('{id}/revenue-history', [CcDashboardController::class, 'getRevenueHistory'])
            ->name('revenue-history')
            ->where('id', '[0-9]+');

        Route::get('{id}/account-managers', [CcDashboardController::class, 'getAccountManagers'])
            ->name('account-managers')
            ->where('id', '[0-9]+');
    });

    // ===== WITEL ROUTES =====
    Route::prefix('witel')->name('witel.')->group(function () {
        Route::get('{id}', [WitelDashboardController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        Route::get('{id}/tab-data', [WitelDashboardController::class, 'getTabData'])
            ->name('tab-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/card-data', [WitelDashboardController::class, 'getCardData'])
            ->name('card-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/chart-data', [WitelDashboardController::class, 'getChartData'])
            ->name('chart-data')
            ->where('id', '[0-9]+');

        Route::get('{id}/export', [WitelDashboardController::class, 'export'])
            ->name('export')
            ->where('id', '[0-9]+');

        Route::get('{id}/info', function ($id) {
            $witel = Witel::findOrFail($id);

            $totalAM = AccountManager::where('witel_id', $id)
                ->where('role', 'AM')
                ->count();

            $totalHOTDA = AccountManager::where('witel_id', $id)
                ->where('role', 'HOTDA')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $witel->id,
                    'nama' => $witel->nama,
                    'total_am' => $totalAM,
                    'total_hotda' => $totalHOTDA,
                    'total_account_managers' => $totalAM + $totalHOTDA
                ]
            ]);
        })->name('info')->where('id', '[0-9]+');

        Route::get('{id}/performance-summary', [WitelDashboardController::class, 'getPerformanceSummary'])
            ->name('performance-summary')
            ->where('id', '[0-9]+');

        Route::get('{id}/top-ams', [WitelDashboardController::class, 'getTopAms'])
            ->name('top-ams')
            ->where('id', '[0-9]+');

        Route::get('{id}/top-customers', [WitelDashboardController::class, 'getTopCustomers'])
            ->name('top-customers')
            ->where('id', '[0-9]+');

        Route::get('{id}/revenue-trend', [WitelDashboardController::class, 'getRevenueTrend'])
            ->name('revenue-trend')
            ->where('id', '[0-9]+');
    });

    // ===== SEGMENT ROUTES =====
    Route::prefix('segment')->name('segment.')->group(function () {
        Route::get('{id}', [DashboardController::class, 'showSegment'])
            ->name('show')
            ->where('id', '[0-9]+');

        Route::get('{id}/data', [DashboardController::class, 'getSegmentData'])
            ->name('data')
            ->where('id', '[0-9]+');

        Route::get('{id}/customers', [DashboardController::class, 'getSegmentCustomers'])
            ->name('customers')
            ->where('id', '[0-9]+');
    });

    // ===== WITEL PERFORM ROUTES =====
    Route::post('/witel-perform/update-charts', [WitelPerformController::class, 'updateCharts'])->name('witel.update-charts');
    Route::post('/witel-perform/filter-by-divisi', [WitelPerformController::class, 'filterByDivisi'])->name('witel.filter-by-divisi');
    Route::post('/witel-perform/filter-by-witel', [WitelPerformController::class, 'filterByWitel'])->name('witel.filter-by-witel');
    Route::post('/witel-perform/filter-by-regional', [WitelPerformController::class, 'filterByRegional'])->name('witel.filter-by-regional');

    // ===== GENERAL API ROUTES =====
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('divisi', function () {
            return response()->json(
                Divisi::select('id', 'nama', 'kode')
                    ->orderBy('nama')
                    ->get()
            );
        })->name('divisi');

        Route::get('witel', function () {
            return response()->json(
                Witel::select('id', 'nama')
                    ->orderBy('nama')
                    ->get()
            );
        })->name('witel');

        Route::get('witels', function () {
            return redirect()->route('api.witel');
        })->name('witels');

        Route::get('segments', function () {
            return response()->json(
                Segment::select('id', 'lsegment_ho', 'ssegment_ho', 'divisi_id')
                    ->orderBy('lsegment_ho')
                    ->get()
            );
        })->name('segments');

        Route::get('segments-by-divisi/{divisi_id}', function ($divisi_id) {
            return response()->json(
                Segment::select('id', 'lsegment_ho', 'ssegment_ho')
                    ->where('divisi_id', $divisi_id)
                    ->orderBy('lsegment_ho')
                    ->get()
            );
        })->name('segments-by-divisi');

        Route::get('revenue-sources', function () {
            return response()->json([
                'all' => 'Semua Source',
                'HO' => 'HO Revenue',
                'BILL' => 'BILL Revenue'
            ]);
        })->name('revenue-sources');

        Route::get('tipe-revenues', function () {
            return response()->json([
                'all' => 'Semua Tipe',
                'REGULER' => 'Revenue Reguler',
                'NGTMA' => 'Revenue NGTMA'
            ]);
        })->name('tipe-revenues');

        Route::get('period-types', function () {
            return response()->json([
                'YTD' => 'Year to Date',
                'MTD' => 'Month to Date',
                'QTD' => 'Quarter to Date'
            ]);
        })->name('period-types');

        Route::get('available-years', function () {
            try {
                $years = CcRevenue::distinct()
                    ->orderBy('tahun', 'desc')
                    ->pluck('tahun')
                    ->filter()
                    ->values()
                    ->toArray();

                if (empty($years)) {
                    $years = [date('Y')];
                }

                return response()->json([
                    'years' => $years,
                    'use_year_picker' => count($years) > 10,
                    'min_year' => min($years),
                    'max_year' => max($years),
                    'current_year' => date('Y')
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get available years', ['error' => $e->getMessage()]);

                return response()->json([
                    'years' => [date('Y')],
                    'use_year_picker' => false,
                    'min_year' => date('Y'),
                    'max_year' => date('Y'),
                    'current_year' => date('Y')
                ]);
            }
        })->name('available-years');

        Route::get('available-months/{year}', function ($year) {
            try {
                $months = CcRevenue::where('tahun', $year)
                    ->distinct()
                    ->orderBy('bulan', 'asc')
                    ->pluck('bulan')
                    ->filter()
                    ->values()
                    ->toArray();

                return response()->json([
                    'success' => true,
                    'months' => $months,
                    'year' => $year
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get available months', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'months' => [],
                    'error' => $e->getMessage()
                ], 500);
            }
        })->name('available-months');

        Route::get('health', function () {
            try {
                DB::connection()->getPdo();
                $dbStatus = 'connected';
            } catch (\Exception $e) {
                $dbStatus = 'error: ' . $e->getMessage();
            }

            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'database' => $dbStatus,
                'app_version' => config('app.version', '2.0'),
                'memory_usage' => memory_get_usage(true)
            ]);
        })->name('health');

        Route::get('user-info', function () {
            $user = Auth::user();
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'account_manager_id' => $user->account_manager_id,
                'witel_id' => $user->witel_id,
                'permissions' => [
                    'can_export' => in_array($user->role, ['admin', 'witel_support', 'account_manager']),
                    'can_view_all_data' => $user->role === 'admin',
                    'can_view_witel_data' => in_array($user->role, ['admin', 'witel_support']),
                    'can_view_am_data' => in_array($user->role, ['admin', 'account_manager']),
                    'can_import_data' => in_array($user->role, ['admin']),
                    'can_delete_data' => in_array($user->role, ['admin']),
                    'can_edit_data' => in_array($user->role, ['admin', 'witel_support'])
                ]
            ]);
        })->name('user-info');

        Route::get('dashboard-stats', function () {
            try {
                $currentYear = date('Y');
                $currentMonth = date('n');

                $stats = [
                    'total_cc' => CorporateCustomer::count(),
                    'total_am' => AccountManager::count(),
                    'total_witel' => Witel::count(),
                    'total_divisi' => Divisi::count(),
                    'revenue_ytd' => CcRevenue::where('tahun', $currentYear)->sum('real_revenue'),
                    'target_ytd' => CcRevenue::where('tahun', $currentYear)->sum('target_revenue'),
                    'revenue_mtd' => CcRevenue::where('tahun', $currentYear)
                        ->where('bulan', $currentMonth)
                        ->sum('real_revenue'),
                    'target_mtd' => CcRevenue::where('tahun', $currentYear)
                        ->where('bulan', $currentMonth)
                        ->sum('target_revenue')
                ];

                $stats['achievement_ytd'] = $stats['target_ytd'] > 0
                    ? round(($stats['revenue_ytd'] / $stats['target_ytd']) * 100, 2)
                    : 0;

                $stats['achievement_mtd'] = $stats['target_mtd'] > 0
                    ? round(($stats['revenue_mtd'] / $stats['target_mtd']) * 100, 2)
                    : 0;

                return response()->json([
                    'success' => true,
                    'data' => $stats
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        })->name('dashboard-stats');
    });

    // ===== LEGACY EXPORT COMPATIBILITY =====
    Route::get('export', function () {
        return redirect()->route('dashboard.export', request()->all());
    })->name('export');

    // ===== PROFILE ROUTES =====
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.remove-photo');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::post('/email/verification-notification', function () {
        request()->user()->sendEmailVerificationNotification();
        return back()->with('verification-link-sent', true);
    })->middleware(['throttle:6,1'])->name('verification.send');

    // ===== SIDEBAR ROUTES =====
    Route::get('/witel-perform', function () {
        return view('performansi.witel');
    })->name('witel.perform');

    // ===== REVENUE DATA ROUTES (ADMIN ONLY) =====
    Route::middleware('admin')->prefix('revenue-data')->name('revenue.')->group(function () {

        // Main Revenue Data Page
        Route::get('/', [RevenueDataController::class, 'index'])->name('data');

        // ===== GET DATA APIs =====
        Route::get('revenue-cc', [RevenueDataController::class, 'getRevenueCC'])->name('api.cc');
        Route::get('revenue-am', [RevenueDataController::class, 'getRevenueAM'])->name('api.am');
        Route::get('data-am', [RevenueDataController::class, 'getDataAM'])->name('api.data.am');
        Route::get('data-cc', [RevenueDataController::class, 'getDataCC'])->name('api.data.cc');
        Route::get('filter-options', [RevenueDataController::class, 'getFilterOptions'])->name('api.filter.options');

        // ===== REVENUE CC CRUD =====
        Route::get('revenue-cc/{id}', [RevenueDataController::class, 'showRevenueCC'])->name('api.show-cc');
        Route::put('revenue-cc/{id}', [RevenueDataController::class, 'updateRevenueCC'])->name('api.update-cc');
        Route::delete('revenue-cc/{id}', [RevenueDataController::class, 'deleteRevenueCC'])->name('api.delete-cc');
        Route::post('bulk-delete-cc-revenue', [RevenueDataController::class, 'bulkDeleteRevenueCC'])->name('api.bulk-delete-cc');
        Route::post('bulk-delete-all-cc-revenue', [RevenueDataController::class, 'bulkDeleteAllRevenueCC'])->name('api.bulk-delete-all-cc');

        // ===== REVENUE AM CRUD =====
        Route::get('revenue-am/{id}', [RevenueDataController::class, 'showRevenueAM'])->name('api.show-am');
        Route::put('revenue-am/{id}', [RevenueDataController::class, 'updateRevenueAM'])->name('api.update-am');
        Route::delete('revenue-am/{id}', [RevenueDataController::class, 'deleteRevenueAM'])->name('api.delete-am');
        Route::post('bulk-delete-am-revenue', [RevenueDataController::class, 'bulkDeleteRevenueAM'])->name('api.bulk-delete-am');
        Route::post('bulk-delete-all-am-revenue', [RevenueDataController::class, 'bulkDeleteAllRevenueAM'])->name('api.bulk-delete-all-am');

        // ===== DATA AM CRUD =====
        Route::get('data-am/{id}', [RevenueDataController::class, 'showDataAM'])->name('api.show-data-am');
        Route::put('data-am/{id}', [RevenueDataController::class, 'updateDataAM'])->name('api.update-data-am');
        Route::delete('data-am/{id}', [RevenueDataController::class, 'deleteDataAM'])->name('api.delete-data-am');
        Route::post('data-am', [RevenueDataController::class, 'createDataAM'])->name('api.create-data-am');
        Route::post('data-am/{id}/change-password', [RevenueDataController::class, 'changePasswordAM'])->name('api.change-password-am');
        Route::post('bulk-delete-data-am', [RevenueDataController::class, 'bulkDeleteDataAM'])->name('api.bulk-delete-data-am');
        Route::post('bulk-delete-all-data-am', [RevenueDataController::class, 'bulkDeleteAllDataAM'])->name('api.bulk-delete-all-data-am');

        // ===== DATA CC CRUD =====
        Route::get('data-cc/{id}', [RevenueDataController::class, 'showDataCC'])->name('api.show-data-cc');
        Route::put('data-cc/{id}', [RevenueDataController::class, 'updateDataCC'])->name('api.update-data-cc');
        Route::delete('data-cc/{id}', [RevenueDataController::class, 'deleteDataCC'])->name('api.delete-data-cc');
        Route::post('data-cc', [RevenueDataController::class, 'createDataCC'])->name('api.create-data-cc');
        Route::post('bulk-delete-data-cc', [RevenueDataController::class, 'bulkDeleteDataCC'])->name('api.bulk-delete-data-cc');
        Route::post('bulk-delete-all-data-cc', [RevenueDataController::class, 'bulkDeleteAllDataCC'])->name('api.bulk-delete-all-data-cc');

        // ===== IMPORT ROUTES =====
        Route::post('import/preview', [RevenueImportController::class, 'previewImport'])->name('import.preview');
        Route::post('import/execute', [RevenueImportController::class, 'executeImport'])->name('import.execute');
        Route::post('import', [RevenueImportController::class, 'import'])->name('import');
        Route::post('import-data-cc', [RevenueImportController::class, 'import'])->name('import.data-cc');
        Route::post('import-data-am', [RevenueImportController::class, 'import'])->name('import.data-am');
        Route::post('import-revenue-cc', [RevenueImportController::class, 'import'])->name('import.revenue-cc');
        Route::post('import-revenue-am', [RevenueImportController::class, 'import'])->name('import.revenue-am');

        // Template downloads
        Route::get('template/data-cc', [ImportCCController::class, 'downloadTemplate'])->defaults('type', 'data-cc')->name('template.data-cc');
        Route::get('template/data-am', [ImportAMController::class, 'downloadTemplate'])->defaults('type', 'data-am')->name('template.data-am');
        Route::get('template/revenue-cc-dgs-real', [ImportCCController::class, 'downloadTemplate'])->defaults('type', 'revenue-cc-dgs')->name('template.revenue-cc-dgs');
        Route::get('template/revenue-cc-dps-real', [ImportCCController::class, 'downloadTemplate'])->defaults('type', 'revenue-cc-dps')->name('template.revenue-cc-dps');
        Route::get('template/revenue-am', [ImportAMController::class, 'downloadTemplate'])->defaults('type', 'revenue-am')->name('template.revenue-am');

        Route::get('template/{type}', function ($type) {
            if (in_array($type, ['data-cc', 'revenue-cc-dgs', 'revenue-cc-dps'])) {
                $controller = new ImportCCController();
                return $controller->downloadTemplate($type);
            } elseif (in_array($type, ['data-am', 'revenue-am'])) {
                $controller = new ImportAMController();
                return $controller->downloadTemplate($type);
            }
            return response()->json(['error' => 'Template not found'], 404);
        })->name('template');

        Route::get('download-error-log/{filename}', [RevenueImportController::class, 'downloadErrorLog'])->name('download.error.log');
        Route::get('import-history', [RevenueImportController::class, 'getImportHistory'])->name('import.history');
        Route::post('validate-import', [RevenueImportController::class, 'validateImport'])->name('validate-import');
    });

    // Legacy route for backward compatibility
    Route::get('/revenue', function () {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Halaman ini hanya untuk Admin.');
        }
        return redirect()->route('revenue.data');
    })->name('revenue.index');
}); // End of auth middleware

// ===== UTILITY ROUTES =====
Route::get('health-check', function () {
    try {
        $dbCheck = DB::connection()->getPdo() ? 'OK' : 'Failed';

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'app' => [
                'name' => config('app.name'),
                'version' => config('app.version', '2.0'),
                'environment' => app()->environment()
            ],
            'services' => [
                'database' => $dbCheck
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('health-check');

// ===== DEBUG ROUTES (DEVELOPMENT ONLY) =====
if (app()->environment('local')) {
    Route::get('debug/routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName()
            ];
        });

        return response()->json([
            'total_routes' => $routes->count(),
            'routes' => $routes->sortBy('uri')->values()
        ]);
    })->name('debug.routes');

    Route::get('debug/user', function () {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $dashboardInfo = [];

        switch ($user->role) {
            case 'admin':
                $dashboardInfo = [
                    'view' => 'dashboard.blade.php',
                    'controller' => 'DashboardController::handleAdminDashboard'
                ];
                break;
            case 'account_manager':
                $dashboardInfo = [
                    'view' => 'am.detailAM.blade.php',
                    'controller' => 'AmDashboardController::index',
                    'account_manager_id' => $user->account_manager_id
                ];
                break;
            case 'witel_support':
                $dashboardInfo = [
                    'view' => 'witel.detailWitel.blade.php',
                    'controller' => 'WitelDashboardController::index',
                    'witel_id' => $user->witel_id
                ];
                break;
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'account_manager_id' => $user->account_manager_id,
                'witel_id' => $user->witel_id
            ],
            'dashboard_route' => route('dashboard'),
            'dashboard_info' => $dashboardInfo
        ]);
    })->name('debug.user');

    Route::get('debug/leaderboard', function () {
        return response()->json([
            'main_route' => 'GET /leaderboard',
            'route_name' => 'leaderboard',
            'description' => 'Leaderboard AM dengan filter lengkap (periode, witel, divisi, kategori, jenis revenue)',
            'example_url' => url('/leaderboard'),
            'available_filters' => [
                'search' => 'Search by nama AM',
                'witel_filter[]' => 'Array of witel IDs',
                'divisi_filter[]' => 'Array of divisi IDs (1=DGS, 2=DSS, 3=DPS)',
                'category_filter[]' => 'Array: enterprise, government, multi',
                'revenue_type_filter[]' => 'Array: Reguler, NGTMA, Kombinasi',
                'period' => 'year_to_date, current_month, custom',
                'start_date' => 'Y-m-d format (for custom period)',
                'end_date' => 'Y-m-d format (for custom period)',
                'per_page' => 'Items per page (default: 10)'
            ],
            'category_logic' => [
                'enterprise' => 'AM dengan DPS/DSS saja (tanpa DGS)',
                'government' => 'AM dengan DGS saja',
                'multi' => 'AM dengan DGS + (DPS/DSS)'
            ],
            'ranking_basis' => 'Total Real Revenue (descending)',
            'ajax_endpoints' => [
                'am_category' => 'GET /leaderboard/am-category/{id}'
            ]
        ]);
    })->name('debug.leaderboard');

    Route::get('debug/witel-routes', function () {
        return response()->json([
            'main_route' => 'GET /witel/{id}',
            'description' => 'Witel detail page with revenue data from CC and AM sources',
            'example_url' => url('/witel/5'),
            'available_endpoints' => [
                'detail' => '/witel/{id}',
                'info' => '/witel/{id}/info',
                'tab_data' => '/witel/{id}/tab-data',
                'card_data' => '/witel/{id}/card-data',
                'chart_data' => '/witel/{id}/chart-data',
                'export' => '/witel/{id}/export',
                'performance_summary' => '/witel/{id}/performance-summary',
                'top_ams' => '/witel/{id}/top-ams',
                'top_customers' => '/witel/{id}/top-customers',
                'revenue_trend' => '/witel/{id}/revenue-trend'
            ]
        ]);
    })->name('debug.witel-routes');

    Route::get('debug/cc-routes', function () {
        return response()->json([
            'main_route' => 'GET /corporate-customer/{id}',
            'description' => 'Corporate Customer detail page with revenue data and analysis',
            'example_url' => url('/corporate-customer/1'),
            'available_endpoints' => [
                'detail' => '/corporate-customer/{id}',
                'info' => '/corporate-customer/{id}/info',
                'tab_data' => '/corporate-customer/{id}/tab-data',
                'card_data' => '/corporate-customer/{id}/card-data',
                'chart_data' => '/corporate-customer/{id}/chart-data',
                'export' => '/corporate-customer/{id}/export',
                'revenue_history' => '/corporate-customer/{id}/revenue-history',
                'account_managers' => '/corporate-customer/{id}/account-managers'
            ]
        ]);
    })->name('debug.cc-routes');

    Route::get('debug/am-routes', function () {
        return response()->json([
            'main_routes' => [
                'dashboard_am' => 'GET /dashboard', // (when logged in as AM)
                'detail_am_from_leaderboard' => 'GET /account-manager/{id}',
                'leaderboard' => 'GET /leaderboard'
            ],
            'ajax_endpoints' => [
                'tab_data' => 'GET /account-manager/{id}/tab-data',
                'card_data' => 'GET /account-manager/{id}/card-data',
                'ranking' => 'GET /account-manager/{id}/ranking',
                'chart_data' => 'GET /account-manager/{id}/chart-data',
                'performance_summary' => 'GET /account-manager/{id}/performance-summary',
                'update_filters' => 'GET /account-manager/{id}/update-filters',
                'export' => 'GET /account-manager/{id}/export'
            ],
            'additional_endpoints' => [
                'info' => 'GET /account-manager/{id}/info',
                'compare' => 'GET /account-manager/{id}/compare',
                'trend' => 'GET /account-manager/{id}/trend',
                'top_customers' => 'GET /account-manager/{id}/top-customers'
            ]
        ]);
    })->name('debug.am-routes');

    Route::get('debug/import-routes', function () {
        return response()->json([
            'two_step_import' => [
                'step_1_preview' => 'POST /revenue-data/import/preview',
                'step_2_execute' => 'POST /revenue-data/import/execute'
            ],
            'legacy_single_step' => 'POST /revenue-data/import',
            'description' => '2-step process with preview and confirmation for duplicate handling',
            'access_control' => 'Admin only - non-admin redirected to dashboard',
            'import_types' => [
                'data_cc' => 'Import Data Corporate Customer',
                'data_am' => 'Import Data Account Manager',
                'revenue_cc' => 'Import Revenue Corporate Customer',
                'revenue_am' => 'Import Revenue AM Mapping'
            ],
            'direct_endpoints' => [
                'data_cc' => 'POST /revenue-data/import-data-cc',
                'data_am' => 'POST /revenue-data/import-data-am',
                'revenue_cc' => 'POST /revenue-data/import-revenue-cc',
                'revenue_am' => 'POST /revenue-data/import-revenue-am'
            ],
            'template_downloads' => [
                'data_cc' => 'GET /revenue-data/template/data-cc',
                'data_am' => 'GET /revenue-data/template/data-am',
                'revenue_cc_dgs' => 'GET /revenue-data/template/revenue-cc-dgs',
                'revenue_cc_dps' => 'GET /revenue-data/template/revenue-cc-dps',
                'revenue_am' => 'GET /revenue-data/template/revenue-am'
            ],
            'additional_endpoints' => [
                'download_error_log' => 'GET /revenue-data/download-error-log/{filename}',
                'import_history' => 'GET /revenue-data/import-history',
                'validate_import' => 'POST /revenue-data/validate-import (legacy)'
            ],
            'preview_parameters' => [
                'file' => 'CSV file to import (required)',
                'import_type' => 'Type of import: data_cc|data_am|revenue_cc|revenue_am (required)',
                'divisi_id' => 'Division ID (required for revenue_cc)',
                'jenis_data' => 'Data type: revenue|target (required for revenue_cc)'
            ],
            'execute_parameters' => [
                'session_id' => 'Session ID from preview response (required)',
                'confirmed_updates' => 'Array of IDs to update (optional)',
                'skip_updates' => 'Array of IDs to skip (optional)'
            ]
        ]);
    })->name('debug.import-routes');

    Route::get('debug/database', function () {
        try {
            $stats = [
                'account_managers' => AccountManager::count(),
                'corporate_customers' => CorporateCustomer::count(),
                'cc_revenues' => CcRevenue::count(),
                'am_revenues' => DB::table('am_revenues')->count(),
                'divisi' => Divisi::count(),
                'witel' => Witel::count(),
                'segments' => Segment::count(),
                'users' => DB::table('users')->count()
            ];

            $latestData = [
                'latest_cc_revenue_year' => CcRevenue::max('tahun'),
                'latest_cc_revenue_month' => CcRevenue::where('tahun', CcRevenue::max('tahun'))->max('bulan'),
                'total_revenue_ytd' => CcRevenue::where('tahun', date('Y'))->sum('real_revenue'),
                'total_target_ytd' => CcRevenue::where('tahun', date('Y'))->sum('target_revenue')
            ];

            return response()->json([
                'status' => 'success',
                'database_stats' => $stats,
                'latest_data' => $latestData,
                'achievement_ytd' => $latestData['total_target_ytd'] > 0
                    ? round(($latestData['total_revenue_ytd'] / $latestData['total_target_ytd']) * 100, 2) . '%'
                    : '0%'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('debug.database');

    Route::get('debug/import-test', function () {
        return view('debug.import-test');
    })->name('debug.import-test');

    Route::get('debug/table-names', function () {
        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = DB::getDatabaseName();

            $tableNames = array_map(function ($table) use ($dbName) {
                $key = "Tables_in_{$dbName}";
                return $table->$key;
            }, $tables);

            return response()->json([
                'status' => 'success',
                'database' => $dbName,
                'total_tables' => count($tableNames),
                'tables' => $tableNames,
                'check' => [
                    'has_witel' => in_array('witel', $tableNames),
                    'has_witels' => in_array('witels', $tableNames),
                    'correct_table_name' => in_array('witel', $tableNames) ? 'witel' : 'witels'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('debug.table-names');

    Route::get('debug/role-access', function () {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json([
            'user' => [
                'name' => $user->name,
                'role' => $user->role
            ],
            'access_rights' => [
                'can_access_revenue_data' => $user->role === 'admin',
                'can_view_leaderboard' => true,
                'can_view_dashboard' => true,
                'redirect_if_not_admin' => $user->role !== 'admin' ? route('dashboard') : null
            ],
            'available_routes' => [
                'dashboard' => route('dashboard'),
                'leaderboard' => route('leaderboard'),
                'revenue_data' => $user->role === 'admin' ? route('revenue.data') : 'ACCESS DENIED'
            ]
        ]);
    })->name('debug.role-access');

    // 🆕 Debug High Five Routes (UPDATED & ENHANCED)
    Route::get('debug/high-five', function () {
        return response()->json([
            'main_route' => 'GET /high-five',
            'route_name' => 'high-five.index',
            'description' => 'High Five RLEGS TR3 - Monitoring Performa Mingguan AM dan Produk High Five (🔄 REVISED: Now uses snapshot architecture)',
            'example_url' => url('/high-five'),
            'architecture_change' => [
                'old_system' => 'Direct Google Sheets API fetch on every request',
                'new_system' => 'Snapshot-based: Fetch → Store in DB → Analyze from DB',
                'benefits' => [
                    'No repeated Google Sheets API calls',
                    'Historical data tracking',
                    'Faster analysis',
                    'Data versioning'
                ]
            ],
            'available_endpoints' => [
                'dashboard' => [
                    'index' => 'GET /high-five',
                    'get_snapshots' => 'GET /high-five/snapshots?divisi_id={id}',
                    'get_latest_snapshots' => 'GET /high-five/latest-snapshots?divisi_id={id}',
                    'get_available_links' => 'GET /high-five/available-links',
                    'fetch_manual' => 'POST /high-five/fetch-manual'
                ],
                'settings' => [
                    'index' => 'GET /high-five/settings',
                    'store' => 'POST /high-five/settings/store',
                    'update' => 'PUT /high-five/settings/update/{id}',
                    'delete' => 'DELETE /high-five/settings/delete/{id}',
                    'fetch_now' => 'POST /high-five/settings/fetch-now/{id}',
                    'history' => 'GET /high-five/settings/history/{id}',
                    'retry_snapshot' => 'POST /high-five/settings/retry-snapshot/{id}'
                ],
                'analysis' => [
                    'am_performance' => 'GET /high-five/am-performance?snapshot_1_id={id1}&snapshot_2_id={id2}',
                    'product_performance' => 'GET /high-five/product-performance?snapshot_1_id={id1}&snapshot_2_id={id2}'
                ],
                'reports' => [
                    'download' => 'GET /high-five/report/download?snapshot_1_id={id1}&snapshot_2_id={id2}',
                    'preview' => 'GET /high-five/report/preview?snapshot_1_id={id1}&snapshot_2_id={id2}'
                ],
                'deprecated' => [
                    'store_dataset' => 'POST /high-five/dataset/store (kept for backward compatibility)',
                    'get_datasets_by_divisi' => 'GET /high-five/dataset/by-divisi (kept for backward compatibility)',
                    'delete_dataset' => 'DELETE /high-five/dataset/{id} (kept for backward compatibility)'
                ]
            ],
            'workflow' => [
                'step_1_settings' => 'Add dataset link in modal (from dashboard)',
                'step_2_fetch' => 'System fetches data from Google Sheets → Stores as snapshot',
                'step_3_user' => 'User selects 2 snapshots → Compares performance',
                'step_4_report' => 'Generate PDF report with insights'
            ],
            'database_tables' => [
                'dataset_links' => 'Stores Google Sheets links per divisi',
                'spreadsheet_snapshots' => 'Stores fetched data with metadata'
            ],
            'features' => [
                'modal_management' => 'Kelola Link Spreadsheet modal with CRUD',
                'snapshot_management' => 'Historical data tracking',
                'benchmarking' => 'Compare 2 snapshots (minggu ini vs minggu lalu)',
                'am_level_analysis' => 'Performa per Account Manager dengan leaderboard',
                'product_level_analysis' => 'Performa per produk per Corporate Customer',
                'witel_summary' => 'Average per witel calculation',
                'progress_bars' => 'Visual progress bars in tables',
                'pdf_report' => 'Generate laporan profesional dalam format PDF',
                'auto_date_calculation' => 'Smart last Friday detection',
                'manual_date_override' => 'User can specify custom dates'
            ],
            'ui_improvements' => [
                '4_cards_horizontal' => 'Analysis cards dalam 1 baris',
                'tab_inactive_on_empty' => 'Tab tidak aktif saat belum load data',
                'solid_white_alerts' => 'Alert dengan background solid white',
                'red_table_headers' => 'Table header dengan gradient merah',
                'progress_bar_cells' => 'Progress bar di setiap cell tabel',
                'accordion_leaderboard' => 'Leaderboard collapsible',
                'witel_filter' => 'Filter witel untuk product level',
                'empty_customer_handling' => 'Default value untuk customer kosong'
            ],
            'required_columns_in_spreadsheet' => [
                'CUSTOMER_NAME',
                'WITEL',
                'AM',
                'PRODUCT',
                'NILAI',
                'Progress',
                'Result',
                'ID LOP MyTens',
                '% Progress',
                '% Results'
            ],
            'progress_mapping' => [
                '1. Visit' => '25%',
                '2. Input MyTens' => '50%',
                '3. Presentasi Layanan' => '75%',
                '4. Submit SPH' => '100%'
            ],
            'result_mapping' => [
                '1. Lose' => '0%',
                '2. Prospect' => '0%',
                '3. Negotiation' => '50%',
                '4. Win' => '100%'
            ]
        ]);
    })->name('debug.high-five');
}

// ===== FALLBACK =====
Route::fallback(function () {
    if (request()->wantsJson()) {
        return response()->json([
            'error' => 'Route not found',
            'available_routes' => [
                'dashboard' => route('dashboard'),
                'leaderboard' => route('leaderboard'),
                'high_five' => route('high-five.index'),
                'health_check' => route('health-check')
            ]
        ], 404);
    }

    return view('errors.404');
});

require __DIR__ . '/auth.php';