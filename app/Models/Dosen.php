<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dosen extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'dosen';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_dosen',
        'nip',
        'email',
        'status',
        'jenis_dosen',
        
    ];

    /**
     * Menonaktifkan timestamps bawaan Laravel (created_at, updated_at)
     * jika Anda ingin Supabase yang mengaturnya.
     * Jika Anda ingin Laravel mengelolanya, biarkan true.
     * Supabase sudah membuat kolom created_at, jadi kita set ke false.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Mendefinisikan relasi ke model JadwalKuliah.
     */
    public function jadwalKuliahs()
    {
        return $this->hasMany(JadwalKuliah::class, 'dosen_id');
    }
}
