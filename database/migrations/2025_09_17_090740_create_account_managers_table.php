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
        Schema::create('account_managers', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 191)->index();
            $table->string('nik', 50)->unique();
            $table->enum('role', ['AM', 'HOTDA'])->default('AM')->index();
            $table->unsignedBigInteger('witel_id');
            $table->unsignedBigInteger('telda_id')->nullable(); // Only for HOTDA
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('witel_id')->references('id')->on('witel')->onDelete('restrict');
            $table->foreign('telda_id')->references('id')->on('teldas')->onDelete('set null');

            // Indexes for performance
            $table->index(['witel_id', 'role']);
            $table->index('telda_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_managers');
    }
};