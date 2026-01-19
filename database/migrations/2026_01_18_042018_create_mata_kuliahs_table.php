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
        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_matkul');
            $table->string('semester');
            $table->integer('sks');
            // $table->foreignUuid('dosen_id')->nullable()->constrained('dosen')
            $table->foreignId('dosen_id')->nullable()->constrained('dosen');
            $table->string('status')->default('Active')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mata_kuliah');
    }
};
