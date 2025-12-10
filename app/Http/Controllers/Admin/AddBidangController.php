<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AddBidangController extends Controller
{
    public function index()
    {
        $bidangs = Bidang::all();
        return view('admin.add_bidangs.index', compact('bidangs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:bidangs,name',
            'description' => 'nullable',
        ]);

        Bidang::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        // gunakan nama route lengkap (pakai prefix admin)
        return redirect()->route('admin.add_bidangs.index')
            ->with('success', 'Bidang berhasil ditambahkan.');
    }

    public function getData(Request $request)
    {
        $data = Bidang::query();

        return DataTables::of($data)
            ->addColumn('actions', function ($row) {
                $deleteUrl = route('admin.add_bidangs.destroy', $row->id);
                $csrf = csrf_field();
                $method = method_field('DELETE');

                return '
                    <button type="button"
                            class="btn btn-warning btn-sm me-2 mb-2 btn-edit"
                            data-id="' . $row->id . '">
                        <i class="bi bi-pencil-square"></i>
                    </button>

                    <form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Yakin hapus?\')">
                        ' . $csrf . '
                        ' . $method . '
                        <button class="btn btn-danger btn-sm mb-2">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function edit($id)
    {
        // Ambil data bidang berdasarkan ID
        $bidang = Bidang::findOrFail($id);

        // Kembalikan JSON untuk dipakai di modal
        return response()->json($bidang);
    }

    public function update(Request $request, $id)
    {
        $bidang = Bidang::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:bidangs,name,' . $bidang->id,
            'description' => 'nullable',
        ]);

        $bidang->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('admin.add_bidangs.index')
            ->with('success', 'Bidang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $bidang = Bidang::findOrFail($id);
        $bidang->delete();

        return redirect()->route('admin.add_bidangs.index')
            ->with('success', 'Bidang berhasil dihapus.');
    }
}
