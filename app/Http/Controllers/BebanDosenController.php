<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BebanDosenController extends Controller
{

    public function index()
    {
        return view('pages.dashboard.bobot_dosen');
    }

    public function data(Request $request)
    {
        $filterSemester = $request->get('semester');

        $condition = "1=1"; // default semua

        if ($filterSemester == 'ganjil') {
            $condition = "mk.semester IN (1,3,5)";
        } elseif ($filterSemester == 'genap') {
            $condition = "mk.semester IN (2,4,6)";
        }

        $dosens = DB::table('dosen')
            ->leftJoin('mata_kuliah as mk', 'dosen.id', '=', 'mk.dosen_id')
            ->select(
                'dosen.id',
                'dosen.nama_dosen',
                'dosen.jenis_dosen',
                'dosen.status',
                DB::raw("
                COALESCE(SUM(
                    CASE 
                        WHEN $condition 
                        THEN (mk.sks_teori + mk.sks_praktek)
                        ELSE 0
                    END
                ), 0) as bobot
            ")
            )
            ->groupBy('dosen.id', 'dosen.nama_dosen', 'dosen.jenis_dosen', 'dosen.status')
            ->get();

        return response()->json($dosens);
    }
}
