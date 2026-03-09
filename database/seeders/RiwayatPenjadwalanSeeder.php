<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RiwayatPenjadwalan;
use App\Models\JadwalKuliah;
use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Hari;
use App\Models\Jam;
use App\Models\User;
use Carbon\Carbon;

class RiwayatPenjadwalanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $matkuls = MataKuliah::all();
        $dosens = Dosen::all();
        $ruangans = Ruangan::all();
        $haris = Hari::all();
        $jams = Jam::all();

        if (!$user || $matkuls->isEmpty() || $dosens->isEmpty() || $ruangans->isEmpty() || $haris->isEmpty() || $jams->isEmpty()) {
            $this->command->warn('Data master belum lengkap. Harap jalankan seeder data master terlebih dahulu.');
            return;
        }

        $semesters = ['Ganjil', 'Genap'];
        $tahunAjaran = ['2024/2025', '2025/2026'];

        $titles = [
            'Jadwal Kuliah Semester Ganjil Simulasi A',
            'Jadwal Perkuliahan Reguler Batch 1',
            'Skenario Uji Coba Penjadwalan Cepat'
        ];

        for ($i = 0; $i < 3; $i++) {
            // Buat Riwayat
            $riwayat = RiwayatPenjadwalan::create([
                'user_id' => $user->id,
                'judul' => $titles[$i] ?? "Jadwal Dummy $i",
                'semester' => $semesters[array_rand($semesters)],
                'tahun_ajaran' => $tahunAjaran[array_rand($tahunAjaran)],
                'best_fitness_value' => rand(0, 5), // Random konflik 0 - 5
                'jumlah_iterasi' => rand(500, 1000),
                'durasi_praktek' => [0.5, 1, 1.5, 2, 4][array_rand([0.5, 1, 1.5, 2, 4])],
                'status' => 'Final',
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);

            // Buat Detail Jadwalacak sekitar 15-20 data per riwayat
            $jumlahJadwal = rand(15, 20);

            for ($j = 0; $j < $jumlahJadwal; $j++) {
                JadwalKuliah::create([
                    'riwayat_penjadwalan_id' => $riwayat->id,
                    'mata_kuliah_id' => $matkuls->random()->id,
                    'dosen_id' => $dosens->random()->id,
                    'ruangan_id' => $ruangans->random()->id,
                    'hari_id' => $haris->random()->id,
                    'jam_id' => $jams->random()->id,
                ]);
            }
        }

        $this->command->info('3 Data Riwayat Penjadwalan beserta detailnya berhasil dibuat.');
    }
}
