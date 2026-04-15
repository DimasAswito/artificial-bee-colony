<?php

namespace App\Services;

use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Hari;
use App\Models\Jam;
use Carbon\Carbon;

class ABCAlgorithm
{
    // =========================================================================
    // PARAMETER ALGORITMA
    // =========================================================================
    protected int   $populationSize; // Jumlah solusi (lebah pekerja) dalam populasi
    protected int   $maxCycles;      // Batas maksimum iterasi/siklus pencarian
    protected int   $limit;          // Batas percobaan sebelum solusi ditinggalkan (Scout)
    protected string $semester;      // Semester aktif: 'Ganjil' atau 'Genap'
    protected float $durasi4Sks;     // Override max jam per pertemuan untuk mata kuliah praktek

    // =========================================================================
    // DATA MASTER (dimuat sekali saat konstruksi)
    // =========================================================================
    protected array $data = [];
    protected bool  $hasLunchSlot = false; // True jika ada slot jam aktif di 12:00-13:00

    // =========================================================================
    // CONSTRUCTOR & DATA LOADER
    // =========================================================================
    public function __construct(int $populationSize = 50, int $maxCycles = 1000, string $semester = 'Ganjil', float $durasi4Sks = 3)
    {
        $this->populationSize = $populationSize;
        $this->maxCycles      = $maxCycles;
        $this->semester       = $semester;
        $this->durasi4Sks     = $durasi4Sks;
        $this->limit = max(20, (int)($populationSize * 2));

        $this->loadData();
    }

    /**
     * Memuat seluruh data master dari database ke memori.
     * Dilakukan sekali di awal agar tidak ada query berulang saat iterasi.
     */
    protected function loadData(): void
    {
        // Filter mata kuliah aktif sesuai semester (ganjil=semester 1,3,5 | genap=2,4,6)
        $this->data['mata_kuliah'] = MataKuliah::where('status', 'Active')
            ->with('dosen')
            ->get()
            ->filter(
                fn($mk) => $this->semester === 'Ganjil'
                    ? ((int)$mk->semester % 2 != 0)
                    : ((int)$mk->semester % 2 == 0)
            )
            ->values();

        // Pisahkan pool ruangan: Teori pakai Ruang 101/Aula, Praktek pakai semua ruangan
        $this->data['ruangan'] = Ruangan::where('status', 'Active')->get();
        $this->data['teori_ruangans'] = $this->data['ruangan']
            ->filter(fn($r) => in_array(strtolower($r->nama_ruangan), ['ruang 101', 'aula']))
            ->values();

        // Fallback: Jika tidak ada Ruang 101/Aula, gunakan semua ruangan
        if ($this->data['teori_ruangans']->isEmpty()) {
            $this->data['teori_ruangans'] = $this->data['ruangan'];
        }

        $this->data['hari'] = Hari::where('status', 'Active')->get();
        $this->data['jam']  = Jam::where('status', 'Active')->orderBy('jam_mulai')->get()->values();

        // Cek apakah ada slot jam aktif di rentang 12:00-13:00 (istirahat dimatikan admin)
        $this->hasLunchSlot = $this->data['jam']->contains(function ($jam) {
            $mulai   = Carbon::parse($jam->jam_mulai);
            $selesai = Carbon::parse($jam->jam_selesai);
            return $mulai >= Carbon::parse('12:00') && $selesai <= Carbon::parse('13:00');
        });
        
    }

