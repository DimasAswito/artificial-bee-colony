<?php

use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Hari;
use App\Models\Jam;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$semesterType = 'Ganjil';

// 1. Calculate Supply
$activeRooms = Ruangan::where('status', 'Active')->count();
$activeDays = Hari::where('status', 'Active')->count();
$activeJams = Jam::where('status', 'Active')->count();

$totalCapacitySlots = $activeRooms * $activeDays * $activeJams;

echo "--- SUPPLY ---\n";
echo "Runs: $activeRooms\n";
echo "Days: $activeDays\n";
echo "Jams/Day: $activeJams\n";
echo "Total Capacity (Slot-Rooms): $totalCapacitySlots\n\n";

// 2. Calculate Demand
$mataKuliahs = MataKuliah::where('status', 'Active')
  ->get()
  ->filter(function ($mk) use ($semesterType) {
    $sem = (int) $mk->semester;
    return $semesterType === 'Ganjil' ? ($sem % 2 != 0) : ($sem % 2 == 0);
  })
  ->sortBy(['semester', 'nama_matkul']);

$totalSlotsNeeded = 0;
$details = [];

foreach ($mataKuliahs as $mk) {
  // Logic from ABCAlgorithm
  // 4 SKS -> 2 Occurrences, each takes 2 slots = 4 slots total?
  // OR 2 Occurrences * 1 slot?
  // Wait, code says:
  // $durationSlots = ($mk->sks == 4) ? 2 : 1; 
  // $occurrences = ($mk->sks == 4) ? 2 : 1;
  // Total slots = occurrences * durationSlots

  // If SKS 4: 2 * 2 = 4 slots occupied in a room.
  // If SKS 2: 1 * 1 = 1 slot occupied.

  $occurrences = ($mk->sks == 4) ? 2 : 1;
  $duration = ($mk->sks == 4) ? 2 : 1;

  $slotsForThis = $occurrences * $duration;
  $totalSlotsNeeded += $slotsForThis;

  $details[] = "{$mk->nama_matkul} - Sem {$mk->semester} (SKS {$mk->sks}): $slotsForThis slots ($occurrences x $duration)";
}

echo "--- DEMAND ---\n";
echo "--- DEMAND BREAKDOWN ---\n";
foreach ($details as $line) {
  echo $line . "\n";
}

echo "\nTotal Courses (Ganjil): " . $mataKuliahs->count() . "\n";
echo "Total Slots Required: $totalSlotsNeeded\n";

$diff = $totalCapacitySlots - $totalSlotsNeeded;
echo "Surplus/Deficit: $diff\n";

if ($diff < 0) {
  echo "CRITICAL: Demand exceeds capacity! Conflicts are inevitable.\n";
} else {
  echo "Capacity is sufficient (theoretically).\n";
}
