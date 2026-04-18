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

    /*
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
*/

    public function count(Request $request)
    {
        $riwayatId = $request->get('riwayat_id');

        if (empty($riwayatId)) {
            return response()->json([]);
        }

        // Ambil semua data jadwal mata kuliah unik yang diajar masing-masing dosen
        $jadwalDosenMk = DB::table('jadwal_kuliah')
            ->join('mata_kuliah', 'jadwal_kuliah.mata_kuliah_id', '=', 'mata_kuliah.id')
            ->select('jadwal_kuliah.dosen_id', 'mata_kuliah.nama_matkul', 'mata_kuliah.sks_teori', 'mata_kuliah.sks_praktek', 'mata_kuliah.kelas')
            ->where('jadwal_kuliah.riwayat_penjadwalan_id', $riwayatId)
            ->distinct()
            ->get();

        // Inisialisasi struktur data per Dosen
        $dosenStats = [];
        $dosens = DB::table('dosen')->select('id', 'nama_dosen', 'jenis_dosen', 'status')->get();

        foreach ($dosens as $d) {
            $dosenStats[$d->id] = [
                'id' => $d->id,
                'nama_dosen' => $d->nama_dosen,
                'jenis_dosen' => $d->jenis_dosen,
                'status' => $d->status,
                'bobot' => 0, // Fallback placeholder (agar logic sortBy UI tak rusak)
                'sks_mk_string' => '-', // Hasil akhir dari unique Teori & Praktek: misal "4 (4/0)"
                'sks_mata_kuliah_teori_total' => 0,
                'sks_mata_kuliah_praktek_total' => 0,
                'sks_ajar_string' => '-', // Format u/ SKS Ajar
                'sks_ajar_teori_total' => 0,
                'sks_ajar_praktek_total' => 0,
                '_base_mks' => [] // array tracker unique base nama mata kuliah
            ];
        }

        // Pre-compute Agregat Base Mata Kuliah untuk rumus Beban SKS
        $agregatBaseMk = [];
        foreach ($jadwalDosenMk as $item) {
            $baseName = trim(preg_replace('/\s+[A-Z]$/', '', $item->nama_matkul));
            $kelasStr = str_replace(['(', ')', ' '], '', $item->kelas);
            $kelasCount = 1;
            if (!empty($kelasStr)) {
                $kelasArray = explode(',', $kelasStr);
                $kelasCount = count(array_filter($kelasArray, fn($k) => trim($k) !== ''));
            }

            if (!isset($agregatBaseMk[$baseName])) {
                $agregatBaseMk[$baseName] = [
                    'sks_teori' => $item->sks_teori,
                    'sks_praktek' => $item->sks_praktek,
                    'total_kelas' => 0,
                    'dosens' => [] // daftar dosen_id -> jumlah kelas yg diajarnya untuk base MK ini
                ];
            }

            $agregatBaseMk[$baseName]['total_kelas'] += $kelasCount;
            if (!isset($agregatBaseMk[$baseName]['dosens'][$item->dosen_id])) {
                $agregatBaseMk[$baseName]['dosens'][$item->dosen_id] = 0;
            }
            $agregatBaseMk[$baseName]['dosens'][$item->dosen_id] += $kelasCount;
        }

        // Hitung Count unik untuk "SKS Mata Kuliah" dan "SKS Ajar"
        foreach ($jadwalDosenMk as $item) {
            $baseName = trim(preg_replace('/\s+[A-Z]$/', '', $item->nama_matkul));
            $dosenId = $item->dosen_id;

            $kelasStr = str_replace(['(', ')', ' '], '', $item->kelas);
            $kelasCount = 1;
            if (!empty($kelasStr)) {
                $kelasArray = explode(',', $kelasStr);
                $kelasCount = count(array_filter($kelasArray, fn($k) => trim($k) !== ''));
            }

            if (isset($dosenStats[$dosenId])) {
                // Jika base MK ini belum dihitung untuk si dosen, tambahkan SKS nya (Bagian: SKS MATA KULIAH)
                if (!isset($dosenStats[$dosenId]['_base_mks'][$baseName])) {
                    $dosenStats[$dosenId]['_base_mks'][$baseName] = true;
                    $dosenStats[$dosenId]['sks_mata_kuliah_teori_total'] += $item->sks_teori;
                    $dosenStats[$dosenId]['sks_mata_kuliah_praktek_total'] += $item->sks_praktek;
                }

                // Kalikan SKS dengan jumlah kelas yg diajarkan oleh dosen dlm 1 id MK (Bagian: SKS AJAR)
                $dosenStats[$dosenId]['sks_ajar_teori_total'] += ($item->sks_teori * $kelasCount);
                $dosenStats[$dosenId]['sks_ajar_praktek_total'] += ($item->sks_praktek * $kelasCount);
            }
        }

        // Hitung Beban SKS dari agregat dan format hasil akhir secara bersamaan guna menghindari pass-by-reference $stat variable shadow
        $resultList = [];
        foreach ($dosenStats as $id => $stat) {
            $totalBebanSks = 0;

            // Cari base MK apa saja yang diajar oleh dosen ini
            foreach ($agregatBaseMk as $baseName => $agg) {
                if (isset($agg['dosens'][$id])) {
                    $jumlahKelasDiajar = $agg['dosens'][$id];
                    $totalKelas = $agg['total_kelas'];
                    $jumlahSks = $agg['sks_teori'] + $agg['sks_praktek'];
                    $jumlahDosenMengajar = count($agg['dosens']);

                    // Rumus Asli User:
                    // jumlah kelas yang diajar / total jumlah kelas X (Jumlah SKS x Jumlah Kelas / jumlah dosen yang mengajar kelas)
                    if ($totalKelas > 0 && $jumlahDosenMengajar > 0) {
                        $bagianDepan = $jumlahKelasDiajar / $totalKelas;
                        $bagianBelakang = ($jumlahSks * $totalKelas) / $jumlahDosenMengajar;

                        $bebanSksMk = $bagianDepan * $bagianBelakang;
                        $totalBebanSks += $bebanSksMk;
                    }
                }
            }

            $stat['beban_sks'] = round($totalBebanSks);

            $t = $stat['sks_mata_kuliah_teori_total'];
            $p = $stat['sks_mata_kuliah_praktek_total'];
            $tot = $t + $p;

            if ($tot > 0) {
                // Format sesuai permintaan user: "Total(T/P)"
                $stat['sks_mk_string'] = "{$tot} ({$t}/{$p})";
            }

            $tAjar = $stat['sks_ajar_teori_total'];
            $pAjar = $stat['sks_ajar_praktek_total'];
            $totAjar = $tAjar + $pAjar;
            if ($totAjar > 0) {
                $stat['sks_ajar_string'] = "{$totAjar} ({$tAjar}/{$pAjar})";
            }

            // Dummy bobot agar urutan sorting berfungsi menggunakan nilai SKS Ajar 
            $stat['bobot'] = $totAjar;

            unset($stat['_base_mks']);
            unset($stat['sks_mata_kuliah_teori_total']);
            unset($stat['sks_mata_kuliah_praktek_total']);
            unset($stat['sks_ajar_teori_total']);
            unset($stat['sks_ajar_praktek_total']);

            $resultList[] = $stat;
        }

        // Sort Data berdasarkan beban total
        usort($resultList, function ($a, $b) {
            return $b['bobot'] <=> $a['bobot'];
        });

        return response()->json($resultList);
    }
}
