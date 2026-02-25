<?php

namespace App\Services;

use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Hari;
use App\Models\Jam;
use Carbon\Carbon;

class ABCAlgorithm
{
  protected $populationSize;
  protected $maxCycles;
  protected $semester;
  protected $durasi4Sks;
  protected $limit;

  protected $data = [];

  public function __construct($populationSize = 50, $maxCycles = 1000, $semester = 'Ganjil', $durasi4Sks = 3)
  {
    $this->populationSize = $populationSize;
    $this->maxCycles = $maxCycles;
    $this->semester = $semester;
    $this->durasi4Sks = $durasi4Sks;
    $this->limit = $populationSize * 5;

    $this->loadData();
  }

  protected function loadData()
  {
    $this->data['mata_kuliah'] = MataKuliah::where('status', 'Active')
      ->with('dosen')
      ->get()
      ->filter(function ($mk) {
        $sem = (int) $mk->semester;
        return $this->semester === 'Ganjil' ? ($sem % 2 != 0) : ($sem % 2 == 0);
      })
      ->values();

    // Batasan: Dosen yang sudah ditugaskan untuk mata kuliah tertentu.
    // Dosen yang TIDAK ada di assignedDosenIds dianggap "Dosen Kosong" yang bisa diset otomatis.
    $allMataKuliah = MataKuliah::where('status', 'Active')->get();
    $assignedDosenIds = $allMataKuliah->pluck('dosen_id')->filter()->unique();

    $this->data['dosen'] = Dosen::where('status', 'Active')->get();

    // Simpan Dosen Bebas (Dosen Aktif yang TIDAK ada di assignedDosenIds)
    $this->data['free_dosens'] = $this->data['dosen']->whereNotIn('id', $assignedDosenIds)->values();

    $this->data['ruangan'] = Ruangan::where('status', 'Active')->get();
    $this->data['hari'] = Hari::where('status', 'Active')->get();
    $this->data['jam'] = Jam::where('status', 'Active')->orderBy('jam_mulai')->get()->values();
  }

  public function run()
  {
    // 1. Initial Check
    if ($this->data['mata_kuliah']->isEmpty() || $this->data['ruangan']->isEmpty() || $this->data['hari']->isEmpty() || $this->data['jam']->isEmpty()) {
      return ['schedule' => [], 'fitness' => 0, 'iterations' => 0];
    }

    // 2. Initialize Population
    $population = [];
    for ($i = 0; $i < $this->populationSize; $i++) {
      $population[] = ['data' => $this->generateRandomSchedule(), 'trial' => 0];
    }

    $bestSolution = $this->getBestSolution($population);
    $cyclesWithoutConflict = 0;

    for ($cycle = 0; $cycle < $this->maxCycles; $cycle++) {
      // 3. Employed Bees Phase
      foreach ($population as $i => $schedule) {
        $newScheduleData = $this->mutate($schedule['data']);
        if ($this->calculateFitness($newScheduleData) < $this->calculateFitness($schedule['data'])) {
          $population[$i] = ['data' => $newScheduleData, 'trial' => 0];
        } else {
          $population[$i]['trial']++;
        }
      }

      // 4. Onlooker Bees Phase
      $probs = $this->calculateProbabilities($population);
      for ($i = 0; $i < $this->populationSize; $i++) {
        $idx = $this->selectByProbability($probs);
        $newScheduleData = $this->mutate($population[$idx]['data']);
        if ($this->calculateFitness($newScheduleData) < $this->calculateFitness($population[$idx]['data'])) {
          $population[$idx] = ['data' => $newScheduleData, 'trial' => 0];
        } else {
          $population[$idx]['trial']++;
        }
      }

      // 5. Scout Bees Phase
      foreach ($population as $i => $schedule) {
        if ($schedule['trial'] > $this->limit) {
          $population[$i] = ['data' => $this->generateRandomSchedule(), 'trial' => 0];
        }
      }

      // 6. Update Best Solution
      $currentBest = $this->getBestSolution($population);
      if ($this->calculateFitness($currentBest['data']) < $this->calculateFitness($bestSolution['data'])) {
        $bestSolution = $currentBest;
      }

      // 7. Termination Condition
      // Tabrakan (Konflik) digandakan 1 Juta poin. Jika < 1 Juta, berarti solusi bebas konflik.
      $bestFitness = $this->calculateFitness($bestSolution['data']);
      if ($bestFitness < 1000000) {
        $cyclesWithoutConflict++;
        // Beri waktu 50 cycle ekstra untuk lebah "memampatkan" jadwal ke pagi hari
        if ($cyclesWithoutConflict > 50) break;
      } else {
        $cyclesWithoutConflict = 0; // Reset
      }
    }

    // 8. Hitung murni jumlah tabrakan untuk ditampilkan ke user
    $finalConflicts = $this->countConflicts($bestSolution['data']);

    return [
      'schedule'   => $bestSolution['data'],
      'fitness'    => $this->calculateFitness($bestSolution['data']),
      'conflicts'  => $finalConflicts,
      'iterations' => $cycle
    ];
  }

