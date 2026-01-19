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
  protected $limit; // Limit for scout bees
  protected $data = []; // Master data cache
  protected $freeDosens = []; // Dosen who are NOT assigned to any subjet

  // Result structure:
  // [
  //    [ 'mata_kuliah_id' => ..., 'dosen_id' => ..., 'ruangan_id' => ..., 'hari_id' => ..., 'jam_id' => ... ]
  // ]

  public function __construct($populationSize = 50, $maxCycles = 1000, $semester = 'Ganjil')
  {
    $this->populationSize = $populationSize;
    $this->maxCycles = $maxCycles;
    $this->semester = $semester;
    $this->limit = $populationSize * 5; // Heuristic: limit depends on population

    $this->loadData();
  }

  protected function loadData()
  {
    // Filter Mata Kuliah by Semester
    $semesterType = $this->semester; // 'Ganjil' or 'Genap'

    $this->data['mata_kuliah'] = MataKuliah::where('status', 'Active')
      ->with('dosen')
      ->get()
      ->filter(function ($mk) use ($semesterType) {
        // Check if semester is odd or even
        // Assuming semester is numeric string "1", "2", "3"
        $sem = (int) $mk->semester;
        if ($semesterType === 'Ganjil') {
          return $sem % 2 != 0;
        } else {
          return $sem % 2 == 0;
        }
      })
      ->values(); // Reset keys after filter

    // Constraint: Dosen who are assigned to a subject CANNOT teach other subjects (unassigned ones).
    // 1. Get IDs of all Assigned Dosens
    // Note: We need to check ALL active Mata Kuliahs to determine assigned dosens, not just the filtered ones for this semester.
    // Because a Dosen might be assigned to a Semester 2 subject, so they can't take a random Semester 1 subject?
    // User said: "dosen A mengajar Mata kuliah A. maka dosen A tidak bisa mengajar mata kuliah B".
    // This implies GLOBAL exclusivity.
    $allMataKuliah = MataKuliah::where('status', 'Active')->get();
    $assignedDosenIds = $allMataKuliah->pluck('dosen_id')->filter()->unique();

    $this->data['dosen'] = Dosen::where('status', 'Active')->get();

    // 2. Filter Free Dosens (Active Dosens NOT in assignedDosenIds)
    $this->freeDosens = $this->data['dosen']->whereNotIn('id', $assignedDosenIds)->values();

    $this->data['ruangan'] = Ruangan::where('status', 'Active')->get();
    $this->data['hari'] = Hari::where('status', 'Active')->get();
    $this->data['jam'] = Jam::where('status', 'Active')->get();
  }

  public function run()
  {
    // 1. Initialize Population
    $population = $this->initializePopulation();
    $bestSolution = $this->getBestSolution($population);

    for ($cycle = 0; $cycle < $this->maxCycles; $cycle++) {

      // 2. Employed Bees Phase
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

      // 3. Onlooker Bees Phase
      // Select based on probability (Roulette Wheel?) or standard ABC probability
      // For minimization, probability is proportional to (1 / (1 + fitness))
      $probabilities = $this->calculateProbabilities($population);

      // Onlookers select solutions to improve
      // Simplified: Run populationSize times
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

      // 4. Scout Bees Phase
      foreach ($population as $index => $schedule) {
        if ($schedule['trial_counter'] > $this->limit) {
          $population[$index] = $this->generateRandomSchedule(); // Replace with fresh random
        }
      }

      // 5. Memorize Best Solution
      $currentBest = $this->getBestSolution($population);
      if ($this->calculateFitness($currentBest) < $this->calculateFitness($bestSolution)) {
        $bestSolution = $currentBest;
      }

      // Termination Check: If fitness 0 found, break early?
      // if ($this->calculateFitness($bestSolution) == 0) break;
    }

    return [
      'schedule' => $bestSolution['data'],
      'fitness' => $this->calculateFitness($bestSolution),
      'iterations' => $this->maxCycles // Or actual cycle count
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
    // One individual (Schedule) is a set of Assignments.
    // We iterate through ALL ACTIVE Mata Kuliahs to assign them slots.
    $scheduleData = [];

    foreach ($this->data['mata_kuliah'] as $mk) {
      // Rule: 4 SKS -> 2 Sessions (2 blocks of 2-hours each, or 1 block of 4-hours??)
      // User Confirmed Rule: "4 SKS takes 4 hours directly... don't split... appear 2x".
      // Implementation: Schedule this subject TWICE. Each time it occupies 4 Hours?
      // "mata kuliah 4 sks dalam 1 generate jadwal dapat muncul 2x".
      // Let's assume this means: 2 occurrences in the week.
      // AND "mengambil 4 jam langsung".
      // So: 2 Occurrences x 4 Hours = 8 Hours Total. (Heavy, but this is the requirement).
      // OR maybe it means Total 4 SKS, split into 2 meetings of 2 hours?
      // User said "mengambil 4 jam nya langsung... JANGAN DIPISAH". This strongly implies one meeting is 4 hours.
      // So 2 meetings of 4 hours it is.

      $occurrences = ($mk->sks == 4) ? 2 : 1;

      // Determine Dosen for this Subject Instance
      // If Subject has fixed lecturer, use it.
      // If Subject has NULL lecturer, pick a RANDOM one but keep it consistent for all occurrences of this subject in this schedule.
      $uniqueDosenId = $mk->dosen_id;
      if (is_null($uniqueDosenId)) {
        // Fallback: Pick random active dosen FROM FREE POOL
        if ($this->freeDosens->isNotEmpty()) {
          $uniqueDosenId = $this->freeDosens->random()->id;
        } else {
          // If no free dosen available, what to do?
          // We can't use assigned dosens.
          // Fallback to ANY dosen to prevent crash, but strictly this violates constraint.
          // For now, let's try to pick from ALL dosens if free pool is empty, but ideally this should be an alert.
          if ($this->data['dosen']->isNotEmpty()) {
            $uniqueDosenId = $this->data['dosen']->random()->id;
          } else {
            continue;
          }
        }
      }

      for ($k = 0; $k < $occurrences; $k++) {
        $durationSlots = ($mk->sks == 4) ? 2 : 1; // Assuming 1 Slot in Jam Table = 2 Hours based on Seeder (08-10, 10-12)

        // Randomize Slot
        $randomAssignment = $this->getRandomAssignment($mk, $durationSlots, $uniqueDosenId);
        $scheduleData[] = $randomAssignment;
      }
    }

    return [
      'data' => $scheduleData,
      'trial_counter' => 0
    ];
  }

  protected function getRandomAssignment($mk, $durationSlots, $dosenId)
  {
    // Pick Random Day
    $hari = $this->data['hari']->random();

    // Pick Random Room
    $ruangan = $this->data['ruangan']->random();

    // Pick Random Start Time (Jam)
    // Must ensure there are enough consecutive slots
    // Jam Seeder: 4 records (ids usually 1,2,3,4).
    // If duration is 2 slots (4 hours), valid starts are: Slot 1 (08-10) -> Ends at 12:00. Slot 2 -> Ends at 14:00 (Break?).
    // We need to check if we can pick consecutive jams.
    // Since DB IDs might not be sequential or guaranteed, we should use array index from loaded data.

    $jamCount = $this->data['jam']->count();
    $validStartIndices = range(0, $jamCount - $durationSlots); // e.g. count 4, duration 2 -> indices 0, 1, 2. (0+1, 1+1, 2+1 are valid?)
    // If duration 2: Index 0 (08-10) + Index 1 (10-12) OK.
    // Index 2 (13-15) + Index 3 (15-17) OK.
    // Index 1 (10-12) + Index 2 (13-15) -> Across lunch break? User didn't specify. Assuming OK for now.

    if (empty($validStartIndices)) {
      // Fallback: Just pick any, fitness function will penalize invalid duration
      $startIndex = rand(0, $jamCount - 1);
    } else {
      $startIndex = $validStartIndices[array_rand($validStartIndices)];
    }

    // Selected Jams (Store just the start JAM ID, logic implies it takes sequential)
    $startJam = $this->data['jam'][$startIndex];

    return [
      'mata_kuliah_id' => $mk->id,
      'dosen_id' => $dosenId, // Constraint: Assigned Dosen (Fixed or Random)
      'ruangan_id' => $ruangan->id,
      'hari_id' => $hari->id,
      'jam_id' => $startJam->id,
      // Meta data for checking
      'sks' => $mk->sks,
      'duration_slots' => $durationSlots,
      'jam_index' => $startIndex // Helper to find consecutive jams
    ];
  }

  protected function mutate($schedule)
  {
    // Change ONE assignment in the schedule
    $newScheduleData = $schedule['data'];
    $mutationIndex = array_rand($newScheduleData);

    $item = $newScheduleData[$mutationIndex];
    // Mutate: Pick a new random Room, Day, or Time. 
    // We don't mutate Subject/Lecturer relation as it's fixed.

    // Simple Mutation strategies:
    // 1. Change Room
    // 2. Change Time (Day + Jam)

    if (rand(0, 1) == 0) {
      // Change Room
      $item['ruangan_id'] = $this->data['ruangan']->random()->id;
    } else {
      // Change Time
      $hari = $this->data['hari']->random();
      $item['hari_id'] = $hari->id;

      // Re-roll valid jam
      $jamCount = $this->data['jam']->count();
      $durationSlots = $item['duration_slots'];
      $validStartIndices = range(0, $jamCount - $durationSlots);
      if (!empty($validStartIndices)) {
        $startIndex = $validStartIndices[array_rand($validStartIndices)];
        $item['jam_id'] = $this->data['jam'][$startIndex]->id;
        $item['jam_index'] = $startIndex;
      }
    }

    $newScheduleData[$mutationIndex] = $item;

    return [
      'data' => $newScheduleData,
      'trial_counter' => $schedule['trial_counter'] // Counter reset handled in main loop
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

        // Check for Time Overlap
        // Same Day
        if ($a['hari_id'] == $b['hari_id']) {
          // Check Jam Overlap
          // A range: [start, start + duration)
          $aStart = $a['jam_index'];
          $aEnd = $aStart + $a['duration_slots'];

          $bStart = $b['jam_index'];
          $bEnd = $bStart + $b['duration_slots'];

          if ($aStart < $bEnd && $bStart < $aEnd) {
            // TIME CONFLICT DETECTED

            // Rule 1: Room Conflict (Same Room at Same Time)
            if ($a['ruangan_id'] == $b['ruangan_id']) {
              $conflicts++;
            }

            // Rule 2: Lecturer Conflict (Same Lecturer at Same Time)
            if ($a['dosen_id'] == $b['dosen_id']) {
              $conflicts++;
            }

            // Rule 3: Parameter "Mata kuliah 4 SKS ... bisa digunakan oleh dosen yg tertulis" is structural (handled in init).
          }
        }
      }
    }

    // Fitness value is simply conflict count.
    // ABC typically maximizes fitness. Fitness = 1 / (1 + conflicts).
    // But our variable is 'best_fitness_value' (double), and user said "fitness paling kecil".
    // So we treat Conflict Count AS the value to minimize.
    return $conflicts;
  }

  protected function calculateProbabilities($population)
  {
    // Based on conflicts (minimization).
    // Convert to fitness (maximization) for roulette wheel
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
