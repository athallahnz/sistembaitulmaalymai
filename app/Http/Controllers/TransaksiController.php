<?php

namespace App\Http\Controllers;

use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    public function create()
    {
        // Ambil akun keuangan dari database
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

        return view('transaksi.create', compact('akunTanpaParent', 'akunDenganParent', 'bidang_name', 'kodeTransaksi'));
    }
    public function store(Request $request)
    {
        // Logging untuk debugging
        Log::info('Memulai proses penyimpanan transaksi', $request->all());

        // Validasi data input
        $validatedData = $request->validate([
            'bidang_name' => 'required|string',
            'deskripsi' => 'required|string',
            'kode_transaksi' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'akun_keuangan_id' => 'required|integer',
            'parent_akun_id' => 'nullable|integer',
            'debit' => 'required|numeric',
            'kredit' => 'required|numeric',
        ]);

        // Pastikan parent_akun_id bisa null jika tidak dipilih
        $validatedData['parent_akun_id'] = $validatedData['parent_akun_id'] ?? null;

        // Hitung saldo berdasarkan debit dan kredit
        $validatedData['saldo'] = $validatedData['debit'] - $validatedData['kredit'];

        // Simpan transaksi ke database menggunakan Eloquent
        $transaksi = Transaksi::create($validatedData);

        // Logging hasil penyimpanan
        if ($transaksi) {
            Log::info('Data transaksi berhasil disimpan', ['id' => $transaksi->id]);
        } else {
            Log::error('Data transaksi gagal disimpan');
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function index()
    {
        // Ambil user yang sedang login
        $user = auth()->user();

        // Ambil transaksi berdasarkan role
        $transaksiQuery = Transaksi::with('user');

        // Jika user memiliki role 'Bidang', filter berdasarkan bidang_name
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        // Ambil hasil transaksi setelah filter
        $transaksi = $transaksiQuery->get();

        // Ambil semua data akun keuangan
        $akunKeuangan = AkunKeuangan::all();  // Assuming you have a model named AkunKeuangan

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

        return view('transaksi.index', compact('transaksi', 'akunTanpaParent', 'akunDenganParent', 'bidang_name', 'akunKeuangan', 'kodeTransaksi'));
    }
}
