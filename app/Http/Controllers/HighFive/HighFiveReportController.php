<?php

namespace App\Http\Controllers\HighFive;

use App\Http\Controllers\Controller;
use App\Models\SpreadsheetSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class HighFiveReportController extends Controller
{
    protected $amPerformanceController;
    protected $productPerformanceController;

    public function __construct(
        HighFiveAMPerformanceController $amPerformanceController,
        HighFiveProductPerformanceController $productPerformanceController
    ) {
        $this->amPerformanceController = $amPerformanceController;
        $this->productPerformanceController = $productPerformanceController;
    }

    /**
     * Helper function untuk memproses data report (agar logika tidak ditulis ulang 2x)
     */
    private function getReportData(Request $request)
    {
        // 1. Fetch Basic Data
        $snapshot1 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_1_id);
        $snapshot2 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_2_id);

        if ($snapshot1->divisi_id !== $snapshot2->divisi_id) {
            throw new \Exception('Snapshot harus dari divisi yang sama');
        }
        if ($snapshot1->fetch_status !== 'success' || $snapshot2->fetch_status !== 'success') {
            throw new \Exception('Hanya snapshot dengan status success yang bisa digunakan');
        }

        // 2. Get Raw Performance Data
        $reqAm = new Request(['snapshot_1_id' => $request->snapshot_1_id, 'snapshot_2_id' => $request->snapshot_2_id]);
        $amData = json_decode($this->amPerformanceController->getAMPerformance($reqAm)->content(), true)['data'];

        $reqProd = new Request(['snapshot_1_id' => $request->snapshot_1_id, 'snapshot_2_id' => $request->snapshot_2_id]);
        $productData = json_decode($this->productPerformanceController->getProductPerformance($reqProd)->content(), true)['data'];

        // 3. Define Witel Scope
        $selectedWitel = $request->input('witel');
        $isWitelSpecific = $selectedWitel && $selectedWitel !== 'all';
        $witel_name = $isWitelSpecific ? $selectedWitel : "TREG 3";

        // Variables for View
        $witelSpecificMetrics = null;
        $witelAMList = [];
        $witelProductDetails = [];
        $overallSummaryData = [];

        // ==========================================
        // LOGIC UTAMA PERBAIKAN DATA WITEL
        // ==========================================
        if ($isWitelSpecific) {
            // A. Filter AM Benchmarking (Hanya AM di Witel ini)
            $amData['benchmarking'] = array_values(array_filter($amData['benchmarking'], function($row) use ($selectedWitel) {
                return trim($row['witel']) === trim($selectedWitel);
            }));

            // Buat daftar Nama AM yang valid di Witel ini untuk filter produk
            $validAMNames = array_column($amData['benchmarking'], 'am');

            // B. Hitung Ulang Metrics AM (Improvement, Win Rate)
            $totalWins = 0; 
            $totalLosses = 0;
            $sumChangeProgress = 0; 
            $sumChangeResult = 0;
            $totalCCs = []; 
            $visitedCCs = [];
            $amCount = count($amData['benchmarking']);

            foreach ($amData['benchmarking'] as $am) {
                $totalWins += $am['stats']['win'] ?? 0;
                $totalLosses += $am['stats']['lose'] ?? 0;
                $sumChangeProgress += $am['change_progress'] ?? 0;
                $sumChangeResult += $am['change_result'] ?? 0;
                
                $totalCCs[$am['am']] = $am['stats']['total_customers'] ?? 0;
                $visitedCCs[$am['am']] = $am['stats']['visited'] ?? 0;
            }

            // C. RE-CALCULATE PRODUCT METRICS
            $witelTotalOfferings = 0;
            $witelProductCounts = [];
            $witelProductWins = [];

            foreach ($productData['products'] ?? [] as $product) {
                // Skip jika AM pemilik produk tidak ada di Witel ini
                if (!in_array($product['am'], $validAMNames)) {
                    continue;
                }

                $witelTotalOfferings++;
                $pName = $product['product'];
                $witelProductCounts[$pName] = ($witelProductCounts[$pName] ?? 0) + 1;

                $res = strtolower($product['result'] ?? '');
                if (strpos($res, 'win') !== false || ($product['result_2'] ?? 0) == 100) {
                    $witelProductWins[$pName] = ($witelProductWins[$pName] ?? 0) + 1;
                }

                // Data untuk Detail Table
                $am = $product['am'];
                $customer = $product['customer'] ?: '(No Customer Data)';
                if ($customer === $product['product']) $customer = '(No Customer Data)';

                if (!isset($witelProductDetails[$am])) $witelProductDetails[$am] = [];
                if (!isset($witelProductDetails[$am][$customer])) $witelProductDetails[$am][$customer] = [];

                $witelProductDetails[$am][$customer][] = [
                    'product' => $pName,
                    'progress_1' => $product['progress_1'] ?? 0,
                    'progress_2' => $product['progress_2'] ?? 0,
                    'result_1' => $product['result_1'] ?? 0,
                    'result_2' => $product['result_2'] ?? 0,
                    'change_avg' => (($product['change_progress'] ?? 0) + ($product['change_result'] ?? 0)) / 2,
                ];
            }

            // D. Susun Ulang Leaderboard Produk (Top 10)
            arsort($witelProductCounts);
            $newTop10 = [];
            foreach (array_slice($witelProductCounts, 0, 10) as $name => $count) {
                $newTop10[] = [
                    'product' => $name,
                    'total_offerings' => $count,
                    'wins' => $witelProductWins[$name] ?? 0
                ];
            }
            $productData['product_leaderboard']['top_10'] = $newTop10;

            // E. Finalize Witel Metrics
            $totalCCCount = array_sum($totalCCs);
            $visitedCCCount = array_sum($visitedCCs);
            $totalClosed = $totalWins + $totalLosses;
            $winRate = $totalClosed > 0 ? round(($totalWins / $totalClosed) * 100, 1) : 0;

            $witelSpecificMetrics = [
                'coverage_ratio' => "$visitedCCCount/$totalCCCount CC visited ($witelTotalOfferings offerings)",
                'avg_progress_improvement' => $amCount > 0 ? round($sumChangeProgress / $amCount, 2) : 0,
                'avg_result_improvement' => $amCount > 0 ? round($sumChangeResult / $amCount, 2) : 0,
                'win_rate' => $winRate,
                'total_wins' => $totalWins,
                'total_losses' => $totalLosses
            ];

            // F. Sort AM Leaderboard
            $witelAMList = $amData['benchmarking'];
            usort($witelAMList, fn($a, $b) => ($b['change_avg'] ?? 0) <=> ($a['change_avg'] ?? 0));
            $amData['leaderboard'] = $witelAMList;
        } 
        
        // ==========================================
        // LOGIC GLOBAL (TREG3)
        // ==========================================
        else {
            foreach ($productData['products'] ?? [] as $product) {
                $witel = $product['witel'] ?? 'Unknown';
                $am = $product['am'] ?? 'Unknown';
                $customer = $product['customer'] ?? null;
                if (empty($am) || empty($customer) || $customer === $product['product']) continue;

                if (!isset($overallSummaryData[$witel][$am][$customer])) {
                    $overallSummaryData[$witel][$am][$customer] = ['sum_p1'=>0, 'sum_p2'=>0, 'sum_r1'=>0, 'sum_r2'=>0, 'count'=>0];
                }
                $d = &$overallSummaryData[$witel][$am][$customer];
                $d['sum_p1'] += $product['progress_1'] ?? 0;
                $d['sum_p2'] += $product['progress_2'] ?? 0;
                $d['sum_r1'] += $product['result_1'] ?? 0;
                $d['sum_r2'] += $product['result_2'] ?? 0;
                $d['count']++;
            }
            // Compute Averages
            foreach ($overallSummaryData as $w => &$wData) {
                foreach ($wData as $a => &$cData) {
                    foreach ($cData as $c => &$d) {
                        $cnt = $d['count'];
                        $d['avg_progress_1'] = $cnt ? $d['sum_p1']/$cnt : 0;
                        $d['avg_progress_2'] = $cnt ? $d['sum_p2']/$cnt : 0;
                        $d['avg_result_1'] = $cnt ? $d['sum_r1']/$cnt : 0;
                        $d['avg_result_2'] = $cnt ? $d['sum_r2']/$cnt : 0;
                        $d['avg_change'] = (($d['avg_progress_2']-$d['avg_progress_1']) + ($d['avg_result_2']-$d['avg_result_1']))/2;
                    }
                }
            }
        }

        $witelLabel = $isWitelSpecific ? " - Witel {$selectedWitel}" : " - Semua Witel";
        
        return [
            'data' => [
                'title' => 'Executive Summary High Five',
                'subtitle' => 'Perbandingan Performa Account Manager dan Produk High Five' . $witelLabel,
                'generated_at' => now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm'),
                'divisi' => $snapshot1->divisi->nama_divisi ?? $snapshot1->divisi->kode ?? 'Unknown',
                'is_witel_specific' => $isWitelSpecific,
                'witel_name' => $witel_name,
                'selected_witel' => $selectedWitel,
                'snapshot_1' => ['date' => $snapshot1->formatted_date],
                'snapshot_2' => ['date' => $snapshot2->formatted_date],
                'am_performance' => $amData,
                'product_performance' => $productData,
                'witel_specific_metrics' => $witelSpecificMetrics,
                'witel_am_list' => $witelAMList,
                'witel_product_details' => $witelProductDetails,
                'overall_summary_data' => $overallSummaryData,
            ],
            'snapshot1' => $snapshot1,
            'snapshot2' => $snapshot2,
            'witel_name' => $witel_name
        ];
    }

    public function downloadReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'snapshot_1_id' => 'required|exists:spreadsheet_snapshots,id',
            'snapshot_2_id' => 'required|exists:spreadsheet_snapshots,id',
            'witel' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Snapshot tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            // Panggil helper function agar logic SAMA PERSIS dengan preview
            $result = $this->getReportData($request);
            $reportData = $result['data'];
            $witel_name = $result['witel_name'];
            $snapshot2 = $result['snapshot2'];

            $pdf = Pdf::loadView('high-five.report-pdf', $reportData)
                ->setPaper('a4', 'landscape')
                ->setOption(['isPhpEnabled' => true]);

            $filename = 'Laporan_HighFive_' . str_replace(' ', '_', $witel_name) . '_' . now()->format('YmdHis') . '.pdf';
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal generate laporan: ' . $e->getMessage()], 500);
        }
    }

    public function previewReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'snapshot_1_id' => 'required|exists:spreadsheet_snapshots,id',
            'snapshot_2_id' => 'required|exists:spreadsheet_snapshots,id',
            'witel' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Snapshot tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            // Panggil helper function agar logic SAMA PERSIS dengan download
            // Ini memastikan filter witel, sorting, dan calculation juga terjadi di PREVIEW
            $result = $this->getReportData($request);
            $reportData = $result['data'];

            $pdf = Pdf::loadView('high-five.report-pdf', $reportData)
                ->setPaper('a4', 'landscape')
                ->setOption(['isPhpEnabled' => true]);

            return $pdf->stream('preview.pdf');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal preview laporan: ' . $e->getMessage()], 500);
        }
    }
}