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
        Schema::create('corporate_customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255)->index();
            $table->string('nipnas', 30)->unique()->index(); // Changed to string as per doc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_customers');
    }
};