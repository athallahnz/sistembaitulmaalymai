@php
    $setting = \App\Models\SidebarSetting::first();
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kwitansi Transaksi - {{ $transaksi->kode_transaksi ?? '' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        @php $bgColor =$setting->background_color ?? '#222e3c';
        $cardBg        = '#ffffff';
        // tetap pakai accent dari setting, tapi header utama nanti dibuat netral (abu)
        $accentBg =$setting->cta_button_color ?? '#81431E';
        $accentBgHover =$setting->cta_button_hover_color ?? '#984F23';
        $accentText =$setting->cta_button_text_color ?? '#fff5e1';
        $borderAccent =$setting->link_active_border_color ?? '#f2c89d';
        $mutedText     = '#6c757d';
        @endphp

        @page {
            size: A5 portrait;
            margin: 12mm 13mm 12mm 13mm;
            /* atas kanan bawah kiri */
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .invoice-wrapper {
            width: 100%;
            background-color: {{ $cardBg }};
            padding: 0;
            /* padding dihapus, biar margin dari @page yang bekerja */
        }

        * {
            box-sizing: border-box;
        }

        .invoice-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid {{ $borderAccent }};
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .invoice-header-left,
        .invoice-header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .invoice-header-left {
            width: 70px;
        }

        .logo {
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }

        .invoice-header-right {
            padding-left: 10px;
        }

        .app-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            color: #343a40;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .app-subtitle {
            font-size: 11px;
            margin: 2px 0 3px;
            color: {{ $mutedText }};
        }

        .org-meta {
            font-size: 9.5px;
            color: {{ $mutedText }};
            line-height: 1.3;
        }

        .invoice-title-bar {
            margin: 8px 0 12px;
            padding: 6px 8px;
            border-radius: 4px;
            background-color: #f5f5f5;
            /* netral abu */
            border-left: 4px solid {{ $borderAccent }};
        }

        .invoice-title-bar h1 {
            margin: 0;
            font-size: 14px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #333333;
            text-align: left;
        }

        .invoice-title-bar p {
            margin: 2px 0 0;
            font-size: 10px;
            color: {{ $mutedText }};
        }

        .invoice-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10.5px;
        }

        .invoice-meta td {
            padding: 2px 0;
        }

        .invoice-meta .label {
            width: 110px;
            color: {{ $mutedText }};
        }

        .invoice-meta .value {
            font-weight: 600;
            color: #212529;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            margin: 10px 0 4px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #495057;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 8px;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #dee2e6;
            padding: 4px 6px;
        }

        .detail-table th {
            background-color: #f8f9fa;
            text-align: left;
            font-weight: 600;
            font-size: 9.5px;
        }

        .detail-table td.amount {
            text-align: right;
            font-weight: 700;
        }

        .notes {
            font-size: 9.5px;
            color: {{ $mutedText }};
            margin-top: 4px;
        }

        .signature-section {
            margin-top: 14px;
            font-size: 10.5px;
        }

        .signature-row {
            display: table;
            width: 100%;
        }

        .signature-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .signature-col:last-child {
            padding-right: 0;
            padding-left: 8px;
        }

        .signature-label {
            margin-bottom: 24px;
        }

        .signature-name {
            font-weight: 600;
            text-decoration: underline;
        }

        .signature-role {
            font-size: 9.5px;
            color: {{ $mutedText }};
        }

        .invoice-footer {
            border-top: 1px dashed #ced4da;
            margin-top: 10px;
            padding-top: 6px;
            text-align: center;
            font-size: 9px;
            color: {{ $mutedText }};
        }

        .invoice-footer .system-note {
            margin-bottom: 2px;
        }

        .invoice-footer .copyright {
            font-size: 9px;
        }
    </style>
</head>

<body>
    <div class="invoice-wrapper">
        {{-- HEADER --}}
        <header class="invoice-header">
            <div class="invoice-header-left">
                @if (!empty($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                @endif
            </div>
            <div class="invoice-header-right">
                <h2 class="app-title">{{ $setting->title ?? 'Sistem Keuangan Baitul Maal' }}</h2>
                <p class="app-subtitle">{{ $setting->subtitle ?? 'Yayasan Masjid Al Iman Surabaya' }}</p>
                <div class="org-meta">
                    <div>Jl. Sutorejo Tengah No. 2-4, Perum. Sutorejo Indah, Dukuh Sutorejo, Mulyorejo, Surabaya</div>
                    <div>Telp: 0853 6936 9517 • Email: masjidalimansurabaya.com</div>
                </div>
            </div>
        </header>

        {{-- TITLE BAR --}}
        <section class="invoice-title-bar">
            <h1>KWITANSI TRANSAKSI</h1>
            <p>Dicetak pada: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
        </section>

        {{-- META TRANSAKSI --}}
        <table class="invoice-meta">
            <tr>
                <td class="label">Kode Transaksi</td>
                <td class="value">{{ $transaksi->kode_transaksi ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal Transaksi</td>
                <td class="value">
                    {{ \Carbon\Carbon::parse($transaksi->tanggal_transaksi ?? $tanggal_transaksi)->format('d/m/Y') }}
                </td>
            </tr>
            <tr>
                <td class="label">Jenis Transaksi</td>
                <td class="value">{{ ucfirst($transaksi->type ?? '-') }}</td>
            </tr>
            @if (!empty($transaksi->bidang_name))
                <tr>
                    <td class="label">Bidang</td>
                    <td class="value">
                        Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}
                    </td>
                </tr>
            @endif
        </table>

        {{-- DETAIL TRANSAKSI --}}
        <h3 class="section-title">Rincian Transaksi</h3>
        <table class="detail-table">
            <tr>
                <th style="width: 25%;">Akun</th>
                <td>
                    {{ $transaksi->akunKeuangan->nama_akun ?? 'N/A' }}
                    @if (!empty($transaksi->akunKeuangan->kode_akun))
                        <br><small style="color:#6c757d;">Kode: {{ $transaksi->akunKeuangan->kode_akun }}</small>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Sub Akun</th>
                <td>
                    {{ $transaksi->parentAkunKeuangan->nama_akun ?? 'N/A' }}
                    @if (!empty($transaksi->parentAkunKeuangan->kode_akun))
                        <br><small style="color:#6c757d;">Kode: {{ $transaksi->parentAkunKeuangan->kode_akun }}</small>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Deskripsi</th>
                <td>{{ $transaksi->deskripsi ?? '-' }}</td>
            </tr>
            <tr>
                <th>Jumlah</th>
                <td class="amount">
                    Rp {{ number_format($transaksi->amount ?? 0, 2, ',', '.') }}
                </td>
            </tr>
        </table>

        <div class="notes">
            <strong>Catatan:</strong> Kwitansi ini merupakan bukti transaksi sah yang tercatat pada
            {{ $setting->title }}. Harap disimpan untuk keperluan dokumentasi.
        </div>

        {{-- TANDA TANGAN --}}
        <section class="signature-section">
            <div class="signature-row">
                <div class="signature-col">
                    <div class="signature-label">
                        Surabaya, {{ \Carbon\Carbon::now()->format('d F Y') }}
                    </div>
                    <div class="signature-name">
                        __________________________
                    </div>
                    <div class="signature-role">
                        Bendahara / Penanggung Jawab
                    </div>
                </div>
                <div class="signature-col">
                    <div class="signature-label">
                        Dicetak oleh:
                    </div>
                    <div class="signature-name">
                        {{ auth()->user()->name ?? 'Administrator Sistem' }}
                    </div>
                    <div class="signature-role">
                        Operator Sistem Informasi Keuangan
                    </div>
                </div>
            </div>
        </section>

        {{-- FOOTER --}}
        <footer class="invoice-footer">
            <div class="system-note">
                Kwitansi ini dihasilkan otomatis oleh
                {{ $setting->title ?? 'Sistem Informasi Keuangan Baitul Maal' }}.
                Tidak memerlukan tanda tangan basah.
            </div>
            <div class="copyright">
                &copy; {{ date('Y') }}
                {{ $setting->subtitle ?? 'Yayasan Masjid Al Iman Surabaya' }}
                &middot;
                {{ $setting->title ?? 'Sistem Baitul Maal' }}.
                Seluruh hak cipta dilindungi.
            </div>
        </footer>
    </div>
</body>

</html>