  protected function countConflicts($scheduleData)
  {
    $conflictsFast = 0;
    $n = count($scheduleData);

    for ($i = 0; $i < $n; $i++) {
      $a = $scheduleData[$i];
      for ($j = $i + 1; $j < $n; $j++) {
        $b = $scheduleData[$j];
        if ($a['hari_id'] == $b['hari_id']) {
          $aEnd = $a['jam_index'] + $a['duration_slots'];
          $bEnd = $b['jam_index'] + $b['duration_slots'];
          if ($a['jam_index'] < $bEnd && $b['jam_index'] < $aEnd) {
            if ($a['ruangan_id'] == $b['ruangan_id']) $conflictsFast++;
            if (!empty($a['dosen_id']) && $a['dosen_id'] == $b['dosen_id']) $conflictsFast++;
          }
        }
      }
    }
    return $conflictsFast;
  }

  protected function generateRandomSchedule()
  {
    $schedule = [];

    foreach ($this->data['mata_kuliah'] as $mk) {
      $occurrences = ($mk->sks == 4) ? 2 : 1;
      $durationSlots = ($mk->sks == 4) ? $this->durasi4Sks : 2;

      // Konstrain B2: Mata kuliah HANYA diajar dosen pengampunya
      $dosenId = $mk->dosen_id;

      // Jika dosen kosong di DB, tugaskan dosen bebas secara acak
      if (!$dosenId) {
        if ($this->data['free_dosens']->isNotEmpty()) {
          $dosenId = $this->data['free_dosens']->random()->id;
        } else {
          // Fallback terakhir jika anehnya tidak ada dosen bebas, ambil dosen sembarang
          $dosenId = $this->data['dosen']->random()->id;
        }
      }

      $usedHariIds = [];

      for ($i = 0; $i < $occurrences; $i++) {
        $assignment = $this->getRandomAssignment($mk->id, $dosenId, $durationSlots, $usedHariIds);
        if ($assignment) {
          $schedule[] = $assignment;
          // Konstrain B3: 4 SKS (2 sesi) tidak boleh di hari yang sama
          $usedHariIds[] = $assignment['hari_id'];
        }
      }
    }

    return $schedule;
  }

