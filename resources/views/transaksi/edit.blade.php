@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Transaksi</h1>
        <form action="{{ route('transaksi.update', $transaksi->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="bidang_name" class="form-label">Bidang</label>
                <input type="text" name="bidang_name" class="form-control" value="{{ $transaksi->bidang_name }}" readonly>
            </div>

            <div class="mb-3">
                <label for="kode_transaksi" class="form-label">Kode Transaksi</label>
                <input type="text" name="kode_transaksi" class="form-control" value="{{ $transaksi->kode_transaksi }}"
                    readonly>
            </div>

            <div class="mb-3">
                <label for="tanggal_transaksi" class="form-label">Tanggal Transaksi</label>
                <input type="date" name="tanggal_transaksi" class="form-control"
                    value="{{ $transaksi->tanggal_transaksi }}" required>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Tipe Transaksi</label>
                <select name="type" class="form-control" required>
                    <option value="penerimaan" {{ $transaksi->type == 'penerimaan' ? 'selected' : '' }}>Penerimaan</option>
                    <option value="pengeluaran" {{ $transaksi->type == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label mb-2" id="akun-label">Asal Akun</label> <!-- Label dinamis -->
                <select class="form-control" name="akun_keuangan_id" id="akun_keuangan" required>
                    @foreach ($akunTanpaParent as $akun)
                        <option value="{{ $akun->id }}" data-saldo-normal="{{ $akun->saldo_normal }}">
                            {{ $akun->nama_akun }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3" id="parent-akun-container" style="display: none;">
                <label class="mb-2">Akun Parent</label>
                <select class="form-control" name="parent_akun_id" id="parent_akun_id">
                    <option value="">Pilih Akun Parent</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <input type="text" name="deskripsi" class="form-control" value="{{ $transaksi->deskripsi }}" required>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Jumlah</label>
                <input type="number" name="amount" class="form-control" value="{{ $transaksi->amount }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const typeSelect = document.getElementById("type-select");
            const akunLabel = document.getElementById("akun-label");

            // Fungsi untuk mengubah label berdasarkan tipe transaksi
            function updateAkunLabel() {
                const selectedType = typeSelect.value;
                if (selectedType === "penerimaan") {
                    akunLabel.textContent = "Asal Akun";
                } else if (selectedType === "pengeluaran") {
                    akunLabel.textContent = "Tujuan Akun";
                }
            }

            // Event listener untuk mendeteksi perubahan tipe transaksi
            typeSelect.addEventListener("change", updateAkunLabel);

            // Set label awal sesuai nilai default
            updateAkunLabel();
        });

        document.addEventListener("DOMContentLoaded", function() {
            let akunKeuangan = document.getElementById("akun_keuangan");
            let parentAkunContainer = document.getElementById("parent-akun-container");
            let parentAkunSelect = document.getElementById("parent_akun_id");

            let akunDenganParent = @json($akunDenganParent);

            akunKeuangan.addEventListener("change", function() {
                let selectedAkunId = this.value;
                parentAkunSelect.innerHTML = '<option value="">Pilih Akun Parent</option>';

                if (selectedAkunId && akunDenganParent[selectedAkunId]) {
                    akunDenganParent[selectedAkunId].forEach(akun => {
                        let newOption = document.createElement("option");
                        newOption.value = akun.id;
                        newOption.textContent = akun.nama_akun;
                        parentAkunSelect.appendChild(newOption);
                    });
                    parentAkunContainer.style.display = "block";
                } else {
                    parentAkunContainer.style.display = "none";
                }

                // Menyesuaikan nilai debit atau kredit berdasarkan saldo_normal
                updateFormByAkun(akunKeuangan);
            });
        });
    </script>
@endsection
