<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hutang;
use App\Models\User;
use App\Models\AkunKeuangan;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\HutangReminder; // Sesuaikan dengan notifikasi yang dibuat

class HutangController extends Controller
{
    public function index()
    {
        $user = auth()->user(); // Ambil user yang sedang login
        $bidangName = $user->bidang_name; // Ambil bidang dari user

        $hutangs = Hutang::with('user', 'akunKeuangan')
            ->where('bidang_name', $bidangName) // Filter berdasarkan bidang
            ->where('user_id', $user->id) // Filter berdasarkan user yang login
            ->get();

        return view('hutang.index', compact('hutangs'));
    }

    public function create()
    {
        $akunKeuangan = AkunKeuangan::all();
        $akunHutang = AkunKeuangan::where('id', 201)->first();
        $parentAkunHutang = AkunKeuangan::where('parent_id', 201)->get();
        $bidang_name = auth()->user()->bidang_name;

        $users = User::where('bidang_name', '!=', $bidang_name)
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'Bendahara');
            })
            ->get();

        return view('hutang.create', compact('akunKeuangan', 'akunHutang', 'parentAkunHutang', 'users'));
    }

    public function store(Request $request)
    {
        Log::info('Menerima request untuk menyimpan Hutang', ['data' => $request->all()]);

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

            $hutang = Hutang::create([
                'user_id' => $validated['user_id'],
                'akun_keuangan_id' => $validated['akun_keuangan_id'],
                'parent_id' => $request->parent_akun_id ?? null,
                'jumlah' => $validated['jumlah'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                'deskripsi' => $validated['deskripsi'],
                'status' => $validated['status'],
                'bidang_name' => $bidangName,
            ]);

            Log::info('Hutang berhasil disimpan', ['hutang' => $hutang]);

            // Coba ambil user yang membuat hutang
            $pembuatHutang = User::find(auth()->id());

            if ($pembuatHutang) {
                Log::info('Mengirim notifikasi ke pembuat hutang', ['user_id' => $pembuatHutang->id]);
                $pembuatHutang->notify(new HutangReminder($hutang));
            } else {
                Log::error('User tidak ditemukan saat mengirim notifikasi.');
            }

            return redirect()->route('hutangs.index')->with('success', 'Hutang berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan Hutang', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan hutang. ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        Log::info('Menerima request untuk memperbarui Hutang', ['data' => $request->all()]);

        try {
            $validated = $request->validate([
                'jumlah' => 'required|numeric|min:0',
                'tanggal_jatuh_tempo' => 'required|date',
                'deskripsi' => 'nullable|string',
                'status' => 'required|in:belum_lunas,lunas',
            ]);

            $hutang = Hutang::findOrFail($id);
            $hutang->update([
                'jumlah' => $validated['jumlah'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                'deskripsi' => $validated['deskripsi'],
                'status' => $validated['status'],
            ]);

            Log::info('Hutang berhasil diperbarui', ['hutang' => $hutang]);

            $bidangName = $hutang->user->bidang_name ?? 'Tidak Diketahui';

            // **1. Perbarui notifikasi jika hutang masih belum lunas**
            $existingNotification = DB::table('notifications')
                ->where('notifiable_id', auth()->id())
                ->whereJsonContains('data->hutang_id', $hutang->id)
                ->first();

            $notificationData = [
                'message' => 'Anda memiliki hutang sebesar Rp' . number_format($hutang->jumlah, 2) . ' kepada: ' . $bidangName . ' yang jatuh tempo pada ' . $hutang->tanggal_jatuh_tempo,
                'url' => url('/hutang/' . $hutang->id),
                'hutang_id' => $hutang->id,
            ];

            if ($existingNotification) {
                DB::table('notifications')
                    ->where('id', $existingNotification->id)
                    ->update([
                        'data' => json_encode($notificationData),
                        'updated_at' => now(),
                    ]);
            } else {
                auth()->user()->notify(new HutangReminder($hutang));
            }

            // **2. Tandai notifikasi sebagai dibaca jika hutang sudah lunas**
            if ($hutang->status === 'lunas') {
                DB::table('notifications')
                    ->where('notifiable_id', auth()->id())
                    ->whereJsonContains('data->hutang_id', $hutang->id)
                    ->update(['read_at' => now()]);
            }

            return redirect()->route('hutangs.index')->with('success', 'Hutang berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui Hutang', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui hutang.');
        }
    }

    public function getData(Request $request)
    {
        Log::info('Fetching data hutang untuk DataTables');

        try {
            $user = auth()->user(); // Ambil user yang login
            Log::info('User login:', ['id' => $user->id, 'bidang' => $user->bidang_name]);

            // Ambil semua hutang yang memiliki bidang_name sesuai dengan user login
            $hutangs = Hutang::with('user')
                ->where('bidang_name', $user->bidang_name);

            Log::info('Query hutang:', ['sql' => $hutangs->toSql(), 'bindings' => $hutangs->getBindings()]);

            return DataTables::of($hutangs)
                ->addIndexColumn()
                ->addColumn('user_name', function ($hutang) {
                    return optional($hutang->user)->name ?? 'N/A';
                })
                ->addColumn('jumlah_formatted', function ($hutang) {
                    return 'Rp ' . number_format($hutang->jumlah, 2, ',', '.');
                })
                ->addColumn('status_badge', function ($hutang) {
                    $class = $hutang->status == 'lunas' ? 'bg-success' : 'bg-danger';
                    return '<span class="badge ' . $class . '">' . ucfirst($hutang->status) . '</span>';
                })
                ->addColumn('actions', function ($hutang) {
                    return view('hutang.actions', compact('hutang'))->render();
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data hutang', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data'], 500);
        }
    }

    public function show(Hutang $hutang)
    {
        return view('hutang.show', compact('hutang'));
    }

    public function edit(Hutang $hutang)
    {
        $users = User::all();
        $akunHutang = AkunKeuangan::where('id', 201)->first();
        $parentAkunHutang = AkunKeuangan::where('parent_id', 201)->get();
        $akunKeuangans = AkunKeuangan::where('parent_id', 201)->get();
        return view('hutang.edit', compact('hutang', 'users', 'akunKeuangans', 'akunHutang', 'parentAkunHutang'));
    }

    public function destroy(Hutang $hutang)
    {
        Log::info('Menerima request untuk menghapus Hutang', ['hutang_id' => $hutang->id]);

        try {
            $hutang->delete();
            Log::info('Hutang berhasil dihapus', ['hutang_id' => $hutang->id]);

            return redirect()->route('hutangs.index')->with('success', 'Hutang berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus Hutang', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus hutang.');
        }
    }
}
