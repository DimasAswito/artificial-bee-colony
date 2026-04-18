<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$abc = new \App\Services\ABCAlgorithm(50, 100, 'Ganjil', 3);
$result = $abc->run();

$teknisiStats = [];
$teknisis = \Illuminate\Support\Facades\DB::table('teknisi')->select('id', 'nama', 'status')->get();

foreach ($teknisis as $t) {
    $teknisiStats[$t->id] = 0;
}

foreach ($result['schedule'] as $item) {
    if (!empty($item['teknisi_id'])) {
        $mk = \App\Models\MataKuliah::find($item['mata_kuliah_id']);
        $teknisiStats[$item['teknisi_id']] += ($mk->sks_teori + $mk->sks_praktek);
    }
}

foreach($teknisiStats as $tId => $sks) {
    echo "Teknisi $tId -> $sks SKS\n";
}
