<?php

namespace App\Http\Controllers\Kemasjidan;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use App\Models\InfaqKemasjidan;
use App\Traits\HasTransaksiKasBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;

class KemasjidanController extends Controller
{
    use HasTransaksiKasBank;

    // === CONFIG COA (sesuaikan bila perlu) ===
    private int $COA_PENDAPATAN_INFAQ_KEMASJIDAN = 2042;

    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user?->bidang || $user->bidang->name !== 'Kemasjidan') {
                abort(403, 'Akses khusus Bidang Kemasjidan');
            }
            return $next($request);
        });
    }

    // =========================
    // Helpers Bulan
    // =========================
    private function bulanMap(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    private function bulanNama(int $bulan): string
    {
        return $this->bulanMap()[$bulan] ?? (string) $bulan;
    }

    // =========================
    // INDEX (Dashboard)
    // =========================
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $tahun = (int) ($request->get('tahun', now()->year));

        $wargas = Warga::query()
            ->kepalaKeluarga()
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('hp', 'like', "%{$q}%")
                        ->orWhere('rt', 'like', "%{$q}%")
                        ->orWhere('alamat', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        // ringkas
        $jumlahWarga = Warga::kepalaKeluarga()->count();

        $totalInfaqTahun = (float) InfaqKemasjidan::query()
            ->where('tahun', $tahun)
            ->sum('nominal');

        $jumlahSudahBayar = (int) InfaqKemasjidan::query()
            ->where('tahun', $tahun)
            ->whereNotNull('warga_id')
            ->distinct('warga_id')
            ->count('warga_id');

        $ringkas = [
            'jumlah_warga' => $jumlahWarga,
            'total_infaq' => $totalInfaqTahun,
            'jumlah_bayar' => $jumlahSudahBayar,
            'tahun' => $tahun,
        ];

        return view('bidang.kemasjidan.infaq.index', compact('wargas', 'ringkas', 'q', 'tahun', 'jumlahSudahBayar'));
    }

    // =========================
    // DATATABLE (opsional)
    // =========================
    public function datatable(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $tahun = (int) ($request->get('tahun', now()->year));
        $globalSearch = trim((string) $request->input('search.value', ''));

        $query = Warga::kepalaKeluarga()
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('hp', 'like', "%{$q}%")
                        ->orWhere('rt', 'like', "%{$q}%")
                        ->orWhere('alamat', 'like', "%{$q}%");
                });
            })
            ->when($globalSearch !== '', function ($qr) use ($globalSearch) {
                $qr->where(function ($sub) use ($globalSearch) {
                    $sub->where('nama', 'like', "%{$globalSearch}%")
                        ->orWhere('hp', 'like', "%{$globalSearch}%")
                        ->orWhere('rt', 'like', "%{$globalSearch}%")
                        ->orWhere('alamat', 'like', "%{$globalSearch}%");
                });
            })
            ->orderBy('nama');

        return DataTables::of($query)
            ->addColumn('nama', fn($w) => $w->nama ?? '-')
            ->addColumn('hp', fn($w) => $w->hp ?? '-')

            ->addColumn('status_infaq', function ($w) use ($tahun) {
                $total = (float) InfaqKemasjidan::where('warga_id', $w->id)
                    ->where('tahun', $tahun)
                    ->sum('nominal');

                $paid = $total > 0;
                $class = $paid ? 'text-bg-success text-white' : 'text-bg-secondary';
                $text = $paid ? 'Sudah Pernah Bayar' : 'Belum Pernah Bayar';

                return '<span class="badge ' . $class . '">' . $text . '</span>';
            })

            ->addColumn('total_infaq', function ($w) use ($tahun) {
                $total = (float) InfaqKemasjidan::where('warga_id', $w->id)
                    ->where('tahun', $tahun)
                    ->sum('nominal');
                return 'Rp ' . number_format($total, 0, ',', '.');
            })

            ->addColumn('aksi', function ($w) use ($tahun) {
                $url = route('kemasjidan.infaq.detail', [$w->id, 'tahun' => $tahun]);
                return '<a href="' . $url . '" class="btn btn-info btn-sm">Detail</a>';
            })
            ->rawColumns(['status_infaq', 'aksi'])
            ->make(true);
    }

    // =========================
    // STORE (input 1 bulan)
    // =========================
    public function store(Request $request)
    {
        $request->validate([
            'warga_id' => ['required', 'integer', 'exists:wargas,id'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tanggal' => ['nullable', 'date'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'metode_bayar' => ['nullable', 'string', 'max:50'],
            'sumber' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            // opsional override nama/no hp donatur (kalau Anda mau input manual)
            'nama_donatur' => ['nullable', 'string', 'max:120'],
            'no_hp' => ['nullable', 'string', 'max:50'],
        ]);

        return DB::transaction(function () use ($request) {
            $user = auth()->user();
            $role = $user->role ?? 'Guest';
            $bidangId = is_numeric($user->bidang_name ?? null) ? (int) $user->bidang_name : null;

            $warga = Warga::findOrFail((int) $request->warga_id);

            $tahun = (int) $request->tahun;
            $bulan = (int) $request->bulan;
            $tanggal = $request->filled('tanggal') ? $request->date('tanggal') : now();
            $nominal = (float) $request->nominal;
            $metode = $request->metode_bayar;

            // Cegah double input bulan yang sama (opsional tapi saya aktifkan)
            $exists = InfaqKemasjidan::where('warga_id', $warga->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->lockForUpdate()
                ->first();

            if ($exists) {
                return back()
                    ->withInput()
                    ->with('error', 'Transaksi gagal: Infaq ' . $this->bulanNama($bulan) . " {$tahun} untuk {$warga->nama} sudah ada.");
            }

            // Tentukan akun debit (kas/bank) by metode
            $akunDebitId = $this->resolveAkunPenerimaanByMetode($role, $bidangId, $metode);
            $akunKreditId = $this->COA_PENDAPATAN_INFAQ_KEMASJIDAN;

            if (!$akunDebitId || !$akunKreditId) {
                return back()->withInput()->with('error', 'Akun DEBIT/KREDIT belum dikonfigurasi.');
            }

            // kode transaksi konsisten + mudah ditrace
            $kodePrefix = $this->makeKodePrefix($role, $bidangId);
            $kodeTransaksi = $kodePrefix . '-INF-KMS-' . now()->format('YmdHis') . '-' . $warga->id . '-' . $tahun . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

            $deskripsi = 'Infaq Kemasjidan ' . $this->bulanNama($bulan) . " {$tahun} - " . ($warga->nama ?? '-') . ' (' . ($warga->hp ?? '-') . ')'
                . ($metode ? ' [' . ucfirst($metode) . ']' : '');

            // 1) Simpan record infaq_kemasjidans
            $trx = InfaqKemasjidan::create([
                'warga_id' => $warga->id,
                'tanggal' => $tanggal->toDateString(),
                'tahun' => $tahun,
                'bulan' => $bulan,
                'nominal' => $nominal,
                'metode_bayar' => $metode,
                'sumber' => $request->sumber,
                'nama_donatur' => $request->nama_donatur ?: ($warga->nama ?? null),
                'no_hp' => $request->no_hp ?: ($warga->hp ?? null),
                'keterangan' => $request->keterangan,
                'akun_debit_id' => $akunDebitId,
                'akun_kredit_id' => $akunKreditId,
                'kode_transaksi' => $kodeTransaksi,
                'created_by' => $user->id ?? null,
            ]);

            // 2) Auto Journal (double-entry) -> Transaksis + Ledgers (via trait)
            try {
                $req = new Request([
                    'kode_transaksi' => $kodeTransaksi,
                    'tanggal_transaksi' => $tanggal->toDateString(),
                    'type' => 'penerimaan',
                    'deskripsi' => $deskripsi,
                    'amount' => $nominal,
                    'bidang_name' => $role === 'Bendahara' ? null : $bidangId,
                ]);

                // penting: panggil core agar tidak redirect ke transaksi.index
                $this->processTransactionCore($req, (int) $akunDebitId, (int) $akunKreditId);
            } catch (\Throwable $e) {
                Log::warning('Jurnal infaq kemasjidan gagal, rollback.', ['err' => $e->getMessage()]);
                throw $e; // biar DB::transaction rollback (infaq_kemasjidans ikut batal)
            }

            return redirect()
                ->route('kemasjidan.infaq.detail', [$warga->id, 'tahun' => $tahun])
                ->with('success', 'Infaq ' . $this->bulanNama($bulan) . " {$tahun} untuk {$warga->nama} tersimpan & jurnal tercatat.");
        });
    }

    // =========================
    // UPDATE (edit nominal/metode/keterangan) - aman: buat koreksi journal
    // =========================
    public function update(Request $request, $id)
    {
        // $id = id infaq_kemasjidans
        $request->validate([
            'tanggal' => ['nullable', 'date'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'metode_bayar' => ['nullable', 'string', 'max:50'],
            'sumber' => ['nullable', 'string', 'max:100'],
            'nama_donatur' => ['nullable', 'string', 'max:120'],
            'no_hp' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($request, $id) {
            $user = auth()->user();
            $role = $user->role ?? 'Guest';
            $bidangId = is_numeric($user->bidang_name ?? null) ? (int) $user->bidang_name : null;

            /** @var InfaqKemasjidan $trx */
            $trx = InfaqKemasjidan::lockForUpdate()->findOrFail($id);

            $warga = Warga::find($trx->warga_id);

            $oldNominal = (float) $trx->nominal;
            $oldDebit = (int) ($trx->akun_debit_id ?? 0);
            $oldKredit = (int) ($trx->akun_kredit_id ?? 0);
            $oldKode = (string) ($trx->kode_transaksi ?? '');

            $newTanggal = $request->filled('tanggal') ? $request->date('tanggal') : now();
            $newNominal = (float) $request->nominal;
            $newMetode = $request->metode_bayar;

            // Recompute akun debit (kas/bank) jika metode berubah
            $newDebit = $this->resolveAkunPenerimaanByMetode($role, $bidangId, $newMetode);
            $newKredit = $this->COA_PENDAPATAN_INFAQ_KEMASJIDAN;

            if (!$newDebit || !$newKredit) {
                return back()->withInput()->with('error', 'Akun DEBIT/KREDIT belum dikonfigurasi.');
            }

            // Jika tidak ada perubahan signifikan, cukup update metadata
            $needsCorrection = ($newNominal !== $oldNominal) || ($newDebit !== $oldDebit) || ($newKredit !== $oldKredit);

            // Update record infaq_kemasjidans dulu
            $trx->tanggal = $newTanggal ? $newTanggal->toDateString() : null;
            $trx->nominal = (float) $newNominal;
            $trx->metode_bayar = $newMetode;
            $trx->sumber = $request->sumber;
            $trx->nama_donatur = $request->nama_donatur ?? $trx->nama_donatur;
            $trx->no_hp = $request->no_hp ?? $trx->no_hp;
            $trx->keterangan = $request->keterangan;
            $trx->akun_debit_id = $newDebit;
            $trx->akun_kredit_id = $newKredit;
            $trx->save();

            // Jika butuh koreksi: JANGAN edit transaksi lama (audit trail).
            // Buat 2 jurnal:
            // (A) pembalik transaksi lama (kebalikan dari yang pernah diposting)
            // (B) posting transaksi baru
            if ($needsCorrection) {
                try {
                    $kodePrefix = $this->makeKodePrefix($role, $bidangId);
                    $kodeKoreksi = $kodePrefix . '-INF-KMS-KOR-' . now()->format('YmdHis') . '-' . $trx->id;

                    $namaBulan = $this->bulanNama((int) $trx->bulan);
                    $tahun = (int) $trx->tahun;

                    // A) REVERSAL: karena posting awal = penerimaan (debit kas/bank, kredit pendapatan),
                    // reversal dibuat sebagai pengeluaran dengan amount yang sama pada akun debit lama.
                    if ($oldNominal > 0 && $oldDebit && $oldKredit) {
                        $reqReverse = new Request([
                            'kode_transaksi' => $kodeKoreksi . '-REV',
                            'tanggal_transaksi' => $newTanggal->toDateString(),
                            'type' => 'pengeluaran',
                            'deskripsi' => 'Koreksi (Reversal) Infaq Kemasjidan ' . $namaBulan . " {$tahun}"
                                . ' - ref: ' . ($oldKode ?: $trx->id),
                            'amount' => $oldNominal,
                            'bidang_name' => $role === 'Bendahara' ? null : $bidangId,
                        ]);
                        $this->processTransactionCore($reqReverse, (int) $oldDebit, (int) $oldKredit);
                    }

                    // B) POST NEW
                    $reqNew = new Request([
                        'kode_transaksi' => $kodeKoreksi . '-NEW',
                        'tanggal_transaksi' => $newTanggal->toDateString(),
                        'type' => 'penerimaan',
                        'deskripsi' => 'Koreksi (Posting Baru) Infaq Kemasjidan ' . $namaBulan . " {$tahun}"
                            . ' - trxId: ' . $trx->id
                            . ($newMetode ? ' [' . ucfirst($newMetode) . ']' : ''),
                        'amount' => $newNominal,
                        'bidang_name' => $role === 'Bendahara' ? null : $bidangId,
                    ]);
                    $this->processTransactionCore($reqNew, (int) $newDebit, (int) $newKredit);

                    // simpan kode koreksi agar mudah dilacak
                    $trx->kode_transaksi = $trx->kode_transaksi ?: $oldKode; // keep jika sudah ada
                    $trx->keterangan = trim(($trx->keterangan ?? '') . ' | Koreksi journal: ' . $kodeKoreksi);
                    $trx->save();
                } catch (\Throwable $e) {
                    Log::warning('Koreksi jurnal infaq gagal, rollback.', ['err' => $e->getMessage()]);
                    throw $e;
                }
            }

            return redirect()
                ->route('kemasjidan.infaq.detail', [$trx->warga_id, 'tahun' => $trx->tahun])
                ->with('success', 'Data infaq diperbarui.' . ($needsCorrection ? ' Koreksi jurnal dibuat (audit-safe).' : ''));
        });
    }

    public function show(Request $request, $wargaId)
    {
        $tahun = (int) ($request->get('tahun', now()->year));
        $warga = Warga::findOrFail($wargaId);

        $bulanMap = $this->bulanMap();

        $rows = InfaqKemasjidan::query()
            ->where('warga_id', $warga->id)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('bulan'); // 1..12

        $bulanList = [];
        $total = 0.0;

        foreach (range(1, 12) as $b) {
            $trx = $rows->get($b);
            $nominal = (float) ($trx->nominal ?? 0);
            $total += $nominal;

            $bulanList[$b] = [
                'nama' => $bulanMap[$b],
                'trx' => $trx,
                'nominal' => $nominal,
                'lunas' => $nominal > 0,
            ];
        }

        return view('bidang.kemasjidan.infaq.detail-infaq', [
            'warga' => $warga,
            'tahun' => $tahun,
            'bulanList' => $bulanList,
            'total' => $total,
        ]);
    }


    // =========================
    // RECEIPT: /kemasjidan/infaq/{warga}/{tahun}/{bulan}/receipt
    // =========================
    public function receipt($wargaId, $tahun, $bulan)
    {
        $tahun = (int) $tahun;
        $bulan = (int) $bulan;
        if ($bulan < 1 || $bulan > 12)
            abort(404);

        $warga = Warga::findOrFail($wargaId);

        $trx = InfaqKemasjidan::query()
            ->where('warga_id', $warga->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        if (!$trx || (float) $trx->nominal <= 0) {
            abort(404, 'Kwitansi belum tersedia untuk bulan ini.');
        }

        $bulanNama = $this->bulanNama($bulan);
        $tanggal = $trx->tanggal ? \Carbon\Carbon::parse($trx->tanggal) : now();

        $meta = $this->receiptFileMeta($warga, $tahun, $bulan, $trx);
        $verifyUrl = route('kemasjidan.infaq.verify', [
            'warga' => $warga->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ], true);

        $qrSvg = QrCode::format('svg')->size(100)->margin(0)->generate($verifyUrl);
        $qrSvg = preg_replace('/\s*(width|height)="[^"]*"/i', '', $qrSvg);

        $payload = [
            'warga' => $warga,
            'trx' => $trx,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'bulanNama' => $bulanNama,
            'nominal' => (float) $trx->nominal,
            'tanggal' => $tanggal,
            'kode' => $meta['kode'],
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrSvg,
            'watermark' => 'Sistem Informasi Infaq Bulanan Al Iman',
            'alamatYayasan' => config('app.org_alamat', 'JL. Sutorejo Tengah X/2-4 Dukuh Sutorejo - Mulyorejo, Surabaya, Jawa Timur 60113'),
            'teleponYayasan' => config('app.org_telp', '0853 6936 9517'),
            'emailYayasan' => config('app.org_email', 'masjidalimansurabaya@gmail.com'),
            'logoDataUri' => $this->logoToDataUri(public_path('img/photos/logo_yys.png')),
            'ttdNama' => config('app.org_ttd_kemasjidan_nama', '____________________'),
            'ttdJabatan' => config('app.org_ttd_kemasjidan_jabatan', 'Koordinator Bidang Kemasjidan'),
        ];

        // MODE PDF langsung download jika ?pdf=1
        if (request()->boolean('pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bidang.kemasjidan.infaq.kwitansi', $payload)
                ->setPaper('a6', 'portrait');

            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);

            $filename = 'Kwitansi-Infaq-Kemasjidan-' . $tahun . '-' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) . '-' . \Illuminate\Support\Str::slug($warga->nama ?? 'warga') . '.pdf';
            return $pdf->download($filename);
        }

        return view('bidang.kemasjidan.infaq.kwitansi', $payload);
    }

    // =========================
    // VERIFY
    // =========================
    public function verifyReceipt($wargaId, $tahun, $bulan)
    {
        $tahun = (int) $tahun;
        $bulan = (int) $bulan;
        if ($bulan < 1 || $bulan > 12)
            abort(404);

        $warga = Warga::findOrFail($wargaId);

        $trx = InfaqKemasjidan::query()
            ->where('warga_id', $warga->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        $valid = $trx && (float) $trx->nominal > 0;
        $nominal = (float) ($trx->nominal ?? 0);

        $bulanNama = $this->bulanNama($bulan);
        $kode = $this->receiptFileMeta($warga, $tahun, $bulan, $trx)['kode'];

        return view('bidang.kemasjidan.infaq.verify', [
            'warga' => $warga,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'bulanNama' => $bulanNama,
            'nominal' => $nominal,
            'kode' => $kode,
            'valid' => $valid,
        ]);
    }

    // =========================
    // CHECK PAID (AJAX)
    // =========================
    public function checkPaid(Request $request)
    {
        $request->validate([
            'warga_id' => ['required', 'integer', 'exists:wargas,id'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $paid = InfaqKemasjidan::query()
            ->where('warga_id', (int) $request->warga_id)
            ->where('tahun', (int) $request->tahun)
            ->where('bulan', (int) $request->bulan)
            ->where('nominal', '>', 0)
            ->exists();

        return response()->json(['found' => true, 'paid' => $paid]);
    }

    // =========================
    // LOOKUP WARGA by HP (AJAX)
    // =========================
    /**
     * AJAX lookup warga by nomor HP (?hp=08xxxx)
     */
    public function lookupWarga(Request $request)
    {
        $request->validate([
            'hp' => ['required', 'string']
        ]);

        $warga = Warga::where('hp', $request->hp)->first();

        if (!$warga) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'id' => $warga->id,
                'nama' => $warga->nama,
                'rt' => $warga->rt,
                'alamat' => $warga->alamat,
                'no' => $warga->no,
                'hp' => $warga->hp,
            ]
        ]);
    }
    // =========================
    // UTIL: file meta & logo
    // =========================
    private function receiptFileMeta(Warga $warga, int $tahun, int $bulan, ?InfaqKemasjidan $trx): array
    {
        $year = (string) $tahun;
        $filename = "{$tahun}-{$warga->id}-" . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) . "-kwitansi.pdf";
        $path = "receipts/infaq-kemasjidan/{$year}/{$filename}";

        // Kode kwitansi: KWKMS/YYYY/xxxxx (pakai id trx jika ada, fallback warga id)
        $seq = $trx?->id ? (string) $trx->id : (string) $warga->id;
        $kode = 'KWKMS/' . $year . '/' . str_pad($seq, 5, '0', STR_PAD_LEFT);

        return compact('year', 'filename', 'path', 'kode');
    }

    private function logoToDataUri(?string $absPath): ?string
    {
        try {
            if (!$absPath || !file_exists($absPath))
                return null;

            $mime = match (strtolower(pathinfo($absPath, PATHINFO_EXTENSION))) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                default => 'application/octet-stream',
            };

            $data = base64_encode(file_get_contents($absPath));
            return "data:{$mime};base64,{$data}";
        } catch (\Throwable $e) {
            return null;
        }
    }
}
