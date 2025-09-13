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
    public function index()
    {
        $akunKeuanganTanpaParent = AkunKeuangan::whereNull('parent_id')->get();
        return view('admin.akun_keuangan.index', compact('akunKeuanganTanpaParent'));
    }

    /**
     * API DataTables (server-side)
     */
    public function dataTable()
    {
        $query = AkunKeuangan::with('parentAkun');

        return DataTables::of($query)
            ->addColumn('parent', function ($row) {
                return $row->parentAkun ? $row->parentAkun->nama_akun : '-';
            })
            ->addColumn('aksi', function ($row) {
                return view('admin.akun_keuangan.partials.aksi', compact('row'))->render();
            })

            ->rawColumns(['aksi'])
            ->make(true);
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

