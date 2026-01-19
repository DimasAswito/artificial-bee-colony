<?php

namespace Database\Seeders;

use App\Models\Hari;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HariSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $haris = [
            [
                'nama_hari' => 'Senin',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_hari' => 'Selasa',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_hari' => 'Rabu',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_hari' => 'Kamis',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_hari' => 'Jumat',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_hari' => 'Sabtu',
                'status' => 'Inactive',
                'created_at' => now(),
            ],
            [
                'nama_hari' => 'Minggu',
                'status' => 'Inactive',
                'created_at' => now(),
            ],
        ];

        foreach ($haris as $hari) {
            Hari::create($hari);
        }
    }
}
