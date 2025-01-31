<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BendaharaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bedahara');  // Menggunakan middleware untuk role ketua
    }
    public function index()
    {
        return view('bendahara.index');
    }
}

