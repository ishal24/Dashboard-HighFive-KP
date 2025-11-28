<?php
/**
 * TEST SCRIPT: Manual AM Revenue Recalculation
 *
 * Purpose: Test if recalculateAMRevenuesForCC() works correctly
 * Usage: Create a route in web.php and access via browser
 */

namespace App\Http\Controllers\Test;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestRecalculateAM
{
    public function test()
    {
        echo "<h1>Test AM Revenue Recalculation</h1>";

        // Get CC Revenue for KEPOLISIAN DAERAH JATENG
        $cc = DB::table('corporate_customers')
            ->where('nama', 'KEPOLISIAN DAERAH JATENG')
            ->first();

        if (!$cc) {
            echo "<p style='color:red'>CC Not Found!</p>";
            return;
        }

        echo "<h2>CC Found</h2>";
        echo "<pre>";
        print_r($cc);
        echo "</pre>";

        // Get CC Revenue for Oct 2025
        $ccRevenue = DB::table('cc_revenues')
            ->where('corporate_customer_id', $cc->id)
            ->where('divisi_id', 1)
            ->where('tahun', 2025)
            ->where('bulan', 10)
            ->first();

        if (!$ccRevenue) {
            echo "<p style='color:red'>CC Revenue Not Found!</p>";
            return;
        }

        echo "<h2>CC Revenue Found</h2>";
        echo "<pre>";
        print_r($ccRevenue);
        echo "</pre>";

        // Get AM Revenues
        $amRevenues = DB::table('am_revenues')
            ->where('corporate_customer_id', $cc->id)
            ->where('divisi_id', 1)
            ->where('tahun', 2025)
            ->where('bulan', 10)
            ->get();

        echo "<h2>AM Revenues Found: " . $amRevenues->count() . "</h2>";
        echo "<pre>";
        print_r($amRevenues->toArray());
        echo "</pre>";

        if ($amRevenues->isEmpty()) {
            echo "<p style='color:red'>No AM Revenues to update!</p>";
            return;
        }

        // MANUAL RECALCULATION
        echo "<h2>Starting Manual Recalculation...</h2>";

        DB::beginTransaction();

        try {
            foreach ($amRevenues as $amRevenue) {
                echo "<h3>Processing AM ID: {$amRevenue->id}</h3>";

                // Normalize proporsi
                $proporsi = $amRevenue->proporsi;
                if ($proporsi > 1) {
                    $proporsi = $proporsi / 100;
                }

                echo "<p>Original Proporsi: {$amRevenue->proporsi}</p>";
                echo "<p>Normalized Proporsi: {$proporsi}</p>";

                // Calculate new values
                $newTargetRevenue = $ccRevenue->target_revenue * $proporsi;
                $newRealRevenue = $ccRevenue->real_revenue * $proporsi;
                $achievementRate = $newTargetRevenue > 0 ? ($newRealRevenue / $newTargetRevenue) * 100 : 0;

                echo "<p>OLD Target: {$amRevenue->target_revenue}</p>";
                echo "<p>NEW Target: {$newTargetRevenue}</p>";
                echo "<p>OLD Real: {$amRevenue->real_revenue}</p>";
                echo "<p>NEW Real: {$newRealRevenue}</p>";
                echo "<p>Achievement: {$achievementRate}%</p>";

                // Update
                $updated = DB::table('am_revenues')
                    ->where('id', $amRevenue->id)
                    ->update([
                        'target_revenue' => $newTargetRevenue,
                        'real_revenue' => $newRealRevenue,
                        'achievement_rate' => round($achievementRate, 2),
                        'updated_at' => now()
                    ]);

                echo "<p style='color:green'>Updated: " . ($updated ? 'YES' : 'NO') . "</p>";
                echo "<hr>";
            }

            DB::commit();
            echo "<h2 style='color:green'>SUCCESS! All AM Revenues Updated</h2>";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "<h2 style='color:red'>ERROR: " . $e->getMessage() . "</h2>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }

        // Show final results
        $amRevenuesAfter = DB::table('am_revenues')
            ->where('corporate_customer_id', $cc->id)
            ->where('divisi_id', 1)
            ->where('tahun', 2025)
            ->where('bulan', 10)
            ->get();

        echo "<h2>AM Revenues After Update</h2>";
        echo "<pre>";
        print_r($amRevenuesAfter->toArray());
        echo "</pre>";
    }
}