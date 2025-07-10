<h2>Verifikasi Kwitansi</h2>
<p><strong>Nama:</strong> {{ $payment->student->nama }}</p>
<p><strong>Jumlah:</strong> Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</p>
<p><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($payment->tanggal)->format('d-m-Y') }}</p>
<p><strong>Status:</strong> Valid ✅</p>
