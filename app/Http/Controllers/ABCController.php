<?php

namespace App\Http\Controllers;

use App\Models\Jam;
use App\Models\Hari;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\MataKuliah;
use App\Models\JadwalKuliah;
use Illuminate\Http\Request;
use App\Services\ABCAlgorithm;
use App\Models\RiwayatPenjadwalan;
use Illuminate\Support\Facades\Auth;
use App\Exports\JadwalExport;
use Maatwebsite\Excel\Facades\Excel;

class ABCController extends Controller
{
    // =========================================================================
    // HALAMAN UTAMA
    // =========================================================================

    /**
     * Menampilkan halaman Generate Jadwal.
     * Memuat semua data master aktif untuk keperluan verifikasi di UI
     * dan menampilkan 5 riwayat generate terakhir.
     */
    public function index()
    {
        $dosen      = Dosen::where('status', 'Active')->get();
        $mataKuliah = MataKuliah::where('status', 'Active')->with('dosen')->get();
        $ruangan    = Ruangan::where('status', 'Active')->get();
        $hari       = Hari::where('status', 'Active')->get();
        $jam        = Jam::where('status', 'Active')->get();
        $history    = RiwayatPenjadwalan::latest()->take(5)->get();

        return view('pages.artificial_bee_colony.generate', compact(
            'dosen',
            'mataKuliah',
            'ruangan',
            'hari',
            'jam',
            'history'
        ));
    }

    // =========================================================================
    // GENERATE JADWAL
    // =========================================================================

