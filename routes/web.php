<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BendaharaController;
use App\Http\Controllers\ManajerController;
use App\Http\Controllers\BidangController;
use App\Http\Controllers\KetuaController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\SaldoKeuanganController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Admin routes
Route::middleware(['role:Admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('admin/admin/users/data', [UserController::class, 'data'])->name('admin.users.data');
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
Route::middleware(['role:Bidang'])->group(function () {
    // Route untuk dashboard Bidang
    Route::get('/bidang/dashboard', [BidangController::class, 'index'])->name('bidang.index');
    // Route::get('/bidang/{id}', [BidangController::class, 'index'])->name('bidang.index');

    // Route untuk transaksi
    Route::prefix('bidang/transaksi')->group(function () {
        Route::get('/', [TransaksiController::class, 'index'])->name('transaksi.index');  // Menampilkan daftar transaksi
        Route::get('/create', [TransaksiController::class, 'create'])->name('transaksi.create'); // Form input transaksi
        Route::post('/store', [TransaksiController::class, 'store'])->name('transaksi.store'); // Menyimpan transaksi
        Route::get('transaksi/data', [TransaksiController::class, 'getData'])->name('transaksi.data');
    });

    // Route untuk saldo keuangan
    Route::get('/saldo-keuangan', [SaldoKeuanganController::class, 'index'])->name('saldo-keuangan.index');
});




// Route untuk welcome page
Route::get('/', function () {
    return redirect()->route('login');
});

// Autentikasi Routes (Login & Logout)
Auth::routes();

// Login routes
// Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Admin User Management Route
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('admin/users/data', [UserController::class, 'data'])->name('admin.users.data');
    Route::put('admin/users/restore/{id}', [UserController::class, 'restore'])->name('admin.users.restore');
    Route::delete('admin/users/force-delete/{id}', [UserController::class, 'forceDelete'])->name('admin.users.forceDelete');
});

// Route User Manajemen
Route::resource('users', UserController::class)->middleware('auth');
Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

// // Routing untuk transaksi
// Route::prefix('transaksi')->middleware(['auth', 'role:Bidang'])->group(function () {
//     Route::resource('transaksi', TransaksiController::class);
// });



// Route untuk home setelah login
Route::get('/home', [HomeController::class, 'index'])->name('home');

