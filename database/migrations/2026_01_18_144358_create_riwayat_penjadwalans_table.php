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
        Schema::create('riwayat_penjadwalan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('judul')->nullable();
            $table->string('semester')->nullable();
            $table->string('tahun_ajaran')->nullable();
            $table->double('best_fitness_value')->nullable(); // To store the best fitness (e.g. 0.0)
            $table->integer('jumlah_iterasi')->nullable(); // How many cycles used
            $table->string('status')->default('Draft'); // Draft (editable) -> Final (locked)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_penjadwalan');
    }
};
