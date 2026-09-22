<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $res['pp']->nomorpp }}</title>
    <style>
        @page {
            margin: 104px 28px 42px;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            line-height: 1.18;
        }

        .pdf-header {
            position: fixed;
            top: -86px;
            right: 0;
            left: 0;
            height: 82px;
        }

        .page-now::before {
            content: counter(page);
        }

        table {
            border-collapse: collapse;
        }

        .header-table,
        .offer-table,
        .delivery-table,
        .item-table,
        .signature-table {
            width: 100%;
        }

        .header-table td,
        .offer-table td,
        .delivery-table td,
        .item-table th,
        .item-table td {
            border: 1px solid #555;
        }

        .header-logo {
            width: 104px;
            vertical-align: top;
        }

        .header-logo img {
            height: 34px;
            margin: 7px 5px 2px;
        }

        .header-logo div {
            margin: 1px 0 5px 22px;
            font-size: 8pt;
            font-weight: 700;
        }

        .company-title {
            padding: 3px 5px;
            background: #345b99;
            color: #fff;
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
        }

        .document-title {
            padding: 2px 5px;
            border-top: 1px solid #555;
            font-size: 8pt;
            font-weight: 700;
            text-align: center;
        }

        .document-identity {
            width: 100%;
            font-size: 6.5pt;
            font-weight: 700;
        }

        .document-identity td {
            padding: 2px 4px;
            border: 0;
            border-top: 1px solid #555;
            white-space: nowrap;
        }

        .control-cell {
            width: 174px;
            padding: 0 !important;
            vertical-align: top;
        }

        .control-table {
            width: 100%;
            font-size: 6pt;
        }

        .control-table td {
            padding: 2px 4px;
            border: 0;
            border-bottom: 1px solid #555;
        }

        .control-table tr:last-child td {
            border-bottom: 0;
        }

        .content {
            margin-top: -8px;
        }

        .deadline {
            margin-bottom: 4px;
            padding: 4px 5px;
            border: 1px solid #555;
            font-size: 7pt;
        }

        .deadline strong {
            margin-left: 7px;
        }

        .offer-table {
            margin-bottom: 0;
            table-layout: fixed;
        }

        .offer-table td {
            height: 92px;
            padding: 6px 5px;
            vertical-align: top;
        }

        .offer-labels {
            width: 67%;
        }

        .offer-lines td {
            height: auto;
            padding: 1px 0;
            border: 0;
            vertical-align: top;
        }

        .offer-lines .label {
            width: 42%;
        }

        .offer-lines .separator {
            width: 4%;
            text-align: center;
        }

        .recipient {
            font-weight: 700;
            line-height: 1.45;
        }

        .delivery-table td {
            padding: 4px 5px;
            border-top: 0;
            background: #c6e0b4;
            font-size: 6.8pt;
        }

        .delivery-table .delivery-label {
            width: 32%;
            background: #fff;
        }

        .item-table {
            table-layout: fixed;
            font-size: 6.5pt;
        }

        .item-table thead {
            display: table-header-group;
        }

        .item-table tr {
            page-break-inside: avoid;
        }

        .item-table th {
            padding: 4px 3px;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
        }

        .item-table td {
            padding: 4px 4px;
            vertical-align: top;
        }

        .intro-row td {
            padding: 5px 5px 3px;
            font-weight: 700;
            line-height: 1.35;
        }

        .item-number,
        .item-qty,
        .item-unit {
            text-align: center;
            vertical-align: middle !important;
        }

        .item-copy {
            line-height: 1.28;
            white-space: normal;
            word-break: break-word;
        }

        .item-name {
            margin-bottom: 2px;
        }

        .summary-label {
            padding: 4px !important;
            text-align: right;
        }

        .delivery-time {
            padding: 5px 5px !important;
        }

        .signature-table {
            margin-top: 8px;
            font-size: 7pt;
        }

        .signature-table td {
            width: 50%;
            padding: 3px 8px;
            border: 0;
            vertical-align: top;
        }

        .signature-space {
            height: 48px;
        }

        .signature-name {
            text-decoration: underline;
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
                    <div class="document-title">PERMINTAAN PENAWARAN</div>
                    <table class="document-identity" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width:11%;">Nomor</td>
                            <td style="width:3%;text-align:center;">:</td>
                            <td style="width:45%;">{{ $res['pp']->nomorpp }}</td>
                            <td style="width:14%;text-align:right;">TANGGAL</td>
                            <td style="width:3%;text-align:center;">:</td>
                            <td style="width:24%;">{{ $res['tanggalPp'] }}</td>
                        </tr>
                    </table>
                </td>
                <td class="control-cell">
                    <table class="control-table" cellspacing="0" cellpadding="0">
                        <tr><td style="width:48%;">No. Dokumen</td><td style="width:8%;text-align:center;">:</td><td></td></tr>
                        <tr><td>Tgl. Berlaku</td><td style="text-align:center;">:</td><td></td></tr>
                        <tr><td>No. Revisi</td><td style="text-align:center;">:</td><td></td></tr>
                        <tr><td>Halaman</td><td style="text-align:center;">:</td><td><span class="page-now"></span></td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <div class="deadline">BATAS PEMASUKAN PENAWARAN <strong>: {{ $res['batasPemasukan'] }}</strong></div>

        <table class="offer-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="offer-labels">
                    <table class="offer-lines" cellspacing="0" cellpadding="0">
                        <tr><td class="label">KODE REKANAN</td><td class="separator">:</td><td></td></tr>
                        <tr><td>NO. SURAT PENAWARAN</td><td class="separator">:</td><td></td></tr>
                        <tr><td>TGL. SURAT PENAWARAN</td><td class="separator">:</td><td></td></tr>
                        <tr><td>BATAS BERLAKU PENAWARAN</td><td class="separator">:</td><td></td></tr>
                        <tr><td style="padding-top:5px;">JAMINAN PENAWARAN</td><td></td><td></td></tr>
                        <tr><td>&nbsp;&nbsp;&nbsp;- &nbsp;&nbsp;NAMA BANK</td><td class="separator">:</td><td></td></tr>
                        <tr><td>&nbsp;&nbsp;&nbsp;- &nbsp;&nbsp;NILAI JAMINAN</td><td class="separator">:</td><td></td></tr>
                    </table>
                </td>
                <td class="recipient">
                    KEPADA :<br>
                    PT PLN NUSANTARA POWER<br>
                    UNIT MAINTENANCE REPAIR &amp; OVERHAUL<br>
                    Jl. Pluit Karang Ayu No. 1<br>
                    Jakarta Utara
                </td>
            </tr>
        </table>

        <table class="delivery-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="delivery-label">TEMPAT PENYERAHAN BARANG &nbsp;:</td>
                <td>PT. PLN NUSANTARA POWER UNIT MAINTENANCE REPAIR &amp; OVERHAUL</td>
            </tr>
        </table>

        <table class="item-table" cellspacing="0" cellpadding="0">
            <colgroup>
                <col style="width:6%;">
                <col style="width:54%;">
                <col style="width:8%;">
                <col style="width:8%;">
                <col style="width:12%;">
                <col style="width:12%;">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" style="width:6%;">NO.</th>
                    <th style="width:54%;">NAMA BARANG &amp; SPESIFIKASI / STOCK CODE</th>
                    <th colspan="2" rowspan="2" style="width:16%;">JUMLAH</th>
                    <th rowspan="2" style="width:12%;">HARGA SATUAN</th>
                    <th rowspan="2" style="width:12%;">JUMLAH HARGA</th>
                </tr>
                <tr><th>URAIAN PEKERJAAN</th></tr>
            </thead>
            <tbody>
                <tr class="intro-row">
                    <td style="width:6%;"></td>
                    <td style="width:54%;">Nomor PR/IR {{ $res['pp']->nopr ?: '-' }}<br>{{ $res['pp']->judulpermintaan ?: '-' }}</td>
                    <td style="width:8%;"></td><td style="width:8%;"></td><td style="width:12%;"></td><td style="width:12%;"></td>
                </tr>
                @forelse ($res['details'] as $index => $detail)
                    <tr>
                        <td class="item-number" style="width:6%;">{{ $index + 1 }}</td>
                        <td class="item-copy" style="width:54%;">
                            <div class="item-name">{{ $detail->namaitem ?: '-' }}</div>
                            @if (!empty($detail->uraianitem))
                                <div>{!! nl2br(e($detail->uraianitem)) !!}</div>
                            @endif
                            @if (!empty($detail->stockcode))
                                <div>Stock code: {{ $detail->stockcode }}</div>
                            @endif
                        </td>
                        <td class="item-qty" style="width:8%;">{{ number_format((float) ($detail->banyak ?? 0), 0, ',', '.') }}</td>
                        <td class="item-unit" style="width:8%;">{{ $detail->satuan ?: '-' }}</td>
                        <td style="width:12%;"></td>
                        <td style="width:12%;"></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:10px;text-align:center;">Tidak ada item PBJ.</td></tr>
                @endforelse
                <tr>
                    <td colspan="2" class="delivery-time">WAKTU PENYERAHAN BARANG &nbsp;: ............. HARI</td>
                    <td></td><td></td><td></td><td></td>
                </tr>
                <tr><td colspan="5" class="summary-label">JUMLAH</td><td></td></tr>
                <tr><td colspan="5" class="summary-label">PPN</td><td></td></tr>
                <tr><td colspan="5" class="summary-label"><strong>TOTAL</strong></td><td></td></tr>
            </tbody>
        </table>

        <table class="signature-table" cellspacing="0" cellpadding="0">
            <tr>
                <td>DITAWARKAN OLEH,</td>
                <td>PT PLN NUSANTARA POWER UMRO<br>MANAGER GENERAL AFFAIR</td>
            </tr>
            <tr><td class="signature-space"></td><td></td></tr>
            <tr>
                <td class="signature-name">(............................................................)</td>
                <td class="signature-name">WAN VARIANI PERMATASARI</td>
            </tr>
        </table>
    </div>
</body>

</html>
