<?php

namespace Database\Seeders;

use App\Models\Jam;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jams = [
            // Sesi Pagi (08:00 - 12:00)
            ['jam_mulai' => '08:00', 'jam_selesai' => '09:00', 'status' => 'Active', 'created_at' => now()],
            ['jam_mulai' => '09:00', 'jam_selesai' => '10:00', 'status' => 'Active', 'created_at' => now()],
            ['jam_mulai' => '10:00', 'jam_selesai' => '11:00', 'status' => 'Active', 'created_at' => now()],
            ['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'Active', 'created_at' => now()],

            // coba tanpa istirahat
            ['jam_mulai' => '12:00', 'jam_selesai' => '13:00', 'status' => 'Active', 'created_at' => now()],

            // Istirahat 12:00 - 13:00 (Tidak ada slot)

            // Sesi Siang (13:00 - 17:00)
            ['jam_mulai' => '13:00', 'jam_selesai' => '14:00', 'status' => 'Active', 'created_at' => now()],
            ['jam_mulai' => '14:00', 'jam_selesai' => '15:00', 'status' => 'Active', 'created_at' => now()],
            ['jam_mulai' => '15:00', 'jam_selesai' => '16:00', 'status' => 'Active', 'created_at' => now()],
            ['jam_mulai' => '16:00', 'jam_selesai' => '17:00', 'status' => 'Active', 'created_at' => now()],
        ];

        foreach ($jams as $jam) {
            Jam::create($jam);
        }
    }
}
