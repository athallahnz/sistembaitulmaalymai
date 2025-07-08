<div class="row">
    <div class="col-md-4">
        <h4 class="mb-3">Data Diri Murid</h4>

        @isset($eduClasses)
            <div class="mb-3">
                <label>Kelas <span class="text-danger">*</span></label>
                <select name="edu_class_id" class="form-select" required>
                    <option value="">Pilih Kelas</option>
                    @foreach ($eduClasses as $class)
                        <option value="{{ $class->id }}"
                            {{ old('edu_class_id', $student->edu_class_id ?? '') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }} - {{ $class->tahun_ajaran }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endisset

        <div class="row mb-3">
            <div class="col-md-8">
                <label>NISN</label>
                <input type="text" name="nisn" class="form-control"
                    value="{{ old('nisn', $student->nisn ?? '') }}" placeholder="Masukkan NISN Murid..">
            </div>
            <div class="col-md-4">
                <label>No. Induk <span class="text-danger">*</span></label>
                <input type="text" name="no_induk" class="form-control"
                    value="{{ old('no_induk', $student->no_induk ?? '') }}" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                    value="{{ old('name', $student->name ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label>Nama Panggilan <span class="text-danger">*</span></label>
                <input type="text" name="nickname" class="form-control"
                    value="{{ old('nickname', $student->nickname ?? '') }}" required>
            </div>
        </div>

        <div class="mb-3">
            <label>Jenis Kelamin <span class="text-danger">*</span></label>
            <select name="jenis_kelamin" class="form-select" required>
                <option value="L"
                    {{ old('jenis_kelamin', $student->jenis_kelamin ?? '') == 'L' ? 'selected' : '' }}>Laki-laki
                </option>
                <option value="P"
                    {{ old('jenis_kelamin', $student->jenis_kelamin ?? '') == 'P' ? 'selected' : '' }}>Perempuan
                </option>
            </select>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Tempat Lahir</label>
                <input type="text" name="tempat_lahir" class="form-control"
                    value="{{ old('tempat_lahir', $student->tempat_lahir ?? '') }}">
            </div>
            <div class="col-md-6">
                <label>Tanggal Lahir</label>
                <input type="date" name="ttl" class="form-control"
                    value="{{ old('ttl', isset($student->ttl) ? \Carbon\Carbon::parse($student->ttl)->format('Y-m-d') : '') }}">
            </div>
        </div>

        <div class="mb-3">
            <label>Usia</label>
            <input type="text" name="usia" class="form-control" value="{{ old('usia', $student->usia ?? '') }}"
                readonly>
        </div>

        <div class="mb-3"><label>NIK</label><input type="text" name="nik" class="form-control"
                value="{{ old('nik', $student->nik ?? '') }}"></div>
        <div class="mb-3"><label>No. Akta</label><input type="text" name="no_akte" class="form-control"
                value="{{ old('no_akte', $student->no_akte ?? '') }}"></div>
        <div class="mb-3"><label>No. KK</label><input type="text" name="no_kk" class="form-control"
                value="{{ old('no_kk', $student->no_kk ?? '') }}"></div>

        <div class="mb-3">
            <label>Alamat KK (Utama)</label>
            <textarea name="alamat_kk" class="form-control">{{ old('alamat_kk', $student->alamat_kk ?? '') }}</textarea>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="copyAlamatTinggal">
            <label class="form-check-label" for="copyAlamatTinggal">Gunakan Alamat Utama sbg Alamat Tinggal</label>
        </div>
        <div class="mb-3">
            <label>Alamat Tinggal</label>
            <textarea name="alamat_tinggal" class="form-control">{{ old('alamat_tinggal', $student->alamat_tinggal ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label>Pas Photo</label>
            @if (isset($student->pas_photo))
                <a href="{{ asset('storage/' . $student->pas_photo) }}" target="_blank">Lihat</a><br>
            @endif
            <input type="file" name="pas_photo" class="form-control">
        </div>
        <div class="mb-3">
            <label>Upload Akta</label>
            @if (isset($student->akte))
                <a href="{{ asset('storage/' . $student->akte) }}" target="_blank">Lihat</a><br>
            @endif
            <input type="file" name="akte" class="form-control">
        </div>
        <div class="mb-3">
            <label>Upload KK</label>
            @if (isset($student->kk))
                <a href="{{ asset('storage/' . $student->kk) }}" target="_blank">Lihat</a><br>
            @endif
            <input type="file" name="kk" class="form-control">
        </div>
    </div>

    <div class="col-md-4">
        <h4 class="mb-3">Data Wali Murid</h4>

        <div class="accordion" id="waliAccordion">
            @foreach (['Ayah' => 'L', 'Ibu' => 'P'] as $hubungan => $jk)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $hubungan }}">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapse{{ $hubungan }}">
                            Data {{ $hubungan }}
                        </button>
                    </h2>
                    <div id="collapse{{ $hubungan }}"
                        class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}">
                        <div class="accordion-body">
                            @include('bidang.pendidikan.wali_murids.partials.form_wali_murid', [
                                'hubungan' => $hubungan,
                                'jenis_kelamin' => $jk,
                                'loopIndex' => $loop->index,
                                'student' => $student ?? null,
                                'wali' => $waliPrefill[$hubungan] ?? null,
                            ])
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Kolom 3: Rincian Biaya & RFID --}}
    <div class="col-md-4">
        <h4 class="mb-3">Rincian Biaya <span class="text-danger">*</span></h4>

        {{-- Template hidden untuk akun --}}
        <select class="akun-template" hidden>
            @foreach ($akunKeuangans as $akun)
                <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
            @endforeach
        </select>

        <table class="table table-bordered" id="costTable">
            <thead>
                <tr>
                    <th>Pos Biaya</th>
                    <th>Jumlah Nominal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($student->costs as $i => $cost)
                    <tr>
                        <td>
                            <select name="biaya[akun_id][]" class="form-select" required>
                                <option value="">Pilih Akun</option>
                                @foreach ($akunKeuangans as $akun)
                                    <option value="{{ $akun->id }}"
                                        {{ $akun->id == $cost->akun_keuangan_id ? 'selected' : '' }}>
                                        {{ $akun->nama_akun }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="biaya[nominal][]" class="form-control nominal-input"
                                value="{{ $cost->jumlah }}" required>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-row">Hapus</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" id="addRow" class="btn btn-sm btn-outline-primary mb-3">+ Tambah Biaya</button>

        <div class="mb-3">
            <label>Total Biaya</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" id="total_display" class="form-control" readonly>
            </div>
            <input type="hidden" name="total_biaya" id="total_biaya">
        </div>

        <hr>
        <h6 class="mb-3">Kartu RFID <span class="text-danger">*</span></h6>
        <div class="mb-3">
            <input type="text" name="rfid_uid" class="form-control" placeholder="Tempelkan Kartu..."
                value="{{ old('rfid_uid', $student->rfid_uid) }}" required>
            @error('rfid_uid')
                <div class="text-danger mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#costTable tbody');
            const totalDisplay = document.querySelector('#total_display');
            const totalInput = document.querySelector('#total_biaya');
            const akunTemplate = document.querySelector('.akun-template');

            function formatRupiah(angka) {
                return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            function calculateTotal() {
                let total = 0;
                document.querySelectorAll('.nominal-input').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                totalDisplay.value = formatRupiah(total);
                totalInput.value = total;
            }

            document.querySelector('#addRow').addEventListener('click', function() {
                const akunSelect = akunTemplate.cloneNode(true);
                akunSelect.hidden = false;
                akunSelect.name = 'biaya[akun_id][]';
                akunSelect.classList.remove('akun-template');
                akunSelect.classList.add('form-select');

                const row = document.createElement('tr');
                row.innerHTML = `
            <td></td>
            <td><input type="number" name="biaya[nominal][]" class="form-control nominal-input" required></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row">Hapus</button></td>
        `;

                row.children[0].appendChild(akunSelect);
                tableBody.appendChild(row);
            });

            tableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-row')) {
                    e.target.closest('tr').remove();
                    calculateTotal();
                }
            });

            tableBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('nominal-input')) {
                    calculateTotal();
                }
            });

            // Hitung total saat load pertama
            calculateTotal();
        });
    </script>
@endpush
