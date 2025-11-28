<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user sesuai struktur tabel users yang baru
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@telkom.co.id'], // Find by email
            [
                'name' => 'Admin Telkom',
                'email' => 'admin@telkom.co.id',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'witel_id' => null, // Admin tidak terikat ke witel tertentu
                'account_manager_id' => null, // Admin bukan account manager
                'profile_image' => null,
                'email_verified_at' => now(), // Set as verified
            ]
        );

        if ($adminUser->wasRecentlyCreated) {
            $this->command->info('Admin user created successfully!');
        } else {
            $this->command->info('Admin user already exists, updated data.');
        }

        $this->command->info('Admin credentials:');
        $this->command->info('Email: admin@telkom.co.id');
        $this->command->info('Password: password');
        $this->command->warn('Please change the default password after first login!');
    }
}