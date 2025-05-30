@extends('layouts.app')

@section('content')
<div class="container">
        <h1 class="mb-2">
            Daftar Murid
        </h1>
    @if (session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('students.store') }}">
        @csrf

        <label>Nama:</label>
        <input type="text" name="name" required><br>

        <label>Kelas:</label>
        <input type="text" name="kelas" required><br>

        <label>Total Biaya:</label>
        <input type="number" name="total_biaya" required><br>

        <label>Tempelkan Kartu RFID:</label>
        <input type="text" name="rfid_uid" id="rfid_uid_input" required autofocus><br>

        <button type="submit">Daftarkan</button>
    </form>
</div>
    <script>
        // Jika menggunakan USB RFID Reader yang mengetik otomatis ke input
        document.getElementById('rfid_uid_input').focus();
    </script>
@endsection
