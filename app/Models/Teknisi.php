<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teknisi extends Model
{
    protected $table = 'teknisi';
    protected $fillable = [
        'nama',
        'status',
    ];

    public function jadwalKuliahs()
    {
        return $this->hasMany(JadwalKuliah::class);
    }

    public function riwayatPenjadwalans()
    {
        return $this->hasMany(RiwayatPenjadwalan::class);
    }
}
