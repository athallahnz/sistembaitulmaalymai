<?php

namespace App\Http\Controllers\Sosial;

use App\Http\Controllers\Controller;
use App\Models\IuranBulanan;
use App\Models\Warga;
use App\Models\Hutang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Traits\HasTransaksiKasBank;
use Yajra\DataTables\Facades\DataTables;


class IuranBulananController extends Controller
{
    use HasTransaksiKasBank;

    public function __construct()
    {
        $this->middleware(['auth']);

        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user?->bidang || $user->bidang->name !== 'Sosial') {
                abort(403, 'Akses khusus Bidang Sosial');
            }

            return $next($request);
        })->except([]); // kalau ada route publik, tambahkan di array
    }

    /**
     * Dashboard Bidang Sosial:
     * - daftar kepala keluarga
     * - status iuran per tahun
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $tahun = (int) ($request->get('tahun') ?: today()->year);

        $kepalas = Warga::query()
            ->kepalaKeluarga()
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('hp', 'like', "%{$q}%")
                        ->orWhere('rt', 'like', "%{$q}%")
                        ->orWhere('alamat', 'like', "%{$q}%");
                });
            })
            ->with([
                'iuranBulanan' => function ($q2) use ($tahun) {
                    $q2->where('tahun', $tahun)
                        ->orderBy('bulan');
                }
            ])
            ->withCount('anggotaKeluarga')    // ⬅️ ini tambahan
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        $ringkas = [
            'tahun' => $tahun,
            'jumlah_kk' => Warga::kepalaKeluarga()->count(),
            'total_anggota' => Warga::count(),
            'total_tagihan' => IuranBulanan::where('tahun', $tahun)->sum('nominal_tagihan'),
            'total_terbayar' => IuranBulanan::where('tahun', $tahun)->sum('nominal_bayar'),
        ];

        return view('bidang.sosial.iuran.index', [
            'kepalas' => $kepalas,
            'ringkas' => $ringkas,
            'q' => $q,
            'tahun' => $tahun,
        ]);
    }

    public function datatable(Request $request)
    {
        $tahun = (int) ($request->tahun ?: today()->year);
        $q = trim((string) $request->get('q')); // <— dari form "Cari"

        $query = Warga::kepalaKeluarga()
            ->withCount('anggotaKeluarga')
            ->with(['iuranBulanan' => fn($q2) => $q2->where('tahun', $tahun)])
            ->whereNull('warga_id')
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('hp', 'like', "%{$q}%")
                        ->orWhere('rt', 'like', "%{$q}%")
                        ->orWhere('alamat', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama');

        return DataTables::of($query)
            ->addColumn('peserta', function ($kk) {
                return ($kk->anggota_keluarga_count + 1) . ' org';
            })
            ->addColumn('status', function ($kk) {
                $totalBulan = 12;
                $lunas = $kk->iuranBulanan
                    ->where('status', IuranBulanan::STATUS_LUNAS)
                    ->count();

                if ($lunas === $totalBulan) {
                    $badge = '<span class="badge text-bg-success">Lunas</span>';
                } elseif ($lunas > 0) {
                    $badge = '<span class="badge text-bg-warning text-dark">Sebagian</span>';
                } else {
                    $badge = '<span class="badge text-bg-danger">Belum</span>';
                }

                return "{$badge} <span class='ms-1 small text-muted'>($lunas/$totalBulan)</span>";
            })
            ->addColumn(
                'tagihan',
                fn($kk) =>
                number_format($kk->iuranBulanan->sum('nominal_tagihan'), 0, ',', '.')
            )
            ->addColumn(
                'bayar',
                fn($kk) =>
                number_format($kk->iuranBulanan->sum('nominal_bayar'), 0, ',', '.')
            )
            ->addColumn('aksi', function ($kk) use ($tahun) {
                return '
                <a href="' . route('sosial.iuran.show', [$kk->id, 'tahun' => $tahun]) . '"
                    class="btn btn-info btn-sm mb-2">Detail</a>

                <button class="btn btn-warning btn-sm btn-anggota mb-2"
                    data-id="' . $kk->id . '"
                    data-nama="' . $kk->nama . '">
                    <i class="bi bi-people-fill"></i>
                </button>
            ';
            })
            ->rawColumns(['aksi', 'status'])
            ->make(true);
    }

    public function anggota($kk)
    {
        $kepala = Warga::with(['anggotaKeluarga'])->findOrFail($kk);

        return response()->json([
            'kepala' => $kepala->nama,
            'status_kepala' => $kepala->status_keluarga,
            'anggota' => $kepala->anggotaKeluarga->map(function ($a) {
                return [
                    'nama' => $a->nama,
                    'status' => $a->status_keluarga,
                ];
            }),
            'jumlah' => $kepala->anggotaKeluarga->count() + 1,
        ]);
    }


    /**
     * Form input iuran untuk 1 KK & 1 bulan
     * (opsional, jika pakai modal dari index aja, bisa skip view ini)
     */
    public function create(Request $request)
    {
        $tahun = (int) ($request->get('tahun') ?: today()->year);
        $bulan = (int) ($request->get('bulan') ?: today()->month);

        $kepalas = Warga::kepalaKeluarga()
            ->orderBy('nama')
            ->get();

        return view('bidang.sosial.iuran.create', compact('kepalas', 'tahun', 'bulan'));
    }

    /**
     * Simpan / update iuran untuk 1 KK & 1 bulan.
     * Logika:
     * - Jika belum ada record → buat baru
     * - Update nominal_tagihan & nominal_bayar
     * - Status:
     *    - belum: bayar = 0
     *    - sebagian: 0 < bayar < tagihan
     *    - lunas: bayar >= tagihan
     * - Blok jika sudah lunas lalu ingin nurunin nominal_bayar (rollback)
     */
    /**
     * Catat penerimaan kas/bank untuk Iuran Sinoman (double-entry).
     * - Debit  : Kas Sosial / Bank Sosial (tergantung metode_bayar)
     * - Kredit : Pendapatan Iuran Sosial (ISI ID akun pendapatan iuran sesuai COA)
     */

    protected function catatPenerimaanIuranTerikat(
        IuranBulanan $iuran,
        Warga $wargaKepala,
        int $tambahanBayar
    ): void {
        if ($tambahanBayar <= 0) return;

        $user = auth()->user();
        if (!$user) {
            throw new \Exception('User tidak terautentikasi.');
        }

        $role = $user->role;
        $bidangId  = is_numeric($user->bidang_name ?? null) ? (int) $user->bidang_name : null;
        $bidangKey = $role === 'Bendahara' ? null : (string) $bidangId; // konsisten string "3"

        $metodeBayar = strtolower(trim((string) $iuran->metode_bayar));
        if (!in_array($metodeBayar, ['tunai', 'transfer'], true)) {
            throw new \Exception('Metode pembayaran tidak valid.');
        }

        // DEBIT: Kas/Bank
        $akunDebitId = $this->resolveAkunPenerimaanByMetode($role, $bidangId, $metodeBayar);

        // KREDIT: Hutang Program Sosial (Dana Kematian)
        $akunHutangTerikatId = 5005;

        if (!$akunDebitId) {
            throw new \Exception('Akun kas/bank belum dikonfigurasi.');
        }

        $kodePrefix = $this->makeKodePrefix($role, $bidangId);
        $kode = $kodePrefix . '-DK-' . now()->format('YmdHis') . '-' . $iuran->id;

        $tanggal = $iuran->tanggal_bayar
            ? (is_string($iuran->tanggal_bayar) ? $iuran->tanggal_bayar : $iuran->tanggal_bayar->toDateString())
            : now()->toDateString();

        $bulan = str_pad((string) $iuran->bulan, 2, '0', STR_PAD_LEFT);

        // Semua diperlakukan sebagai Dana Kematian => pakai [DK]
        $deskripsi = "[DK] Iuran {$iuran->tahun}-{$bulan} - {$wargaKepala->nama}";

        // ===============================
        // TRANSAKSI + LEDGER (double-entry)
        // ===============================
        $req = new Request([
            'kode_transaksi' => $kode,
            'tanggal_transaksi' => $tanggal,
            'type' => 'penerimaan',
            'deskripsi' => $deskripsi,
            'amount' => $tambahanBayar,
            'bidang_name' => $bidangKey,
        ]);

        $trxAkun = $this->storeTransactionOrFail(
            $req,
            (int) $akunDebitId,
            (int) $akunHutangTerikatId
        );

        // ===============================
        // 1) SUB-LEDGER: Hutang per Warga (untuk FIFO santunan)
        // ===============================
        Hutang::create([
            'user_id' => $user->id,
            'akun_keuangan_id' => $akunHutangTerikatId,
            'parent_id' => null,
            'warga_kepala_id' => $iuran->warga_kepala_id,
            'iuran_bulanan_id' => $iuran->id,
            'jumlah' => $tambahanBayar,
            'tanggal_jatuh_tempo' => now()->addYears(50)->toDateString(),
            'deskripsi' => $deskripsi,
            'status' => 'belum_lunas',
            'bidang_name' => $bidangKey,
            'kode_transaksi' => $kode,
            'transaksi_id' => $trxAkun->id ?? null,
        ]);

        // ===============================
        // 2) SUB-LEDGER: POOL (SELALU)
        // ===============================
        $pool = Hutang::firstOrCreate(
            [
                'akun_keuangan_id' => $akunHutangTerikatId,
                'warga_kepala_id'  => null,
                'bidang_name'      => $bidangKey,
                'kode_transaksi'   => 'DK-POOL',
            ],
            [
                'user_id' => $user->id,
                'parent_id' => null,
                'iuran_bulanan_id' => null,
                'jumlah' => 0,
                'deskripsi' => '[DK-POOL] Dana Program Sosial (POOL)',
                'status' => 'belum_lunas',
                'tanggal_jatuh_tempo' => now()->addYears(50)->toDateString(),
            ]
        );

        $pool->jumlah = (float) $pool->jumlah + (float) $tambahanBayar;
        $pool->save();
    }

    public function store(Request $request)
    {
        $request->validate([
            'warga_kepala_id' => ['required', 'exists:wargas,id'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'nominal_tagihan' => ['required', 'integer', 'min:0'],
            'nominal_bayar' => ['required', 'integer', 'min:0'],

            // metode_bayar WAJIB jika nominal_bayar > 0
            'metode_bayar' => ['nullable', Rule::in(['tunai', 'transfer'])],
        ]);

        return DB::transaction(function () use ($request) {
            $wargaId = (int) $request->warga_kepala_id;
            $tahun = (int) $request->tahun;
            $bulan = (int) $request->bulan;

            /** @var \App\Models\IuranBulanan|null $iuran */
            $iuran = IuranBulanan::where('warga_kepala_id', $wargaId)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->lockForUpdate()
                ->first();

            $tagihanBaru = (int) $request->nominal_tagihan;
            $bayarBaru = (int) $request->nominal_bayar;

            $bayarLama = $iuran ? (int) $iuran->nominal_bayar : 0;
            $tambahanBayar = max(0, $bayarBaru - $bayarLama);

            if (!$iuran) {
                $iuran = new IuranBulanan([
                    'warga_kepala_id' => $wargaId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                ]);
            } else {
                // blok rollback untuk yang sudah lunas
                if (
                    $iuran->status === IuranBulanan::STATUS_LUNAS &&
                    $bayarBaru < (int) $iuran->nominal_bayar
                ) {
                    return back()
                        ->withInput()
                        ->with('error', 'Transaksi gagal: Iuran bulan tersebut sudah LUNAS, nominal bayar tidak boleh dikurangi.');
                }
            }

            $iuran->nominal_tagihan = $tagihanBaru;
            $iuran->nominal_bayar = $bayarBaru;

            // Set metode_bayar hanya jika ada pembayaran (bayarBaru > 0).
            // Ini memastikan metode tersimpan rapi dan resolve akun debit tidak null ketika harus jurnal.
            $iuran->metode_bayar = $bayarBaru > 0 ? $request->metode_bayar : null;

            if ($bayarBaru <= 0) {
                $iuran->status = IuranBulanan::STATUS_BELUM;
                $iuran->tanggal_bayar = null;
            } elseif ($bayarBaru > 0 && $bayarBaru < $tagihanBaru) {
                $iuran->status = IuranBulanan::STATUS_SEBAGIAN;
                $iuran->tanggal_bayar = today();
            } else {
                $iuran->status = IuranBulanan::STATUS_LUNAS;
                $iuran->tanggal_bayar = today();
            }

            $iuran->save();

            // === PENCATATAN JURNAL KAS/BANK + HUTANG TERIKAT ===
            // hanya untuk tambahan bayar, bukan total bayar
            if ($tambahanBayar > 0) {
                $warga = Warga::kepalaKeluarga()->find($wargaId);
                if ($warga) {
                    $this->catatPenerimaanIuranTerikat($iuran, $warga, $tambahanBayar);
                }
            }

            return redirect()
                ->route('sosial.iuran.show', [$wargaId, 'tahun' => $tahun])
                ->with('success', 'Iuran bulan ' . $iuran->nama_bulan . ' untuk ' . ($warga?->nama ?? 'warga') . ' berhasil disimpan.');
        });
    }


    public function update(Request $request, $wargaId)
    {
        $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'nominal_tagihan' => ['required', 'array'],
            'nominal_tagihan.*' => ['nullable', 'integer', 'min:0'],
            'nominal_bayar' => ['required', 'array'],
            'nominal_bayar.*' => ['nullable', 'integer', 'min:0'],

            // divalidasi nullable dulu; nanti kita wajibkan secara kondisional (jika ada tambahan bayar)
            'metode_bayar' => ['nullable', Rule::in(['tunai', 'transfer'])],
        ]);

        return DataTables::transaction(function () use ($request, $wargaId) {
            $tahun = (int) $request->tahun;

            // pastikan ini kepala keluarga
            $warga = Warga::kepalaKeluarga()->findOrFail($wargaId);

            $tagihanArr = $request->input('nominal_tagihan', []);
            $bayarArr = $request->input('nominal_bayar', []);

            // Ambil semua iuran existing untuk tahun tsb, lock untuk update
            $existing = IuranBulanan::where('warga_kepala_id', $warga->id)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->get()
                ->keyBy('bulan'); // key: 1..12

            // 1) PASS PERTAMA: cek pelanggaran rollback (turunkan nominal_bayar dari yang sudah lunas)
            foreach (range(1, 12) as $bulan) {
                /** @var \App\Models\IuranBulanan|null $row */
                $row = $existing->get($bulan);
                $newBayar = (int) ($bayarArr[$bulan] ?? 0);

                if ($row && $row->status === IuranBulanan::STATUS_LUNAS && $newBayar < (int) $row->nominal_bayar) {
                    $namaBulan = (new IuranBulanan(['bulan' => $bulan]))->nama_bulan;

                    return back()
                        ->withInput()
                        ->with('error', 'Transaksi gagal: Iuran bulan ' . $namaBulan . ' sudah LUNAS, nominal bayar tidak boleh dikurangi.');
                }
            }

            // 1.5) PRECHECK: apakah ada tambahan bayar pada submit ini?
            $adaTambahanBayar = false;
            foreach (range(1, 12) as $bulan) {
                $row = $existing->get($bulan);
                $oldBayar = $row ? (int) $row->nominal_bayar : 0;
                $newBayar = (int) ($bayarArr[$bulan] ?? 0);

                if ($newBayar > $oldBayar) {
                    $adaTambahanBayar = true;
                    break;
                }
            }

            // Jika ada tambahan bayar, metode_bayar wajib (karena untuk menentukan akun debit)
            if ($adaTambahanBayar && blank($request->metode_bayar)) {
                return back()
                    ->withInput()
                    ->withErrors(['metode_bayar' => 'Metode bayar wajib diisi jika ada pembayaran.']);
            }

            // 2) PASS KEDUA: create/update per bulan + catat jurnal & hutang terikat untuk tambahan bayar
            foreach (range(1, 12) as $bulan) {
                /** @var \App\Models\IuranBulanan|null $row */
                $row = $existing->get($bulan);

                $newTagihan = (int) ($tagihanArr[$bulan] ?? 0);
                $newBayar = (int) ($bayarArr[$bulan] ?? 0);

                $oldBayar = $row ? (int) $row->nominal_bayar : 0;
                $tambahanBayar = max(0, $newBayar - $oldBayar);

                // kalau tidak ada record + nilai 0 semua → lewati (tidak buat baris baru)
                if (!$row && $newTagihan === 0 && $newBayar === 0) {
                    continue;
                }

                if (!$row) {
                    $row = new IuranBulanan([
                        'warga_kepala_id' => $warga->id,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                    ]);
                }

                // set metode_bayar hanya jika ada tambahan bayar
                if ($tambahanBayar > 0) {
                    $row->metode_bayar = $request->metode_bayar;
                } elseif ($newBayar <= 0) {
                    // kalau tidak ada bayar sama sekali, rapikan metode menjadi null
                    $row->metode_bayar = null;
                }
                // catatan: jika newBayar == oldBayar > 0, kita biarkan metode_bayar existing (tidak diubah)

                $row->nominal_tagihan = $newTagihan;
                $row->nominal_bayar = $newBayar;

                // Tentukan status & tanggal_bayar
                if ($newBayar <= 0) {
                    $row->status = IuranBulanan::STATUS_BELUM;
                    $row->tanggal_bayar = null;
                } elseif ($newBayar > 0 && $newBayar < $newTagihan) {
                    $row->status = IuranBulanan::STATUS_SEBAGIAN;
                    $row->tanggal_bayar = today();
                } else {
                    $row->status = IuranBulanan::STATUS_LUNAS;
                    $row->tanggal_bayar = today();
                }

                $row->save();

                if ($tambahanBayar > 0) {
                    $this->catatPenerimaanIuranTerikat($row, $warga, $tambahanBayar);
                }
            }

            return redirect()
                ->route('sosial.iuran.show', [$warga->id, 'tahun' => $tahun])
                ->with('success', 'Iuran bulanan untuk ' . ($warga->nama ?? 'warga') . ' tahun ' . $tahun . ' berhasil diperbarui.');
        });
    }

    /**
     * Detail iuran untuk satu kepala keluarga (per tahun).
     */
    public function show($wargaId, Request $request)
    {
        $tahun = (int) ($request->get('tahun') ?: today()->year);

        $warga = Warga::kepalaKeluarga()
            ->with([
                'iuranBulanan' => function ($q) use ($tahun) {
                    $q->where('tahun', $tahun)->orderBy('bulan');
                }
            ])
            ->findOrFail($wargaId);

        // siapkan status per bulan 1–12
        $bulanList = [];
        for ($m = 1; $m <= 12; $m++) {
            $bulanList[$m] = [
                'nama' => (new IuranBulanan(['bulan' => $m]))->nama_bulan,
                'data' => null,
                'status' => IuranBulanan::STATUS_BELUM,
            ];
        }

        foreach ($warga->iuranBulanan as $row) {
            $bulanList[$row->bulan]['data'] = $row;
            $bulanList[$row->bulan]['status'] = $row->status;
        }

        return view('bidang.sosial.iuran.detail-iuran', [
            'warga' => $warga,
            'tahun' => $tahun,
            'bulanList' => $bulanList,
        ]);
    }

    /**
     * Cek via AJAX: apakah warga (berdasarkan HP) sudah bayar iuran bulan/tahun tertentu.
     * Mirip Kemasjidan::checkPaid tapi pakai numeric bulan & tahun.
     */
    public function checkPaid(Request $request)
    {
        $request->validate([
            'hp' => ['required', 'string'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
        ]);

        $warga = Warga::kepalaKeluarga()
            ->where('hp', $request->hp)
            ->first();

        if (!$warga) {
            return response()->json(['found' => false, 'paid' => false]);
        }

        $iuran = IuranBulanan::where('warga_kepala_id', $warga->id)
            ->where('tahun', (int) $request->tahun)
            ->where('bulan', (int) $request->bulan)
            ->first();

        if (!$iuran) {
            return response()->json(['found' => true, 'paid' => false, 'status' => IuranBulanan::STATUS_BELUM]);
        }

        return response()->json([
            'found' => true,
            'paid' => $iuran->status === IuranBulanan::STATUS_LUNAS,
            'status' => $iuran->status,
            'data' => [
                'nominal_tagihan' => $iuran->nominal_tagihan,
                'nominal_bayar' => $iuran->nominal_bayar,
                'tanggal_bayar' => optional($iuran->tanggal_bayar)->toDateString(),
            ],
        ]);
    }
}
