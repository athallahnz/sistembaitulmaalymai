<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
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

// Admin routes
Route::middleware(['role:Admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('admin/users/data', [UserController::class, 'data'])->name('admin.users.data');
    Route::put('admin/users/restore/{id}', [UserController::class, 'restore'])->name('admin.users.restore');
    Route::delete('admin/users/force-delete/{id}', [UserController::class, 'forceDelete'])->name('admin.users.forceDelete');
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

});

// Bidang routes
Route::middleware(['role:Bendahara|Bidang'])->group(function () {
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    // Route untuk dashboard Bidang
    Route::get('/bidang/dashboard', [BidangController::class, 'index'])->name('bidang.index');
    Route::get('/bidang/detail/data', [BidangController::class, 'getDetailData'])->name('bidang.detail.data');
    Route::get('/bidang/detail', [BidangController::class, 'showDetail'])->name('bidang.detail');
    Route::get('/laporan/arus-kas', [LaporanKeuanganController::class, 'arusKas'])->name('laporan.arus-kas');
    Route::get('/laporan/arus-kas/pdf', [LaporanKeuanganController::class, 'exportArusKasPDF'])->name('laporan.arus-kas.pdf');
    Route::get('/laporan/posisi-keuangan', [LaporanKeuanganController::class, 'posisiKeuangan'])->name('laporan.posisi-keuangan');
    Route::get('/laporan/laba-rugi', [LaporanKeuanganController::class, 'labaRugi'])->name('laporan.laba-rugi');
    Route::get('/laporan/neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldo'])->name('laporan.neraca-saldo');

    // Route untuk transaksi
    Route::prefix('bidang/transaksi')->group(function () {
        // Route untuk CRU Transaksi
        Route::get('/', [TransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('/create', [TransaksiController::class, 'create'])->name('transaksi.create');
        Route::post('/store', [TransaksiController::class, 'store'])->name('transaksi.store');
        Route::post('/storebank', [TransaksiController::class, 'storeBankTransaction'])->name('transaksi.storeBankTransaction');
        Route::get('transaksi/data', [TransaksiController::class, 'getData'])->name('transaksi.data');
        Route::get('{id}/edit', [TransaksiController::class, 'edit'])->name('transaksi.edit');
        Route::put('/transaksi/{id}/update', [TransaksiController::class, 'update'])->name('transaksi.update');
        Route::put('/transaksi/{id}/update-bank', [TransaksiController::class, 'updateBankTransaction'])->name('transaksi.updateBankTransaction');

        // Route untuk Cetak Pdf
        Route::get('nota/{id}', [TransaksiController::class, 'exportNota'])->name('transaksi.exportPdf');
        Route::get('transaksi/export-pdf', [TransaksiController::class, 'exportAllPdf'])->name('transaksi.exportAllPdf');
        Route::get('transaksi/export-excel', [TransaksiController::class, 'exportExcel'])->name('transaksi.exportExcel');
        // Route untuk ledger
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::get('/ledger/data', [LedgerController::class, 'getData'])->name('ledger.data');
        // Route untuk laporan bank
        Route::get('/bank', [LaporanController::class, 'index'])->name('laporan.bank');
        Route::get('/bank/data', [LaporanController::class, 'getData'])->name('laporan.bank.data');

    });


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