    // =========================================================================
    // FUNGSI UTAMA: JALANKAN ALGORITMA ABC
    // =========================================================================
    public function run(): array
    {
        // Amankan jika data master kosong
        if (
            $this->data['mata_kuliah']->isEmpty() || $this->data['ruangan']->isEmpty()
            || $this->data['hari']->isEmpty() || $this->data['jam']->isEmpty()
        ) {
            return ['schedule' => [], 'fitness' => 0, 'iterations' => 0, 'conflicts' => 0, 'stopped_by' => 'empty_data'];
        }

        // -----------------------------------------------------------------
        // FASE 1: BANGKITKAN POPULASI AWAL (Initialization)
        // Buat $populationSize jadwal acak sebagai populasi awal.
        // Setiap individu disimpan bersama nilai fitness-nya agar tidak
        // perlu dihitung ulang setiap kali dibandingkan.
        // -----------------------------------------------------------------
        $population = [];
        for ($i = 0; $i < $this->populationSize; $i++) {
            $scheduleData = $this->generateRandomSchedule();
            $population[] = [
                'data'    => $scheduleData,
                'trial'   => 0,
                'fitness' => $this->calculateFitness($scheduleData),
            ];
        }

        // Urutkan populasi awal dan ambil yang terbaik
        usort($population, fn($a, $b) => $a['fitness'] <=> $b['fitness']);
        $bestSolution         = $population[0];
        $cyclesWithoutConflict = 0;
        $stoppedBy            = 'max_cycles';
        $startTime            = time();

        // -----------------------------------------------------------------
        // LOOP UTAMA: Siklus iterasi algoritma ABC
        // -----------------------------------------------------------------
        for ($cycle = 0; $cycle < $this->maxCycles; $cycle++) {

            // -----------------------------------------------------------------
            // FASE 2: EMPLOYED BEE (Lebah Pekerja)
            // Setiap lebah pekerja mencoba memperbaiki solusinya sendiri
            // dengan melakukan mutasi. Jika hasil mutasi lebih baik, solusi
            // lama diganti. Jika tidak, counter trial bertambah.
            // -----------------------------------------------------------------
            foreach ($population as $i => $bee) {
                $newData    = $this->mutate($bee['data']);
                $newFitness = $this->calculateFitness($newData);

                if ($newFitness < $bee['fitness']) {
                    $population[$i] = ['data' => $newData, 'trial' => 0, 'fitness' => $newFitness];
                } else {
                    $population[$i]['trial']++;
                }
            }

            // -----------------------------------------------------------------
            // FASE 3: ONLOOKER BEE (Lebah Pengamat)
            // Lebah pengamat memilih solusi yang akan diperbaiki berdasarkan
            // probabilitas proporsional terhadap kualitas (fitness) solusi.
            // Solusi yang lebih baik mendapat peluang lebih besar untuk dipilih.
            // -----------------------------------------------------------------
            $probs = $this->calculateProbabilities($population);
            for ($i = 0; $i < $this->populationSize; $i++) {
                $idx        = $this->selectByProbability($probs);
                $newData    = $this->mutate($population[$idx]['data']);
                $newFitness = $this->calculateFitness($newData);

                if ($newFitness < $population[$idx]['fitness']) {
                    $population[$idx] = ['data' => $newData, 'trial' => 0, 'fitness' => $newFitness];
                } else {
                    $population[$idx]['trial']++;
                }
            }

            // -----------------------------------------------------------------
            // FASE 4: SCOUT BEE (Lebah Pramuka)
            // Jika sebuah solusi gagal berkembang melampaui batas $limit,
            // solusi tersebut "ditinggalkan" dan diganti dengan solusi acak baru.
            // Ini mencegah algoritma terjebak di titik optimal lokal (local optimum).
            // -----------------------------------------------------------------
            foreach ($population as $i => $bee) {
                if ($bee['trial'] > $this->limit) {
                    $newData        = $this->generateRandomSchedule();
                    $population[$i] = ['data' => $newData, 'trial' => 0, 'fitness' => $this->calculateFitness($newData)];
                }
            }

            // -----------------------------------------------------------------
            // PERBARUI SOLUSI TERBAIK GLOBAL
            // Bandingkan semua individu populasi saat ini dengan solusi
            // terbaik yang pernah ditemukan. Gunakan nilai fitness yang sudah
            // tersimpan (ter-cache) agar tidak perlu dihitung ulang.
            // -----------------------------------------------------------------
            foreach ($population as $ind) {
                if ($ind['fitness'] < $bestSolution['fitness']) {
                    $bestSolution = $ind;
                }
            }

            // -----------------------------------------------------------------
            // KONDISI BERHENTI DINI (Early Termination)
            // Jika sudah ditemukan solusi tanpa konflik (fitness < 1.000.000),
            // beri 50 siklus tambahan agar penjadwalan semakin dipadatkan ke
            // jam pagi. Setelah itu algoritma berhenti lebih awal.
            // -----------------------------------------------------------------
            if ($bestSolution['fitness'] < 1000000) {
                $cyclesWithoutConflict++;
                if ($cyclesWithoutConflict > 20) {
                    $stoppedBy = 'conflict_free';
                    break;
                }
            } else {
                $cyclesWithoutConflict = 0;
            }
        }

        return [
            'schedule'   => $bestSolution['data'],
            'fitness'    => $bestSolution['fitness'],
            'conflicts'  => $this->countConflicts($bestSolution['data']),
            'iterations' => $cycle,
            'stopped_by' => $stoppedBy,
        ];
    }

