<?php

use App\Models\EduPayment;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AddBidangController;
use App\Http\Controllers\AkunKeuanganController;
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

Route::middleware(['role:Admin'])->prefix('admin')->group(function () {
    // Dashboard Admin
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.index');

    // Manajemen Users
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/data', [UserController::class, 'data'])->name('admin.users.data');
    Route::put('/users/restore/{id}', [UserController::class, 'restore'])->name('admin.users.restore');
    Route::delete('/users/force-delete/{id}', [UserController::class, 'forceDelete'])->name('admin.users.forceDelete');

    // Manajemen Akun Keuangan
    Route::get('/akun-keuangan', [AkunKeuanganController::class, 'index'])->name('admin.akun_keuangan.index');
    Route::get('/akun-keuangan/create', [AkunKeuanganController::class, 'create'])->name('admin.akun_keuangan.create');
    Route::post('/akun-keuangan', [AkunKeuanganController::class, 'store'])->name('admin.akun_keuangan.store');
    Route::get('/akun-keuangan/{akunKeuangan}/edit', [AkunKeuanganController::class, 'edit'])->name('admin.akun_keuangan.edit');
    Route::put('/akun-keuangan/{akunKeuangan}', [AkunKeuanganController::class, 'update'])->name('admin.akun_keuangan.update');
    Route::delete('/akun-keuangan/{akunKeuangan}', [AkunKeuanganController::class, 'destroy'])->name('admin.akun_keuangan.destroy');

    // Manajemen Bidang
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
    Route::get('/bidang/laporan/arus-kas', [LaporanKeuanganController::class, 'arusKas'])->name('laporan.arus-kas');
    Route::get('/bidang/laporan/arus-kas/pdf', [LaporanKeuanganController::class, 'exportArusKasPDF'])->name('laporan.arus-kas.pdf');
    Route::get('/bidang/laporan/posisi-keuangan', [LaporanKeuanganController::class, 'posisiKeuangan'])->name('laporan.posisi-keuangan');
    Route::get('/bidang/laporan/neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldo'])->name('laporan.neraca-saldo');
    Route::get('/piutangs/data', [PiutangController::class, 'getData'])->name('piutangs.data');
    Route::get('/hutangs/data', [HutangController::class, 'getData'])->name('hutangs.data');

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

    Route::post('/payment/store', [EduPaymentController::class, 'store'])->name('payment.store');
    Route::get('/payment/history/{student_id}', [EduPaymentController::class, 'history'])->name('payment.history');
    Route::get('/payments/data', [EduPaymentController::class, 'getData'])->name('payments.data');
    Route::get('/payment-dashboard', [EduPaymentController::class, 'index'])->name('payment.dashboard');
    Route::get('/payment-dashboard/{student}', [EduPaymentController::class, 'show'])->name('payment.show');
    Route::get('/pendidikan/tagihan-spp/create', [TagihanSppController::class, 'create'])->name('tagihan-spp.create');
    Route::post('/tagihan-spp/store', [TagihanSppController::class, 'store'])->name('tagihan-spp.store');
    Route::get('/tagihan-spp/export', [TagihanSppController::class, 'export'])->name('tagihan-spp.export');
    Route::get('/dashboard-tagihan', [TagihanSppController::class, 'dashboardTagihan'])->name('tagihan-spp.dashboard');


    //Student Route
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::get('students/{id}', [StudentController::class, 'show'])->name('students.show');
    Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/data', [StudentController::class, 'getData'])->name('students.data');

    Route::resource('edu_classes', EduClassController::class);

    Route::get('/students/{student}/costs/create', [StudentCostController::class, 'create'])->name('student_costs.create');
    Route::post('/students/{student}/costs', [StudentCostController::class, 'store'])->name('student_costs.store');
    Route::get('/kelas/{id}/akun-keuangan', [StudentController::class, 'getAkunKeuanganByClass']);

    Route::resource('wali-murids', \App\Http\Controller\Pendidikan\WaliMuridController::class)->only(['index', 'show']);
});

// Route untuk home setelah login
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Route untuk Update Profile Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
});

// Route User Manajemen
Route::resource('users', UserController::class)->middleware('auth');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

// Login routes
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');


Route::resource('piutangs', PiutangController::class);
Route::resource('hutangs', HutangController::class);



Route::get('/notifications/read', function () {
    if (Auth::check()) {
        Auth::user()->unreadNotifications->markAsRead();
    }
    return redirect()->back();
})->name('notifications.markAsRead');
