<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hari extends Model
{
    use HasFactory;

    protected $table = 'hari';

    protected $fillable = [
        'nama_hari',
        'status',
    ];

    public $timestamps = false;
}