    // =========================================================================
    // PEMBANGKITAN SOLUSI AWAL
    // =========================================================================

    /**
     * Membangkitkan satu jadwal acak yang valid untuk semua mata kuliah.
     *
     * Constraint-Aware Initialization:
     * 1. Kelas Teori diproses LEBIH DAHULU karena lebih susah ditempatkan.
     * 2. Slot Teori di-tracking per semester → Workshop menghindarinya.
     * 3. Slot Workshop di-tracking per (semester, kelas) → Workshop kelas
     *    yang sama di semester yang sama menghindari satu sama lain.
     *    Contoh: WS Web A dan WS Mobile A (kelas A, sem 3) tidak saling bertumpuk.
     */
protected function generateRandomSchedule(): array
{
    $schedule = [];

    // Teori duluan agar slot-nya terdaftar sebelum Workshop ditempatkan
    $sorted = $this->data['mata_kuliah']->sortByDesc(fn($mk) => $mk->sks_teori > 0)->values();

    // Tracker slot Teori: [semester][hari_id] => [[start, end], ...]
    $teoriSlots = [];
    // Tracker slot Workshop per-kelas: [semester][kelas][hari_id] => [[start, end], ...]
    $kelasSlots = [];
    // Tracker slot Dosen: [dosen_id][hari_id] => [[start, end], ...]
    // Mencegah 1 dosen mengajar 2 kelas berbeda di waktu yang sama (lintas semester)
    $dosenSlots = [];

    foreach ($sorted as $mk) {
        [$totalDurationSlots, $occurrences] = $this->calculateDurationSlots($mk);

        $usedHariIds = [];
        for ($i = 0; $i < $occurrences; $i++) {
            // ─────────────────────────────────────────────────────────────────
            // FALLBACK BERLAPIS — pastikan SEMUA occurrence selalu masuk jadwal.
            //
            // Jika placement gagal (null), constraint dilonggarkan bertahap:
            //   Level 1: Semua constraint aktif (kondisi ideal).
            //   Level 2: Boleh pakai hari yang sama dengan sesi sebelumnya.
            //   Level 3: Lepas constraint kelas & dosen, hindari slot teori saja.
            //   Level 4: Abaikan semua constraint — taruh di slot waktu manapun.
            //
            // Konflik yang ditimbulkan Level 2-4 diselesaikan oleh ABC iterasinya.
            // ─────────────────────────────────────────────────────────────────

            // Level 1 — ideal
            $assignment = $this->getRandomAssignment(
                $mk, $mk->dosen_id, $totalDurationSlots,
                $usedHariIds, $teoriSlots, $kelasSlots, $dosenSlots
            );

            // Level 2 — boleh hari yang sama
            if ($assignment === null) {
                $assignment = $this->getRandomAssignment(
                    $mk, $mk->dosen_id, $totalDurationSlots,
                    [], $teoriSlots, $kelasSlots, $dosenSlots
                );
            }

            // Level 3 — lepas kelas & dosen, tetap hindari teori
            if ($assignment === null) {
                $assignment = $this->getRandomAssignment(
                    $mk, $mk->dosen_id, $totalDurationSlots,
                    [], $teoriSlots, [], []
                );
            }

            // Level 4 — abaikan semua constraint; hanya syarat blok waktu valid
            if ($assignment === null) {
                $assignment = $this->getRandomAssignment(
                    $mk, $mk->dosen_id, $totalDurationSlots,
                    [], [], [], []
                );
            }

            if ($assignment) {
                $schedule[]    = $assignment;
                $usedHariIds[] = $assignment['hari_id']; // B3: Sesi MK yang sama harus beda hari

                $sem   = $mk->semester;
                $hId   = $assignment['hari_id'];
                $start = $assignment['jam_index'];
                $end   = $start + $assignment['duration_slots'];

                if ($mk->sks_teori > 0) {
                    // Teori: blokir untuk semua workshop semester yang sama
                    $teoriSlots[$sem][$hId][] = [$start, $end];
                } elseif (!empty($mk->kelas)) {
                    // Workshop dengan kelas (A/B/C): blokir untuk workshop
                    // kelas yang sama di semester yang sama
                    $kelasSlots[$sem][$mk->kelas][$hId][] = [$start, $end];
                }

                // Dosen: blokir slot ini untuk semua mata kuliah dosen yang sama
                if ($mk->dosen_id) {
                    $dosenSlots[$mk->dosen_id][$hId][] = [$start, $end];
                }
            }
        }
    }

    return $schedule;
}

