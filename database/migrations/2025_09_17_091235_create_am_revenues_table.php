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
        Schema::create('am_revenues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_manager_id')->index();
            $table->unsignedBigInteger('corporate_customer_id')->index();

            // Snapshot columns (nullable, fallback to master if NULL)
            $table->unsignedBigInteger('divisi_id')->nullable()->index();
            $table->unsignedBigInteger('witel_id')->nullable()->index();
            $table->unsignedBigInteger('telda_id')->nullable()->index(); // For HOTDA only

            // Revenue calculation
            $table->decimal('proporsi', 5, 2)->default(0.00); // Percentage (0.00-100.00)
            $table->decimal('target_revenue', 20, 2)->default(0.00);
            $table->decimal('real_revenue', 20, 2)->default(0.00);
            $table->decimal('achievement_rate', 5, 2)->nullable(); // Optional: can be calculated on-the-fly

            // Period
            $table->tinyInteger('bulan')->unsigned(); // 1-12
            $table->smallInteger('tahun')->unsigned();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('account_manager_id')->references('id')->on('account_managers')->onDelete('cascade');
            $table->foreign('corporate_customer_id')->references('id')->on('corporate_customers')->onDelete('cascade');
            $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('set null');
            $table->foreign('witel_id')->references('id')->on('witel')->onDelete('set null');
            $table->foreign('telda_id')->references('id')->on('teldas')->onDelete('set null');

            // Unique constraint to prevent duplicate AM-CC per period
            $table->unique(['account_manager_id', 'corporate_customer_id', 'tahun', 'bulan'], 'am_revenue_unique');

            // Performance indexes
            $table->index(['tahun', 'bulan', 'account_manager_id']);
            $table->index(['tahun', 'bulan', 'witel_id']);
            $table->index(['tahun', 'bulan', 'divisi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('am_revenues');
    }
};