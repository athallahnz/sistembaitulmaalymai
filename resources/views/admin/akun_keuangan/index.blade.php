@extends('layouts.app')
@section('title', 'Manajemen Akun Keuangan')

@section('content')
    <style>
        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-10px);
            }

            40% {
                transform: translateX(10px);
            }

            60% {
                transform: translateX(-10px);
            }

            80% {
                transform: translateX(10px);
            }

            100% {
                transform: translateX(0);
            }
        }
    </style>
    <div class="container">
        <h2 class="mb-4">Daftar Akun Keuangan</h2>

        <!-- Button untuk membuka modal Create -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#akunModal">
            Tambah Akun Keuangan
        </button>

        <!-- Modal -->
        <div class="modal fade" id="akunModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="akunModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="akunModalLabel">Silahkan Isi Data Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Form Create -->
                        <form id="akunForm" action="{{ route('admin.akun_keuangan.store') }}" method="POST">
                            @csrf
                            <input type="hidden" id="formMethod" name="_method" value="POST">

                            {{-- ID Akun --}}
                            <div class="mb-3">
                                <label class="mb-2">ID Akun</label>
                                <input type="number" id="id" name="id" class="form-control"
                                    placeholder="Masukkan ID Akun" required>
                            </div>

                            {{-- Nama Akun --}}
                            <div class="mb-3">
                                <label class="mb-2">Nama Akun</label>
                                <input type="text" id="nama_akun" name="nama_akun" class="form-control"
                                    placeholder="Masukkan Nama Akun" required>
                            </div>

                            {{-- Tipe Akun --}}
                            <div class="mb-3">
                                <label class="mb-2">Tipe Akun</label>
                                <select class="form-control" id="tipe_akun" name="tipe_akun" required>
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="revenue">Revenue</option>
                                    <option value="expense">Expense</option>
                                    <option value="equity">Equity</option>
                                </select>
                            </div>

                            {{-- Kategori PSAK --}}
                            <div class="mb-3">
                                <label class="mb-2">Kategori PSAK (Opsional)</label>
                                <select class="form-control" id="kategori_psak" name="kategori_psak">
                                    <option value="">- Tidak Ada -</option>
                                    <option value="aset_lancar">Aset Lancar</option>
                                    <option value="aset_tidak_lancar">Aset Tidak Lancar</option>
                                    <option value="liabilitas_jangka_pendek">Liabilitas Jangka Pendek</option>
                                    <option value="liabilitas_jangka_panjang">Liabilitas Jangka Panjang</option>
                                    <option value="aset_neto_tidak_terikat">Aset Neto Tidak Terikat</option>
                                    <option value="aset_neto_terikat_temporer">Aset Neto Terikat Temporer</option>
                                    <option value="aset_neto_terikat_permanen">Aset Neto Terikat Permanen</option>
                                    <option value="pendapatan">Pendapatan</option>
                                    <option value="beban">Beban</option>
                                </select>
                            </div>

                            {{-- Pembatasan --}}
                            <div class="mb-3">
                                <label class="mb-2">Pembatasan (Opsional)</label>
                                <select class="form-control" id="pembatasan" name="pembatasan">
                                    <option value="">- Tidak Ada -</option>
                                    <option value="tidak_terikat">Tidak Terikat</option>
                                    <option value="terikat_temporer">Terikat Temporer</option>
                                    <option value="terikat_permanen">Terikat Permanen</option>
                                </select>
                            </div>

                            {{-- Kode Akun --}}
                            <div class="mb-3">
                                <label class="mb-2">Kode Akun</label>
                                <input type="text" name="kode_akun" id="kode_akun" class="form-control"
                                    placeholder="Masukkan Kode Akun" required>
                            </div>

                            {{-- Saldo Normal --}}
                            <div class="mb-3">
                                <label class="mb-2">Saldo Normal</label>
                                <select class="form-control" id="saldo_normal" name="saldo_normal" required>
                                    <option value="debit">Debit</option>
                                    <option value="kredit">Kredit</option>
                                </select>
                            </div>

                            {{-- Kas / Bank --}}
                            <div class="mb-3">
                                <label class="mb-2 d-block">Kas / Bank?</label>

                                <!-- agar current state unchecked tetap mengirim 0 -->
                                <input type="hidden" name="is_kas_bank" value="0">

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_kas_bank" name="is_kas_bank"
                                        value="1">
                                    <label class="form-check-label" for="is_kas_bank">
                                        Centang jika akun ini termasuk Kas/Bank
                                    </label>
                                </div>
                            </div>

                            {{-- Kategori Arus Kas --}}
                            <div class="mb-3">
                                <label class="mb-2">Kategori Arus Kas (Opsional)</label>
                                <select class="form-control" id="cashflow_category" name="cashflow_category">
                                    <option value="">- Tidak Ada -</option>
                                    <option value="operasional">Operasional</option>
                                    <option value="investasi">Investasi</option>
                                    <option value="pendanaan">Pendanaan</option>
                                </select>
                            </div>

                            {{-- Induk Akun --}}
                            <div class="mb-3">
                                <label class="mb-2">Induk Akun (Opsional)</label>
                                <select class="form-control" id="parent_id" name="parent_id">
                                    <option value="">- Tidak Ada Induk -</option>
                                    @isset($akunKeuanganTanpaParent)
                                        @foreach ($akunKeuanganTanpaParent as $akun)
                                            <option value="{{ $akun->id }}">
                                                {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3 shadow table-responsive rounded">
            <table class="table table-striped table-bordered" id="akunTable">
                <thead class="table-light">
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th>Tipe Akun</th>
                        <th>Induk Akun</th>
                        <th>Saldo Normal</th>
                        <th>Kas / Bank</th>
                        <th>Kategori Arus Kas</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#akunTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.akun_keuangan.datatable') }}",
                columns: [{
                        data: 'kode_akun',
                        name: 'kode_akun' // kolom DB: kode_akun
                    },
                    {
                        data: 'nama_akun',
                        name: 'nama_akun' // kolom DB: nama_akun
                    },
                    {
                        data: 'tipe_akun',
                        name: 'tipe_akun' // kolom DB: tipe_akun
                    },
                    {
                        data: 'parent',
                        name: 'parent', // BUKAN kolom DB
                        orderable: false,
                        searchable: false // ⬅️ WAJIB: jangan ikut search
                    },
                    {
                        data: 'saldo_normal',
                        name: 'saldo_normal' // kolom DB: saldo_normal
                    },
                    {
                        data: 'kas_bank',
                        name: 'is_kas_bank', // kolom DB sebenarnya: is_kas_bank
                        orderable: false,
                        searchable: false // badge HTML saja, nggak usah di-search
                    },
                    {
                        data: 'cashflow',
                        name: 'cashflow_category', // kolom DB sebenarnya
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi', // bukan kolom DB
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ]
            });
        });

        function confirmDelete(event, akunId) {
            event.preventDefault();

            Swal.fire({
                title: 'Yakin ingin menghapus akun ini?',
                text: "Data akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + akunId).submit();
                }
            });

            return false;
        }
    </script>

    {{-- SweetAlert dari Session --}}
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '{{ session('error') }}'
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                html: `{!! implode('<br>', $errors->all()) !!}`
            });
        </script>
    @endif
@endpush
