<?php

use App\Models\EduPayment;
use App\Models\TagihanSpp;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
use App\Http\Controllers\Laporan\LaporanController;
use App\Http\Controllers\Laporan\LaporanKeuanganController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\HutangController;
use App\Http\Controllers\Pendidikan\EduPaymentController;
use App\Http\Controllers\Pendidikan\EduClassController;
use App\Http\Controllers\Pendidikan\StudentController;
use App\Http\Controllers\Pendidikan\StudentCostController;
use App\Http\Controllers\Pendidikan\TagihanSppController;
use App\Http\Controllers\Pendidikan\WaliMuridController;
use App\Http\Controllers\Sosial\SosialController;
use App\Http\Controllers\Sosial\TrackingInfaqController;
use App\Exports\TransaksisExport;
use Maatwebsite\Excel\Facades\Excel;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Autentikasi Routes (Login & Logout)
Auth::routes();

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
    Route::get('/bendahara/detail/data', [BendaharaController::class, 'getDetailDataBendahara'])->name('bendahara.detail.data');
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

    Route::get('/bidang/laporan/arus-kas', [LaporanKeuanganController::class, 'arusKas'])->name('laporan.arus-kas');
    Route::get('/bidang/laporan/arus-kas/pdf', [LaporanKeuanganController::class, 'exportArusKasPDF'])->name('laporan.arus-kas.pdf');
    Route::get('/bidang/laporan/posisi-keuangan', [LaporanKeuanganController::class, 'posisiKeuangan'])->name('laporan.posisi-keuangan');
    Route::get('/bidang/laporan/neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldo'])->name('laporan.neraca-saldo');
    Route::get('/bidang/laporan/aktivitas', [LaporanKeuanganController::class, 'aktivitas'])->name('laporan.aktivitas');

    Route::prefix('laporan')->name('laporan.')->group(function () {
        // halaman utama (sudah ada):
        // Route::get('/neraca', [LaporanKeuanganController::class, 'neracaSaldo'])->name('neraca-saldo');
        // Route::get('/aktivitas', [LaporanKeuanganController::class, 'laporanAktivitas'])->name('aktivitas');

        // export neraca
        Route::get('/neraca/export/excel', [LaporanKeuanganController::class, 'exportNeracaExcel'])->name('neraca.export.excel');
        Route::get('/neraca/export/pdf', [LaporanKeuanganController::class, 'exportNeracaPdf'])->name('neraca.export.pdf');

        // export aktivitas
        Route::get('/aktivitas/export/excel', [LaporanKeuanganController::class, 'exportAktivitasExcel'])->name('aktivitas.export.excel');
        Route::get('/aktivitas/export/pdf', [LaporanKeuanganController::class, 'exportAktivitasPdf'])->name('aktivitas.export.pdf');
    });

    // Route untuk transaksi
    Route::prefix('bidang/transaksi')->group(function () {
        // Route untuk CRU Transaksi
        Route::get('/', [TransaksiController::class, 'index'])->name('transaksi.index.bidang'); // List transactions
        Route::get('/create', [TransaksiController::class, 'create'])->name('transaksi.create'); // Create transaction form
        Route::post('/store', [TransaksiController::class, 'store'])->name('transaksi.store'); // Store transaction
        Route::post('/storebank', [TransaksiController::class, 'storeBankTransaction'])->name('transaksi.storeBank'); // Store bank transaction
        Route::get('data', [TransaksiController::class, 'getData'])->name('transaksi.data'); // Get transaction data
        Route::get('{id}/edit', [TransaksiController::class, 'edit'])->name('transaksi.edit'); // Edit transaction form
        Route::put('{id}/update', [TransaksiController::class, 'update'])->name('transaksi.update'); // Update transaction
        Route::put('{id}/update-bank', [TransaksiController::class, 'updateBankTransaction'])->name('transaksi.updateBank'); // Update bank transaction
        Route::delete('{id}', [TransaksiController::class, 'destroy'])->name('transaksi.destroy');

        // Route untuk Cetak Pdf
        Route::get('nota/{id}', [TransaksiController::class, 'exportNota'])->name('transaksi.exportPdf'); // Export single transaction PDF
        Route::get('export-pdf', [TransaksiController::class, 'exportAllPdf'])->name('transaksi.exportAllPdf'); // Export all transactions PDF
        Route::get('export-excel', [TransaksiController::class, 'exportExcel'])->name('transaksi.exportExcel'); // Export all transactions Excel

        // Route untuk ledger
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index'); // Ledger index
        Route::get('/ledger/data', [LedgerController::class, 'getData'])->name('ledger.data'); // Ledger data

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

    Route::get('/payment-dashboard', [EduPaymentController::class, 'index'])->name('payment.dashboard');
    Route::get('/payment-dashboard/{student}', [EduPaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/store', [EduPaymentController::class, 'store'])->name('payment.store');
    Route::get('/payments/data', [EduPaymentController::class, 'getData'])->name('payments.data');
    Route::get('/payment/history/{student_id}', [EduPaymentController::class, 'history'])->name('payment.history');
    Route::get('/payments/{payment}/kwitansi', [EduPaymentController::class, 'cetakKwitansiPerTransaksi'])->name('payments.kwitansi.per');

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

    Route::prefix('bidang/sosial/infaq')->name('sosial.infaq.')->group(function () {
        Route::get('/', [SosialController::class, 'index'])->name('index');            // dashboard (modal create)
        Route::get('/create', [SosialController::class, 'create'])->name('create');    // optional (kalau mau halaman terpisah)
        Route::get('/lookup', [SosialController::class, 'lookupWarga'])->name('lookup');
        Route::get('/check', [SosialController::class, 'checkPaid'])->name('check');
        Route::post('/store', [SosialController::class, 'store'])->name('store');
        Route::get('/detail/{id}', [SosialController::class, 'show'])->name('detail');
        Route::put('/update/{id}', [SosialController::class, 'update'])->name('update'); // <— dipakai form di atas
        Route::get('/receipt/{warga}/{bulan}', [SosialController::class, 'receipt'])->name('receipt'); // cetak
        Route::get('/receipt/{warga}/{bulan}/open-wa', [SosialController::class, 'openWhatsappLink'])
            ->name('open-wa');

        // halaman verifikasi kwitansi (bisa public jika mau, pindahkan keluar middleware auth)
        Route::get('/verify/{warga}/{bulan}/{year}', [SosialController::class, 'verifyReceipt'])
            ->name('verify');
    });
});

// // ========== ROUTE PUBLIK (untuk WARGA) ==========
Route::prefix('warga-infaq')->name('warga.')->group(function () {
    // halaman & aksi login khusus warga infaq
    Route::get('/masuk', [TrackingInfaqController::class, 'showLogin'])->name('login.form');
    Route::post('/masuk', [TrackingInfaqController::class, 'login'])->name('login');

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

