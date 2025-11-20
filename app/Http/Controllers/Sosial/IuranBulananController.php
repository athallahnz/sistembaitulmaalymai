<?php

namespace App\Http\Controllers\Sosial;

use App\Http\Controllers\Controller;
use App\Models\IuranBulanan;
use App\Models\Warga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\HasTransaksiKasBank;
use DataTables;


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

        $query = Warga::withCount('anggotaKeluarga')
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
    protected function catatPenerimaanIuran(
        IuranBulanan $iuran,
        Warga $warga,
        int $tambahanBayar
    ): void {
        if ($tambahanBayar <= 0) {
            return; // tidak ada tambahan bayar, tidak perlu jurnal baru
        }

        $user = auth()->user();
        if (!$user) {
            Log::warning('catatPenerimaanIuran dipanggil tanpa user login, transaksi kas di-skip.');
            return;
        }

        $role = $user->role;
        $bidangId = is_numeric($user->bidang_name ?? null) ? (int) $user->bidang_name : null;

        // --- 1) Tentukan akun sisi DEBIT berdasarkan metode_bayar ---
        $metodeBayar = $iuran->metode_bayar; // sudah di-set di store()
        $akunDebitId = $this->resolveAkunPenerimaanByMetode($role, $bidangId, $metodeBayar);

        // --- 2) Akun PENDAPATAN IURAN SOSIAL (sisi KREDIT) ---
        // TODO: ganti 3021 dengan ID akun pendapatan iuran sosial di tabel akun_keuangans
        $akunPendapatanIuranId = 2029;

        if (!$akunDebitId || !$akunPendapatanIuranId) {
            Log::warning('Akun debit (kas/bank) atau pendapatan iuran belum dikonfigurasi, jurnal kas di-skip.', [
                'akun_debit' => $akunDebitId,
                'akun_pendapatan' => $akunPendapatanIuranId,
                'metode_bayar' => $metodeBayar,
            ]);
            return;
        }

        // --- 3) Generate kode transaksi unik ---
        $kodePrefix = $this->makeKodePrefix($role, $bidangId);
        $kode = $kodePrefix . '-IUR-' . now()->format('YmdHis') . '-' . $iuran->id;

        $tanggal = $iuran->tanggal_bayar
            ? $iuran->tanggal_bayar->toDateString()
            : now()->toDateString();

        $namaBulan = $iuran->nama_bulan ?? ('Bulan ' . $iuran->bulan);

        // Label metode buat deskripsi
        $labelMetode = $metodeBayar ? (' [' . ucfirst($metodeBayar) . ']') : '';

        // --- 4) Request virtual untuk storeTransaction() ---
        $req = new Request([
            'kode_transaksi' => $kode,
            'tanggal_transaksi' => $tanggal,
            'type' => 'penerimaan',
            'deskripsi' => "Iuran Sinoman {$namaBulan} {$iuran->tahun} - {$warga->nama} (RT {$warga->rt}){$labelMetode}",
            'amount' => $tambahanBayar,
            'bidang_name' => $role === 'Bendahara' ? null : $bidangId,
        ]);

        // --- 5) Simpan double entry ---
        // Debit  : akunDebitId (Kas / Bank Sosial)
        // Kredit : akunPendapatanIuranId
        $this->storeTransaction($req, $akunDebitId, $akunPendapatanIuranId);
    }


    public function store(Request $request)
    {
        $request->validate([
            'warga_kepala_id' => ['required', 'exists:wargas,id'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'nominal_tagihan' => ['required', 'integer', 'min:0'],
            'nominal_bayar' => ['required', 'integer', 'min:0'],
            'metode_bayar' => ['nullable', 'string', 'max:50'],   // ⬅️ penting
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
                if (
                    $iuran->status === IuranBulanan::STATUS_LUNAS &&
                    $bayarBaru < $iuran->nominal_bayar
                ) {
                    return back()
                        ->withInput()
                        ->with('error', 'Transaksi gagal: Iuran bulan tersebut sudah LUNAS, nominal bayar tidak boleh dikurangi.');
                }
            }

            $iuran->nominal_tagihan = $tagihanBaru;
            $iuran->nominal_bayar = $bayarBaru;
            $iuran->metode_bayar = $request->metode_bayar; // ⬅️ disimpan

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

            // === PENCATATAN JURNAL KAS/BANK ===
            $warga = Warga::find($wargaId);
            if ($warga) {
                $this->catatPenerimaanIuran($iuran, $warga, $tambahanBayar);
            }

            return redirect()
                ->route('sosial.iuran.show', [$wargaId, 'tahun' => $tahun])
                ->with('success', 'Iuran bulan ' . $iuran->nama_bulan . ' untuk ' . ($warga->nama ?? 'warga') . ' berhasil disimpan.');
        });
    }

    /**
     * Update iuran untuk 1 kepala keluarga dalam 1 tahun (12 bulan sekaligus).
     */
    public function update(Request $request, $wargaId)
    {
        $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'nominal_tagihan' => ['required', 'array'],
            'nominal_tagihan.*' => ['nullable', 'integer', 'min:0'],
            'nominal_bayar' => ['required', 'array'],
            'nominal_bayar.*' => ['nullable', 'integer', 'min:0'],
        ]);

        return DB::transaction(function () use ($request, $wargaId) {
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

                if ($row && $row->status === IuranBulanan::STATUS_LUNAS && $newBayar < $row->nominal_bayar) {
                    $namaBulan = (new IuranBulanan(['bulan' => $bulan]))->nama_bulan;

                    return back()
                        ->withInput()
                        ->with('error', 'Transaksi gagal: Iuran bulan ' . $namaBulan . ' sudah LUNAS, nominal bayar tidak boleh dikurangi.');
                }
            }

            // 2) PASS KEDUA: create/update per bulan
            foreach (range(1, 12) as $bulan) {
                /** @var \App\Models\IuranBulanan|null $row */
                $row = $existing->get($bulan);

                $newTagihan = (int) ($tagihanArr[$bulan] ?? 0);
                $newBayar = (int) ($bayarArr[$bulan] ?? 0);

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
