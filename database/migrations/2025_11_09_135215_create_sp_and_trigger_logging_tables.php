<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================================
        // Table: sp_logs (Stored Procedure Execution Logs)
        // ============================================================
        if (!Schema::hasTable('sp_logs')) {
            Schema::create('sp_logs', function (Blueprint $table) {
                $table->id();
                $table->string('procedure_name', 100)->default('sp_recalculate_am_revenues');
                $table->unsignedBigInteger('cc_id')->nullable()->index();
                $table->unsignedBigInteger('divisi_id')->nullable();
                $table->unsignedTinyInteger('bulan')->nullable();
                $table->unsignedSmallInteger('tahun')->nullable();
                $table->integer('updated_count')->default(0);
                $table->decimal('execution_time_ms', 10, 2)->nullable();
                $table->text('message')->nullable();
                $table->timestamp('created_at')->useCurrent();

                // Indexes for faster queries
                $table->index(['tahun', 'bulan'], 'idx_sp_logs_period');
                $table->index('created_at', 'idx_sp_logs_created');
            });
        }

        // ============================================================
        // Table: trigger_logs (Trigger Execution Logs)
        // ============================================================
        if (!Schema::hasTable('trigger_logs')) {
            Schema::create('trigger_logs', function (Blueprint $table) {
                $table->id();
                $table->string('table_name', 100)->index();
                $table->enum('action', ['INSERT', 'UPDATE', 'DELETE']);
                $table->unsignedBigInteger('record_id')->nullable()->index();
                $table->decimal('old_target_revenue', 25, 2)->nullable();
                $table->decimal('old_real_revenue', 25, 2)->nullable();
                $table->decimal('new_target_revenue', 25, 2)->nullable();
                $table->decimal('new_real_revenue', 25, 2)->nullable();
                $table->integer('am_updated_count')->default(0);
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();

                // Index for faster queries
                $table->index('created_at', 'idx_trigger_logs_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trigger_logs');
        Schema::dropIfExists('sp_logs');
    }
};