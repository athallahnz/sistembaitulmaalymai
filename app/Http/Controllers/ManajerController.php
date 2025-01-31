<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManajerController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Manajer Keuangan');  // Menggunakan middleware untuk role ketua
    }
    public function index()
    {
        return view('manajer.index');
    }
}

