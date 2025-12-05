<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Bidang;
use App\Models\AkunKeuangan;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Admin');  // Menggunakan middleware untuk role admin
    }

    public function index()
    {
        // Total untuk cards
        $totalUser = User::count();
        $totalAkun = AkunKeuangan::count();
        $totalBidang = Bidang::count();

        // Data chart: Akun Keuangan per tipe_akun
        $akunByTipe = AkunKeuangan::select('tipe_akun', DB::raw('COUNT(*) as total'))
            ->groupBy('tipe_akun')
            ->get();

        // Data chart: User per role
        $userByRole = User::select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->get();

        // Activity log terakhir (misal 10 baris)
        $latestActivities = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.index', compact(
            'totalUser',
            'totalAkun',
            'totalBidang',
            'akunByTipe',
            'userByRole',
            'latestActivities'
        ));
    }
}
