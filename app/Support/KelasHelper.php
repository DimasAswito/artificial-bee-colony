<?php

namespace App\Support;

/**
 * Helper untuk kolom mata_kuliah.kelas, yang mendukung kombinasi lebih dari
 * satu kelas dalam satu baris (mis. "A,B,C") lewat dropdown di form master
 * data. Dipakai bersama oleh ABCAlgorithm (proses generate) dan ABCController
 * (tampilan detail riwayat) agar definisi "dua MK melibatkan kelas yang sama"
 * selalu konsisten di kedua tempat.
 */
class KelasHelper
{
    /**
     * Memecah nilai kolom kelas (mis. "A,B,C") menjadi array huruf kelas.
     */
    public static function set(?string $kelas): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $kelas))));
    }

    /**
     * Dua MK dianggap melibatkan mahasiswa yang sama jika kelasnya beririsan,
     * bukan harus identik — MK kelas "A" dan MK kelas "A,B" sama-sama
     * melibatkan mahasiswa kelas A sehingga harus dianggap bentrok.
     */
    public static function intersects(?string $a, ?string $b): bool
    {
        return count(array_intersect(self::set($a), self::set($b))) > 0;
    }
}
