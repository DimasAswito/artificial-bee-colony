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
        Schema::table('riwayat_penjadwalan', function (Blueprint $table) {
            $table->float('durasi_praktek')->default(0)->after('tahun_ajaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_penjadwalan', function (Blueprint $table) {
            $table->dropColumn('durasi_praktek');
        });
    }
};
