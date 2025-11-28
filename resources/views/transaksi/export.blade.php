<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Buku Harian Kas & Bank</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        h2,
        h3 {
            margin: 0 0 8px 0;
            padding: 0;
        }

        .subtitle {
            font-size: 10px;
            color: #555;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        $bidangLabel = $user->bidang->name ?? 'Semua Bidang';
    @endphp

    {{-- ===========================
    BAGIAN 1: BUKU HARIAN KAS
    ============================ --}}
    <h2>BUKU HARIAN KAS</h2>
    <div class="subtitle">
        Bidang: {{ $bidangLabel }} <br>
        Dicetak pada: {{ now()->format('d-m-Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis Transaksi</th>
                <th>Sumber Akun</th>
                <th>Tujuan Akun</th>
                <th>Deskripsi</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
                <th class="text-right">Saldo Akun</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse ($kasTransaksis as $t)
                @php
                    $tanggal = Carbon::parse($t->tanggal_transaksi);
                    $akun = $t->akunKeuangan;
                    $parent = $t->parentAkunKeuangan;

                    // LOGIKA DEBIT/KREDIT khusus buku KAS/BANK:
                    // - type 'penerimaan'  => DEBIT kas/bank
                    // - type 'pengeluaran' => KREDIT kas/bank
                    if ($t->type === 'penerimaan') {
                        $debit = $t->amount;
                        $kredit = 0;
                    } elseif ($t->type === 'pengeluaran') {
                        $debit = 0;
                        $kredit = $t->amount;
                    } else {
                        // kalau ada tipe lain (transfer-masuk/keluar dll)
                        $debit = 0;
                        $kredit = 0;
                    }
                @endphp

                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $tanggal->format('Y-m-d') }}</td>
                    <td>
                        @if ($t->type === 'penerimaan')
                            <span style="background-color: #d4edda; color: #155724; padding: 2px 4px; border-radius: 4px; font-weight: bold;">Penerimaan</span>
                        @elseif ($t->type === 'pengeluaran')
                            <span style="background-color: #f8d7da; color: #721c24; padding: 2px 4px; border-radius: 4px; font-weight: bold;">Pengeluaran</span>
                        @else
                            <span style="background-color: #e2e3e5; color: #383d41; padding: 2px 4px; border-radius: 4px; font-weight: bold;">{{ $t->type }}</span>
                        @endif
                    </td>
                    <td>{{ $akun->nama_akun ?? '-' }}</td>
                    <td>{{ $parent->nama_akun ?? '-' }}</td>
                    <td>{{ $t->deskripsi }}</td>
                    <td class="text-right">{{ number_format($debit, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($kredit, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($t->saldo ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">Tidak ada transaksi kas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===========================
    BAGIAN 2: BUKU HARIAN BANK
    ============================ --}}
    <div class="page-break"></div>

    <h2>BUKU HARIAN BANK</h2>
    <div class="subtitle">
        Bidang: {{ $bidangLabel }} <br>
        Dicetak pada: {{ now()->format('d-m-Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis Transaksi</th>
                <th>Sumber Akun</th>
                <th>Tujuan Akun</th>
                <th>Deskripsi</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
                <th class="text-right">Saldo Akun</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse ($bankTransaksis as $t)
                @php
                    $tanggal = Carbon::parse($t->tanggal_transaksi);
                    $akun = $t->akunKeuangan;
                    $parent = $t->parentAkunKeuangan;

                    // LOGIKA DEBIT/KREDIT khusus buku KAS/BANK:
                    // - type 'penerimaan'  => DEBIT kas/bank
                    // - type 'pengeluaran' => KREDIT kas/bank
                    if ($t->type === 'penerimaan') {
                        $debit = $t->amount;
                        $kredit = 0;
                    } elseif ($t->type === 'pengeluaran') {
                        $debit = 0;
                        $kredit = $t->amount;
                    } else {
                        // kalau ada tipe lain (transfer-masuk/keluar dll)
                        $debit = 0;
                        $kredit = 0;
                    }
                @endphp

                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $tanggal->format('Y-m-d') }}</td>
                    <td>
                        @if ($t->type === 'penerimaan')
                            <span style="background-color: #d4edda; color: #155724; padding: 2px 4px; border-radius: 4px; font-weight: bold;">Penerimaan</span>
                        @elseif ($t->type === 'pengeluaran')
                            <span style="background-color: #f8d7da; color: #721c24; padding: 2px 4px; border-radius: 4px; font-weight: bold;">Pengeluaran</span>
                        @else
                            <span style="background-color: #e2e3e5; color: #383d41; padding: 2px 4px; border-radius: 4px; font-weight: bold;">{{ $t->type }}</span>
                        @endif
                    </td>
                    <td>{{ $akun->nama_akun ?? '-' }}</td>
                    <td>{{ $parent->nama_akun ?? '-' }}</td>
                    <td>{{ $t->deskripsi }}</td>
                    <td class="text-right">{{ number_format($debit, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($kredit, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($t->saldo ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">Tidak ada transaksi bank.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
