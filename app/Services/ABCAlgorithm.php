<?php

namespace App\Services;

use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Hari;
use App\Models\Jam;
use Illuminate\Support\Facades\Log;

class ABCAlgorithm
{
  protected $populationSize;
  protected $maxCycles;
  protected $semester;
  protected $jumatId; // Cache ID Jumat
  protected $limit; // Batas limit untuk lebah pramuka (scout bees)
  protected $data = []; // Cache data master
  /** @var \Illuminate\Support\Collection */
  protected $freeDosens; // Dosen yang BELUM ditugaskan ke mata kuliah apapun

  // Struktur Hasil:
  // [
  //    [ 'mata_kuliah_id' => ..., 'dosen_id' => ..., 'ruangan_id' => ..., 'hari_id' => ..., 'jam_id' => ... ]
  // ]

  public function __construct($populationSize = 50, $maxCycles = 1000, $semester = 'Ganjil')
  {
    $this->populationSize = $populationSize;
    $this->maxCycles = $maxCycles;
    $this->semester = $semester;
    $this->limit = $populationSize * 5; // Heuristik: limit bergantung pada populasi

    // Get Jumat ID dynamically (or fallback to 5)
    $hariJumat = \App\Models\Hari::where('nama_hari', 'Jumat')->first();
    $this->jumatId = $hariJumat ? $hariJumat->id : 5;

    $this->loadData();
  }

  protected function loadData()
  {
    // Filter Mata Kuliah berdasarkan Semester
    $semesterType = $this->semester; // 'Ganjil' or 'Genap'

    $this->data['mata_kuliah'] = MataKuliah::where('status', 'Active')
      ->with('dosen')
      ->get()
      ->filter(function ($mk) use ($semesterType) {
        // Cek apakah semester ganjil atau genap
        // Asumsi semester adalah string numerik "1", "2", "3"
        $sem = (int) $mk->semester;
        if ($semesterType === 'Ganjil') {
          return $sem % 2 != 0;
        } else {
          return $sem % 2 == 0;
        }
      })
      ->values(); // Reset keys after filter

    // Batasan: Dosen yang sudah ditugaskan untuk mata kuliah tertentu TIDAK BISA mengajar mata kuliah lain (yang belum ada tugasnya).
    // 1. Ambil ID semua Dosen yang sudah Ditugaskan
    $allMataKuliah = MataKuliah::where('status', 'Active')->get();
    $assignedDosenIds = $allMataKuliah->pluck('dosen_id')->filter()->unique();

    $this->data['dosen'] = Dosen::where('status', 'Active')->get();

    // 2. Filter Dosen Bebas (Dosen Aktif yang TIDAK ada di assignedDosenIds)
    $this->freeDosens = $this->data['dosen']->whereNotIn('id', $assignedDosenIds)->values();

    $this->data['ruangan'] = Ruangan::where('status', 'Active')->get();
    $this->data['hari'] = Hari::where('status', 'Active')->get();
    $this->data['jam'] = Jam::where('status', 'Active')->orderBy('jam_mulai')->get()->values(); // Ensure strictly ordered and indexed
  }

  public function run()
  {
    // 1. Inisialisasi Populasi
    $population = $this->initializePopulation();
    $bestSolution = $this->getBestSolution($population);

    for ($cycle = 0; $cycle < $this->maxCycles; $cycle++) {

      // 2. Fase Lebah Pekerja (Employed Bees)
      foreach ($population as $index => $schedule) {
        $newSchedule = $this->mutate($schedule);

        $currentFitness = $this->calculateFitness($schedule);
        $newFitness = $this->calculateFitness($newSchedule);

        if ($newFitness < $currentFitness) {
          $population[$index] = $newSchedule;
          $population[$index]['trial_counter'] = 0;
        } else {
          $population[$index]['trial_counter']++;
        }
      }

      // 3. Fase Lebah Pengamat (Onlooker Bees)
      // Pilih berdasarkan probabilitas (Roulette Wheel?) atau probabilitas standar ABC
      // Untuk minimasi, probabilitas sebanding dengan (1 / (1 + fitness))
      $probabilities = $this->calculateProbabilities($population);

      // Onlooker memilih solusi untuk ditingkatkan
      // Disederhanakan: Jalankan sebanyak populationSize
      for ($i = 0; $i < $this->populationSize; $i++) {
        $selectedIndex = $this->selectByProbability($probabilities);
        $selectedSchedule = $population[$selectedIndex];

        $newSchedule = $this->mutate($selectedSchedule);
        $currentFitness = $this->calculateFitness($selectedSchedule);
        $newFitness = $this->calculateFitness($newSchedule);

        if ($newFitness < $currentFitness) {
          $population[$selectedIndex] = $newSchedule;
          $population[$selectedIndex]['trial_counter'] = 0;
        } else {
          $population[$selectedIndex]['trial_counter']++;
        }
      }

      // 4. Fase Lebah Pramuka (Scout Bees)
      foreach ($population as $index => $schedule) {
        if ($schedule['trial_counter'] > $this->limit) {
          $population[$index] = $this->generateRandomSchedule(); // Ganti dengan random baru
        }
      }

      // 5. Simpan Solusi Terbaik
      $currentBest = $this->getBestSolution($population);
      if ($this->calculateFitness($currentBest) < $this->calculateFitness($bestSolution)) {
        $bestSolution = $currentBest;
      }

      // Cek Terminasi: Jika fitness 0 ditemukan, berhenti lebih awal?
      if ($this->calculateFitness($bestSolution) == 0) break;
    }

    return [
      'schedule' => $bestSolution['data'],
      'fitness' => $this->calculateFitness($bestSolution),
      'iterations' => $this->maxCycles // Atau jumlah siklus aktual
    ];
  }

