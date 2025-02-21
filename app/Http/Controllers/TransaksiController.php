<?php

namespace App\Http\Controllers;

use App\Exports\TransaksisExport;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransaksisExpors;
use App\Services\LaporanService;



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

        $role = $user->role;
        $bidang_name = $user->bidang_name;

        // Tentukan prefix berdasarkan role dan bidang_name
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
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH'; // Prefix untuk Bendahara
        }

        // Generate kode transaksi
        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // Ambil transaksi terakhir yang melibatkan akun Kas (ID = 101)
        $lastSaldo = Transaksi::where('akun_keuangan_id', 101) // Cek untuk akun Kas (ID = 101)
            ->when($role === 'Bidang', function ($query) use ($bidang_name) {
                return $query->where('bidang_name', $bidang_name);
            })
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

        // Ambil transaksi terakhir yang melibatkan akun Bank (ID = 102)
        $lastSaldo = Transaksi::where('akun_keuangan_id', 102) // Cek untuk akun Bank (ID = 102)
            ->where('bidang_name', $bidang_name)
            ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
            ->get() // Ambil semua data sebagai collection
            ->last(); // Ambil baris terakhir dalam hasil (data terbaru)

        // Pastikan $lastSaldo adalah objek Transaksi dan mengakses saldo dengan benar
        $saldoBank = $lastSaldo ? $lastSaldo->saldo : 0; // Jika tidak ada transaksi sebelumnya, saldo Bank dianggap 0

        Log::info('Saldo akun Bank ID 102:', ['saldo' => $saldoBank]);

        // Logika untuk pengeluaran
        if ($validatedData['type'] === 'pengeluaran') {
            // Cek jika pengeluaran melebihi saldo Bank yang ada
            if ($validatedData['amount'] > $saldoBank) {
                return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akun Bank.']);
            }
        }

        // Tentukan saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoBank; // Set saldo awal dengan saldo terakhir dari akun Bank

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
        $transaksi->akun_keuangan_id = 102; // Menyimpan transaksi pada akun Bank (ID = 102)
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
            $this->createBankJournalEntry($transaksi);
        } else {
            Log::error('Gagal menyimpan data transaksi');
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $transaksi = Transaksi::findOrFail($id);

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

        return view('transaksi.edit', compact('transaksi', 'akunTanpaParent', 'akunDenganParent'));
    }

    public function update(Request $request, $id)
    {
        // Logging untuk debugging
        Log::info('Memulai proses update transaksi', ['id' => $id, 'request' => $request->all()]);

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

        // Ambil saldo terakhir sebelum transaksi ini
        $lastSaldo = Transaksi::where('akun_keuangan_id', 101)
            ->where('bidang_name', $validatedData['bidang_name'])
            ->where('id', '!=', $id) // Hindari transaksi yang sedang diperbarui
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0;

        Log::info('Saldo akun Kas ID 101 sebelum update:', ['saldo' => $saldoKas]);

        // Cek jika pengeluaran melebihi saldo
        if ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] > $saldoKas) {
            return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akun Kas.']);
        }

        // Hitung saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoKas;
        if ($validatedData['type'] === 'penerimaan') {
            $newSaldo += $validatedData['amount'];
        } else {
            $newSaldo -= $validatedData['amount'];
        }

        // Update transaksi
        $transaksi->update([
            'bidang_name' => $validatedData['bidang_name'],
            'kode_transaksi' => $validatedData['kode_transaksi'],
            'tanggal_transaksi' => $validatedData['tanggal_transaksi'],
            'type' => $validatedData['type'],
            'akun_keuangan_id' => 101,
            'parent_akun_id' => $validatedData['parent_akun_id'],
            'deskripsi' => $validatedData['deskripsi'],
            'amount' => $validatedData['amount'],
            'saldo' => $newSaldo,
        ]);

        Log::info('Data transaksi berhasil diperbarui', ['id' => $transaksi->id]);
        // Perbarui jurnal berdasarkan metode pembayaran (Kas atau Bank)
        if ($transaksi->akun_keuangan_id == 101) {
            $this->createJournalEntry($transaksi);
        } elseif ($transaksi->akun_keuangan_id == 102) {
            $this->createBankJournalEntry($transaksi);
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil diperbarui!');
    }

    public function updateBankTransaction(Request $request, $id)
    {
        // Logging untuk debugging
        Log::info('Memulai proses update transaksi Bank', ['id' => $id, 'request' => $request->all()]);

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

        // Ambil saldo terakhir sebelum transaksi ini
        $lastSaldo = Transaksi::where('akun_keuangan_id', 102)
            ->where('bidang_name', $validatedData['bidang_name'])
            ->where('id', '!=', $id) // Hindari transaksi yang sedang diperbarui
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoBank = $lastSaldo ? $lastSaldo->saldo : 0;

        Log::info('Saldo akun Bank ID 102 sebelum update:', ['saldo' => $saldoBank]);

        // Cek jika pengeluaran melebihi saldo
        if ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] > $saldoBank) {
            return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akun Bank.']);
        }

        // Hitung saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoBank;
        if ($validatedData['type'] === 'penerimaan') {
            $newSaldo += $validatedData['amount'];
        } else {
            $newSaldo -= $validatedData['amount'];
        }

        // Update transaksi
        $transaksi->update([
            'bidang_name' => $validatedData['bidang_name'],
            'kode_transaksi' => $validatedData['kode_transaksi'],
            'tanggal_transaksi' => $validatedData['tanggal_transaksi'],
            'type' => $validatedData['type'],
            'akun_keuangan_id' => 102,
            'parent_akun_id' => $validatedData['parent_akun_id'],
            'deskripsi' => $validatedData['deskripsi'],
            'amount' => $validatedData['amount'],
            'saldo' => $newSaldo,
        ]);

        Log::info('Data transaksi Bank berhasil diperbarui', ['id' => $transaksi->id]);

        // Perbarui jurnal berdasarkan metode pembayaran (Kas atau Bank)
        if ($transaksi->akun_keuangan_id == 101) {
            $this->createJournalEntry($transaksi);
        } elseif ($transaksi->akun_keuangan_id == 102) {
            $this->createBankJournalEntry($transaksi);
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi Bank berhasil diperbarui!');
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
        // Ambil akun Kas dan Pendapatan
        $kas = AkunKeuangan::where('id', 101)->first(); // Akun Kas
        $pendapatan = AkunKeuangan::where('id', 202)->first(); // Akun Pendapatan

        // Tentukan akun beban berdasarkan parent_akun_id
        $akunBebanId = $transaksi->parent_akun_id ?? $transaksi->akun_keuangan_id;

        if (!$kas || !$pendapatan || !$akunBebanId) {
            Log::error('Akun tidak ditemukan dalam database', [
                'kas' => $kas,
                'pendapatan' => $pendapatan,
                'akunBebanId' => $akunBebanId,
            ]);
            return;
        }

        // Hapus ledger lama untuk transaksi ini agar tidak duplikat
        Ledger::where('transaksi_id', $transaksi->id)->delete();

        // Jika transaksi adalah penerimaan
        if ($transaksi->type == 'penerimaan') {
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kas->id,
                'debit' => $transaksi->amount,
                'credit' => 0
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $pendapatan->id,
                'debit' => 0,
                'credit' => $transaksi->amount
            ]);
        }
        // Jika transaksi adalah pengeluaran
        else if ($transaksi->type == 'pengeluaran') {
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunBebanId,
                'debit' => $transaksi->amount,
                'credit' => 0
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kas->id,
                'debit' => 0,
                'credit' => $transaksi->amount
            ]);
        }

        // Hitung total saldo kas setelah transaksi
        $totalKas = Ledger::where('akun_keuangan_id', $kas->id)
            ->sum('debit') - Ledger::where('akun_keuangan_id', $kas->id)->sum('credit');

        Log::info('Jurnal berhasil diperbarui. Saldo kas terbaru: ' . $totalKas, [
            'id_transaksi' => $transaksi->id,
            'total_kas' => $totalKas
        ]);
    }


    private function createBankJournalEntry($transaksi)
    {
        // Ambil akun Bank dan Pendapatan
        $bank = AkunKeuangan::where('id', 102)->first(); // Akun Bank
        $pendapatan = AkunKeuangan::where('id', 202)->first(); // Akun Pendapatan

        // Tentukan akun beban berdasarkan parent_akun_id
        $akunBebanId = $transaksi->parent_akun_id ?? $transaksi->akun_keuangan_id;

        if (!$bank || !$pendapatan || !$akunBebanId) {
            Log::error('Akun tidak ditemukan dalam database', [
                'bank' => $bank,
                'pendapatan' => $pendapatan,
                'akunBebanId' => $akunBebanId,
            ]);
            return;
        }

        // Hapus ledger lama untuk transaksi ini agar tidak duplikat
        Ledger::where('transaksi_id', $transaksi->id)->delete();

        // Cari entri ledger berdasarkan transaksi ID
        $ledgerBank = Ledger::where('transaksi_id', $transaksi->id)
            ->where('akun_keuangan_id', $bank->id)
            ->first();

        $ledgerPendapatan = Ledger::where('transaksi_id', $transaksi->id)
            ->where('akun_keuangan_id', $pendapatan->id)
            ->first();

        $ledgerBeban = Ledger::where('transaksi_id', $transaksi->id)
            ->where('akun_keuangan_id', $akunBebanId)
            ->first();

        // Jika transaksi adalah penerimaan
        if ($transaksi->type == 'penerimaan') {
            if ($ledgerBank && $ledgerPendapatan) {
                // Update nilai jika sudah ada jurnal sebelumnya
                $ledgerBank->update(['debit' => $transaksi->amount, 'credit' => 0]);
                $ledgerPendapatan->update(['credit' => $transaksi->amount, 'debit' => 0]);
            } else {
                // Jika belum ada, buat jurnal baru
                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $bank->id,
                    'debit' => $transaksi->amount,
                    'credit' => 0
                ]);

                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $pendapatan->id,
                    'debit' => 0,
                    'credit' => $transaksi->amount
                ]);
            }
        }
        // Jika transaksi adalah pengeluaran
        else if ($transaksi->type == 'pengeluaran') {
            if ($ledgerBank && $ledgerBeban) {
                // Update jika sudah ada
                $ledgerBank->update(['credit' => $transaksi->amount, 'debit' => 0]);
                $ledgerBeban->update(['debit' => $transaksi->amount, 'credit' => 0]);
            } else {
                // Buat jurnal baru jika belum ada
                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $akunBebanId,
                    'debit' => $transaksi->amount,
                    'credit' => 0
                ]);

                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $bank->id,
                    'debit' => 0,
                    'credit' => $transaksi->amount
                ]);
            }
        }

        // Hitung total saldo bank setelah transaksi
        $totalBank = Ledger::where('akun_keuangan_id', $bank->id)
            ->sum('debit') - Ledger::where('akun_keuangan_id', $bank->id)->sum('credit');

        Log::info('Jurnal bank berhasil diperbarui. Saldo bank terbaru: ' . $totalBank, [
            'id_transaksi' => $transaksi->id,
            'total_bank' => $totalBank
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

    public function exportNota($id)
    {
        // Retrieve the transaction data based on ID
        $transaksi = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])->find($id);

        // Pastikan $transaksi ditemukan sebelum melanjutkan
        if (!$transaksi) {
            return redirect()->route('transaksi.index')->with('error', 'Transaksi tidak ditemukan');
        }

        // Mengambil data yang diperlukan dari transaksi
        $tanggal_transaksi = $transaksi->tanggal_transaksi;
        $jenis_transaksi = $transaksi->type;
        $akun = $transaksi->akunKeuangan ? $transaksi->akunKeuangan->nama_akun : 'N/A';
        $sub_akun = $transaksi->parentAkunKeuangan ? $transaksi->parentAkunKeuangan->nama_akun : 'N/A';

        // Generate the PDF from a view
        $pdf = Pdf::loadView('transaksi.nota', compact('transaksi', 'tanggal_transaksi', 'akun', 'sub_akun', 'jenis_transaksi'));

        // Return the PDF as a response for download
        return $pdf->download('Invoice_' . $transaksi->kode_transaksi . '.pdf');
    }

    public function exportAllPdf()
    {
        // Ambil user yang sedang login
        $user = auth()->user();

        // Query transaksi berdasarkan role dan bidang_name
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan', 'user');

        // Filter berdasarkan role 'Bidang'
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        // Ambil hasil query
        $transaksi = $transaksiQuery->get();

        // Pastikan ada data transaksi yang tersedia
        if ($transaksi->isEmpty()) {
            return redirect()->route('transaksi.index')->with('error', 'Tidak ada data transaksi untuk diunduh!.');
        }

        // Siapkan data untuk dikirim ke view PDF
        $data = [
            'transaksis' => $transaksi
        ];

        // Ambil bidang_name dari user login sebagai nama file PDF
        $bidangName = $user->bidang_name ?? 'Transaksi'; // Gunakan bidang_name user atau default 'Transaksi'

        // Load view untuk PDF, kirimkan data transaksi
        $pdf = Pdf::loadView('transaksi.export', $data);

        // Kembalikan file PDF untuk di-download
        return $pdf->download('Laporan_Keuangan_' . $bidangName . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $user = auth()->user();

        // Ambil filter bidang_name dari form
        $bidangName = $request->input('bidang_name', $user->bidang_name);

        // Ambil data transaksi berdasarkan filter bidang_name
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan');

        // Filter berdasarkan role 'Bidang' dan bidang_name
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $bidangName);
        }

        // Ambil data transaksi
        $transaksi = $transaksiQuery->get();

        // Pastikan ada data transaksi yang tersedia
        if ($transaksi->isEmpty()) {
            return redirect()->route('transaksi.index')->with('error', 'Tidak ada data transaksi untuk diekspor!.');
        }

        // Jika data tersedia, lanjutkan ekspor ke Excel
        return Excel::download(new TransaksisExport($bidangName), 'Laporan_Keuangan_' . $bidangName . '.xlsx');
    }

}
