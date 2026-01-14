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
     * 🔄 REVISED: Generate and download PDF report
     *
     * INPUT CHANGES:
     * - OLD: dataset_1_id, dataset_2_id
     * - NEW: snapshot_1_id, snapshot_2_id
     * - ➕ NEW: witel (optional) - filter by specific Witel
     *
     * ✅ PRESERVED: All PDF generation logic remains the same
     */
    public function downloadReport(Request $request)
    {
        // 🔄 CHANGED: Validation input (witel is optional)
        $validator = Validator::make($request->all(), [
            'snapshot_1_id' => 'required|exists:spreadsheet_snapshots,id',
            'snapshot_2_id' => 'required|exists:spreadsheet_snapshots,id',
            'witel' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Snapshot tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 🔄 CHANGED: Get snapshots info instead of datasets
            $snapshot1 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_1_id);
            $snapshot2 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_2_id);

            // Validate same divisi
            if ($snapshot1->divisi_id !== $snapshot2->divisi_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot harus dari divisi yang sama'
                ], 422);
            }

            // Validate both snapshots are successful
            if ($snapshot1->fetch_status !== 'success' || $snapshot2->fetch_status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya snapshot dengan status success yang bisa digunakan'
                ], 422);
            }

            // ✅ PRESERVED: Get AM Level data (only changed parameter names)
            $amPerformanceRequest = new Request([
                'snapshot_1_id' => $request->snapshot_1_id,
                'snapshot_2_id' => $request->snapshot_2_id,
            ]);
            $amPerformanceResponse = $this->amPerformanceController->getAMPerformance($amPerformanceRequest);
            $amData = json_decode($amPerformanceResponse->content(), true)['data'];

            // ➕ NEW: Filter AM data by Witel if specified
            $selectedWitel = $request->input('witel');
            $isWitelSpecific = $selectedWitel && $selectedWitel !== 'all';
            
            if ($isWitelSpecific) {
                $amData['benchmarking'] = array_filter($amData['benchmarking'], function($row) use ($selectedWitel) {
                    return $row['witel'] === $selectedWitel;
                });
                $amData['benchmarking'] = array_values($amData['benchmarking']); // Re-index array
            }

            // ✅ PRESERVED: Get Product Level data (only changed parameter names)
            $productPerformanceRequest = new Request([
                'snapshot_1_id' => $request->snapshot_1_id,
                'snapshot_2_id' => $request->snapshot_2_id,
            ]);
            $productPerformanceResponse = $this->productPerformanceController->getProductPerformance($productPerformanceRequest);
            $productData = json_decode($productPerformanceResponse->content(), true)['data'];

            // ➕ NEW: Calculate Witel-specific metrics if filtering by Witel
            $witelSpecificMetrics = null;
            $witelAMList = [];
            
            if ($isWitelSpecific) {
                // Calculate metrics from filtered AM data
                $totalWins = 0;
                $totalLosses = 0;
                $totalCCs = [];
                $visitedCCs = [];
                $sumChangeProgress = 0;
                $sumChangeResult = 0;
                $amCount = count($amData['benchmarking']);
                
                foreach ($amData['benchmarking'] as $am) {
                    $totalWins += $am['stats']['win'] ?? 0;
                    $totalLosses += $am['stats']['lose'] ?? 0;
                    $sumChangeProgress += $am['change_progress'] ?? 0;
                    $sumChangeResult += $am['change_result'] ?? 0;
                    
                    // Track CCs from this AM's stats
                    $totalCCs[$am['am']] = $am['stats']['total_customers'] ?? 0;
                    $visitedCCs[$am['am']] = $am['stats']['visited'] ?? 0;
                }
                
                $totalCCCount = array_sum($totalCCs);
                $visitedCCCount = array_sum($visitedCCs);
                $totalOfferings = 0;
                
                // Count offerings from product data for this Witel
                foreach ($productData['products'] ?? [] as $product) {
                    foreach ($amData['benchmarking'] as $am) {
                        if ($product['am'] === $am['am']) {
                            $totalOfferings++;
                            break;
                        }
                    }
                }
                
                $totalClosed = $totalWins + $totalLosses;
                $winRate = $totalClosed > 0 ? round(($totalWins / $totalClosed) * 100, 1) : 0;
                $avgProgressImprovement = $amCount > 0 ? round($sumChangeProgress / $amCount, 2) : 0;
                $avgResultImprovement = $amCount > 0 ? round($sumChangeResult / $amCount, 2) : 0;
                
                $witelSpecificMetrics = [
                    'coverage_ratio' => "$visitedCCCount/$totalCCCount CC visited (total $totalOfferings product offerings)",
                    'avg_progress_improvement' => $avgProgressImprovement,
                    'avg_result_improvement' => $avgResultImprovement,
                    'win_rate' => $winRate,
                    'total_wins' => $totalWins,
                    'total_losses' => $totalLosses,
                ];
                
                // Prepare AM list sorted by improvement
                $witelAMList = $amData['benchmarking'];
                usort($witelAMList, function($a, $b) {
                    return ($b['change_avg'] ?? 0) <=> ($a['change_avg'] ?? 0);
                });
                
                // ➕ NEW: Prepare detailed product data grouped by AM → Customer → Product
                $witelAMNames = array_column($amData['benchmarking'], 'am');
                $witelProductDetails = [];
                
                foreach ($productData['products'] ?? [] as $product) {
                    $am = $product['am'] ?? '';
                    
                    // ✅ FIX: Skip products with empty AM or AM not in this Witel
                    if (empty($am) || !in_array($am, $witelAMNames)) {
                        continue;
                    }
                    
                    // ✅ FIX: Handle empty/null customer by using placeholder
                    $customer = $product['customer'] ?? null;
                    if (empty($customer) || $customer === $product['product']) {
                        $customer = '(No Customer Data)';
                    }
                    $productName = $product['product'] ?? 'Unknown';
                    
                    if (!isset($witelProductDetails[$am])) {
                        $witelProductDetails[$am] = [];
                    }
                    if (!isset($witelProductDetails[$am][$customer])) {
                        $witelProductDetails[$am][$customer] = [];
                    }
                    
                    $witelProductDetails[$am][$customer][] = [
                        'product' => $productName,
                        'progress_1' => $product['progress_1'] ?? 0,
                        'progress_2' => $product['progress_2'] ?? 0,
                        'result_1' => $product['result_1'] ?? 0,
                        'result_2' => $product['result_2'] ?? 0,
                        'change_avg' => (($product['change_progress'] ?? 0) + ($product['change_result'] ?? 0)) / 2,
                    ];
                }
            }

            // ➕ NEW: Prepare overall summary data for Section IV (only for overall reports)
            $overallSummaryData = [];
            if (!$isWitelSpecific) {
                // Group by Witel → AM → Customer and calculate averages
                foreach ($productData['products'] ?? [] as $product) {
                    $witel = $product['witel'] ?? 'Unknown';
                    $am = $product['am'] ?? 'Unknown';
                    $customer = $product['customer'] ?? null;
                    
                    // Skip if missing key data
                    if (empty($am) || empty($customer) || $customer === $product['product']) {
                        continue;
                    }
                    
                    if (!isset($overallSummaryData[$witel])) {
                        $overallSummaryData[$witel] = [];
                    }
                    if (!isset($overallSummaryData[$witel][$am])) {
                        $overallSummaryData[$witel][$am] = [];
                    }
                    if (!isset($overallSummaryData[$witel][$am][$customer])) {
                        $overallSummaryData[$witel][$am][$customer] = [
                            'sum_progress_1' => 0,
                            'sum_progress_2' => 0,
                            'sum_result_1' => 0,
                            'sum_result_2' => 0,
                            'count' => 0
                        ];
                    }
                    
                    // Accumulate sums
                    $overallSummaryData[$witel][$am][$customer]['sum_progress_1'] += $product['progress_1'] ?? 0;
                    $overallSummaryData[$witel][$am][$customer]['sum_progress_2'] += $product['progress_2'] ?? 0;
                    $overallSummaryData[$witel][$am][$customer]['sum_result_1'] += $product['result_1'] ?? 0;
                    $overallSummaryData[$witel][$am][$customer]['sum_result_2'] += $product['result_2'] ?? 0;
                    $overallSummaryData[$witel][$am][$customer]['count']++;
                }
                
                // Calculate averages
                foreach ($overallSummaryData as $witel => &$witelAmData) {
                    foreach ($witelAmData as $am => &$customerData) {
                        foreach ($customerData as $customer => &$data) {
                            $count = $data['count'];
                            $data['avg_progress_1'] = $count > 0 ? $data['sum_progress_1'] / $count : 0;
                            $data['avg_progress_2'] = $count > 0 ? $data['sum_progress_2'] / $count : 0;
                            $data['avg_result_1'] = $count > 0 ? $data['sum_result_1'] / $count : 0;
                            $data['avg_result_2'] = $count > 0 ? $data['sum_result_2'] / $count : 0;
                            $data['avg_change'] = (($data['avg_progress_2'] - $data['avg_progress_1']) + 
                                                   ($data['avg_result_2'] - $data['avg_result_1'])) / 2;
                        }
                    }
                }
            }

            // ✅ PRESERVED: Prepare data for PDF (only changed dataset → snapshot references)
            $witelLabel = $isWitelSpecific ? " - Witel {$selectedWitel}" : " - Semua Witel";
            $reportData = [
                'title' => 'Laporan Benchmarking Performa High Five RLEGS TR3',
                'subtitle' => 'Perbandingan Performa Account Manager dan Produk High Five' . $witelLabel,
                'generated_at' => now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm'),
                'divisi' => $snapshot1->divisi->nama ?? $snapshot1->divisi->kode ?? 'Unknown',
                'snapshot_1' => [
                    'name' => $snapshot1->display_name,
                    'date' => $snapshot1->formatted_date,
                ],
                'snapshot_2' => [
                    'name' => $snapshot2->display_name,
                    'date' => $snapshot2->formatted_date,
                ],
                'am_performance' => $amData,
                'product_performance' => $productData,
                'is_witel_specific' => $isWitelSpecific,
                'selected_witel' => $selectedWitel,
                'witel_specific_metrics' => $witelSpecificMetrics,
                'witel_am_list' => $witelAMList,
                'witel_product_details' => $witelProductDetails ?? [],
                'overall_summary_data' => $overallSummaryData,
            ];

            // ✅ PRESERVED: Generate PDF (unchanged)
            $pdf = Pdf::loadView('high-five.report-pdf', $reportData)
                ->setPaper('a4', 'portrait')  // Portrait orientation as reference
                ->setOption('margin-top', '25.4mm')     // 2.54cm = 25.4mm = 1 inch
                ->setOption('margin-bottom', '25.4mm')
                ->setOption('margin-left', '25.4mm')
                ->setOption('margin-right', '25.4mm');

            // ✅ PRESERVED: Filename format (only changed dataset → snapshot reference)
            $witelFilename = ($selectedWitel && $selectedWitel !== 'all') ? "_{$selectedWitel}" : "";
            $filename = 'Laporan_HighFive_' .
                        str_replace(' ', '_', $snapshot1->divisi->kode ?? 'Unknown') . 
                        $witelFilename . '_' .
                        $snapshot2->snapshot_date->format('Ymd') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ PRESERVED: Preview report in browser (optional feature)
     *
     * 🔄 CHANGED: Input validation only
     */
    public function previewReport(Request $request)
    {
        // 🔄 CHANGED: Validation input
        $validator = Validator::make($request->all(), [
            'snapshot_1_id' => 'required|exists:spreadsheet_snapshots,id',
            'snapshot_2_id' => 'required|exists:spreadsheet_snapshots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Snapshot tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 🔄 CHANGED: Get snapshots info
            $snapshot1 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_1_id);
            $snapshot2 = SpreadsheetSnapshot::with('divisi')->findOrFail($request->snapshot_2_id);

            // Validate same divisi
            if ($snapshot1->divisi_id !== $snapshot2->divisi_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot harus dari divisi yang sama'
                ], 422);
            }

            // Validate both snapshots are successful
            if ($snapshot1->fetch_status !== 'success' || $snapshot2->fetch_status !== 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya snapshot dengan status success yang bisa digunakan'
                ], 422);
            }

            // ✅ PRESERVED: Get AM & Product data
            $amPerformanceRequest = new Request([
                'snapshot_1_id' => $request->snapshot_1_id,
                'snapshot_2_id' => $request->snapshot_2_id,
            ]);
            $amPerformanceResponse = $this->amPerformanceController->getAMPerformance($amPerformanceRequest);
            $amData = json_decode($amPerformanceResponse->content(), true)['data'];

            $productPerformanceRequest = new Request([
                'snapshot_1_id' => $request->snapshot_1_id,
                'snapshot_2_id' => $request->snapshot_2_id,
            ]);
            $productPerformanceResponse = $this->productPerformanceController->getProductPerformance($productPerformanceRequest);
            $productData = json_decode($productPerformanceResponse->content(), true)['data'];

            // ✅ PRESERVED: Prepare PDF data
            $reportData = [
                'title' => 'Laporan Benchmarking Performa High Five RLEGS TR3',
                'subtitle' => 'Perbandingan Performa Account Manager dan Produk High Five',
                'generated_at' => now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm'),
                'divisi' => $snapshot1->divisi->nama ?? $snapshot1->divisi->kode ?? 'Unknown',
                'snapshot_1' => [
                    'name' => $snapshot1->display_name,
                    'date' => $snapshot1->formatted_date,
                ],
                'snapshot_2' => [
                    'name' => $snapshot2->display_name,
                    'date' => $snapshot2->formatted_date,
                ],
                'am_performance' => $amData,
                'product_performance' => $productData,
            ];

            // ✅ PRESERVED: Generate PDF with stream() instead of download()
            $pdf = Pdf::loadView('high-five.report-pdf', $reportData)
                ->setPaper('a4', 'portrait')  // Portrait orientation as reference
                ->setOption('margin-top', '25.4mm')     // 2.54cm = 25.4mm = 1 inch
                ->setOption('margin-bottom', '25.4mm')
                ->setOption('margin-left', '25.4mm')
                ->setOption('margin-right', '25.4mm');

            $filename = 'Laporan_HighFive_' .
                        str_replace(' ', '_', $snapshot1->divisi->kode ?? 'Unknown') . '_' .
                        $snapshot2->snapshot_date->format('Ymd') . '.pdf';

            return $pdf->stream($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal preview laporan: ' . $e->getMessage()
            ], 500);
        }
    }
}