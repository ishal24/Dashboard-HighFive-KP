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
        Schema::create('spreadsheet_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dataset_link_id');
            $table->unsignedBigInteger('divisi_id');
            $table->date('snapshot_date'); // Friday date (e.g., 2024-11-22)
            $table->longText('data_json'); // Parsed spreadsheet data as JSON
            $table->integer('total_rows')->default(0);
            $table->integer('total_ams')->default(0);
            $table->integer('total_customers')->default(0);
            $table->integer('total_products')->default(0);
            $table->timestamp('fetched_at');
            $table->string('fetch_status')->default('success'); // 'success' or 'failed'
            $table->text('fetch_error')->nullable(); // Error message if failed
            $table->timestamps();

            // Foreign keys
            $table->foreign('dataset_link_id')->references('id')->on('dataset_links')->onDelete('cascade');
            $table->foreign('divisi_id')->references('id')->on('divisi')->onDelete('cascade');

            // Unique constraint: Only 1 snapshot per divisi per date
            $table->unique(['divisi_id', 'snapshot_date']);

            // Indexes for fast querying
            $table->index('snapshot_date');
            $table->index(['divisi_id', 'snapshot_date']);
            $table->index('fetch_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spreadsheet_snapshots');
    }
};