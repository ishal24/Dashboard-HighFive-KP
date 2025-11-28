<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Segment;
use App\Models\Divisi;

class SegmentSeeder extends Seeder
{
    public function run()
    {
        // Get divisi IDs
        $divisiMapping = Divisi::pluck('id', 'kode')->toArray();

        if (empty($divisiMapping)) {
            $this->command->error('Divisi data not found. Please run DivisiSeeder first.');
            return;
        }

        // Segment data berdasarkan yang Anda berikan
        $segments = [
            // DGS Segments (Government)
            "GPS" => ["GOVERNMENT PUBLIC SERVICE", "DGS"],
            "GDS" => ["GOVERNMENT DEFENSE SERVICE", "DGS"],
            "GIS" => ["GOVERNMENT INFRASTRUCTURE SERVICE", "DGS"],
            "GRS" => ["GOVERNMENT REGIONAL SERVICE", "DGS"],
            "LGS" => ["GOVERNMENT REGIONAL SERVICE", "DGS"], // Duplicate in your data

            // DSS Segments (SOE/State) - Assuming these go to DSS
            "LMS" => ["LOGISTIC & MANUFACTURING SERVICE", "DSS"],
            "MIS" => ["MANUFACTURING & INFRASTRUCTURE SERVICE", "DSS"],
            "ERS" => ["ENERGY & RESOURCES SERVICE", "DSS"],

            // DPS Segments (Private)
            "FWS" => ["FINANCIAL & WELFARE SERVICE", "DPS"],
            "PBS" => ["PRIVATE BANKING SERVICE", "DPS"],
            "RMS" => ["RETAIL & MEDIA SERVICE", "DPS"],
            "PCS" => ["PRIVATE CONGLOMERATION SERVICE", "DPS"],
            "PRS" => ["PROPERTY & RESOURCES SERVICE", "DPS"],
            "FRBS" => ["FINANCIAL & REGIONAL BANKING SERVICE", "DPS"],
            "TWS" => ["TOURISM & WELFARE SERVICE", "DPS"],
        ];

        foreach ($segments as $kode => $data) {
            $namaLengkap = $data[0];
            $divisiKode = $data[1];

            if (!isset($divisiMapping[$divisiKode])) {
                $this->command->warn("⚠️  Divisi {$divisiKode} tidak ditemukan untuk segment {$kode}");
                continue;
            }

            Segment::updateOrCreate(
                [
                    'ssegment_ho' => $kode,
                    'divisi_id' => $divisiMapping[$divisiKode]
                ],
                [
                    'lsegment_ho' => $namaLengkap,
                    'ssegment_ho' => $kode,
                    'divisi_id' => $divisiMapping[$divisiKode]
                ]
            );
        }

        $segmentCount = Segment::count();
        $this->command->info("✅ Seeded {$segmentCount} segments");

        // Show summary by divisi
        foreach ($divisiMapping as $kode => $id) {
            $count = Segment::where('divisi_id', $id)->count();
            $this->command->info("   - {$kode}: {$count} segments");
        }
    }
}