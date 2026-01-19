<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal_kuliah', function (Blueprint $table) {
            $table->id();

            // Relasi ke History
            $table->foreignId('riwayat_penjadwalan_id')->constrained('riwayat_penjadwalan')->onDelete('cascade');

            // Relasi ke Master Data
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah');
            $table->foreignId('dosen_id')->constrained('dosen');
            $table->foreignId('ruangan_id')->constrained('ruangan');
            $table->foreignId('hari_id')->constrained('hari');
            $table->foreignId('jam_id')->constrained('jam');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_kuliah');
    }
};
