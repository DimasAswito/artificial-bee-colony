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
            ],
            [
                'nama_ruangan' => 'Ruang 101',
                'status' => 'Active',
            ],
            [
                'nama_ruangan' => 'Lab SKK',
                'status' => 'Active',
            ],
            [
                'nama_ruangan' => 'Lab RSI',
                'status' => 'Active',
            ],
        ];

        foreach ($ruangans as $ruangan) {
            Ruangan::create($ruangan);
        }
    }
}
