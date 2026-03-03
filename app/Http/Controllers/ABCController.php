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
    public function index()
    {
        $dosen = Dosen::where('status', 'Active')->get();
        $mataKuliah = MataKuliah::where('status', 'Active')->with('dosen')->get();
        $ruangan = Ruangan::where('status', 'Active')->get();
        $hari = Hari::where('status', 'Active')->get();
        $jam = Jam::where('status', 'Active')->get();

        $history = RiwayatPenjadwalan::latest()->take(5)->get();

        return view('pages.artificial_bee_colony.generate', compact('dosen', 'mataKuliah', 'ruangan', 'hari', 'jam', 'history'));
    }

    public function generate(Request $request)
    {
        set_time_limit(300); // 5 Minutes max execution time

        $request->validate([
            'judul' => 'required|string|max:255',
            'population' => 'required|integer|min:10|max:200',
            'max_cycles' => 'required|integer|min:100|max:10000',
            'semester' => 'required|string',
            'tahun_ajaran' => 'required|string',
            'durasi_4_sks' => 'required|integer|in:3,4', // Validasi input baru
        ]);

        // Capacity Check Pre-Validation
        $capacityCheck = $this->checkCapacity($request->semester, $request->durasi_4_sks);
        if (!$capacityCheck['success']) {
            return response()->json([
                'success' => false,
                'message' => $capacityCheck['message'],
                'details' => $capacityCheck['details']
            ], 422); // Unprocessable Entity
        }

        $algorithm = new ABCAlgorithm($request->population, $request->max_cycles, $request->semester, $request->durasi_4_sks);
        $result = $algorithm->run();

        // Simpan Riwayat
        $history = RiwayatPenjadwalan::create([
            'user_id' => Auth::id(),
            'judul' => $request->judul,
            'semester' => $request->semester,
            'tahun_ajaran' => $request->tahun_ajaran,
            'best_fitness_value' => $result['conflicts'], // Simpan MURNI jumlah konflik ke DB
            'jumlah_iterasi' => $request->max_cycles,
            'status' => 'Final', // Locked for now
        ]);

        // Simpan Detail Jadwal
        foreach ($result['schedule'] as $item) {
            JadwalKuliah::create([
                'riwayat_penjadwalan_id' => $history->id,
                'mata_kuliah_id' => $item['mata_kuliah_id'],
                'dosen_id' => $item['dosen_id'],
                'ruangan_id' => $item['ruangan_id'],
                'hari_id' => $item['hari_id'],
                'jam_id' => $item['jam_id'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil digenerate!',
            'fitness' => $result['conflicts'], // Kirim konflik murni ke Pop-up UI
            'history_id' => $history->id
        ]);
    }
    public function riwayat()
    {
        $history = RiwayatPenjadwalan::latest()->get();
        return view('pages.artificial_bee_colony.riwayat', compact('history'));
    }

    public function detail($id)
    {
        $history = RiwayatPenjadwalan::with(['jadwalKuliahs' => function ($query) {
            $query->orderBy('hari_id')->orderBy('jam_id');
        }, 'jadwalKuliahs.mataKuliah', 'jadwalKuliahs.dosen', 'jadwalKuliahs.ruangan', 'jadwalKuliahs.hari', 'jadwalKuliahs.jam'])
            ->findOrFail($id);

        // Deteksi Konflik untuk Highlight
        $items = $history->jadwalKuliahs;
        $conflictingIds = [];

        // Map Jam ID ke Index (Asumsi ID berurutan atau kita load semua jam)
        $allJams = Jam::where('status', 'Active')->orderBy('jam_mulai')->get()->pluck('id')->values()->toArray();

        foreach ($items as $keyA => $a) {
            foreach ($items as $keyB => $b) {
                if ($a->id == $b->id) continue;

                if ($a->hari_id == $b->hari_id) {
                    // Hitung Rentang Waktu A
                    $aDurasi = ($a->mataKuliah->sks == 4) ? 3 : (($a->mataKuliah->sks == 2) ? 2 : 1);
                    $aIndex = array_search($a->jam_id, $allJams);
                    if ($aIndex === false) continue;

                    // Hitung Rentang Waktu B
                    $bDurasi = ($b->mataKuliah->sks == 4) ? 3 : (($b->mataKuliah->sks == 2) ? 2 : 1);
                    $bIndex = array_search($b->jam_id, $allJams);
                    if ($bIndex === false) continue;

                    $aEnd = $aIndex + $aDurasi;
                    $bEnd = $bIndex + $bDurasi;

                    // Cek Irisan
                    if ($aIndex < $bEnd && $bIndex < $aEnd) {
                        // Cek Konflik Ruangan atau Dosen
                        if ($a->ruangan_id == $b->ruangan_id || $a->dosen_id == $b->dosen_id) {
                            $conflictingIds[] = $a->id;
                            // Tidak perlu break, biarkan loop menandai semua yang terlibat
                        }
                    }
                }
            }
        }
        $conflictingIds = array_unique($conflictingIds);

        // Detect Durations (Heuristic for 4 SKS)
        // 1. Map Usage (Penting: harus berurutan index jam_id)
        $jamIndices = $allJams; // Array ID jam berurutan
        $jamIdToIndex = array_flip($jamIndices);

        $usage = [];
        foreach ($history->jadwalKuliahs as $jadwal) {
            $h = $jadwal->hari_id;
            $r = $jadwal->ruangan_id;
            $j = $jadwal->jam_id;
            if (isset($jamIdToIndex[$j])) {
                $idx = $jamIdToIndex[$j];
                $usage[$h][$r][$idx] = $jadwal->id;
            }
        }

        // 2. Calculate Real Duration (Reverse-engineered from ABCAlgorithm SKS logic)
        $durations = [];
        $occurrencesMap = [];
        // Pre-count occurrences per mata_kuliah_id
        foreach ($history->jadwalKuliahs as $jadwal) {
            $occurrencesMap[$jadwal->mata_kuliah_id] = ($occurrencesMap[$jadwal->mata_kuliah_id] ?? 0) + 1;
        }

        foreach ($history->jadwalKuliahs as $jadwal) {
            $mk = $jadwal->mataKuliah;
            $totalIdealSlots = ($mk->sks_teori * 2) + ($mk->sks_praktek * 4);
            $occurrences = $occurrencesMap[$mk->id] ?? 1;

            $slotsPerSession = (int) floor($totalIdealSlots / $occurrences);
            $durations[$jadwal->id] = max(1, $slotsPerSession); // Minimum 1 slot
        }

        // 3. Calculate End Times untuk View (mempertimbangkan gap jam istirahat)
        $jams = Jam::where('status', 'Active')->orderBy('jam_mulai')->get();
        $jamList = $jams->values(); // Reset index to 0, 1, 2...
        $endTimes = [];

        foreach ($history->jadwalKuliahs as $jadwal) {
            $durationSlots = $durations[$jadwal->id] ?? 1;

            // Cari index dari jam_id ini
            $startIdx = $jamList->search(function ($jam) use ($jadwal) {
                return $jam->id == $jadwal->jam_id;
            });

            if ($startIdx !== false && isset($jamList[$startIdx + $durationSlots - 1])) {
                $endJam = $jamList[$startIdx + $durationSlots - 1]; // Jam terakhir dari slot ini
                $endTimes[$jadwal->id] = \Carbon\Carbon::parse($endJam->jam_selesai)->format('H:i');
            } else {
                // Fallback jika tidak ditemukan (seharusnya tidak terjadi)
                $endTimes[$jadwal->id] = \Carbon\Carbon::parse($jadwal->jam->jam_mulai)->addHours($durationSlots)->format('H:i');
            }
        }

        return view('pages.artificial_bee_colony.detail_riwayat', compact('history', 'conflictingIds', 'durations', 'endTimes'));
    }

    public function export($id)
    {
        $history = RiwayatPenjadwalan::with(['jadwalKuliahs.mataKuliah', 'jadwalKuliahs.dosen', 'jadwalKuliahs.ruangan', 'jadwalKuliahs.hari', 'jadwalKuliahs.jam'])
            ->findOrFail($id);

        $haris = Hari::orderBy('id')->where('status', 'Active')->get();
        $jams = Jam::orderBy('jam_mulai')->where('status', 'Active')->get();
        $ruangans = Ruangan::orderBy('nama_ruangan')->where('status', 'Active')->get();

        // 1. Inisialisasi Grid
        $grid = [];
        foreach ($haris as $h) {
            foreach ($jams as $j) {
                foreach ($ruangans as $r) {
                    $grid[$h->id][$j->id][$r->id] = null;
                }
            }
        }

        // 2. Petakan Jam ke Index untuk akses berurutan
        $jamIndices = $jams->pluck('id')->values()->toArray();
        $jamIdToIndex = array_flip($jamIndices);

        // 3. Pre-Calculate Usage Map (Heuristic Pass)
        $usageMap = [];
        foreach ($history->jadwalKuliahs as $jadwal) {
            $hId = $jadwal->hari_id;
            $rId = $jadwal->ruangan_id;
            $jId = $jadwal->jam_id;

            if (isset($jamIdToIndex[$jId])) {
                $idx = $jamIdToIndex[$jId];
                $usageMap[$hId][$rId][$idx] = $jadwal->id;
            }
        }

        // 4. Hitung Durasi (Reverse-engineered from ABCAlgorithm SKS logic)
        $durations = [];
        $occurrencesMap = [];
        // Pre-count occurrences per mata_kuliah_id
        foreach ($history->jadwalKuliahs as $jadwal) {
            $occurrencesMap[$jadwal->mata_kuliah_id] = ($occurrencesMap[$jadwal->mata_kuliah_id] ?? 0) + 1;
        }

        foreach ($history->jadwalKuliahs as $jadwal) {
            $mk = $jadwal->mataKuliah;
            $totalIdealSlots = ($mk->sks_teori * 2) + ($mk->sks_praktek * 4);
            $occurrences = $occurrencesMap[$mk->id] ?? 1;

            $slotsPerSession = (int) floor($totalIdealSlots / $occurrences);
            $durations[$jadwal->id] = max(1, $slotsPerSession); // Minimum 1 slot
        }

        // 5. Isi Grid (Final Pass)
        foreach ($history->jadwalKuliahs as $jadwal) {
            $hId = $jadwal->hari_id;
            $jId = $jadwal->jam_id;
            $rId = $jadwal->ruangan_id;

            if (isset($grid[$hId][$jId][$rId]) && $grid[$hId][$jId][$rId] === 'SKIP') {
                continue;
            }

            $grid[$hId][$jId][$rId] = $jadwal;
            $durationSlots = $durations[$jadwal->id] ?? 2;

            // Mark upcoming slots as SKIP
            if ($durationSlots > 1) {
                $currentIndex = array_search($jId, $jamIndices);
                if ($currentIndex !== false) {
                    for ($k = 1; $k < $durationSlots; $k++) {
                        if (isset($jamIndices[$currentIndex + $k])) {
                            $nextJamId = $jamIndices[$currentIndex + $k];
                            // Hanya mark jika kosong
                            if (!isset($grid[$hId][$nextJamId][$rId])) {
                                $grid[$hId][$nextJamId][$rId] = 'SKIP';
                            }
                        }
                    }
                }
            }
        }

        $data = [
            'history' => $history,
            'haris' => $haris,
            'jams' => $jams,
            'ruangans' => $ruangans,
            'grid' => $grid,
            'durations' => $durations // Pass this
        ];

        $rawFilename = sprintf('Jadwal %s semester %s %s.xlsx', $history->judul, $history->semester, $history->tahun_ajaran);
        $filename = str_replace(['/', '\\'], '-', $rawFilename);
        return Excel::download(new JadwalExport($data, $history->judul), $filename);
    }

    private function checkCapacity($semesterType, $durasi4Sks)
    {
        // 1. Calculate Supply
        $activeRooms = Ruangan::where('status', 'Active')->count();
        $activeDays = Hari::where('status', 'Active')->count();
        $activeJams = Jam::where('status', 'Active')->count();

        $totalCapacitySlots = $activeRooms * $activeDays * $activeJams;

        if ($activeRooms == 0 || $activeDays == 0 || $activeJams == 0) {
            return [
                'success' => false,
                'message' => 'Data Master Tidak Lengkap!',
                'details' => 'Pastikan ada minimal 1 Ruangan, 1 Hari, dan 1 Jam yang aktif.'
            ];
        }

        // 2. Calculate Demand
        $mataKuliahs = MataKuliah::where('status', 'Active')
            ->get()
            ->filter(function ($mk) use ($semesterType) {
                $sem = (int) $mk->semester;
                // Semester Ganjil: 1, 3, 5, 7 ... (Ganjil)
                // Semester Genap: 2, 4, 6, 8 ... (Genap)
                return $semesterType === 'Ganjil' ? ($sem % 2 != 0) : ($sem % 2 == 0);
            });

        if ($mataKuliahs->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Data Mata Kuliah Kosong!',
                'details' => 'Tidak ada mata kuliah aktif untuk semester ' . $semesterType . ' ini.'
            ];
        }

        $totalSlotsNeeded = 0;

        foreach ($mataKuliahs as $mk) {
            $durasiTeoriSlots = $mk->sks_teori * 2;

            $durasiPraktekSlots = 0;
            $occurrencesPraktek = 1;

            if ($mk->sks_praktek > 0) {
                $defaultPraktekJam = $mk->sks_praktek * 2; // Default praktek jam
                $maxJamPraktek = (float) $durasi4Sks;

                if ($mk->sks_teori > 0 && $mk->sks_praktek > 0) {
                    $totalJamPraktek = $defaultPraktekJam;
                    $occurrencesPraktek = 1;
                } else {
                    if ($maxJamPraktek > 0) {
                        $occurrencesPraktek = 2;
                        $totalJamPraktek = $maxJamPraktek * $occurrencesPraktek;
                    } else {
                        $totalJamPraktek = $defaultPraktekJam;
                        $occurrencesPraktek = ($totalJamPraktek > 4) ? 2 : 1;
                    }
                }

                $durasiPraktekSlots = ($totalJamPraktek / $occurrencesPraktek) * 2; // Jam -> Slot
            }

            $totalDurationSlots = $durasiTeoriSlots + $durasiPraktekSlots;

            // Total sum of slots taken inside the schedule grid
            $totalSlotsNeeded += ($occurrencesPraktek * $totalDurationSlots);
        }

        if ($totalSlotsNeeded > $totalCapacitySlots) {
            return [
                'success' => false,
                'message' => "Kapasitas ruangan tidak mencukupi!",
                'details' => "Dibutuhkan {$totalSlotsNeeded} slot, tetapi hanya tersedia {$totalCapacitySlots} slot. Kurang: " . ($totalSlotsNeeded - $totalCapacitySlots) . " slot."
            ];
        }

        return ['success' => true];
    }

    public function destroy($id)
    {
        try {
            $riwayat = RiwayatPenjadwalan::findOrFail($id);
            $riwayat->delete();
            return response()->json(['message' => 'Riwayat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus riwayat', 'error' => $e->getMessage()], 500);
        }
    }
}