    /**
     * Menghitung jumlah slot waktu (30 menit = 1 slot) dan jumlah pertemuan
     * untuk sebuah mata kuliah berdasarkan konfigurasi SKS-nya.
     *
     * Aturan:
     * - Teori    : 1 SKS = 2 slot (1 jam)
     * - Praktek  : 1 SKS = 2 slot default, tapi bisa di-override oleh $durasi4Sks
     * - Campuran : Teori + Praktek digabung menjadi 1 sesi kontinu (tidak kena override)
     *
     * @return array [$durationSlotsPerSession, $numberOfSessions]
     */
    protected function calculateDurationSlots($mk): array
    {
        // 1 SKS Teori  = 1 jam tatap muka = 2 slot (1 slot = 30 menit)
        // 1 SKS Praktek = 2 jam tatap muka = 4 slot
        $teoriSlots = $mk->sks_teori * 2; // SKS × 2 slot/SKS

        if ($mk->sks_praktek == 0) {
            // Murni Teori: 1 pertemuan
            return [$teoriSlots, 1];
        }

        // $praktekSlots = SKS Praktek × 2 jam/SKS × 2 slot/jam = SKS × 4 slot
        $defaultPraktekSlots = $mk->sks_praktek * 4;

        if ($mk->sks_teori > 0) {
            // Campuran (Teori + Praktek): Gabung jadi 1 sesi, tidak kena override
            return [$teoriSlots + $defaultPraktekSlots, 1];
        }

        // Murni Praktek: Terapkan override max jam jika disetting
        $maxJamPraktek = (float) $this->durasi4Sks;
        if ($maxJamPraktek > 0) {
            $occurrences  = 2;                  // Dibagi 2 pertemuan
            $slotsPerSesi = $maxJamPraktek * 2; // Jam → slot (1 jam = 2 slot)
        } else {
            // Fallback otomatis: jika total > 4 jam, bagi 2 pertemuan
            $totalJam    = $mk->sks_praktek * 2; // SKS × 2 jam/SKS
            $occurrences = ($totalJam > 4) ? 2 : 1;
            $slotsPerSesi = ($totalJam / $occurrences) * 2;
        }

        return [(int) $slotsPerSesi, $occurrences];
    }

