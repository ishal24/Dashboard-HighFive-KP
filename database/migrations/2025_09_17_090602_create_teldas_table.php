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
        Schema::create('teldas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255)->index();
            $table->unsignedBigInteger('witel_id')->index();
            $table->unsignedBigInteger('divisi_id')->index();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('witel_id')->references('id')->on('witel')->onDelete('restrict');
            $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('restrict');

            // Unique constraint to prevent duplicate TELDA in same Witel & Divisi
            $table->unique(['nama', 'witel_id', 'divisi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teldas');
    }
};
