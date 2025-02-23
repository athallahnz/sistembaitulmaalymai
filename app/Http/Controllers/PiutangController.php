<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Piutang;
use App\Models\User;
use App\Models\AkunKeuangan;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PiutangController extends Controller
{
    public function index()
    {
        $bidangName = auth()->user()->bidang_name; // Ambil bidang dari user yang login

        $piutangs = Piutang::with('user', 'akunKeuangan')
            ->where('bidang_name', $bidangName) // Filter berdasarkan bidang
            ->get();

        return view('piutang.index', compact('piutangs'));
    }


    public function create()
    {
        // Ambil akun keuangan dari database
        $akunKeuangan = AkunKeuangan::all();

        // Ambil akun piutang dengan ID 103
        $akunPiutang = AkunKeuangan::where('id', 103)->first();

        // Ambil akun yang memiliki parent_id = 103
        $parentAkunPiutang = AkunKeuangan::where('parent_id', 103)->get();

        // Ambil bidang_name selain yang login
        // Ambil bidang_name selain yang login
        $bidang_name = auth()->user()->bidang_name;

        // Ambil user yang memiliki bidang berbeda dari user yang login & memiliki role "Bendahara" atau "Bidang"
        $users = User::where('bidang_name', '!=', $bidang_name) // Hanya user dengan bidang berbeda
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'Bendahara'); // User dengan role Bendahara
            })
            ->get();

        return view('piutang.create', compact('akunKeuangan', 'akunPiutang', 'parentAkunPiutang', 'users'));
    }

    public function store(Request $request)
    {
        Log::info('Menerima request untuk menyimpan Piutang', ['data' => $request->all()]);

        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
                'parent_akun_id' => 'nullable|exists:akun_keuangans,id',
                'jumlah' => 'required|numeric|min:0',
                'tanggal_jatuh_tempo' => 'required|date',
                'deskripsi' => 'nullable|string',
                'status' => 'required|in:belum_lunas,lunas',
            ]);

            $bidangName = auth()->user()->bidang_name;

            $piutang = Piutang::create([
                'user_id' => $validated['user_id'],
                'akun_keuangan_id' => $validated['akun_keuangan_id'],
                'parent_id' => $request->parent_akun_id ?? null,
                'jumlah' => $validated['jumlah'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                'deskripsi' => $validated['deskripsi'],
                'status' => $validated['status'],
                'bidang_name' => $bidangName,
            ]);

            Log::info('Piutang berhasil disimpan', ['piutang' => $piutang]);

            return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan Piutang', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan piutang.');
        }
    }

    public function getData(Request $request)
    {
        Log::info('Fetching data piutang untuk DataTables');

        try {
            $user = auth()->user(); // Ambil user yang login
            Log::info('User login:', ['id' => $user->id, 'bidang' => $user->bidang_name]);

            // Ambil semua piutang yang memiliki bidang_name sesuai dengan user login
            $piutangs = Piutang::with('user')
                ->where('bidang_name', $user->bidang_name);

            Log::info('Query piutang:', ['sql' => $piutangs->toSql(), 'bindings' => $piutangs->getBindings()]);

            return DataTables::of($piutangs)
                ->addIndexColumn()
                ->addColumn('user_name', function ($piutang) {
                    return optional($piutang->user)->name ?? 'N/A';
                })
                ->addColumn('jumlah_formatted', function ($piutang) {
                    return 'Rp ' . number_format($piutang->jumlah, 2, ',', '.');
                })
                ->addColumn('status_badge', function ($piutang) {
                    $class = $piutang->status == 'lunas' ? 'bg-success' : 'bg-danger';
                    return '<span class="badge ' . $class . '">' . ucfirst($piutang->status) . '</span>';
                })
                ->addColumn('actions', function ($piutang) {
                    return view('piutang.actions', compact('piutang'))->render();
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data piutang', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data'], 500);
        }
    }

    public function show(Piutang $piutang)
    {
        return view('piutang.show', compact('piutang'));
    }

    public function edit(Piutang $piutang)
    {
        $users = User::all();

        $akunPiutang = AkunKeuangan::where('id', 103)->first();
        $parentAkunPiutang = AkunKeuangan::where('parent_id', 103)->get();

        $akunKeuangans = AkunKeuangan::where('parent_id', 103)->get();
        return view('piutang.edit', compact('piutang', 'users', 'akunKeuangans', 'akunPiutang', 'parentAkunPiutang'));
    }

    public function update(Request $request, Piutang $piutang)
    {
        Log::info('Menerima request untuk update Piutang', [
            'piutang_id' => $piutang->id,
            'data_baru' => $request->all()
        ]);

        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
                'jumlah' => 'required|numeric|min:0',
                'tanggal_jatuh_tempo' => 'required|date',
                'deskripsi' => 'nullable|string',
                'status' => 'required|in:belum_lunas,lunas',
            ]);

            $piutang->update($validated);

            Log::info('Piutang berhasil diperbarui', ['piutang' => $piutang]);

            return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui Piutang', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui piutang.');
        }
    }

    public function destroy(Piutang $piutang)
    {
        Log::info('Menerima request untuk menghapus Piutang', ['piutang_id' => $piutang->id]);

        try {
            $piutang->delete();
            Log::info('Piutang berhasil dihapus', ['piutang_id' => $piutang->id]);

            return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus Piutang', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus piutang.');
        }
    }

}
