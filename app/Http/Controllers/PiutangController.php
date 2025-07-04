<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\Piutang;
use App\Models\User;
use App\Models\Transaksi;
use App\Models\AkunKeuangan;
use App\Models\PendapatanBelumDiterima;
use App\Notifications\HutangJatuhTempo; // Sesuaikan dengan notifikasi yang dibuat
use Illuminate\Notifications\DatabaseNotification;
use Yajra\DataTables\Facades\DataTables;

class PiutangController extends Controller
{
    public function index()
    {
        $bidangName = auth()->user()->bidang_name; // Ambil bidang dari user yang login

        $piutangs = Piutang::with('user', 'akunKeuangan')
            ->where('bidang_name', $bidangName) // Filter berdasarkan bidang
            ->get();

        return view('piutang.index', compact('piutangs'));
    }

    public function create()
    {
        // Ambil akun keuangan dari database
        $akunKeuangan = AkunKeuangan::all();

        // Ambil akun piutang dengan ID 103
        $akunPiutang = AkunKeuangan::where('id', 103)->first();

        // Ambil akun yang memiliki parent_id = 103
        $parentAkunPiutang = AkunKeuangan::where('parent_id', 103)->get();

        // Ambil data user yang login
        $user = auth()->user();
        $bidangName = $user->bidang_name;

        // Tentukan akun kas atau bank berdasarkan role dan bidang
        if ($user->role === 'Bendahara') {
            // Akun Kas untuk Bendahara
            $akunKeuanganKas = 1011; // Akun Kas
            $akunKeuanganBank = 1021; // Akun Bank
        } else {
            // Akun Kas berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            // Akun Bank berdasarkan bidang_id
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];

            // Pilih akun kas dan akun bank berdasarkan bidang_name
            $akunKeuanganKas = $akunKas[$user->bidang_name] ?? null;
            $akunKeuanganBank = $akunBank[$user->bidang_name] ?? null;
        }

        // Menyediakan pilihan akun untuk form
        $akunKeuanganOptions = [
            'Kas' => $akunKeuanganKas,
            'Bank' => $akunKeuanganBank
        ];

