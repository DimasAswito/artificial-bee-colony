<?php

namespace App\Jobs;

use App\Models\JadwalKuliah;
use App\Models\RiwayatPenjadwalan;
use App\Services\ABCAlgorithm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateJadwalJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Batas waktu eksekusi job (dalam detik).
     * Tidak ada batasan dari HTTP/Nginx, sehingga bisa sangat besar.
     */
    public int $timeout = 3600; // 1 jam

    /**
     * Jumlah percobaan ulang jika job gagal.
     */
    public int $tries = 1;

    public function __construct(
        protected int    $riwayatId,
        protected int    $population,
        protected int    $maxCycles,
        protected string $semester,
        protected float  $durasi4Sks,
    ) {}

    /**
     * Jalankan algoritma ABC di background.
     * Status riwayat diperbarui: Pending → Processing → Final (atau Failed).
     */
    public function handle(): void
    {
        $history = RiwayatPenjadwalan::find($this->riwayatId);

        if (!$history) {
            Log::warning("GenerateJadwalJob: Riwayat #{$this->riwayatId} tidak ditemukan.");
            return;
        }

        try {
            // Tandai sebagai sedang diproses
            $history->update(['status' => 'Processing']);

            // Jalankan algoritma ABC (berjalan di CLI worker, tidak ada timeout HTTP)
            $algorithm = new ABCAlgorithm(
                $this->population,
                $this->maxCycles,
                $this->semester,
                $this->durasi4Sks
            );
            $result = $algorithm->run();

            // Simpan setiap baris jadwal ke tabel jadwal_kuliah
            foreach ($result['schedule'] as $item) {
                JadwalKuliah::create([
                    'riwayat_penjadwalan_id' => $history->id,
                    'mata_kuliah_id'         => $item['mata_kuliah_id'],
                    'dosen_id'               => $item['dosen_id'],
                    'ruangan_id'             => $item['ruangan_id'],
                    'hari_id'                => $item['hari_id'],
                    'jam_id'                 => $item['jam_id'],
                    'teknisi_id'             => $item['teknisi_id'] ?? null,
                ]);
            }

            // Update status dan nilai fitness menjadi Final
            $history->update([
                'status'             => 'Final',
                'best_fitness_value' => $result['conflicts'],
            ]);

            Log::info("GenerateJadwalJob: Riwayat #{$this->riwayatId} selesai. Konflik: {$result['conflicts']}");

        } catch (Throwable $e) {
            Log::error("GenerateJadwalJob: Riwayat #{$this->riwayatId} gagal. Error: {$e->getMessage()}");

            // Tandai sebagai gagal agar UI bisa memberikan feedback kepada user
            $history->update(['status' => 'Failed']);
        }
    }

    /**
     * Dipanggil saat job kehabisan tries / timeout.
     */
    public function failed(Throwable $e): void
    {
        Log::error("GenerateJadwalJob: Job failed permanently. Riwayat #{$this->riwayatId}. Error: {$e->getMessage()}");

        $history = RiwayatPenjadwalan::find($this->riwayatId);
        $history?->update(['status' => 'Failed']);
    }
}
