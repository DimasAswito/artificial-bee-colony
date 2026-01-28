<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\RiwayatPenjadwalan;
use App\Models\JadwalKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Metric Cards
        $totalDosen = Dosen::where('status', 'Active')->count();
        $totalMataKuliah = MataKuliah::where('status', 'Active')->count();
        $totalRuangan = Ruangan::where('status', 'Active')->count();
        $totalRiwayat = RiwayatPenjadwalan::count();

        // 2. Recent History (Table - 5 Terakhir)
        $recentHistory = RiwayatPenjadwalan::latest()->take(5)->get();

        // 3. Chart 1: Jumlah Kuliah per HARI (Hasil Generate Terbaru)
        // Ambil riwayat TERAKHIR
        $lastHistory = RiwayatPenjadwalan::latest()->first();

        $chartHariLabels = [];
        $chartHariData = [];

        if ($lastHistory) {
            $hariStats = JadwalKuliah::where('riwayat_penjadwalan_id', $lastHistory->id)
                ->join('hari', 'jadwal_kuliah.hari_id', '=', 'hari.id')
                ->select('hari.nama_hari', DB::raw('count(*) as total'))
                ->groupBy('hari.nama_hari', 'hari.id')
                ->orderBy('hari.id')
                ->get();

            foreach ($hariStats as $stat) {
                $chartHariLabels[] = $stat->nama_hari;
                $chartHariData[] = $stat->total;
            }
        }

        // 4. Chart 2: Donut Chart (Semester Ganjil vs Genap - Active MK)
        $activeMK = MataKuliah::where('status', 'Active')->get();
        // Assuming Semester Ganjil = 1, 3, 5, 7... (Odd)
        // Assuming Semester Genap = 2, 4, 6, 8... (Even)
        $totalGanjil = $activeMK->filter(function ($mk) {
            return $mk->semester % 2 != 0;
        })->count();
        $totalGenap = $activeMK->filter(function ($mk) {
            return $mk->semester % 2 == 0;
        })->count();

        $chartSemesterLabels = ['Semester Ganjil', 'Semester Genap'];
        $chartSemesterData = [$totalGanjil, $totalGenap];

        return view('pages.dashboard.ecommerce', compact(
            'totalDosen',
            'totalMataKuliah',
            'totalRuangan',
            'totalRiwayat',
            'recentHistory',
            'chartHariLabels',
            'chartHariData',
            'chartSemesterLabels',
            'chartSemesterData'
        ));
    }
}
