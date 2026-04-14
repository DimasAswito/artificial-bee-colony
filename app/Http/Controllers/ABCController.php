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

        $calendarInfo = $this->getAcademicCalendarData();

        return view('pages.artificial_bee_colony.generate', compact(
            'dosen',
            'mataKuliah',
            'ruangan',
            'hari',
            'jam',
            'history',
            'calendarInfo'
        ));
    }

    /**
     * Mengambil default Tahun Ajaran, opsi dropdown, dan Semester berdasarkan tanggal sekarang.
     * Logika:
     * - Genap: Februari s/d Juli
     * - Ganjil: Agustus s/d Januari
     * @return array
     */
    private function getAcademicCalendarData(): array
    {
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        if ($currentMonth >= 8 || $currentMonth == 1) { // Ganjil
            $semester = 'Ganjil';
        } else { // Genap
            $semester = 'Genap';
        }

        // Secara default menggunakan tahun berjalan sebagai tahun awal (misal 2026 jadi 2026/2027)
        $startYear = $currentYear;
        $currentTahunAjar = $startYear . '/' . ($startYear + 1);

        $tahunAjarOptions = [
            ($startYear - 1) . '/' . $startYear,       // 1 Tahun Kebelakang
            $currentTahunAjar,                         // Tahun Sekarang (Default)
            ($startYear + 1) . '/' . ($startYear + 2), // 1 Tahun Kedepan
        ];

        return [
            'default_semester' => $semester,
            'default_tahun_ajaran' => $currentTahunAjar,
            'tahun_ajaran_options' => $tahunAjarOptions,
        ];
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
                'teknisi_id'             => $item['teknisi_id'] ?? null,
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
            'jadwalKuliahs.teknisi',
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
                if (!empty($a->teknisi_id) && $a->teknisi_id == $b->teknisi_id) $isConflict = true;
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
            'jadwalKuliahs.teknisi',
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
     * Memverifikasi bahwa kapasitas jadwal yang tersedia cukup untuk menampung
     * semua mata kuliah yang akan dijadwalkan.
     *
     * Dua lapis pengecekan:
     *  1. Global: Total slot yang dibutuhkan vs kapasitas ruangan × hari × jam.
     *  2. Per-kelas Workshop: Jumlah sesi workshop satu kelas (mis. kelas A)
     *     harus ≤ jumlah blok non-overlapping yang muat dalam semua hari aktif.
     *     (karena workshop kelas A tidak boleh bentrok satu sama lain)
     *
     * @param  string $semesterType  'Ganjil' atau 'Genap'
     * @param  float  $durasi4Sks   Max jam per pertemuan untuk mata kuliah praktek
     * @return array  ['success' => bool, 'message' => string, 'details' => string]
     */
    private function checkCapacity(string $semesterType, float $durasi4Sks): array
    {
        $activeRooms   = Ruangan::where('status', 'Active')->count();
        $activeTeknisi = \App\Models\Teknisi::where('status', 'Active')->count();
        $haris         = Hari::where('status', 'Active')->get();
        $jams          = Jam::where('status', 'Active')->orderBy('jam_mulai')->get()->values();
        $activeDays    = $haris->count();
        $activeJams    = $jams->count();

        if ($activeRooms == 0 || $activeDays == 0 || $activeJams == 0) {
            return [
                'success' => false,
                'message' => 'Data jadwal belum lengkap.',
                'details' => 'Pastikan sudah ada minimal 1 ruangan, 1 hari, dan 1 jam kuliah yang diaktifkan '
                    . 'sebelum membuat jadwal.',
            ];
        }

        // Cek apakah slot istirahat (12:00-13:00) aktif
        $hasLunchSlot = $jams->contains(function ($jam) {
            $s = \Carbon\Carbon::parse($jam->jam_mulai);
            $e = \Carbon\Carbon::parse($jam->jam_selesai);
            return $s >= \Carbon\Carbon::parse('12:00') && $e <= \Carbon\Carbon::parse('13:00');
        });

        /**
         * Menghitung berapa banyak blok non-overlapping dengan ukuran $durationSlots
         * yang bisa masuk pada satu hari tertentu, dengan mempertimbangkan:
         * - Barrier 12:00 (jika slot istirahat tidak aktif)
         * - Blokir Sholat Jumat 11:00–13:00
         *
         * Pakai greedy: ambil blok pertama yang valid, lalu loncat ke setelahnya.
         */
        $countNonOverlappingBlocks = function (int $durationSlots, $hari) use ($jams, $activeJams, $hasLunchSlot): int {
            $count     = 0;
            $nextStart = 0;
            while ($nextStart <= $activeJams - $durationSlots) {
                $mulai   = \Carbon\Carbon::parse($jams[$nextStart]->jam_mulai);
                $selesai = \Carbon\Carbon::parse($jams[$nextStart + $durationSlots - 1]->jam_selesai);

                $blocked = false;

                // Barrier 12:00: blok tidak boleh melintasi jam makan siang
                if (!$hasLunchSlot
                    && $mulai < \Carbon\Carbon::parse('12:00')
                    && $selesai > \Carbon\Carbon::parse('12:00')
                ) {
                    $blocked = true;
                }

                // Jumat: blokir slot yang melintasi 11:00–13:00
                if ($hari->nama_hari === 'Jumat'
                    && $mulai < \Carbon\Carbon::parse('13:00')
                    && $selesai > \Carbon\Carbon::parse('11:00')
                ) {
                    $blocked = true;
                }

                if (!$blocked) {
                    $count++;
                    $nextStart += $durationSlots; // loncat melewati blok ini (non-overlapping)
                } else {
                    $nextStart++; // coba posisi berikutnya
                }
            }
            return $count;
        };

        // Filter mata kuliah sesuai semester yang akan digenerate
        $mataKuliahs = MataKuliah::where('status', 'Active')->get()->filter(function ($mk) use ($semesterType) {
            $sem = (int) $mk->semester;
            return $semesterType === 'Ganjil' ? ($sem % 2 != 0) : ($sem % 2 == 0);
        });

        if ($mataKuliahs->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Belum ada mata kuliah untuk semester ini.',
                'details' => 'Tidak ditemukan mata kuliah aktif untuk semester ' . $semesterType
                    . '. Silakan tambahkan dan aktifkan mata kuliah terlebih dahulu.',
            ];
        }

        $workshopDurationSlots = (int) ($durasi4Sks * 2); // jam → slot

        // ─────────────────────────────────────────────────────────
        // CEK 1 — Global: total slot dibutuhkan vs kapasitas kasar
        // ─────────────────────────────────────────────────────────
        $totalSlotsNeeded         = 0;
        $kelasOccurrences         = []; // [semester_kelas] => total sesi workshop
        $totalWorkshopOccurrences = 0; // semua sesi workshop lintas semester (untuk Cek 3)

        foreach ($mataKuliahs as $mk) {
            // 1 SKS Teori = 2 slot | 1 SKS Praktek = 4 slot (2 jam × 2 slot/jam)
            $durasiTeoriSlots   = $mk->sks_teori * 2;
            $durasiPraktekSlots = 0;
            $occurrencesPraktek = 1;

            if ($mk->sks_praktek > 0) {
                $defaultPraktekSlots = $mk->sks_praktek * 4;

                if ($mk->sks_teori > 0) {
                    // Campuran: 1 sesi, tidak kena override
                    $durasiPraktekSlots = $defaultPraktekSlots;
                    $occurrencesPraktek = 1;
                } else {
                    // Murni Praktek: terapkan override
                    $maxJamPraktek = (float) $durasi4Sks;
                    if ($maxJamPraktek > 0) {
                        $occurrencesPraktek = 2;
                        $durasiPraktekSlots = $maxJamPraktek * 2; // jam → slot
                    } else {
                        $totalJam           = $mk->sks_praktek * 2;
                        $occurrencesPraktek = ($totalJam > 4) ? 2 : 1;
                        $durasiPraktekSlots = ($totalJam / $occurrencesPraktek) * 2;
                    }

                    // Catat per-kelas untuk Cek 2
                    if (!empty($mk->kelas)) {
                        $kelasKey = $mk->semester . '_' . $mk->kelas;
                        $kelasOccurrences[$kelasKey] = ($kelasOccurrences[$kelasKey] ?? 0) + $occurrencesPraktek;
                    }

                    // Catat total untuk Cek 3 (termasuk workshop sem-6 tanpa kelas)
                    $totalWorkshopOccurrences += $occurrencesPraktek;
                }
            }

            $totalDurationSlots  = $durasiTeoriSlots + $durasiPraktekSlots;
            $totalSlotsNeeded   += $occurrencesPraktek * $totalDurationSlots;
        }

        $totalCapacitySlots = $activeRooms * $activeDays * $activeJams;
        if ($totalSlotsNeeded > $totalCapacitySlots) {
            return [
                'success' => false,
                'message' => 'Ruang kuliah tidak cukup untuk semua mata kuliah.',
                'details' => 'Jumlah jam mengajar yang dibutuhkan melebihi total jam yang tersedia '
                    . 'di semua ruangan dan hari aktif. '
                    . 'Saran: nonaktifkan beberapa mata kuliah, atau tambah ruangan/hari kuliah.',
            ];
        }

        // ─────────────────────────────────────────────────────────────────
        // CEK 2 — Per-kelas Workshop: workshop kelas A (mis.) tidak boleh
        // overlap satu sama lain, sehingga setiap sesi butuh "jendela waktu"
        // eksklusif. Hitung total jendela non-overlapping yang tersedia, dan
        // bandingkan dengan total sesi yang dibutuhkan kelompok kelas itu.
        // ─────────────────────────────────────────────────────────────────

        // Pre-compute sekali — dipakai di Cek 2 dan Cek 3
        $totalAvailableBlocks = 0;
        foreach ($haris as $hari) {
            $totalAvailableBlocks += $countNonOverlappingBlocks($workshopDurationSlots, $hari);
        }

        $infeasibleGroups = [];
        foreach ($kelasOccurrences as $kelasKey => $totalOccurrences) {
            if ($totalOccurrences > $totalAvailableBlocks) {
                [$sem, $kelas] = explode('_', $kelasKey, 2);
                $infeasibleGroups[] = "Semester {$sem} Kelas {$kelas}: butuh {$totalOccurrences} sesi, "
                    . "tersedia {$totalAvailableBlocks} slot waktu non-tumpang-tindih.";
            }
        }

        if (!empty($infeasibleGroups)) {
            // Ubah pesan per-kelompok ke bahasa yang lebih mudah dipahami
            $detailPesan = [];
            foreach ($infeasibleGroups as $grup) {
                // Contoh format internal: "Semester 2 Kelas A: butuh 10 sesi, tersedia 8 slot"
                // Kita langsung ganti seluruh daftar dengan kalimat ringkas
                $detailPesan[] = $grup;
            }
            return [
                'success' => false,
                'message' => 'Jadwal workshop tidak dapat dibuat tanpa bentrok.',
                'details' => 'Jumlah pertemuan workshop terlalu banyak dibanding waktu yang tersedia. '
                    . 'Hal ini terjadi karena mahasiswa dalam satu kelas tidak boleh mengikuti '
                    . 'dua workshop secara bersamaan. '
                    . 'Saran: kurangi durasi per pertemuan workshop, atau nonaktifkan beberapa mata kuliah workshop.',
            ];
        }

        // ─────────────────────────────────────────────────────────────────
        // CEK 3 — Tekanan ruangan per blok waktu (lintas semester & kelas)
        //
        // Cek 2 hanya melihat setiap kelas secara terpisah. Tapi di lapangan,
        // workshop dari SEMUA semester dan kelas berebut ruangan yang SAMA
        // di blok waktu yang SAMA.
        //
        // Utilisasi = total sesi workshop / (blok tersedia × jumlah ruangan)
        // Jika utilisasi > 80%: ABC sudah sangat sulit menemukan solusi bebas
        // konflik karena hampir setiap blok terisi penuh dan tidak ada sisa
        // ruang untuk bergerak.
        // ─────────────────────────────────────────────────────────────────
        if ($totalWorkshopOccurrences > 0 && $totalAvailableBlocks > 0) {
            if ($activeTeknisi == 0) {
                return [
                    'success' => false,
                    'message' => 'Belum ada teknisi aktif.',
                    'details' => 'Mata kuliah workshop membutuhkan minimal 1 teknisi aktif. Silakan tambahkan dan aktifkan teknisi terlebih dahulu.',
                ];
            }
            
            $effectiveRoomsForWorkshop = min($activeRooms, $activeTeknisi);
            $roomBlockCapacity = $totalAvailableBlocks * $effectiveRoomsForWorkshop;

            if ($totalWorkshopOccurrences > $roomBlockCapacity) {
                // Hard block: secara matematis tidak mungkin
                return [
                    'success' => false,
                    'message' => 'Ruangan/Teknisi tidak cukup untuk semua sesi workshop.',
                    'details' => 'Jumlah sesi workshop yang harus dijadwalkan (' . $totalWorkshopOccurrences . ' sesi) '
                        . 'melebihi total tempat/teknisi yang tersedia (' . $roomBlockCapacity . ' slot waktu). '
                        . 'Ini berarti sebagian mata kuliah pasti tidak bisa mendapat jadwal. '
                        . 'Saran: nonaktifkan beberapa mata kuliah praktek, atau tambah teknisi/hari kuliah.',
                ];
            }

            $utilizationPct = (int) round($totalWorkshopOccurrences / $roomBlockCapacity * 100);
            if ($utilizationPct > 85) {
                $saranDurasi = round($durasi4Sks - 0.5, 1);
                return [
                    'success' => false,
                    'message' => 'Jadwal terlalu padat — kemungkinan besar akan ada bentrok.',
                    'details' => 'Jumlah sesi workshop terlalu banyak dibanding ruangan dan waktu yang tersedia. '
                        . 'Dengan kondisi ini, sistem tidak dapat menyusun jadwal yang sepenuhnya bebas bentrok. '
                        . 'Saran yang bisa dilakukan: '
                        . '(1) Kurangi durasi workshop per pertemuan'
                        . 'atau (2) Aktifkan lebih banyak hari kuliah.',
                ];
            }
        }

        return ['success' => true];
    }
}
