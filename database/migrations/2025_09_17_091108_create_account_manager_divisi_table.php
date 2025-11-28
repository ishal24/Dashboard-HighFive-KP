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
        Schema::create('account_manager_divisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_manager_id')->index();
            $table->unsignedBigInteger('divisi_id')->index();
            $table->tinyInteger('is_primary')->default(0)->index(); // 0 or 1
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('account_manager_id')->references('id')->on('account_managers')->onDelete('cascade');
            $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('restrict');

            // Unique constraint to prevent duplicate AM-Divisi relationship
            $table->unique(['account_manager_id', 'divisi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_manager_divisi');
    }
};