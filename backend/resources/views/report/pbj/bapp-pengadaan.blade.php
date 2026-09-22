<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $res['bapp']->nomorbapp }}</title>
    <style>
        @page {
            margin: 26px 30px 34px;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            line-height: 1.25;
        }

        table {
            border-collapse: collapse;
        }

        .pdf-header {
            margin-bottom: 5px;
        }

        .header-table,
        .identity-table,
        .vendor-table,
        .signature-table {
            width: 100%;
        }

        .header-table td,
        .vendor-table th,
        .vendor-table td {
            border: 1px solid #555;
        }

        .header-logo {
            width: 105px;
            vertical-align: top;
        }

        .header-logo img {
            height: 34px;
            margin: 8px 5px 2px;
        }

        .header-logo div {
            margin: 1px 0 5px 23px;
            font-size: 8.5pt;
            font-weight: 700;
        }

        .company-title {
            padding: 3px 5px;
            background: #345b99;
            color: #fff;
            font-size: 9.5pt;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
        }

        .document-title {
            padding: 3px 5px;
            border-top: 1px solid #555;
            font-size: 8.5pt;
            font-weight: 700;
            text-align: center;
        }

        .document-number {
            padding: 3px 7px;
            border-top: 1px solid #555;
            font-size: 7pt;
            font-weight: 700;
            text-align: center;
        }

        .control-cell {
            width: 176px;
            padding: 0 !important;
            vertical-align: top;
        }

        .control-table {
            width: 100%;
            font-size: 6.5pt;
        }

        .control-table td {
            padding: 3px 4px;
            border: 0;
            border-bottom: 1px solid #555;
        }

        .control-table tr:last-child td {
            border-bottom: 0;
        }

        .page-now::before {
            content: counter(page);
        }

        .content {
            margin-top: 0;
        }

        .result-title {
            margin-bottom: 12px;
            padding: 6px;
            border-top: 1px solid #555;
            border-bottom: 1px solid #555;
            font-size: 9pt;
            font-weight: 700;
            text-align: center;
        }

        .identity-table {
            margin-bottom: 12px;
            font-size: 8pt;
        }

        .identity-table td {
            padding: 3px 2px;
            border: 0;
            vertical-align: top;
        }

        .identity-label {
            width: 30%;
        }

        .identity-separator {
            width: 3%;
            text-align: center;
        }

        .section-title {
            margin: 7px 0 5px;
            font-weight: 700;
        }

        .vendor-table {
            table-layout: fixed;
            font-size: 7.5pt;
        }

        .vendor-table thead {
            display: table-header-group;
        }

        .vendor-table tr {
            page-break-inside: avoid;
        }

        .vendor-table th {
            padding: 6px 4px;
            background: #fff;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }

        .vendor-table td {
            min-height: 28px;
            padding: 7px 6px;
            vertical-align: top;
            word-break: break-word;
        }

        .vendor-table th:nth-child(1),
        .vendor-table td:nth-child(1) {
            width: 4%;
        }

        .vendor-table th:nth-child(2),
        .vendor-table td:nth-child(2) {
            width: 50%;
        }

        .vendor-table th:nth-child(3),
        .vendor-table td:nth-child(3) {
            width: 21%;
        }

        .vendor-table th:nth-child(4),
        .vendor-table td:nth-child(4) {
            width: 25%;
        }

        .number-cell {
            text-align: center;
        }

        .amount-cell {
            text-align: right;
            white-space: nowrap;
        }

        .empty-row td {
            height: 34px;
            color: #555;
            text-align: center;
            vertical-align: middle;
        }

        .signature-heading {
            margin-top: 14px;
            padding: 8px;
            border-top: 1px solid #555;
            border-bottom: 1px solid #555;
            font-size: 7.5pt;
            font-weight: 700;
            line-height: 1.35;
            text-align: center;
        }

        .signature-block {
            page-break-inside: avoid;
        }

        .signature-table {
            margin-top: 8px;
            font-size: 7.5pt;
        }

        .signature-table td {
            padding: 9px 5px;
            border: 0;
            vertical-align: top;
        }

        .signature-number {
            width: 5%;
            text-align: center;
        }

        .signature-role {
            width: 35%;
        }

        .signature-name {
            width: 31%;
            font-weight: 700;
        }

        .signature-line {
            width: 29%;
        }
    </style>
</head>

<body>
    @php
        $logoPath = public_path('img/pln.png');
        $logoSource = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <div class="pdf-header">
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="header-logo">
                    @if ($logoSource)
                        <img src="{{ $logoSource }}" alt="PLN Nusantara Power">
                    @endif
                    <div>UMRO</div>
                </td>
                <td style="padding:0;vertical-align:top;">
                    <div class="company-title">
                        PT PLN NUSANTARA POWER<br>
                        UNIT MAINTENANCE REPAIR &amp; OVERHAUL
                    </div>
                    <div class="document-title">BERITA ACARA PEMBUKAAN PENAWARAN</div>
                    <div class="document-number">Nomor : {{ $res['bapp']->nomorbapp }}</div>
                </td>
                <td class="control-cell">
                    <table class="control-table" cellspacing="0" cellpadding="0">
                        <tr><td style="width:48%;">Nomor</td><td style="width:8%;text-align:center;">:</td><td></td></tr>
                        <tr><td>Tgl Berlaku</td><td style="text-align:center;">:</td><td></td></tr>
                        <tr><td>No. Revisi</td><td style="text-align:center;">:</td><td></td></tr>
                        <tr><td>Halaman</td><td style="text-align:center;">:</td><td><span class="page-now"></span></td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <div class="result-title">HASIL PEMBUKAAN PENAWARAN</div>

        <table class="identity-table" cellspacing="0" cellpadding="0">
            <tr><td class="identity-label">Tanggal</td><td class="identity-separator">:</td><td>{{ $res['tanggalBapp'] }}</td></tr>
            <tr><td>Nama Pekerjaan</td><td class="identity-separator">:</td><td>{{ $res['bapp']->judulpermintaan ?: '-' }}</td></tr>
            <tr><td>No. Permintaan Penawaran</td><td class="identity-separator">:</td><td>{{ $res['bapp']->nomorpp }}</td></tr>
            <tr><td>Tgl Permintaan Penawaran</td><td class="identity-separator">:</td><td>{{ $res['tanggalPp'] }}</td></tr>
        </table>

        <div class="section-title">1. HARGA PENAWARAN</div>
        <table class="vendor-table" cellspacing="0" cellpadding="0">
            <colgroup>
                <col style="width:4%;">
                <col style="width:50%;">
                <col style="width:21%;">
                <col style="width:25%;">
            </colgroup>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA PENYEDIA BARANG/JASA</th>
                    <th>TOTAL HARGA PENAWARAN<br>(Rp)</th>
                    <th>KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($res['penyedia'] as $index => $row)
                    <tr>
                        <td class="number-cell">{{ $index + 1 }}</td>
                        <td>{{ $row->namapenyedia }}</td>
                        <td class="amount-cell">{{ number_format((float) $row->totalharga, 0, ',', '.') }}</td>
                        <td>{{ $row->keterangan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="4">Belum ada penyedia yang ditambahkan.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="signature-block">
            <div class="signature-heading">
                PELAKSANA PENGADAAN BARANG/JASA<br>
                PT PLN NUSANTARA POWER<br>
                UNIT MAINTENANCE REPAIR &amp; OVERHAUL
            </div>
            <table class="signature-table" cellspacing="0" cellpadding="0">
                <tr><td class="signature-number">1</td><td class="signature-role">Manager General Affair</td><td class="signature-name">WAN VARIANI PERMATASARI</td><td class="signature-line">: ................................................</td></tr>
                <tr><td class="signature-number">2</td><td class="signature-role">Assistant Manager Pengadaan</td><td class="signature-name">KUSWINARTO</td><td class="signature-line">: ................................................</td></tr>
                <tr><td class="signature-number">3</td><td class="signature-role">Pelaksana Pengadaan</td><td class="signature-name">Grastika Selvya S.D</td><td class="signature-line">: ................................................</td></tr>
            </table>
        </div>
    </div>
</body>

</html>