  protected function initializePopulation()
  {
    $population = [];
    for ($i = 0; $i < $this->populationSize; $i++) {
      $population[] = $this->generateRandomSchedule();
    }
    return $population;
  }

  protected function generateRandomSchedule()
  {
    // Satu individu (Jadwal) adalah sekumpulan Penugasan.
    // Kita iterasi melalui SEMUA Mata Kuliah yang AKTIF untuk memberikan slot.
    $scheduleData = [];
    $dosenUsageCount = [];

    // Inisialisasi usage count
    if ($this->data['dosen']->isNotEmpty()) {
      foreach ($this->data['dosen'] as $dosen) {
        $dosenUsageCount[$dosen->id] = 0;
      }
    }

    foreach ($this->data['mata_kuliah'] as $mk) {
      // Aturan: 4 SKS -> 2 Sesi (2 blok masing-masing 2 jam, atau 1 blok 4 jam??)

      $occurrences = ($mk->sks == 4) ? 2 : 1;

      // Tentukan Dosen untuk Instance Mata Kuliah ini
      // Jika Mata Kuliah memiliki dosen tetap, gunakan itu.
      // Jika Mata Kuliah memiliki dosen NULL, pilih satu secara ACAK tapi tetap konsisten untuk semua kemunculan mapel ini di jadwal ini.
      $uniqueDosenId = $mk->dosen_id;
      if (is_null($uniqueDosenId)) {
        // Strategi Baru "Cara Cepat": Load Balanced Random Selection
        // Pilih dosen yang jumlah jam mengajarnya PALING SEDIKIT saat ini

        $candidates = $this->freeDosens->isNotEmpty() ? $this->freeDosens : $this->data['dosen'];
        if ($candidates->isEmpty()) continue;

        // Cari Usage Terkecil
        $minUsage = PHP_INT_MAX;
        foreach ($candidates as $cand) {
          $id = is_array($cand) ? $cand['id'] : $cand->id;
          $usage = $dosenUsageCount[$id] ?? 0;
          if ($usage < $minUsage) {
            $minUsage = $usage;
          }
        }

        // Filter Candidate dengan Minimum Usage
        $bestCandidates = [];
        foreach ($candidates as $cand) {
          $id = is_array($cand) ? $cand['id'] : $cand->id;
          $usage = $dosenUsageCount[$id] ?? 0;
          if ($usage == $minUsage) {
            $bestCandidates[] = $cand;
          }
        }

        // Pilih satu secara acak dari yang terbaik
        $randomDosen = $bestCandidates[array_rand($bestCandidates)];
        $uniqueDosenId = is_array($randomDosen) ? $randomDosen['id'] : $randomDosen->id;
      }

      // Update Usage Count untuk Dosen Terpilih
      if (isset($dosenUsageCount[$uniqueDosenId])) {
        $dosenUsageCount[$uniqueDosenId]++;
      } else {
        $dosenUsageCount[$uniqueDosenId] = 1;
      }

      for ($k = 0; $k < $occurrences; $k++) {
        // PERUBAHAN LOGIKA DURASI (1 Slot = 1 Jam)
        // 4 SKS = 3 Jam (3 Slot) -- Permintaan user
        // 2 SKS = 2 Jam (2 Slot)
        $durationSlots = ($mk->sks == 4) ? 3 : 2;

        // Acak Slot
        $randomAssignment = $this->getRandomAssignment($mk, $durationSlots, $uniqueDosenId);
        $scheduleData[] = $randomAssignment;
      }
    }

    return [
      'data' => $scheduleData,
      'trial_counter' => 0
    ];
  }

