<?php

namespace Database\Seeders;

use App\Models\Ruangan;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ruangans = [
            [
                'nama_ruangan' => 'Aula',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_ruangan' => 'Ruang 101',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_ruangan' => 'Lab SKK',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_ruangan' => 'Lab RSI',
                'status' => 'Active',
                'created_at' => now(),
            ],
        ];

        foreach ($ruangans as $ruangan) {
            Ruangan::create($ruangan);
        }
    }
}
