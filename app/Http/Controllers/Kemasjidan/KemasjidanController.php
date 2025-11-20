<?php

namespace App\Http\Controllers\Kemasjidan;

use App\Http\Controllers\Controller;
use App\Traits\HasTransaksiKasBank;
use App\Models\Warga;
use App\Models\InfaqSosial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;
use PDF;

class KemasjidanController extends Controller
{
    use HasTransaksiKasBank;

    public function __construct()
    {
        // Proteksi khusus halaman Kemasjidan (kecuali kalau kamu mau mengecualikan endpoint tertentu)
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next): mixed {
            // Jika user tidak punya relasi bidang, atau bukan Kemasjidan → tolak
            $user = auth()->user();
            if (!$user?->bidang || $user->bidang->name !== 'Kemasjidan') {
                abort(403, 'Akses khusus Bidang Kemasjidan');
            }
            return $next($request);
        })->except([]); // jika ada route yang ingin dikecualikan, sebutkan di array
    }

    /**
     * Dashboard Kemasjidan (pakai modal untuk create infaq)
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

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
            ->with('infaq')
            ->withCount('anggotaKeluarga')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        // 🔍 hitung semua KK yang punya infaq.total > 0
        $jumlahSudahBayar = Warga::kepalaKeluarga()
            ->whereHas('infaq', function ($q) {
                $q->where('total', '>', 0);
            })
            ->count();

        $totalInfaq = InfaqSosial::from('infaq_sosials as i')
            ->join('wargas as w', 'i.warga_id', '=', 'w.id')
            ->whereNull('w.warga_id')
            ->sum('i.total');

        $ringkas = [
            'jumlah_warga' => Warga::kepalaKeluarga()->count(),
            'total_infaq' => $totalInfaq,
            'jumlah_bayar' => $jumlahSudahBayar, // bisa pakai ini
        ];

        return view('bidang.kemasjidan.infaq.index', compact('wargas', 'ringkas', 'q', 'jumlahSudahBayar'));
    }

    public function datatable(Request $request)
    {
        // Filter dari form "Cari"
        $q = trim((string) $request->get('q'));

        // 🔍 Filter dari search bawaan DataTables (kotak kanan atas)
        $globalSearch = trim((string) $request->input('search.value', ''));

        $query = Warga::kepalaKeluarga()
            ->with('infaq')
            // filter dasar dari form
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('hp', 'like', "%{$q}%")
                        ->orWhere('rt', 'like', "%{$q}%")
                        ->orWhere('alamat', 'like', "%{$q}%");
                });
            })
            // filter tambahan dari DataTables global search
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

            ->addColumn('status_infaq', function ($w) {
                $totalInfaq = optional($w->infaq)->total ?? 0;
                $paid = $totalInfaq > 0;

                $class = $paid ? 'text-bg-success text-white' : 'text-bg-secondary';
                $text = $paid ? 'Sudah Pernah Bayar' : 'Belum Pernah Bayar';

                return '<span class="badge ' . $class . '">' . $text . '</span>';
            })

            ->addColumn('total_infaq', function ($w) {
                $totalInfaq = optional($w->infaq)->total ?? 0;
                return 'Rp ' . number_format($totalInfaq, 0, ',', '.');
            })

            ->addColumn('aksi', function ($w) {
                $url = route('kemasjidan.infaq.detail', $w->id);

                return '<a href="' . $url . '" class="btn btn-info btn-sm">
                        Detail
                    </a>';
            })

            ->rawColumns(['status_infaq', 'aksi'])
            ->make(true);
    }

    /**
     * Halaman create terpisah (kalau kamu mau selain modal)
     * Tidak wajib dipakai jika semua input lewat modal di index.
     */
    public function create()
    {
        return view('bidang.kemasjidan.infaq.create');
    }
    /**
     * Simpan infaq (otomatis create/udpate Warga berdasarkan HP)
     */

    /**
     * Catat penerimaan kas/bank untuk Infaq Kemasjidan (double-entry).
     * - Debit  : Kas Kemasjidan / Bank Kemasjidan (by metode_bayar)
     * - Kredit : Pendapatan Infaq Kemasjidan
     *
     * $bulanKolom = nama kolom di InfaqSosial, mis. 'januari', 'februari', ...
     */
    protected function catatPenerimaanInfaq(
        Warga $warga,
        string $bulanKolom,
        float $nominal,
        ?string $metodeBayar
    ): void {
        if ($nominal <= 0) {
            return;
        }

        $user = auth()->user();
        if (!$user) {
            Log::warning('catatPenerimaanInfaq dipanggil tanpa user login, transaksi kas di-skip.');
            return;
        }

        $role = $user->role;
        $bidangId = is_numeric($user->bidang_name ?? null) ? (int) $user->bidang_name : null;

        // 1) Pilih akun sisi DEBIT berdasarkan metode_bayar
        $akunDebitId = $this->resolveAkunPenerimaanByMetode($role, $bidangId, $metodeBayar);

        // 2) Akun pendapatan infaq kemasjidan (KREDIT)
        $akunPendapatanInfaqId = 2028;

        if (!$akunDebitId || !$akunPendapatanInfaqId) {
            Log::warning('Akun debit (kas/bank) atau pendapatan infaq belum dikonfigurasi, jurnal kas di-skip.', [
                'akun_debit' => $akunDebitId,
                'akun_pendapatan' => $akunPendapatanInfaqId,
                'metode_bayar' => $metodeBayar,
            ]);
            return;
        }

        // Label bulan
        $mapBulan = [
            'januari' => 'Januari',
            'februari' => 'Februari',
            'maret' => 'Maret',
            'april' => 'April',
            'mei' => 'Mei',
            'juni' => 'Juni',
            'juli' => 'Juli',
            'agustus' => 'Agustus',
            'september' => 'September',
            'oktober' => 'Oktober',
            'november' => 'November',
            'desember' => 'Desember',
        ];
        $namaBulan = $mapBulan[strtolower($bulanKolom)] ?? ucfirst($bulanKolom);

        $kodePrefix = $this->makeKodePrefix($role, $bidangId);
        $kode = $kodePrefix . '-INF-' . now()->format('YmdHis') . '-' . $warga->id;

        $tanggal = now()->toDateString();

        $labelMetode = $metodeBayar ? (' [' . ucfirst($metodeBayar) . ']') : '';

        $req = new Request([
            'kode_transaksi' => $kode,
            'tanggal_transaksi' => $tanggal,
            'type' => 'penerimaan',
            'deskripsi' => "Infaq Kemasjidan bulan {$namaBulan} - {$warga->nama} ({$warga->hp}){$labelMetode}",
            'amount' => $nominal,
            'bidang_name' => $role === 'Bendahara' ? null : $bidangId,
        ]);

        $this->storeTransaction($req, $akunDebitId, $akunPendapatanInfaqId);
    }

    public function store(Request $request)
    {
        $request->validate([
            // data warga
            'hp' => ['required', 'string', 'max:255'],
            'nama' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no' => ['nullable', 'string', 'max:255'],

            // opsi PIN
            'auto_pin' => ['nullable', 'boolean'],
            'pin' => ['nullable', 'string', 'min:4', 'max:16'],

            // pembayaran
            'bulan' => ['required', Rule::in(InfaqSosial::monthColumns())],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'metode_bayar' => ['nullable', 'string', 'max:50'],  // ⬅️ baru
        ]);

        return DB::transaction(function () use ($request) {
            $hp = trim($request->hp);

            $generatedPin = null;
            $pinToSave = null;

            if ($request->boolean('auto_pin')) {
                $generatedPin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $pinToSave = $generatedPin;
            } elseif ($request->filled('pin')) {
                $pinToSave = $request->pin;
            }

            // 1) cari/buat warga by HP
            $warga = Warga::where('hp', $hp)->first();
            if (!$warga) {
                $warga = Warga::create([
                    'nama' => $request->nama ?? '-',
                    'rt' => $request->rt ?? '-',
                    'alamat' => $request->alamat,
                    'no' => $request->no,
                    'hp' => $hp,
                    'pin' => $pinToSave,
                ]);
            } else {
                $warga->update(array_filter([
                    'nama' => $request->nama,
                    'rt' => $request->rt,
                    'alamat' => $request->alamat,
                    'no' => $request->no,
                    'pin' => $pinToSave,
                ], fn($v) => $v !== null && $v !== ''));
            }

            $bulan = $request->bulan;
            $nominal = (float) $request->nominal;
            $metodeBayar = $request->metode_bayar;

            // 2) lock baris infaq
            $infaq = InfaqSosial::where('warga_id', $warga->id)->lockForUpdate()->first();

            if ($infaq) {
                if ((float) $infaq->$bulan > 0) {
                    return back()
                        ->withInput()
                        ->with('error', 'Transaksi gagal: Infaq bulan ' . ucfirst($bulan) . ' untuk ' . ($warga->nama ?? $warga->hp) . ' sudah LUNAS.');
                }
            } else {
                $infaq = InfaqSosial::create(
                    ['warga_id' => $warga->id] +
                    array_fill_keys(InfaqSosial::monthColumns(), 0.00) +
                    ['total' => 0.00]
                );
            }

            // 3) set nilai bulan
            $infaq->$bulan = $nominal;

            // 4) hitung total
            $total = 0.0;
            foreach (InfaqSosial::monthColumns() as $m) {
                $total += (float) $infaq->$m;
            }
            $infaq->total = $total;
            $infaq->metode_bayar = $metodeBayar;
            $infaq->save();

            // === JURNAL KAS/BANK (double-entry) ===
            $this->catatPenerimaanInfaq($warga, $bulan, $nominal, $metodeBayar);

            // redirect + flash message
            $redirect = redirect()
                ->route('kemasjidan.infaq.index')
                ->with('success', 'Infaq bulan ' . ucfirst($bulan) . ' untuk ' . ($warga->nama ?? $warga->hp) . ' tersimpan.'
                    . ($request->boolean('auto_pin') ? ' PIN warga digenerate otomatis.' : ''));

            if ($generatedPin) {
                $redirect->with('generated_pin', $generatedPin);
            }

            return $redirect;
        });
    }

    public function update(Request $request, $id)
    {
        $bulanList = InfaqSosial::monthColumns();

        // boleh kosong (nullable) tapi jika diisi harus >= 0
        $rules = [];
        foreach ($bulanList as $b) {
            $rules[$b] = ['nullable', 'numeric', 'min:0'];
        }

        // tambah validasi metode_bayar (boleh kosong)
        $rules['metode_bayar'] = ['nullable', 'string', 'max:50'];

        $validated = $request->validate($rules);

        return DB::transaction(function () use ($validated, $id, $bulanList) {
            $warga = Warga::findOrFail($id);

            // kunci baris agar aman dari race-condition
            $infaq = InfaqSosial::where('warga_id', $warga->id)
                ->lockForUpdate()
                ->first();

            if (!$infaq) {
                // buat record baru (semua 0) jika belum ada
                $infaq = InfaqSosial::create(
                    ['warga_id' => $warga->id] +
                    array_fill_keys($bulanList, 0.00) +
                    ['total' => 0.00]
                );
            }

            // ❗ blokir perubahan bulan yang sudah LUNAS (old > 0)
            foreach ($bulanList as $b) {
                if (isset($validated[$b]) && $validated[$b] !== null) {
                    $old = (float) $infaq->$b;
                    $new = (float) $validated[$b];

                    // jika sudah lunas & nilai mau diubah → tolak
                    if ($old > 0 && abs($new - $old) > 0.0001) {
                        return back()
                            ->withInput()
                            ->with('error', 'Transaksi gagal: Bulan ' . ucfirst($b) . ' sudah LUNAS dan tidak bisa diubah.');
                    }
                }
            }

            // simpan bulan-bulan yang BARU diisi (untuk jurnal)
            $bulanBaru = []; // ['januari' => 50000, ...]

            // set nilai untuk bulan yang BELUM lunas saja
            foreach ($bulanList as $b) {
                if (isset($validated[$b]) && $validated[$b] !== null) {
                    $old = (float) $infaq->$b;
                    $new = (float) $validated[$b];

                    if ($old <= 0 && $new > 0) {
                        // isi pertama kali → catat nanti ke jurnal
                        $infaq->$b = $new;
                        $bulanBaru[$b] = $new;
                    }
                    // jika old > 0 → biarkan (no-op)
                }
            }

            // hitung ulang total
            $infaq->total = array_reduce($bulanList, fn($carry, $m) => $carry + (float) $infaq->$m, 0.0);
            $infaq->save();

            // ========= JURNAL DOUBLE ENTRY =========
            $metodeBayar = $validated['metode_bayar'] ?? null;

            foreach ($bulanBaru as $bulan => $nominal) {
                // gunakan helper yang juga dipakai di store()
                $this->catatPenerimaanInfaq($warga, $bulan, (float) $nominal, $metodeBayar);
            }

            return redirect()
                ->route('kemasjidan.infaq.detail', $warga->id)
                ->with('success', 'Data infaq berhasil diperbarui.');
        });
    }

    public function checkPaid(Request $request)
    {
        $request->validate([
            'hp' => ['required', 'string'],
            'bulan' => ['required', Rule::in(InfaqSosial::monthColumns())],
        ]);

        $warga = Warga::where('hp', $request->hp)->first();
        if (!$warga) {
            return response()->json(['found' => false, 'paid' => false]);
        }

        $infaq = InfaqSosial::where('warga_id', $warga->id)->first();
        if (!$infaq) {
            return response()->json(['found' => true, 'paid' => false]);
        }

        $paid = (float) $infaq->{$request->bulan} > 0;
        return response()->json(['found' => true, 'paid' => $paid]);
    }

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

    /**
     * Detail per Warga
     */
    public function show($id)
    {
        $warga = Warga::with('infaq')->findOrFail($id);
        $infaq = $warga->infaq;

        // siapkan ringkas status per bulan (Lunas/Belum)
        $status = [];
        if ($infaq) {
            foreach (InfaqSosial::monthColumns() as $m) {
                $status[$m] = ((float) $infaq->$m) > 0 ? 'Lunas' : 'Belum';
            }
        }

        return view('bidang.kemasjidan.infaq.detail-infaq', compact('warga', 'infaq', 'status'));
    }

    public function receipt($wargaId, $bulan)
    {
        $bulan = strtolower($bulan);
        if (!in_array($bulan, InfaqSosial::monthColumns(), true))
            abort(404);

        $warga = Warga::with('infaq')->findOrFail($wargaId);
        $infaq = $warga->infaq;
        $nominal = (float) ($infaq->$bulan ?? 0);
        if ($nominal <= 0)
            abort(404, 'Kwitansi belum tersedia untuk bulan ini.');

        // meta & verify
        $meta = $this->receiptFileMeta($warga, $bulan);
        $verifyUrl = route('kemasjidan.infaq.verify', ['warga' => $warga->id, 'bulan' => $bulan, 'year' => $meta['year']], true);

        // QR SVG → hapus width/height agar bisa dikecilkan lewat CSS
        $qrSvg = QrCode::format('svg')->size(100)->margin(0)->generate($verifyUrl);
        $qrSvg = preg_replace('/\s*(width|height)="[^"]*"/i', '', $qrSvg); // penting!

        $payload = [
            'warga' => $warga,
            'bulan' => $bulan,
            'nominal' => $nominal,
            'tanggal' => now(),
            'kode' => $meta['kode'],
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrSvg,
            'watermark' => 'Sistem Informasi Infaq Bulanan Al Iman',
            'alamatYayasan' => config('app.org_alamat', 'JL. Sutorejo Tengah X/2-4 Dukuh Sutorejo - Mulyorejo, Surabaya, Jawa Timur 60113'),
            'teleponYayasan' => config('app.org_telp', '0853 6936 9517'),
            'emailYayasan' => config('app.org_email', 'masjidalimansurabaya@gmail.com'),
            'logoDataUri' => $this->logoToDataUri(public_path('img/photos/logo_yys.png')),
            'ttdNama' => config('app.org_ttd_sosial_nama', 'Bpk. Zainal Arifin'),
            'ttdJabatan' => config('app.org_ttd_sosial_jabatan', 'Koordinator Bidang Sosial'),
        ];

        // === MODE PDF: langsung download (tanpa redirect) ===
        if (request()->boolean('pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bidang.kemasjidan.infaq.kwitansi', $payload)
                ->setPaper('a4', 'portrait');

            // opsi (jika perlu):
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);

            $filename = 'Kwitansi-Infaq-' . strtoupper($bulan) . '-' . \Illuminate\Support\Str::slug($warga->nama ?? 'warga') . '.pdf';
            return $pdf->download($filename); // ⬅️ ini yang bikin langsung download
        }

        // === MODE HTML (print-friendly) ===
        return view('bidang.kemasjidan.infaq.kwitansi', $payload);
    }

    private function formatMsisdn62(string $hp): string
    {
        $num = preg_replace('/\D+/', '', $hp);
        if (str_starts_with($num, '0'))
            return '62' . substr($num, 1);
        if (!str_starts_with($num, '62'))
            return '62' . $num;
        return $num;
    }

    private function generateReceiptPdf($warga, string $bulan, float $nominal): array
    {
        $bulan = strtolower($bulan);
        $meta = $this->receiptFileMeta($warga, $bulan);

        // 1) URL verifikasi (absolute)
        $verifyUrl = route('kemasjidan.infaq.verify', [
            'warga' => $warga->id,
            'bulan' => $bulan,
            'year' => $meta['year'],
        ], true);

        // 2) QR SVG
        $qrSvg = QrCode::format('svg')->size(120)->margin(0)->generate($verifyUrl);

        // 3) Reuse jika sudah ada
        if (Storage::disk('public')->exists($meta['path'])) {
            $url = url(Storage::disk('public')->url($meta['path']));
            return ['path' => $meta['path'], 'url' => $url, 'kode' => $meta['kode'], 'verifyUrl' => $verifyUrl, 'qrSvg' => $qrSvg];
        }

        // 4) Render PDF baru
        $pdf = PDF::loadView('bidang.kemasjidan.infaq.kwitansi', [
            'warga' => $warga,
            'bulan' => $bulan,
            'nominal' => $nominal,
            'tanggal' => now(),
            'kode' => $meta['kode'],
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrSvg,
            'watermark' => 'Sistem Informasi Infaq Bulanan Al Iman',
            'alamatYayasan' => config('app.org_alamat', 'Jl. Sutorejo Indah, Surabaya'),
            'teleponYayasan' => config('app.org_telp', '031-xxxxxxx'),
            'emailYayasan' => config('app.org_email', 'info@alimansurabaya.com'),
            'logoDataUri' => $this->logoToDataUri(public_path('images/logo-yayasan.png')),
        ])
            ->setPaper('a5', 'portrait');

        // Opsi dompdf (opsional): aktifkan SVG & remote
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        Storage::disk('public')->put($meta['path'], $pdf->output());
        $url = url(Storage::disk('public')->url($meta['path']));

        return ['path' => $meta['path'], 'url' => $url, 'kode' => $meta['kode'], 'verifyUrl' => $verifyUrl, 'qrSvg' => $qrSvg];
    }

    /**
     * Metadata file PDF (nama & path & kode)
     */

    private function receiptFileMeta($warga, string $bulan): array
    {
        $year = now()->format('Y');
        $safeName = str($warga->nama ?? 'warga')->slug('-');
        $filename = "{$year}-{$warga->id}-{$bulan}-kwitansi.pdf";
        $path = "receipts/infaq/{$year}/{$filename}";
        $kode = 'KW/' . $year . '/' . str_pad((string) ($warga->infaq->id ?? $warga->id), 5, '0', STR_PAD_LEFT);

        return compact('year', 'filename', 'path', 'kode');
    }

    /**
     * Convert logo file ke data-uri base64 (return null kalau tidak ada)
     */
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

    /**
     * Kirim via WA (opsi #1 tanpa Cloud API): buat PDF + redirect ke wa.me
     */
    public function openWhatsappLink($wargaId, $bulan)
    {
        $bulan = strtolower($bulan);
        if (!in_array($bulan, InfaqSosial::monthColumns(), true))
            abort(404);

        $warga = Warga::with('infaq')->findOrFail($wargaId);
        $nominal = (float) ($warga->infaq->$bulan ?? 0);
        if ($nominal <= 0)
            abort(404, 'Kwitansi belum tersedia untuk bulan ini.');

        $doc = $this->generateReceiptPdf($warga, $bulan, $nominal);
        $to = $this->formatMsisdn62($warga->hp);

        $msg = "Assalamualaikum, *{$warga->nama}*%0A"
            . "Berikut kwitansi Infaq bulan *" . ucfirst($bulan) . "*:%0A"
            . "Nominal: *Rp " . number_format($nominal, 0, ',', '.') . "*%0A"
            . "Kode: {$doc['kode']}%0A"
            . "Verifikasi: {$doc['verifyUrl']}%0A%0A"
            . "Unduh kwitansi (PDF): {$doc['url']}%0A%0A"
            . "Terima kasih.";

        $waUrl = "https://wa.me/{$to}?text={$msg}";
        return redirect()->away($waUrl);
    }

    /**
     * Halaman verifikasi kwitansi (sederhana).
     * Bisa ditingkatkan: cek hash, cek status DB, tanda tangan digital, dsb.
     */
    public function verifyReceipt($wargaId, $bulan, $year)
    {
        $bulan = strtolower($bulan);
        if (!in_array($bulan, InfaqSosial::monthColumns(), true))
            abort(404);

        $warga = Warga::with('infaq')->findOrFail($wargaId);
        $nominal = (float) ($warga->infaq->$bulan ?? 0);

        return view('bidang.kemasjidan.infaq.verify', [
            'warga' => $warga,
            'bulan' => $bulan,
            'year' => $year,
            'nominal' => $nominal,
            'kode' => 'KW/' . $year . '/' . str_pad((string) ($warga->infaq->id ?? $warga->id), 5, '0', STR_PAD_LEFT),
            'valid' => $nominal > 0,
        ]);
    }
}
