<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalKuliah extends Model
{
  use HasFactory;

  protected $table = 'jadwal_kuliah';

  protected $fillable = [
    'riwayat_penjadwalan_id',
    'mata_kuliah_id',
    'dosen_id',
    'ruangan_id',
    'hari_id',
    'jam_id',
    'kelas',
  ];

  public function riwayatPenjadwalan()
  {
    return $this->belongsTo(RiwayatPenjadwalan::class);
  }

  public function mataKuliah()
  {
    return $this->belongsTo(MataKuliah::class);
  }

  public function dosen()
  {
    return $this->belongsTo(Dosen::class);
  }

  public function ruangan()
  {
    return $this->belongsTo(Ruangan::class);
  }

  public function hari()
  {
    return $this->belongsTo(Hari::class);
  }

  public function jam()
  {
    return $this->belongsTo(Jam::class);
  }
}
