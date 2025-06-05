@extends('layouts.app')

@section('content')
    <div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 50vh;">
        <h1 class="mb-4 text-center">Check Pembayaran Murid</h1>

        <form method="POST" action="{{ route('payment.store') }}" class="w-50 p-4 border rounded shadow-sm bg-light">
            @csrf

            <div class="mb-3">
                <label for="rfid_uid_input" class="form-label">Tempelkan ID Card Murid</label>
                <input type="text" id="rfid_uid_input" name="rfid_uid" class="form-control text-center"
                    placeholder="Tempelkan Kartu RFID..." required autofocus>
            </div>

            <!-- Card loading dan hasil siswa -->
            <div id="student-card" class="mt-4 d-none">
                <div id="student-card-body">
                    <!-- Konten siswa akan dimasukkan lewat JS -->
                </div>
            </div>

            <div class="mb-3 mt-4">
                <label for="jumlah" class="form-label">Jumlah Bayar</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" placeholder="Masukkan jumlah"
                    required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Bayar</button>
            </div>
        </form>
    </div>

    <script>
        const rfidInput = document.getElementById('rfid_uid_input');
        const studentCard = document.getElementById('student-card');
        const studentCardBody = document.getElementById('student-card-body');

        rfidInput.addEventListener('input', function() {
            const uid = this.value.trim();

            if (!uid) {
                studentCard.classList.add('d-none');
                studentCardBody.innerHTML = '';
                return;
            }

            studentCard.classList.remove('d-none');
            studentCardBody.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border text-center text-primary me-2" role="status" style="width: 1.5rem; height: 1.5rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-muted">Sedang mencari data siswa...</span>
                </div>
            `;

            clearTimeout(window.fetchTimeout);
            window.fetchTimeout = setTimeout(() => {
                fetch(`/api/student-by-rfid/${uid}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.name) {
                            studentCardBody.innerHTML = `
                <div class="mb-3">
                    <label for="student_name" class="form-label">Nama</label>
                    <input type="text" id="student_name" class="form-control" value="${data.name}" readonly>
                </div>
                <div class="mb-3">
                    <label for="student_class" class="form-label">Kelas</label>
                    <input type="text" id="student_class" class="form-control" value="${data.edu_class} (${data.tahun_ajaran})" readonly>
                </div>
                <div class="mb-3">
                    <label for="total_biaya" class="form-label">Total Biaya</label>
                    <input type="text" id="total_biaya" class="form-control" value="Rp ${Number(data.total_biaya).toLocaleString()}" readonly>
                </div>
                <div class="mb-3">
                    <label for="sisa_tanggungan" class="form-label">Sisa Tanggungan</label>
                    <input type="text" id="sisa_tanggungan" class="form-control" value="Rp ${Number(data.sisa).toLocaleString()}" readonly>
                </div>
                <input type="hidden" name="student_id" value="${data.id}">
            `;
                        } else {
                            setTimeout(() => {
                                if (rfidInput.value.trim() === uid) {
                                    studentCardBody.innerHTML =
                                        `<p class="text-danger mb-0">❌ Siswa tidak ditemukan!</p>`;
                                }
                            }, 1000);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        studentCardBody.innerHTML =
                            `<p class="text-danger">⚠️ Terjadi kesalahan saat mengambil data.</p>`;
                    });
            }, 300);
        });
    </script>
@endsection