  protected function getRandomAssignment($mkId, $dosenId, $durationSlots, $excludedHariIds = [])
  {
    $availableDays = $this->data['hari']->whereNotIn('id', $excludedHariIds);

    // Fallback jika anehnya semua hari ter-exclude (harusnya tidak mungkin jika hari cukup)
    if ($availableDays->isEmpty()) {
      $availableDays = $this->data['hari'];
    }

    // Acak urutan hari untuk dicoba
    $shuffledDays = $availableDays->shuffle();

    foreach ($shuffledDays as $hari) {
      $validJamIndices = $this->getValidJamIndices($hari, $durationSlots);

      if (!empty($validJamIndices)) {
        // Heuristik C1: Prioritas Pagi (Indeks indeks kecil)
        sort($validJamIndices);

        // Gunakan fungsi eksponensial (r^3) agar probabilitas memilih angka kecil jauh lebih tinggi
        $r = mt_rand(0, 1000) / 1000;
        $biasedIndex = floor(pow($r, 3) * count($validJamIndices));

        // Pastikan aman tidak Out of Bounds
        $biasedIndex = min(count($validJamIndices) - 1, max(0, $biasedIndex));
        $startIdx = $validJamIndices[$biasedIndex];

        $ruangan = $this->data['ruangan']->random();

        return [
          'mata_kuliah_id' => $mkId,
          'dosen_id'       => $dosenId,
          'ruangan_id'     => $ruangan->id,
          'hari_id'        => $hari->id,
          'jam_id'         => $this->data['jam'][$startIdx]->id,
          'jam_index'      => $startIdx,
          'duration_slots' => $durationSlots
        ];
      }
    }

    return null;
  }

  protected function getValidJamIndices($hari, $durationSlots)
  {
    $valid = [];
    $jamCount = count($this->data['jam']);

    // Cek semua kemungkinan index awal yang cukup untuk menampung durasi kelas
    for ($start = 0; $start <= $jamCount - $durationSlots; $start++) {
      if ($this->isValidTimeBlock($hari, $start, $durationSlots)) {
        $valid[] = $start;
      }
    }
    return $valid;
  }

  protected function isValidTimeBlock($hari, $startIdx, $durationSlots)
  {
    $startJam = $this->data['jam'][$startIdx];
    $endJam = $this->data['jam'][$startIdx + $durationSlots - 1];

    $mulai = Carbon::parse($startJam->jam_mulai);
    $selesai = Carbon::parse($endJam->jam_selesai);

    // Konstrain B4: Cek strict barrier jam 12:00 (Istirahat Siang)
    // Kelas tidak boleh menyeberangi jam 12:00. Jika mulai sebelum 12 dan selesai sesudah 12 = Invalid.
    $jam12 = Carbon::parse('12:00:00');
    if ($mulai < $jam12 && $selesai > $jam12) {
      return false;
    }

    // Konstrain B5: Aturan Sholat Jumat (11:00 - 13:00)
    if ($hari->nama_hari === 'Jumat') {
      $jumatStart = Carbon::parse('11:00:00');
      $jumatEnd = Carbon::parse('13:00:00');

      // Logika Irisan Waktu (Overlap)
      if ($mulai < $jumatEnd && $selesai > $jumatStart) {
        return false;
      }
    }

    return true;
  }

  protected function mutate($scheduleData)
  {
    if (empty($scheduleData)) return $scheduleData;

    // Conflict-Directed Mutation: Prioritaskan mengubah kelas yang sedang tabrakan
    $conflictingIndices = $this->getConflictingIndices($scheduleData);
    $mutateIdx = !empty($conflictingIndices)
      ? $conflictingIndices[array_rand($conflictingIndices)]
      : array_rand($scheduleData);

    $item = $scheduleData[$mutateIdx];

    // 50% ganti Ruangan, 50% ganti Waktu
    if (mt_rand(0, 1) == 0) {
      $item['ruangan_id'] = $this->data['ruangan']->random()->id;
    } else {
      // Untuk merubah waktu, kita harus mengecek sibling session hari (jika 4 SKS)
      $excludedHariIds = [];
      foreach ($scheduleData as $idx => $other) {
        if ($idx != $mutateIdx && $other['mata_kuliah_id'] == $item['mata_kuliah_id']) {
          $excludedHariIds[] = $other['hari_id'];
        }
      }

      $newAssignment = $this->getRandomAssignment($item['mata_kuliah_id'], $item['dosen_id'], $item['duration_slots'], $excludedHariIds);
      if ($newAssignment) {
        $item['hari_id']   = $newAssignment['hari_id'];
        $item['jam_id']    = $newAssignment['jam_id'];
        $item['jam_index'] = $newAssignment['jam_index'];
      }
    }

    $scheduleData[$mutateIdx] = $item;
    return $scheduleData;
  }