    /**
     * Mencari penugasan ruangan, hari, dan jam yang valid secara acak
     * untuk sebuah mata kuliah dengan durasi tertentu.
     *
     * @param $teoriSlotsPerSemester array Slot yang sudah dipakai Teori per semester,
     *   format: [semester][hari_id] => [[start, end], ...]. Digunakan saat inisialisasi
     *   untuk menghindari konflik semester sejak awal.
     */
    protected function getRandomAssignment($mk, $dosenId, int $durationSlots, array $excludedHariIds = [], array $teoriSlotsPerSemester = [], array $kelasSlots = [], array $dosenSlots = []): ?array
    {
        $availableDays = $this->data['hari']->whereNotIn('id', $excludedHariIds);

        // Fallback: Jika semua hari di-exclude, gunakan semua hari
        if ($availableDays->isEmpty()) {
            $availableDays = $this->data['hari'];
        }

        foreach ($availableDays->shuffle() as $hari) {
            $validJamIndices = $this->getValidJamIndices($hari, $durationSlots);

            // Filter 1: Buang slot yang bertabrakan dengan Teori semester yang sama
            if (!empty($teoriSlotsPerSemester[$mk->semester][$hari->id])) {
                $validJamIndices = array_values(array_filter($validJamIndices, function ($start) use ($durationSlots, $teoriSlotsPerSemester, $mk, $hari) {
                    $end = $start + $durationSlots;
                    foreach ($teoriSlotsPerSemester[$mk->semester][$hari->id] as [$tStart, $tEnd]) {
                        if ($start < $tEnd && $end > $tStart) return false;
                    }
                    return true;
                }));
            }

            // Filter 2: Buang slot yang bertabrakan dengan Workshop kelas yang sama
            // (misal: WS Web A tidak boleh bersamaan dengan WS Mobile A di semester yang sama)
            $mkKelas = $mk->kelas ?? '';
            if (!empty($mkKelas) && !empty($kelasSlots[$mk->semester][$mkKelas][$hari->id])) {
                $validJamIndices = array_values(array_filter($validJamIndices, function ($start) use ($durationSlots, $kelasSlots, $mk, $hari, $mkKelas) {
                    $end = $start + $durationSlots;
                    foreach ($kelasSlots[$mk->semester][$mkKelas][$hari->id] as [$kStart, $kEnd]) {
                        if ($start < $kEnd && $end > $kStart) return false;
                    }
                    return true;
                }));
            }

            // Filter 3: Buang slot yang sudah dipakai dosen yang sama (lintas semester)
            // Memastikan 1 dosen tidak mengajar 2 kelas berbeda di waktu yang sama
            if ($dosenId && !empty($dosenSlots[$dosenId][$hari->id])) {
                $validJamIndices = array_values(array_filter($validJamIndices, function ($start) use ($durationSlots, $dosenSlots, $dosenId, $hari) {
                    $end = $start + $durationSlots;
                    foreach ($dosenSlots[$dosenId][$hari->id] as [$dStart, $dEnd]) {
                        if ($start < $dEnd && $end > $dStart) return false;
                    }
                    return true;
                }));
            }

            if (empty($validJamIndices)) {
                continue;
            }

            // C1: Heuristik prioritas pagi — pilih index jam lebih awal dengan probabilitas lebih tinggi
            sort($validJamIndices);
            $r        = mt_rand(0, 1000) / 1000;
            $startIdx = $validJamIndices[min(count($validJamIndices) - 1, (int) floor(pow($r, 3) * count($validJamIndices)))];

            // Pilih ruangan sesuai tipe MK (Teori/Campuran -> 101/Aula | Praktek -> semua)
            $ruangan = ($mk->sks_teori > 0)
                ? $this->data['teori_ruangans']->random()
                : $this->data['ruangan']->random();

            return [
                'mata_kuliah_id' => $mk->id,
                'dosen_id'       => $dosenId,
                'ruangan_id'     => $ruangan->id,
                'hari_id'        => $hari->id,
                'jam_id'         => $this->data['jam'][$startIdx]->id,
                'jam_index'      => $startIdx,
                'duration_slots' => $durationSlots,
                'semester'       => $mk->semester,
                'is_teori'       => $mk->sks_teori > 0,
                'kelas'          => $mk->kelas ?? '', // Kelas mahasiswa: 'A', 'B', 'C', atau '' untuk teori
                'teknisi_id'     => null, // Di-assign oleh TeknisiAssigner setelah ABC selesai
            ];
        }

        return null; // Tidak menemukan slot yang valid
    }

    /**
     * Mengembalikan daftar index jam yang valid sebagai titik awal kelas,
     * yaitu semua posisi yang cukup panjang dan lolos pengecekan batasan waktu.
     */
    protected function getValidJamIndices($hari, int $durationSlots): array
    {
        $valid    = [];
        $jamCount = count($this->data['jam']);

        for ($start = 0; $start <= $jamCount - $durationSlots; $start++) {
            if ($this->isValidTimeBlock($hari, $start, $durationSlots)) {
                $valid[] = $start;
            }
        }

        return $valid;
    }

