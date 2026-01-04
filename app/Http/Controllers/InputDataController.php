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
  private function logActivity($type, $description, $note = null)
  {
    // Ensure user is authenticated, otherwise logging might fail due to null user_id
    // For testing purposes, you might want to ensure a user is logged in or handle this gracefully.
    $userId = Auth::id() ?? 1; // Fallback to ID 1 if no user logged in (for dev/testing convenience)

    Log::create([
      'user_id' => $userId,
      'type' => $type,
      'description' => $description,
      'note' => $note,
    ]);
  }

  // --- DOSEN ---
  public function indexDosen()
  {
    return response()->json(Dosen::all());
  }

  public function storeDosen(Request $request)
  {
    $request->validate(['nama_dosen' => 'required|string|max:255']);
    $dosen = Dosen::create($request->all());
    $this->logActivity('create', 'Menambah Data Dosen', 'Nama: ' . $dosen->nama_dosen);
    return response()->json(['message' => 'Dosen created successfully', 'data' => $dosen], 201);
  }

  public function showDosen($id)
  {
    return response()->json(Dosen::findOrFail($id));
  }

  public function updateDosen(Request $request, $id)
  {
    $request->validate(['nama_dosen' => 'required|string|max:255']);
    $dosen = Dosen::findOrFail($id);
    $dosen->update($request->all());
    $this->logActivity('update', 'Mengupdate Data Dosen', 'ID: ' . $id . ', Nama Baru: ' . $dosen->nama_dosen);
    return response()->json(['message' => 'Dosen updated successfully', 'data' => $dosen]);
  }

  public function destroyDosen($id)
  {
    Dosen::findOrFail($id)->delete();
    $this->logActivity('delete', 'Menghapus Data Dosen', 'ID: ' . $id);
    return response()->json(['message' => 'Dosen deleted successfully']);
  }

  // --- HARI ---
  public function indexHari()
  {
    return response()->json(Hari::all());
  }

  public function storeHari(Request $request)
  {
    $request->validate(['nama_hari' => 'required|string|max:255']);
    $hari = Hari::create($request->all());
    $this->logActivity('create', 'Menambah Data Hari', 'Nama: ' . $hari->nama_hari);
    return response()->json(['message' => 'Hari created successfully', 'data' => $hari], 201);
  }

  public function showHari($id)
  {
    return response()->json(Hari::findOrFail($id));
  }

  public function updateHari(Request $request, $id)
  {
    $request->validate(['nama_hari' => 'required|string|max:255']);
    $hari = Hari::findOrFail($id);
    $hari->update($request->all());
    $this->logActivity('update', 'Mengupdate Data Hari', 'ID: ' . $id . ', Nama Baru: ' . $hari->nama_hari);
    return response()->json(['message' => 'Hari updated successfully', 'data' => $hari]);
  }

  public function destroyHari($id)
  {
    Hari::findOrFail($id)->delete();
    $this->logActivity('delete', 'Menghapus Data Hari', 'ID: ' . $id);
    return response()->json(['message' => 'Hari deleted successfully']);
  }

  // --- JAM ---
  public function indexJam()
  {
    return response()->json(Jam::all());
  }

  public function storeJam(Request $request)
  {
    $request->validate([
      'jam_mulai' => 'required',
      'jam_selesai' => 'required',
    ]);
    $jam = Jam::create($request->all());
    $this->logActivity('create', 'Menambah Data Jam', 'Mulai: ' . $jam->jam_mulai . ', Selesai: ' . $jam->jam_selesai);
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
    ]);
    $jam = Jam::findOrFail($id);
    $jam->update($request->all());
    $this->logActivity('update', 'Mengupdate Data Jam', 'ID: ' . $id);
    return response()->json(['message' => 'Jam updated successfully', 'data' => $jam]);
  }

  public function destroyJam($id)
  {
    Jam::findOrFail($id)->delete();
    $this->logActivity('delete', 'Menghapus Data Jam', 'ID: ' . $id);
    return response()->json(['message' => 'Jam deleted successfully']);
  }

  // --- MATA KULIAH ---
  public function indexMataKuliah()
  {
    return response()->json(MataKuliah::with('dosen')->get());
  }

  public function storeMataKuliah(Request $request)
  {
    $request->validate([
      'nama_matkul' => 'required|string|max:255',
      'sks' => 'required|integer',
      'dosen_id' => 'required|exists:dosen,id',
    ]);
    $mataKuliah = MataKuliah::create($request->all());
    $this->logActivity('create', 'Menambah Data Mata Kuliah', 'Nama: ' . $mataKuliah->nama_matkul);
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
      'dosen_id' => 'required|exists:dosen,id',
    ]);
    $mataKuliah = MataKuliah::findOrFail($id);
    $mataKuliah->update($request->all());
    $this->logActivity('update', 'Mengupdate Data Mata Kuliah', 'ID: ' . $id);
    return response()->json(['message' => 'Mata Kuliah updated successfully', 'data' => $mataKuliah]);
  }

  public function destroyMataKuliah($id)
  {
    MataKuliah::findOrFail($id)->delete();
    $this->logActivity('delete', 'Menghapus Data Mata Kuliah', 'ID: ' . $id);
    return response()->json(['message' => 'Mata Kuliah deleted successfully']);
  }

  // --- RUANGAN ---
  public function indexRuangan()
  {
    return response()->json(Ruangan::all());
  }

  public function storeRuangan(Request $request)
  {
    $request->validate(['nama_ruangan' => 'required|string|max:255']);
    $ruangan = Ruangan::create($request->all());
    $this->logActivity('create', 'Menambah Data Ruangan', 'Nama: ' . $ruangan->nama_ruangan);
    return response()->json(['message' => 'Ruangan created successfully', 'data' => $ruangan], 201);
  }

  public function showRuangan($id)
  {
    return response()->json(Ruangan::findOrFail($id));
  }

  public function updateRuangan(Request $request, $id)
  {
    $request->validate(['nama_ruangan' => 'required|string|max:255']);
    $ruangan = Ruangan::findOrFail($id);
    $ruangan->update($request->all());
    $this->logActivity('update', 'Mengupdate Data Ruangan', 'ID: ' . $id);
    return response()->json(['message' => 'Ruangan updated successfully', 'data' => $ruangan]);
  }

  public function destroyRuangan($id)
  {
    Ruangan::findOrFail($id)->delete();
    $this->logActivity('delete', 'Menghapus Data Ruangan', 'ID: ' . $id);
    return response()->json(['message' => 'Ruangan deleted successfully']);
  }
}
