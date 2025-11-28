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
     *
     * ✅ PRESERVED: All PDF generation logic remains the same
     */
    public function downloadReport(Request $request)
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

            // ✅ PRESERVED: Get Product Level data (only changed parameter names)
            $productPerformanceRequest = new Request([
                'snapshot_1_id' => $request->snapshot_1_id,
                'snapshot_2_id' => $request->snapshot_2_id,
            ]);
            $productPerformanceResponse = $this->productPerformanceController->getProductPerformance($productPerformanceRequest);
            $productData = json_decode($productPerformanceResponse->content(), true)['data'];

            // ✅ PRESERVED: Prepare data for PDF (only changed dataset → snapshot references)
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

            // ✅ PRESERVED: Generate PDF (unchanged)
            $pdf = Pdf::loadView('high-five.report-pdf', $reportData)
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            // ✅ PRESERVED: Filename format (only changed dataset → snapshot reference)
            $filename = 'Laporan_HighFive_' .
                        str_replace(' ', '_', $snapshot1->divisi->kode ?? 'Unknown') . '_' .
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
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

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