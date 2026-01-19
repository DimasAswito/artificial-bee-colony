<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPenjadwalan extends Model
{
  use HasFactory;

  protected $table = 'riwayat_penjadwalan';

  protected $fillable = [
    'user_id',
    'judul',
    'semester',
    'tahun_ajaran',
    'best_fitness_value',
    'jumlah_iterasi',
    'status',
  ];

  public function jadwalKuliahs()
  {
    return $this->hasMany(JadwalKuliah::class);
  }
}