    /**
     * Memproses request generate jadwal dari form.
     *
     * Alur:
     * 1. Validasi input form.
     * 2. Verifikasi kapasitas ruangan (Capacity Check) — tolak jika tidak cukup.
     * 3. Jalankan algoritma ABC untuk menghasilkan jadwal terbaik.
     * 4. Simpan riwayat dan detail jadwal ke database.
     * 5. Kembalikan respons JSON ke UI.
     */
    public function generate(Request $request)
    {
        set_time_limit(300); // Maksimum 5 menit eksekusi

        $request->validate([
            'judul'        => 'required|string|max:255',
            'population'   => 'required|integer|min:10|max:700',
            'max_cycles'   => 'required|integer|min:100|max:10000',
            'semester'     => 'required|string',
            'tahun_ajaran' => 'required|string',
            'durasi_4_sks' => 'required|numeric|min:0.5',
        ]);

        // Langkah 1: Verifikasi kapasitas sebelum menjalankan algoritma
        $capacityCheck = $this->checkCapacity($request->semester, $request->durasi_4_sks);
        if (!$capacityCheck['success']) {
            return response()->json([
                'success' => false,
                'message' => $capacityCheck['message'],
                'details' => $capacityCheck['details'],
            ], 422);
        }

        // Langkah 2: Jalankan algoritma ABC
        $algorithm = new ABCAlgorithm(
            $request->population,
            $request->max_cycles,
            $request->semester,
            $request->durasi_4_sks
        );
        $result = $algorithm->run();

        // Langkah 3: Simpan riwayat ke tabel riwayat_penjadwalan
        $history = RiwayatPenjadwalan::create([
            'user_id'           => Auth::id(),
            'judul'             => $request->judul,
            'semester'          => $request->semester,
            'tahun_ajaran'      => $request->tahun_ajaran,
            'durasi_praktek'    => $request->durasi_4_sks,
            'best_fitness_value' => $result['conflicts'],
            'jumlah_iterasi'    => $request->max_cycles,
            'status'            => 'Final',
        ]);

        // Langkah 4: Simpan setiap baris jadwal ke tabel jadwal_kuliah
        foreach ($result['schedule'] as $item) {
            JadwalKuliah::create([
                'riwayat_penjadwalan_id' => $history->id,
                'mata_kuliah_id'         => $item['mata_kuliah_id'],
                'dosen_id'               => $item['dosen_id'],
                'ruangan_id'             => $item['ruangan_id'],
                'hari_id'                => $item['hari_id'],
                'jam_id'                 => $item['jam_id'],
            ]);
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Jadwal berhasil digenerate!',
            'fitness'    => $result['conflicts'],
            'history_id' => $history->id,
        ]);
    }

    // =========================================================================
    // HALAMAN RIWAYAT
    // =========================================================================

    /**
     * Menampilkan daftar semua riwayat generate jadwal dengan paginasi 10 per halaman.
     */
    public function riwayat()
    {
        $history = RiwayatPenjadwalan::latest()->paginate(10);
        return view('pages.artificial_bee_colony.riwayat', compact('history'));
    }

    /**
     * Menampilkan detail jadwal dari satu riwayat tertentu.
     * Menghitung konflik yang ada pada jadwal tersebut untuk ditampilkan dengan highlight merah.
     */
    public function detail($id)
    {
        $history = RiwayatPenjadwalan::with([
            'jadwalKuliahs' => fn($q) => $q->orderBy('hari_id')->orderBy('jam_id'),
            'jadwalKuliahs.mataKuliah',
            'jadwalKuliahs.dosen',
            'jadwalKuliahs.ruangan',
            'jadwalKuliahs.hari',
            'jadwalKuliahs.jam',
        ])->findOrFail($id);

        // Hitung durasi riil setiap mata kuliah berdasarkan konfigurasi SKS & history
        $durations = $this->calculateDurations($history);

        // Siapkan data pendukung untuk pengecekan konflik
        $items  = $history->jadwalKuliahs;
        $jams   = Jam::orderBy('jam_mulai')->where('status', 'Active')->get();
        $allJams = $jams->pluck('id')->values()->toArray();
        $conflictingIds = [];

        // Deteksi konflik: bandingkan semua pasangan jadwal pada hari yang sama
        foreach ($items as $a) {
            foreach ($items as $b) {
                if ($a->id == $b->id) continue;
                if ($a->hari_id != $b->hari_id) continue;

                $aIndex = array_search($a->jam_id, $allJams);
                $bIndex = array_search($b->jam_id, $allJams);
                if ($aIndex === false || $bIndex === false) continue;

                $aEnd = $aIndex + ($durations[$a->id] ?? 1);
                $bEnd = $bIndex + ($durations[$b->id] ?? 1);

                // Cek ada irisan waktu
                if (!($aIndex < $bEnd && $bIndex < $aEnd)) continue;

                $aSemester = $a->mataKuliah->semester ?? null;
                $bSemester = $b->mataKuliah->semester ?? null;
                $aIsTeori  = ($a->mataKuliah->sks_teori ?? 0) > 0;
                $bIsTeori  = ($b->mataKuliah->sks_teori ?? 0) > 0;
                $aKelas    = $a->mataKuliah->kelas ?? '';
                $bKelas    = $b->mataKuliah->kelas ?? '';

                $isConflict = false;
                if ($a->ruangan_id == $b->ruangan_id) $isConflict = true;
                if ($a->dosen_id && $a->dosen_id == $b->dosen_id) $isConflict = true;
                if ($aSemester == $bSemester && ($aIsTeori || $bIsTeori)) $isConflict = true;
                if ($aSemester == $bSemester && !$aIsTeori && !$bIsTeori && !empty($aKelas) && $aKelas == $bKelas) $isConflict = true;

                if ($isConflict) {
                    $conflictingIds[] = $a->id;
                }
            }
        }
        $conflictingIds = array_unique($conflictingIds);

        // Hitung jam selesai setiap kelas untuk ditampilkan di view
        $jamList  = $jams->values();
        $endTimes = [];
        foreach ($history->jadwalKuliahs as $jadwal) {
            $durationSlots = $durations[$jadwal->id] ?? 1;
            $startIdx = $jamList->search(fn($j) => $j->id == $jadwal->jam_id);

            if ($startIdx !== false && isset($jamList[$startIdx + $durationSlots - 1])) {
                $endTimes[$jadwal->id] = \Carbon\Carbon::parse($jamList[$startIdx + $durationSlots - 1]->jam_selesai)->format('H:i');
            } else {
                $endTimes[$jadwal->id] = \Carbon\Carbon::parse($jadwal->jam->jam_mulai)->addMinutes($durationSlots * 30)->format('H:i');
            }
        }

        return view('pages.artificial_bee_colony.detail_riwayat', compact(
            'history',
            'conflictingIds',
            'durations',
            'endTimes'
        ));
    }

    // =========================================================================
    // EXPORT EXCEL
    // =========================================================================

    /**
     * Mengekspor jadwal terpilih ke file Excel (.xlsx).
     * Membangun grid hari × jam × ruangan dan mengisinya dengan data jadwal.
     */
    public function export($id)
    {
        $history = RiwayatPenjadwalan::with([
            'jadwalKuliahs.mataKuliah',
            'jadwalKuliahs.dosen',
            'jadwalKuliahs.ruangan',
            'jadwalKuliahs.hari',
            'jadwalKuliahs.jam',
        ])->findOrFail($id);

        $haris   = Hari::orderBy('id')->where('status', 'Active')->get();
        $jams    = Jam::orderBy('jam_mulai')->where('status', 'Active')->get();
        $ruangans = Ruangan::orderBy('nama_ruangan')->where('status', 'Active')->get();

        // Inisialisasi grid kosong: grid[hari_id][jam_id][ruangan_id]
        $grid = [];
        foreach ($haris as $h) {
            foreach ($jams as $j) {
                foreach ($ruangans as $r) {
                    $grid[$h->id][$j->id][$r->id] = null;
                }
            }
        }

        // Mapping jam_id ke index urutan (untuk menghitung slot berikutnya)
        $jamIndices  = $jams->pluck('id')->values()->toArray();
        $jamIdToIndex = array_flip($jamIndices);
        $durations   = $this->calculateDurations($history);

        // Isi grid dan tandai slot lanjutan dengan 'SKIP'
        foreach ($history->jadwalKuliahs as $jadwal) {
            $hId = $jadwal->hari_id;
            $jId = $jadwal->jam_id;
            $rId = $jadwal->ruangan_id;

            if (isset($grid[$hId][$jId][$rId]) && $grid[$hId][$jId][$rId] === 'SKIP') {
                continue;
            }

            $grid[$hId][$jId][$rId]   = $jadwal;
            $durationSlots             = $durations[$jadwal->id] ?? 2;

            if ($durationSlots > 1 && isset($jamIdToIndex[$jId])) {
                $currentIndex = $jamIdToIndex[$jId];
                for ($k = 1; $k < $durationSlots; $k++) {
                    if (isset($jamIndices[$currentIndex + $k])) {
                        $nextJamId = $jamIndices[$currentIndex + $k];
                        if (!isset($grid[$hId][$nextJamId][$rId])) {
                            $grid[$hId][$nextJamId][$rId] = 'SKIP';
                        }
                    }
                }
            }
        }

        // Cek apakah ada slot jam istirahat yang aktif (untuk keperluan tampilan Excel)
        $hasLunchSlot = Jam::where('status', 'Active')->get()->contains(function ($jam) {
            $mulai   = \Carbon\Carbon::parse($jam->jam_mulai);
            $selesai = \Carbon\Carbon::parse($jam->jam_selesai);
            return $mulai >= \Carbon\Carbon::parse('12:00') && $selesai <= \Carbon\Carbon::parse('13:00');
        });

        $data = compact('history', 'haris', 'jams', 'ruangans', 'grid', 'durations', 'hasLunchSlot');

        $rawFilename = sprintf('Jadwal %s semester %s %s.xlsx', $history->judul, $history->semester, $history->tahun_ajaran);
        $filename    = str_replace(['/', '\\'], '-', $rawFilename);

        return Excel::download(new JadwalExport($data, $history->judul), $filename);
    }

    // =========================================================================
    // HAPUS RIWAYAT
    // =========================================================================

    /**
     * Menghapus riwayat penjadwalan beserta semua jadwal detailnya.
     * Cascade delete dikonfigurasi di level database (foreign key on delete cascade).
     */
    public function destroy($id)
    {
        try {
            $riwayat = RiwayatPenjadwalan::findOrFail($id);
            $riwayat->delete();
            return response()->json(['message' => 'Riwayat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus riwayat',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // HELPER PRIVATE
    // =========================================================================

    /**
     * Menghitung durasi slot (30 menit = 1 slot) setiap jadwal berdasarkan konfigurasi
     * SKS mata kuliah dan setting durasi praktek yang disimpan di riwayat.
     *
     * Dipanggil oleh detail() dan export() untuk mengetahui seberapa banyak baris
     * grid yang dipakai oleh setiap kelas.
     *
     * @param  RiwayatPenjadwalan $history
     * @return array  [jadwal_id => jumlah_slot]
     */
    private function calculateDurations($history): array
    {
        $durations = [];

        foreach ($history->jadwalKuliahs as $jadwal) {
            $mk               = $jadwal->mataKuliah;
            $durasiTeoriSlots = $mk->sks_teori * 2; // 1 SKS Teori = 2 slot (1 jam)
            $durasiPraktekSlots = 0;

            if ($mk->sks_praktek > 0) {
                $defaultPraktekJam = $mk->sks_praktek * 2;
                $maxJamPraktek     = (float) $history->durasi_praktek;

                if ($mk->sks_teori > 0 && $mk->sks_praktek > 0) {
                    // Mata kuliah Campuran (Teori + Praktek): digabung jadi 1 sesi
                    $totalJamPraktek = $defaultPraktekJam;
                    $occur           = 1;
                } else {
                    // Mata kuliah Murni Praktek: dibagi sesuai setting durasi
                    if ($maxJamPraktek > 0) {
                        $occur           = 2;
                        $totalJamPraktek = $maxJamPraktek * $occur;
                    } else {
                        $totalJamPraktek = $defaultPraktekJam;
                        $occur           = ($totalJamPraktek > 4) ? 2 : 1;
                    }
                }
                $durasiPraktekSlots = ($totalJamPraktek / $occur) * 2; // Jam → Slot
            }

            $durations[$jadwal->id] = max(1, $durasiTeoriSlots + $durasiPraktekSlots);
        }

        return $durations;
    }

    /**
     * Memverifikasi bahwa kapasitas slot waktu yang tersedia cukup untuk menampung
     * semua mata kuliah yang akan dijadwalkan.
     *
     * Jika total slot yang dibutuhkan melebihi total slot yang tersedia
     * (Ruangan × Hari × Jam), generate akan ditolak sebelum algoritma dijalankan.
     *
     * @param  string $semesterType  'Ganjil' atau 'Genap'
     * @param  float  $durasi4Sks   Max jam per pertemuan untuk mata kuliah praktek
     * @return array  ['success' => bool, 'message' => string, 'details' => string]
     */
    private function checkCapacity(string $semesterType, float $durasi4Sks): array
    {
        // Hitung slot yang tersedia: Ruangan × Hari × Jam
        $activeRooms = Ruangan::where('status', 'Active')->count();
        $activeDays  = Hari::where('status', 'Active')->count();
        $activeJams  = Jam::where('status', 'Active')->count();

        if ($activeRooms == 0 || $activeDays == 0 || $activeJams == 0) {
            return [
                'success' => false,
                'message' => 'Data Master Tidak Lengkap!',
                'details' => 'Pastikan ada minimal 1 Ruangan, 1 Hari, dan 1 Jam yang aktif.',
            ];
        }

        $totalCapacitySlots = $activeRooms * $activeDays * $activeJams;

        // Filter mata kuliah sesuai semester yang akan digenerate
        $mataKuliahs = MataKuliah::where('status', 'Active')->get()->filter(function ($mk) use ($semesterType) {
            $sem = (int) $mk->semester;
            return $semesterType === 'Ganjil' ? ($sem % 2 != 0) : ($sem % 2 == 0);
        });

        if ($mataKuliahs->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Data Mata Kuliah Kosong!',
                'details' => 'Tidak ada mata kuliah aktif untuk semester ' . $semesterType . ' ini.',
            ];
        }

        // Hitung total slot yang dibutuhkan oleh semua mata kuliah
        $totalSlotsNeeded = 0;
        foreach ($mataKuliahs as $mk) {
            $durasiTeoriSlots   = $mk->sks_teori * 2;
            $durasiPraktekSlots = 0;
            $occurrencesPraktek = 1;

            if ($mk->sks_praktek > 0) {
                $defaultPraktekJam = $mk->sks_praktek * 2;
                $maxJamPraktek     = (float) $durasi4Sks;

                if ($mk->sks_teori > 0 && $mk->sks_praktek > 0) {
                    $totalJamPraktek    = $defaultPraktekJam;
                    $occurrencesPraktek = 1;
                } else {
                    if ($maxJamPraktek > 0) {
                        $occurrencesPraktek = 2;
                        $totalJamPraktek    = $maxJamPraktek * $occurrencesPraktek;
                    } else {
                        $totalJamPraktek    = $defaultPraktekJam;
                        $occurrencesPraktek = ($totalJamPraktek > 4) ? 2 : 1;
                    }
                }
                $durasiPraktekSlots = ($totalJamPraktek / $occurrencesPraktek) * 2;
            }

            $totalDurationSlots = $durasiTeoriSlots + $durasiPraktekSlots;
            $totalSlotsNeeded  += ($occurrencesPraktek * $totalDurationSlots);
        }

        if ($totalSlotsNeeded > $totalCapacitySlots) {
            return [
                'success' => false,
                'message' => 'Kapasitas ruangan tidak mencukupi!',
                'details' => 'Anda dapat mengurangi waktu workshop tiap pertemuan atau menonaktifkan beberapa mata kuliah.',
            ];
        }

        return ['success' => true];
    }
}
