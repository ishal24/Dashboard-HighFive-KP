<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Divisi;

class DivisiSeeder extends Seeder
{
    public function run()
    {
        // Daftar divisi berdasarkan spesifikasi dokumen
        $divisiList = [
            [
                'nama' => 'Government Service',
                'kode' => 'DGS'
            ],
            [
                'nama' => 'SOE/State Service',
                'kode' => 'DSS'
            ],
            [
                'nama' => 'Private Service',
                'kode' => 'DPS'
            ]
        ];

        foreach ($divisiList as $divisiData) {
            Divisi::updateOrCreate(
                ['kode' => $divisiData['kode']], // Find by kode
                [
                    'nama' => $divisiData['nama'],
                    'kode' => $divisiData['kode']
                ]
            );
        }

        $this->command->info('✅ Divisi seeded: DGS, DSS, DPS');
    }
}