  protected function getConflictingIndices($scheduleData)
  {
    $indices = [];
    $n = count($scheduleData);

    for ($i = 0; $i < $n; $i++) {
      for ($j = $i + 1; $j < $n; $j++) {
        $a = $scheduleData[$i];
        $b = $scheduleData[$j];

        if ($a['hari_id'] == $b['hari_id']) {
          $aEnd = $a['jam_index'] + $a['duration_slots'];
          $bEnd = $b['jam_index'] + $b['duration_slots'];

          // Cek Irisan/Tabrakan Waktu Jam
          if ($a['jam_index'] < $bEnd && $b['jam_index'] < $aEnd) {
            // Jika jam sama-sama bertumpuk, cek apakah di ruangan yang sama
            if ($a['ruangan_id'] == $b['ruangan_id']) {
              $indices[] = $i;
              $indices[] = $j;
            }
            // Aturan B2/Dosen: Atau dosen yang sama
            if (!empty($a['dosen_id']) && $a['dosen_id'] == $b['dosen_id']) {
              $indices[] = $i;
              $indices[] = $j;
            }
          }
        }
      }
    }

    return array_unique($indices);
  }

  protected function calculateFitness($scheduleData)
  {
    $conflictsFast = 0;
    $timePenalty = 0;
    $n = count($scheduleData);

    for ($i = 0; $i < $n; $i++) {
      $a = $scheduleData[$i];

      // Evaluasi D: Penalty Waktu (semakin sore indexnya, semakin tinggi poinnya/buruk)
      $timePenalty += $a['jam_index'];

      for ($j = $i + 1; $j < $n; $j++) {
        $b = $scheduleData[$j];

        if ($a['hari_id'] == $b['hari_id']) {
          $aEnd = $a['jam_index'] + $a['duration_slots'];
          $bEnd = $b['jam_index'] + $b['duration_slots'];

          // Overlap
          if ($a['jam_index'] < $bEnd && $b['jam_index'] < $aEnd) {
            if ($a['ruangan_id'] == $b['ruangan_id']) $conflictsFast++;
            if (!empty($a['dosen_id']) && $a['dosen_id'] == $b['dosen_id']) $conflictsFast++;
          }
        }
      }
    }

    // Konstrain B6 & Evaluasi D: 1 Konflik dibobot 1 Juta (Sangat buruk).
    return ($conflictsFast * 1000000) + $timePenalty;
  }

  protected function calculateProbabilities($population)
  {
    $fitnesses = [];
    $total = 0;
    foreach ($population as $ind) {
      $fitValue = $this->calculateFitness($ind['data']);
      // Inverse fitness karena algoritma mencari angka mengecil (0 Konflik)
      $invFit = 1 / (1 + $fitValue);
      $fitnesses[] = $invFit;
      $total += $invFit;
    }

    $probs = [];
    foreach ($fitnesses as $fit) {
      $probs[] = $fit / $total;
    }
    return $probs;
  }

  protected function selectByProbability($probs)
  {
    $r = mt_rand(0, 1000) / 1000;
    $cum = 0;
    foreach ($probs as $i => $p) {
      $cum += $p;
      if ($r <= $cum) return $i;
    }
    return count($probs) - 1;
  }

  protected function getBestSolution($population)
  {
    $best = $population[0];
    $minFit = $this->calculateFitness($best['data']);

    foreach ($population as $ind) {
      $fit = $this->calculateFitness($ind['data']);
      if ($fit < $minFit) {
        $minFit = $fit;
        $best = $ind;
      }
    }

    return $best;
  }
}
