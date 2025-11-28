<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class RLEGSChart
{
    public function buildLineChart(): LarapexChart
    {
        return (new LarapexChart)
            ->setTitle('Revenue Witel')
            ->setType('line') 
            ->setXAxis(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Juni', 'Juli', 'Ags', 'Sept', 'Okt', 'Nov', 'Des'])
            ->setHeight(280)
            ->setDataset([
                [
                    'name' => 'Real Revenue',
                    'data' => [
                        200000000, 400000000, 600000000, 800000000, 1000000000,
                        1100000000, 1150000000, 1200000000, 1250000000, 1300000000, 1350000000, 1400000000
                    ]
                ],
                [
                    'name' => 'Target Revenue',
                    'data' => [
                        300000000, 200000000, 500000000, 900000000, 800000000,
                        950000000, 1000000000, 1050000000, 1100000000, 1150000000, 1200000000, 1250000000
                    ]
                ],
            ]);
    }

    public function buildComparisonBarChart(): LarapexChart
    {
        return (new LarapexChart)
            ->setTitle('Trend revenue')
            ->setSubtitle('During season 2024-2025')
            ->setType('bar')
            ->setHeight(280)
            ->setDataset([
                [
                    'name' => '2024',
                    'data' => [6, 9, 3, 4, 10, 8, 6, 9, 3, 4, 10, 8]
                ],
                [
                    'name' => '2025',
                    'data' => [7, 3, 8, 2, 6, 4, 7, 3, 8, 2, 6, 4]
                ]
            ])
            ->setXAxis(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'Sept', 'Oct', 'Nov', 'Des'])
            ->setGrid(false);
    }

    public function buildDonutChart(): LarapexChart
    {
        return (new LarapexChart)
            ->setTitle('Top 3 Scorers of the Team')
            ->setSubtitle('Season 2025')
            ->setType('donut') 
            ->setDataset([20, 24, 30]) 
            ->setLabels(['DPS', 'DGS', 'DSS']);
    }
    
}