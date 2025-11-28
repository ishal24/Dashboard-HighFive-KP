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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 191);
            $table->enum('role', ['admin', 'witel', 'account_manager'])->index();
            $table->unsignedBigInteger('witel_id')->nullable()->index();
            $table->unsignedBigInteger('account_manager_id')->nullable()->index();
            $table->string('profile_image', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();

            // Foreign keys will be added in separate migration after all tables are created
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};