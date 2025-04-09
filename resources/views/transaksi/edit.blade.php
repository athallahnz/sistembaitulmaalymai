@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Transaksi</h1>
        @php
            // Pastikan role user tersedia
            $userRole = auth()->user()->role ?? null;
            $bidangName = old('bidang_name', $transaksi->bidang_name);

            // Mapping akun sesuai role dan bidang
            if ($userRole === 'Bendahara') {
                $akun_keuangan_id = 1011; // Akun khusus Bendahara
            } else {
                $akunKas = [
                    1 => 1012, // Kemasjidan
                    2 => 1013, // Pendidikan
                    3 => 1014, // Sosial
                    4 => 1015, // Usaha
                ];
                $akun_keuangan_id = $akunKas[$bidangName] ?? $transaksi->akun_keuangan_id;
            }

            // Tentukan route berdasarkan akun keuangan
            $routeName = in_array($akun_keuangan_id, [1011, 1012, 1013, 1014, 1015])
                ? 'transaksi.update'
                : 'transaksi.updateBankTransaction';

            // Ambil nilai parent akun dari database
            $selectedParentAkun = $transaksi->parent_akun_id;
            $parentAkunNama = $transaksi->parentAkun->nama_akun ?? 'Tidak ada data';
        @endphp

        <form action="{{ route($routeName, $transaksi->id) }}" method="POST">
            @csrf
            @method('PUT')

            <input type="hidden" name="bidang_name" value="{{ $bidangName }}">
            <input type="hidden" name="akun_keuangan_id" value="{{ $akun_keuangan_id }}">
            <input type="hidden" name="parent_akun_id" value="{{ $selectedParentAkun }}">

            <div class="mb-3">
                <label for="kode_transaksi" class="form-label">Kode Transaksi</label>
                <input type="text" name="kode_transaksi" class="form-control"
                    value="{{ old('kode_transaksi', $transaksi->kode_transaksi) }}" readonly>
            </div>

            <div class="mb-3">
                <label for="tanggal_transaksi" class="form-label">Tanggal Transaksi</label>
                <input type="date" name="tanggal_transaksi" class="form-control"
                    value="{{ old('tanggal_transaksi', $transaksi->tanggal_transaksi) }}" required>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Tipe Transaksi</label>
                <select name="type" class="form-control" required>
                    <option value="penerimaan" {{ old('type', $transaksi->type) == 'penerimaan' ? 'selected' : '' }}>
                        Penerimaan</option>
                    <option value="pengeluaran" {{ old('type', $transaksi->type) == 'pengeluaran' ? 'selected' : '' }}>
                        Pengeluaran</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label mb-2">Asal Akun</label>
                <select class="form-control" name="akun_keuangan_id" id="akun_keuangan" required>
                    <option value="">Pilih Akun</option>
                    @foreach ($akunTanpaParent as $akun)
                        <option value="{{ $akun->id }}"
                            {{ old('akun_keuangan_id', $akunKeuangan) == $akun->id ? 'selected' : '' }}>
                            {{ $akun->nama_akun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3" id="parent-akun-container">
                <label class="mb-2">Akun Parent</label>
                <select class="form-control" name="parent_akun_id" id="parent_akun_id">
                    <option value="">Pilih Akun Parent</option>
                    @foreach ($akunDenganParent as $parent_id => $akuns)
                        @foreach ($akuns as $akun)
                            <option value="{{ $akun->id }}"
                                {{ old('parent_akun_id', $transaksi->parent_akun_id ?? '') == $akun->id ? 'selected' : '' }}>
                                {{ $akun->nama_akun }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <input type="text" name="deskripsi" class="form-control"
                    value="{{ old('deskripsi', $transaksi->deskripsi) }}" required>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Jumlah</label>
                <input type="number" name="amount" class="form-control" value="{{ old('amount', $transaksi->amount) }}"
                    required>
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
            let parentAkunSelect = document.getElementById("parent_akun_id");

            // Data akun parent dari backend (Laravel JSON)
            let akunDenganParent = @json($akunDenganParent);
            let oldParentAkunId = "{{ old('parent_akun_id', $transaksi->parent_akun_id ?? '') }}";

            // **Memastikan Akun Parent Ditampilkan Sejak Awal**
            function loadAllParentAkunOptions() {
                parentAkunSelect.innerHTML = '<option value="">Pilih Akun Parent</option>';

                for (let parentId in akunDenganParent) {
                    akunDenganParent[parentId].forEach(akun => {
                        let newOption = document.createElement("option");
                        newOption.value = akun.id;
                        newOption.textContent = akun.nama_akun;

                        if (akun.id == oldParentAkunId) {
                            newOption.selected = true;
                        }

                        parentAkunSelect.appendChild(newOption);
                    });
                }
            }

            // **Fungsi untuk memperbarui daftar akun parent berdasarkan pilihan akun keuangan**
            function updateParentAkunOptions(selectedAkunId) {
                parentAkunSelect.innerHTML = '<option value="">Pilih Akun Parent</option>';

                if (selectedAkunId && akunDenganParent[selectedAkunId]) {
                    akunDenganParent[selectedAkunId].forEach(akun => {
                        let newOption = document.createElement("option");
                        newOption.value = akun.id;
                        newOption.textContent = akun.nama_akun;

                        if (akun.id == oldParentAkunId) {
                            newOption.selected = true;
                        }

                        parentAkunSelect.appendChild(newOption);
                    });
                } else {
                    loadAllParentAkunOptions();
                }
            }

            // Saat akun keuangan berubah, update daftar akun parent
            akunKeuangan.addEventListener("change", function() {
                updateParentAkunOptions(this.value);
            });

            // **Menampilkan semua akun parent sejak awal**
            loadAllParentAkunOptions();
        });
    </script>
@endsection
