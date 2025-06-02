@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Detail Wali Murid</h4>
    <ul class="list-group">
        <li class="list-group-item"><strong>Nama:</strong> {{ $waliMurid->nama }}</li>
        <li class="list-group-item"><strong>NIK:</strong> {{ $waliMurid->nik }}</li>
        <li class="list-group-item"><strong>Jenis Kelamin:</strong> {{ $waliMurid->jenis_kelamin }}</li>
        <li class="list-group-item"><strong>Hubungan:</strong> {{ $waliMurid->hubungan }}</li>
        <li class="list-group-item"><strong>No HP:</strong> {{ $waliMurid->no_hp }}</li>
        <li class="list-group-item"><strong>Alamat:</strong> {{ $waliMurid->alamat }}</li>
        <li class="list-group-item">
            <strong>Foto KTP:</strong><br>
            @if($waliMurid->foto_ktp)
                <img src="{{ asset('storage/' . $waliMurid->foto_ktp) }}" width="200" class="img-thumbnail mt-2">
            @else
                <em>Tidak ada</em>
            @endif
        </li>
    </ul>
    <a href="{{ route('wali-murids.index') }}" class="btn btn-secondary mt-3">Kembali</a>
</div>
@endsection