  protected function getConflictingIndices($assignments)
  {
    $indices = [];
    $n = count($assignments);

    for ($i = 0; $i < $n; $i++) {
      for ($j = $i + 1; $j < $n; $j++) {
        $a = $assignments[$i];
        $b = $assignments[$j];

        // Cek Hari Sama
        if ($a['hari_id'] == $b['hari_id']) {
          $aStart = $a['jam_index'];
          $aEnd = $aStart + $a['duration_slots'];
          $bStart = $b['jam_index'];
          $bEnd = $bStart + $b['duration_slots'];

          // Cek Irisan Waktu
          if ($aStart < $bEnd && $bStart < $aEnd) {
            // Konflik Ruangan ATAU Dosen
            if ($a['ruangan_id'] == $b['ruangan_id'] || $a['dosen_id'] == $b['dosen_id']) {
              $indices[] = $i;
              $indices[] = $j;
            }
          }
        }
      }
    }
    return array_unique($indices);
  }

  protected function getRandomAssignment($mk, $durationSlots, $dosenId)
  {
    // Pilih Hari Secara Acak
    $hari = $this->data['hari']->random();

    // Pilih Ruangan Secara Acak
    $ruangan = $this->data['ruangan']->random();

    // Pilih Jam Mulai Secara Acak (Jam)
    // Harus memastikan ada cukup slot yang berurutan
    // Seeder Jam: 4 record (id biasanya 1,2,3,4).
    // Jika durasi 2 slot (4 jam), mulai yang valid: Slot 1 (08-10) -> Berakhir 12:00. Slot 2 -> Berakhir 14:00 (Istirahat?).
    // Kita perlu mengecek apakah kita bisa memilih jam berurutan.
    // Karena ID DB mungkin tidak berurutan atau dijamin, kita gunakan index array dari data yang dimuat.

    $jamCount = $this->data['jam']->count();
    // GUNAKAN LOGIKA VALID INDICES (INLINE)
    // Cegah menyeberang istirahat (Index 3 = 11-12, Index 4 = 13-14)
    // Blok Pagi: 0, 1, 2, 3
    // Blok Siang: 4, 5, 6, 7

    $validStartIndices = [];
    $range = range(0, $jamCount - $durationSlots);

    foreach ($range as $start) {
      $end = $start + $durationSlots;
      // Jika start di Pagi (<=3), End harus <= 4 (artinya max sampai jam 12)
      // Jika start di Siang (>=4), aman

      $isCrossingBreak = false;
      // USER REQUEST: DISABLE BREAK CONSTRAINT (9 SLOTS CONTINUOUS)
      // if ($start <= 3 && $end > 4) {
      //   $isCrossingBreak = true;
      // }

      // FRIDAY PRAYER EXCEPTION: Forbidden slots 3 (11:00-12:00) and 4 (12:00-13:00)
      if ($hari->id == $this->jumatId) {
        for ($s = $start; $s < $end; $s++) {
          if ($s == 3 || $s == 4) {
            $isCrossingBreak = true;
            break;
          }
        }
      }

      if (!$isCrossingBreak) {
        $validStartIndices[] = $start;
      }
    }

    if (empty($validStartIndices)) {
      // Fallback: Pilih sembarang jika terpaksa
      $startIndex = rand(0, $jamCount - 1);
    } else {
      $startIndex = $validStartIndices[array_rand($validStartIndices)];
    }

    // Jam Terpilih (Simpan hanya JAM ID mulai, logika menyiratkan urutan)
    $startJam = $this->data['jam'][$startIndex];

    return [
      'mata_kuliah_id' => $mk->id,
      'dosen_id' => $dosenId, // Batasan: Dosen yang Ditugaskan (Tetap atau Acak)
      'ruangan_id' => $ruangan->id,
      'hari_id' => $hari->id,
      'jam_id' => $startJam->id,
      // Meta data untuk pengecekan
      'sks' => $mk->sks,
      'duration_slots' => $durationSlots,
      'jam_index' => $startIndex // Pembantu untuk mencari jam berurutan
    ];
  }

