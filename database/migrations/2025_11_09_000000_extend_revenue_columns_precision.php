<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Extend Revenue Columns Precision
 *
 * PURPOSE: Support unlimited digits for revenue values
 * - FROM: DECIMAL(15,2) = max 999 billion
 * - TO: DECIMAL(20,2) = max 999 trillion
 *
 * AFFECTED TABLES:
 * - cc_revenues: target_revenue, real_revenue
 * - am_revenues: target_revenue, real_revenue
 *
 * DATE: 2025-11-09
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Log::info('🚀 Starting migration: Extend revenue columns precision');

            // Check current column types
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
                AND COLUMN_NAME IN ('target_revenue', 'real_revenue')
            ");

            Log::info('📋 Current column types', [
                'cc_revenues' => $ccRevenueColumns,
                'am_revenues' => $amRevenueColumns
            ]);

            // Modify CC Revenues table
            Schema::table('cc_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 20, 2)->default(0)->change();
                $table->decimal('real_revenue', 20, 2)->default(0)->change();
            });

            Log::info('✅ Modified cc_revenues columns to DECIMAL(20,2)');

            // Modify AM Revenues table
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 20, 2)->default(0)->change();
                $table->decimal('real_revenue', 20, 2)->default(0)->change();
                $table->decimal('achievement_rate', 8, 2)->default(0)->change();
            });

            Log::info('✅ Modified am_revenues columns to DECIMAL(20,2)');

            // Verify changes
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

            Log::info('📋 Column types after migration', [
                'cc_revenues' => $ccRevenueColumnsAfter,
                'am_revenues' => $amRevenueColumnsAfter
            ]);

            Log::info('✅ Migration completed successfully: Revenue columns extended to DECIMAL(20,2)');

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
            Log::info('🔄 Reverting migration: Restore revenue columns to DECIMAL(15,2)');

            // Revert CC Revenues table
            Schema::table('cc_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 15, 2)->default(0)->change();
                $table->decimal('real_revenue', 15, 2)->default(0)->change();
            });

            Log::info('✅ Reverted cc_revenues columns to DECIMAL(15,2)');

            // Revert AM Revenues table
            Schema::table('am_revenues', function (Blueprint $table) {
                $table->decimal('target_revenue', 15, 2)->default(0)->change();
                $table->decimal('real_revenue', 15, 2)->default(0)->change();
                $table->decimal('achievement_rate', 5, 2)->default(0)->change();
            });

            Log::info('✅ Reverted am_revenues columns to DECIMAL(15,2)');
            Log::info('✅ Migration rollback completed');

        } catch (\Exception $e) {
            Log::error('❌ Migration rollback failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
};