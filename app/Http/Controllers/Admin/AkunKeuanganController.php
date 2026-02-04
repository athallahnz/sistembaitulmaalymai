<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AkunKeuangan;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AkunKeuanganController extends Controller
{
    public function index()
    {
        $akunKeuanganTanpaParent = AkunKeuangan::whereNull('parent_id')->get();
        return view('admin.akun_keuangan.index', compact('akunKeuanganTanpaParent'));
    }

    /**
     * API DataTables (server-side)
     */
    public function dataTable()
    {
        $query = AkunKeuangan::query()
            ->select([
                'id',
                'kode_akun',
                'nama_akun',
                'tipe_akun',
                'parent_id',
                'saldo_normal',
                'is_kas_bank',
                'cashflow_category'
            ])
            ->with(['parentAkun:id,kode_akun,nama_akun']);
        $query = AkunKeuangan::with('parentAkun');

        return DataTables::of($query)

            /* ============================================
            |   SEARCHING UNTUK KOLOM PARENT (INDUK)
            |   Searching: nama induk / kode induk akun
            ============================================= */
            ->filterColumn('parent', function ($query, $keyword) {
                $query->whereHas('parentAkun', function ($q) use ($keyword) {
                    $q->where('nama_akun', 'like', "%{$keyword}%")
                        ->orWhere('kode_akun', 'like', "%{$keyword}%");
                });
            })

            /* ============================================
            |   DISPLAY COLUMN
            ============================================= */
            ->addColumn('parent', function ($row) {
                return $row->parentAkun
                    ? $row->parentAkun->kode_akun . ' - ' . $row->parentAkun->nama_akun
                    : '-';
            })

            ->addColumn('kas_bank', function ($row) {
                return $row->is_kas_bank
                    ? '<span class="badge bg-success">Kas / Bank</span>'
                    : '<span class="badge bg-secondary">Bukan</span>';
            })

            ->addColumn('cashflow', function ($row) {
                if (!$row->cashflow_category)
                    return '-';

                switch ($row->cashflow_category) {
                    case 'operasional':
                        $color = 'bg-primary';      // biru
                        break;

                    case 'investasi':
                        $color = 'bg-success';      // hijau
                        break;

                    case 'pendanaan':
                        $color = 'bg-warning text-dark'; // kuning / oranye
                        break;

                    default:
                        $color = 'bg-secondary';    // fallback
                }

                return "<span class='badge {$color}'>" . ucfirst($row->cashflow_category) . "</span>";
            })

            ->addColumn('aksi', function ($row) {
                return view('admin.akun_keuangan.partials.aksi', compact('row'))->render();
            })

            ->rawColumns(['kas_bank', 'cashflow', 'aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        try {
            Log::info('Request masuk ke store()', ['request' => $request->all()]);

            // Validasi request
            $validatedData = $request->validate([
                'id' => 'required|numeric|unique:akun_keuangans,id',
                'kode_akun' => 'required|unique:akun_keuangans,kode_akun',
                'nama_akun' => 'required|string|max:255',
                'tipe_akun' => 'required|in:asset,liability,revenue,expense,equity',
                'kategori_psak' => 'nullable|in:aset_lancar,aset_tidak_lancar,liabilitas_jangka_pendek,liabilitas_jangka_panjang,aset_neto_tidak_terikat,aset_neto_terikat_temporer,aset_neto_terikat_permanen,pendapatan,beban',
                'pembatasan' => 'nullable|in:tidak_terikat,terikat_temporer,terikat_permanen',
                'saldo_normal' => 'required|in:debit,kredit',
                'is_kas_bank' => 'required|boolean',
                'parent_id' => 'nullable|exists:akun_keuangans,id',
                'cashflow_category' => 'nullable|in:operasional,investasi,pendanaan',
                'icon' => 'nullable|string|max:255',
                'show_on_dashboard'   => 'required|boolean',
                'dashboard_scope'     => 'nullable|in:BIDANG,BENDAHARA,YAYASAN,BOTH',
                'dashboard_section'   => 'nullable|in:asset,liability,revenue,expense,kpi',
                'dashboard_calc'      => 'nullable|in:rollup_children_period,rollup_children_ytd,balance_asof,custom',
                'dashboard_order'     => 'nullable|integer',
                'dashboard_title'     => 'nullable|string|max:255',
                'dashboard_link_route' => 'nullable|string|max:255',
                'dashboard_link_param' => 'nullable|string|max:255',
                'dashboard_format'    => 'nullable|in:currency,number',
                'dashboard_masked'    => 'nullable|boolean',
                'dashboard_icon'      => 'nullable|string|max:255',

            ]);

            Log::info('Validasi sukses', ['validatedData' => $validatedData]);

            // Simpan data
            $akun = AkunKeuangan::create($validatedData);
            Log::info('Data berhasil disimpan', ['akun' => $akun]);

            return redirect()
                ->route('admin.akun_keuangan.index')
                ->with('success', 'Akun Keuangan berhasil ditambahkan.');
        } catch (ValidationException $e) {
            Log::error('Validasi gagal', ['errors' => $e->errors()]);
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat menyimpan akun', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan akun.');
        }
    }

    public function edit($id)
    {
        $akunKeuangan = AkunKeuangan::findOrFail($id); // Menampilkan 404 jika tidak ditemukan
        $akunKeuangantanpaparent = AkunKeuangan::whereNull('parent_id')->get();

        return view('admin.akun_keuangan.edit', compact('akunKeuangan', 'akunKeuangantanpaparent'));
    }

    public function detailJson($id)
    {
        $a = AkunKeuangan::query()
            ->with(['parentAkun:id,kode_akun,nama_akun'])
            ->select([
                'id',
                'kode_akun',
                'nama_akun',
                'tipe_akun',
                'kategori_psak',
                'pembatasan',
                'parent_id',
                'saldo_normal',
                'is_kas_bank',
                'cashflow_category',
                'icon',
                'show_on_dashboard',
                'dashboard_scope',
                'dashboard_section',
                'dashboard_calc',
                'dashboard_order',
                'dashboard_title',
                'dashboard_link_route',
                'dashboard_link_param',
                'dashboard_format',
                'dashboard_masked',
                'dashboard_icon',
                'created_at',
                'updated_at'
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => $a,
            'parent' => $a->parentAkun
                ? ($a->parentAkun->kode_akun . ' - ' . $a->parentAkun->nama_akun)
                : '-',
        ]);
    }


    public function update(Request $request, AkunKeuangan $akunKeuangan)
    {
        try {
            Log::info('Request masuk ke update()', [
                'request' => $request->all(),
                'akun' => $akunKeuangan
            ]);

            $validatedData = $request->validate([
                'id' => 'required|numeric|unique:akun_keuangans,id,' . $akunKeuangan->id . ',id',
                'kode_akun' => 'required|unique:akun_keuangans,kode_akun,' . $akunKeuangan->id . ',id',
                'nama_akun' => 'required|string|max:255',
                'tipe_akun' => 'required|in:asset,liability,revenue,expense,equity',
                'kategori_psak' => 'nullable|in:aset_lancar,aset_tidak_lancar,liabilitas_jangka_pendek,liabilitas_jangka_panjang,aset_neto_tidak_terikat,aset_neto_terikat_temporer,aset_neto_terikat_permanen,pendapatan,beban',
                'pembatasan' => 'nullable|in:tidak_terikat,terikat_temporer,terikat_permanen',
                'saldo_normal' => 'required|in:debit,kredit',
                'is_kas_bank' => 'required|boolean',
                'parent_id' => 'nullable|exists:akun_keuangans,id',
                'cashflow_category' => 'nullable|in:operasional,investasi,pendanaan',
                'icon' => 'nullable|string|max:255',
                'show_on_dashboard'   => 'required|boolean',
                'dashboard_scope'     => 'nullable|in:BIDANG,BENDAHARA,YAYASAN,BOTH',
                'dashboard_section'   => 'nullable|in:asset,liability,revenue,expense,kpi',
                'dashboard_calc'      => 'nullable|in:rollup_children_period,rollup_children_ytd,balance_asof,custom',
                'dashboard_order'     => 'nullable|integer',
                'dashboard_title'     => 'nullable|string|max:255',
                'dashboard_link_route' => 'nullable|string|max:255',
                'dashboard_link_param' => 'nullable|string|max:255',
                'dashboard_format'    => 'nullable|in:currency,number',
                'dashboard_masked'    => 'nullable|boolean',
                'dashboard_icon'      => 'nullable|string|max:255',
            ]);

            // Update data
            $akunKeuangan->update($validatedData);

            Log::info('Data berhasil diperbarui', ['akun' => $akunKeuangan]);

            return redirect()
                ->route('admin.akun_keuangan.index')
                ->with('success', 'Akun berhasil diperbarui!');
        } catch (ValidationException $e) {
            Log::error('Validasi gagal saat update', ['errors' => $e->errors()]);
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat update akun', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat update akun.');
        }
    }

    public function destroy(AkunKeuangan $akunKeuangan)
    {
        $akunKeuangan->delete();
        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