    /**
     * Mengecek apakah blok waktu [startIdx, startIdx+durationSlots-1] valid.
     *
     * Constraint yang dicek:
     * - B4: Kelas tidak boleh melintas jam 12:00 (kecuali slot istirahat diaktifkan admin)
     * - B5: Hari Jumat: jam 11:00-13:00 diblokir untuk Sholat Jumat
     */
    protected function isValidTimeBlock($hari, int $startIdx, int $durationSlots): bool
    {
        $mulai   = Carbon::parse($this->data['jam'][$startIdx]->jam_mulai);
        $selesai = Carbon::parse($this->data['jam'][$startIdx + $durationSlots - 1]->jam_selesai);

        // B4: Barrier jam 12:00 — hanya aktif jika admin tidak mengaktifkan slot istirahat
        if (!$this->hasLunchSlot && $mulai < Carbon::parse('12:00') && $selesai > Carbon::parse('12:00')) {
            return false;
        }

        // B5: Blokir Sholat Jumat (11:00 - 13:00)
        if ($hari->nama_hari === 'Jumat') {
            $jumatStart = Carbon::parse('11:00');
            $jumatEnd   = Carbon::parse('13:00');
            if ($mulai < $jumatEnd && $selesai > $jumatStart) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // MUTASI SOLUSI
    // =========================================================================

    /**
     * Menghasilkan solusi baru dengan mengubah satu kelas secara acak.
     * Menggunakan "Conflict-Directed Mutation": kelas yang sedang konflik
     * diprioritaskan untuk dimutasi agar resolusi lebih terarah dan cepat.
     *
     * 50% peluang: ganti ruangan | 50% peluang: ganti hari & jam
     *
     * Perbaikan: Mutasi hari & jam sekarang sadar-constraint semester.
     * Slot jam Teori yang sudah ada di jadwal di-extract terlebih dahulu,
     * agar kelas yang dimutasi tidak mendarat di slot yang sudah tabrakan.
     */
    protected function mutate(array $scheduleData): array
    {
        if (empty($scheduleData)) {
            return $scheduleData;
        }

        // Prioritaskan mutasi pada kelas yang sedang bertabrakan
        $conflictingIndices = $this->getConflictingIndices($scheduleData);
        $mutateIdx = !empty($conflictingIndices)
            ? $conflictingIndices[array_rand($conflictingIndices)]
            : array_rand($scheduleData);

        $item     = $scheduleData[$mutateIdx];
        $mkMutate = $this->data['mata_kuliah']->firstWhere('id', $item['mata_kuliah_id']);

        $hasConflicts = !empty($conflictingIndices);

        if (mt_rand(0, 9) < ($hasConflicts ? 7 : 5)) {
            // Ganti Hari & Jam (70% ketika ada konflik, 50% ketika tidak ada).
            // Konflik dosen/semester/kelas HANYA bisa diselesaikan dengan ganti waktu,
            // bukan ganti ruangan, maka kita bias ke sini saat ada konflik.

            // B3: Hindari hari yang sudah dipakai sesi lain dari MK yang sama
            $excludedHariIds = collect($scheduleData)
                ->filter(fn($other, $idx) => $idx != $mutateIdx && $other['mata_kuliah_id'] == $item['mata_kuliah_id'])
                ->pluck('hari_id')
                ->toArray();

            // Bangun peta slot Teori, kelas-Workshop, dan Dosen dari jadwal saat ini
            // agar mutasi menempatkan kelas ke posisi yang sudah pasti bebas konflik
            $teoriSlots = [];
            $kelasSlots = [];
            $dosenSlots = [];
            $itemKelas  = $item['kelas'] ?? '';

            foreach ($scheduleData as $idx => $other) {
                if ($idx === $mutateIdx) continue;

                $oHId   = $other['hari_id'];
                $oStart = $other['jam_index'];
                $oEnd   = $oStart + $other['duration_slots'];

                // Track teori (hanya untuk semester yang sama)
                if ($other['semester'] == $item['semester'] && $other['is_teori']) {
                    $teoriSlots[$other['semester']][$oHId][] = [$oStart, $oEnd];
                }

                // Track kelas workshop (hanya kelas yang sama di semester yang sama)
                if (
                    $other['semester'] == $item['semester'] && !$other['is_teori']
                    && !empty($itemKelas) && ($other['kelas'] ?? '') === $itemKelas
                ) {
                    $kelasSlots[$other['semester']][$itemKelas][$oHId][] = [$oStart, $oEnd];
                }

                // Track dosen (lintas semester — 1 dosen tidak boleh mengajar 2 kelas bersamaan)
                if (!empty($other['dosen_id']) && $other['dosen_id'] == $item['dosen_id']) {
                    $dosenSlots[$item['dosen_id']][$oHId][] = [$oStart, $oEnd];
                }
            }

            $newAssignment = $this->getRandomAssignment($mkMutate, $item['dosen_id'], $item['duration_slots'], $excludedHariIds, $teoriSlots, $kelasSlots, $dosenSlots);
            if ($newAssignment) {
                $item['hari_id']   = $newAssignment['hari_id'];
                $item['jam_id']    = $newAssignment['jam_id'];
                $item['jam_index'] = $newAssignment['jam_index'];
            }
        } else {
            // Ganti Ruangan (30% ketika ada konflik, 50% ketika tidak ada)
            $item['ruangan_id'] = ($mkMutate && $mkMutate->sks_teori > 0)
                ? $this->data['teori_ruangans']->random()->id
                : $this->data['ruangan']->random()->id;
        }

        $scheduleData[$mutateIdx] = $item;
        return $scheduleData;
    }

    // =========================================================================
    // EVALUASI FITNESS & KONFLIK
    // =========================================================================

    /**
     * Memeriksa apakah dua kelas (a dan b) bertabrakan (overlap).
     * Dipanggil oleh calculateFitness() dan getConflictingIndices().
     *
     * Aturan konflik yang dicek:
     * - Ruangan yang sama di waktu yang sama
     * - Dosen yang sama di waktu yang sama
     * - Semester yang sama, salah satunya Teori (seluruh angkatan hadir)
     */
    private function hasConflict(array $a, array $b): bool
    {
        // Jika beda hari, tidak mungkin konflik
        if ($a['hari_id'] != $b['hari_id']) {
            return false;
        }

        // Cek apakah ada irisan/tumpang tindih waktu
        $aEnd = $a['jam_index'] + $a['duration_slots'];
        $bEnd = $b['jam_index'] + $b['duration_slots'];
        if (!($a['jam_index'] < $bEnd && $b['jam_index'] < $aEnd)) {
            return false; // Tidak overlap, langsung keluar
        }

        // Konflik ruangan: dua kelas berbeda di ruangan yang sama di waktu bersamaan
        if ($a['ruangan_id'] == $b['ruangan_id']) return true;

        // Konflik dosen: dosen yang sama mengajar di waktu bersamaan
        if (!empty($a['dosen_id']) && $a['dosen_id'] == $b['dosen_id']) return true;

        // Konflik angkatan-Teori: kelas Teori untuk semester yang sama tidak boleh bersamaan
        // karena seluruh angkatan harus hadir (Workshop/Praktek boleh paralel karena kelas dibagi)
        if ($a['semester'] == $b['semester'] && ($a['is_teori'] || $b['is_teori'])) return true;

        // Konflik kelas Workshop: dua Workshop dari kelas yang sama (A/B/C) di semester yang sama
        // tidak boleh bersamaan karena mahasiswanya adalah orang yang sama.
        // Contoh: Workshop Web A dan Workshop Mobile A = mahasiswa kelas A yang sama.
        if (
            $a['semester'] == $b['semester']
            && !$a['is_teori'] && !$b['is_teori']      // Keduanya Workshop
            && !empty($a['kelas']) && $a['kelas'] == $b['kelas'] // Kelas yang sama
        ) return true;

        return false;
    }

    /**
     * Menghitung nilai fitness (kualitas) sebuah jadwal.
     * Semakin kecil nilai = semakin baik.
     *
     * Optimasi: pre-group berdasarkan hari_id terlebih dahulu agar hanya
     * pasangan dalam hari yang SAMA yang dibandingkan → ~5x lebih cepat.
     */
    protected function calculateFitness(array $scheduleData): int
    {
        $violations = 0;

        // Group by hari untuk menghindari perbandingan beda hari yang pasti tidak konflik
        $byDay = [];
        foreach ($scheduleData as $item) {
            $byDay[$item['hari_id']][] = $item;
        }

        foreach ($byDay as $dayItems) {
            $m = count($dayItems);
            for ($i = 0; $i < $m; $i++) {
                $a    = $dayItems[$i];
                $aEnd = $a['jam_index'] + $a['duration_slots'];
                for ($j = $i + 1; $j < $m; $j++) {
                    $b    = $dayItems[$j];
                    $bEnd = $b['jam_index'] + $b['duration_slots'];

                    // Tidak ada irisan waktu → skip
                    if (!($a['jam_index'] < $bEnd && $b['jam_index'] < $aEnd)) continue;

                    // Hitung setiap tipe pelanggaran secara terpisah
                    if ($a['ruangan_id'] == $b['ruangan_id']) $violations++;
                    if (!empty($a['dosen_id']) && $a['dosen_id'] == $b['dosen_id']) $violations++;
                    if ($a['semester'] == $b['semester'] && ($a['is_teori'] || $b['is_teori'])) $violations++;
                    if (
                        $a['semester'] == $b['semester']
                        && !$a['is_teori'] && !$b['is_teori']
                        && !empty($a['kelas']) && $a['kelas'] == $b['kelas']
                    ) $violations++;
                }
            }
        }

        return $violations * 1000000;
    }

    /**
     * Menghitung jumlah konflik murni pada jadwal final (tanpa pinalti waktu).
     * Digunakan untuk menampilkan angka konflik kepada pengguna.
     */
    protected function countConflicts(array $scheduleData): int
    {
        $conflicts = 0;
        $n         = count($scheduleData);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($this->hasConflict($scheduleData[$i], $scheduleData[$j])) {
                    $conflicts++;
                }
            }
        }

        return $conflicts;
    }

