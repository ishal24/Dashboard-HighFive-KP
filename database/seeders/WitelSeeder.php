<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Witel;

class WitelSeeder extends Seeder
{
    public function run()
    {
        $witelList = [
            'BALI',
            'JATIM BARAT',
            'JATIM TIMUR',
            'NUSA TENGGARA',
            'SEMARANG JATENG UTARA',
            'SOLO JATENG TIMUR',
            'SURAMADU',
            'YOGYA JATENG SELATAN'
        ];

        foreach ($witelList as $witel) {
            Witel::create(['nama' => $witel]);
        }
    }
}


