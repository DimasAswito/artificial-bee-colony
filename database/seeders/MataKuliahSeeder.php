<?php

namespace Database\Seeders;

use App\Models\MataKuliah;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MataKuliahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matkul = [
            [
                'nama_matkul' => 'Agama',
                'sks' => '2',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Pancasila',
                'sks' => '2',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Basic English',
                'sks' => '2',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Logika dan Algoritma',
                'sks' => '2',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Konsep Basis Data',
                'sks' => '2',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],            [
                'nama_matkul' => 'Pemrograman Dasar',
                'sks' => '2',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Pengembangan Perangkat Lunak',
                'sks' => '4',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Basis Data',
                'sks' => '4',
                'semester' => '1',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Bahasa Indonesia',
                'sks' => '2',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Kewarganegaraan',
                'sks' => '2',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Intermediate English',
                'sks' => '2',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Interaksi Manusia dan Komputer',
                'sks' => '2',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Sistem Aplikasi Berbasis Obyek',
                'sks' => '2',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Perencanaan Proyek Perangkat lunak',
                'sks' => '2',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Sistem Informasi Berbasis Desktop',
                'sks' => '4',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Manajemen Proyek',
                'sks' => '4',
                'semester' => '2',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Interpersonal Skill',
                'sks' => '2',
                'semester' => '3',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Matematika Diskrit',
                'sks' => '2',
                'semester' => '3',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Konsep Jaringan Komputer',
                'sks' => '2',
                'semester' => '3',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Struktur Data',
                'sks' => '2',
                'semester' => '3',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Kualitas Perangkat Lunak',
                'sks' => '4',
                'semester' => '3',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Sistem Informasi Berbasis Web',
                'sks' => '4',
                'semester' => '3',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Mobile Applications',
                'sks' => '4',
                'semester' => '3',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Literasi Digital',
                'sks' => '2',
                'semester' => '4',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Kewirausahaan',
                'sks' => '2',
                'semester' => '4',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Manajemen Kualitas Perangkat Lunak',
                'sks' => '2',
                'semester' => '4',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Perawatan Perangkat Lunak',
                'sks' => '2',
                'semester' => '4',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Pengujian Perangkat Lunak',
                'sks' => '2',
                'semester' => '4',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Sistem Informasi Web Framework',
                'sks' => '4',
                'semester' => '4',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Sistem Mobile Applications Framework',
                'sks' => '4',
                'semester' => '4',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Aplikasi Sistem Tertanam',
                'sks' => '2',
                'semester' => '5',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Sistem Cerdas',
                'sks' => '2',
                'semester' => '5',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Agroinformatika',
                'sks' => '2',
                'semester' => '5',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Multimedia Permainan',
                'sks' => '2',
                'semester' => '5',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Pengolahan Citra dan Vision',
                'sks' => '4',
                'semester' => '5',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Sistem Tertanam',
                'sks' => '4',
                'semester' => '5',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Sistem Cerdas',
                'sks' => '4',
                'semester' => '5',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Teknik Penulisan Ilmiah',
                'sks' => '2',
                'semester' => '6',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Statistika',
                'sks' => '2',   
                'semester' => '6',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Tren Teknologi',
                'sks' => '2',   
                'semester' => '6',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Data Warehouse',
                'sks' => '2',   
                'semester' => '6',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Developer Operational',
                'sks' => '4',   
                'semester' => '6',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Tata Kelola Teknologi Informasi',
                'sks' => '4',   
                'semester' => '6',
                'status' => 'Active',
                'created_at' => now(),
            ],
            [
                'nama_matkul' => 'Workshop Proyek Sistem Informasi',
                'sks' => '4',   
                'semester' => '6',
                'status' => 'Active',
                'created_at' => now(),
            ],
        ];

        foreach ($matkul as $matkul) {
            MataKuliah::create($matkul);
        }
    }
}
