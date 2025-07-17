<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;


class AddBidangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bidangs = Bidang::all();
        return view('admin.add_bidangs.index', compact('bidangs'));
    }

    public function create()
    {
        return view('admin.add_bidangs.create');
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

        return redirect()->route('add_bidangs.index')->with('success', 'Bidang berhasil ditambahkan.');
    }

    public function getData(Request $request)
    {
        $data = Bidang::query();

        return DataTables::of($data)
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.add_bidangs.edit', $row->id);
                $deleteUrl = route('admin.add_bidangs.destroy', $row->id);
                $csrf = csrf_field();
                $method = method_field('DELETE');

                return <<<HTML
                <a href="{$editUrl}" class="btn btn-warning btn-sm me-2 mb-2">
                    <i class="bi bi-pencil-square"></i></a>
                <form action="{$deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?')">
                    {$csrf}
                    {$method}
                    <button class="btn btn-danger btn-sm mb-2"><i class="bi bi-trash"></i></button>
                </form>
            HTML;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function edit(Bidang $bidang)
    {
        return view('admin.add_bidangs.edit', compact('bidang'));
    }

    public function update(Request $request, Bidang $bidang)
    {
        $request->validate([
            'name' => 'required|unique:bidangs,name,' . $bidang->id,
            'description' => 'nullable',
        ]);

        $bidang->update($request->all());

        return redirect()->route('add_bidangs.index')->with('success', 'Bidang berhasil diperbarui.');
    }

    public function destroy(Bidang $bidang)
    {
        $bidang->delete();
        return redirect()->route('add_bidangs.index')->with('success', 'Bidang berhasil dihapus.');
    }

}


