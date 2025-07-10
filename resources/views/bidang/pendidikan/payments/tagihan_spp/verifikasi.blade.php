<h2>Verifikasi Kwitansi</h2>
<p><strong>Nama:</strong> {{ $tagihan->student->nama }}</p>
<p><strong>Jumlah:</strong> Rp {{ number_format($tagihan->jumlah, 0, ',', '.') }}</p>
<p><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($tagihan->tanggal)->format('d-m-Y') }}</p>
<p><strong>Status:</strong> Valid ✅</p>
