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
        ]);

        $algorithm = new ABCAlgorithm($request->population, $request->max_cycles, $request->semester);
        $result = $algorithm->run();

        // Simpan Riwayat
        $history = RiwayatPenjadwalan::create([
            'user_id' => Auth::id(),
            'judul' => $request->judul,
            'semester' => $request->semester,
            'tahun_ajaran' => $request->tahun_ajaran,
            'best_fitness_value' => $result['fitness'],
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
            'fitness' => $result['fitness'],
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

        return view('pages.artificial_bee_colony.detail_riwayat', compact('history', 'conflictingIds'));
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

        // 3. Isi Grid
        foreach ($history->jadwalKuliahs as $jadwal) {
            $hId = $jadwal->hari_id;
            $jId = $jadwal->jam_id;
            $rId = $jadwal->ruangan_id;

            // Cek apakah slot ini sudah ditandai SKIP (ditempati oleh 4 SKS sebelumnya)
            if (isset($grid[$hId][$jId][$rId]) && $grid[$hId][$jId][$rId] === 'SKIP') {
                continue;
            }

            $grid[$hId][$jId][$rId] = $jadwal;

            // Tangani 4 SKS
            if ($jadwal->mataKuliah->sks == 4) {
                // Cari index jam saat ini
                $currentIndex = array_search($jId, $jamIndices);
                if ($currentIndex !== false && isset($jamIndices[$currentIndex + 1])) {
                    $nextJamId = $jamIndices[$currentIndex + 1];
                    $grid[$hId][$nextJamId][$rId] = 'SKIP';
                }
            }
        }

        $data = [
            'history' => $history,
            'haris' => $haris,
            'jams' => $jams,
            'ruangans' => $ruangans,
            'grid' => $grid
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\JadwalExport($data, $history->judul), 'Jadwal-' . $history->id . '.xlsx');
    }
}