  protected function mutate($schedule)
  {
    // Ubah SATU penugasan dalam jadwal
    $newScheduleData = $schedule['data'];

    // LOGIKA MUTASI PINTAR (Conflict-Directed Mutation)
    // 1. Identifikasi item yang mengalami konflik
    $conflictingIndices = $this->getConflictingIndices($newScheduleData);

    // 2. Jika ada konflik, prioritaskan mutasi pada item tersebut
    if (!empty($conflictingIndices)) {
      $mutationIndex = $conflictingIndices[array_rand($conflictingIndices)];
    } else {
      // Jika tidak ada konflik, mutasi acak
      $mutationIndex = array_rand($newScheduleData);
    }

    $item = $newScheduleData[$mutationIndex];
    // Mutasi: Pilih Ruangan, Hari, atau Waktu acak baru.
    // Kita tidak memutasi relasi Mata Kuliah/Dosen karena itu tetap.

    // Strategi Mutasi Sederhana:
    // 1. Ubah Ruangan
    // 2. Ubah Waktu (Hari + Jam)

    if (rand(0, 1) == 0) {
      // Ubah Ruangan
      $item['ruangan_id'] = $this->data['ruangan']->random()->id;
    } else {
      // Ubah Waktu
      $hari = $this->data['hari']->random();
      $item['hari_id'] = $hari->id;

      // Acak ulang jam yang valid
      $jamCount = $this->data['jam']->count();
      $durationSlots = $item['duration_slots'];

      $validStartIndices = [];
      $range = range(0, $jamCount - $durationSlots);

      foreach ($range as $start) {
        $end = $start + $durationSlots;
        // Cek Crossing Break (Pagi -> Siang) (Inline Logic)
        // USER REQUEST: DISABLE BREAK CONSTRAINT
        // if ($start <= 3 && $end > 4) {
        //   continue; // Skip
        // }

        // FRIDAY PRAYER EXCEPTION
        if ($hari->id == $this->jumatId) {
          $hitFridayBreak = false;
          for ($s = $start; $s < $end; $s++) {
            if ($s == 3 || $s == 4) {
              $hitFridayBreak = true;
              break;
            }
          }
          if ($hitFridayBreak) continue;
        }

        $validStartIndices[] = $start;
      }

      if (!empty($validStartIndices)) {
        $startIndex = $validStartIndices[array_rand($validStartIndices)];
        $item['jam_id'] = $this->data['jam'][$startIndex]->id;
        $item['jam_index'] = $startIndex;
      }
    }

    $newScheduleData[$mutationIndex] = $item;

    return [
      'data' => $newScheduleData,
      'trial_counter' => $schedule['trial_counter'] // Reset counter ditangani di loop utama
    ];
  }

  protected function calculateFitness($schedule)
  {
    $conflicts = 0;
    $assignments = $schedule['data'];
    $n = count($assignments);

    for ($i = 0; $i < $n; $i++) {
      for ($j = $i + 1; $j < $n; $j++) {
        $a = $assignments[$i];
        $b = $assignments[$j];

        // Cek Tumpang Tindih Waktu
        // Hari Sama
        if ($a['hari_id'] == $b['hari_id']) {
          // Cek Tumpang Tindih Jam
          // Rentang A: [mulai, mulai + durasi)
          $aStart = $a['jam_index'];
          $aEnd = $aStart + $a['duration_slots'];

          $bStart = $b['jam_index'];
          $bEnd = $bStart + $b['duration_slots'];

          if ($aStart < $bEnd && $bStart < $aEnd) {
            // KONFLIK WAKTU TERDETEKSI

            // Aturan 1: Konflik Ruangan (Ruangan Sama pada Waktu Sama)
            if ($a['ruangan_id'] == $b['ruangan_id']) {
              $conflicts++;
            }

            // Aturan 2: Konflik Dosen (Dosen Sama pada Waktu Sama)
            if ($a['dosen_id'] == $b['dosen_id']) {
              $conflicts++;
            }

            // Aturan 3: Parameter "Mata kuliah 4 SKS ... bisa digunakan oleh dosen yg tertulis" adalah struktural (ditangani di init).
          }
        }
      }
    }

    // Nilai fitness hanyalah jumlah konflik.
    // ABC biasanya memaksimalkan fitness. Fitness = 1 / (1 + conflicts).
    // Tapi variabel kita adalah 'best_fitness_value' (double), dan user bilang "fitness paling kecil".
    // Jadi kita perlakukan Jumlah Konflik SEBAGAI nilai yang diminimalkan.
    return $conflicts;
  }

  protected function calculateProbabilities($population)
  {
    // Berdasarkan konflik (minimasi).
    // Konversi ke fitness (maksimasi) untuk roulette wheel
    // f = 1 / (1 + conflicts)

    $fitnesses = [];
    $totalFitness = 0;

    foreach ($population as $ind) {
      $conflicts = $this->calculateFitness($ind);
      $fit = 1 / (1 + $conflicts);
      $fitnesses[] = $fit;
      $totalFitness += $fit;
    }

    // Normalize
    $probs = [];
    foreach ($fitnesses as $fit) {
      $probs[] = $fit / $totalFitness;
    }

    return $probs;
  }

  protected function selectByProbability($probs)
  {
    $r = rand(0, 1000) / 1000;
    $cumulative = 0;
    foreach ($probs as $index => $prob) {
      $cumulative += $prob;
      if ($r <= $cumulative) {
        return $index;
      }
    }
    return count($probs) - 1;
  }

  protected function getBestSolution($population)
  {
    $best = $population[0];
    $minConflicts = $this->calculateFitness($best);

    foreach ($population as $ind) {
      $conflicts = $this->calculateFitness($ind);
      if ($conflicts < $minConflicts) {
        $minConflicts = $conflicts;
        $best = $ind;
      }
    }
    return $best;
  }
}
