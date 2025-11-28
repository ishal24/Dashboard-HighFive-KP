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
        Schema::create('cc_revenues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('corporate_customer_id')->index();
            $table->unsignedBigInteger('divisi_id')->index();
            $table->unsignedBigInteger('segment_id')->index();
            $table->unsignedBigInteger('witel_ho_id')->nullable()->index();
            $table->unsignedBigInteger('witel_bill_id')->nullable()->index();

            // Snapshot data for audit/export
            $table->string('nama_cc', 255)->index();
            $table->string('nipnas', 50)->index();

            // Revenue data
            $table->decimal('target_revenue', 20, 2)->default(0.00);
            $table->decimal('real_revenue', 20, 2)->default(0.00);
            $table->enum('revenue_source', ['HO', 'BILL'])->index();
            $table->enum('tipe_revenue', ['REGULER', 'NGTMA'])->default('REGULER')->index();

            // Period
            $table->tinyInteger('bulan')->unsigned(); // 1-12
            $table->smallInteger('tahun')->unsigned();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('corporate_customer_id')->references('id')->on('corporate_customers')->onDelete('cascade');
            $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('restrict');
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('restrict');
            $table->foreign('witel_ho_id')->references('id')->on('witel')->onDelete('set null');
            $table->foreign('witel_bill_id')->references('id')->on('witel')->onDelete('set null');

            // Unique constraint to prevent duplicate records
            $table->unique(['corporate_customer_id', 'tahun', 'bulan', 'tipe_revenue'], 'cc_revenue_unique');

            // Performance indexes
            $table->index(['tahun', 'bulan', 'divisi_id']);
            $table->index(['tahun', 'bulan', 'witel_ho_id']);
            $table->index(['tahun', 'bulan', 'witel_bill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cc_revenues');
    }
};