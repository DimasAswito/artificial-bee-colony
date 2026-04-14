<?php

namespace Database\Seeders;

use App\Models\Teknisi;
use Illuminate\Database\Seeder;

class TeknisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'nama' => 'Teknisi 1',
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Teknisi 2',
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Teknisi 3',
                'status' => 'Non-Active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Teknisi 4',
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Teknisi::insert($data);
    }
}
