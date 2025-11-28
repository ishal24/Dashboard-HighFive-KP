<?php

// NOTE: Status Script: Unknown if Used or Not (VERDICT: NOT USED)

namespace App\Support;

use Illuminate\Support\Facades\DB;

class CCWRevenueAggregator
{
    /**
     * Return standardized series arrays for a given year and source.
     * [
     *   'months' => [1..12],
     *   'dgs' => [12], 'dps' => [12], 'dss' => [12]
     * ]
     */
    public static function yearlySeries(int $year, string $source): array
    {
        $months = range(1, 12);

        // Base query: group by (division, month)
        $q = DB::table('cc_revenues')
            ->select([
                'divisi_id',
                'bulan',
                DB::raw('SUM(real_revenue) as total'),
            ])
            ->where('tahun', $year)
            ->where('tipe_revenue', $source);

        // NOTE: DGS: 1, DSS: 2, DPS: 3

        // For ngtma, impose metric rule;
        // for non-ngtma we don’t filter metric (or you can if your schema needs it)
        if ($source === 'NGTMA') {
            $q->where(function ($qq) {
                $qq->where(function ($q1) {
                    $q1->where('divisi_id', 3)->where('revenue_source', 'BILL');
                })->orWhere(function ($q2) {
                    $q2->whereIn('divisi_id', [1, 2])->where('revenue_source', 'SOLD');
                });
            });
        }

        $rows = $q->groupBy('divisi_id', 'bulan')->get();

        // Initialize zero-filled
        $series = [
            'months' => $months,
            'dgs' => array_fill(0, 12, 0),
            'dps' => array_fill(0, 12, 0),
            'dss' => array_fill(0, 12, 0),
        ];

        foreach ($rows as $r) {
            $idx = (int)$r->bulan - 1;
            if ($idx < 0 || $idx > 11) continue;
            $key = strtolower($r->divisi_id);
            if (!isset($series[$key])) continue;
            $series[$key][$idx] = (float)$r->total;
        }

        return $series;
    }
}
