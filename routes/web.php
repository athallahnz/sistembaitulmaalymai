<?php

use App\Models\EduPayment;
use App\Models\TagihanSpp;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\LandingPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SidebarSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AddBidangController;
use App\Http\Controllers\Admin\AkunKeuanganController;
use App\Http\Controllers\BendaharaController;
use App\Http\Controllers\ManajerController;
use App\Http\Controllers\BidangController;
use App\Http\Controllers\KetuaController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\HutangController;
use App\Http\Controllers\PengajuanDanaController;
use App\Http\Controllers\Laporan\LaporanController;
use App\Http\Controllers\Laporan\LaporanKeuanganController;
use App\Http\Controllers\Pendidikan\EduPaymentController;
use App\Http\Controllers\Pendidikan\EduClassController;
use App\Http\Controllers\Pendidikan\StudentController;
use App\Http\Controllers\Pendidikan\StudentCostController;
use App\Http\Controllers\Pendidikan\TagihanSppController;
use App\Http\Controllers\Pendidikan\WaliMuridController;
use App\Http\Controllers\Kemasjidan\KemasjidanController;
use App\Http\Controllers\Kemasjidan\TrackingInfaqController;
use App\Http\Controllers\Sosial\IuranBulananController;
use App\Http\Controllers\WargaController;
use App\Exports\TransaksisExport;
use Maatwebsite\Excel\Facades\Excel;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Autentikasi Routes (Login & Logout)
Auth::routes();

Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!File::exists($fullPath)) {
        abort(404);
    }

    $mimeType = File::mimeType($fullPath);

    return Response::file($fullPath, [
        'Content-Type' => $mimeType,
    ]);
})->where('path', '.*');

// Route untuk welcome page
Route::get('/', function () {
    return redirect()->route('login');
});

// Route Landing Page (Public)
Route::get('/welcome', [LandingPageController::class, 'index'])->name('landing');

// Route untuk home setelah login
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/spp/verifikasi/{id}', function ($id) {
    $tagihan = TagihanSpp::with('student')->findOrFail($id); // ini akan return 1 model, bukan collection

    return "Kwitansi ini valid untuk: " . $tagihan->student->name . ", Bulan: " . $tagihan->bulan;
})->name('spp.verifikasi');

Route::get('/kwitansi/verifikasi/{token}', [EduPaymentController::class, 'verifikasiKwitansi'])->name('payments.verifikasi');

// Route untuk Update Profile Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
});

// Route User Manajemen
Route::resource('users', UserController::class)->middleware('auth');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('setting/edit', [SidebarSettingController::class, 'edit'])->name('sidebar_setting.edit');
    Route::post('setting/update', [SidebarSettingController::class, 'update'])->name('sidebar_setting.update');

    Route::get('/dashboard', [AdminController::class, 'index'])->name('index'); // admin.index

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/data', [UserController::class, 'data'])->name('data');
        Route::put('/restore/{id}', [UserController::class, 'restore'])->name('restore');
        Route::delete('/force-delete/{id}', [UserController::class, 'forceDelete'])->name('forceDelete');
    });

    Route::prefix('akun-keuangan')->name('akun_keuangan.')->group(function () {
        // Index (utama)
        Route::get('/', [AkunKeuanganController::class, 'index'])->name('index');

        // DataTables AJAX
        Route::get('/data/table', [AkunKeuanganController::class, 'dataTable'])->name('datatable');

        // Resource CRUD (tanpa index karena sudah ada di atas)
        Route::resource('data', AkunKeuanganController::class)->except(['index'])->parameters([
            'data' => 'akunKeuangan'
        ])->names([
                    'create' => 'create',
                    'store' => 'store',
                    'show' => 'show',
                    'edit' => 'edit',
                    'update' => 'update',
                    'destroy' => 'destroy',
                ]);
    });

    Route::prefix('add_bidangs')->name('add_bidangs.')->group(function () {
        Route::get('/', [AddBidangController::class, 'index'])->name('index');
        Route::get('/create', [AddBidangController::class, 'create'])->name('create');
        Route::post('/', [AddBidangController::class, 'store'])->name('store');
        Route::get('/{bidang}/edit', [AddBidangController::class, 'edit'])->name('edit');
        Route::put('/{bidang}', [AddBidangController::class, 'update'])->name('update');
        Route::delete('/{bidang}', [AddBidangController::class, 'destroy'])->name('destroy');
        Route::get('/data', [AddBidangController::class, 'getData'])->name('data');
    });
});

