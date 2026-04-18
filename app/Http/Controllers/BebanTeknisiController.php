<?php

namespace App\Http\Controllers;

use App\Models\RiwayatPenjadwalan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BebanTeknisiController extends Controller
{
    public function index()
    {
        $riwayats = RiwayatPenjadwalan::latest('created_at')->get();
        return view('pages.dashboard.bobot_teknisi', compact('riwayats'));
    }
    public function count(Request $request)
    {
        $riwayatId = $request->get('riwayat_id');

        if (empty($riwayatId)) {
            return response()->json([]);
        }

        // Ambil data jadwal khusus untuk teknisi di riwayat terpilih
        $jadwalTeknisiMk = DB::table('jadwal_kuliah')
            ->join('mata_kuliah', 'jadwal_kuliah.mata_kuliah_id', '=', 'mata_kuliah.id')
            ->select('jadwal_kuliah.teknisi_id', 'mata_kuliah.nama_matkul', 'mata_kuliah.sks_teori', 'mata_kuliah.sks_praktek', 'mata_kuliah.kelas')
            ->where('jadwal_kuliah.riwayat_penjadwalan_id', $riwayatId)
            ->whereNotNull('jadwal_kuliah.teknisi_id')
            ->distinct()
            ->get();

        // Inisialisasi struktur data per Teknisi
        $teknisiStats = [];
        $teknisis = DB::table('teknisi')->select('id', 'nama', 'status')->get();

        foreach ($teknisis as $t) {
            $teknisiStats[$t->id] = [
                'id' => $t->id,
                'nama' => $t->nama,
                'status' => $t->status,
                'beban_sks' => 0
            ];
        }

        // Kalkulasi SKS Mata Kuliah × 1 (karena tiap baris mata kuliah bagi teknisi dihitung sebagai 1 kelas yang dia pegang)
        foreach ($jadwalTeknisiMk as $item) {
            $teknisiId = $item->teknisi_id;
            
            if (isset($teknisiStats[$teknisiId])) {
                $sksTotal = $item->sks_teori + $item->sks_praktek;
                $teknisiStats[$teknisiId]['beban_sks'] += $sksTotal;
            }
        }

        $resultList = array_values($teknisiStats);

        // Sorting dari beban terbanyak ke tersedikit
        usort($resultList, function($a, $b) {
            return $b['beban_sks'] <=> $a['beban_sks'];
        });

        return response()->json($resultList);
    }
}
