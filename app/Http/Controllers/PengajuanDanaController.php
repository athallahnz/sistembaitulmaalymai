<?php

namespace App\Http\Controllers;

use App\Models\PengajuanDana;
use App\Models\PengajuanDanaDetail;
use App\Models\AkunKeuangan;
use App\Traits\HasTransaksiKasBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class PengajuanDanaController extends Controller
{
    use HasTransaksiKasBank;

    // HALAMAN INDEX
    public function index(Request $request)
    {
        // 1. Logika untuk DataTables (AJAX) - Tetap sama
        if ($request->ajax()) {
            $query = PengajuanDana::with(['Bidang'])->latest();

            if (auth()->user()->role == 'Bidang') {
                $query->where('user_id', auth()->id());
            }

            return DataTables::of($query)
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y');
                })
                ->editColumn('total_jumlah', function ($row) {
                    return 'Rp' . number_format($row->total_jumlah, 0, ',', '.');
                })
                ->addColumn('user_name', function ($row) {
                    return $row->pembuat->name ?? '-';
                })
                ->addColumn('aksi', function ($row) {
                    $role = auth()->user()->role;
                    $userId = auth()->user()->id;
                    $url = route('pengajuan.show', $row->id);
                    $exportUrl = route('pengajuan.export.pdf', $row->id);

                    $btnClass = 'btn-primary text-white rounded';
                    $icon = 'bi-eye';
                    $label = 'Detail';

                    $editBtn = '';
                    $deleteBtn = '';
                    $canEdit = ($userId === $row->user_id);

                    // Tombol Edit / Revisi + Delete
                    if ($canEdit && ($row->status === 'Menunggu Verifikasi' || $row->status === 'Ditolak')) {
                        $btnLabel = ($row->status === 'Ditolak') ? 'Revisi' : 'Edit';
                        $btnClassRevisi = ($row->status === 'Ditolak') ? 'btn-danger' : 'btn-warning';

                        $editBtn = '
        <button type="button"
            class="btn btn-sm ' . $btnClassRevisi . ' rounded shadow-sm btn-edit-pengajuan"
            data-id="' . $row->id . '"
            data-bs-toggle="modal"
            data-bs-target="#modalCreatePengajuan">
            <i class="bi bi-pencil-square"></i> ' . $btnLabel . '
        </button>';

                        $deleteBtn = '
        <button type="button"
            class="btn btn-sm btn-danger rounded shadow-sm btn-delete-pengajuan"
            data-id="' . $row->id . '"
            data-url="' . route('pengajuan.destroy', $row->id) . '">
            <i class="bi bi-trash"></i> Hapus
        </button>';
                    }

                    // Aksi utama (Detail / Review / Cairkan)
                    if ($role == 'Manajer Keuangan' && $row->status == 'Menunggu Verifikasi') {
                        $btnClass = 'btn-warning text-dark rounded';
                        $icon = 'bi-clipboard-check';
                        $label = 'Review';
                    } elseif ($role == 'Bendahara' && $row->status == 'Disetujui') {
                        $btnClass = 'btn-success rounded';
                        $icon = 'bi-cash-stack';
                        $label = 'Cairkan';
                    }

                    $aksiBtn = '
    <a href="' . $url . '"
        class="btn btn-sm ' . $btnClass . ' rounded shadow-sm">
        <i class="bi ' . $icon . '"></i> ' . $label . '
    </a>';

                    $exportBtn = '
    <a href="' . $exportUrl . '"
        target="_blank"
        class="btn btn-sm btn-outline-danger rounded shadow-sm">
        <i class="bi bi-file-earmark-pdf"></i> PDF
    </a>';

                    // Wrapper:
                    // - flex-column di mobile
                    // - berubah jadi flex-row ketika >= sm (desktop/tab)
                    return '
    <div class="d-flex flex-column flex-sm-row flex-sm-wrap gap-1" style="width:100%;">
        ' . $editBtn . $deleteBtn . $aksiBtn . $exportBtn . '
    </div>';
                })
                ->rawColumns(['aksi', 'status'])
                ->make(true);
        }

        // 2. LOGIKA BARU: Menghitung Summary Cards
        // Kita buat query dasar lagi
        $baseQuery = PengajuanDana::query();

        // Terapkan filter yang sama: Staff hanya lihat statistik miliknya sendiri
        if (auth()->user()->role == 'Bidang') {
            $baseQuery->where('user_id', auth()->id());
        }

        // Hitung semua status dalam satu query agar efisien
        $summary = $baseQuery->selectRaw("
        count(case when status = 'Menunggu Verifikasi' then 1 end) as menunggu,
        count(case when status = 'Disetujui' then 1 end) as disetujui,
        count(case when status = 'Dicairkan' then 1 end) as dicairkan,
        count(case when status = 'Ditolak' then 1 end) as ditolak
    ")->first();

        // TAMBAHAN: Ambil data Akun untuk Dropdown di Modal
        $akunKeuangans = AkunKeuangan::where('tipe_akun', 'expense')
            ->whereNotNull('parent_id')
            ->orderBy('id', 'asc')
            ->get();

        // Kirim variabel $summary ke view
        return view('bidang.pengajuan.index', compact('summary', 'akunKeuangans'));
    }

    // HALAMAN CREATE
    public function create()
    {
        // Ambil data CoA: Tipe 'expense' DAN parent_id TIDAK NULL
        $akunKeuangans = AkunKeuangan::where('tipe_akun', 'expense')
            ->whereNotNull('parent_id') // Hanya ambil sub-akun (bukan header)
            ->orderBy('kode_akun')
            ->get();

        return view('bidang.pengajuan.create', compact('akunKeuangans'));
    }

    // PROSES STORE
    public function store(Request $request)
    {
        // Validasi Input
        $request->validate([
            'judul' => 'required|string|max:255',
            'details' => 'required|array|min:1', // Minimal 1 item
            'details.*.akun_keuangan_id' => 'required|exists:akun_keuangans,id',
            'details.*.keterangan_item' => 'required|string',
            'details.*.kuantitas' => 'required|numeric|min:1',
            'details.*.harga_pokok' => 'required|numeric|min:0',
        ]);

        // Gunakan Transaksi DB agar data Header & Detail masuk bersamaan
        DB::beginTransaction();
        try {
            $totalKeseluruhan = 0;
            $dataDetails = [];

            // Loop untuk hitung total & siapkan array detail
            foreach ($request->details as $item) {
                $subtotal = $item['kuantitas'] * $item['harga_pokok'];
                $totalKeseluruhan += $subtotal;

                $dataDetails[] = [
                    'akun_keuangan_id' => $item['akun_keuangan_id'],
                    'keterangan_item' => $item['keterangan_item'],
                    'kuantitas' => $item['kuantitas'],
                    'harga_pokok' => $item['harga_pokok'],
                    'jumlah_dana' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // A. Simpan Header Pengajuan
            $pengajuan = PengajuanDana::create([
                'user_id' => Auth::id(),
                'bidang_id' => Auth::user()->bidang_name, // Asumsi user punya bidang_id
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'total_jumlah' => $totalKeseluruhan,
                'status' => 'Menunggu Verifikasi',
            ]);

            // B. Masukkan ID Header ke setiap array detail
            foreach ($dataDetails as &$detail) {
                $detail['pengajuan_dana_id'] = $pengajuan->id;
            }

            // C. Simpan Detail Sekaligus (Bulk Insert)
            PengajuanDanaDetail::insert($dataDetails);

            DB::commit(); // Simpan permanen

            return redirect()->route('pengajuan.index')->with('success', 'Pengajuan berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan jika error
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // AJAX GET SALDO AKUN
    public function getSaldoAkun($akunId)
    {
        // Ambil role Bendahara (untuk melihat saldo Global)
        $userRole = auth()->user()->role;
        $bidangValue = null;

        // Panggil helper Trait untuk mendapatkan saldo
        // Menggunakan tanggal hari ini sebagai cutoff
        $saldo = $this->getLastSaldoBySaldoColumn(
            (int) $akunId,
            $userRole,
            $bidangValue,
            now()->toDateString()
        );

        // Format Rupiah
        $formattedSaldo = number_format($saldo, 0, ',', '.');

        return response()->json([
            'saldo' => $saldo, // Nilai float untuk perbandingan di JS
            'formatted' => 'Rp ' . $formattedSaldo // Nilai string untuk tampilan
        ]);
    }

    // AJAX GET PENGAJUAN DATA (UNTUK EDIT)
    public function getPengajuanJson($id)
    {
        $pengajuan = PengajuanDana::with(['details'])->findOrFail($id);

        // Guard: Hanya pembuat yang boleh melihat data edit
        if (auth()->user()->id !== $pengajuan->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($pengajuan);
    }

    // HALAMAN EDIT
    public function edit($id)
    {
        $pengajuan = PengajuanDana::with(['details'])->findOrFail($id);

        // Guard: Hanya pembuat dan hanya jika status masih Menunggu Verifikasi
        if (auth()->user()->id !== $pengajuan->user_id || $pengajuan->status !== 'Menunggu Verifikasi') {
            return redirect()->route('pengajuan.show', $id)->with('error', 'Pengajuan ini tidak dapat diubah karena sudah diverifikasi atau bukan milik Anda.');
        }

        // Ambil akun keuangan (expense) untuk dropdown detail
        $akunKeuangans = AkunKeuangan::where('tipe_akun', 'expense')
            ->whereNotNull('parent_id')
            ->orderBy('kode_akun', 'asc')
            ->get();

        return view('bidang.pengajuan.edit', compact('pengajuan', 'akunKeuangans'));
    }

    // PROSES UPDATE
    public function update(Request $request, $id)
    {
        $pengajuan = PengajuanDana::findOrFail($id);
        $oldStatus = $pengajuan->status;

        // Guard: Hanya pembuat dan status harus Menunggu Verifikasi ATAU Ditolak
        if (auth()->user()->id !== $pengajuan->user_id || ($oldStatus !== 'Menunggu Verifikasi' && $oldStatus !== 'Ditolak')) {
            return back()->with('error', 'Pengajuan tidak dapat diubah karena statusnya tidak valid.');
        }

        // 1. Validasi Data (tetap sama)
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.akun_keuangan_id' => 'required|exists:akun_keuangans,id',
            'details.*.keterangan_item' => 'required|string|max:255',
            'details.*.kuantitas' => 'required|numeric|min:1',
            'details.*.harga_pokok' => 'required|numeric|min:0',
        ]);

        // 2. Hitung Ulang Total (tetap sama)
        $totalJumlah = 0;
        foreach ($validated['details'] as $detail) {
            $totalJumlah += $detail['kuantitas'] * $detail['harga_pokok'];
        }

        // Tentukan status baru: Jika status lama Ditolak, reset ke Menunggu Verifikasi
        $newStatus = ($oldStatus === 'Ditolak') ? 'Menunggu Verifikasi' : $oldStatus;

        // 3. Update Header Pengajuan
        $pengajuan->update([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'total_jumlah' => $totalJumlah,

            'status' => $newStatus, // <--- UPDATE STATUS BARU

            // Reset kolom verifikasi karena pengajuan diajukan ulang
            'validator_id' => null,
            'tgl_verifikasi' => null,
            'alasan_tolak' => null,
        ]);

        // 4. Update Detail Items (tetap sama)
        $pengajuan->details()->delete();
        $detailsData = [];
        foreach ($validated['details'] as $detail) {
            $detailsData[] = [
                'akun_keuangan_id' => $detail['akun_keuangan_id'],
                'keterangan_item' => $detail['keterangan_item'],
                'kuantitas' => $detail['kuantitas'],
                'harga_pokok' => $detail['harga_pokok'],
                'jumlah_dana' => $detail['kuantitas'] * $detail['harga_pokok'],
            ];
        }
        $pengajuan->details()->createMany($detailsData);

        $message = ($oldStatus === 'Ditolak')
            ? 'Pengajuan berhasil direvisi dan diajukan ulang untuk verifikasi.'
            : 'Pengajuan dana berhasil diperbarui.';

        return redirect()->route('pengajuan.show', $pengajuan->id)->with('success', $message);
    }

    // DETAIL SHOW
    public function show($id)
    {
        // 1. Load Data Pengajuan
        // Pastikan relasi 'treasurer' ditambahkan di sini
        $pengajuan = PengajuanDana::with(['details.akunKeuangan', 'pembuat', 'validator', 'bidang', 'treasurer'])
            ->findOrFail($id);

        // 2. Load Data Akun Kas & Bank (SOLUSI ERROR)
        // Kita ambil akun yang tipe-nya Kas atau Bank agar Bendahara bisa memilih sumber dana
        // List ID ini disesuaikan dengan Trait HasTransaksiKasBank Anda
        $akunKasBank = AkunKeuangan::whereIn('id', [
            1011,
            1021,
        ])->orderBy('kode_akun', 'asc')->get();

        // 3. Kirim variable ke View (tambahkan 'akunKasBank' di compact)
        return view('bidang.pengajuan.show', compact('pengajuan', 'akunKasBank'));
    }

    // EXPORT PDF
    public function exportPdf($id)
    {
        $pengajuan = PengajuanDana::with(['details.akunKeuangan', 'pembuat', 'validator', 'bidang', 'treasurer'])->findOrFail($id);

        $userRole = auth()->user()->role;
        $userId = auth()->user()->id;

        // OTORISASI: IZINKAN JIKA SALAH SATU SYARAT TERPENUHI
        // 1. Pembuat pengajuan
        // 2. Manajer Keuangan (Validator)
        // 3. Bendahara (Treasurer)

        $canAccess = ($userId === $pengajuan->user_id) ||
            ($userRole === 'Manajer Keuangan') ||
            ($userRole === 'Bendahara');

        if (!$canAccess) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengunduh dokumen ini.');
        }

        // 1. Load View Blade ke PDF
        $pdf = PDF::loadView('bidang.pengajuan.pdf', compact('pengajuan'));

        // 2. Set nama file
        $fileName = 'Pengajuan_Dana_' . str_replace(' ', '_', $pengajuan->judul) . '_' . $pengajuan->id . '.pdf';

        // 3. Download
        return $pdf->download($fileName);
    }

    // APPROVE
    public function approve($id)
    {
        // Pastikan hanya Manajer Keuangan
        if (auth()->user()->role !== 'Manajer Keuangan') {
            abort(403, 'Unauthorized action.');
        }

        $pengajuan = PengajuanDana::findOrFail($id);

        if ($pengajuan->status !== 'Menunggu Verifikasi') {
            return back()->with('error', 'Status pengajuan tidak valid.');
        }

        $pengajuan->update([
            'status' => 'Disetujui',
            'validator_id' => auth()->id(),
            'tgl_verifikasi' => now(),
        ]);

        return back()->with('success', 'Pengajuan berhasil disetujui. Menunggu pencairan Bendahara.');
    }

    // AJAX GET APPROVAL COUNT
    public function getApprovalCount()
    {
        $userRole = auth()->user()->role;

        if ($userRole === 'Manajer Keuangan') {
            // Manajer Keuangan: Hitung pengajuan yang statusnya 'Menunggu Verifikasi'
            $count = PengajuanDana::where('status', 'Menunggu Verifikasi')->count();
        } elseif ($userRole === 'Bendahara') {
            // Bendahara: Hitung pengajuan yang statusnya 'Disetujui' (siap dicairkan)
            $count = PengajuanDana::where('status', 'Disetujui')->count();
        } else {
            $count = 0; // Role lain tidak perlu notifikasi ini
        }

        return response()->json(['count' => $count]);
    }

    // REJECT (TOLAK)
    public function reject(Request $request, $id)
    {
        // Pastikan hanya Manajer Keuangan
        if (auth()->user()->role !== 'Manajer Keuangan') {
            abort(403, 'Unauthorized action.');
        }

        // 1. Validasi Alasan Tolak
        $validated = $request->validate(['alasan_tolak' => 'required|string|max:255']);

        $pengajuan = PengajuanDana::findOrFail($id);

        if ($pengajuan->status !== 'Menunggu Verifikasi') {
            return back()->with('error', 'Status pengajuan tidak valid.');
        }

        // 2. Update Status dan Simpan Alasan Tolak
        $pengajuan->update([
            'status' => 'Ditolak',
            'validator_id' => auth()->id(),
            'tgl_verifikasi' => now(),
            'alasan_tolak' => $validated['alasan_tolak'], // <--- BARIS PENTING DITAMBAHKAN
        ]);

        return back()->with('success', 'Pengajuan telah ditolak. Alasan penolakan telah dicatat.');
    }

    // PENCAIRAN DANA
    public function cairkan(Request $request, $id)
    {
        // 1. Validasi Input Bendahara
        $request->validate([
            'tanggal_cair' => 'required|date',
            'sumber_dana_id' => 'required|exists:akun_keuangans,id',
        ]);

        $pengajuan = PengajuanDana::findOrFail($id); // Tidak perlu with('details') karena kita hanya pakai total_jumlah

        if ($pengajuan->status !== 'Disetujui') {
            return back()->with('error', 'Pengajuan belum disetujui validator.');
        }

        DB::beginTransaction();
        try {
            // A. IDENTIFIKASI AKUN LAWAN (TARGET)
            $sumberId = (int) $request->sumber_dana_id;
            $bidangId = $pengajuan->bidang_id;

            // 1. Tentukan apakah Sumber adalah KAS atau BANK (dari ID yang dipilih)
            // Kita gunakan ID range yang sudah kita ketahui dari Trait
            $isSumberBank = in_array($sumberId, [1021, 1022, 1023, 1024, 1025]);

            // 2. Cari Akun Tujuan (TARGET) milik Bidang menggunakan Helper Trait
            $targetAkunId = null;
            if ($isSumberBank) {
                $targetAkunId = $this->getDefaultBankAkunId('Bidang', $bidangId);
            } else {
                $targetAkunId = $this->getDefaultKasAkunId('Bidang', $bidangId);
            }

            if (!$targetAkunId) {
                throw new \Exception("Sistem tidak menemukan akun Kas/Bank Bidang untuk Bidang ID: {$bidangId}. Mohon cek mapping akun di Trait.");
            }

            // B. EKSEKUSI SATU KALI TRANSAKSI TRANSFER

            $prefix = $this->makeKodePrefix('Bendahara', null);
            $kodeTransaksi = $request->kode_referensi ?: 'TRF-' . $prefix . '-' . date('ym') . '-' . rand(100, 999);

            $trxRequest = new Request([
                'kode_transaksi' => $kodeTransaksi,
                'tanggal_transaksi' => $request->tanggal_cair,
                'type' => 'pengeluaran', // Dari sisi Bendahara
                'deskripsi' => "Transfer Dana Pengajuan: " . $pengajuan->judul,
                'amount' => $pengajuan->total_jumlah, // Menggunakan total jumlah
                'bidang_name' => null, // Global
            ]);

            // PANGGIL CORE TRAIT
            // Parameter 2 (Akun Utama): SUMBER DANA (Kas Bendahara) -> DIKREDIT (Pengeluaran)
            // Parameter 3 (Akun Lawan): TARGET DANA (Kas Bidang) -> DI-DEBIT (Penerimaan Lawan)

            $this->processTransactionCore(
                $trxRequest,
                $sumberId,      // Akun Keuangan ID (Utama/Sumber Bendahara)
                $targetAkunId   // Parent Akun ID (Lawan/Target Bidang)
            );

            // C. UPDATE STATUS PENGAJUAN
            $pengajuan->update([
                'status' => 'Dicairkan',
                'treasurer_id' => auth()->id(),
                'tgl_pencairan' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return back()->with('success', 'Dana berhasil ditransfer ke Akun Kas/Bank Bidang.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal Transfer Dana: ' . $e->getMessage());
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $pengajuan = PengajuanDana::findOrFail($id);

        // Jika user adalah Bidang, pastikan hanya bisa hapus miliknya sendiri
        if (auth()->user()->role === 'Bidang' && $pengajuan->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk menghapus pengajuan ini.'
            ], 403);
        }

        // Batasi status yang bisa dihapus
        if (!in_array($pengajuan->status, ['Menunggu Verifikasi', 'Ditolak'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan tidak dapat dihapus karena sudah diproses.'
            ], 422);
        }

        // Jika ada relasi detail dan menggunakan ON DELETE CASCADE / manual delete, sesuaikan di sini.
        // $pengajuan->details()->delete(); // contoh jika perlu

        $pengajuan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan berhasil dihapus.'
        ]);
    }

}
