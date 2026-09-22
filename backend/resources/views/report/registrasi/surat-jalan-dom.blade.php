<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Surat Jalan</title>
</head>

<style>
    @page {
        margin-top: -10px;
        margin-bottom: 35px;
        margin-left: 5px;
        margin-right: 5px;
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        font-size: 10pt;
        margin: 0;
        padding: 25px;
    }

    .page-break {
        page-break-before: always;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .bold {
        font-weight: bold;
    }

    .surat-wrapper {
        border: 2px solid #000;
        padding: 18px;
        margin: 0;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #000;
        margin-bottom: 16px;
    }

    .header-table td {
        border: 1px solid #000;
        vertical-align: top;
    }

    .header-logo {
        width: 170px;
        height: 76px;
        text-align: center;
        vertical-align: middle !important;
        padding: 7px;
    }

    .header-logo img {
        width: 115px;
        height: auto;
    }

    .header-text {
        padding: 10px 12px;
        font-size: 10pt;
        line-height: 1.45;
    }

    .title-table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #000;
        margin-bottom: 16px;
    }

    .title-main {
        font-size: 21pt;
        font-weight: bold;
        text-decoration: underline;
        text-align: center;
        line-height: 1;
        padding-top: 8px;
    }

    .title-no {
        font-size: 12pt;
        text-align: center;
        padding-top: 3px;
        padding-bottom: 8px;
    }

    .doc-row td {
        border-top: 2px solid #000;
        font-size: 8.5pt;
        font-weight: bold;
        padding: 4px 6px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
    }

    .info-table td {
        font-size: 9.5pt;
        padding: 3px 4px;
        vertical-align: top;
    }

    .info-label {
        width: 150px;
    }

    .info-sep {
        width: 14px;
        text-align: center;
    }

    .barang-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-top: 8px;
        margin-bottom: 18px;
    }

    .barang-table thead {
        display: table-header-group;
    }

    .barang-table tr {
        page-break-inside: avoid;
    }

    .barang-table th {
        border: 1px solid #000;
        padding: 5px 4px;
        font-size: 9pt;
        font-weight: bold;
        text-align: center;
        line-height: 1.15;
    }

    .barang-table td {
        border: 1px solid #000;
        padding: 5px 4px;
        font-size: 9pt;
        text-align: center;
        vertical-align: middle;
        line-height: 1.15;
        word-wrap: break-word;
    }

    .col-no {
        width: 6%;
    }

    .col-nama {
        width: 22%;
    }

    .col-merk {
        width: 25%;
    }

    .col-sn {
        width: 21%;
    }

    .col-jumlah {
        width: 12%;
    }

    .col-ket {
        width: 14%;
    }

    .vehicle-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        margin-bottom: 22px;
    }

    .vehicle-table td {
        font-size: 9.5pt;
        padding: 3px 4px;
        vertical-align: top;
    }

    .vehicle-label {
        width: 150px;
    }

    .vehicle-sep {
        width: 14px;
        text-align: center;
    }

    .signature-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    .signature-table td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        font-size: 9.5pt;
    }

    .signature-location {
        height: 22px;
    }

    .signature-title {
        height: 26px;
    }

    .signature-box {
        height: 92px;
        text-align: center;
        vertical-align: bottom !important;
    }

    .signature-line {
        width: 180px;
        height: 1px;
        border-bottom: 1px solid transparent;
        margin: 0 auto;
    }

    .signature-name {
        font-size: 9.5pt;
        white-space: nowrap;
    }

    .mengetahui {
        width: 100%;
        text-align: center;
        margin-top: 28px;
        page-break-inside: avoid;
    }

    .mengetahui-title {
        font-size: 9.5pt;
        margin-bottom: 5px;
    }

    .mengetahui-box {
        height: 88px;
        text-align: center;
    }

    .mengetahui-name {
        font-size: 9.5pt;
        margin-top: 0;
    }

    .mengetahui-jabatan {
        margin-top: 6px;
        font-size: 8.5pt;
        line-height: 1.3;
    }

    .footer-note {
        border-top: 2px solid #000;
        padding-top: 8px;
        margin-top: 22px;
    }

    .footer-note-title {
        font-size: 8.5pt;
        font-weight: bold;
        font-style: italic;
    }

    .footer-note-row {
        font-size: 7.8pt;
        margin-top: 5px;
        margin-left: 22px;
    }

    .lampiran-wrapper {
        border: 2px solid #000;
        padding: 45px 40px 40px 40px;
        margin: 0;
    }

    .lampiran-title {
        text-align: center;
        font-size: 13pt;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 8px;
        margin-bottom: 55px;
    }

    .lampiran-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .lampiran-table td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        padding: 0 25px 42px 25px;
        page-break-inside: avoid;
    }

    .lampiran-img {
        width: 245px;
        max-width: 100%;
        height: 300px;
        object-fit: contain;
    }

    .lampiran-caption {
        margin-top: 10px;
        font-size: 10pt;
        line-height: 1.25;
        text-align: center;
        font-weight: bold;
    }

    .lampiran-sn {
        margin-top: 3px;
        font-size: 9pt;
        line-height: 1.25;
        text-align: center;
    }
</style>

