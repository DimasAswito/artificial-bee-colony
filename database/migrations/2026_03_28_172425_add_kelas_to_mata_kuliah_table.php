<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mata_kuliah', function (Blueprint $table) {
            // Kelas mahasiswa: 'A', 'B', 'C' untuk kelas Workshop.
            // Kosong ('') untuk mata kuliah Teori (seluruh angkatan).
            // Constraint: Dua Workshop dengan semester dan kelas yang sama tidak boleh bersamaan.
            $table->string('kelas', 5)->nullable()->default('')->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('mata_kuliah', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });
    }
};
