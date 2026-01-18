<?php

namespace Database\Seeders;

use App\Models\Dosen;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DosenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dosens = [
            [
                'nama_dosen' => 'Rani Purbaningtyas, S.Kom, M.T',
                'nip' => '198203122005012002',
                'email' => 'rpurbaningtyas@polije.ac.id',
                'status' => 'Active',
            ],
            [
                'nama_dosen' => 'Dhony Manggala Putra, S.E, MM',
                'nip' => '199203072023211018',
                'email' => 'dhony_manggala@polije.ac.id',
                'status' => 'Active',
            ],
            [
                'nama_dosen' => 'Adi Sucipto, S.ST., M.Tr.T.',
                'nip' => '199508242022031015',
                'email' => 'adi_sucipto@polije.ac.id',
                'status' => 'Active',
            ],
            [
                'nama_dosen' => 'Sholihah Ayu Wulandari, S.ST., M.Tr.T.',
                'nip' => '199311242024062003',
                'email' => 'sholihah.ayuwulan@polije.ac.id',
                'status' => 'Active',
            ],
            [
                'nama_dosen' => 'Rifqi Aji Widarso, S.T. M.T.',
                'nip' => '199012072024061001',
                'email' => 'rifqiaji_w@polije.ac.id',
                'status' => 'Active',
            ],
            [
                'nama_dosen' => 'Mochammad Rifki Ulil Albaab, ST., M.Tr.T',
                'nip' => '199404232024061002',
                'email' => 'mochrifki@polije.ac.id',
                'status' => 'Inactive',
            ],
            [
                'nama_dosen' => 'Agung Sutrisno, S.S, M.Hum',
                'status' => 'Active',
            ],
            [
                'nama_dosen' => 'Iin Widayani, M.Pd',
                'status' => 'Active',
            ],
            [
                'nama_dosen' => 'Drs. Asmunir, M.M',
                'status' => 'Inactive',
            ],
        ];

        foreach ($dosens as $dosen) {
            Dosen::create($dosen);
        }
    }
}