        // Ambil saldo masing-masing akun berdasarkan role dan bidang
        $saldos = [];
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id
        foreach ($akunKeuanganOptions as $label => $akunId) {
            if ($user->role === 'Bendahara') {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $akunId)
                    ->whereNull('bidang_name')
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            } else {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $akunId)
                    ->where('bidang_name', $bidang_id)
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            }

            $saldos[$akunId] = $lastSaldo ? $lastSaldo->saldo : 0;
        }


        // Ambil bidang_name selain yang login
        $bidang_name = auth()->user()->bidang_name;

        $users = User::with('bidang')
            ->where('bidang_name', '!=', auth()->user()->bidang_name) // sekarang ini sudah benar
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'Bendahara');
            })
            ->get();

        return view('piutang.create', compact('akunKeuangan', 'akunPiutang', 'parentAkunPiutang', 'users', 'akunKeuanganOptions', 'saldos'));
    }

    public function store(Request $request)
    {
        Log::info('Menerima request untuk menyimpan Piutang', ['data' => $request->all()]);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
            'parent_akun_id' => 'nullable|exists:akun_keuangans,id',
            'jumlah' => 'required|numeric|min:0',
            'tanggal_jatuh_tempo' => 'required|date',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:belum_lunas,lunas',
        ]);

        Log::info('Validasi berhasil', ['validated_data' => $validated]);

        DB::beginTransaction();

        try {
            $tanggalTransaksi = now()->toDateString();
            $user = auth()->user();
            $bidangName = $user->bidang_name;

            // Ambil saldo terakhir berdasarkan role
            if ($user->role === 'Bendahara') {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $validated['akun_keuangan_id'])
                    ->whereNull('bidang_name')
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            } else {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $validated['akun_keuangan_id'])
                    ->where('bidang_name', $bidangName)
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            }

            $saldoSebelumnya = $lastSaldo ? $lastSaldo->saldo : 0;

            Log::info('Mengambil saldo terakhir', [
                'role' => $user->role,
                'akun_keuangan_id' => $validated['akun_keuangan_id'],
                'bidang_name' => $bidangName,
                'saldo_terakhir' => $saldoSebelumnya
            ]);

            // Validasi apakah saldo mencukupi
            if ($saldoSebelumnya < $validated['jumlah']) {
                Log::warning('Saldo tidak mencukupi', [
                    'saldo' => $saldoSebelumnya,
                    'jumlah_pengeluaran' => $validated['jumlah'],
                    'akun_keuangan_id' => $validated['akun_keuangan_id']
                ]);

                return redirect()->back()
                    ->with('error', 'Saldo akun Kas/Bank tidak mencukupi untuk mencatat piutang ini.')
                    ->withInput();
            }

            $saldoSetelahnya = $saldoSebelumnya - $validated['jumlah'];
            $kodeTransaksi = 'PIU-' . now()->format('YmdHis') . '-' . rand(100, 999);

            // Buat entri Piutang
            $piutang = Piutang::create([
                'user_id' => $validated['user_id'],
                'akun_keuangan_id' => $validated['akun_keuangan_id'],
                'parent_id' => $request->parent_akun_id ?? null,
                'jumlah' => $validated['jumlah'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                'deskripsi' => $validated['deskripsi'],
                'status' => $validated['status'],
                'bidang_name' => $bidangName,
            ]);

            Log::info('Piutang berhasil dibuat', ['piutang_id' => $piutang->id]);

            // Simpan Transaksi kas/bank
            Transaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'tanggal_transaksi' => $tanggalTransaksi,
                'type' => 'pengeluaran',
                'deskripsi' => 'Pencatatan Piutang ke ' . $piutang->user->name,
                'akun_keuangan_id' => $validated['akun_keuangan_id'],
                'parent_akun_id' => $request->parent_akun_id ?? null,
                'amount' => $validated['jumlah'],
                'saldo' => $saldoSetelahnya,
                'bidang_name' => $bidangName,
            ]);

            Log::info('Transaksi kas/bank disimpan', [
                'kode_transaksi' => $kodeTransaksi,
                'saldo_setelah' => $saldoSetelahnya,
                'akun_keuangan_id' => $validated['akun_keuangan_id']
            ]);

            // Kirim notifikasi
            $userToNotify = User::find($validated['user_id']);
            if ($userToNotify) {
                Notification::send($userToNotify, new HutangJatuhTempo($piutang));
                Log::info('Notifikasi jatuh tempo dikirim', [
                    'kepada_user_id' => $userToNotify->id,
                    'piutang_id' => $piutang->id
                ]);
            }

            DB::commit();
            Log::info('Proses penyimpanan piutang selesai sukses', ['piutang_id' => $piutang->id]);

            return redirect()->route('piutangs.index')->with('success', 'Piutang dan transaksi berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menyimpan Piutang', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan piutang.')->withInput();
        }
    }

    public function getData(Request $request)
    {
        Log::info('Fetching data piutang untuk DataTables');

        try {
            $user = auth()->user(); // Ambil user yang login
            Log::info('User login:', ['id' => $user->id, 'bidang' => $user->bidang_name]);

            // Ambil semua piutang yang memiliki bidang_name sesuai dengan user login
            $piutangs = Piutang::with('user', 'student')
                ->where('bidang_name', $user->bidang_name);

            Log::info('Query piutang:', ['sql' => $piutangs->toSql(), 'bindings' => $piutangs->getBindings()]);

            return DataTables::of($piutangs)
                ->addIndexColumn()
                ->addColumn('user_name', function ($piutang) {
                    return optional($piutang->user)->name
                        ?? optional($piutang->student)->name
                        ?? 'N/A';
                })
                ->addColumn('jumlah_formatted', function ($piutang) {
                    return 'Rp ' . number_format($piutang->jumlah, 2, ',', '.');
                })
                ->addColumn('status_badge', function ($piutang) {
                    $class = $piutang->status == 'lunas' ? 'bg-success' : 'bg-danger';
                    return '<span class="badge ' . $class . '">' . ucfirst($piutang->status) . '</span>';
                })
                ->addColumn('actions', function ($piutang) {
                    return view('piutang.actions', compact('piutang'))->render();
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data piutang', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data'], 500);
        }
    }

    public function show(Piutang $piutang)
    {
        return view('piutang.show', compact('piutang'));
    }

    public function edit(Piutang $piutang)
    {
        $users = User::all();

        $akunPiutang = AkunKeuangan::where('id', 103)->first();
        $parentAkunPiutang = AkunKeuangan::where('parent_id', 103)->get();

        $akunKeuangans = AkunKeuangan::where('parent_id', 103)->get();
        // Ambil data user yang login
        $user = auth()->user();
        $bidangName = $user->bidang_name;

        // Tentukan akun kas atau bank berdasarkan role dan bidang
        if ($user->role === 'Bendahara') {
            // Akun Kas untuk Bendahara
            $akunKeuanganKas = 1011; // Akun Kas
            $akunKeuanganBank = 1021; // Akun Bank
        } else {
            // Akun Kas berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            // Akun Bank berdasarkan bidang_id
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];

            // Pilih akun kas dan akun bank berdasarkan bidang_name
            $akunKeuanganKas = $akunKas[$user->bidang_name] ?? null;
            $akunKeuanganBank = $akunBank[$user->bidang_name] ?? null;
        }

        // Menyediakan pilihan akun untuk form
        $akunKeuanganOptions = [
            'Kas' => $akunKeuanganKas,
            'Bank' => $akunKeuanganBank
        ];

        // Ambil saldo masing-masing akun berdasarkan role dan bidang
        $saldos = [];
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id
        foreach ($akunKeuanganOptions as $label => $akunId) {
            if ($user->role === 'Bendahara') {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $akunId)
                    ->whereNull('bidang_name')
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            } else {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $akunId)
                    ->where('bidang_name', $bidang_id)
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            }

            $saldos[$akunId] = $lastSaldo ? $lastSaldo->saldo : 0;
        }

        return view('piutang.edit', compact('piutang', 'akunKeuangans', 'akunPiutang', 'parentAkunPiutang', 'users', 'akunKeuanganOptions', 'saldos'));
    }

    public function update(Request $request, Piutang $piutang)
    {
        Log::info('Menerima request untuk update Piutang', [
            'piutang_id' => $piutang->id,
            'data_baru' => $request->all()
        ]);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
            'parent_akun_id' => 'nullable|exists:akun_keuangans,id',
            'jumlah' => 'required|numeric|min:0',
            'tanggal_jatuh_tempo' => 'required|date',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:belum_lunas,lunas',
        ]);

        DB::beginTransaction();

        try {
            $tanggalTransaksi = now()->toDateString();
            $user = auth()->user();
            $bidangName = $user->bidang_name;
            $statusSebelumnya = $piutang->status;

            // Ambil saldo terakhir
            if ($user->role === 'Bendahara') {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $validated['akun_keuangan_id'])
                    ->whereNull('bidang_name')
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            } else {
                $lastSaldo = Transaksi::where('akun_keuangan_id', $validated['akun_keuangan_id'])
                    ->where('bidang_name', $bidangName)
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            }

            $saldoSebelumnya = $lastSaldo ? $lastSaldo->saldo : 0;

            // Jika belum lunas -> lunas, cek saldo cukup
            if ($statusSebelumnya !== 'lunas' && $validated['status'] === 'lunas') {
                if ($saldoSebelumnya < $validated['jumlah']) {
                    Log::warning('Saldo tidak mencukupi untuk update pelunasan piutang', [
                        'saldo' => $saldoSebelumnya,
                        'jumlah' => $validated['jumlah'],
                        'akun_keuangan_id' => $validated['akun_keuangan_id']
                    ]);

                    return redirect()->back()
                        ->with('error', 'Saldo akun Kas/Bank tidak mencukupi untuk mencatat pelunasan piutang.')
                        ->withInput();
                }

                $saldoSetelahnya = $saldoSebelumnya - $validated['jumlah'];
                $kodeTransaksi = 'PIU-UPD-' . now()->format('YmdHis') . '-' . rand(100, 999);

                // Tambahkan transaksi pelunasan
                Transaksi::create([
                    'kode_transaksi' => $kodeTransaksi,
                    'tanggal_transaksi' => $tanggalTransaksi,
                    'type' => 'pengeluaran',
                    'deskripsi' => 'Pelunasan Piutang #' . $piutang->id,
                    'akun_keuangan_id' => $validated['akun_keuangan_id'],
                    'parent_akun_id' => $validated['parent_akun_id'] ?? null,
                    'amount' => $validated['jumlah'],
                    'saldo' => $saldoSetelahnya,
                    'bidang_name' => $bidangName,
                ]);

                Log::info('Transaksi pelunasan dicatat', [
                    'kode_transaksi' => $kodeTransaksi,
                    'saldo_setelah' => $saldoSetelahnya
                ]);

                // Hapus dari PendapatanBelumDiterima
                PendapatanBelumDiterima::where('user_id', $piutang->user_id)
                    ->where('jumlah', $piutang->jumlah)
                    ->where('bidang_name', $bidangName)
                    ->delete();

                Log::info('PendapatanBelumDiterima dihapus karena status lunas');
            }

            // Update Piutang
            $piutang->update(array_merge($validated, ['bidang_name' => $bidangName]));

            Log::info('Piutang berhasil diperbarui', ['piutang' => $piutang]);

            // Update atau kirim notifikasi
            $userToNotify = User::find($validated['user_id']);
            if ($userToNotify) {
                $existingNotification = DatabaseNotification::whereJsonContains('data->piutang_id', $piutang->id)->first();

                if ($existingNotification) {
                    if ($statusSebelumnya === 'belum_lunas' && $validated['status'] === 'lunas') {
                        $existingNotification->update(['read_at' => now()]);
                        Log::info('Notifikasi ditandai sebagai dibaca');
                    } else {
                        $existingNotification->update([
                            'data' => [
                                'message' => 'Hutang sebesar Rp' . number_format($piutang->jumlah, 2, ',', '.') .
                                    ' jatuh tempo pada ' . \Carbon\Carbon::parse($piutang->tanggal_jatuh_tempo)->format('d M Y'),
                                'url' => url('/hutang'),
                                'piutang_id' => $piutang->id,
                            ]
                        ]);
                        Log::info('Notifikasi diperbarui');
                    }
                } else {
                    Notification::send($userToNotify, new HutangJatuhTempo($piutang));
                    Log::info('Notifikasi baru dikirim');
                }
            }

            DB::commit();
            return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal update Piutang', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui piutang.')->withInput();
        }
    }

    public function destroy(Piutang $piutang)
    {
        Log::info('Menerima request untuk menghapus Piutang', ['piutang_id' => $piutang->id]);

        try {
            $piutang->delete();
            Log::info('Piutang berhasil dihapus', ['piutang_id' => $piutang->id]);

            return redirect()->route('piutangs.index')->with('success', 'Piutang berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus Piutang', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus piutang.');
        }
    }

    public function indexPenerima()
    {
        $user = auth()->user();

        // Ambil piutang yang ditujukan ke bidang ini (Bidang B), belum lunas
        $piutangs = Piutang::where('user_id', $user->id)
            ->where('status', 'belum_lunas')
            ->get();

        return view('piutang.terima', compact('piutangs'));
    }

    public function showPayForm($id)
    {
        $piutang = Piutang::findOrFail($id);

        // Tentukan akun kas atau bank berdasarkan role dan bidang
        $user = auth()->user();
        $bidangName = $user->bidang_name;

        if ($user->role === 'Bendahara') {
            // Akun Kas untuk Bendahara
            $akunKeuanganKas = 1011; // Akun Kas
            $akunKeuanganBank = 1021; // Akun Bank
        } else {
            // Akun Kas berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            // Akun Bank berdasarkan bidang_id
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];

            // Pilih akun kas dan akun bank berdasarkan bidang_name
            $akunKeuanganKas = $akunKas[$bidangName] ?? null;
            $akunKeuanganBank = $akunBank[$bidangName] ?? null;
        }

        // Menyediakan pilihan akun untuk form
        $akunKeuanganOptions = [
            'Kas' => $akunKeuanganKas,
            'Bank' => $akunKeuanganBank
        ];

        // Validasi akses: hanya bidang yang berbeda yang bisa melunasi
        if ($piutang->bidang_name === auth()->user()->bidang_name) {
            return redirect()->route('piutangs.index')->with('error', 'Anda tidak bisa melunasi piutang yang Anda buat sendiri.');
        }

        return view('piutang.form-pelunasan', compact('piutang', 'akunKeuanganOptions'));
    }

    public function storePayment(Request $request, $Id)
    {
        // Validasi input pelunasan
        $validated = $request->validate([
            'jumlah_bayar' => 'required|numeric|min:0',
            'akun_keuangan_id' => 'required|exists:akun_keuangans,id',
        ]);

        $piutang = Piutang::findOrFail($Id);

        // Pastikan piutang belum lunas
        if ($piutang->status === 'lunas') {
            return redirect()->back()->with('error', 'Piutang sudah dilunasi.');
        }

        $jumlahBayar = $validated['jumlah_bayar'];
        $sisaPiutang = $piutang->jumlah - $jumlahBayar;

        // Validasi jumlah pembayaran tidak lebih besar dari sisa piutang
        if ($jumlahBayar > $piutang->jumlah) {
            return redirect()->back()->with('error', 'Jumlah pembayaran tidak boleh lebih besar dari sisa piutang.');
        }

        // Tentukan akun kas atau bank berdasarkan role dan bidang
        $user = auth()->user();
        $bidangName = $user->bidang_name;

        if ($user->role === 'Bendahara') {
            // Akun Kas untuk Bendahara
            $akunKeuanganKas = 1011; // Akun Kas
            $akunKeuanganBank = 1021; // Akun Bank
        } else {
            // Akun Kas berdasarkan bidang_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            // Akun Bank berdasarkan bidang_id
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];

            // Pilih akun kas dan akun bank berdasarkan bidang_name
            $akunKeuanganKas = $akunKas[$bidangName] ?? null;
            $akunKeuanganBank = $akunBank[$bidangName] ?? null;
        }

        // Menyediakan pilihan akun untuk form
        $akunKeuanganOptions = [
            'Kas' => $akunKeuanganKas,
            'Bank' => $akunKeuanganBank
        ];

        // Ambil saldo akun yang dipilih
        $akunKeuanganId = $validated['akun_keuangan_id'];
        if (!isset($akunKeuanganOptions[$akunKeuanganId])) {
            return redirect()->back()->with('error', 'Akun kas atau bank yang dipilih tidak valid.');
        }

        $lastSaldo = Transaksi::where('akun_keuangan_id', $akunKeuanganId)
            ->where('bidang_name', $bidangName)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoAkun = $lastSaldo ? $lastSaldo->saldo : 0;
        $kodeTransaksi = 'HUT-' . now()->format('YmdHis') . '-' . rand(100, 999);

        // Validasi apakah saldo akun mencukupi
        if ($saldoAkun < $jumlahBayar) {
            return redirect()->back()->with('error', 'Saldo pada akun kas/bank tidak mencukupi untuk pelunasan.');
        }

        // Simpan Transaksi Pelunasan
        Transaksi::create([
            'kode_transaksi' => $kodeTransaksi,
            'tanggal_transaksi' => now()->toDateString(),
            'tipe' => 'pengeluaran', // Perbaikan: Ubah tipe menjadi 'pengeluaran'
            'deskripsi' => 'Pelunasan Piutang #' . $piutang->id,
            'akun_keuangan_id' => $akunKeuanganId,
            'amount' => $jumlahBayar,
            'saldo' => $saldoAkun - $jumlahBayar, // update saldo setelah pelunasan
            'bidang_name' => $bidangName,
        ]);

        // Update status piutang
        $piutang->status = ($sisaPiutang <= 0) ? 'lunas' : 'belum_lunas';
        $piutang->jumlah = $sisaPiutang;
        $piutang->save();

        return redirect()->route('piutangs.index')->with('success', 'Pelunasan piutang berhasil.');
    }

}
