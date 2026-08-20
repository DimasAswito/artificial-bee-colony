<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'ruangan';

    protected $fillable = [
        'nama_ruangan',
        'status',
    ];

    public $timestamps = false;

    public function jadwalKuliahs()
    {
        return $this->hasMany(JadwalKuliah::class, 'ruangan_id');
    }

    /**
     * Menyaring ruangan yang boleh dipakai untuk mata kuliah Teori/Campuran.
     * Dipakai bersama oleh ABCAlgorithm dan ABCController::checkCapacity()
     * agar keduanya selalu memakai definisi pool Teori yang sama persis.
     *
     * Fallback: jika tidak ada ruangan bernama "Ruang 101"/"Aula", semua
     * ruangan aktif boleh dipakai untuk Teori.
     */
    public static function filterTeoriPool(\Illuminate\Support\Collection $activeRuangans): \Illuminate\Support\Collection
    {
        $teori = $activeRuangans
            ->filter(fn($r) => in_array(strtolower($r->nama_ruangan), ['ruang 101', 'aula']))
            ->values();

        return $teori->isEmpty() ? $activeRuangans : $teori;
    }
}
