<?php

namespace App\Http\Controllers;

use App\Models\Warga;
use App\Models\IuranBulanan;
use App\Models\InfaqSosial;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use Illuminate\Http\Request;

class WargaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        // kalau mau batasi role/bidang, tambahkan middleware/cek disini
    }

    /**
     * List Warga + filter kepala/anggota + pencarian
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $filter = $request->get('jenis'); // dipakai hanya untuk isi select awal

        // ringkasan kecil
        $ringkas = [
            'total_semua' => Warga::count(),
            'total_kk' => Warga::kepalaKeluarga()->count(),
            'total_anggota' => Warga::whereNotNull('warga_id')->count(),
        ];

        // daftar kepala keluarga untuk dropdown di modal
        $kepalas = Warga::kepalaKeluarga()
            ->orderBy('rt')
            ->orderBy('no')
            ->orderBy('nama')
            ->get();

        // ⚠ tidak perlu $wargas, karena daftar pakai DataTables Ajax
        return view('bidang.kemasjidan.warga.index', [
            'ringkas' => $ringkas,
            'q' => $q,
            'filter' => $filter,
            'kepalas' => $kepalas,
        ]);
    }

    /**
     * Sumber data JSON untuk Yajra DataTables
     */
    public function data(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $filter = $request->get('jenis');

        $filterStatus = $request->get('status');

        $query = Warga::query()
            ->with('kepala')
            ->when($filter === 'kk', fn($q) => $q->whereNull('warga_id'))
            ->when($filter === 'anggota', fn($q) => $q->whereNotNull('warga_id'))
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('hp', 'like', "%{$q}%")
                        ->orWhere('rt', 'like', "%{$q}%")
                        ->orWhere('alamat', 'like', "%{$q}%");
                });
            })
            ->when($filterStatus, function ($qr) use ($filterStatus) {
                $qr->where('status_keluarga', $filterStatus);
            });

        return DataTables::eloquent($query)
            ->addColumn('jenis_label', function (Warga $w) {
                if (is_null($w->warga_id)) {
                    return '<span class="badge text-bg-primary">Kepala Keluarga</span>';
                }
                return '<span class="badge text-bg-secondary">Anggota</span>';
            })
            ->addColumn('kepala_nama', function (Warga $w) {
                return is_null($w->warga_id)
                    ? '-'
                    : ($w->kepala->nama ?? '-');
            })
            ->addColumn('status_label', function (Warga $w) {
                $status = $w->status_keluarga ?? 'aktif';

                return match ($status) {
                    'meninggal' => '<span class="badge text-bg-secondary">Meninggal</span>',
                    default => '<span class="badge text-white text-bg-success">Aktif</span>',
                };
            })
            ->addColumn('aksi', function (Warga $w) {
                $isKepala = is_null($w->warga_id) ? 1 : 0;
                $updateUrl = route('wargas.update', $w->id);
                $deleteUrl = route('wargas.destroy', $w->id);

                return view('bidang.kemasjidan.warga._aksi', compact('w', 'isKepala', 'updateUrl', 'deleteUrl'))->render();
            })
            ->rawColumns(['jenis_label', 'aksi', 'status_label'])
            ->toJson();
    }

    /**
     * Form create Warga
     */
    public function create()
    {
        $kepalas = Warga::kepalaKeluarga()
            ->orderBy('rt')
            ->orderBy('no')
            ->orderBy('nama')
            ->get();

        return view('warga.create', [
            'warga' => new Warga(),
            'kepalas' => $kepalas,
        ]);
    }

    /**
     * Simpan Warga baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'rt' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no' => ['nullable', 'string', 'max:255'],
            'hp' => ['nullable', 'string', 'max:255', 'unique:wargas,hp'],
            'pin' => ['nullable', 'string', 'min:4', 'max:16'],
            'warga_id' => ['nullable', 'integer', 'exists:wargas,id'],
        ]);

        // kalau dipilih sebagai kepala keluarga, paksa warga_id null
        if ($request->boolean('set_kepala')) {
            $data['warga_id'] = null;
        }

        $warga = Warga::create($data); // PIN otomatis di-hash oleh mutator

        return redirect()
            ->route('wargas.index')
            ->with('success', 'Data warga "' . $warga->nama . '" berhasil ditambahkan.');
    }

    /**
     * Form edit Warga
     */
    public function edit(Warga $warga)
    {
        $kepalas = Warga::kepalaKeluarga()
            ->where('id', '!=', $warga->id) // jangan refer ke diri sendiri
            ->orderBy('rt')
            ->orderBy('no')
            ->orderBy('nama')
            ->get();

        return view('warga.edit', [
            'warga' => $warga,
            'kepalas' => $kepalas,
        ]);
    }

    /**
     * Update Warga
     */
    public function update(Request $request, Warga $warga)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'rt' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no' => ['nullable', 'string', 'max:255'],
            'hp' => ['nullable', 'string', 'max:255', 'unique:wargas,hp,' . $warga->id],
            'pin' => ['nullable', 'string', 'min:4', 'max:16'],
            'warga_id' => ['nullable', 'integer', 'exists:wargas,id'],
        ]);

        if ($request->boolean('set_kepala')) {
            $data['warga_id'] = null;
        } else {
            // jangan sampai warga menunjuk ke dirinya sendiri
            if (isset($data['warga_id']) && (int) $data['warga_id'] === $warga->id) {
                unset($data['warga_id']);
            }
        }

        // Kalau pin dikosongkan di form, jangan ubah PIN lama
        if ($data['pin'] === null || $data['pin'] === '') {
            unset($data['pin']);
        }

        $warga->update($data);

        return redirect()
            ->route('wargas.index')
            ->with('success', 'Data warga "' . $warga->nama . '" berhasil diperbarui.');
    }

    /**
     * Hapus Warga
     */
    public function destroy(Warga $warga)
    {
        // 1) Tidak boleh hapus kepala keluarga yang masih punya anggota
        if ($warga->anggotaKeluarga()->count() > 0) {
            return back()->with(
                'error',
                'Tidak bisa menghapus kepala keluarga yang masih memiliki anggota. ' .
                'Gunakan fitur "Kepala Keluarga Meninggal" untuk mengalihkan kepala keluarga.'
            );
        }

        // 2) Hapus semua iuran bulanan untuk warga ini
        $warga->iuranBulanan()->delete();

        // 3) Hapus semua catatan infaq sosial
        if ($warga->infaq) {
            $warga->infaq->delete();
        }

        $nama = $warga->nama;
        $warga->delete();

        return back()->with(
            'success',
            "Data warga {$nama} beserta seluruh riwayat keuangannya berhasil dihapus."
        );
    }

    public function markAsDeceased(Request $request, Warga $warga)
    {
        // pastikan dia memang kepala keluarga
        if (!is_null($warga->warga_id)) {
            return back()->with('error', 'Hanya kepala keluarga yang bisa ditandai meninggal.');
        }

        // punya anggota?
        $anggotaIds = $warga->anggotaKeluarga()->pluck('id')->toArray();
        if (empty($anggotaIds)) {
            return back()->with(
                'error',
                'Kepala keluarga ini tidak memiliki anggota. ' .
                'Jika ingin menghapus, gunakan tombol Hapus biasa.'
            );
        }

        $request->validate([
            'pengganti_id' => [
                'required',
                'integer',
                Rule::in($anggotaIds), // harus salah satu anggota keluarganya
            ],
        ]);

        $penggantiId = (int) $request->pengganti_id;

        DB::beginTransaction();

        try {
            // Ambil pengganti (anggota yang akan dijadikan kepala baru)
            /** @var \App\Models\Warga $pengganti */
            $pengganti = Warga::where('id', $penggantiId)
                ->where('warga_id', $warga->id)
                ->firstOrFail();

            // 1) Jadikan pengganti sebagai kepala: warga_id = null
            $pengganti->update([
                'warga_id' => null,
                'status_keluarga' => 'aktif',
            ]);

            // 2) Pindahkan anggota lain ke pengganti
            Warga::where('warga_id', $warga->id)
                ->where('id', '!=', $pengganti->id)
                ->update([
                    'warga_id' => $pengganti->id,
                    'status_keluarga' => 'aktif',
                ]);

            // 3) Alihkan seluruh riwayat iuran ke kepala baru
            IuranBulanan::where('warga_kepala_id', $warga->id)
                ->update(['warga_kepala_id' => $pengganti->id]);

            // 4) Alihkan seluruh riwayat infaq ke kepala baru
            InfaqSosial::where('warga_id', $warga->id)
                ->update(['warga_id' => $pengganti->id]);

            // 5) Tandai kepala lama sebagai meninggal (tidak dihapus)
            $warga->update([
                'status_keluarga' => 'meninggal',
                // optional: jadikan dia "anggota kehormatan" di bawah kepala baru,
                // atau biarkan null supaya berdiri sendiri sebagai arsip.
                'warga_id' => $pengganti->id,
                'pin' => null, // supaya tidak bisa login lagi
            ]);

            DB::commit();

            return back()->with(
                'success',
                "Kepala keluarga {$warga->nama} berhasil ditandai meninggal. " .
                "Kepala keluarga baru: {$pengganti->nama}."
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->with(
                'error',
                'Terjadi kesalahan saat mengalihkan kepala keluarga: ' . $e->getMessage()
            );
        }
    }

    public function getAnggota(Warga $warga)
    {
        // pastikan dia kepala
        if (!is_null($warga->warga_id)) {
            return response()->json(data: [
                'kepala' => $warga->nama,
                'anggota' => [],
            ]);
        }

        $anggota = $warga->anggotaKeluarga()
            ->orderBy('nama')
            ->get(['id', 'nama', 'rt']);

        return response()->json([
            'kepala' => $warga->nama,
            'anggota' => $anggota,
        ]);
    }

    /**
     * Normalisasi header: lowercase, hilangkan tanda baca & spasi ganda.
     */
    protected function normalizeHeader(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['.', ',', ':', ';', '-', '_', '(', ')'], ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v);
        return $v;
    }

    /**
     * Coba cocokkan 1 baris sebagai header, kembalikan mapping field -> kolom.
     *
     * @return array [fieldColumns, fieldLabels]
     */
    protected function matchHeaderRow(array $row, array $targetFields): array
    {
        $fieldColumns = [];
        $fieldLabels = [];

        foreach ($targetFields as $field) {
            $fieldColumns[$field] = null;
            $fieldLabels[$field] = null;
        }

        foreach ($row as $col => $label) {
            $labelStr = (string) $label;
            $norm = $this->normalizeHeader($labelStr);

            // === NAMA ===
            if (!$fieldColumns['nama'] && preg_match('/\bnama\b/', $norm)) {
                $fieldColumns['nama'] = $col;
                $fieldLabels['nama'] = $labelStr;
            }

            // === RT ===
            if (!$fieldColumns['rt'] && preg_match('/\brt\b|\br t\b|\br\/t\b/', $norm)) {
                $fieldColumns['rt'] = $col;
                $fieldLabels['rt'] = $labelStr;
            }

            // === ALAMAT ===
            if (!$fieldColumns['alamat'] && preg_match('/alamat/', $norm)) {
                $fieldColumns['alamat'] = $col;
                $fieldLabels['alamat'] = $labelStr;
            }

            // === NO RUMAH (no rumah / nomor rumah / rumah no / no. rumah) ===
            if (
                !$fieldColumns['no'] &&
                preg_match('/no.? rumah|nomor rumah|rumah no/', $norm)
            ) {
                $fieldColumns['no'] = $col;
                $fieldLabels['no'] = $labelStr;
            }

            // === HP / WA / TELEPON ===
            if (
                !$fieldColumns['hp'] &&
                preg_match('/hp|handphone|telepon|telp|wa|whatsapp/', $norm)
            ) {
                $fieldColumns['hp'] = $col;
                $fieldLabels['hp'] = $labelStr;
            }
        }

        return [$fieldColumns, $fieldLabels];
    }
    /**
     * Scan beberapa baris pertama untuk mencari baris header terbaik.
     *
     * @return array [headerIndex, headerRow, fieldColumns, fieldLabels]
     */
    protected function detectHeaderAndColumns(array $rows, array $targetFields): array
    {
        $bestIndex = 0;
        $bestScore = -1;
        $bestHeaderRow = $rows[0] ?? [];
        $bestFieldCols = [];
        $bestFieldLabels = [];

        $maxScan = min(20, count($rows)); // jangan scan terlalu jauh

        for ($i = 0; $i < $maxScan; $i++) {
            $row = $rows[$i] ?? [];
            if (empty($row)) {
                continue;
            }

            [$fieldCols, $fieldLabels] = $this->matchHeaderRow($row, $targetFields);

            $score = collect($fieldCols)->filter(fn($v) => !is_null($v))->count();

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $i;
                $bestHeaderRow = $row;
                $bestFieldCols = $fieldCols;
                $bestFieldLabels = $fieldLabels;
            }
        }

        return [$bestIndex, $bestHeaderRow, $bestFieldCols, $bestFieldLabels];
    }

    public function importPreview(Request $request)
    {
        // 1. Kalau dari modal (upload file)
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            ]);

            $path = $request->file('file')->store('import_warga');
            $sheetIndex = 0;
        } else {
            // 2. Kalau dari select sheet (tanpa upload ulang)
            $request->validate([
                'import_path' => ['required', 'string'],
                'sheet' => ['nullable', 'integer'],
            ]);

            $path = $request->import_path;
            $sheetIndex = (int) ($request->sheet ?? 0);
        }

        $fullPath = storage_path('app/' . $path);
        if (!file_exists($fullPath)) {
            return redirect()
                ->route('kemasjidan.warga.index')
                ->with('error', 'File import tidak ditemukan. Silakan ulangi upload.');
        }

        $spreadsheet = IOFactory::load($fullPath);
        $sheetNames = $spreadsheet->getSheetNames();

        // clamp index
        if ($sheetIndex < 0 || $sheetIndex >= count($sheetNames)) {
            $sheetIndex = 0;
        }

        $sheet = $spreadsheet->setActiveSheetIndex($sheetIndex);
        $rows = $sheet->toArray(null, true, true, true); // A,B,C,...

        $targetFields = ['nama', 'rt', 'alamat', 'no', 'hp'];

        // 🔍 Deteksi header & mapping kolom (header tidak harus di row 0)
        [$headerIndex, $header, $fieldColumns, $fieldLabels] = $this->detectHeaderAndColumns($rows, $targetFields);

        // ambil max 10 baris mulai dari header (header + 10 data)
        $previewRows = array_slice($rows, $headerIndex, 11);

        // ==== DATA INDEX BIASA (sesuaikan dengan milikmu) ====
        $q = trim((string) $request->get('q', ''));
        $filter = $request->get('jenis', '');

        $baseQuery = Warga::query()->with('kepala');

        if ($filter === 'kk') {
            $baseQuery->whereNull('warga_id');
        } elseif ($filter === 'anggota') {
            $baseQuery->whereNotNull('warga_id');
        }

        if ($q !== '') {
            $baseQuery->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('hp', 'like', "%{$q}%")
                    ->orWhere('rt', 'like', "%{$q}%")
                    ->orWhere('alamat', 'like', "%{$q}%");
            });
        }

        $wargas = $baseQuery->orderBy('nama')->paginate(15)->withQueryString();

        $ringkas = [
            'total_semua' => Warga::count(),
            'total_kk' => Warga::kepalaKeluarga()->count(),
            'total_anggota' => Warga::whereNotNull('warga_id')->count(),
        ];

        $kepalas = Warga::kepalaKeluarga()
            ->orderBy('nama')
            ->get(['id', 'nama', 'rt', 'no']);

        return view('bidang.kemasjidan.warga.index', [
            'wargas' => $wargas,
            'ringkas' => $ringkas,
            'q' => $q,
            'filter' => $filter,
            'kepalas' => $kepalas,

            // IMPORT PREVIEW
            'importPath' => $path,
            'sheetNames' => $sheetNames,
            'sheetIndex' => $sheetIndex,
            'previewRows' => $previewRows,
            'header' => $header,
            'targetFields' => $targetFields,
            'fieldColumns' => $fieldColumns,
            'fieldLabels' => $fieldLabels,
        ]);
    }

    public function importCommit(Request $request)
    {
        $request->validate([
            'import_path' => ['required', 'string'],
            'sheet' => ['required', 'integer'],
        ]);

        $path = $request->input('import_path');
        $sheetIndex = (int) $request->input('sheet');

        $fullPath = storage_path('app/' . $path);
        if (!file_exists($fullPath)) {
            return redirect()
                ->route('kemasjidan.warga.index')
                ->with('error', 'File import tidak ditemukan. Silakan ulangi proses import.');
        }

        $spreadsheet = IOFactory::load($fullPath);
        $sheetNames = $spreadsheet->getSheetNames();

        if ($sheetIndex < 0 || $sheetIndex >= count($sheetNames)) {
            return redirect()
                ->route('kemasjidan.warga.index')
                ->with('error', 'Sheet yang dipilih tidak valid.');
        }

        $sheet = $spreadsheet->setActiveSheetIndex($sheetIndex);
        $rows = $sheet->toArray(null, true, true, true); // A,B,C,...

        if (count($rows) <= 1) {
            return redirect()
                ->route('kemasjidan.warga.index')
                ->with('error', 'Tidak ada data yang bisa diimport dari sheet tersebut.');
        }

        $targetFields = ['nama', 'rt', 'alamat', 'no', 'hp'];

        // 🔍 Deteksi header & mapping kolom
        [$headerIndex, $header, $fieldColumns, $fieldLabels] = $this->detectHeaderAndColumns($rows, $targetFields);

        // Minimal: nama & rt harus ketemu
        if (empty($fieldColumns['nama']) || empty($fieldColumns['rt'])) {
            return redirect()
                ->route('kemasjidan.warga.index')
                ->with('error', "Kolom 'nama' dan/atau 'rt' tidak ditemukan di header Excel. Pastikan header sudah sesuai.");
        }

        $inserted = 0;

        DB::beginTransaction();
        try {
            // mulai dari baris setelah header
            for ($i = $headerIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // skip baris kosong total
                $isEmpty = true;
                foreach ($row as $v) {
                    if (trim((string) $v) !== '') {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty) {
                    continue;
                }

                $data = [];
                foreach ($targetFields as $field) {
                    $col = $fieldColumns[$field] ?? null;
                    if ($col && isset($row[$col])) {
                        $data[$field] = trim((string) $row[$col]);
                    }
                }

                // nama & rt wajib
                if (empty($data['nama']) || empty($data['rt'])) {
                    continue;
                }

                Warga::create($data); // pin, warga_id, infaq_sosial_id => null
                $inserted++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()
                ->route('kemasjidan.warga.index')
                ->with('error', 'Terjadi kesalahan saat import data warga: ' . $e->getMessage());
        }

        return redirect()
            ->route('kemasjidan.warga.index')
            ->with('success', "Import selesai. Berhasil menambahkan {$inserted} data warga baru.");
    }
}
