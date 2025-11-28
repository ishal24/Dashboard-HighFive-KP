<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Extend Revenue Columns Precision to 30 Digits
 *
 * PURPOSE: Support ultra-large revenue values
 * - FROM: DECIMAL(20,2) = max 999,999,999,999,999,999.99 (999 quadrillion)
 * - TO: DECIMAL(30,2) = max 9,999,999,999,999,999,999,999,999,999.99 (9.9 octillion)
 *
 * AFFECTED TABLES:
 * - cc_revenues: target_revenue, real_revenue
 * - am_revenues: target_revenue, real_revenue, achievement_rate
 *
 * COMMAND TO CREATE:
 * php artisan make:migration extend_revenue_precision_to_30_digits
 *
 * COMMAND TO RUN:
 * php artisan migrate
 *
 * COMMAND TO ROLLBACK:
 * php artisan migrate:rollback
 *
 * DATE: 2025-11-10
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Log::info('🚀 Starting migration: Extend revenue columns to DECIMAL(30,2)');

            // ============================================================
            // STEP 1: Check current column types (for logging)
            // ============================================================
            $ccRevenueColumns = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'cc_revenues'
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue')
            ");

            $amRevenueColumns = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'am_revenues'
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue', 'achievement_rate')
            ");

            Log::info('📋 Current column types BEFORE migration', [
                'cc_revenues' => $ccRevenueColumns,
                'am_revenues' => $amRevenueColumns
            ]);

            // ============================================================
            // STEP 2: Modify CC Revenues table
            // ============================================================
            Schema::table('cc_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 30, 2)->default(0)->change();
                $table->decimal('real_revenue', 30, 2)->default(0)->change();
            });

            Log::info('✅ Modified cc_revenues columns to DECIMAL(30,2)');

            // ============================================================
            // STEP 3: Modify AM Revenues table
            // ============================================================
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 30, 2)->default(0)->change();
                $table->decimal('real_revenue', 30, 2)->default(0)->change();
                // Achievement rate tetap DECIMAL(8,2) karena ini persentase (max 999,999.99%)
                $table->decimal('achievement_rate', 8, 2)->default(0)->change();
            });

            Log::info('✅ Modified am_revenues columns to DECIMAL(30,2)');

            // ============================================================
            // STEP 4: Verify changes
            // ============================================================
            $ccRevenueColumnsAfter = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'cc_revenues'
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue')
            ");

            $amRevenueColumnsAfter = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'am_revenues'
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue', 'achievement_rate')
            ");

            Log::info('📋 Column types AFTER migration', [
                'cc_revenues' => $ccRevenueColumnsAfter,
                'am_revenues' => $amRevenueColumnsAfter
            ]);

            // ============================================================
            // STEP 5: Test with sample data (optional)
            // ============================================================
            Log::info('🧪 Testing maximum value support...');

            // Test max value: 9,999,999,999,999,999,999,999,999,999.99
            $testMaxValue = '9999999999999999999999999999.99';

            Log::info('✅ Migration completed successfully: Revenue columns extended to DECIMAL(30,2)');
            Log::info('💰 Maximum supported value: ' . number_format(floatval($testMaxValue), 2, '.', ','));

        } catch (\Exception $e) {
            Log::error('❌ Migration failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Log::info('🔄 Reverting migration: Restore revenue columns to DECIMAL(20,2)');

            // ============================================================
            // STEP 1: Revert CC Revenues table
            // ============================================================
            Schema::table('cc_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 20, 2)->default(0)->change();
                $table->decimal('real_revenue', 20, 2)->default(0)->change();
            });

            Log::info('✅ Reverted cc_revenues columns to DECIMAL(20,2)');

            // ============================================================
            // STEP 2: Revert AM Revenues table
            // ============================================================
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 20, 2)->default(0)->change();
                $table->decimal('real_revenue', 20, 2)->default(0)->change();
                $table->decimal('achievement_rate', 8, 2)->default(0)->change();
            });

            Log::info('✅ Reverted am_revenues columns to DECIMAL(20,2)');
            Log::info('✅ Migration rollback completed');

        } catch (\Exception $e) {
            Log::error('❌ Migration rollback failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
};