// Ketua routes
Route::middleware(['role:Ketua Yayasan'])->group(function () {
    Route::get('/ketua/dashboard', [KetuaController::class, 'index'])->name('ketua.index');
});

// Manajer routes
Route::middleware(['role:Manajer Keuangan'])->group(function () {
    Route::get('/manajer/dashboard', [ManajerController::class, 'index'])->name('manajer.index');
});

// Bendahara routes
Route::middleware(['role:Bendahara'])->group(function () {
    Route::get('/bendahara/dashboard', [BendaharaController::class, 'index'])->name('bendahara.index');

    Route::get('/bendahara/laporan/neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldoBendahara'])->name('laporan.neraca-saldo-bendahara');
    Route::get('/bendahara/detail', [BendaharaController::class, 'showDetailBendahara'])->name('bendahara.detail');
    Route::get('/bendahara/detail/data', [BendaharaController::class, 'detailData'])->name('bendahara.detail.data');
    // NEW AJAX ROUTE: Ambil Saldo Akun berdasarkan ID
    Route::get('api/get-saldo/{akunId}', [PengajuanDanaController::class, 'getSaldoAkun'])->name('api.get-saldo');
});

// Bidang routes
Route::middleware(['role:Bendahara|Bidang'])->group(function () {

    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    // Route untuk dashboard Bidang
    Route::get('/bidang/dashboard', [BidangController::class, 'index'])->name('bidang.index');
    Route::get('/bidang/detail/data', [BidangController::class, 'getDetailData'])->name('bidang.detail.data');
    Route::get('/bidang/detail', [BidangController::class, 'showDetail'])->name('bidang.detail');
    Route::get('/piutangs/data', [PiutangController::class, 'getData'])->name('piutangs.data');
    Route::get('/hutangs/data', [HutangController::class, 'getData'])->name('hutangs.data');

    Route::prefix('bidang/laporan')->name('laporan.')->group(function () {

        // ===== ARUS KAS =====
        Route::get('/arus-kas', [LaporanKeuanganController::class, 'arusKas'])
            ->name('arus-kas');

        Route::get('/arus-kas/export/pdf', [LaporanKeuanganController::class, 'exportArusKasPdf'])
            ->name('arus-kas.export.pdf');

        Route::get('/arus-kas/export/excel', [LaporanKeuanganController::class, 'exportArusKasExcel'])
            ->name('arus-kas.export.excel');

        // ===== POSISI KEUANGAN (Neraca PSAK 45) =====
        Route::get('/posisi-keuangan', [LaporanKeuanganController::class, 'posisiKeuangan'])
            ->name('posisi-keuangan');

        Route::get('/posisi-keuangan/export/pdf', [LaporanKeuanganController::class, 'exportPosisiKeuanganPdf'])
            ->name('posisi-keuangan.export.pdf');

        Route::get('/posisi-keuangan/export/excel', [LaporanKeuanganController::class, 'exportPosisiKeuanganExcel'])
            ->name('posisi-keuangan.export.excel');

        // ===== AKTIVITAS =====
        Route::get('/aktivitas', [LaporanKeuanganController::class, 'aktivitas'])
            ->name('aktivitas');

        Route::get('/aktivitas/export/pdf', [LaporanKeuanganController::class, 'exportAktivitasPdf'])
            ->name('aktivitas.export.pdf');

        Route::get('/aktivitas/export/excel', [LaporanKeuanganController::class, 'exportAktivitasExcel'])
            ->name('aktivitas.export.excel');
    });

    // Route untuk transaksi
    Route::prefix('bidang/transaksi')->group(function () {
        // Route untuk CRU Transaksi
        Route::get('/', [TransaksiController::class, 'index'])->name('transaksi.index.bidang'); // List transactions
        Route::get('/create', [TransaksiController::class, 'create'])->name('transaksi.create'); // Create transaction form
        Route::post('/store', [TransaksiController::class, 'store'])->name('transaksi.store'); // Store transaction
        Route::post('/storebank', [TransaksiController::class, 'storeBankTransaction'])->name('transaksi.storeBank'); // Store bank transaction
        Route::get('data', [TransaksiController::class, 'getData'])->name('transaksi.data'); // Get transaction data
        Route::get('/mutasi/data', [TransaksiController::class, 'getMutasiData'])->name('transaksi.mutasi.data');
        Route::get('{id}/edit', [TransaksiController::class, 'edit'])->name('transaksi.edit'); // Edit transaction form
        Route::put('{id}/update', [TransaksiController::class, 'update'])->name('transaksi.update'); // Update transaction
        Route::put('{id}/update-bank', [TransaksiController::class, 'updateBankTransaction'])->name('transaksi.updateBank'); // Update bank transaction
        Route::delete('{id}', [TransaksiController::class, 'destroy'])->name('transaksi.destroy');
        Route::get('/{id}/json', [TransaksiController::class, 'showJson'])->name('transaksi.json');

        // Route untuk menyimpan Opening Balance
        Route::post('/transaksi/opening-balance', [TransaksiController::class, 'storeOpeningBalance'])
            ->name('transaksi.opening-balance.store');

        // Route untuk menyimpan Transfer antar Akun
        Route::post('/transaksi/transfer', [TransaksiController::class, 'storeTransfer'])
            ->name('transaksi.transfer.store');

        // Route untuk Cetak Pdf
        Route::get('nota/{id}', [TransaksiController::class, 'exportNota'])->name('transaksi.exportPdf'); // Export single transaction PDF
        Route::get('export-pdf', [TransaksiController::class, 'exportAllPdf'])->name('transaksi.exportAllPdf'); // Export all transactions PDF
        Route::get('export-excel', [TransaksiController::class, 'exportExcel'])->name('transaksi.exportExcel'); // Export all transactions Excel

        // Route untuk ledger
        Route::get('/kas', [LedgerController::class, 'index'])->name('ledger.index'); // Ledger index
        Route::get('/kas/data', [LedgerController::class, 'getData'])->name('ledger.data'); // Ledger data

        // Route untuk laporan bank
        Route::get('/bank', [LaporanController::class, 'index'])->name('laporan.bank'); // Bank report index
        Route::get('/bank/data', [LaporanController::class, 'getData'])->name('laporan.bank.data'); // Bank report data
    });

    Route::get('/piutangs/terima', [PiutangController::class, 'indexPenerima'])->name('piutangs.penerima');
    Route::get('/piutangs/{id}/pay', [PiutangController::class, 'showPayForm'])->name('piutangs.showPayForm');
    Route::post('/piutangs/{id}/pay', [PiutangController::class, 'storePayment'])->name('piutangs.storePayment');

    Route::get('/pendidikan/payment/form', function () {
        return view('bidang.pendidikan.payments.form');
    })->name('pendidikan.payment.form');

    // ========== PMB: Payment Dashboard ==========
    Route::get('/payment-dashboard', [EduPaymentController::class, 'index'])->name('payment.dashboard');
    Route::get('/payment-dashboard/{student}', [EduPaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/store', [EduPaymentController::class, 'store'])->name('payment.store');
    Route::get('/payments/data', [EduPaymentController::class, 'getData'])->name('payments.data');
    Route::get('/payments/chart-bulanan', [EduPaymentController::class, 'chartBulanan'])->name('payments.chart-bulanan');
    Route::get('/payment/history/{student_id}', [EduPaymentController::class, 'history'])->name('payment.history');
    Route::get('/payments/{payment}/kwitansi', [EduPaymentController::class, 'cetakKwitansiPerTransaksi'])->name('payments.kwitansi.per');

    // ========== SPP: Tagihan SPP ==========
    Route::get('/pendidikan/tagihan-spp/create', [TagihanSppController::class, 'create'])->name('tagihan-spp.create');
    Route::get('/dashboard-tagihan', [TagihanSppController::class, 'dashboardTagihan'])->name('tagihan-spp.dashboard');
    Route::get('/dashboard-tagihan/data', [TagihanSppController::class, 'getData'])->name('tagihan-spp.data');
    Route::post('/tagihan-spp/store', [TagihanSppController::class, 'store'])->name('tagihan-spp.store');
    Route::get('/tagihan-spp/export', [TagihanSppController::class, 'export'])->name('tagihan-spp.export');
    Route::post('/tagihan-spp/bayar', [TagihanSppController::class, 'bayar'])->name('tagihan-spp.bayar');
    Route::get('/chart-bulanan', [TagihanSppController::class, 'getChartBulanan'])->name('tagihan-spp.chart-bulanan');
    Route::get('/api/spp-tagihan-by-rfid/{uid}', [TagihanSppController::class, 'getTagihanByRfid']);
    Route::get('/tagihan-spp/{id}', [TagihanSppController::class, 'show'])->name('tagihan-spp.show');
    Route::get('/tagihan-spp/kwitansi/{id}', [TagihanSppController::class, 'printReceipt'])->name('tagihan-spp.kwitansi.per');

    // ========== PMB: recognize per siswa dari payment-dashboard ==========
    Route::post('/payment-dashboard/{student}/recognize-pmb', [EduPaymentController::class, 'recognizePMB'])
        ->name('payment.recognize_pmb');

    // ========== SPP: recognize per siswa ==========
    Route::post('/tagihan-spp/{student}/recognize', [TagihanSppController::class, 'recognizeStudentSPP'])
        ->name('tagihan-spp.recognize.student');

    // ========== SPP: recognize BULK per bulan (semua siswa yang punya tagihan) ==========
    Route::post('/dashboard-tagihan/recognize-bulk', [TagihanSppController::class, 'recognizeSPPBulk'])
        ->name('tagihan-spp.recognize.bulk');

    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::get('students/{id}', [StudentController::class, 'show'])->name('students.show');
    Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/data', [StudentController::class, 'getData'])->name('students.data');

    Route::get('edu_classes/data', [EduClassController::class, 'data'])->name('edu_classes.data');
    Route::resource('edu_classes', EduClassController::class);

    Route::get('/students/{student}/costs/create', [StudentCostController::class, 'create'])->name('student_costs.create');
    Route::post('/students/{student}/costs', [StudentCostController::class, 'store'])->name('student_costs.store');
    Route::get('/kelas/{id}/akun-keuangan', [StudentController::class, 'getAkunKeuanganByClass']);

    Route::resource('wali-murids', WaliMuridController::class)->only(['index', 'show']);

    Route::prefix('bidang/kemasjidan/infaq')->name('kemasjidan.infaq.')->group(function () {
        Route::get('/', [KemasjidanController::class, 'index'])->name('index');            // dashboard (modal create)
        Route::get('/create', [KemasjidanController::class, 'create'])->name('create');    // optional (kalau mau halaman terpisah)
        Route::get('/lookup', [KemasjidanController::class, 'lookupWarga'])->name('lookup');
        Route::get('/check', [KemasjidanController::class, 'checkPaid'])->name('check');
        Route::post('/store', [KemasjidanController::class, 'store'])->name('store');
        Route::get('/detail/{id}', [KemasjidanController::class, 'show'])->name('detail');
        Route::put('/update/{id}', [KemasjidanController::class, 'update'])->name('update'); // <— dipakai form di atas
        Route::get('/receipt/{warga}/{bulan}', [KemasjidanController::class, 'receipt'])->name('receipt'); // cetak
        Route::get('/receipt/{warga}/{bulan}/open-wa', [KemasjidanController::class, 'openWhatsappLink'])
            ->name('open-wa');
        Route::get('/verify/{warga}/{bulan}/{year}', [KemasjidanController::class, 'verifyReceipt'])
            ->name('verify');
        Route::get('/datatable', [KemasjidanController::class, 'datatable'])
            ->name('datatable');
    });

    Route::resource('wargas', WargaController::class)->except(['show']);
    Route::prefix('bidang/kemasjidan/warga')->name('kemasjidan.warga.')->group(function () {
        Route::get('/', [WargaController::class, 'index'])->name('index');
        Route::get('/data', [WargaController::class, 'data'])->name('data');
        Route::post('/import/preview', [WargaController::class, 'importPreview'])->name('import.preview');
        Route::post('/import/commit', [WargaController::class, 'importCommit'])->name('import.commit');
        // API: ambil anggota keluarga (JSON)
        Route::get('/{warga}/anggota', [WargaController::class, 'getAnggota'])
            ->name('anggota');

        // Tandai kepala keluarga meninggal + alihkan kepala
        Route::post('/{warga}/meninggal', [WargaController::class, 'markAsDeceased'])
            ->name('meninggal');
    });


    Route::prefix('bidang/sosial/iuran')->name('sosial.iuran.')->group(function () {
        // yang TIDAK pakai parameter dulu
        Route::get('/', [IuranBulananController::class, 'index'])->name('index');
        Route::post('/', [IuranBulananController::class, 'store'])->name('store');

        // AJAX / util routes (spesifik)
        Route::get('/datatable', [IuranBulananController::class, 'datatable'])->name('datatable');
        Route::get('/anggota/{kk}', [IuranBulananController::class, 'anggota'])
            ->name('anggota')
            ->whereNumber('kk');
        Route::post('/check-paid', [IuranBulananController::class, 'checkPaid'])->name('checkPaid');

        // baru yang pakai wildcard di paling bawah
        Route::get('/{warga}', [IuranBulananController::class, 'show'])
            ->name('show')
            ->whereNumber('warga');

        Route::put('/{warga}', [IuranBulananController::class, 'update'])
            ->name('update')
            ->whereNumber('warga');
    });

    Route::prefix('bidang/pengajuan')->name('pengajuan.')->group(function () {
        // List Data
        Route::get('/', [PengajuanDanaController::class, 'index'])->name('index');

        // Create Data
        Route::get('/create', [PengajuanDanaController::class, 'create'])->name('create');
        Route::post('/store', [PengajuanDanaController::class, 'store'])->name('store');

        // Detail Data
        Route::get('/{id}', [PengajuanDanaController::class, 'show'])->name('show');

        // Actions (Approval & Pencairan)
        Route::post('/{id}/approve', [PengajuanDanaController::class, 'approve'])->name('approve');
        Route::get('api/approval-count', [PengajuanDanaController::class, 'getApprovalCount'])->name('api.approval.count');
        Route::post('/{id}/reject', [PengajuanDanaController::class, 'reject'])->name('reject');
        Route::post('/{id}/cairkan', [PengajuanDanaController::class, 'cairkan'])->name('cairkan');

        // Edit & Update Data
        Route::get('{id}/edit', [PengajuanDanaController::class, 'edit'])->name('edit');
        Route::put('{id}', [PengajuanDanaController::class, 'update'])->name('update');
        Route::get('pengajuan-json/{id}', [PengajuanDanaController::class, 'getPengajuanJson'])->name('json');
        Route::get('{id}/export-pdf', [PengajuanDanaController::class, 'exportPdf'])->name('export.pdf');
    });
});

// // ========== ROUTE PUBLIK (untuk WARGA) ==========
Route::prefix('tracking-infaq')->name('warga.')->group(function () {
    // halaman & aksi login khusus warga infaq
    Route::get('/login', [TrackingInfaqController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [TrackingInfaqController::class, 'login'])->name('login');

    // logout
    Route::post('/keluar', [TrackingInfaqController::class, 'logout'])->name('logout');

    // halaman tracking (protected)
    Route::middleware('warga.auth')->group(function () {
        Route::get('/tracking', [TrackingInfaqController::class, 'dashboard'])->name('dashboard');
    });
});


// Login routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login/nomor', [LoginController::class, 'verifyNomor'])->name('login.nomor');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/login/reset', function () {
    session()->forget(['step', 'nomor']);
    return redirect()->route('login');
})->name('login.reset');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::resource('piutangs', PiutangController::class);
Route::resource('hutangs', HutangController::class);

Route::get('/notifications/read', function () {
    dd(Auth::user());
    $user = Auth::user();

    if ($user && method_exists($user, 'unreadNotifications')) {
        $user->unreadNotifications()->markAsRead();
    }

    return redirect()->back();
})->name('notifications.markAsRead');

