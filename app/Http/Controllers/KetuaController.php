<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KetuaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Ketua Yayasan');  // Menggunakan middleware untuk role ketua
    }
    public function index()
    {
        return view('ketua.index');
    }
}

