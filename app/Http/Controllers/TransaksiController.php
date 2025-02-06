<?php

namespace App\Http\Controllers;

use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;


class TransaksiController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Ambil saldo terakhir berdasarkan bidang_name
        $lastSaldo = Transaksi::where('bidang_name', $user->bidang_name)
            ->latest()
            ->first()->saldo ?? 0;

        // Ambil transaksi berdasarkan role
        $transaksiQuery = Transaksi::with('parentAkunKeuangan', 'user');

        // Jika user memiliki role 'Bidang', filter berdasarkan bidang_name
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        // Ambil hasil transaksi setelah filter
        $transaksi = $transaksiQuery->get();

        // Ambil semua data akun keuangan
        $akunKeuangan = AkunKeuangan::all();

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id')
            ->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray();

        $role = auth()->user()->role;
        $bidang_name = auth()->user()->bidang_name;

        // Tentukan prefix berdasarkan bidang_name
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_name) {
                case 'Pendidikan':
                    $prefix = 'PND';
                    break;
                case 'Kemasjidan':
                    $prefix = 'SJD';
                    break;
                case 'Sosial':
                    $prefix = 'SOS';
                    break;
                case 'Usaha':
                    $prefix = 'UHA';
                    break;
                case 'Pembangunan':
                    $prefix = 'BGN';
                    break;
            }
        }

        // Generate kode transaksi
        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        return view('transaksi.index', compact('transaksi', 'akunTanpaParent', 'akunDenganParent', 'bidang_name', 'akunKeuangan', 'kodeTransaksi', 'lastSaldo'));
    }

    public function create()
    {
        // Ambil akun keuangan dari database
        $akunKeuangan = AkunKeuangan::all();

        // Ambil saldo terakhir berdasarkan bidang_name
        $lastSaldo = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->latest()
            ->first()->saldo ?? 0;

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id')
            ->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray(); // Ubah menjadi array agar bisa digunakan di Blade

        // Ambil role dan bidang_name dari user yang sedang login
        $role = auth()->user()->role;
        $bidang_name = auth()->user()->bidang_name;

        // Tentukan prefix berdasarkan bidang_name
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_name) {
                case 'Kemasjidan':
                    $prefix = 'SJD-';
                    break;
                case 'Sosial':
                    $prefix = 'SOS-';
                    break;
                case 'Usaha':
                    $prefix = 'UHA-';
                    break;
                case 'Pembangunan':
                    $prefix = 'BGN-';
                    break;
                default:
                    $prefix = 'UNKNOWN-';
                    break;
            }
        }

        // Generate kode transaksi
        $kodeTransaksi = $prefix . strtoupper($bidang_name) . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        return view('transaksi.create', compact('akunTanpaParent', 'akunDenganParent', 'bidang_name', 'kodeTransaksi', 'lastSaldo'));
    }

    public function store(Request $request)
    {
        // Logging untuk debugging
        Log::info('Memulai proses penyimpanan transaksi', $request->all());

        // Validasi data input
        $validatedData = $request->validate([
            'bidang_name' => 'required|string',
            'kode_transaksi' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'akun_keuangan_id' => 'required|integer',
            'parent_akun_id' => 'nullable|integer',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        // Hitung saldo berdasarkan debit dan kredit
        // Ambil saldo terakhir berdasarkan bidang_name
        $lastSaldo = Transaksi::where('bidang_name', $request->input('bidang_name'))
            ->latest()
            ->first()->saldo ?? 0;

        // Simpan transaksi baru
        $transaksi = new Transaksi();
        $transaksi->bidang_name = $request->input('bidang_name');
        $transaksi->kode_transaksi = $request->input('kode_transaksi');
        $transaksi->tanggal_transaksi = $request->input('tanggal_transaksi');
        $transaksi->type = $request->input('type');
        $transaksi->akun_keuangan_id = $request->input('akun_keuangan_id');
        $transaksi->parent_akun_id = $request->input('parent_akun_id') ?? null; // Pastikan jika parent_akun_id tidak ada, diset ke null
        $transaksi->deskripsi = $request->input('deskripsi');
        $transaksi->amount = $request->input('amount');
        $transaksi->save();

        // Logging hasil penyimpanan
        if ($transaksi->exists) {
            Log::info('Data transaksi berhasil disimpan', ['id' => $transaksi->id]);

            // Panggil createJournalEntry untuk mencatat jurnal
            $this->createJournalEntry($transaksi);
        } else {
            Log::error('Data transaksi gagal disimpan');
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function edit($id)
    {
        // Ambil semua data akun keuangan
        $akunKeuangan = AkunKeuangan::all();

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id')
            ->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray();

        // Ambil data transaksi berdasarkan ID
        $transaksi = Transaksi::findOrFail($id);

        // Kirim data transaksi ke view edit
        return view('transaksi.edit', compact('transaksi', 'akunTanpaParent', 'akunDenganParent'));
    }

    public function update(Request $request, $id)
    {
        // Validasi data input
        $validatedData = $request->validate([
            'bidang_name' => 'required|string',
            'kode_transaksi' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'akun_keuangan_id' => 'required|integer',
            'parent_akun_id' => 'nullable|integer',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        // Cari transaksi berdasarkan ID
        $transaksi = Transaksi::findOrFail($id);

        // Update data transaksi
        $transaksi->update($validatedData);

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil diperbarui!');
    }

    public function getData()
    {
        $user = auth()->user();

        // Ambil transaksi berdasarkan role
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan', 'user'); // Pastikan relasi sudah dimuat

        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        // Debugging relasi parentAkunKeuangan
        Log::info('Data transaksi dengan parent akun:', $transaksi->toArray());

        return DataTables::of($transaksi)
            ->addColumn('parent_akun_nama', function ($item) {
                return $item->parentAkunKeuangan ? $item->parentAkunKeuangan->nama_akun : 'N/A';
            })
            ->addColumn('actions', function ($item) {
                return view('transaksi.actions', ['id' => $item->id]);
            })
            ->rawColumns(['actions']) // Memberikan raw HTML pada kolom actions
            ->make(true);
    }

    private function createJournalEntry($transaksi)
    {
        // Ambil akun Kas (misalnya akun dengan id 101) dan Pendapatan (misalnya akun dengan id 202)
        $kas = AkunKeuangan::where('id', 101)->first(); // Akun Kas
        $pendapatan = AkunKeuangan::where('id', 202)->first(); // Akun Pendapatan

        // Tentukan akun beban berdasarkan parent_akun_id
        $akunBebanId = $transaksi->parent_akun_id ?? $transaksi->akun_keuangan_id;

        // Pastikan akun yang diperlukan ada sebelum membuat jurnal
        if (!$kas || !$pendapatan || !$akunBebanId) {
            Log::error('Akun tidak ditemukan dalam database', [
                'kas' => $kas,
                'pendapatan' => $pendapatan,
                'akunBebanId' => $akunBebanId,
            ]);
            return;
        }

        // Jika transaksi adalah pemasukan (penerimaan)
        if ($transaksi->type == 'penerimaan') {
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kas->id, // Kas bertambah (debit)
                'debit' => $transaksi->amount,
                'credit' => 0
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $pendapatan->id, // Pendapatan bertambah (credit)
                'debit' => 0,
                'credit' => $transaksi->amount
            ]);
        }
        // Jika transaksi adalah pengeluaran (beban)
        else if ($transaksi->type == 'pengeluaran') {
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunBebanId, // Beban bertambah (debit)
                'debit' => $transaksi->amount,
                'credit' => 0
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kas->id, // Kas berkurang (credit)
                'debit' => 0,
                'credit' => $transaksi->amount
            ]);
        }

        // Hitung total saldo kas setelah transaksi
        $totalKas = Ledger::where('akun_keuangan_id', $kas->id)
            ->sum('debit') - Ledger::where('akun_keuangan_id', $kas->id)->sum('credit');

        // Logging saldo kas terbaru
        Log::info('Jurnal berhasil dibuat. Saldo kas terbaru: ' . $totalKas, [
            'id_transaksi' => $transaksi->id,
            'total_kas' => $totalKas
        ]);
    }

    public function destroy($id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus!'
        ]);
    }



}
