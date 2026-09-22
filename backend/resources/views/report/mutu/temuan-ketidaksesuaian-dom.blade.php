<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Temuan Ketidaksesuaian {{ $res['lokasiCetak'] }} {{ $res['tahun'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 112px 28px 38px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
        }

        .pdf-header {
            position: fixed;
            top: -96px;
            left: 0;
            right: 0;
            height: 92px;
        }

        .first-page-header {
            position: absolute;
            top: -96px;
            left: 0;
            right: 0;
            height: 92px;
        }

        .header-table,
        .meta-table,
        .info-table,
        .team-summary-table,
        .team-table,
        .finding-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .meta-table td {
            border: 1px solid #222;
        }

        .header-logo {
            width: 18%;
            text-align: center;
            vertical-align: middle;
        }

        .header-title {
            width: 51%;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            line-height: 1.25;
        }

        .header-meta {
            width: 31%;
            padding: 0;
            vertical-align: top;
        }

        .meta-table td {
            border-top: 0;
            border-right: 0;
            padding: 2px 4px;
            font-size: 7pt;
        }

        .meta-table tr:first-child td {
            border-top: 0;
        }

        .meta-table tr:last-child td {
            border-bottom: 0;
        }

        .meta-table td:first-child {
            border-left: 0;
            width: 44%;
        }

        .meta-table td:last-child {
            width: 56%;
        }

        .info-table {
            margin-bottom: 14px;
        }

        .info-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .info-label {
            width: 23%;
        }

        .info-separator {
            width: 2%;
        }

        .team-summary-table th,
        .team-summary-table td,
        .team-table th,
        .team-table td,
        .finding-table th,
        .finding-table td,
        .summary-table th,
        .summary-table td {
            border: 1px solid #222;
            padding: 4px;
            vertical-align: top;
        }

        .team-summary-table th,
        .team-table th,
        .finding-table th,
        .summary-table th {
            background: #fff;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .team-summary-table tr,
        .team-table tr,
        .finding-table tr,
        .summary-table tr {
            page-break-inside: avoid;
        }

        .team-summary-table {
            margin: 3px 0 12px 0;
            table-layout: fixed;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }

        .team-summary-table .team-title {
            padding: 7px 6px;
            text-align: left;
        }

        .team-summary-table .count-title {
            padding: 7px 6px;
            text-align: left;
            line-height: 1.2;
        }

        .team-summary-table .manual-signature {
            height: 42px;
            background: #fff;
            text-align: center;
            vertical-align: middle;
        }

        .participant-signature {
            display: block;
            width: 105px;
            height: 40px;
            margin: 0 auto;
        }

        .team-summary-table .count-cell {
            vertical-align: middle;
            line-height: 1.3;
        }

        .finding-table {
            table-layout: fixed;
            font-size: 7.2pt;
        }

        .finding-table thead {
            display: table-header-group;
        }

        .finding-table .col-no {
            width: 6%;
            text-align: center;
        }

        .finding-table .col-bagian {
            width: 13%;
        }

        .finding-table .col-klausul {
            width: 11%;
            text-align: center;
        }

        .finding-table .col-kategori {
            width: 12%;
            text-align: center;
        }

        .finding-table .col-uraian {
            width: 44%;
            line-height: 1.35;
            text-align: justify;
        }

        .finding-table .col-auditor {
            width: 14%;
            text-align: center;
        }

        .closing-place {
            margin: 10px 0 7px 0;
            text-align: right;
        }

        .auditee-wrap {
            width: 82%;
            page-break-inside: avoid;
        }

        .manual-signature-cell {
            height: 42px;
            background: #fff;
            text-align: center;
            vertical-align: middle;
        }

        .page-break {
            page-break-before: always;
        }

        .summary-title {
            margin-bottom: 14px;
            font-size: 9pt;
        }

        .summary-table {
            margin-bottom: 16px;
            font-size: 8pt;
        }

        .summary-table td {
            text-align: center;
        }

        .summary-table td.text-left {
            text-align: left;
        }

        .approval-signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
            font-size: 8.5pt;
        }

        .approval-signature-table td {
            width: 50%;
            border: 1px solid #222;
            text-align: center;
            vertical-align: top;
            padding: 6px 10px;
        }

        .approval-signature-table .signature-space {
            height: 68px;
            vertical-align: middle;
        }

        .approval-signature {
            display: block;
            width: 145px;
            height: 56px;
            margin: 0 auto;
        }

        .approval-signature-table .signature-name {
            height: 20px;
            vertical-align: middle;
            font-weight: bold;
            text-decoration: underline;
        }

        .empty-state {
            padding: 40px 20px;
            border: 1px solid #999;
            text-align: center;
            color: #555;
        }
    </style>
</head>

<body>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('Arial', 'normal');
            $pdf->page_text(491, 51, '{PAGE_NUM} dari {PAGE_COUNT}', $font, 7, array(0, 0, 0));
        }
    </script>

    <div class="first-page-header">
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('img/pln.png') }}" alt="PLN" style="width:82px; height:auto;">
                </td>
                <td class="header-title">
                    <div style="font-size:9pt;">PT PLN NUSANTARA POWER</div>
                    <div style="font-size:8pt;">INTEGRATED MANAGEMENT SYSTEM</div>
                    <div style="font-size:8pt; margin-top:3px;">LAPORAN RINGKAS DAN LEMBAR</div>
                    <div style="font-size:9pt;">TEMUAN KETIDAKSESUAIAN</div>
                </td>
                <td class="header-meta">
                    <table class="meta-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td>No Dokumen</td>
                            <td>: FMMO-163-14.4.3.b-88.5</td>
                        </tr>
                        <tr>
                            <td>Revisi</td>
                            <td>: 00</td>
                        </tr>
                        <tr>
                            <td>Tgl Terbit</td>
                            <td>: 06 Februari 2023</td>
                        </tr>
                        <tr>
                            <td>Halaman</td>
                            <td>:</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @forelse ($res['audits'] as $audit)
        <div class="audit-section{{ $loop->first ? '' : ' page-break' }}">

        <table class="info-table">
            <tr>
                <td class="info-label">Nama LPK</td>
                <td class="info-separator">:</td>
                <td>{{ $audit->namalpk }}</td>
            </tr>
            <tr>
                <td class="info-label">Standar Acuan</td>
                <td class="info-separator">:</td>
                <td>{{ $audit->standaracuan }}</td>
            </tr>
            <tr>
                <td class="info-label">Tanggal Audit Internal</td>
                <td class="info-separator">:</td>
                <td>{{ \Carbon\Carbon::parse($audit->tanggalaudit)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="info-label">Jenis Audit</td>
                <td class="info-separator">:</td>
                <td>{{ $audit->jenisaudit }}</td>
            </tr>
        </table>

        @php
            $jumlahBarisTim = max($audit->timAudit->count(), 3);
        @endphp
        <table class="team-summary-table">
            <colgroup>
                <col style="width:6%;">
                <col style="width:22%;">
                <col style="width:22%;">
                <col style="width:28%;">
                <col style="width:22%;">
            </colgroup>
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th colspan="3" class="team-title">Tim Audit Internal {{ $audit->lingkup }}</th>
                    <th class="count-title">Jumlah<br>Ketidaksesuaian</th>
                </tr>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Tugas dalam tim</th>
                    <th>Tanda Tangan</th>
                    <td class="count-cell">Kategori 1 : {{ $audit->kategori1 }}</td>
                </tr>
            </thead>
            <tbody>
                @for ($indexTim = 0; $indexTim < $jumlahBarisTim; $indexTim++)
                    @php
                        $peserta = $audit->timAudit->get($indexTim);
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $peserta ? $indexTim + 1 : '' }}</td>
                        <td>{{ $peserta->nama ?? '' }}</td>
                        <td>{{ $peserta->tugas ?? '' }}</td>
                        <td class="manual-signature">
                            @if ($peserta && !empty($peserta->tandatangan))
                                <img class="participant-signature" src="{{ $peserta->tandatangan }}" alt="Tanda tangan {{ $peserta->nama }}">
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td class="count-cell">
                            @if ($indexTim === 0)
                                Kategori 2 : {{ $audit->kategori2 }}
                            @elseif ($indexTim === 1)
                                Kategori 3 : {{ $audit->kategori3 }}
                            @elseif ($indexTim === 2)
                                <b>Total : {{ $audit->jumlahtemuan }} Temuan {{ $audit->lingkup }}</b>
                            @endif
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <table class="finding-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-bagian">Bagian</th>
                    <th class="col-klausul">Klausul</th>
                    <th class="col-kategori">Kategori Temuan</th>
                    <th class="col-uraian">Uraian Ketidaksesuaian</th>
                    <th class="col-auditor">Auditor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($audit->temuan as $temuan)
                    <tr>
                        <td class="col-no">{{ $loop->iteration }}</td>
                        <td class="col-bagian">{{ $temuan->bagian }}</td>
                        <td class="col-klausul">{{ $temuan->klausul }}</td>
                        <td class="col-kategori">{{ $temuan->kategoritemuan }}</td>
                        <td class="col-uraian">{!! nl2br(e($temuan->uraianketidaksesuaian)) !!}</td>
                        <td class="col-auditor">{{ $temuan->auditor }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="height:34px; text-align:center; color:#777;">Belum ada temuan yang diinput.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="closing-place">
            {{ $audit->lokasi === 'Jakarta & Gresik' ? 'Jakarta' : $audit->lokasi }},
            {{ \Carbon\Carbon::parse($audit->tanggalaudit)->translatedFormat('d F Y') }}
        </div>

        <div class="auditee-wrap">
            <div style="font-weight:bold; margin-bottom:3px;">Auditee</div>
            <table class="team-table">
                <thead>
                    <tr>
                        <th style="width:8%;">No</th>
                        <th style="width:35%;">Nama</th>
                        <th style="width:27%;">Tugas dalam tim</th>
                        <th style="width:30%;">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audit->auditee as $peserta)
                        <tr>
                            <td style="text-align:center;">{{ $loop->iteration }}</td>
                            <td>{{ $peserta->nama }}</td>
                            <td>{{ $peserta->tugas }}</td>
                            <td class="manual-signature-cell">
                                @if (!empty($peserta->tandatangan))
                                    <img class="participant-signature" src="{{ $peserta->tandatangan }}" alt="Tanda tangan {{ $peserta->nama }}">
                                @else
                                    &nbsp;
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="height:26px; text-align:center; color:#777;">Belum ada auditee</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    @empty
        <div class="empty-state">
            Belum ada data Temuan Ketidaksesuaian untuk tahun {{ $res['tahun'] }}.
        </div>
    @endforelse

    @if (count($res['audits']) > 0)
        <div class="page-break">
        <div class="summary-title">Setelah dilakukan klarifikasi antara auditor dan auditee diputuskan bahwa:</div>

        <table class="summary-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width:6%;">No</th>
                    <th rowspan="2" style="width:22%;">Lingkup Audit</th>
                    <th rowspan="2" style="width:20%;">Lokasi</th>
                    <th colspan="3">Temuan</th>
                    <th rowspan="2" style="width:12%;">Jumlah</th>
                </tr>
                <tr>
                    <th style="width:10%;">Kategori 1</th>
                    <th style="width:10%;">Kategori 2</th>
                    <th style="width:10%;">Kategori 3</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalKategori1 = 0;
                    $totalKategori2 = 0;
                    $totalKategori3 = 0;
                    $totalTemuan = 0;
                @endphp
                @foreach ($res['audits'] as $audit)
                    @php
                        $totalKategori1 += $audit->kategori1;
                        $totalKategori2 += $audit->kategori2;
                        $totalKategori3 += $audit->kategori3;
                        $totalTemuan += $audit->jumlahtemuan;
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-left">{{ $audit->lingkup }}</td>
                        <td class="text-left">{{ $audit->lokasi }}</td>
                        <td>{{ $audit->kategori1 }}</td>
                        <td>{{ $audit->kategori2 }}</td>
                        <td>{{ $audit->kategori3 }}</td>
                        <td>{{ $audit->jumlahtemuan }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight:bold;">
                    <td colspan="3" style="text-align:right;">Jumlah</td>
                    <td>{{ $totalKategori1 }}</td>
                    <td>{{ $totalKategori2 }}</td>
                    <td>{{ $totalKategori3 }}</td>
                    <td>{{ $totalTemuan }}</td>
                </tr>
            </tbody>
        </table>

        <div style="font-weight:bold; margin-bottom:8px;">Keterangan Kategori Temuan:</div>
        <table style="width:250px; line-height:1.8;">
            <tr><td style="width:25px;">1</td><td style="width:12px;">:</td><td>Major</td></tr>
            <tr><td>2</td><td>:</td><td>Minor</td></tr>
            <tr><td>3</td><td>:</td><td>Observasi</td></tr>
        </table>

        @php
            // Jarak menyesuaikan jumlah baris ringkasan agar kolom tanda tangan
            // tetap berada dekat bagian bawah halaman tanpa membuat halaman baru.
            $marginAtasTandaTangan = max(330, 580 - (count($res['audits']) * 24));
        @endphp
        <table class="approval-signature-table" style="margin-top:{{ $marginAtasTandaTangan }}px;">
            <tr>
                <td>
                    Dibuat oleh,<br>
                    <b>Lead Auditor</b>
                </td>
                <td>
                    Disetujui oleh,<br>
                    <b>Kepala Laboratorium (Manager Repair)</b>
                </td>
            </tr>
            <tr>
                <td class="signature-space">
                    @if (!empty($res['leadAuditor']->tandatangan))
                        <img class="approval-signature" src="{{ $res['leadAuditor']->tandatangan }}" alt="Tanda tangan Lead Auditor">
                    @else
                        &nbsp;
                    @endif
                </td>
                <td class="signature-space">
                    @if (!empty($res['kepalaLaboratorium']->tandatangan))
                        <img class="approval-signature" src="{{ $res['kepalaLaboratorium']->tandatangan }}" alt="Tanda tangan Kepala Laboratorium">
                    @else
                        &nbsp;
                    @endif
                </td>
            </tr>
            <tr>
                <td class="signature-name">{{ $res['leadAuditor']->namapegawai ?? '' }}</td>
                <td class="signature-name">{{ $res['kepalaLaboratorium']->namapegawai ?? '' }}</td>
            </tr>
        </table>
        </div>
    @endif

    <div class="pdf-header">
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('img/pln.png') }}" alt="PLN" style="width:82px; height:auto;">
                </td>
                <td class="header-title">
                    <div style="font-size:9pt;">PT PLN NUSANTARA POWER</div>
                    <div style="font-size:8pt;">INTEGRATED MANAGEMENT SYSTEM</div>
                    <div style="font-size:8pt; margin-top:3px;">LAPORAN RINGKAS DAN LEMBAR</div>
                    <div style="font-size:9pt;">TEMUAN KETIDAKSESUAIAN</div>
                </td>
                <td class="header-meta">
                    <table class="meta-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td>No Dokumen</td>
                            <td>: FMMO-163-14.4.3.b-88.5</td>
                        </tr>
                        <tr>
                            <td>Revisi</td>
                            <td>: 00</td>
                        </tr>
                        <tr>
                            <td>Tgl Terbit</td>
                            <td>: 06 Februari 2023</td>
                        </tr>
                        <tr>
                            <td>Halaman</td>
                            <td>:</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
