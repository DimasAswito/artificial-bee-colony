<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Hari;
use App\Models\Jam;
use App\Models\Log;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InputDataController extends Controller
{
  /**
   * Helper to log activity
   */
  private function logActivity($type, $description)
  {
    // Ensure user is authenticated, otherwise logging might fail due to null user_id
    // For testing purposes, you might want to ensure a user is logged in or handle this gracefully.
    $userId = Auth::id() ?? 1; // Fallback to ID 1 if no user logged in (for dev/testing convenience)

    Log::create([
      'user_id' => $userId,
      'type' => $type,
      'description' => $description,
    ]);
  }

  public function getLogs(Request $request)
  {
    $query = Log::with('user')->orderBy('created_at', 'desc');

    if ($request->has('type')) {
      $query->where('type', $request->type);
    }

    // Fetch latest 10 logs with user info
    $logs = $query->take(10)->get();

    // Map to format expected by frontend
    $formattedLogs = $logs->map(function ($log) {
      return [
        'user' => $log->user ? $log->user->name : 'System',
        'action' => $log->description,
        'time' => \Carbon\Carbon::parse($log->created_at)->format('Y-M-d H:i:s'),
      ];
    });

    return response()->json($formattedLogs);
  }

  // --- DOSEN ---
  public function indexDosen()
  {
    return view('pages.master-data.dosen');
  }

  public function getDosenData()
  {
    $dosen = Dosen::orderBy('nama_dosen', 'asc')->get();
    return response()->json($dosen);
  }


  public function storeDosen(Request $request)
  {
    $request->validate([
      'nama_dosen' => 'required|string|max:255',
      'nip' => 'nullable|string|max:20',
      'email' => 'nullable|email|max:255',
      'status' => 'nullable|in:Active,Inactive',
    ]);

    $dosen = Dosen::create($request->all());
    $this->logActivity('Data Dosen', 'Menambah Data Dosen : ' . $dosen->nama_dosen);
    return response()->json(['message' => 'Dosen created successfully', 'data' => $dosen], 201);
  }

  public function showDosen($id)
  {
    return response()->json(Dosen::findOrFail($id));
  }

  public function updateDosen(Request $request, $id)
  {
    $request->validate([
      'nama_dosen' => 'required|string|max:255',
      'nip' => 'nullable|string|max:20',
      'email' => 'nullable|email|max:255',
      'status' => 'nullable|in:Active,Inactive',
    ]);

    $dosen = Dosen::findOrFail($id);
    $dosen->update($request->all());
    $this->logActivity('Data Dosen', 'Mengubah data Dosen : ' . $dosen->nama_dosen);
    return response()->json(['message' => 'Dosen updated successfully', 'data' => $dosen]);
  }

  public function destroyDosen($id)
  {
    $dosen = Dosen::findOrFail($id);
    $namaDosen = $dosen->nama_dosen;
    $dosen->delete();
    $this->logActivity('Data Dosen', 'Menghapus Data Dosen : ' . $namaDosen);
    return response()->json(['message' => 'Dosen deleted successfully']);
  }

  // --- HARI ---
  public function indexHari()
  {
    return view('pages.master-data.hari');
  }

  public function getHariData()
  {
    $hari = Hari::orderBy('id', 'asc')->get();

    $formattedData = $hari->map(function ($h) {
      return [
        'id' => $h->id,
        'name' => $h->nama_hari,
        'status' => $h->status
      ];
    });

    return response()->json($formattedData);
  }

  public function storeHari(Request $request)
  {
    $request->validate(['nama_hari' => 'required|string|max:255']);
    $hari = Hari::create($request->all());
    $this->logActivity('Data Hari', 'Menambah Data Hari : ' . $hari->nama_hari);
    return response()->json(['message' => 'Hari created successfully', 'data' => $hari], 201);
  }

  public function showHari($id)
  {
    return response()->json(Hari::findOrFail($id));
  }

  public function updateHari(Request $request, $id)
  {
    $request->validate([
      'nama_hari' => 'required|string|max:255',
      'status' => 'nullable|in:Active,Inactive'
    ]);
    $hari = Hari::findOrFail($id);
    $hari->update($request->all());
    $this->logActivity('Data Hari', 'Mengubah data Hari : ' . $hari->nama_hari . ' menjadi ' . ($hari->status ?? 'Active'));
    return response()->json(['message' => 'Hari updated successfully', 'data' => $hari]);
  }

  public function destroyHari($id)
  {
    $hari = Hari::findOrFail($id);
    $namaHari = $hari->nama_hari;
    $hari->delete();
    $this->logActivity('Data Hari', 'Menghapus Data Hari : ' . $namaHari);
    return response()->json(['message' => 'Hari deleted successfully']);
  }

  // --- JAM ---
  public function indexJam()
  {
    return view('pages.master-data.jam');
  }

  public function getJamData()
  {
    $jam = Jam::orderBy('jam_mulai', 'asc')->get();

    $formattedData = $jam->map(function ($j) {
      return [
        'id' => $j->id,
        'start' => \Carbon\Carbon::parse($j->jam_mulai)->format('H:i'),
        'end' => \Carbon\Carbon::parse($j->jam_selesai)->format('H:i'),
        'status' => $j->status
      ];
    });

    return response()->json($formattedData);
  }

  public function storeJam(Request $request)
  {
    $request->validate([
      'jam_mulai' => 'required',
      'jam_selesai' => 'required',
      'status' => 'nullable|in:Active,Inactive'
    ]);

    $data = $request->all();
    $data['status'] = $data['status'] ?? 'Active';

    $jam = Jam::create($data);
    $this->logActivity('Data Jam', 'Menambah Data Jam : ' . $jam->jam_mulai . ' - ' . $jam->jam_selesai);
    return response()->json(['message' => 'Jam created successfully', 'data' => $jam], 201);
  }

  public function showJam($id)
  {
    return response()->json(Jam::findOrFail($id));
  }

  public function updateJam(Request $request, $id)
  {
    $request->validate([
      'jam_mulai' => 'required',
      'jam_selesai' => 'required',
      'status' => 'nullable|in:Active,Inactive'
    ]);
    $jam = Jam::findOrFail($id);
    $jam->update($request->all());
    $this->logActivity('Data Jam', 'Mengubah data Jam : ' . $jam->jam_mulai . ' - ' . $jam->jam_selesai);
    return response()->json(['message' => 'Jam updated successfully', 'data' => $jam]);
  }

  public function destroyJam($id)
  {
    $jam = Jam::findOrFail($id);
    $jamDetail = $jam->jam_mulai . ' - ' . $jam->jam_selesai;
    $jam->delete();
    $this->logActivity('Data Jam', 'Menghapus Data Jam : ' . $jamDetail);
    return response()->json(['message' => 'Jam deleted successfully']);
  }

  // --- MATA KULIAH ---
  public function indexMataKuliah()
  {
    return view('pages.master-data.mata-kuliah');
  }

  public function getMataKuliahData()
  {
    $mataKuliah = MataKuliah::with('dosen')->orderBy('id', 'desc')->get();

    // Map data to match frontend expectation
    $formattedData = $mataKuliah->map(function ($mk) {
      return [
        'id' => $mk->id,
        'name' => $mk->nama_matkul,
        'sks' => $mk->sks,
        'dosen' => $mk->dosen ? $mk->dosen->nama_dosen : '-',
        'dosen_id' => $mk->dosen_id, // Needed for edit form
        'status' => $mk->status
      ];
    });

    return response()->json($formattedData);
  }

  public function storeMataKuliah(Request $request)
  {
    $request->validate([
      'nama_matkul' => 'required|string|max:255',
      'sks' => 'required|integer',
      'dosen_id' => 'nullable|exists:dosen,id',
      'status' => 'nullable|in:Active,Inactive'
    ]);

    $data = $request->all();
    // Ensure default status if not provided (though frontend sends it)
    $data['status'] = $data['status'] ?? 'Active';

    $mataKuliah = MataKuliah::create($data);
    $this->logActivity('Data Mata Kuliah', 'Menambah Data Mata Kuliah : ' . $mataKuliah->nama_matkul);
    return response()->json(['message' => 'Mata Kuliah created successfully', 'data' => $mataKuliah], 201);
  }

  public function showMataKuliah($id)
  {
    return response()->json(MataKuliah::with('dosen')->findOrFail($id));
  }

  public function updateMataKuliah(Request $request, $id)
  {
    $request->validate([
      'nama_matkul' => 'required|string|max:255',
      'sks' => 'required|integer',
      'dosen_id' => 'nullable|exists:dosen,id',
      'status' => 'nullable|in:Active,Inactive'
    ]);
    $mataKuliah = MataKuliah::findOrFail($id);
    $mataKuliah->update($request->all());
    $this->logActivity('Data Mata Kuliah', 'Mengubah data Mata Kuliah : ' . $mataKuliah->nama_matkul);
    return response()->json(['message' => 'Mata Kuliah updated successfully', 'data' => $mataKuliah]);
  }

  public function destroyMataKuliah($id)
  {
    $mataKuliah = MataKuliah::findOrFail($id);
    $namaMatkul = $mataKuliah->nama_matkul;
    $mataKuliah->delete();
    $this->logActivity('Data Mata Kuliah', 'Menghapus Data Mata Kuliah : ' . $namaMatkul);
    return response()->json(['message' => 'Mata Kuliah deleted successfully']);
  }

  // --- RUANGAN ---
  public function indexRuangan()
  {
    return view('pages.master-data.ruangan');
  }

  public function getRuanganData()
  {
    $ruangan = Ruangan::orderBy('id', 'desc')->get();

    $formattedData = $ruangan->map(function ($room) {
      return [
        'id' => $room->id,
        'name' => $room->nama_ruangan,
        'status' => $room->status
      ];
    });

    return response()->json($formattedData);
  }

  public function storeRuangan(Request $request)
  {
    $request->validate([
      'nama_ruangan' => 'required|string|max:255',
      'status' => 'nullable|in:Active,Inactive'
    ]);

    $data = $request->all();
    $data['status'] = $data['status'] ?? 'Active';

    $ruangan = Ruangan::create($data);
    $this->logActivity('Data Ruangan', 'Menambah Data Ruangan : ' . $ruangan->nama_ruangan);
    return response()->json(['message' => 'Ruangan created successfully', 'data' => $ruangan], 201);
  }

  public function updateRuangan(Request $request, $id)
  {
    $request->validate([
      'nama_ruangan' => 'required|string|max:255',
      'status' => 'nullable|in:Active,Inactive'
    ]);

    $ruangan = Ruangan::findOrFail($id);
    $ruangan->update($request->all());
    $this->logActivity('Data Ruangan', 'Mengubah Data Ruangan : ' . $ruangan->nama_ruangan);
    return response()->json(['message' => 'Ruangan updated successfully', 'data' => $ruangan]);
  }

  public function destroyRuangan($id)
  {
    $ruangan = Ruangan::findOrFail($id);
    $namaRuangan = $ruangan->nama_ruangan;
    $ruangan->delete();
    $this->logActivity('Data Ruangan', 'Menghapus Data Ruangan : ' . $namaRuangan);
    return response()->json(['message' => 'Ruangan deleted successfully']);
  }
  public function showRuangan($id)
  {
    return response()->json(Ruangan::findOrFail($id));
  }
}
