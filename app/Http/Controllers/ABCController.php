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
        $request->validate([
            'judul' => 'required|string|max:255',
            'population' => 'required|integer|min:10|max:200',
            'max_cycles' => 'required|integer|min:100|max:10000',
            'semester' => 'required|string',
            'tahun_ajaran' => 'required|string',
        ]);

        $algorithm = new ABCAlgorithm($request->population, $request->max_cycles, $request->semester);
        $result = $algorithm->run();

        // Save History
        $history = RiwayatPenjadwalan::create([
            'user_id' => Auth::id(),
            'judul' => $request->judul,
            'semester' => $request->semester,
            'tahun_ajaran' => $request->tahun_ajaran,
            'best_fitness_value' => $result['fitness'],
            'jumlah_iterasi' => $request->max_cycles,
            'status' => 'Final', // Locked for now
        ]);

        // Save Schedule Details
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

        return view('pages.artificial_bee_colony.detail_riwayat', compact('history'));
    }
}
