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
            $table->uuid('id')->primary();
            $table->string('nama_matkul');
            $table->string('semester');
            $table->integer('sks');
            // $table->foreignUuid('dosen_id')->nullable()->constrained('dosen')
            $table->uuid('dosen_id')->nullable();
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
