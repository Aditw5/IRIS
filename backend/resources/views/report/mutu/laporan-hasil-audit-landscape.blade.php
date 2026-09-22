<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Resume Tindak Lanjut LHA {{ $res['tahun'] }}</title>
    <style>
        @font-face {
            font-family: 'Lato';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path('fonts/Lato/Lato-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Lato';
            font-style: italic;
            font-weight: 400;
            src: url('{{ public_path('fonts/Lato/Lato-Italic.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Lato';
            font-style: normal;
            font-weight: 700;
            src: url('{{ public_path('fonts/Lato/Lato-Bold.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Lato';
            font-style: italic;
            font-weight: 700;
            src: url('{{ public_path('fonts/Lato/Lato-BoldItalic.ttf') }}') format('truetype');
        }
        @page { size: A4 landscape; margin: 28mm 12mm 25mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #000;
            font-family: 'Lato', sans-serif;
            font-size: 7.7pt;
            line-height: 1.3;
        }
        .page-decoration {
            display: none;
            position: fixed;
            z-index: -1000;
            top: -28mm;
            left: -12mm;
            width: 297mm;
            height: 210mm;
        }
        .report-header {
            display: none;
            position: fixed;
            top: -22.5mm;
            left: 0;
            right: 0;
            height: 16mm;
        }
        .report-header table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .report-header td { padding: 0; vertical-align: middle; }
        .header-copy {
            width: 53%;
            color: #7fa7b4;
            font-size: 6.8pt;
            font-style: italic;
            font-weight: 700;
            line-height: 1.25;
        }
        .header-pln { width: 27%; text-align: center; }
        .header-pln img { width: 42mm; height: auto; }
        .header-right { width: 20%; text-align: right; }
        .header-right-crop {
            display: inline-block;
            width: 36mm;
            height: 12mm;
            overflow: hidden;
            text-align: left;
        }
        .header-right-crop img { width: 55.4mm; height: auto; }
        .caption {
            margin: 0 0 3mm;
            color: #000;
            font-size: 8pt;
            font-weight: 700;
            text-align: center;
        }
        .detail-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .detail-table thead { display: table-header-group; }
        .detail-table tr { page-break-inside: avoid; }
        .detail-table th, .detail-table td {
            padding: 2.4mm 1.5mm;
            border: 1px solid #000;
            color: #000;
            vertical-align: middle;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .detail-table th { font-size: 8pt; font-weight: 700; line-height: 1.2; text-align: center; }
        .detail-table td { text-align: center; white-space: pre-line; }
        .detail-table th:nth-child(1), .detail-table td:nth-child(1) { width: 28%; }
        .detail-table th:nth-child(2), .detail-table td:nth-child(2) { width: 7%; }
        .detail-table th:nth-child(3), .detail-table td:nth-child(3) { width: 9%; }
        .detail-table th:nth-child(4), .detail-table td:nth-child(4) { width: 15%; }
        .detail-table th:nth-child(5), .detail-table td:nth-child(5) { width: 16%; }
        .detail-table th:nth-child(6), .detail-table td:nth-child(6) { width: 16%; }
        .detail-table th:nth-child(7), .detail-table td:nth-child(7) { width: 9%; }
        .detail-table td:nth-child(2),
        .detail-table td:nth-child(3) { padding-right: 1mm; padding-left: 1mm; font-size: 7pt; }
    </style>
</head>

<body>
    <img class="page-decoration" src="{{ public_path('img/lha-reference-page-decoration.png') }}" alt="">
    <div class="report-header">
        <table>
            <tr>
                <td class="header-copy">
                    LAPORAN HASIL AUDIT (LHA) SNI ISO/IEC 17025:2017 TAHUN {{ $res['tahun'] }}<br>
                    FMMO-163-14.4.3.b-88.6<br>
                    REV 00
                </td>
                <td class="header-pln"><img src="{{ public_path('img/lha-reference-pln.png') }}" alt="PLN Nusantara Power"></td>
                <td class="header-right">
                    <span class="header-right-crop"><img src="{{ public_path('img/lha-reference-umro-kan-pjb.png') }}" alt="Unit Laboratory dan KAN"></span>
                </td>
            </tr>
        </table>
    </div>

    <div class="caption">Tabel A. 2 Resume Tindak Lanjut Hasil Audit Internal Laboratorium Kalibrasi UMRO (U-LAB) Bidang Mutu dan Teknik</div>
    <table class="detail-table">
        <colgroup>
            <col style="width:28%;">
            <col style="width:7%;">
            <col style="width:9%;">
            <col style="width:15%;">
            <col style="width:16%;">
            <col style="width:16%;">
            <col style="width:9%;">
        </colgroup>
        <thead>
            <tr>
                <th width="28%">Uraian Ketidaksesuaian</th>
                <th width="7%">Auditor</th>
                <th width="9%">Auditee</th>
                <th width="15%">Analisa Penyebab</th>
                <th width="16%">Tindakan Koreksi</th>
                <th width="16%">Tindakan Korektif</th>
                <th width="9%">Rencana<br>Penyelesaian</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($res['semuaTemuan'] as $temuan)
                <tr>
                    <td>{!! nl2br(e($temuan->uraianketidaksesuaian ?: '-')) !!}</td>
                    <td>{{ $temuan->auditor ?: '-' }}</td>
                    <td>{{ $temuan->namaauditee ?: '-' }}</td>
                    <td>{!! nl2br(e($temuan->analisapenyebab ?: '-')) !!}</td>
                    <td>{!! nl2br(e($temuan->tindakankoreksi ?: '-')) !!}</td>
                    <td>{!! nl2br(e($temuan->tindakankorektif ?: '-')) !!}</td>
                    <td>
                        {{ $temuan->rencanapenyelesaian
                            ? \Carbon\Carbon::parse($temuan->rencanapenyelesaian)->locale('id')->translatedFormat('d F Y')
                            : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
