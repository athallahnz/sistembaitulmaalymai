<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use App\Exports\BukuKasBankExport;
use App\Services\LaporanKeuanganService;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\SidebarSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class TransaksiController extends Controller
{
    private const AKUN_IKHTISAR = 9001; // akun sementara / penutup
    private const AKUN_ASET_NETO_TIDAK_TERIKAT = 4001;

    public function index()
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name; // integer id bidang
        $role = $user->role;
        $bidangName = $user->bidang_name;

        $lapService = new LaporanKeuanganService();

        // ==========================
        // Ambil transaksi (untuk tabel)
        // ==========================
        $transaksiQuery = Transaksi::with('parentAkunKeuangan', 'user');

        if ($role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $bidangName);
        }

        $transaksi = $transaksiQuery->get();

        // Semua akun (kalau masih dipakai di tempat lain)
        $akunKeuangan = AkunKeuangan::all();

        // ==========================
        // 🔹 Aset Neto: Induk & Anak (untuk dropdown tujuan OB)
        // ==========================
        $equityTanpaParent = AkunKeuangan::where('tipe_akun', 'equity')
            ->whereNull('parent_id')
            ->orderBy('kode_akun')
            ->get();

        $equityDenganParent = AkunKeuangan::where('tipe_akun', 'equity')
            ->whereNotNull('parent_id')
            ->orderBy('kode_akun')
            ->get()
            ->groupBy('parent_id');

        // ==========================
        // 🔹 Akun Keuangan untuk dropdown (modal tambah transaksi)
        $akunTanpaParent = AkunKeuangan::whereNull('parent_id')
            ->whereIn('tipe_akun', ['asset', 'revenue', 'expense', 'equity']) // contoh
            ->orderBy('kode_akun')
            ->get();

        $akunDenganParent = AkunKeuangan::whereNotNull('parent_id')
            ->whereIn('tipe_akun', ['asset', 'revenue', 'expense', 'equity'])
            ->orderBy('kode_akun')
            ->get()
            ->groupBy('parent_id');

        $akunNonKas = AkunKeuangan::where('is_kas_bank', false)
            ->orderBy('kode_akun')
            ->get();

        // ==========================
        // 🔹 Prefix kode transaksi (transaksi biasa)
        // ==========================
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidangId) {
                case 1:
                    $prefix = 'SJD';
                    break; // Pendidikan
                case 2:
                    $prefix = 'PND';
                    break; // Kemasjidan
                case 3:
                    $prefix = 'SOS';
                    break; // Sosial
                case 4:
                    $prefix = 'UHA';
                    break; // Usaha
                case 5:
                    $prefix = 'BGN';
                    break; // Pembangunan
            }
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH';
        }

        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // ==========================
        // 🔹 Mapping Kas & Bank per role/bidang
        // ==========================
        $kasMap = [
            1 => 1012,
            2 => 1013,
            3 => 1014,
            4 => 1015,
        ];

        $bankMap = [
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ];

        if ($role === 'Bendahara') {
            $kasAkunId = 1011; // Kas Bendahara
            $bankAkunId = 1021; // Bank Bendahara
        } else {
            $kasAkunId = $kasMap[$bidangId] ?? null;
            $bankAkunId = $bankMap[$bidangId] ?? null;
        }

        $kasAkun = $kasAkunId ? AkunKeuangan::find($kasAkunId) : null;
        $bankAkun = $bankAkunId ? AkunKeuangan::find($bankAkunId) : null;

        // ==========================
        // 🔹 Saldo Kas via LaporanKeuanganService
        // ==========================
        if ($kasAkun) {
            $saldoKas = $lapService->getSaldoAkunSampai($kasAkun, Carbon::now());
        } else {
            $saldoKas = 0;
        }

        // 🔹 Saldo Bank via LaporanKeuanganService
        if ($bankAkun) {
            $saldoBank = $lapService->getSaldoAkunSampai($bankAkun, Carbon::now());
        } else {
            $saldoBank = 0;
        }

        // ==========================
        // 🔹 Data untuk modal TRANSFER
        // ==========================
        // ==========================

        // 🔹 Daftar akun kas/bank tujuan (semua anak kas/bank)
        $kasBankTujuan = AkunKeuangan::where('is_kas_bank', 1)
            ->whereNotNull('parent_id')
            ->orderBy('kode_akun')
            ->get();

        // kode transaksi khusus transfer
        $kodeTransaksiTransfer = 'TRF-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        return view('transaksi.index', compact(
            'transaksi',
            'equityDenganParent',
            'equityTanpaParent',
            'akunTanpaParent',
            'akunDenganParent',
            'akunNonKas',
            'bidangName',
            'akunKeuangan',
            'kodeTransaksi',
            'saldoKas',
            'saldoBank',
            'kasAkun',
            'bankAkun',
            'kasBankTujuan',
            'kodeTransaksiTransfer',
            'bankAkunId',
        ));
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

    /***********************
     * Helper & Guards
     ***********************/

    /** Apakah akun butuh cek saldo saat menjadi sumber (non-kas-bank asset, dll.) */
    private function needsSaldoCheck(int $akunId): bool
    {
        $akun = AkunKeuangan::find($akunId);
        if (!$akun)
            return false;
        return ($akun->tipe_akun ?? '') === 'asset';
    }

    /** Daftar akun Kas/Bank (pindahkan ke config bila perlu) */
    private function isKasBank(int $akunId): bool
    {
        $akunKasBank = [1011, 1012, 1013, 1014, 1015, 1021, 1022, 1023, 1024, 1025];
        return in_array($akunId, $akunKasBank, true);
    }

    protected function getSaldoLedgerSampaiTanggal(
        ?int $akunId,
        string $tanggal,
        ?int $bidangValue,
        string $userRole
    ): float {
        if (!$akunId)
            return 0.0;

        // Ambil akun untuk saldo_normal
        $akun = AkunKeuangan::find($akunId);
        if (!$akun)
            return 0.0;

        $q = Ledger::where('akun_keuangan_id', $akunId)
            ->whereHas('transaksi', function ($tr) use ($tanggal, $bidangValue, $userRole) {
                $tr->whereDate('tanggal_transaksi', '<=', $tanggal);

                // Bidang filter
                if ($userRole !== 'Bendahara') {
                    $tr->where('bidang_name', $bidangValue);
                } else {
                    $tr->whereNull('bidang_name');
                }
            });

        $debit = (float) $q->sum('debit');
        $credit = (float) $q->sum('credit');

        return $akun->saldo_normal === 'debit'
            ? ($debit - $credit)
            : ($credit - $debit);
    }


    /** Ambil id akun Kas default berdasar role & bidang */
    protected function getDefaultKasAkunId(string $role, ?int $bidangId): ?int
    {
        if ($role === 'Bendahara')
            return 1011;
        $map = [1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015];
        return $bidangId && isset($map[$bidangId]) ? $map[$bidangId] : null;
    }

    /** Ambil id akun Bank default berdasar role & bidang */
    protected function getDefaultBankAkunId(string $role, ?int $bidangId): ?int
    {
        if ($role === 'Bendahara')
            return 1021;
        $map = [1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025];
        return $bidangId && isset($map[$bidangId]) ? $map[$bidangId] : null;
    }

    /** Prefix kode transaksi (ikut mapping kamu) */
    protected function makeKodePrefix(string $role, ?int $bidangId): string
    {
        if ($role === 'Bidang') {
            return match ($bidangId) {
                1 => 'SJD', // Pendidikan
                2 => 'PND', // Kemasjidan
                3 => 'SOS', // Sosial
                4 => 'UHA', // Usaha
                5 => 'BGN', // Pembangunan
                default => '',
            };
        }
        return $role === 'Bendahara' ? 'BDH' : '';
    }

    /***********************
     * Core Create Flow
     ***********************/
    public function storeTransaction(Request $request, $akun_keuangan_id, $parent_akun_id)
    {
        Log::info('Memulai proses penyimpanan transaksi', $request->all());

        $userRole = auth()->user()->role ?? 'Guest';

        $rules = [
            'kode_transaksi' => 'required|string|unique:transaksis,kode_transaksi',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ];
        if ($userRole !== 'Bendahara') {
            $rules['bidang_name'] = 'required|integer';
        }
        $validated = $request->validate($rules);

        // 2) Konteks bidang
        $bidangValue = ($userRole === 'Bendahara') ? null : (int) $validated['bidang_name'];

        // 3) Casting aman
        $akun_keuangan_id = (int) $akun_keuangan_id;
        $parent_akun_id = $parent_akun_id ? (int) $parent_akun_id : null;
        $tanggal = Carbon::parse($validated['tanggal_transaksi'])->toDateString();
        $amount = (float) $validated['amount'];
        $tipe = $validated['type']; // 'penerimaan' | 'pengeluaran'

        // 4) Ambil saldo sebelumnya via KOLOM `saldo` (<= cutoff)
        $saldoSebelumnyaAkun = $this->getSaldoLedgerSampaiTanggal(
            $akun_keuangan_id,
            $tanggal,
            $bidangValue,
            $userRole
        );

        $saldoSebelumnyaLawan = $this->getSaldoLedgerSampaiTanggal(
            $parent_akun_id,
            $tanggal,
            $bidangValue,
            $userRole
        );

        Log::info("Saldo akun $akun_keuangan_id sebelum:", ['saldo' => $saldoSebelumnyaAkun]);
        Log::info("Saldo akun lawan " . ($parent_akun_id ?? 'NULL') . " sebelum:", ['saldo' => $saldoSebelumnyaLawan]);

        // 5) Validasi limit saldo untuk PENGELUARAN (akun utama/sumber form)
        if ($tipe === 'pengeluaran' && $amount > $saldoSebelumnyaAkun) {
            return back()->with('error', 'Jumlah pengeluaran tidak boleh melebihi saldo akun utama.');
        }

        // 6) Validasi PENERIMAAN: saldo SUMBER (parent) wajib cukup bila parent Kas/Bank
        if ($tipe === 'penerimaan' && $parent_akun_id) {
            if ($this->isKasBank($parent_akun_id)) {
                if ($amount > $saldoSebelumnyaLawan) {
                    return back()->with('error', 'Saldo sumber (Kas/Bank) tidak mencukupi untuk transfer.');
                }
            } elseif ($this->needsSaldoCheck($parent_akun_id) && $amount > $saldoSebelumnyaLawan) {
                return back()->with('error', 'Jumlah penerimaan tidak boleh melebihi saldo akun asal (lawan).');
            }
        }

        // 7) Hitung saldo baru
        $newSaldoAkun = ($tipe === 'penerimaan')
            ? $saldoSebelumnyaAkun + $amount
            : $saldoSebelumnyaAkun - $amount;

        $newSaldoLawan = null;
        if ($parent_akun_id) {
            $newSaldoLawan = ($tipe === 'penerimaan')
                ? $saldoSebelumnyaLawan - $amount   // uang keluar dari lawan
                : $saldoSebelumnyaLawan + $amount;  // uang masuk ke lawan
            if ($newSaldoLawan === null || is_nan($newSaldoLawan))
                $newSaldoLawan = 0.0;
        }

        // 8) Guard negatif untuk Kas/Bank (akun sumber pada PENGELUARAN)
        if ($tipe === 'pengeluaran' && $this->isKasBank($akun_keuangan_id) && $newSaldoAkun < 0) {
            return back()->with('error', 'Transaksi gagal! Saldo Kas/Bank tidak boleh negatif.');
        }

        // 9) Guard negatif untuk sisi LAWAN yang Kas/Bank (semua skenario)
        if ($parent_akun_id && $this->isKasBank($parent_akun_id) && $newSaldoLawan !== null && $newSaldoLawan < 0) {
            return back()->with('error', 'Transaksi gagal! Saldo Kas/Bank (sumber) tidak boleh negatif.');
        }

        // 10) Simpan atomik
        DB::transaction(function () use ($validated, $akun_keuangan_id, $parent_akun_id, $bidangValue, $tanggal, $amount, $tipe, $newSaldoAkun, $newSaldoLawan) {
            $userId = auth()->id();

            // Utama
            $trxAkun = Transaksi::create([
                'bidang_name' => $bidangValue,
                'kode_transaksi' => $validated['kode_transaksi'],
                'tanggal_transaksi' => $tanggal,
                'type' => $tipe,
                'akun_keuangan_id' => $akun_keuangan_id,
                'parent_akun_id' => $parent_akun_id,
                'deskripsi' => $validated['deskripsi'],
                'amount' => $amount,
                'saldo' => (float) $newSaldoAkun,
                'user_id' => $userId,
                'updated_by' => $userId,
            ]);

            // Lawan (jika ada)
            if ($parent_akun_id) {
                $typeLawan = ($tipe === 'penerimaan') ? 'pengeluaran' : 'penerimaan';
                $trxLawan = Transaksi::create([
                    'bidang_name' => $bidangValue,
                    'kode_transaksi' => $validated['kode_transaksi'] . '-LAWAN',
                    'tanggal_transaksi' => $tanggal,
                    'type' => $typeLawan,
                    'akun_keuangan_id' => $parent_akun_id,
                    'parent_akun_id' => $akun_keuangan_id,
                    'deskripsi' => $validated['deskripsi'],
                    'amount' => $amount,
                    'saldo' => (float) $newSaldoLawan,
                    'user_id' => $userId,
                    'updated_by' => $userId,
                ]);

                // Ledger lawan
                Ledger::create([
                    'transaksi_id' => $trxLawan->id,
                    'akun_keuangan_id' => $trxLawan->akun_keuangan_id,
                    'debit' => $trxLawan->type === 'penerimaan' ? $amount : 0,
                    'credit' => $trxLawan->type === 'pengeluaran' ? $amount : 0,
                ]);
            }

            // Ledger utama
            Ledger::create([
                'transaksi_id' => $trxAkun->id,
                'akun_keuangan_id' => $trxAkun->akun_keuangan_id,
                'debit' => $trxAkun->type === 'penerimaan' ? $amount : 0,
                'credit' => $trxAkun->type === 'pengeluaran' ? $amount : 0,
            ]);

            Log::info('Data transaksi berhasil disimpan', [
                'transaksi_utama_id' => $trxAkun->id,
            ]);
        });

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan!');
    }

    /**
     * 🔍 Preview Surplus / Defisit
     * Dipanggil saat modal dibuka
     */
    public function checkAdjustment(Request $request)
    {
        $tanggal = Carbon::parse($request->tanggal)->toDateString();
        $bidangName = auth()->user()->role === 'Bidang' ? auth()->user()->bidang_name : null;

        $exists = Transaksi::where('type', 'penyesuaian')
            ->where('bidang_name', $bidangName)
            ->whereYear('tanggal_transaksi', Carbon::parse($tanggal)->year)
            ->exists();

        return response()->json(['exists' => $exists]);
    }


    public function previewAdjustment(Request $request)
    {
        $request->validate(['tanggal' => ['required', 'date']]);

        $tanggal = Carbon::parse($request->tanggal)->toDateString();
        $user = auth()->user();
        $bidangName = $user->role === 'Bidang' ? $user->bidang_name : null;

        // Validasi: tanggal harus awal tahun
        $awalTahun = Carbon::parse($tanggal)->startOfYear()->toDateString();
        abort_if($tanggal != $awalTahun, 422, 'Penyesuaian hanya boleh tanggal awal tahun.');

        // Hitung pendapatan & beban
        $data = DB::selectOne("
        SELECT
            SUM(CASE WHEN ak.kategori_psak = 'pendapatan'
                THEN l.credit - l.debit ELSE 0 END) AS pendapatan,
            SUM(CASE WHEN ak.kategori_psak = 'beban'
                THEN l.debit - l.credit ELSE 0 END) AS beban
        FROM ledgers l
        JOIN akun_keuangans ak ON ak.id = l.akun_keuangan_id
        JOIN transaksis t ON t.id = l.transaksi_id
        WHERE t.tanggal_transaksi <= ?
        " . ($bidangName ? "AND t.bidang_name = ?" : "AND t.bidang_name IS NULL") . "
    ", $bidangName ? [$tanggal, $bidangName] : [$tanggal]);

        $pendapatan = (float) ($data->pendapatan ?? 0);
        $beban      = (float) ($data->beban ?? 0);
        $surplus    = $pendapatan - $beban;

        return response()->json([
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'surplus_defisit' => $surplus,
            'status' => $surplus > 0 ? 'SURPLUS' : ($surplus < 0 ? 'DEFISIT' : 'NOL'),
        ]);
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate(['tanggal' => ['required', 'date']]);
        $tanggal = Carbon::parse($request->tanggal)->toDateString();
        $user = auth()->user();
        $bidangName = $user->role === 'Bidang' ? $user->bidang_name : null;

        // Validasi: tanggal harus awal tahun
        $awalTahun = Carbon::parse($tanggal)->startOfYear()->toDateString();
        abort_if($tanggal != $awalTahun, 422, 'Penyesuaian hanya boleh tanggal awal tahun.');

        // ==== GUARD: sudah pernah penyesuaian untuk tahun & bidang ini? ====
        $exists = Transaksi::where('type', 'penyesuaian')
            ->where('bidang_name', $bidangName)
            ->whereYear('tanggal_transaksi', Carbon::parse($tanggal)->year)
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Penyesuaian sudah pernah dilakukan untuk tahun ini.'
            ], 422);
        }

        // Hitung surplus / defisit
        $data = DB::table('ledgers as l')
            ->join('akun_keuangans as ak', 'ak.id', '=', 'l.akun_keuangan_id')
            ->join('transaksis as t', 't.id', '=', 'l.transaksi_id')
            ->where('t.tanggal_transaksi', '<=', $tanggal)
            ->when($bidangName, fn($q) => $q->where('t.bidang_name', $bidangName))
            ->selectRaw("
            SUM(CASE WHEN ak.kategori_psak = 'pendapatan' THEN l.credit - l.debit ELSE 0 END) AS pendapatan,
            SUM(CASE WHEN ak.kategori_psak = 'beban' THEN l.debit - l.credit ELSE 0 END) AS beban
        ")->first();

        $surplus = ((float) $data->pendapatan) - ((float) $data->beban);
        if (abs($surplus) < 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada surplus atau defisit untuk disesuaikan.'
            ], 422);
        }

        // Transaksi penyesuaian (sama seperti sebelumnya)
        DB::transaction(function () use ($tanggal, $surplus, $bidangName) {
            $nilai = abs($surplus);
            $trx = Transaksi::create([
                'kode_transaksi' => 'ADJ-' . date('Ymd-His'),
                'tanggal_transaksi' => $tanggal,
                'type' => 'penyesuaian',
                'deskripsi' => 'Penyesuaian Surplus / Defisit Hasil Aktivitas',
                'user_id' => auth()->id(),
                'updated_by' => auth()->id(),
                'amount' => $nilai,
                'bidang_name' => $bidangName,
            ]);

            if ($surplus > 0) {
                Ledger::insert([
                    ['transaksi_id' => $trx->id, 'akun_keuangan_id' => self::AKUN_IKHTISAR, 'debit' => $nilai, 'credit' => 0],
                    ['transaksi_id' => $trx->id, 'akun_keuangan_id' => self::AKUN_ASET_NETO_TIDAK_TERIKAT, 'debit' => 0, 'credit' => $nilai],
                ]);
            } else {
                Ledger::insert([
                    ['transaksi_id' => $trx->id, 'akun_keuangan_id' => self::AKUN_ASET_NETO_TIDAK_TERIKAT, 'debit' => $nilai, 'credit' => 0],
                    ['transaksi_id' => $trx->id, 'akun_keuangan_id' => self::AKUN_IKHTISAR, 'debit' => 0, 'credit' => $nilai],
                ]);
            }

            $cek = DB::selectOne("SELECT ABS(SUM(debit) - SUM(credit)) AS selisih FROM ledgers WHERE transaksi_id = ?", [$trx->id]);
            abort_if($cek->selisih > 0.01, 500, 'Jurnal tidak balance.');
        });

        return response()->json(['success' => true, 'message' => 'Penyesuaian berhasil diposting.']);
    }


    /***********************
     * Entry Points
     ***********************/
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::error('User tidak terautentikasi.');
            return back()->withErrors(['error' => 'Silakan login terlebih dahulu.']);
        }

        $role = $user->role;
        $bidang_id = $user->bidang_name; // integer id bidang

        // Tentukan akun kas default
        $akun_keuangan_id = $this->getDefaultKasAkunId($role, is_numeric($bidang_id) ? (int) $bidang_id : null);
        if (!$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun keuangan (Kas) tidak ditemukan untuk bidang ini.']);
        }

        // Pastikan akun ada
        if (!DB::table('akun_keuangans')->where('id', $akun_keuangan_id)->exists()) {
            return back()->withErrors(['error' => "Akun keuangan ID $akun_keuangan_id tidak ditemukan."]);
        }

        // Validasi parent_akun_id jika ada
        $parent_akun_id = $request->input('parent_akun_id');
        if ($parent_akun_id && !DB::table('akun_keuangans')->where('id', $parent_akun_id)->exists()) {
            return back()->withErrors(['error' => "Parent Akun ID $parent_akun_id tidak ditemukan."]);
        }

        // Wajib akun lawan untuk penerimaan ke kas
        if ($request->input('type') === 'penerimaan' && empty($parent_akun_id)) {
            return back()->withErrors(['parent_akun_id' => 'Akun lawan wajib diisi untuk transaksi penerimaan.']);
        }

        // Merge konteks agar validasi & saldo konsisten
        $request->merge([
            'bidang_name' => $role === 'Bendahara' ? null : $bidang_id,
            'akun_keuangan_id' => $akun_keuangan_id,
        ]);

        return $this->storeTransaction($request, $akun_keuangan_id, $parent_akun_id);
    }

    public function storeBankTransaction(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::error('User tidak terautentikasi.');
            return back()->withErrors(['error' => 'Silakan login terlebih dahulu.']);
        }

        $role = $user->role;
        $bidang_id = $user->bidang_name;

        // Tentukan akun bank default
        $akun_keuangan_id = $this->getDefaultBankAkunId($role, is_numeric($bidang_id) ? (int) $bidang_id : null);
        if (!$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun bank tidak ditemukan untuk bidang ini.']);
        }

        // Validasi parent_akun_id jika ada
        $parent_akun_id = $request->input('parent_akun_id');
        if ($parent_akun_id && !DB::table('akun_keuangans')->where('id', $parent_akun_id)->exists()) {
            return back()->withErrors(['error' => "Parent Akun ID $parent_akun_id tidak ditemukan."]);
        }

        // Wajib akun lawan untuk penerimaan ke bank
        if ($request->input('type') === 'penerimaan' && empty($parent_akun_id)) {
            return back()->withErrors(['parent_akun_id' => 'Akun lawan wajib diisi untuk transaksi penerimaan.']);
        }

        // Merge konteks
        $request->merge([
            'bidang_name' => $role === 'Bendahara' ? null : $bidang_id,
            'akun_keuangan_id' => $akun_keuangan_id,
        ]);

        return $this->storeTransaction($request, $akun_keuangan_id, $parent_akun_id);
    }

    public function edit($id)
    {
        $transaksi = Transaksi::findOrFail($id);

        // Parent akun dari parent_akun_id (tetap seperti semula)
        $akunKeuangan = AkunKeuangan::find($transaksi->parent_akun_id)?->parent_id ?? $transaksi->parent_akun_id;

        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id')
            ->whereNotIn('id', [103, 104, 105]) // sesuaikan pengecualian
            ->get();

        $oldParentAkunId = old('parent_akun_id', $transaksi->parent_akun_id ?? null);

        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->orderByRaw("FIELD(id, ?) DESC", [$oldParentAkunId])
            ->get()
            ->groupBy('parent_id');

        return view('transaksi.edit', compact('transaksi', 'akunKeuangan', 'akunTanpaParent', 'oldParentAkunId', 'akunDenganParent'));
    }

    public function showJson($id)
    {
        $t = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])->findOrFail($id);

        // mapping akun kas & bank (buat nentuin route update)
        $kasIds = [1011, 1012, 1013, 1014, 1015];
        $bankIds = [1021, 1022, 1023, 1024, 1025];

        // Log untuk debugging
        Log::info('showJson: Ambil transaksi', [
            'id' => $id,
            'akun_keuangan_id' => $t->akun_keuangan_id,
            'kasIds' => $kasIds,
            'bankIds' => $bankIds,
        ]);

        // Tentukan URL update (kas / bank)
        if (in_array($t->akun_keuangan_id, $bankIds)) {
            Log::info('showJson: Mendeteksi akun BANK', ['akun_id' => $t->akun_keuangan_id]);
            $updateUrl = route('transaksi.updateBank', $t->id);
        } else {
            Log::info('showJson: Mendeteksi akun KAS', ['akun_id' => $t->akun_keuangan_id]);
            $updateUrl = route('transaksi.update', $t->id);
        }

        // ==========================
        // Hitung ID induk untuk dropdown "Asal/Tujuan Akun"
        // ==========================
        $akunLawan = $t->parentAkunKeuangan; // akun lawan (anak)
        $akunIndukId = null;

        if ($akunLawan) {
            // kalau akun lawan punya parent_id → pakai parent-nya
            // kalau tidak punya → pakai id akun lawan sendiri
            $akunIndukId = $akunLawan->parent_id ?: $akunLawan->id;
        }

        return response()->json([
            'id' => $t->id,
            'bidang_name' => $t->bidang_name,
            'kode_transaksi' => $t->kode_transaksi,
            'tanggal_transaksi' => $t->tanggal_transaksi,
            'type' => $t->type,

            // 👉 ini ID INDUK utk dropdown "Asal Akun" / "Tujuan Akun"
            'akun_keuangan_id' => $akunIndukId,

            // 👉 ini anak akun lawan (old value) utk dropdown anak
            'parent_akun_id' => $t->parent_akun_id,

            // 👉 ini kas/bank sumber (ID yg tersimpan di transaksis.akun_keuangan_id)
            'akun_sumber_id' => $t->akun_keuangan_id,

            'deskripsi' => $t->deskripsi,
            'amount' => $t->amount,
            'update_url' => $updateUrl,
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info('🚀 Masuk ke updateKasTransaction', ['id' => $id, 'request' => $request->all()]);

        $user = auth()->user();
        $userRole = $user->role ?? 'Guest';

        // 1) Validasi kondisional seperti store core
        $rules = [
            'kode_transaksi' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'parent_akun_id' => 'nullable|integer',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ];

        // bidang_name hanya wajib jika bukan Bendahara
        if ($userRole !== 'Bendahara') {
            $rules['bidang_name'] = 'required|integer';
        } else {
            $rules['bidang_name'] = 'nullable';
        }

        try {
            $validatedData = $request->validate($rules);
            Log::info('✅ Validasi update KAS berhasil', ['validatedData' => $validatedData]);
        } catch (ValidationException $e) {
            Log::error('❌ Validasi update KAS gagal', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors());
        }

        // 2) Ambil transaksi utama
        $transaksi = Transaksi::where('id', $id)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->first();

        if (!$transaksi) {
            Log::error('❌ Transaksi KAS tidak ditemukan', ['id' => $id]);
            return back()->withErrors(['error' => 'Transaksi tidak ditemukan.']);
        }

        // 3) Konteks bidang mengikuti store core
        $bidangValue = ($userRole === 'Bendahara') ? null : (int) $validatedData['bidang_name'];

        // 4) Mapping akun kas
        if ($userRole === 'Bendahara') {
            $akun_kas_id = 1011; // Kas Bendahara (global)
        } else {
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];
            $akun_kas_id = $akunKas[$bidangValue] ?? null;
        }

        if (!$akun_kas_id) {
            return back()->withErrors(['bidang_name' => 'Bidang tidak valid atau tidak memiliki akun kas.']);
        }

        // 5) Hitung saldo sebelum transaksi ini (basis saldo kas)
        //    NOTE: filter bidang hanya jika bukan bendahara
        $qLastSaldo = Transaksi::query()
            ->where('akun_keuangan_id', $akun_kas_id)
            ->where('id', '!=', $id)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->orderBy('tanggal_transaksi', 'asc')
            ->orderBy('id', 'asc');

        if ($bidangValue !== null) {
            $qLastSaldo->where('bidang_name', $bidangValue);
        }

        $lastSaldo = $qLastSaldo->get()->last();
        $saldoKas = $lastSaldo ? (float) $lastSaldo->saldo : 0.0;

        Log::info('🔄 Saldo akun Kas sebelum update', [
            'akun_kas_id' => $akun_kas_id,
            'bidang_name' => $bidangValue,
            'saldoKas' => $saldoKas,
        ]);

        // 6) Hitung saldo baru
        $amount = (float) $validatedData['amount'];
        $tipe = $validatedData['type'];

        $newSaldo = ($tipe === 'penerimaan')
            ? $saldoKas + $amount
            : $saldoKas - $amount; // sesuai aturan Anda (boleh minus)

        DB::transaction(function () use ($validatedData, $transaksi, $akun_kas_id, $bidangValue, $newSaldo, $tipe, $amount) {
            $userId = auth()->id();

            // 1) Update transaksi utama (KAS)
            $transaksi->update([
                'bidang_name' => $bidangValue, // NULL jika Bendahara
                'kode_transaksi' => $validatedData['kode_transaksi'],
                'tanggal_transaksi' => Carbon::parse($validatedData['tanggal_transaksi'])->toDateString(),
                'type' => $tipe,
                'akun_keuangan_id' => $akun_kas_id,
                'parent_akun_id' => $validatedData['parent_akun_id'] ?? null,
                'deskripsi' => $validatedData['deskripsi'],
                'amount' => $amount,
                'saldo' => (float) $newSaldo,
                'updated_by' => $userId,
            ]);

            Log::info('✅ Data transaksi Kas berhasil diperbarui', [
                'id' => $transaksi->id,
                'saldo_baru' => $newSaldo,
            ]);

            // 2) Update transaksi lawan jika ada
            $kodeBase = $validatedData['kode_transaksi'];
            $kodeLawan = $kodeBase . '-LAWAN';

            $trxLawan = Transaksi::where('kode_transaksi', $kodeLawan)->first();
            if ($trxLawan) {
                $typeLawan = ($tipe === 'penerimaan') ? 'pengeluaran' : 'penerimaan';

                $trxLawan->update([
                    'bidang_name' => $bidangValue, // NULL jika Bendahara
                    'tanggal_transaksi' => Carbon::parse($validatedData['tanggal_transaksi'])->toDateString(),
                    'type' => $typeLawan,
                    // akun_keuangan_id lawan tetap akun awal lawan (kalau struktur Anda begitu)
                    'deskripsi' => '(Lawan) ' . $validatedData['deskripsi'],
                    'amount' => $amount,
                    'updated_by' => $userId,
                ]);

                Log::info('✅ Transaksi lawan (KAS) ikut diperbarui', [
                    'id' => $trxLawan->id,
                    'kode' => $kodeLawan,
                ]);
            }

            // 3) Sync ledger pasangan transaksi
            $this->syncLedgerAfterUpdate($transaksi);
        });

        return redirect()->route('transaksi.index')->with('success', 'Transaksi Kas berhasil diperbarui!');
    }


    public function updateBankTransaction(Request $request, $id)
    {
        Log::info('🚀 Masuk ke updateBankTransaction', ['id' => $id, 'request' => $request->all()]);

        $user = auth()->user();
        $userRole = $user->role ?? 'Guest';

        // 1) Validasi kondisional seperti store core
        $rules = [
            'kode_transaksi' => 'required|string',
            'tanggal_transaksi' => 'required|date',
            'type' => 'required|in:penerimaan,pengeluaran',
            'parent_akun_id' => 'nullable|integer',
            'deskripsi' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ];

        if ($userRole !== 'Bendahara') {
            $rules['bidang_name'] = 'required|integer';
        } else {
            $rules['bidang_name'] = 'nullable';
        }

        try {
            $validated = $request->validate($rules);
            Log::info('✅ Validasi update BANK berhasil', ['validatedData' => $validated]);
        } catch (ValidationException $e) {
            Log::error('❌ Validasi update BANK gagal', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors());
        }

        // 2) Ambil transaksi utama (tanpa -LAWAN)
        $transaksi = Transaksi::where('id', $id)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->first();

        if (!$transaksi) {
            Log::error('❌ Transaksi BANK utama tidak ditemukan', ['id' => $id]);
            return back()->withErrors(['error' => 'Transaksi tidak ditemukan.']);
        }

        // 3) Konteks bidang
        $bidangValue = ($userRole === 'Bendahara') ? null : (int) $validated['bidang_name'];

        // 4) Tentukan akun BANK berdasar role + bidang
        if ($userRole === 'Bendahara') {
            $akunBankId = 1021; // Bank Bendahara (global)
        } else {
            $bankMap = [
                1 => 1022,
                2 => 1023,
                3 => 1024,
                4 => 1025,
            ];
            $akunBankId = $bankMap[$bidangValue] ?? null;
        }

        if (!$akunBankId) {
            Log::error('❌ Gagal tentukan akun BANK untuk bidang', [
                'bidang_name' => $bidangValue,
                'role' => $userRole,
            ]);
            return back()->withErrors(['bidang_name' => 'Bidang tidak valid atau tidak memiliki akun Bank.']);
        }

        // 5) Hitung saldo BANK sebelum transaksi ini (filter bidang hanya jika bukan Bendahara)
        $qLastSaldoBank = Transaksi::query()
            ->where('akun_keuangan_id', $akunBankId)
            ->where('id', '!=', $id)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->orderBy('tanggal_transaksi', 'asc')
            ->orderBy('id', 'asc');

        if ($bidangValue !== null) {
            $qLastSaldoBank->where('bidang_name', $bidangValue);
        }

        $lastSaldoBank = $qLastSaldoBank->get()->last();
        $saldoBank = $lastSaldoBank ? (float) $lastSaldoBank->saldo : 0.0;

        Log::info('🔄 Saldo akun BANK sebelum update', [
            'akun_bank_id' => $akunBankId,
            'bidang_name' => $bidangValue,
            'saldoBank' => $saldoBank,
        ]);

        $amount = (float) $validated['amount'];
        $tipe = $validated['type'];

        // 6) Hitung saldo baru BANK
        $newSaldoBank = ($tipe === 'penerimaan')
            ? $saldoBank + $amount
            : $saldoBank - $amount;

        DB::transaction(function () use ($validated, $transaksi, $akunBankId, $bidangValue, $newSaldoBank, $amount, $tipe) {

            $userId = auth()->id();

            // Update transaksi utama
            $transaksi->update([
                'bidang_name' => $bidangValue, // NULL jika Bendahara
                'kode_transaksi' => $validated['kode_transaksi'],
                'tanggal_transaksi' => Carbon::parse($validated['tanggal_transaksi'])->toDateString(),
                'type' => $tipe,
                'parent_akun_id' => $validated['parent_akun_id'] ?? null,
                'deskripsi' => $validated['deskripsi'],
                'amount' => $amount,
                // saldo akun non-bank biarkan sesuai mekanisme Anda
                'updated_by' => $userId,
            ]);

            Log::info('✅ Data transaksi BANK (utama) berhasil diperbarui', [
                'id' => $transaksi->id,
                'kode' => $transaksi->kode_transaksi,
            ]);

            // Update transaksi lawan (BANK)
            $kodeBase = $validated['kode_transaksi'];
            $kodeLawan = $kodeBase . '-LAWAN';

            $trxLawan = Transaksi::where('kode_transaksi', $kodeLawan)->first();

            if ($trxLawan) {
                $typeLawan = ($tipe === 'penerimaan') ? 'pengeluaran' : 'penerimaan';

                $trxLawan->update([
                    'bidang_name' => $bidangValue, // NULL jika Bendahara
                    'kode_transaksi' => $kodeLawan,
                    'tanggal_transaksi' => Carbon::parse($validated['tanggal_transaksi'])->toDateString(),
                    'type' => $typeLawan,
                    'akun_keuangan_id' => $akunBankId,
                    'parent_akun_id' => $transaksi->akun_keuangan_id,
                    'deskripsi' => '(Lawan) ' . $validated['deskripsi'],
                    'amount' => $amount,
                    'saldo' => (float) $newSaldoBank,
                    'updated_by' => $userId,
                ]);

                Log::info('✅ Transaksi lawan (BANK) ikut diperbarui', [
                    'id' => $trxLawan->id,
                    'kode' => $trxLawan->kode_transaksi,
                ]);
            } else {
                Log::warning('⚠️ Transaksi lawan BANK tidak ditemukan saat update', [
                    'base_kode' => $kodeBase,
                ]);
            }

            // Sync ledger dari pasangan transaksi
            $this->syncLedgerAfterUpdate($transaksi);
        });

        return redirect()->route('transaksi.index')->with('success', 'Transaksi Bank berhasil diperbarui!');
    }

    private function syncLedgerAfterUpdate(Transaksi $baseTransaksi)
    {
        // 1) Ambil kode transaksi dasar
        $kodeBase = str_replace('-LAWAN', '', $baseTransaksi->kode_transaksi);

        // 2) Ambil semua transaksi pasangan (utama + lawan) untuk kode ini
        $transaksis = Transaksi::where(function ($q) use ($kodeBase) {
            $q->where('kode_transaksi', $kodeBase)
                ->orWhere('kode_transaksi', $kodeBase . '-LAWAN');
        })
            ->get();

        if ($transaksis->isEmpty()) {
            Log::warning('[LEDGER] Tidak ada transaksi untuk disinkronkan', [
                'kode_base' => $kodeBase,
            ]);
            return;
        }

        // 3) Hapus semua ledger lama untuk transaksi-transaksi ini
        $transaksiIds = $transaksis->pluck('id')->all();
        Ledger::whereIn('transaksi_id', $transaksiIds)->delete();

        // 4) Deteksi khusus Kas/Bank vs akun lawan
        $kasBankIds = [1011, 1012, 1013, 1014, 1015, 1021, 1022, 1023, 1024, 1025];

        $akunKasBank = null;
        $akunLawan = null;

        foreach ($transaksis as $t) {
            // Kandidat Kas/Bank
            if (in_array($t->akun_keuangan_id, $kasBankIds)) {
                $akunKasBank = $t->akun_keuangan_id;
            }

            // Kandidat akun lawan:
            // 1) akun_keuangan_id yang bukan Kas/Bank
            if (!in_array($t->akun_keuangan_id, $kasBankIds)) {
                $akunLawan = $t->akun_keuangan_id;
            }

            // 2) parent_akun_id yang bukan Kas/Bank (backup kalau akun_keuangan_id-nya Bank semua)
            if ($t->parent_akun_id && !in_array($t->parent_akun_id, $kasBankIds)) {
                $akunLawan = $t->parent_akun_id;
            }
        }

        // Kalau pola Kas/Bank ketemu dengan jelas → pakai skema debit/kredit khusus
        if ($akunKasBank && $akunLawan) {
            $tipe = $baseTransaksi->type;      // 'penerimaan' / 'pengeluaran'
            $amount = $baseTransaksi->amount;    // nominal terbaru

            // DEBET / KREDIT
            if ($tipe === 'penerimaan') {
                // Kas/Bank masuk dari akun lawan
                $debitKas = $amount;
                $kreditKas = 0;
                $debitLaw = 0;
                $kreditLaw = $amount;
            } elseif ($tipe === 'pengeluaran') {
                // Kas/Bank keluar ke akun lawan
                $debitKas = 0;
                $kreditKas = $amount;
                $debitLaw = $amount;
                $kreditLaw = 0;
            } else {
                $debitKas = $kreditKas = $debitLaw = $kreditLaw = 0;
            }

            // Simpan ledger: 2 baris saja, 1 untuk Kas/Bank, 1 untuk akun lawan.
            // transaksi_id boleh pakai transaksi utama (baseTransaksi) untuk keduanya.
            Ledger::create([
                'transaksi_id' => $baseTransaksi->id,
                'akun_keuangan_id' => $akunKasBank,
                'debit' => $debitKas,
                'credit' => $kreditKas,
            ]);

            Ledger::create([
                'transaksi_id' => $baseTransaksi->id,
                'akun_keuangan_id' => $akunLawan,
                'debit' => $debitLaw,
                'credit' => $kreditLaw,
            ]);

            Log::info('[LEDGER] Sync Kas/Bank berhasil', [
                'kode_base' => $kodeBase,
                'akun_kasbank' => $akunKasBank,
                'akun_lawan' => $akunLawan,
                'tipe' => $tipe,
                'amount' => $amount,
            ]);

            return;
        }

        // 5) Fallback: kalau bukan pola Kas/Bank, pakai mode generic (1 transaksi = 1 ledger)
        foreach ($transaksis as $t) {
            Ledger::create([
                'transaksi_id' => $t->id,
                'akun_keuangan_id' => $t->akun_keuangan_id,
                'debit' => $t->type === 'penerimaan' ? $t->amount : 0,
                'credit' => $t->type === 'pengeluaran' ? $t->amount : 0,
            ]);
        }

        Log::info('[LEDGER] Sync generic (non Kas/Bank) selesai', [
            'kode_base' => $kodeBase,
            'transaksi_ids' => $transaksiIds,
        ]);
    }

    public function storeOpeningBalance(Request $request)
    {
        $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'kode_transaksi' => ['nullable', 'string', 'max:255'],
            'kas_bank_akun_id' => ['required', 'exists:akun_keuangans,id'],
            'akun_keuangan_id' => ['required', 'exists:akun_keuangans,id'], // Induk Aset Neto
            'parent_akun_id' => ['nullable', 'exists:akun_keuangans,id'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $tanggal = Carbon::parse($request->tanggal_transaksi);
        $amount = (float) $request->amount;
        $deskripsi = $request->deskripsi ?: 'Saldo awal per ' . $tanggal->translatedFormat('d F Y');

        $kasBankId = (int) $request->kas_bank_akun_id;
        $asetNetoId = $request->parent_akun_id ?: $request->akun_keuangan_id;

        DB::transaction(function () use ($request, $tanggal, $amount, $deskripsi, $kasBankId, $asetNetoId) {
            $kodeTransaksi = $request->filled('kode_transaksi')
                ? $request->kode_transaksi
                : 'OPEN-' . $tanggal->format('Y') . '-' . strtoupper(uniqid());

            $userId = auth()->id();

            $transaksi = Transaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'tanggal_transaksi' => $tanggal->toDateString(),
                'type' => 'penerimaan', // hanya isi kolom
                'deskripsi' => $deskripsi,
                'akun_keuangan_id' => $kasBankId,
                'parent_akun_id' => $asetNetoId,
                'bidang_name' => auth()->user()->role === 'Bidang'
                    ? auth()->user()->bidang_name
                    : null,
                'sumber' => null,
                'amount' => $amount,
                'saldo' => 0,
                'user_id' => $userId,
                'updated_by' => $userId,
            ]);

            // Debit Kas/Bank
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $kasBankId,
                'debit' => $amount,
                'credit' => 0,
            ]);

            // Kredit Aset Neto
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $asetNetoId,
                'debit' => 0,
                'credit' => $amount,
            ]);
        });

        return back()->with('success', 'Saldo awal (opening balance) berhasil dicatat.');
    }

    public function storeTransfer(Request $request)
    {
        Log::info('====== [TRANSFER] Memulai proses storeTransfer ======', [
            'payload' => $request->all(),
            'user' => auth()->id(),
        ]);

        // STEP 1: VALIDASI DASAR
        Log::info('[TRANSFER] Step 1: Validasi input');

        $validated = $request->validate([
            'kode_transaksi' => ['required', 'string'],
            'tanggal_transaksi' => ['required', 'date'],
            'sumber_akun_id' => ['required', 'exists:akun_keuangans,id'],
            'tujuan_akun_id' => ['required', 'exists:akun_keuangans,id', 'different:sumber_akun_id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'deskripsi' => ['required', 'string', 'max:255'],
            'is_transfer' => ['nullable'], // biar ikut ke old()
        ]);

        $user = auth()->user();
        $role = $user->role;                       // 'Bendahara' atau 'Bidang'
        $bidangName = $user->bidang_name ?? null;        // id bidang kalau user Bidang

        $tanggal = \Carbon\Carbon::parse($validated['tanggal_transaksi'])->toDateString();
        $amount = (float) $validated['amount'];
        $kode = $validated['kode_transaksi'];

        $sumberId = (int) $validated['sumber_akun_id'];
        $tujuanId = (int) $validated['tujuan_akun_id'];

        // ===============================
        //  STEP 1.5: MAP KAS / BANK
        // ===============================

        // Kas/Bank Bendahara (pusat)
        $akunKasBankBendahara = [1011, 1021];

        // Kas/Bank per Bidang (mapping id bidang -> [kas, bank])
        $mapKasBankBidang = [
            1 => [1012, 1022],
            2 => [1013, 1023],
            3 => [1014, 1024],
            4 => [1015, 1025],
            // kalau nanti ada bidang 5 dan seterusnya, tambahkan di sini
        ];

        // Index balik: akun_id -> bidang_id
        $bidangByAkun = [];
        foreach ($mapKasBankBidang as $bId => $akunArr) {
            foreach ($akunArr as $aId) {
                $bidangByAkun[$aId] = $bId;
            }
        }

        $isSumberBendaharaKas = in_array($sumberId, $akunKasBankBendahara);
        $isTujuanBendaharaKas = in_array($tujuanId, $akunKasBankBendahara);

        $sumberBidang = $bidangByAkun[$sumberId] ?? null;
        $tujuanBidang = $bidangByAkun[$tujuanId] ?? null;

        // Deteksi jenis transfer:
        $isBidangToBendahara = !$isSumberBendaharaKas && $isTujuanBendaharaKas && $sumberBidang !== null;
        $isBendaharaToBidang = $isSumberBendaharaKas && !$isTujuanBendaharaKas && $tujuanBidang !== null;

        Log::info('[TRANSFER] Tipe transfer', [
            'isBidangToBendahara' => $isBidangToBendahara,
            'isBendaharaToBidang' => $isBendaharaToBidang,
            'sumberBidang' => $sumberBidang,
            'tujuanBidang' => $tujuanBidang,
        ]);

        // ===============================
        //  STEP 2: CEK SALDO SUMBER
        // ===============================
        Log::info('[TRANSFER] Step 2: Hitung saldo sumber sebelum transfer', [
            'akun_sumber' => $sumberId,
            'tanggal' => $tanggal,
            'bidangName' => $bidangName,
            'role' => $role,
        ]);

        $saldoSumber = $this->getSaldoLedgerSampaiTanggal(
            $sumberId,
            $tanggal,
            $role === 'Bendahara' ? null : $bidangName, // pakai aturan lama
            $role
        );

        Log::info('[TRANSFER] Saldo sumber sebelum transfer', [
            'akun_sumber' => $sumberId,
            'saldo' => $saldoSumber,
            'amount' => $amount,
        ]);

        if ($amount > $saldoSumber) {
            Log::warning('[TRANSFER] SALDO TIDAK CUKUP!', [
                'saldo_sumber' => $saldoSumber,
                'amount' => $amount,
            ]);

            return back()
                ->withErrors([
                    'amount' => 'Saldo sumber tidak mencukupi. Saldo saat ini: ' .
                        number_format($saldoSumber, 0, ',', '.') .
                        ', jumlah yang diminta: ' .
                        number_format($amount, 0, ',', '.'),
                ])
                ->withInput();
        }

        // ===============================
        //  STEP 3: SIMPAN TRANSAKSI
        // ===============================
        Log::info('[TRANSFER] Step 3: Simpan transaksi & ledger');

        DB::transaction(function () use ($validated, $sumberId, $tujuanId, $bidangName, $tanggal, $amount, $kode, $role, $isBidangToBendahara, $isBendaharaToBidang, $sumberBidang, $tujuanBidang) {
            $userId = auth()->id();

            // Ambil ID akun perantara dari config / fallback ke hardcoded
            $akunPiutangPerantara = config('akun.piutang_perantara', 1033);
            $akunHutangPerantara = config('akun.hutang_perantara_bidang', 50016);

            // ===============================
            //  CASE 1: BIDANG → BENDAHARA
            // ===============================
            if ($isBidangToBendahara) {
                $bidangId = $sumberBidang;

                // --- 3.1.1 Transaksi BIDANG: Kas Bidang -> Piutang Perantara ---
                $trxBidang = Transaksi::create([
                    'bidang_name' => $bidangId,
                    'kode_transaksi' => $kode,
                    'tanggal_transaksi' => $tanggal,
                    'type' => 'mutasi',
                    'akun_keuangan_id' => $sumberId,              // Kas/Bank Bidang
                    'parent_akun_id' => $akunPiutangPerantara,  // Lawan: Piutang Perantara
                    'deskripsi' => $validated['deskripsi'],
                    'amount' => $amount,
                    'saldo' => 0,
                    'user_id' => $userId,
                    'updated_by' => $userId,
                ]);

                // Ledger BIDANG:
                //  Cr Kas Bidang
                //  Dr Piutang Perantara
                Ledger::create([
                    'transaksi_id' => $trxBidang->id,
                    'akun_keuangan_id' => $sumberId,
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                Ledger::create([
                    'transaksi_id' => $trxBidang->id,
                    'akun_keuangan_id' => $akunPiutangPerantara,
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                // --- 3.1.2 Transaksi BENDAHARA: Kas Bendahara -> Hutang Perantara ---
                $trxBendahara = Transaksi::create([
                    'bidang_name' => null, // Bendahara (pusat)
                    'kode_transaksi' => $kode . '-BDH',
                    'tanggal_transaksi' => $tanggal,
                    'type' => 'mutasi',
                    'akun_keuangan_id' => $tujuanId,              // Kas/Bank Bendahara
                    'parent_akun_id' => $akunHutangPerantara,   // Lawan: Hutang Perantara
                    'deskripsi' => '(Bendahara) ' . $validated['deskripsi'],
                    'amount' => $amount,
                    'saldo' => 0,
                    'user_id' => $userId,
                    'updated_by' => $userId,
                ]);

                // Ledger BENDAHARA:
                //  Dr Kas Bendahara
                //  Cr Hutang Perantara
                Ledger::create([
                    'transaksi_id' => $trxBendahara->id,
                    'akun_keuangan_id' => $tujuanId,
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                Ledger::create([
                    'transaksi_id' => $trxBendahara->id,
                    'akun_keuangan_id' => $akunHutangPerantara,
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                Log::info('[TRANSFER] Bidang → Bendahara tersimpan', [
                    'trx_bidang_id' => $trxBidang->id,
                    'trx_bendahara_id' => $trxBendahara->id,
                ]);

                return;
            }

            // ===============================
            //  CASE 2: BENDAHARA → BIDANG
            // ===============================
            if ($isBendaharaToBidang) {
                $bidangId = $tujuanBidang;

                // --- 3.2.1 Transaksi BENDAHARA: Kas Bendahara -> Hutang Perantara ---
                $trxBendahara = Transaksi::create([
                    'bidang_name' => null,
                    'kode_transaksi' => $kode,
                    'tanggal_transaksi' => $tanggal,
                    'type' => 'mutasi',
                    'akun_keuangan_id' => $sumberId,              // Kas/Bank Bendahara
                    'parent_akun_id' => $akunHutangPerantara,   // Lawan: Hutang Perantara
                    'deskripsi' => $validated['deskripsi'],
                    'amount' => $amount,
                    'saldo' => 0,
                    'user_id' => $userId,
                    'updated_by' => $userId,
                ]);

                // Ledger BENDAHARA:
                // Cr Kas/Bank Bendahara
                Ledger::create([
                    'transaksi_id' => $trxBendahara->id,
                    'akun_keuangan_id' => $sumberId,
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                // Dr Piutang Perantara (1033) - BUKAN Dr Hutang
                Ledger::create([
                    'transaksi_id' => $trxBendahara->id,
                    'akun_keuangan_id' => $akunPiutangPerantara,
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                // --- 3.2.2 Transaksi BIDANG: Kas Bidang -> Piutang Perantara (turun) ---
                $trxBidang = Transaksi::create([
                    'bidang_name' => $bidangId,
                    'kode_transaksi' => $kode . '-BDG',
                    'tanggal_transaksi' => $tanggal,
                    'type' => 'mutasi',
                    'akun_keuangan_id' => $tujuanId,              // Kas/Bank Bidang
                    'parent_akun_id' => $akunPiutangPerantara,  // Lawan: Piutang Perantara
                    'deskripsi' => '(Bidang) ' . $validated['deskripsi'],
                    'amount' => $amount,
                    'saldo' => 0,
                    'user_id' => $userId,
                    'updated_by' => $userId,
                ]);

                // Ledger BIDANG:
                // Dr Kas/Bank Bidang
                Ledger::create([
                    'transaksi_id' => $trxBidang->id,
                    'akun_keuangan_id' => $tujuanId,
                    'debit' => $amount,
                    'credit' => 0,
                ]);

                // Cr Hutang Perantara (50016) - BUKAN Cr Piutang
                Ledger::create([
                    'transaksi_id' => $trxBidang->id,
                    'akun_keuangan_id' => $akunHutangPerantara,
                    'debit' => 0,
                    'credit' => $amount,
                ]);

                Log::info('[TRANSFER] Bendahara → Bidang tersimpan', [
                    'trx_bendahara_id' => $trxBendahara->id,
                    'trx_bidang_id' => $trxBidang->id,
                ]);

                return;
            }

            // ===============================
            //  CASE 3: TRANSFER BIASA (internal)
            //  (fallback: logika lama, antar akun saja)
            // ===============================
            $userId = auth()->id();
            $bidangValue = $role === 'Bendahara' ? null : $bidangName;

            // 4a. Transaksi keluar (SUMBER) – pengeluaran
            $trxKeluar = Transaksi::create([
                'bidang_name' => $bidangValue,
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal,
                'type' => 'mutasi',
                'akun_keuangan_id' => $sumberId,
                'parent_akun_id' => $tujuanId,
                'deskripsi' => $validated['deskripsi'],
                'amount' => $amount,
                'saldo' => 0,
                'user_id' => $userId,
                'updated_by' => $userId,
            ]);

            Ledger::create([
                'transaksi_id' => $trxKeluar->id,
                'akun_keuangan_id' => $sumberId,
                'debit' => 0,
                'credit' => $amount,
            ]);

            // 4b. Transaksi masuk (TUJUAN) – penerimaan
            $trxMasuk = Transaksi::create([
                'bidang_name' => $bidangValue,
                'kode_transaksi' => $kode . '-LAWAN',
                'tanggal_transaksi' => $tanggal,
                'type' => 'mutasi',
                'akun_keuangan_id' => $tujuanId,
                'parent_akun_id' => $sumberId,
                'deskripsi' => '(Lawan) ' . $validated['deskripsi'],
                'amount' => $amount,
                'saldo' => 0,
                'user_id' => $userId,
                'updated_by' => $userId,
            ]);

            Ledger::create([
                'transaksi_id' => $trxMasuk->id,
                'akun_keuangan_id' => $tujuanId,
                'debit' => $amount,
                'credit' => 0,
            ]);

            Log::info('[TRANSFER] Transfer internal biasa berhasil disimpan', [
                'trx_keluar_id' => $trxKeluar->id,
                'trx_masuk_id' => $trxMasuk->id,
            ]);
        });

        return back()->with('success', 'Transfer antar akun berhasil!');
    }

    public function getData()
    {
        $user = auth()->user();

        // Ambil transaksi berdasarkan role
        $transaksiQuery = Transaksi::with([
            'akunKeuangan',
            'parentAkunKeuangan',
            'user',
            'updatedBy',
        ])
            // Hindari transaksi lawan
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            // HINDARI TRANSAKSI MUTASI (TRF-*)
            ->where('kode_transaksi', 'not like', 'TRF-%');

        // Jika user adalah Bidang → filter transaksi miliknya
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        Log::info('Data transaksi (exclude TRF-*, LAWAN) dengan relasi akun & user:', $transaksi->toArray());

        return DataTables::of($transaksi)
            ->addColumn('parent_akun_nama', function ($item) {
                return $item->parentAkunKeuangan ? $item->parentAkunKeuangan->nama_akun : 'N/A';
            })
            ->addColumn('user_name', function ($item) {
                return optional($item->user)->name ?: '-';
            })
            ->addColumn('updated_by_name', function ($item) {
                return optional($item->updatedBy)->name ?: '-';
            })
            ->addColumn('actions', function ($item) {
                return view('transaksi.actions', ['id' => $item->id]);
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function getMutasiData()
    {
        $user = auth()->user();

        $transaksiQuery = Transaksi::with([
            'akunKeuangan',
            'parentAkunKeuangan',
            'user',
            'updatedBy',
        ])
            // Hanya transaksi transfer / mutasi
            ->where('kode_transaksi', 'like', 'TRF-%')
            // Hindari transaksi lawan
            ->where('kode_transaksi', 'not like', '%-LAWAN');

        // Kalau role Bidang, tetap dibatasi ke bidangnya
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        Log::info('Data MUTASI transaksi (kode TRF-*) dengan relasi akun & user:', $transaksi->toArray());

        return DataTables::of($transaksi)
            ->addColumn('parent_akun_nama', function ($item) {
                return $item->parentAkunKeuangan ? $item->parentAkunKeuangan->nama_akun : 'N/A';
            })
            ->addColumn('user_name', function ($item) {
                return optional($item->user)->name ?: '-';
            })
            ->addColumn('updated_by_name', function ($item) {
                return optional($item->updatedBy)->name ?: '-';
            })
            ->addColumn('actions', function ($item) {
                return view('transaksi.actions', ['id' => $item->id]);
            })
            ->rawColumns(['actions'])
            ->make(true);
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
        $user = auth()->user();
        $bidangName = $user->bidang_name;
        // Ambil transaksi
        $transaksi = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan', 'bidang'])->find($id);

        if (!$transaksi) {
            return redirect()->route('transaksi.index')->with('error', 'Transaksi tidak ditemukan');
        }

        // Ambil setting untuk logo & brand
        $setting = SidebarSetting::first();

        // Buat absolute path untuk DomPDF
        $logoPath = null;
        if ($setting && $setting->logo_path) {
            $logoPath = public_path('storage/' . $setting->logo_path);
        }

        $tanggal_transaksi = $transaksi->tanggal_transaksi;
        $jenis_transaksi = $transaksi->type;
        $akun = $transaksi->akunKeuangan->nama_akun ?? 'N/A';
        $sub_akun = $transaksi->parentAkunKeuangan->nama_akun ?? 'N/A';

        $pdf = Pdf::loadView('transaksi.nota', compact(
            'transaksi',
            'tanggal_transaksi',
            'akun',
            'sub_akun',
            'jenis_transaksi',
            'setting',
            'bidangName',
            'logoPath'
        ))
            ->setPaper('a5', 'portrait');

        return $pdf->download('Invoice_' . $transaksi->kode_transaksi . '.pdf');
    }

    public function exportAllPdf()
    {
        $user = auth()->user();

        // ==========================
        // 🔹 Tentukan bidang & role
        // ==========================
        $bidangId = $user->bidang_name;   // integer
        $role = $user->role;

        // ==========================
        // 🔹 Mapping akun Kas/Bank per bidang
        // ==========================
        $kasMap = [
            1 => 1012, // Kemasjidan
            2 => 1013, // Pendidikan
            3 => 1014, // Sosial
            4 => 1015, // Usaha
        ];

        $bankMap = [
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ];

        if ($role === 'Bendahara') {
            $kasAkunId = 1011; // Kas Bendahara
            $bankAkunId = 1021; // Bank Bendahara
        } else {
            $kasAkunId = $kasMap[$bidangId] ?? null;
            $bankAkunId = $bankMap[$bidangId] ?? null;
        }

        // ==========================
        // 🔹 Query Kas (transaksis utk akun kas user ini)
        // ==========================
        $kasTransaksis = collect();
        if ($kasAkunId) {
            $kasTransaksis = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan', 'user'])
                ->where('akun_keuangan_id', $kasAkunId)
                ->when($role === 'Bidang', function ($q) use ($bidangId) {
                    $q->where('bidang_name', $bidangId);
                })
                ->orderBy('tanggal_transaksi')
                ->orderBy('id')
                ->get();
        }

        // ==========================
        // 🔹 Query Bank (transaksis utk akun bank user ini)
        // ==========================
        $bankTransaksis = collect();
        if ($bankAkunId) {
            $bankTransaksis = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan', 'user'])
                ->where('akun_keuangan_id', $bankAkunId)
                ->when($role === 'Bidang', function ($q) use ($bidangId) {
                    $q->where('bidang_name', $bidangId);
                })
                ->orderBy('tanggal_transaksi')
                ->orderBy('id')
                ->get();
        }

        if ($kasTransaksis->isEmpty() && $bankTransaksis->isEmpty()) {
            return redirect()
                ->route('transaksi.index')
                ->with('error', 'Tidak ada data transaksi Kas/Bank untuk diunduh.');
        }

        // Kirim ke view
        $data = [
            'kasTransaksis' => $kasTransaksis,
            'bankTransaksis' => $bankTransaksis,
            'user' => $user,
        ];

        $bidangNameForFile = $user->bidang->name ?? $user->bidang_name ?? 'Umum';

        $pdf = Pdf::loadView('transaksi.export', $data)
            ->setPaper('a4', 'landscape');   // ⬅️ Landscape

        return $pdf->download('Buku_Harian_Kas_Bank_' . $bidangNameForFile . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $user = auth()->user();

        $bidangName = $request->input('bidang_name');
        if ($user->role === 'Bidang') {
            $bidangName = $user->bidang_name;
        }

        $bulan = $request->input('bulan'); // format YYYY-MM
        $startDate = $endDate = null;

        if ($bulan) {
            [$year, $month] = explode('-', $bulan);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate = (clone $startDate)->endOfMonth()->endOfDay();
        }

        // Cek ada transaksi kas/bank untuk filter ini,
        // tetap pakai scope excludeInternalKasBankAndLawan
        $query = Transaksi::query()
            ->whereHas('akunKeuangan', function ($q) {
                $q->whereIn('parent_id', [101, 102]); // 101=Kas, 102=Bank
            });

        if ($bidangName) {
            $query->where('bidang_name', $bidangName);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
        }

        if (!$query->exists()) {
            return back()->with('error', 'Tidak ada transaksi kas/bank sesuai filter.');
        }

        $fileName = 'Buku_Kas_Bank';
        if ($bidangName) {
            $fileName .= '_Bidang_' . $bidangName;
        }
        if ($bulan) {
            $fileName .= '_' . $bulan;
        }
        $fileName .= '.xlsx';

        return Excel::download(
            new BukuKasBankExport($bidangName, $startDate, $endDate),
            $fileName
        );
    }
}
