<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Nonaktifkan foreign key checks untuk menghindari masalah constraint
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Jalankan seeder master data berdasarkan urutan dependency
            $this->call([
                // 1. Master tables tanpa dependency
                WitelSeeder::class,
                DivisiSeeder::class,

                // 2. Tables yang depend pada Divisi
                SegmentSeeder::class,

                // 3. Tables yang depend pada Witel & Divisi
                TeldaSeeder::class,

                // 4. User data (terakhir karena bisa depend pada data lain)
                AdminUserSeeder::class,
            ]);

            $this->command->info('✅ All seeders completed successfully!');
        } catch (\Exception $e) {
            // Log error
            \Illuminate\Support\Facades\Log::error("Error saat menjalankan seeder: " . $e->getMessage());

            // Tampilkan error
            $this->command->error("❌ Seeder Error: " . $e->getMessage());

            throw $e; // Re-throw to stop execution
        } finally {
            // Pastikan foreign key checks selalu diaktifkan kembali, bahkan jika terjadi error
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}