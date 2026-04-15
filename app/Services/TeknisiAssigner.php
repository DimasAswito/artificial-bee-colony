<?php

namespace App\Services;

use App\Models\Teknisi;

/**
 * TeknisiAssigner — Post-processing setelah algoritma ABC selesai.
 *
 * Cara kerja:
 * 1. Algorima ABC berjalan seperti biasa (tanpa beban tracking teknisi).
 * 2. Setelah jadwal final terbentuk, kelas yang MURNI WORKSHOP dipilah.
 * 3. Setiap sesi workshop di-assign teknisi secara greedy:
 *    - Teknisi dipilih acak dari daftar yang belum dipakai di slot hari+jam yang sama.
 *    - Jika semua teknisi bentrok di slot itu, tetap assign salah satu (soft fallback)
 *      agar tidak ada kelas tanpa teknisi.
 * 4. Kelas teori dan kelas campuran (teori+praktek) TIDAK perlu teknisi → teknisi_id = null.
 */
class TeknisiAssigner
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    private $teknisis;

    public function __construct()
    {
        $this->teknisis = Teknisi::where('status', 'Active')->get();
    }

    /**
     * Assign teknisi ke setiap sesi workshop dalam jadwal.
     *
     * @param  array $schedule  Array hasil ABCAlgorithm::run()['schedule']
     * @return array            Jadwal yang sama dengan teknisi_id sudah terisi
     */
    public function assign(array $schedule): array
    {
        if ($this->teknisis->isEmpty()) {
            // Tidak ada teknisi aktif — kembalikan jadwal apa adanya
            return $schedule;
        }

        // Tracker: [teknisi_id][hari_id] => [[start, end], ...]
        // Digunakan agar satu teknisi tidak menangani dua workshop di waktu yang sama.
        $teknisiSlots = [];

        foreach ($schedule as &$item) {
            // Tetapkan teknisi_id = null sebagai default
            $item['teknisi_id'] = null;

            // Hanya workshop MURNI (sks_teori == 0, sks_praktek > 0) yang butuh teknisi.
            // Flag ini sudah ada di output ABC: is_teori = false berarti workshop.
            if ($item['is_teori']) {
                continue; // Kelas teori atau campuran — skip
            }

            $hariId    = $item['hari_id'];
            $startIdx  = $item['jam_index'];
            $endIdx    = $startIdx + $item['duration_slots'];

            // Cari teknisi yang belum dipakai di slot ini
            $freeTeknisis = [];
            foreach ($this->teknisis as $t) {
                $conflict = false;
                foreach ($teknisiSlots[$t->id][$hariId] ?? [] as [$s, $e]) {
                    if ($startIdx < $e && $endIdx > $s) {
                        $conflict = true;
                        break;
                    }
                }
                if (!$conflict) {
                    $freeTeknisis[] = $t->id;
                }
            }

            if (!empty($freeTeknisis)) {
                // Pilih teknisi bebas secara acak
                $chosenId = $freeTeknisis[array_rand($freeTeknisis)];
            } else {
                // Soft fallback: semua teknisi sudah dipakai di slot ini.
                // Tetap assign agar tidak ada kelas workshop tanpa teknisi.
                $chosenId = $this->teknisis->random()->id;
            }

            $item['teknisi_id'] = $chosenId;

            // Catat slot yang sudah dipakai teknisi terpilih
            $teknisiSlots[$chosenId][$hariId][] = [$startIdx, $endIdx];
        }
        unset($item); // Hapus referensi setelah foreach by-reference

        return $schedule;
    }

    /**
     * Kembalikan jumlah teknisi aktif.
     * Dipakai oleh ABCController::checkCapacity() jika diperlukan.
     */
    public function countActive(): int
    {
        return $this->teknisis->count();
    }
}
