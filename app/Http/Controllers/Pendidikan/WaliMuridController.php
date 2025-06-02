<?php

namespace App\Http\Controller\Pendidikan;

use App\Http\Controllers\Controller;
use App\Models\WaliMurid;

class WaliMuridController extends Controller
{
    public function index()
    {
        $waliMurids = WaliMurid::latest()->get();
        return view('wali_murids.index', compact('waliMurids'));
    }

    public function show(WaliMurid $waliMurid)
    {
        return view('wali_murids.show', compact('waliMurid'));
    }
}
