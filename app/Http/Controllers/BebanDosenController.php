<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\RiwayatPenjadwalan;

class BebanDosenController extends Controller
{

    public function index()
    {
        $riwayats = RiwayatPenjadwalan::latest()->get();
        return view('pages.dashboard.bobot_dosen', compact('riwayats'));
    }

    public function data(Request $request)
    {
        $riwayatId = $request->get('riwayat_id');
        
        if (empty($riwayatId)) {
            return response()->json([]);
        }

        $dosens = DB::table('dosen')
            ->leftJoinSub(
                DB::table('jadwal_kuliah')
                    ->select('dosen_id', 'mata_kuliah_id')
                    ->where('riwayat_penjadwalan_id', $riwayatId)
                    ->distinct(),
                'distinct_jk',
                function ($join) {
                    $join->on('dosen.id', '=', 'distinct_jk.dosen_id');
                }
            )
            ->leftJoin('mata_kuliah as mk', 'distinct_jk.mata_kuliah_id', '=', 'mk.id')
            ->select(
                'dosen.id',
                'dosen.nama_dosen',
                'dosen.jenis_dosen',
                'dosen.status',
                DB::raw("
                COALESCE(SUM(mk.sks_teori + mk.sks_praktek), 0) as bobot
            ")
            )
            ->groupBy('dosen.id', 'dosen.nama_dosen', 'dosen.jenis_dosen', 'dosen.status')
            ->orderBy('bobot', 'desc')
            ->get();

        return response()->json($dosens);
    }
}
