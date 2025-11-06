<?php

namespace App\Http\Controllers\Sosial;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use App\Models\InfaqSosial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SosialController extends Controller
{
    public function __construct()
    {
        // Proteksi khusus halaman Sosial (kecuali kalau kamu mau mengecualikan endpoint tertentu)
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            // Jika user tidak punya relasi bidang, atau bukan Sosial → tolak
            $user = auth()->user();
            if (!$user?->bidang || $user->bidang->name !== 'Sosial') {
                abort(403, 'Akses khusus Bidang Sosial');
            }
            return $next($request);
        })->except([]); // jika ada route yang ingin dikecualikan, sebutkan di array
    }

    /**
     * Dashboard Sosial (pakai modal untuk create infaq)
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $wargas = Warga::query()
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('hp', 'like', "%{$q}%")
                        ->orWhere('rt', 'like', "%{$q}%")
                        ->orWhere('alamat', 'like', "%{$q}%");
                });
            })
            ->with('infaq')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        // total ringkas (opsional, untuk widget ringkasan)
        $ringkas = [
            'jumlah_warga' => Warga::count(),
            'total_infaq' => InfaqSosial::sum('total'),
        ];

        return view('bidang.sosial.infaq.index', compact('wargas', 'ringkas', 'q'));
    }

    /**
     * Halaman create terpisah (kalau kamu mau selain modal)
     * Tidak wajib dipakai jika semua input lewat modal di index.
     */
    public function create()
    {
        return view('bidang.sosial.infaq.create');
    }
    /**
     * Simpan infaq (otomatis create/udpate Warga berdasarkan HP)
     */

    public function update(Request $request, $id)
    {
        $bulanList = InfaqSosial::monthColumns();

        // boleh kosong (nullable) tapi jika diisi harus >= 0
        $rules = [];
        foreach ($bulanList as $b) {
            $rules[$b] = ['nullable', 'numeric', 'min:0'];
        }
        $request->validate($rules);

        return DB::transaction(function () use ($request, $id, $bulanList) {
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
                if ($request->filled($b)) {
                    $old = (float) $infaq->$b;
                    $new = (float) $request->input($b);

                    // jika sudah lunas & nilai mau diubah → tolak
                    if ($old > 0 && abs($new - $old) > 0.0001) {
                        return back()
                            ->withInput()
                            ->with('error', 'Transaksi gagal: Bulan ' . ucfirst($b) . ' sudah LUNAS dan tidak bisa diubah.');
                    }
                }
            }

            // set nilai untuk bulan yang BELUM lunas saja
            foreach ($bulanList as $b) {
                if ($request->filled($b)) {
                    $old = (float) $infaq->$b;
                    $new = (float) $request->input($b);

                    if ($old <= 0 && $new >= 0) {
                        $infaq->$b = $new; // isi pertama kali
                    }
                    // jika old > 0 → biarkan (no-op)
                }
            }

            // hitung ulang total
            $infaq->total = array_reduce($bulanList, fn($carry, $m) => $carry + (float) $infaq->$m, 0.0);
            $infaq->save();

            return redirect()
                ->route('sosial.infaq.detail', $warga->id)
                ->with('success', 'Data infaq berhasil diperbarui.');
        });
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
        ]);

        return DB::transaction(function () use ($request) {
            $hp = trim($request->hp);

            // tentukan pin yang akan disimpan + siapkan flash jika auto generate
            $generatedPin = null;   // <- untuk ditampilkan sekali ke admin
            $pinToSave = null;   // <- disimpan (akan di-hash oleh mutator)

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
                    'pin' => $pinToSave, // di-hash oleh mutator
                ]);
            } else {
                $warga->update(array_filter([
                    'nama' => $request->nama,
                    'rt' => $request->rt,
                    'alamat' => $request->alamat,
                    'no' => $request->no,
                    'pin' => $pinToSave, // update kalau ada (akan di-hash)
                ], fn($v) => $v !== null && $v !== ''));
            }

            $bulan = $request->bulan;
            $nominal = (float) $request->nominal;

            // 2) lock baris infaq
            $infaq = InfaqSosial::where('warga_id', $warga->id)->lockForUpdate()->first();

            if ($infaq) {
                // blokir jika bulan sudah lunas
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
            $infaq->save();

            // redirect + flash message
            $redirect = redirect()
                ->route('sosial.infaq.index')
                ->with('success', 'Infaq bulan ' . ucfirst($bulan) . ' untuk ' . ($warga->nama ?? $warga->hp) . ' tersimpan.'
                    . ($request->boolean('auto_pin') ? ' PIN warga digenerate otomatis.' : ''));

            // jika auto generate, kirim PIN plaintext sekali pakai via flash
            if ($generatedPin) {
                $redirect->with('generated_pin', $generatedPin);
            }

            return $redirect;
        });
    }

    public function receipt($wargaId, $bulan)
    {
        $bulan = strtolower($bulan);
        if (!in_array($bulan, InfaqSosial::monthColumns(), true)) {
            abort(404);
        }

        $warga = Warga::with('infaq')->findOrFail($wargaId);
        $infaq = $warga->infaq;
        $nominal = (float) ($infaq->$bulan ?? 0);

        if ($nominal <= 0) {
            abort(404, 'Kwitansi belum tersedia untuk bulan ini.');
        }

        // Sederhana: tampilkan view kwitansi HTML (bisa di-print).
        // Nanti bisa kamu ganti ke PDF (dompdf/snappy) bila perlu.
        return view('bidang.sosial.infaq.kwitansi', [
            'warga' => $warga,
            'bulan' => $bulan,
            'nominal' => $nominal,
            'tanggal' => now(),
            'kode' => 'KW/' . now()->format('Y') . '/' . str_pad((string) $infaq->id, 5, '0', STR_PAD_LEFT), // contoh kode
        ]);
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

        return view('bidang.sosial.infaq.detail-infaq', compact('warga', 'infaq', 'status'));
    }

    /**
     * Generate PDF kwitansi → simpan ke storage/public → return path + url.
     * Jika ingin menghindari duplikasi, kamu bisa cek & reuse file yang ada berdasarkan kunci warga+bulan+tahun.
     */
    private function formatMsisdn62(string $hp): string
    {
        $num = preg_replace('/\D+/', '', $hp);
        if (str_starts_with($num, '0'))
            return '62' . substr($num, 1);
        if (!str_starts_with($num, '62'))
            return '62' . $num;
        return $num;
    }

    /**
     * Bangun kode & nama file kwitansi konsisten: 1 file per (warga, bulan, tahun).
     */
    private function receiptFileMeta($warga, string $bulan): array
    {
        $year = now()->year;
        $kode = 'KW/' . $year . '/' . str_pad((string) ($warga->infaq->id ?? $warga->id), 5, '0', STR_PAD_LEFT);
        $fileName = "kwitansi_{$warga->id}_{$bulan}_{$year}.pdf";
        $path = 'kwitansi/' . $fileName;

        return compact('kode', 'fileName', 'path', 'year');
    }

    /**
     * Generate (atau reuse) PDF kwitansi dengan QR & watermark.
     * - QR berisi link verifikasi signed (opsional: bisa non-signed).
     */
    private function generateReceiptPdf($warga, string $bulan, float $nominal): array
    {
        $bulan = strtolower($bulan);
        $meta = $this->receiptFileMeta($warga, $bulan);

        // 1) siapkan URL verifikasi (public-friendly)
        // pakai signed route agar tidak mudah ditebak (opsional; kalau public tanpa login, pastikan middleware VerifyCsrf off untuk GET)
        $verifyUrl = route('sosial.infaq.verify', [
            'warga' => $warga->id,
            'bulan' => $bulan,
            'year' => $meta['year'],
        ], true); // absolute URL

        // 2) render QR ke data-uri (SVG->PNG base64)
        $qrDataUri = QrCode::format('svg')->size(180)->margin(0)->generate($verifyUrl);

        // 3) Reuse file jika sudah ada
        if (Storage::disk('public')->exists($meta['path'])) {
            $url = url(Storage::disk('public')->url($meta['path']));
            return ['path' => $meta['path'], 'url' => $url, 'kode' => $meta['kode'], 'verifyUrl' => $verifyUrl, 'qrSvg' => $qrDataUri];
        }

        // 4) buat PDF baru
        $pdf = PDF::loadView('bidang.sosial.infaq.kwitansi', [
            'warga' => $warga,
            'bulan' => $bulan,
            'nominal' => $nominal,
            'tanggal' => now(),
            'kode' => $meta['kode'],
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrDataUri,
            'watermark' => 'Sistem Infaq Sosial — Yayasan Al Iman', // ganti sesuai yayasan
        ])->setPaper('a5', 'portrait');

        Storage::disk('public')->put($meta['path'], $pdf->output());
        $url = url(Storage::disk('public')->url($meta['path']));

        return ['path' => $meta['path'], 'url' => $url, 'kode' => $meta['kode'], 'verifyUrl' => $verifyUrl, 'qrSvg' => $qrDataUri];
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

        return view('bidang.sosial.infaq.verify', [
            'warga' => $warga,
            'bulan' => $bulan,
            'year' => $year,
            'nominal' => $nominal,
            'kode' => 'KW/' . $year . '/' . str_pad((string) ($warga->infaq->id ?? $warga->id), 5, '0', STR_PAD_LEFT),
            'valid' => $nominal > 0,
        ]);
    }
}