    /**
     * Mengembalikan daftar indeks kelas yang sedang mengalami konflik.
     * Digunakan oleh mutate() untuk menentukan kelas mana yang perlu dipindah.
     *
     * Optimasi: pre-group by hari_id agar tidak iterasi O(n²) penuh.
     */
    protected function getConflictingIndices(array $scheduleData): array
    {
        $conflictSet = [];

        // Group dengan menyertakan indeks aslinya
        $byDay = [];
        foreach ($scheduleData as $idx => $item) {
            $byDay[$item['hari_id']][] = ['idx' => $idx, 'data' => $item];
        }

        foreach ($byDay as $dayItems) {
            $m = count($dayItems);
            for ($i = 0; $i < $m; $i++) {
                $a    = $dayItems[$i]['data'];
                $aIdx = $dayItems[$i]['idx'];
                $aEnd = $a['jam_index'] + $a['duration_slots'];
                for ($j = $i + 1; $j < $m; $j++) {
                    $b    = $dayItems[$j]['data'];
                    $bIdx = $dayItems[$j]['idx'];
                    $bEnd = $b['jam_index'] + $b['duration_slots'];

                    if (!($a['jam_index'] < $bEnd && $b['jam_index'] < $aEnd)) continue;

                    if ($this->hasConflict($a, $b)) {
                        $conflictSet[$aIdx] = true;
                        $conflictSet[$bIdx] = true;
                    }
                }
            }
        }

        return array_keys($conflictSet);
    }

    // =========================================================================
    // PROBABILITAS & SELEKSI (Onlooker Bee)
    // =========================================================================

    /**
     * Menghitung probabilitas seleksi setiap individu menggunakan inverse fitness.
     * Individu dengan fitness lebih kecil (lebih baik) mendapat probabilitas lebih tinggi.
     */
    protected function calculateProbabilities(array $population): array
    {
        $inverseFitnesses = array_map(fn($ind) => 1 / (1 + $ind['fitness']), $population);
        $total            = array_sum($inverseFitnesses);
        return array_map(fn($inv) => $inv / $total, $inverseFitnesses);
    }

    /**
     * Memilih indeks individu secara acak berbobot (Roulette Wheel Selection).
     */
    protected function selectByProbability(array $probs): int
    {
        $r   = mt_rand(0, 1000) / 1000;
        $cum = 0;
        foreach ($probs as $i => $p) {
            $cum += $p;
            if ($r <= $cum) return $i;
        }
        return count($probs) - 1;
    }
}
