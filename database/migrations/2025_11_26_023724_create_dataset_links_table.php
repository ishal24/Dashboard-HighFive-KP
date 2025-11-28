<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dataset_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('divisi_id');
            $table->text('link_spreadsheet');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->integer('total_snapshots')->default(0);
            $table->text('last_fetch_status')->nullable(); // 'success' or error message
            $table->timestamps();

            // Foreign key
            $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('cascade');

            // Unique constraint: Only 1 link per divisi
            $table->unique('divisi_id');

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dataset_links');
    }
};