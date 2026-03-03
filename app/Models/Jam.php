<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jam extends Model
{
    use HasFactory;

    protected $table = 'jam';

    protected $fillable = [
        'jam_mulai',
        'jam_selesai',
        'status',
    ];

    public $timestamps = false;

    public function jadwalKuliahs()
    {
        return $this->hasMany(JadwalKuliah::class, 'jam_id');
    }
}
