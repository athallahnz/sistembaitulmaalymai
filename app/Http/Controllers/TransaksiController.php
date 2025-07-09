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
        $bidang_id = $user->bidang_name; // Ambil bidang_id dari user

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
            ->whereNull('parent_id') // Ambil akun tanpa parent
            ->whereNotIn('id', [101, 103, 104, 105, 201]) // Kecualikan ID tertentu
            ->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray();

        $role = $user->role;
        $bidang_name = $user->bidang_name;

        // Tentukan prefix berdasarkan bidang_id
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_id) {
                case 1: // Pendidikan
                    $prefix = 'SJD';
                    break;
                case 2: // Kemasjidan
                    $prefix = 'PND';
                    break;
                case 3: // Sosial
                    $prefix = 'SOS';
                    break;
                case 4: // Usaha
                    $prefix = 'UHA';
                    break;
                case 5: // Pembangunan
                    $prefix = 'BGN';
                    break;
            }
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH'; // Prefix untuk Bendahara
        }

        // Generate kode transaksi
        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));


        // Cek apakah pengguna adalah Bendahara
        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1011; // Akun Bank untuk Bendahara

            $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();
        } else {
            // Daftar akun Bank berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            // Pastikan bidang_id yang diberikan ada dalam daftar
            if (isset($akunKas[$bidang_id])) {
                $akun_keuangan_id = $akunKas[$bidang_id];

                $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                    ->where('bidang_name', $bidang_name) // Gunakan bidang_id sebagai referensi
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            } else {
                $lastSaldo = null; // Jika bidang_id tidak ditemukan, return null
            }
        }

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
            ->whereNull('parent_id') // Ambil akun tanpa parent
            ->whereNotIn('id', [101, 103, 104, 105, 201]) // Kecualikan ID tertentu
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

    public function storeTransaction(Request $request, $akun_keuangan_id, $parent_akun_id)
    {
        Log::info('Memulai proses penyimpanan transaksi', $request->all());

        // Get the current user's role
        $userRole = auth()->user()->role; // Assuming you have a method to get the user's role

        // Define validation rules
        $rules = [
            'kode_transaksi' => 'required|string|unique:transaksis,kode_transaksi',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ];

        // Add conditional validation for bidang_name based on user role
        if ($userRole === 'Bendahara') {
            // Skip bidang_name validation for Bendahara
            // No need to add it to the rules
        } else {
            // For other roles, ensure bidang_name is required and is an integer
            $rules['bidang_name'] = 'required|integer';
        }

        // Validate the request data
        $validatedData = $request->validate($rules);

        $bidang = $request->input('bidang_name');

        // Ambil saldo terakhir akun utama
        if (auth()->user()->role === 'Bendahara') {
            // Jika role adalah Bendahara, tidak menggunakan bidang_name
            $lastSaldoAkun = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->whereNull('bidang_name') // Mencari transaksi dengan bidang_name NULL
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil baris terakhir dalam hasil (data terbaru)
        } else {
            // Untuk role lain, gunakan bidang_name
            $lastSaldoAkun = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil baris terakhir dalam hasil (data terbaru)
        }

        $saldoSebelumnyaAkun = $lastSaldoAkun ? $lastSaldoAkun->saldo : 0;

        // Ambil saldo terakhir akun lawan (parent_akun_id)
        if (auth()->user()->role === 'Bendahara') {
            // Jika role adalah Bendahara, tidak menggunakan bidang_name
            $lastSaldoLawan = Transaksi::where('akun_keuangan_id', $parent_akun_id)
                ->whereNull('bidang_name') // Mencari transaksi dengan bidang_name NULL
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil baris terakhir dalam hasil (data terbaru)
        } else {
            // Untuk role lain, gunakan bidang_name
            $lastSaldoLawan = Transaksi::where('akun_keuangan_id', $parent_akun_id)
                ->where('bidang_name', $bidang) // Menggunakan bidang_name
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil baris terakhir dalam hasil (data terbaru)
        }

        $saldoSebelumnyaLawan = $lastSaldoLawan ? $lastSaldoLawan->saldo : 0;

        // Logging untuk debug
        Log::info("Saldo akun ID $akun_keuangan_id:", ['saldo' => $saldoSebelumnyaAkun]);
        Log::info("Saldo akun lawan ID $parent_akun_id:", ['saldo' => $saldoSebelumnyaLawan]);

        // Daftar akun yang dikenai aturan saldo tidak boleh 0 saat pengeluaran
        $akunTerbatas = [1011, 1012, 1013, 1014, 1015, 1022, 1023, 1024, 1025];

        // **Validasi saldo akun lawan (tidak boleh 0 saat melakukan pengeluaran)**
        if ($validatedData['type'] === 'pengeluaran' && in_array($parent_akun_id, $akunTerbatas) && $saldoSebelumnyaLawan == 0) {
            return redirect()->back()->with('error', 'Transaksi gagal! Akun lawan tidak memiliki saldo.');
        }

        // **Validasi saldo untuk pengeluaran (boleh jika saldo cukup, termasuk menjadi 0)**
        if ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] > $saldoSebelumnyaAkun) {
            return redirect()->back()->with('error', 'Jumlah pengeluaran tidak boleh melebihi saldo akun utama.');
        } elseif ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] == $saldoSebelumnyaAkun) {
            // Transaksi tetap diperbolehkan jika jumlah pengeluaran = saldo
            // Tidak perlu redirect atau error message
        }

        // Validasi saldo akun lawan untuk penerimaan: akun kas/bank tidak boleh minus
        if (
            $validatedData['type'] === 'penerimaan' &&
            $validatedData['amount'] > $saldoSebelumnyaLawan &&
            in_array($parent_akun_id, $akunTerbatas)
        ) {
            return redirect()->back()->with('error', 'Transaksi gagal! Saldo akun kas/bank tidak mencukupi untuk penerimaan.');
        }

        // Hitung saldo baru untuk akun utama
        $newSaldoAkun = $validatedData['type'] === 'penerimaan'
            ? $saldoSebelumnyaAkun + $validatedData['amount']
            : $saldoSebelumnyaAkun - $validatedData['amount'];

        // Hitung saldo baru untuk akun lawan (hanya jika ada parent akun)
        if (!empty($parent_akun_id)) {
            // Jika akun lawan adalah kas/bank, maka saldo harus berkurang saat penerimaan dan bertambah saat pengeluaran
            $newSaldoLawan = $validatedData['type'] === 'penerimaan'
                ? $saldoSebelumnyaLawan - $validatedData['amount']
                : $saldoSebelumnyaLawan + $validatedData['amount'];
        } else {
            $newSaldoLawan = null; // Jika tidak ada akun lawan, saldo tidak dihitung
        }

        // **Simpan transaksi utama**
        $transaksiAkun = new Transaksi();

        // Check user role and set bidang_name accordingly
        if (auth()->user()->role === 'Bendahara') {
            $transaksiAkun->bidang_name = null; // Set to null for Bendahara
        } else {
            $transaksiAkun->bidang_name = $validatedData['bidang_name']; // Use validated bidang_name for other roles
        }

        $transaksiAkun->kode_transaksi = $validatedData['kode_transaksi'];
        $transaksiAkun->tanggal_transaksi = $validatedData['tanggal_transaksi'];
        $transaksiAkun->type = $validatedData['type'];
        $transaksiAkun->akun_keuangan_id = $akun_keuangan_id;
        $transaksiAkun->parent_akun_id = $parent_akun_id;
        $transaksiAkun->deskripsi = $validatedData['deskripsi'];
        $transaksiAkun->amount = $validatedData['amount'];
        $transaksiAkun->saldo = $newSaldoAkun;
        $transaksiAkun->save();

        // **Simpan transaksi lawan secara otomatis**
        $transaksiLawan = new Transaksi();

        // Check user role and set bidang_name accordingly for the opposing transaction
        if (auth()->user()->role === 'Bendahara') {
            $transaksiLawan->bidang_name = null; // Set to null for Bendahara
        } else {
            $transaksiLawan->bidang_name = $validatedData['bidang_name']; // Use validated bidang_name for other roles
        }

        $transaksiLawan->kode_transaksi = $validatedData['kode_transaksi'] . '-LAWAN'; // Kode unik untuk transaksi lawan
        $transaksiLawan->tanggal_transaksi = $validatedData['tanggal_transaksi'];
        $transaksiLawan->type = $validatedData['type'] === 'penerimaan' ? 'pengeluaran' : 'penerimaan';
        $transaksiLawan->akun_keuangan_id = $parent_akun_id;
        $transaksiLawan->parent_akun_id = $akun_keuangan_id;
        $transaksiLawan->deskripsi = "(Lawan) " . $validatedData['deskripsi'];
        $transaksiLawan->amount = $validatedData['amount'];
        $transaksiLawan->saldo = $newSaldoLawan;
        $transaksiLawan->save();

        Log::info('Data transaksi berhasil disimpan', [
            'transaksi_utama_id' => $transaksiAkun->id,
            'transaksi_lawan_id' => $transaksiLawan->id,
        ]);

        // Simpan ke ledger untuk transaksi utama
        $ledgerAkun = new Ledger();
        $ledgerAkun->transaksi_id = $transaksiAkun->id;
        $ledgerAkun->akun_keuangan_id = $transaksiAkun->akun_keuangan_id;
        $ledgerAkun->debit = $transaksiAkun->type === 'penerimaan' ? $transaksiAkun->amount : 0;
        $ledgerAkun->credit = $transaksiAkun->type === 'pengeluaran' ? $transaksiAkun->amount : 0;
        $ledgerAkun->save();

        // Simpan ke ledger untuk transaksi lawan
        $ledgerLawan = new Ledger();
        $ledgerLawan->transaksi_id = $transaksiLawan->id;
        $ledgerLawan->akun_keuangan_id = $transaksiLawan->akun_keuangan_id;
        $ledgerLawan->debit = $transaksiLawan->type === 'penerimaan' ? $transaksiLawan->amount : 0;
        $ledgerLawan->credit = $transaksiLawan->type === 'pengeluaran' ? $transaksiLawan->amount : 0;
        $ledgerLawan->save();


        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Set bidang_id based on user role
        if ($user->role === 'Bendahara') {
            $bidang_id = null; // No bidang for Bendahara
            $akun_keuangan_id = 1011; // Use specific account for Bendahara
        } else {
            $bidang_id = $user->bidang_name; // Integer (ID bidang)
        }

        // Jika pengguna adalah Bendahara, gunakan akun keuangan khusus
        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1011;
        } else {
            // Mapping akun bank berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];
            $akun_keuangan_id = $akunKas[$bidang_id] ?? null;
        }

        // Jika akun_keuangan_id tidak ditemukan, kembalikan error
        if (!$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun keuangan tidak ditemukan untuk bidang ini.']);
        }

        // 🔍 Cek apakah akun_keuangan_id ada di database
        if (!DB::table('akun_keuangans')->where('id', $akun_keuangan_id)->exists()) {
            return back()->withErrors(['error' => "Akun keuangan ID $akun_keuangan_id tidak ditemukan."]);
        }

        // 🔍 Cek apakah parent_akun_id valid jika ada
        $parent_akun_id = $request->input('parent_akun_id');
        if ($parent_akun_id && !DB::table('akun_keuangans')->where('id', $parent_akun_id)->exists()) {
            return back()->withErrors(['error' => "Parent Akun ID $parent_akun_id tidak ditemukan."]);
        }

        return $this->storeTransaction($request, $akun_keuangan_id, $parent_akun_id);
    }

    public function storeBankTransaction(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            \Log::error("User  tidak terautentikasi.");
            return back()->withErrors(['error' => 'Silakan login terlebih dahulu.']);
        }

        \Log::info("User  Data:", $user->toArray());

        // Set bidang_id based on user role
        if ($user->role === 'Bendahara') {
            $bidang_id = null; // No bidang for Bendahara
            $akun_keuangan_id = 1021; // Use specific account for Bendahara
        } else {
            $bidang_id = $user->bidang_name; // Integer (ID bidang)
        }
        \Log::info("Bidang ID: $bidang_id");

        // Mapping akun bank berdasarkan bidang_id
        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1021; // Specific account for Bendahara
        } else {
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];
            $akun_keuangan_id = $akunBank[$bidang_id] ?? null; // Map based on bidang_id
        }

        \Log::info("Akun Keuangan ID: " . ($akun_keuangan_id ?? 'NULL'));

        // Jika akun_keuangan_id tidak ditemukan, kembalikan error
        if (!$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun bank tidak ditemukan untuk bidang ini.']);
        }

        // 🔍 Cek apakah parent_akun_id valid jika ada
        $parent_akun_id = $request->input('parent_akun_id');
        if ($parent_akun_id && !DB::table('akun_keuangans')->where('id', $parent_akun_id)->exists()) {
            return back()->withErrors(['error' => "Parent Akun ID $parent_akun_id tidak ditemukan."]);
        }

        // Merge additional data into the request
        $request->merge([
            'bidang_name' => $bidang_id, // Pastikan bidang_name dikirim sebagai bidang ID
            'akun_keuangan_id' => $akun_keuangan_id,
        ]);

        // Call the storeTransaction method to handle the actual transaction storage
        return $this->storeTransaction($request, $akun_keuangan_id, $parent_akun_id);
    }

    public function edit($id)
    {
        $transaksi = Transaksi::findOrFail($id);
        // Cari parent akun dari parent_akun_id
        $akunKeuangan = AkunKeuangan::find($transaksi->parent_akun_id)?->parent_id ?? $transaksi->parent_akun_id;

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id') // Ambil akun tanpa parent
            ->whereNotIn('id', [103, 104, 105,]) // Kecualikan ID tertentu
            ->get();

        // Ambil old parent_akun_id
        $oldParentAkunId = old('parent_akun_id', $transaksi->parent_akun_id ?? null);

        // Ambil semua akun keuangan
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->orderByRaw("FIELD(id, ?) DESC", [$oldParentAkunId]) // Urutkan berdasarkan akun yang memiliki ID tersebut
            ->get()
            ->groupBy('parent_id');


        return view('transaksi.edit', compact('transaksi', 'akunKeuangan', 'akunTanpaParent', 'oldParentAkunId', 'akunDenganParent'));
    }

    public function update(Request $request, $id)
    {
        // Logging awal untuk debugging
        Log::info('🚀 Masuk ke updateKasTransaction', ['id' => $id, 'request' => $request->all()]);

        // Validasi data input
        try {
            $validatedData = $request->validate([
                'bidang_name' => 'required|integer', // Pastikan bidang_name adalah integer
                'kode_transaksi' => 'required|string',
                'tanggal_transaksi' => 'required|date',
                'type' => 'required|in:penerimaan,pengeluaran',
                'akun_keuangan_id' => 'required|integer',
                'parent_akun_id' => 'nullable|integer',
                'deskripsi' => 'required|string',
                'amount' => 'required|numeric|min:0',
            ]);
            Log::info('✅ Validasi berhasil', ['validatedData' => $validatedData]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validasi gagal', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors());
        }

        // Cari transaksi berdasarkan ID
        $transaksi = Transaksi::find($id);
        if (!$transaksi) {
            Log::error('❌ Transaksi tidak ditemukan', ['id' => $id]);
            return back()->withErrors(['error' => 'Transaksi tidak ditemukan.']);
        }
        Log::info('✅ Transaksi ditemukan', ['transaksi' => $transaksi]);

        // Ambil user yang sedang login
        $user = auth()->user();

        // Jika pengguna adalah Bendahara, gunakan akun keuangan khusus
        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1011;
        } else {
            // Mapping akun Kas berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];
            $akun_keuangan_id = $akunKas[$validatedData['bidang_name']] ?? null;
        }

        // Pastikan akun_keuangan_id ditemukan
        if (!$akun_keuangan_id) {
            return back()->withErrors(['bidang_name' => 'Bidang tidak valid atau tidak memiliki akun keuangan.']);
        }

        // Ambil transaksi yang akan diperbarui
        $transaksi = Transaksi::findOrFail($id);

        // Ambil saldo terakhir sebelum transaksi ini
        $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
            ->where('bidang_name', $validatedData['bidang_name'])
            ->where('id', '!=', $id) // Hindari transaksi yang sedang diperbarui
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0;
        Log::info('🔄 Saldo akun Kas sebelum update', ['saldoKas' => $saldoKas]);

        // Cek jika pengeluaran melebihi saldo
        if ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] > $saldoKas) {
            Log::warning('⚠️ Pengeluaran melebihi saldo', [
                'amount' => $validatedData['amount'],
                'saldoKas' => $saldoKas
            ]);
            return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akun Kas.']);
        }

        // Hitung saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoKas;
        if ($validatedData['type'] === 'penerimaan') {
            $newSaldo += $validatedData['amount'];
        } else {
            $newSaldo -= $validatedData['amount'];
        }

        // Update transaksi dalam database
        try {
            $transaksi->update([
                'bidang_name' => $validatedData['bidang_name'], // Tetap menggunakan bidang_name
                'kode_transaksi' => $validatedData['kode_transaksi'],
                'tanggal_transaksi' => $validatedData['tanggal_transaksi'],
                'type' => $validatedData['type'],
                'akun_keuangan_id' => $akun_keuangan_id, // Pastikan ini untuk akun Kas
                'parent_akun_id' => $validatedData['parent_akun_id'],
                'deskripsi' => $validatedData['deskripsi'],
                'amount' => $validatedData['amount'],
                'saldo' => $newSaldo,
            ]);
            Log::info('✅ Data transaksi Kas berhasil diperbarui', ['id' => $transaksi->id, 'saldo_baru' => $newSaldo]);
        } catch (\Exception $e) {
            Log::error('❌ Gagal update transaksi', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Gagal update transaksi.']);
        }

        // Perbarui jurnal berdasarkan metode pembayaran (Kas)
        try {
            $this->createJournalEntry($transaksi);
            Log::info('✅ Jurnal transaksi berhasil diperbarui', ['id' => $transaksi->id]);
        } catch (\Exception $e) {
            Log::error('❌ Gagal update jurnal transaksi', ['error' => $e->getMessage()]);
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi Kas berhasil diperbarui!');
    }

    public function updateBankTransaction(Request $request, $id)
    {
        // Logging untuk debugging
        Log::info('Memulai proses update transaksi Bank', ['id' => $id, 'request' => $request->all()]);

        // Validasi data input
        $validatedData = $request->validate([
            'bidang_name' => 'required|integer|exists:bidangs,id', // bidang_name harus berupa integer dan ada di tabel bidangs
            'kode_transaksi' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'akun_keuangan_id' => 'required|integer',
            'parent_akun_id' => 'nullable|integer',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        // Pastikan akun yang digunakan termasuk akunBank dengan prefix 102
        $akunBankList = [1021, 1022, 1023, 1024, 1025]; // Menggunakan prefix 102
        if (!in_array($validatedData['akun_keuangan_id'], $akunBankList)) {
            return back()->withErrors(['akun_keuangan_id' => 'Akun yang dipilih bukan akunBank yang valid.']);
        }

        // Ambil transaksi yang akan diperbarui
        $transaksi = Transaksi::findOrFail($id);

        // Ambil saldo terakhir sebelum transaksi ini untuk akunBank terkait
        $lastSaldo = Transaksi::where('akun_keuangan_id', $validatedData['akun_keuangan_id'])
            ->where('bidang_name', $validatedData['bidang_name']) // Sesuai bidang_name (integer)
            ->where('id', '!=', $id) // Hindari transaksi yang sedang diperbarui
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoBank = $lastSaldo ? $lastSaldo->saldo : 0;

        Log::info('Saldo akunBank sebelum update:', ['akun_keuangan_id' => $validatedData['akun_keuangan_id'], 'saldo' => $saldoBank]);

        // Cek jika pengeluaran melebihi saldo
        if ($validatedData['type'] === 'pengeluaran' && $validatedData['amount'] > $saldoBank) {
            return back()->withErrors(['amount' => 'Jumlah pengeluaran tidak boleh melebihi saldo akunBank.']);
        }

        // Hitung saldo baru berdasarkan tipe transaksi
        $newSaldo = $saldoBank;
        if ($validatedData['type'] === 'penerimaan') {
            $newSaldo += $validatedData['amount'];
        } else {
            $newSaldo -= $validatedData['amount'];
        }

        // Update transaksi dengan akunBank yang valid
        $transaksi->update([
            'bidang_name' => $validatedData['bidang_name'], // Menggunakan bidang_name yang berupa integer
            'kode_transaksi' => $validatedData['kode_transaksi'],
            'tanggal_transaksi' => $validatedData['tanggal_transaksi'],
            'type' => $validatedData['type'],
            'akun_keuangan_id' => $validatedData['akun_keuangan_id'], // Sesuai dengan akunBank yang dipilih
            'parent_akun_id' => $validatedData['parent_akun_id'],
            'deskripsi' => $validatedData['deskripsi'],
            'amount' => $validatedData['amount'],
            'saldo' => $newSaldo,
        ]);

        Log::info('Data transaksi Bank berhasil diperbarui', ['id' => $transaksi->id]);

        // Perbarui jurnal khusus untuk akunBank
        $this->createBankJournalEntry($transaksi);

        return redirect()->route('transaksi.index')->with('success', 'Transaksi Bank berhasil diperbarui!');
    }

    public function getData()
    {
        $user = auth()->user();

        // Ambil transaksi berdasarkan role
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan', 'user') // Pastikan relasi sudah dimuat
            ->where('kode_transaksi', 'not like', '%-LAWAN'); // Hindari transaksi lawan

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
        // Ambil user yang sedang login
        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id

        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1021;
        } else {
            $akunBank = [
                1 => 1022,
                2 => 1023,
                3 => 1024,
                4 => 1025,
            ];
            $akun_keuangan_id = $akunBank[$bidang_id] ?? null;
        }
        // Pastikan akun_keuangan_id ditemukan
        if (!$akun_keuangan_id) {
            return back()->withErrors(['bidang_name' => 'Bidang tidak valid atau tidak memiliki akun keuangan.']);
        }

        // Ambil akun Kas dan Pendapatan
        $kas = AkunKeuangan::where('id', $akun_keuangan_id)->first(); // Akun Kas
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
        // Ambil transaksi utama
        $transaksi = Transaksi::findOrFail($id);

        // Cek dan ambil transaksi lawan berdasarkan pola kode_transaksi
        $kodeLawan = $transaksi->kode_transaksi . '-LAWAN';
        $transaksiLawan = Transaksi::where('kode_transaksi', $kodeLawan)->first();

        // Hapus ledger untuk transaksi utama
        Ledger::where('transaksi_id', $transaksi->id)->delete();

        // Hapus ledger untuk transaksi lawan jika ada
        if ($transaksiLawan) {
            Ledger::where('transaksi_id', $transaksiLawan->id)->delete();
            $transaksiLawan->delete(); // Hapus transaksi lawan
        }

        // Hapus transaksi utama
        $transaksi->delete();

        return redirect()->route('transaksi.index.bidang')->with('success', 'Transaksi dan data terkait berhasil dihapus.');
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
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan', 'user')
            ->where('kode_transaksi', 'not like', '%-LAWAN'); // Hindari transaksi lawan

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

        // Daftar akun yang termasuk Kas & Bank
        $akunKasBidang = [1012, 1013, 1014, 1015];
        $akunBendahara = 1011;

        // Ambil data transaksi yang hanya terkait dengan akun Kas & Bank
        $transaksiQuery = Transaksi::with('akunKeuangan', 'parentAkunKeuangan');

        // Hanya ambil transaksi dari akun yang ada dalam daftar akun kas
        if ($user->role === 'Bendahara') {
            // Jika user adalah Bendahara, ambil semua akun kas termasuk akun 1011
            $transaksiQuery->whereIn('akun_keuangan_id', array_merge([$akunBendahara], $akunKasBidang));
        } else {
            // Jika bukan bendahara, hanya ambil akun kas bidang
            $transaksiQuery->whereIn('akun_keuangan_id', $akunKasBidang);
        }

        // Filter berdasarkan role 'Bidang' dan bidang_name
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $bidangName);
        }

        // Ambil data transaksi
        $transaksi = $transaksiQuery->get();

        // Debugging: Cek apakah data transaksi sudah benar
        if ($transaksi->isEmpty()) {
            return redirect()->route('transaksi.index')->with('error', 'Tidak ada data transaksi untuk diekspor!');
        }

        // Jika data tersedia, lanjutkan ekspor ke Excel
        return Excel::download(new TransaksisExport($bidangName), 'Laporan_Keuangan_' . $bidangName . '.xlsx');
    }

}
