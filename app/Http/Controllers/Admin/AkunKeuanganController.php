<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AkunKeuangan;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AkunKeuanganController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10); // Default 10
        $sortColumn = $request->input('sort', 'id'); // Default sorting by ID
        $sortDirection = $request->input('direction', 'asc'); // Default ASC

        $akunKeuangans = AkunKeuangan::with('parentAkun')
            ->when($search, function ($query) use ($search) {
                return $query->where('nama_akun', 'like', "%$search%")
                    ->orWhere('kode_akun', 'like', "%$search%");
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage);

        $akunKeuanganTanpaParent = AkunKeuangan::whereNull('parent_id')->get();

        return view('admin.akun_keuangan.index', compact('akunKeuangans', 'akunKeuanganTanpaParent', 'search', 'perPage', 'sortColumn', 'sortDirection'));
    }

    public function store(Request $request)
    {
        try {
            Log::info('Request masuk ke store()', ['request' => $request->all()]);

            // Validasi request
            $validatedData = $request->validate([
                'id' => 'required|numeric|unique:akun_keuangans,id',
                'kode_akun' => 'required|unique:akun_keuangans,kode_akun',
                'nama_akun' => 'required|string|max:255',
                'tipe_akun' => 'required|in:asset,liability,revenue,expense,equity',
                'saldo_normal' => 'required|in:debit,kredit',
                'parent_id' => 'nullable|exists:akun_keuangans,id',
                'cashflow_category' => 'nullable|in:operasional,investasi,pendanaan',
                'icon' => 'nullable|string|max:255',
            ]);

            Log::info('Validasi sukses', ['validatedData' => $validatedData]);

            // Simpan data
            $akun = AkunKeuangan::create($validatedData);
            Log::info('Data berhasil disimpan', ['akun' => $akun]);

            return redirect()->route('admin.akun_keuangan.index')->with('success', 'Akun Keuangan berhasil ditambahkan.');

        } catch (ValidationException $e) {
            Log::error('Validasi gagal', ['errors' => $e->errors()]);
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat menyimpan akun', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan akun.');
        }
    }

    public function edit($id)
    {
        $akunKeuangan = AkunKeuangan::findOrFail($id); // Menampilkan 404 jika tidak ditemukan
        $akunKeuangantanpaparent = AkunKeuangan::whereNull('parent_id')->get();

        return view('admin.akun_keuangan.edit', compact('akunKeuangan', 'akunKeuangantanpaparent'));
    }

    public function update(Request $request, AkunKeuangan $akunKeuangan)
    {
        try {
            Log::info('Request masuk ke update()', ['request' => $request->all(), 'akun' => $akunKeuangan]);

            $request->validate([
                'kode_akun' => 'required|unique:akun_keuangans,kode_akun,' . $akunKeuangan->id,
                'nama_akun' => 'required|string|max:255',
                'tipe_akun' => 'required|in:asset,liability,revenue,expense,equity',
                'saldo_normal' => 'required|in:debit,kredit',
                'parent_id' => 'nullable|exists:akun_keuangans,id',
                'cashflow_category' => 'nullable|in:operasional,investasi,pendanaan',
                'icon' => 'nullable|string|max:255'
            ]);

            $akunKeuangan->update($request->all());

            Log::info('Data berhasil diperbarui', ['akun' => $akunKeuangan]);

            return redirect()->route('admin.akun_keuangan.index')->with('success', 'Akun berhasil diperbarui!');

        } catch (ValidationException $e) {
            Log::error('Validasi gagal saat update', ['errors' => $e->errors()]);
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat update akun', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat update akun.');
        }
    }


    public function destroy(AkunKeuangan $akunKeuangan)
    {
        $akunKeuangan->delete();
        return back()->with('success', 'Akun berhasil dihapus.');
    }

}

