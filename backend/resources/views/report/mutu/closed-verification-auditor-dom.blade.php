<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Closed Verification Auditor {{ $res['lokasiCetak'] }} {{ $res['tahun'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 130px 22px 34px 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
        }

        .pdf-header {
            position: fixed;
            top: -117px;
            left: 0;
            right: 0;
            height: 82px;
        }

        .first-page-header {
            position: absolute;
            top: -117px;
            left: 0;
            right: 0;
            height: 82px;
        }

        .header-table,
        .meta-table,
        .verification-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .meta-table td {
            border: 1px solid #555;
        }

        .header-logo {
            width: 15%;
            text-align: center;
            vertical-align: middle;
        }

        .header-center {
            width: 70%;
            padding: 0;
            text-align: center;
            vertical-align: middle;
            font-family: DejaVu Serif, serif;
            font-weight: bold;
        }

        .header-center .company {
            padding: 4px;
            border-bottom: 1px solid #555;
            font-size: 11pt;
        }

        .header-center .form-label {
            padding: 2px;
            border-bottom: 1px solid #555;
            font-size: 9pt;
        }

        .header-center .document-title {
            padding: 2px;
            font-size: 10pt;
        }

        .header-meta {
            padding: 0;
        }

        .meta-table td {
            padding: 2px 5px;
            font-family: DejaVu Serif, serif;
            font-size: 6.5pt;
            font-weight: bold;
            text-align: center;
        }

        .document-info {
            margin-bottom: 10px;
            font-family: DejaVu Serif, serif;
            font-size: 8pt;
            line-height: 1.35;
        }

        .document-info .label {
            display: inline-block;
            width: 125px;
        }

        .check-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin: 0 4px -2px 3px;
            border: 1px solid #111;
            background: #111;
        }

        .verification-table {
            table-layout: fixed;
            font-family: DejaVu Serif, serif;
            font-size: 6.2pt;
        }

        .verification-table thead {
            display: table-header-group;
        }

        .verification-table tr {
            page-break-inside: avoid;
        }

        .verification-table th,
        .verification-table td {
            border: 1px solid #333;
            padding: 4px 3px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .verification-table th {
            text-align: center;
            vertical-align: middle;
            font-size: 6.5pt;
            line-height: 1.15;
        }

        .verification-table td {
            line-height: 1.25;
        }

        .evidence-link {
            display: block;
            margin-top: 5px;
            color: #075b9a;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 5.8pt;
            text-decoration: underline;
        }

        .text-center {
            text-align: center;
        }

        .audit-group td {
            padding: 4px 6px;
            background: #f1f1f1;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
        }

        .signature-wrap {
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .signature-table {
            table-layout: fixed;
            font-family: DejaVu Serif, serif;
            font-size: 7.5pt;
        }

        .signature-table td {
            width: 25%;
            padding: 3px 8px;
            text-align: center;
            vertical-align: top;
        }

        .signature-heading-table {
            width: 100%;
            margin-bottom: 2px;
            border-collapse: collapse;
            font-family: DejaVu Serif, serif;
            font-size: 8pt;
        }

        .signature-heading-table td {
            width: 50%;
            padding: 0 8px;
            border: 0;
        }

        .signature-date {
            text-align: right;
        }

        .signature-space {
            height: 66px;
        }

        .signature-names {
            min-height: 16px;
            font-weight: bold;
            text-decoration: underline;
        }

        .signature-role {
            margin-top: 3px;
        }

        .legend {
            margin-top: 22px;
            font-family: DejaVu Serif, serif;
            font-size: 7pt;
            line-height: 1.35;
        }
    </style>
</head>

<body>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('DejaVu Serif', 'normal');
            $pdf->page_text(670, 74, '{PAGE_NUM} dari {PAGE_COUNT}', $font, 6.5, array(0, 0, 0));
        }
    </script>

    <div class="first-page-header">
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('img/pln.png') }}" alt="PLN Nusantara Power" style="width:88px; height:auto;">
                </td>
                <td class="header-center">
                    <div class="company">LABORATORIUM KALIBRASI PT PLN NP</div>
                    <div class="form-label">FORMULIR</div>
                    <div class="document-title">CLOSED VERIFICATION AUDITOR</div>
                    <table class="meta-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width:38%;">No.Dok: FMMO-163-14.4.3.b-88.7</td>
                            <td style="width:14%;">Revisi: 00</td>
                            <td style="width:26%;">Tanggal: 01-08-2022</td>
                            <td style="width:22%;">Halaman:</td>
                        </tr>
                    </table>
                </td>
                <td class="header-logo">
                    <img src="{{ public_path('img/UMRO.png') }}" alt="UMRO Laboratory" style="width:82px; height:auto;">
                </td>
            </tr>
        </table>
    </div>

    @php
        $tanggalAudit = $res['audits']->groupBy('lokasi')->map(function ($audits, $lokasi) {
            $tanggal = $audits->pluck('tanggalaudit')->filter()->unique()->map(function ($nilai) {
                return \Carbon\Carbon::parse($nilai)->translatedFormat('d F Y');
            })->implode(', ');
            return $lokasi . ' ' . $tanggal;
        })->implode('; ');
        $bagianAudit = $res['audits']->pluck('lingkup')->filter()->unique()->implode(' & ');
        $nomorTemuan = 1;
    @endphp

    <div class="document-info">
        <div><span class="label">Tanggal Audit</span>: {{ $tanggalAudit }}</div>
        <div><span class="label">Bagian</span>: {{ $bagianAudit ?: '-' }}</div>
        <div><span class="label">Sumber Ketidaksesuaian</span>: <span class="check-box"></span> Audit Internal</div>
    </div>

    <table class="verification-table">
        <colgroup>
            <col style="width:2.5%;">
            <col style="width:5%;">
            <col style="width:4%;">
            <col style="width:4.5%;">
            <col style="width:17%;">
            <col style="width:7%;">
            <col style="width:7.5%;">
            <col style="width:13%;">
            <col style="width:11%;">
            <col style="width:11%;">
            <col style="width:10.5%;">
            <col style="width:7%;">
        </colgroup>
        <thead>
            <tr>
                <th style="width:2.5%;">No</th>
                <th style="width:5%;">Bagian</th>
                <th style="width:4%;">Klausul</th>
                <th style="width:4.5%;">Kategori<br>Temuan</th>
                <th style="width:17%;">Uraian<br>Ketidaksesuaian</th>
                <th style="width:7%;">Auditor</th>
                <th style="width:7.5%;">Auditee</th>
                <th style="width:13%;">Analisa<br>Penyebab</th>
                <th style="width:11%;">Tindakan<br>Koreksi</th>
                <th style="width:11%;">Tindakan<br>Korektif</th>
                <th style="width:10.5%;">Rencana<br>Penyelesaian</th>
                <th style="width:7%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($res['audits'] as $audit)
                <tr class="audit-group">
                    <td colspan="12">{{ $audit->jenisaudit }}</td>
                </tr>
                @foreach ($audit->temuan as $temuan)
                    @php
                        $buktiPerKolom = [];
                        foreach (['analisa', 'koreksi', 'korektif'] as $kolomBukti) {
                            $dokumenId = $temuan->{$kolomBukti . 'dokumenauditfk'} ?? null;
                            $tautan = $temuan->{$kolomBukti . 'tautandokumen'} ?? null;
                            $nama = $temuan->{$kolomBukti . 'namadokumen'} ?? null;
                            $tipe = $temuan->{$kolomBukti . 'tipedokumen'} ?? 'file';
                            if ($dokumenId) {
                                $tautan = url('/module/mutu/dokumen-audit-internal') . '?' . http_build_query([
                                    'openUdrId' => $dokumenId,
                                    'targetType' => $tipe === 'folder' ? 'folder' : 'file',
                                    'jenisudr' => 'Audit Internal',
                                ]);
                            }
                            $buktiPerKolom[$kolomBukti] = [
                                'tautan' => $tautan,
                                'nama' => $nama ?: 'Buka dokumen bukti',
                            ];
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $nomorTemuan++ }}</td>
                        <td>{{ $temuan->bagian }}</td>
                        <td>{{ $temuan->klausul }}</td>
                        <td class="text-center">{{ $temuan->kategoritemuan }}</td>
                        <td>{!! nl2br(e($temuan->uraianketidaksesuaian)) !!}</td>
                        <td>{{ $temuan->auditor }}</td>
                        <td>{{ $temuan->namaauditee }}</td>
                        <td>
                            {!! nl2br(e($temuan->analisapenyebab ?? '')) !!}
                            @if ($buktiPerKolom['analisa']['tautan'])
                                <a class="evidence-link" href="{{ $buktiPerKolom['analisa']['tautan'] }}" target="_blank">
                                    {{ $buktiPerKolom['analisa']['nama'] }}
                                </a>
                            @endif
                        </td>
                        <td>
                            {!! nl2br(e($temuan->tindakankoreksi ?? '')) !!}
                            @if ($buktiPerKolom['koreksi']['tautan'])
                                <a class="evidence-link" href="{{ $buktiPerKolom['koreksi']['tautan'] }}" target="_blank">
                                    {{ $buktiPerKolom['koreksi']['nama'] }}
                                </a>
                            @endif
                        </td>
                        <td>
                            {!! nl2br(e($temuan->tindakankorektif ?? '')) !!}
                            @if ($buktiPerKolom['korektif']['tautan'])
                                <a class="evidence-link" href="{{ $buktiPerKolom['korektif']['tautan'] }}" target="_blank">
                                    {{ $buktiPerKolom['korektif']['nama'] }}
                                </a>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $temuan->rencanapenyelesaian
                                ? \Carbon\Carbon::parse($temuan->rencanapenyelesaian)->translatedFormat('d F Y')
                                : '' }}
                        </td>
                        <td class="text-center">{{ $temuan->statusverifikasi ?? '' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="signature-wrap">
        <table class="signature-heading-table">
            <tr>
                <td>Mengetahui,</td>
                <td class="signature-date">
                    {{ $res['lokasiCetak'] }},
                    {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
                </td>
            </tr>
        </table>
        <table class="signature-table">
            <tr>
                <td><b>Kepala Laboratorium (Auditee)</b></td>
                <td><b>Lead Auditor</b></td>
                <td><b>Auditor Observer</b></td>
                <td><b>Auditor Observer</b></td>
            </tr>
            <tr>
                <td class="signature-space">&nbsp;</td>
                <td class="signature-space">&nbsp;</td>
                <td class="signature-space">&nbsp;</td>
                <td class="signature-space">&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <div class="signature-names">{{ $res['managerPenandatangan']->first() }}</div>
                    <div class="signature-role">Kepala Laboratorium (Auditee)</div>
                </td>
                <td>
                    <div class="signature-names">{{ $res['leadAuditorPenandatangan']->first() }}</div>
                    <div class="signature-role">Lead Auditor</div>
                </td>
                <td>
                    <div class="signature-names">{{ $res['auditorObserverPenandatangan']->get(0) }}</div>
                    <div class="signature-role">Auditor Observer</div>
                </td>
                <td>
                    <div class="signature-names">{{ $res['auditorObserverPenandatangan']->get(1) }}</div>
                    <div class="signature-role">Auditor Observer</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="legend">
        <b>EVIDENCE PENYELESAIAN TEMUAN AUDIT INTERNAL U-LAB</b><br>
        Keterangan: 1. Temuan Major &nbsp;&nbsp; 2. Temuan Minor &nbsp;&nbsp; 3. Saran/Rekomendasi
    </div>

    <div class="pdf-header">
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('img/pln.png') }}" alt="PLN Nusantara Power" style="width:88px; height:auto;">
                </td>
                <td class="header-center">
                    <div class="company">LABORATORIUM KALIBRASI PT PLN NP</div>
                    <div class="form-label">FORMULIR</div>
                    <div class="document-title">CLOSED VERIFICATION AUDITOR</div>
                    <table class="meta-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width:38%;">No.Dok: FMMO-163-14.4.3.b-88.7</td>
                            <td style="width:14%;">Revisi: 00</td>
                            <td style="width:26%;">Tanggal: 01-08-2022</td>
                            <td style="width:22%;">Halaman:</td>
                        </tr>
                    </table>
                </td>
                <td class="header-logo">
                    <img src="{{ public_path('img/UMRO.png') }}" alt="UMRO Laboratory" style="width:82px; height:auto;">
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
