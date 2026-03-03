<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah';

    protected $fillable = [
        'nama_matkul',
        'kode_mk',
        'sks_teori',
        'sks_praktek',
        'semester',
        'dosen_id',
        'status',
    ];

    public $timestamps = false;

    protected $appends = ['sks'];

    public function getSksAttribute()
    {
        return $this->sks_teori + $this->sks_praktek;
    }

    /**
     * Mendefinisikan relasi ke model Dosen.
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    /**
     * Mendefinisikan relasi ke model JadwalKuliah.
     */
    public function jadwalKuliahs()
    {
        return $this->hasMany(JadwalKuliah::class, 'mata_kuliah_id');
    }
}
