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
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->string('lsegment_ho', 150)->index(); // Nama lengkap segment
            $table->string('ssegment_ho', 30)->unique(); // Kode singkatan segment
            $table->unsignedBigInteger('divisi_id')->index();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};