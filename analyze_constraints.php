<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MataKuliah;
use App\Models\Dosen;

echo "\n--- ANALISA BEBAN DOSEN & SLOT ---\n";

// 1. Ambil Data Semester Ganjil
$mataKuliahs = MataKuliah::where('status', 'Active')
  ->get()
  ->filter(function ($mk) {
    return $mk->semester % 2 != 0;
  });

// 2. Hitung Beban per Dosen
$dosenLoad = [];
$totalSlotsRequired = 0;

foreach ($mataKuliahs as $mk) {
  $sks = $mk->sks;
  $slots = ($sks == 4) ? 3 : (($sks == 2) ? 2 : 1);
  $dosenName = $mk->dosen ? $mk->dosen->nama_dosen : 'No Dosen (Random)';

  if (!isset($dosenLoad[$dosenName])) {
    $dosenLoad[$dosenName] = [
      'total_slots' => 0,
      'courses' => []
    ];
  }

  $dosenLoad[$dosenName]['total_slots'] += $slots;
  $dosenLoad[$dosenName]['courses'][] = "{$mk->nama_matkul} ({$sks} SKS, {$slots} Slots)";
  $totalSlotsRequired += $slots;
}

// 3. Tampilkan Beban Dosen
echo "\n[BEBAN DOSEN]\n";
echo "Max Slots Available per Dosen (1 Room Assumption): 8 slots/day * 5 days = 40 slots (Theoretical)\n";
echo "Realistic Max (Human): ~20 slots/week?\n\n";

foreach ($dosenLoad as $name => $data) {
  echo "Dosen: {$name}\n";
  echo "Total Load: " . $data['total_slots'] . " Slots (approx " . $data['total_slots'] . " Hours)\n";
  foreach ($data['courses'] as $course) {
    echo "  - $course\n";
  }
  echo "--------------------------\n";
}

// 4. Analisa Rigidity (Kekakuan)
$count4SKS = $mataKuliahs->where('sks', '4')->count();
$count2SKS = $mataKuliahs->where('sks', '2')->count();

echo "\n[ANALISA KEKAKUAN SLOT]\n";
echo "Total Mata Kuliah 4 SKS: $count4SKS\n";
echo "  -> Butuh 3 Jam Block (Morning / Afternoon Pure)\n";
echo "  -> Start Options: 08:00, 09:00, 13:00, 14:00 (Hanya 4 opsi per Hari per Ruangan)\n";
echo "  -> Total Slot '3-Jam' Tersedia per Minggu: 4 Ruangan * 5 Hari * 2 Sesi = 40 Slot Besar.\n";
echo "  -> Permintaan: $count4SKS Slot Besar.\n";

if ($count4SKS > 40) {
  echo "  -> [CRITICAL] Permintaan melebih kapasitas slot besar!\n";
} else {
  echo "  -> [SAFE] Kapasitas Slot Besar cukup ($count4SKS < 40).\n";
}

echo "\nTotal Mata Kuliah 2 SKS: $count2SKS\n";
