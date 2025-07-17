<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Admin');  // Menggunakan middleware untuk role admin
    }
    public function index()
    {
        return view('admin.index');
    }
}

