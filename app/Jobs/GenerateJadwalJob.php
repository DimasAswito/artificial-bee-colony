<?php

namespace App\Jobs;

use App\Models\JadwalKuliah;
use App\Models\RiwayatPenjadwalan;
use App\Services\ABCAlgorithm;
use App\Services\TeknisiAssigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateJadwalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Waktu maksimal job ini boleh berjalan (detik).
     * Set 10 menit agar iterasi banyak tidak masalah.
     */
    public int $timeout = 600;

    /**
     * Hanya coba 1 kali — jika gagal, tandai sebagai Failed.
     */
    public int $tries = 1;

    public function __construct(
        public readonly int   $riwayatId,
        public readonly int   $population,
        public readonly int   $maxCycles,
        public readonly string $semester,
        public readonly float  $durasi4Sks,
    ) {}

    /**
     * Jalankan algoritma ABC di background.
     */
    public function handle(): void
    {
        // Jalankan algoritma ABC
        $algorithm = new ABCAlgorithm(
            $this->population,
            $this->maxCycles,
            $this->semester,
            $this->durasi4Sks
        );
        $result = $algorithm->run();

        // Assign teknisi (post-processing, tidak menambah beban ABC)
        $assigner = new TeknisiAssigner();
        $result['schedule'] = $assigner->assign($result['schedule']);

        // Simpan detail jadwal ke database
        foreach ($result['schedule'] as $item) {
            JadwalKuliah::create([
                'riwayat_penjadwalan_id' => $this->riwayatId,
                'mata_kuliah_id'         => $item['mata_kuliah_id'],
                'dosen_id'               => $item['dosen_id'],
                'ruangan_id'             => $item['ruangan_id'],
                'hari_id'                => $item['hari_id'],
                'jam_id'                 => $item['jam_id'],
                'teknisi_id'             => $item['teknisi_id'] ?? null,
            ]);
        }

        // Update riwayat: ubah status Pending → Final dengan hasil fitness
        RiwayatPenjadwalan::where('id', $this->riwayatId)->update([
            'best_fitness_value' => $result['conflicts'],
            'status'             => 'Final',
        ]);
    }

    /**
     * Jika job gagal (exception), tandai riwayat sebagai Failed
     * agar frontend bisa memberikan pesan yang jelas.
     */
    public function failed(\Throwable $e): void
    {
        RiwayatPenjadwalan::where('id', $this->riwayatId)->update([
            'status' => 'Failed',
        ]);
    }
}
