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

        // Ambil transaksi terakhir yang melibatkan akun Kas (ID = 101)
        $lastSaldo = Transaksi::where('akun_keuangan_id', 101) // Cek untuk akun Kas (ID = 101)
            ->where('bidang_name', $bidang_name)
            ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
            ->get() // Ambil semua data sebagai collection
            ->last(); // Ambil baris terakhir dalam hasil (data terbaru)

        // Pastikan $lastSaldo adalah objek Transaksi dan mengakses saldo dengan benar
        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0; // Jika tidak ada transaksi sebelumnya, saldo Kas dianggap 0

        return view('transaksi.index', compact('transaksi', 'akunTanpaParent', 'akunDenganParent', 'bidang_name', 'akunKeuangan', 'kodeTransaksi', 'lastSaldo', 'saldoKas'));
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

        // Logging data setelah validasi
        Log::info('Data setelah validasi:', $validatedData);

        $bidang_name = $validatedData['bidang_name']; // Nama bidang dari input

        // Ambil transaksi terakhir yang melibatkan akun Kas (ID = 101)
        $lastSaldo = Transaksi::where('akun_keuangan_id', 101) // Cek untuk akun Kas (ID = 101)
            ->where('bidang_name', $bidang_name)
            ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
            ->get() // Ambil semua data sebagai collection
            ->last(); // Ambil baris terakhir dalam hasil (data terbaru)

        // Pastikan $lastSaldo adalah objek Transaksi dan mengakses saldo dengan benar
        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0; // Jika tidak ada transaksi sebelumnya, saldo Kas dianggap 0

        Log::info('Saldo akun Kas ID 101:', ['saldo' => $saldoKas]);

        // Logika untuk pengeluaran
        if ($validatedData['type'] === 'pengeluaran') {
            // Cek jika pengeluaran melebihi saldo Kas yang ada
            if ($validatedData['amount'] > $saldoKas) {
                return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akun Kas.']);
            }
        }

        // Tentukan saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoKas; // Set saldo awal dengan saldo terakhir dari akun Kas

        if ($validatedData['type'] === 'penerimaan') {
            // Tambah saldo untuk penerimaan
            $newSaldo += $validatedData['amount'];
        } else {
            // Kurangi saldo untuk pengeluaran
            $newSaldo -= $validatedData['amount'];
        }

        // Simpan transaksi baru
        $transaksi = new Transaksi();
        $transaksi->bidang_name = $validatedData['bidang_name'];
        $transaksi->kode_transaksi = $validatedData['kode_transaksi'];
        $transaksi->tanggal_transaksi = $validatedData['tanggal_transaksi'];
        $transaksi->type = $validatedData['type'];
        $transaksi->akun_keuangan_id = 101; // Menyimpan transaksi pada akun Kas (ID = 101)
        $transaksi->parent_akun_id = $validatedData['parent_akun_id'];
        $transaksi->deskripsi = $validatedData['deskripsi'];
        $transaksi->amount = $validatedData['amount'];
        $transaksi->saldo = $newSaldo; // Update saldo dengan nilai baru setelah transaksi

        // Logging sebelum penyimpanan
        Log::info('Menyimpan transaksi:', $transaksi->toArray());

        if ($transaksi->save()) {
            // Logging hasil penyimpanan
            Log::info('Data transaksi berhasil disimpan', ['id' => $transaksi->id]);

            // Panggil createJournalEntry untuk mencatat jurnal
            $this->createJournalEntry($transaksi);
        } else {
            Log::error('Gagal menyimpan data transaksi');
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function storeBankTransaction(Request $request)
    {
        // Logging untuk debugging
        Log::info('Memulai proses penyimpanan transaksi bank', $request->all());

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

        $akun_keuangan_id = $validatedData['akun_keuangan_id']; // ID akun keuangan dari input
        $bidang_name = $validatedData['bidang_name']; // Nama bidang dari input

        // Ambil saldo terakhir berdasarkan akun_keuangan_id dan bidang_name
        $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
            ->where('bidang_name', $bidang_name)
            ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
            ->get() // Ambil semua data sebagai collection
            ->last() // Ambil baris terakhir dalam hasil (data terbaru)
                ?->saldo ?? 0; // Ambil nilai kolom 'saldo' atau default 0 jika tidak ada data

        // Menggunakan $lastSaldo
        $lastSaldo;

        // Periksa logika berdasarkan tipe transaksi
        if ($validatedData['type'] === 'penerimaan') {
            // Tambah saldo untuk penerimaan
            $newSaldo = $lastSaldo + $validatedData['amount'];
        } else {
            // Cek jika saldo terakhir = 0
            if ($lastSaldo == 0) {
                return back()->withErrors(['amount' => 'Saldo saat ini kosong, tidak dapat melakukan pengurangan.']);
            }

            // Kurangi saldo untuk pengeluaran
            $newSaldo = $lastSaldo - $validatedData['amount'];

            // Cek jika saldo menjadi negatif
            if ($newSaldo < 0) {
                return back()->withErrors(['amount' => 'Saldo tidak mencukupi untuk pengeluaran ini.']);
            }
        }

        // Simpan transaksi baru
        $transaksi = new Transaksi();
        $transaksi->bidang_name = $validatedData['bidang_name'];
        $transaksi->kode_transaksi = $validatedData['kode_transaksi'];
        $transaksi->tanggal_transaksi = $validatedData['tanggal_transaksi'];
        $transaksi->type = $validatedData['type'];
        $transaksi->akun_keuangan_id = $validatedData['akun_keuangan_id'];
        $transaksi->parent_akun_id = $validatedData['parent_akun_id'] ?? null;
        $transaksi->deskripsi = $validatedData['deskripsi'];
        $transaksi->amount = $validatedData['amount'];
        $transaksi->saldo = $newSaldo;
        $transaksi->save();

        // Logging hasil penyimpanan
        if ($transaksi->exists) {
            Log::info('Data transaksi bank berhasil disimpan', ['id' => $transaksi->id]);
        } else {
            Log::error('Data transaksi bank gagal disimpan');
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

        // Ambil transaksi yang akan diperbarui
        $transaksi = Transaksi::findOrFail($id);

        $bidang_name = $validatedData['bidang_name']; // Nama bidang dari input

        // Ambil transaksi terakhir kedua yang melibatkan akun Kas (ID = 101)
        $lastTwoTransactions = Transaksi::where('akun_keuangan_id', 101)
            ->where('bidang_name', 'Pendidikan')
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->reverse()  // Balikkan urutan data
            ->slice(0, 2) // Ambil dua transaksi terakhir
            ->last(); // Ambil transaksi kedua terakhir (setelah reverse)

        // Ambil transaksi terakhir kedua, jika ada
        $lastSaldo = $lastTwoTransactions;

        // Saldo dari transaksi terakhir kedua, jika tidak ada maka default ke 0
        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0;

        Log::info('Saldo akun Kas ID 101 dari transaksi terakhir kedua:', ['saldo' => $saldoKas]);

        // Logika untuk pengeluaran
        if ($validatedData['type'] === 'pengeluaran') {
            // Cek jika pengeluaran melebihi saldo Kas yang ada
            if ($validatedData['amount'] > $saldoKas) {
                return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akun Kas.']);
            }
        }

        // Tentukan saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoKas; // Set saldo awal dengan saldo terakhir dari akun Kas

        if ($validatedData['type'] === 'penerimaan') {
            // Tambah saldo untuk penerimaan
            $newSaldo += $validatedData['amount'];
        } else {
            // Kurangi saldo untuk pengeluaran
            $newSaldo -= $validatedData['amount'];
        }

        // Update data transaksi
        $transaksi->bidang_name = $validatedData['bidang_name'];
        $transaksi->kode_transaksi = $validatedData['kode_transaksi'];
        $transaksi->tanggal_transaksi = $validatedData['tanggal_transaksi'];
        $transaksi->type = $validatedData['type'];
        $transaksi->akun_keuangan_id = 101; // Update pada akun Kas (ID = 101)
        $transaksi->parent_akun_id = $validatedData['parent_akun_id'];
        $transaksi->deskripsi = $validatedData['deskripsi'];
        $transaksi->amount = $validatedData['amount'];
        $transaksi->saldo = $newSaldo; // Update saldo dengan nilai baru setelah transaksi

        // Logging sebelum update
        Log::info('Data transaksi sebelum update:', $transaksi->toArray());

        if ($transaksi->save()) {
            // Logging hasil update
            Log::info('Data transaksi berhasil diperbarui', ['id' => $transaksi->id]);

            // Panggil createJournalEntry untuk mencatat jurnal
            $this->createJournalEntry($transaksi);
        } else {
            Log::error('Gagal memperbarui data transaksi');
        }

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
