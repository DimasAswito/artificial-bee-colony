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
            ],
            [
                'nama_hari' => 'Selasa',
                'status' => 'Active',
            ],
            [
                'nama_hari' => 'Rabu',
                'status' => 'Active',
            ],
            [
                'nama_hari' => 'Kamis',
                'status' => 'Active',
            ],
            [
                'nama_hari' => 'Jumat',
                'status' => 'Active',
            ],
            [
                'nama_hari' => 'Sabtu',
                'status' => 'Inactive',
            ],
            [
                'nama_hari' => 'Minggu',
                'status' => 'Inactive',
            ],
        ];

        foreach ($haris as $hari) {
            Hari::create($hari);
        }
    }
}