<body>
    @php
        $identitas = $res['identitas'];

        $tanggalSurat = !empty($identitas->tanggalsurat)
            ? \Carbon\Carbon::parse($identitas->tanggalsurat)->locale('id')->isoFormat('D MMMM Y')
            : '-';

        $tanggalKota = !empty($identitas->tanggalsurat)
            ? \Carbon\Carbon::parse($identitas->tanggalsurat)->locale('id')->isoFormat('D MMMM Y')
            : \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y');

        $noSurat = $identitas->nosuratjalan ?? '-';

        $namaPembuat = $res['namaPembuat'] ?? '-';
        $namaPembawa = $res['namaPembawa'] ?? '-';

        $lampiran = $res['lampiran'] ?? [];
    @endphp

    <div class="surat-wrapper">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('img/pln.png') }}" alt="PLN">
                </td>
                <td class="header-text">
                    <div>PT PLN Nusantara Power UMRO</div>
                    <div>Unit Maintenance Repair &amp; Overhaul</div>
                </td>
            </tr>
        </table>

        <table class="title-table">
            <tr>
                <td>
                    <div class="title-main">SURAT JALAN</div>
                    <div class="title-no">No.{{ $noSurat }}</div>
                </td>
            </tr>
            <tr class="doc-row">
                <td>
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="50%" style="text-align:left; border:none; padding:0;">
                                Edisi I Revisi 0
                            </td>
                            <td width="50%" style="text-align:right; border:none; padding:0;">
                                UB-FR-3-15-61-06
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td class="info-label">Diberikan kepada</td>
                <td class="info-sep">:</td>
                <td>{{ $identitas->diberikankepada ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Berdasarkan</td>
                <td class="info-sep">:</td>
                <td>{{ $identitas->berdasarkan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Tanggal</td>
                <td class="info-sep">:</td>
                <td>{{ $tanggalSurat }}</td>
            </tr>
            <tr>
                <td class="info-label">Tujuan</td>
                <td class="info-sep">:</td>
                <td>{{ $identitas->tujuan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Barang-barang dari</td>
                <td class="info-sep">:</td>
                <td>{{ $identitas->barangbarangdari ?? '-' }}</td>
            </tr>
        </table>

        <table class="barang-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-nama">Nama Barang</th>
                    <th class="col-merk">Merk/Tipe</th>
                    <th class="col-sn">S/N</th>
                    <th class="col-jumlah">Jumlah</th>
                    <th class="col-ket">Ket</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($res['alat'] as $index => $alat)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $alat->namabarang ?? '-' }}</td>
                        <td>{{ trim(($alat->namamerk ?? '') . ' ' . ($alat->namatipe ?? '')) ?: '-' }}</td>
                        <td>{{ $alat->namaserialnumber ?? '-' }}</td>
                        <td>{{ $alat->jumlah ?? '1' }} {{ $alat->satuan ?? 'SET' }}</td>
                        <td>{{ $alat->ket ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="vehicle-table">
            <tr>
                <td class="vehicle-label">Kendaraan</td>
                <td class="vehicle-sep">:</td>
                <td>{{ $identitas->kendaraan ?? '' }}</td>
            </tr>
            <tr>
                <td class="vehicle-label">Nomor Polisi</td>
                <td class="vehicle-sep">:</td>
                <td>{{ $identitas->nomorpolisi ?? '' }}</td>
            </tr>
            <tr>
                <td class="vehicle-label">Pengemudi</td>
                <td class="vehicle-sep">:</td>
                <td>{{ $identitas->pengemudi ?? '' }}</td>
            </tr>
        </table>

        <table class="signature-table">
            <tr>
                <td class="signature-location"></td>
                <td class="signature-location">Jakarta, {{ $tanggalKota }}</td>
            </tr>
            <tr>
                <td class="signature-title">Yang Membuat</td>
                <td class="signature-title">Yang Membawa</td>
            </tr>
            <tr>
                <td class="signature-box"></td>
                <td class="signature-box"></td>
            </tr>
            <tr>
                <td class="signature-name">( {{ $namaPembuat }} )</td>
                <td class="signature-name">( {{ $namaPembawa }} )</td>
            </tr>
        </table>

        <div class="mengetahui">
            <div class="mengetahui-title">Mengetahui</div>
            <div class="mengetahui-box"></div>
            <div class="mengetahui-name">( Mahzumi )</div>
            <div class="mengetahui-jabatan">
                Assistant Manager Non Mechanical Workshop
            </div>
        </div>

        <div class="footer-note">
            <div class="footer-note-title">Keterangan :</div>
            <div class="footer-note-row">- &nbsp;&nbsp; Asli Untuk Security</div>
            <div class="footer-note-row">- &nbsp;&nbsp; Copy Untuk Ybs</div>
        </div>
    </div>

    @if (!empty($lampiran) && count($lampiran) > 0)
        @foreach (array_chunk($lampiran, 4) as $pageLampiran)
            <div class="page-break"></div>

            <div class="lampiran-wrapper">
                <div class="lampiran-title">LAMPIRAN</div>

                <table class="lampiran-table">
                    @foreach (array_chunk($pageLampiran, 2) as $rowLampiran)
                        <tr>
                            @foreach ($rowLampiran as $foto)
                                <td>
                                    <img src="{{ $foto['path'] }}" class="lampiran-img">
                                    <div class="lampiran-caption">
                                        {{ $foto['caption'] ?? '-' }}
                                    </div>
                                    <div class="lampiran-sn">
                                        S/N : {{ $foto['sn'] ?? '-' }}
                                    </div>
                                </td>
                            @endforeach

                            @if (count($rowLampiran) == 1)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif
</body>

</html>