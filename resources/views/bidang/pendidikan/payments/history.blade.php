@extends('layouts.app')

@section('content')
    <div class="container">
        <h3>Riwayat Pembayaran - {{ $student->name }}</h3>

        <div class="mb-4">
            <p><strong>Kelas:</strong> {{ $student->edu_class }}</p>
            <p><strong>Total Biaya:</strong> Rp {{ number_format($totalBiaya, 0, ',', '.') }}</p>
            <p><strong>Total Dibayar:</strong> Rp {{ number_format($totalBayar, 0, ',', '.') }}</p>
            <p><strong>Sisa Tanggungan:</strong> Rp {{ number_format($sisa, 0, ',', '.') }}</p>
        </div>

        <h5>Detail Pembayaran</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($student->pembayaran as $index => $bayar)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($bayar->tanggal)->format('d-m-Y') }}</td>
                        <td>Rp {{ number_format($bayar->jumlah, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Belum ada pembayaran</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
