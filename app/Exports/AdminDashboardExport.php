<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminDashboardExport implements WithMultipleSheets
{
    protected $exportData;
    protected $dateRange;
    protected $filters;

    public function __construct($exportData, $dateRange, $filters)
    {
        $this->exportData = $exportData;
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Summary/Overview
        $sheets[] = new SummarySheet($this->exportData['summary'], $this->dateRange, $this->filters);

        // Sheet 2: Account Managers Performance
        if (isset($this->exportData['performance']['account_managers']) &&
            $this->exportData['performance']['account_managers']->isNotEmpty()) {
            $sheets[] = new AccountManagerSheet($this->exportData['performance']['account_managers']);
        }

        // Sheet 3: Witels Performance
        if (isset($this->exportData['performance']['witels']) &&
            $this->exportData['performance']['witels']->isNotEmpty()) {
            $sheets[] = new WitelSheet($this->exportData['performance']['witels']);
        }

        // Sheet 4: Segments Performance
        if (isset($this->exportData['performance']['segments']) &&
            $this->exportData['performance']['segments']->isNotEmpty()) {
            $sheets[] = new SegmentSheet($this->exportData['performance']['segments']);
        }

        // Sheet 5: Corporate Customers Performance
        if (isset($this->exportData['performance']['corporate_customers']) &&
            $this->exportData['performance']['corporate_customers']->isNotEmpty()) {
            $sheets[] = new CorporateCustomerSheet($this->exportData['performance']['corporate_customers']);
        }

        // Sheet 6: Revenue Table (Detail)
        if (isset($this->exportData['revenue_table']) &&
            $this->exportData['revenue_table']->isNotEmpty()) {
            $sheets[] = new RevenueTableSheet($this->exportData['revenue_table']);
        }

        return $sheets;
    }
}

class SummarySheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $summaryData;
    protected $dateRange;
    protected $filters;

    public function __construct($summaryData, $dateRange, $filters)
    {
        $this->summaryData = $summaryData;
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function collection()
    {
        $data = [];

        // Export Info
        $data[] = ['RLEGS Dashboard Export Summary', '', '', ''];
        $data[] = ['Generated:', now()->format('d/m/Y H:i:s'), '', ''];
        $data[] = ['Period:', $this->formatPeriod(), '', ''];
        $data[] = ['Filter Divisi:', $this->getFilterDivisi(), '', ''];
        $data[] = ['Filter Revenue Source:', $this->filters['revenue_source'] ?? 'All', '', ''];
        $data[] = ['Filter Tipe Revenue:', $this->filters['tipe_revenue'] ?? 'All', '', ''];
        $data[] = ['', '', '', ''];

        // Summary Statistics
        $data[] = ['RINGKASAN PERFORMA', '', '', ''];
        $data[] = ['Metric', 'Value', 'Target', 'Achievement (%)'];

        $totalRevenue = $this->summaryData['total_revenue'] ?? 0;
        $totalTarget = $this->summaryData['total_target'] ?? 0;
        $achievementRate = $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0;

        $data[] = [
            'Total Revenue',
            $this->formatCurrency($totalRevenue),
            $this->formatCurrency($totalTarget),
            round($achievementRate, 2) . '%'
        ];

        $data[] = ['', '', '', ''];
        $data[] = ['CATATAN:', '', '', ''];
        $data[] = ['- Data berdasarkan periode ' . $this->formatPeriod(), '', '', ''];
        $data[] = ['- Achievement rate dihitung dari Real Revenue vs Target Revenue', '', '', ''];
        $data[] = ['- Mata uang dalam Rupiah (Rp)', '', '', ''];

        return collect($data);
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        return []; // Headers are included in the data
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 20,
            'D' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4472C4']],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
            9 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
            ],
            10 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F2F2F2']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge cells for title
                $sheet->mergeCells('A1:D1');

                // Set alignment
                $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B2:B6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Add borders to summary table
                $sheet->getStyle('A9:D11')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
            },
        ];
    }

    private function formatPeriod()
    {
        if (!$this->dateRange || !isset($this->dateRange['start']) || !isset($this->dateRange['end'])) {
            return 'N/A';
        }

        $start = Carbon::parse($this->dateRange['start']);
        $end = Carbon::parse($this->dateRange['end']);

        return $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
    }

    private function getFilterDivisi()
    {
        $divisiId = $this->filters['divisi_id'] ?? null;
        if (!$divisiId || $divisiId === 'all') {
            return 'Semua Divisi';
        }
        return "Divisi ID: {$divisiId}";
    }

    private function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

class AccountManagerSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($am, $index) {
            return [
                'rank' => $index + 1,
                'nama' => $am->nama ?? 'N/A',
                'witel' => $am->witel->nama ?? 'N/A',
                'divisi_list' => $am->divisi_list ?? 'N/A',
                'total_revenue' => $am->total_revenue ?? 0,
                'total_target' => $am->total_target ?? 0,
                'achievement_rate' => round($am->achievement_rate ?? 0, 2),
                'status' => $this->getStatusText($am->achievement_rate ?? 0),
            ];
        });
    }

    public function title(): string
    {
        return 'Account Managers';
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Nama AM',
            'Witel',
            'Divisi',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status Performance'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
            ],
        ];
    }

    private function getStatusText($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'Excellent (≥100%)';
        } elseif ($achievementRate >= 80) {
            return 'Good (80-99%)';
        } else {
            return 'Poor (<80%)';
        }
    }
}

class WitelSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($witel, $index) {
            return [
                'rank' => $index + 1,
                'nama' => $witel->nama ?? 'N/A',
                'total_customers' => $witel->total_customers ?? 0,
                'total_revenue' => $witel->total_revenue ?? 0,
                'total_target' => $witel->total_target ?? 0,
                'achievement_rate' => round($witel->achievement_rate ?? 0, 2),
                'status' => $this->getStatusText($witel->achievement_rate ?? 0),
            ];
        });
    }

    public function title(): string
    {
        return 'Witels Performance';
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Nama Witel',
            'Total Customers',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status Performance'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
            ],
        ];
    }

    private function getStatusText($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'Excellent (≥100%)';
        } elseif ($achievementRate >= 80) {
            return 'Good (80-99%)';
        } else {
            return 'Poor (<80%)';
        }
    }
}

class SegmentSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($segment, $index) {
            return [
                'rank' => $index + 1,
                'segment_name' => $segment->lsegment_ho ?? $segment->nama ?? 'N/A',
                'divisi' => $segment->divisi_nama ?? ($segment->divisi->nama ?? 'N/A'),
                'total_customers' => $segment->total_customers ?? 0,
                'total_revenue' => $segment->total_revenue ?? 0,
                'total_target' => $segment->total_target ?? 0,
                'achievement_rate' => round($segment->achievement_rate ?? 0, 2),
                'status' => $this->getStatusText($segment->achievement_rate ?? 0),
            ];
        });
    }

    public function title(): string
    {
        return 'Segments Performance';
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Segment Name',
            'Divisi',
            'Total Customers',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status Performance'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
            ],
        ];
    }

    private function getStatusText($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'Excellent (≥100%)';
        } elseif ($achievementRate >= 80) {
            return 'Good (80-99%)';
        } else {
            return 'Poor (<80%)';
        }
    }
}

class CorporateCustomerSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($customer, $index) {
            return [
                'rank' => $index + 1,
                'nama' => $customer->nama ?? 'N/A',
                'nipnas' => $customer->nipnas ?? 'N/A',
                'divisi' => $customer->divisi_nama ?? 'N/A',
                'segment' => $customer->segment_nama ?? 'N/A',
                'total_revenue' => $customer->total_revenue ?? 0,
                'total_target' => $customer->total_target ?? 0,
                'achievement_rate' => round($customer->achievement_rate ?? 0, 2),
                'status' => $this->getStatusText($customer->achievement_rate ?? 0),
            ];
        });
    }

    public function title(): string
    {
        return 'Corporate Customers';
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Nama Customer',
            'NIPNAS',
            'Divisi',
            'Segment',
            'Total Revenue (Rp)',
            'Total Target (Rp)',
            'Achievement (%)',
            'Status Performance'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
            ],
        ];
    }

    private function getStatusText($achievementRate)
    {
        if ($achievementRate >= 100) {
            return 'Excellent (≥100%)';
        } elseif ($achievementRate >= 80) {
            return 'Good (80-99%)';
        } else {
            return 'Poor (<80%)';
        }
    }
}

class RevenueTableSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($row) {
            return [
                'bulan_tahun' => $this->formatMonthYear($row['bulan'] ?? null, $row['tahun'] ?? null),
                'divisi' => $row['divisi_nama'] ?? 'N/A',
                'witel_ho' => $row['witel_ho_nama'] ?? 'N/A',
                'witel_bill' => $row['witel_bill_nama'] ?? 'N/A',
                'segment' => $row['segment_nama'] ?? 'N/A',
                'customer' => $row['customer_nama'] ?? 'N/A',
                'nipnas' => $row['nipnas'] ?? 'N/A',
                'revenue_source' => $row['revenue_source'] ?? 'N/A',
                'tipe_revenue' => $row['tipe_revenue'] ?? 'N/A',
                'real_revenue' => $row['real_revenue'] ?? 0,
                'target_revenue' => $row['target_revenue'] ?? 0,
                'achievement' => $this->calculateAchievement($row['real_revenue'] ?? 0, $row['target_revenue'] ?? 0),
            ];
        });
    }

    public function title(): string
    {
        return 'Revenue Detail';
    }

    public function headings(): array
    {
        return [
            'Bulan-Tahun',
            'Divisi',
            'Witel HO',
            'Witel BILL',
            'Segment',
            'Customer',
            'NIPNAS',
            'Revenue Source',
            'Tipe Revenue',
            'Real Revenue (Rp)',
            'Target Revenue (Rp)',
            'Achievement (%)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
            ],
        ];
    }

    /**
     * FIXED: Format month and year properly - handles both integer and string inputs
     */
    private function formatMonthYear($bulan, $tahun)
    {
        // Handle null values
        if (!$bulan || !$tahun) {
            return 'N/A';
        }

        // Convert to integers if they're strings
        $bulanInt = is_numeric($bulan) ? (int)$bulan : null;
        $tahunInt = is_numeric($tahun) ? (int)$tahun : null;

        // Validate month range
        if ($bulanInt < 1 || $bulanInt > 12 || !$tahunInt) {
            return 'N/A';
        }

        try {
            // Use Carbon for safe date formatting
            $date = Carbon::create($tahunInt, $bulanInt, 1);
            return $date->format('F Y'); // e.g., "January 2024"
        } catch (\Exception $e) {
            // Fallback to manual formatting if Carbon fails
            $months = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            return ($months[$bulanInt] ?? 'Unknown') . ' ' . $tahunInt;
        }
    }

    private function calculateAchievement($realRevenue, $targetRevenue)
    {
        if (!$targetRevenue || $targetRevenue == 0) {
            return 0;
        }

        return round(($realRevenue / $targetRevenue) * 100, 2);
    }
}