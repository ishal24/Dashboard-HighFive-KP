<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Telda;
use App\Models\Witel;
use App\Models\Divisi;

class TeldaSeeder extends Seeder
{
    public function run()
    {
        // Get reference data
        $witelMapping = Witel::pluck('id', 'nama')->toArray();
        $divisiMapping = Divisi::pluck('id', 'kode')->toArray();

        if (empty($witelMapping) || empty($divisiMapping)) {
            $this->command->error('Witel or Divisi data not found. Please run WitelSeeder and DivisiSeeder first.');
            return;
        }

        // Parse TELDA data dari input Anda dan hapus duplikasi
        $teldaRawData = [
            // BALI
            ['BALI', 'DGS', 'TELKOM DAERAH GIANYAR'],
            ['BALI', 'DGS', 'TELKOM DAERAH UBUNG'],
            ['BALI', 'DGS', 'TELKOM DAERAH SINGARAJA'],
            ['BALI', 'DGS', 'TELKOM DAERAH TABANAN'],
            ['BALI', 'DGS', 'TELKOM DAERAH SANUR'],
            ['BALI', 'DGS', 'TELKOM DAERAH JEMBRANA'],
            ['BALI', 'DGS', 'TELKOM DAERAH KLUNGKUNG'],

            // JATIM BARAT
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH BOJONEGORO'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH MADIUN'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH NGAWI'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH TUBAN'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH TRENGGALEK'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH PONOROGO'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH BLITAR'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH BATU'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH KEPANJEN'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH KEDIRI'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH NGANJUK'],
            ['JATIM BARAT', 'DGS', 'TELKOM DAERAH TULUNGAGUNG'],

            // JATIM TIMUR
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH LUMAJANG'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH BANYUWANGI'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH SITUBONDO'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH BONDOWOSO'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH JEMBER'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH JOMBANG'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH PROBOLINGGO'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH MOJOKERTO'],
            ['JATIM TIMUR', 'DGS', 'TELKOM DAERAH PASURUAN'],

            // NUSA TENGGARA
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH BIMA'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH SUMBAWA'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH ENDE'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH MAUMERE'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH LABUAN BAJO'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH WAINGAPU'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH KUPANG'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH LOMBOK BARU TENGAH'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH LOMBOK TIMUR UTARA'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH ATAMBUA'],
            ['NUSA TENGGARA', 'DGS', 'TELKOM DAERAH WAIKABUBAK'],

            // SEMARANG JATENG UTARA
            ['SEMARANG JATENG UTARA', 'DGS', 'MEA SEMARANG'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH KENDAL'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH UNGARAN'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH SALATIGA'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH BATANG'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH PEKALONGAN'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH PEMALANG'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH BREBES'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH SLAWI'],
            ['SEMARANG JATENG UTARA', 'DGS', 'TELKOM DAERAH TEGAL'],

            // SOLO JATENG TIMUR
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH BLORA'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH KUDUS'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH PURWODADI'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH JEPARA'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH PATI'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH REMBANG'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH WONOGIRI'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH BOYOLALI'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH SRAGEN'],
            ['SOLO JATENG TIMUR', 'DGS', 'TELKOM DAERAH KLATEN'],

            // SURAMADU
            ['SURAMADU', 'DGS', 'TELKOM DAERAH BANGKALAN'],
            ['SURAMADU', 'DGS', 'TELKOM DAERAH GRESIK'],
            ['SURAMADU', 'DGS', 'TELKOM DAERAH LAMONGAN'],
            ['SURAMADU', 'DGS', 'TELKOM DAERAH PAMEKASAN'],
            ['SURAMADU', 'DGS', 'TELKOM DAERAH MANYAR'],
            ['SURAMADU', 'DGS', 'TELKOM DAERAH KETINTANG'],

            // YOGYA JATENG SELATAN
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH CILACAP'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH BANJARNEGARA'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH PURBALINGGA'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH SLEMAN'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH PURWOKERTO'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH GUNUNG KIDUL'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH BANTUL'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH MUNTILAN'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH TEMANGGUNG'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH MAGELANG'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH KEBUMEN'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH PURWOREJO'],
            ['YOGYA JATENG SELATAN', 'DGS', 'TELKOM DAERAH WONOSOBO'],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($teldaRawData as $teldaData) {
            [$witelNama, $divisiKode, $teldaNama] = $teldaData;

            // Check if witel exists
            if (!isset($witelMapping[$witelNama])) {
                $this->command->warn("Witel '{$witelNama}' not found, skipping TELDA '{$teldaNama}'");
                $skippedCount++;
                continue;
            }

            // Check if divisi exists
            if (!isset($divisiMapping[$divisiKode])) {
                $this->command->warn("Divisi '{$divisiKode}' not found, skipping TELDA '{$teldaNama}'");
                $skippedCount++;
                continue;
            }

            // Create TELDA
            $telda = Telda::updateOrCreate(
                [
                    'nama' => $teldaNama,
                    'witel_id' => $witelMapping[$witelNama],
                    'divisi_id' => $divisiMapping[$divisiKode]
                ],
                [
                    'nama' => $teldaNama,
                    'witel_id' => $witelMapping[$witelNama],
                    'divisi_id' => $divisiMapping[$divisiKode]
                ]
            );

            if ($telda->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        $this->command->info("TELDA seeding completed:");
        $this->command->info("- Created: {$createdCount} new TELDAs");
        $this->command->info("- Skipped: {$skippedCount} TELDAs");

        // Show summary by Witel
        foreach ($witelMapping as $witelNama => $witelId) {
            $count = Telda::where('witel_id', $witelId)->count();
            if ($count > 0) {
                $this->command->info("  {$witelNama}: {$count} TELDAs");
            }
        }
    }
}