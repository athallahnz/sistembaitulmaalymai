@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-2">
            Daftar Murid
        </h1>

        <form method="POST" action="{{ route('payment.store') }}">
            @csrf
            <label>Tempelkan Kartu RFID</label>
            <input type="text" id="rfid_uid_input" name="rfid_uid" required autofocus>

            <div id="student-info" style="margin-top: 20px;"></div>

            <input type="number" name="jumlah" placeholder="Jumlah Bayar" required>
            <button type="submit">Bayar</button>
        </form>
    </div>
    <script>
        document.getElementById('rfid_uid_input').addEventListener('input', function() {
            const uid = this.value;

            fetch(`/api/student-by-rfid/${uid}`)
                .then(res => res.json())
                .then(data => {
                    if (data) {
                        document.getElementById('student-info').innerHTML = `
                        <p><strong>Nama:</strong> ${data.name}</p>
                        <p><strong>Kelas:</strong> ${data.kelas}</p>
                        <p><strong>Total Biaya:</strong> Rp ${data.total_biaya}</p>
                        <p><strong>Sisa Tanggungan:</strong> Rp ${data.sisa}</p>
                        <input type="hidden" name="student_id" value="${data.id}">
                    `;
                    } else {
                        document.getElementById('student-info').innerHTML =
                            `<p style="color:red;">Siswa tidak ditemukan!</p>`;
                    }
                });
        });
    </script>
@endsection
