<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AmDashboardExport implements WithMultipleSheets
{
    protected $exportData;
    protected $accountManager;
    protected $filters;

    public function __construct($exportData, $accountManager, $filters)
    {
        $this->exportData = $exportData;
        $this->accountManager = $accountManager;
        $this->filters = $filters;
    }

    /**
     * Generate multiple sheets
     */
    public function sheets(): array
    {
        return [
            new ProfileSheet($this->exportData['profile'], $this->accountManager),
            new SummarySheet($this->exportData['summary'], $this->filters),
            new RankingSheet($this->exportData['ranking'], $this->accountManager),
            new CustomerAgregatCCSheet($this->exportData['customer_data']['agregat_cc']),
            new CustomerAgregatBulanSheet($this->exportData['customer_data']['agregat_bulan']),
            new CustomerDetailSheet($this->exportData['customer_data']['detail']),
            new PerformanceAnalysisSheet($this->exportData['performance']),
            new MonthlyChartDataSheet($this->exportData['monthly_chart_data'])
        ];
    }
}

/**
 * SHEET 1: Profile & Info Account Manager
 */
class ProfileSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $profileData;
    protected $accountManager;

    public function __construct($profileData, $accountManager)
    {
        $this->profileData = $profileData;
        $this->accountManager = $accountManager;
    }

    public function collection()
    {
        $divisiList = collect($this->profileData['divisis'])->pluck('nama')->join(', ');

        return collect([
            ['Nama', $this->profileData['nama']],
            ['NIK', $this->profileData['nik']],
            ['Witel', $this->profileData['witel']['nama']],
            ['Divisi', $divisiList],
            ['Primary Divisi', $this->profileData['selected_divisi_name']],
            ['Multi Divisi', $this->profileData['is_multi_divisi'] ? 'Ya' : 'Tidak'],
        ]);
    }

    public function headings(): array
    {
        return ['Informasi', 'Detail'];
    }

    public function title(): string
    {
        return 'Profil AM';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
            'A:B' => ['alignment' => ['vertical' => Alignment::VERTICAL_TOP]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 50,
        ];
    }
}

/**
 * SHEET 2: Summary Card Group
 */
class SummarySheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $summaryData;
    protected $filters;

    public function __construct($summaryData, $filters)
    {
        $this->summaryData = $summaryData;
        $this->filters = $filters;
    }

    public function collection()
    {
        return collect([
            [
                'Total Revenue',
                $this->summaryData['total_revenue'],
                'Rp ' . number_format($this->summaryData['total_revenue'], 0, ',', '.')
            ],
            [
                'Total Target',
                $this->summaryData['total_target'],
                'Rp ' . number_format($this->summaryData['total_target'], 0, ',', '.')
            ],
            [
                'Achievement Rate',
                $this->summaryData['achievement_rate'],
                $this->summaryData['achievement_rate'] . '%'
            ],
            [
                'Total Customers',
                $this->summaryData['total_customers'],
                $this->summaryData['total_customers'] . ' customers'
            ],
            [
                'Period',
                '',
                $this->summaryData['period_text']
            ],
        ]);
    }

    public function headings(): array
    {
        return ['Metrik', 'Nilai (Numeric)', 'Nilai (Formatted)'];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 30,
        ];
    }
}

/**
 * SHEET 3: Ranking AM
 */
class RankingSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $rankingData;
    protected $accountManager;

    public function __construct($rankingData, $accountManager)
    {
        $this->rankingData = $rankingData;
        $this->accountManager = $accountManager;
    }

    public function collection()
    {
        $rows = collect([
            [
                'Global Ranking',
                $this->rankingData['global']['rank'] ?? 'N/A',
                $this->rankingData['global']['total'] ?? 0,
                $this->rankingData['global']['status'] ?? 'unknown',
                $this->rankingData['global']['change'] ?? 0,
                ($this->rankingData['global']['percentile'] ?? 0) . '%'
            ],
            [
                'Witel Ranking',
                $this->rankingData['witel']['rank'] ?? 'N/A',
                $this->rankingData['witel']['total'] ?? 0,
                $this->rankingData['witel']['status'] ?? 'unknown',
                $this->rankingData['witel']['change'] ?? 0,
                ($this->rankingData['witel']['percentile'] ?? 0) . '%'
            ]
        ]);

        // Add divisi rankings
        foreach ($this->rankingData['divisi'] as $kode => $divisiRank) {
            $rows->push([
                "Divisi Ranking ({$kode})",
                $divisiRank['rank'] ?? 'N/A',
                $divisiRank['total'] ?? 0,
                $divisiRank['status'] ?? 'unknown',
                $divisiRank['change'] ?? 0,
                ($divisiRank['percentile'] ?? 0) . '%'
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Tipe Ranking', 'Peringkat', 'Total AM', 'Status', 'Perubahan', 'Percentile'];
    }

    public function title(): string
    {
        return 'Ranking';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
                'font' => ['color' => ['rgb' => '000000']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 12,
            'C' => 12,
            'D' => 12,
            'E' => 12,
            'F' => 12,
        ];
    }
}

/**
 * SHEET 4: Customer Data - Agregat per CC
 */
class CustomerAgregatCCSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $customerData;

    public function __construct($customerData)
    {
        $this->customerData = $customerData;
    }

    public function collection()
    {
        return collect($this->customerData)->map(function($customer) {
            return [
                $customer['customer_name'] ?? 'N/A',
                $customer['nipnas'] ?? 'N/A',
                $customer['divisi'] ?? 'N/A',
                $customer['segment'] ?? 'N/A',
                $customer['total_revenue'] ?? 0,
                $customer['total_target'] ?? 0,
                $customer['achievement_rate'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'NIPNAS',
            'Divisi',
            'Segment',
            'Total Revenue',
            'Total Target',
            'Achievement Rate (%)'
        ];
    }

    public function title(): string
    {
        return 'Data CC - Agregat per CC';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B9BD5']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 15,
            'C' => 20,
            'D' => 30,
            'E' => 18,
            'F' => 18,
            'G' => 18,
        ];
    }
}

/**
 * SHEET 5: Customer Data - Agregat per Bulan
 */
class CustomerAgregatBulanSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $monthlyData;

    public function __construct($monthlyData)
    {
        $this->monthlyData = $monthlyData;
    }

    public function collection()
    {
        return collect($this->monthlyData)->map(function($month) {
            return [
                $month['bulan_name'] ?? 'N/A',
                $month['total_revenue'] ?? 0,
                $month['total_target'] ?? 0,
                $month['achievement_rate'] ?? 0,
                $month['customer_count'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Bulan',
            'Total Revenue',
            'Total Target',
            'Achievement Rate (%)',
            'Jumlah Customer'
        ];
    }

    public function title(): string
    {
        return 'Data CC - Agregat per Bulan';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B9BD5']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 18,
            'C' => 18,
            'D' => 18,
            'E' => 18,
        ];
    }
}

/**
 * SHEET 6: Customer Data - Detail (per bulan per CC)
 */
class CustomerDetailSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $detailData;

    public function __construct($detailData)
    {
        $this->detailData = $detailData;
    }

    public function collection()
    {
        return collect($this->detailData)->map(function($item) {
            return [
                $item['customer_name'] ?? 'N/A',
                $item['nipnas'] ?? 'N/A',
                $item['divisi'] ?? 'N/A',
                $item['segment'] ?? 'N/A',
                $item['bulan_name'] ?? 'N/A',
                $item['revenue'] ?? 0,
                $item['target'] ?? 0,
                $item['achievement_rate'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'NIPNAS',
            'Divisi',
            'Segment',
            'Bulan',
            'Revenue',
            'Target',
            'Achievement Rate (%)'
        ];
    }

    public function title(): string
    {
        return 'Data CC - Detail';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B9BD5']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 15,
            'C' => 20,
            'D' => 30,
            'E' => 12,
            'F' => 18,
            'G' => 18,
            'H' => 18,
        ];
    }
}

/**
 * SHEET 7: Performance Analysis Summary
 */
class PerformanceAnalysisSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $performanceData;

    public function __construct($performanceData)
    {
        $this->performanceData = $performanceData;
    }

    public function collection()
    {
        return collect([
            [
                'Total Revenue All Time',
                $this->performanceData['total_revenue_all_time'] ?? 0,
                'Rp ' . number_format($this->performanceData['total_revenue_all_time'] ?? 0, 0, ',', '.')
            ],
            [
                'Total Target All Time',
                $this->performanceData['total_target_all_time'] ?? 0,
                'Rp ' . number_format($this->performanceData['total_target_all_time'] ?? 0, 0, ',', '.')
            ],
            [
                'Highest Achievement',
                $this->performanceData['highest_achievement']['value'] ?? 0,
                ($this->performanceData['highest_achievement']['value'] ?? 0) . '% (' . ($this->performanceData['highest_achievement']['bulan'] ?? 'N/A') . ')'
            ],
            [
                'Highest Revenue',
                $this->performanceData['highest_revenue']['value'] ?? 0,
                'Rp ' . number_format($this->performanceData['highest_revenue']['value'] ?? 0, 0, ',', '.') . ' (' . ($this->performanceData['highest_revenue']['bulan'] ?? 'N/A') . ')'
            ],
            [
                'Average Achievement',
                $this->performanceData['average_achievement'] ?? 0,
                ($this->performanceData['average_achievement'] ?? 0) . '%'
            ],
            [
                'Trend',
                $this->performanceData['trend'] ?? 'unknown',
                $this->performanceData['trend_description'] ?? 'N/A'
            ],
            [
                'Trend Percentage',
                $this->performanceData['trend_percentage'] ?? 0,
                ($this->performanceData['trend_percentage'] ?? 0) . '%'
            ],
        ]);
    }

    public function headings(): array
    {
        return ['Metrik', 'Nilai (Numeric)', 'Nilai (Formatted)'];
    }

    public function title(): string
    {
        return 'Analisis Performa';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ED7D31']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 50,
        ];
    }
}

/**
 * SHEET 8: Monthly Chart Data
 */
class MonthlyChartDataSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $chartData;

    public function __construct($chartData)
    {
        $this->chartData = $chartData;
    }

    public function collection()
    {
        $rows = collect();

        $labels = $this->chartData['labels'] ?? [];
        $realRevenue = $this->chartData['datasets']['real_revenue'] ?? [];
        $targetRevenue = $this->chartData['datasets']['target_revenue'] ?? [];
        $achievementRate = $this->chartData['datasets']['achievement_rate'] ?? [];

        foreach ($labels as $index => $label) {
            $rows->push([
                $label,
                $realRevenue[$index] ?? 0,
                $targetRevenue[$index] ?? 0,
                $achievementRate[$index] ?? 0,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Bulan',
            'Real Revenue',
            'Target Revenue',
            'Achievement Rate (%)'
        ];
    }

    public function title(): string
    {
        return 'Chart Data - Bulanan';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A5A5A5']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 20,
            'C' => 20,
            'D' => 20,
        ];
    }
}