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
            [
                'jam_mulai' => '08:00',
                'jam_selesai' => '10:00',
                'status' => 'Active',
            ],
            [
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
                'status' => 'Active',
            ],
            [
                'jam_mulai' => '13:00',
                'jam_selesai' => '15:00',
                'status' => 'Active',
            ],
            [
                'jam_mulai' => '15:00',
                'jam_selesai' => '17:00',
                'status' => 'Active',
            ],
        ];

        foreach ($jams as $jam) {
            Jam::create($jam);
        }
    }
}
