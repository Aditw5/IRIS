<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sampul Laporan Hasil Audit {{ $res['tahun'] }}</title>
    <style>
        @font-face {
            font-family: 'Lato';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path('fonts/Lato/Lato-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Lato';
            font-style: normal;
            font-weight: 700;
            src: url('{{ public_path('fonts/Lato/Lato-Bold.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Lato Black';
            font-style: normal;
            font-weight: 900;
            src: url('{{ public_path('fonts/Lato/Lato-Black.ttf') }}') format('truetype');
        }
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 210mm; height: 297mm; margin: 0; padding: 0; }
        body { color: #000; background: #fff; font-family: 'Lato', sans-serif; }
        .cover { position: relative; width: 210mm; height: 297mm; overflow: hidden; }
        .brand-strip {
            position: absolute;
            z-index: 2;
            top: 0;
            left: 14mm;
            width: 182mm;
            height: 17mm;
            background: #fff;
        }
        .pln-logo { position: absolute; z-index: 3; top: 1.5mm; left: 18mm; width: 67mm; height: auto; }
        .right-logo-crop {
            position: absolute;
            z-index: 3;
            top: 1.5mm;
            right: 18mm;
            width: 45mm;
            height: 14.5mm;
            overflow: hidden;
        }
        .right-logo-crop img { width: 69.2mm; height: auto; }
        .cover-photo {
            position: absolute;
            z-index: 1;
            top: 17mm;
            left: 14mm;
            width: 182mm;
            height: 272mm;
        }
        .cover-copy {
            position: absolute;
            z-index: 4;
            top: 222mm;
            left: 17mm;
            width: 176mm;
            min-height: 62mm;
            padding: 3.5mm 4mm 2.5mm;
            background: rgba(255, 255, 255, 0.68);
            text-align: left;
        }
        .cover-copy h1 {
            margin: 0;
            font-family: 'Lato Black', 'Lato', sans-serif;
            font-size: 22pt;
            font-weight: 900;
            line-height: 1.05;
        }
        .cover-copy .standard {
            margin: 0 0 0.5mm;
            font-family: 'Lato Black', 'Lato', sans-serif;
            font-size: 22pt;
            font-weight: 900;
            line-height: 1.08;
        }
        .cover-copy .number {
            margin-bottom: 7mm;
            font-size: 13pt;
            font-weight: 700;
            line-height: 1.1;
        }
        .cover-copy .unit {
            font-size: 12.5pt;
            font-weight: 700;
            line-height: 1.25;
        }
        .cover-copy .date {
            margin-top: 0.5mm;
            font-size: 14pt;
            font-weight: 400;
            line-height: 1.1;
        }
    </style>
</head>

<body>
    @php
        $tanggalAuditList = $res['tanggalAudit']->map(function ($tanggal) {
            return \Carbon\Carbon::parse($tanggal)->locale('id');
        });
        $bulanAudit = $tanggalAuditList->map->format('Y-m')->unique();
        $tanggalAuditTeks = $bulanAudit->count() === 1 && $tanggalAuditList->isNotEmpty()
            ? $tanggalAuditList->map->format('d')->implode(', ') . ' ' . $tanggalAuditList->first()->translatedFormat('F Y')
            : $tanggalAuditList->map->translatedFormat('d F Y')->implode(', ');
    @endphp
    <section class="cover">
        <div class="brand-strip"></div>
        <img class="pln-logo" src="{{ public_path('img/lha-reference-pln.png') }}" alt="PLN Nusantara Power">
        <div class="right-logo-crop">
            <img src="{{ public_path('img/lha-reference-umro-kan-pjb.png') }}" alt="Unit Laboratory dan KAN">
        </div>
        <img class="cover-photo" src="{{ public_path('img/lha-cover-calibration.png') }}" alt="Kegiatan laboratorium kalibrasi">

        <div class="cover-copy">
            <h1>Laporan Hasil Audit (LHA)</h1>
            <div class="standard">SNI ISO/IEC 17025:2017</div>
            <div class="number">No: {{ $res['nomorLha'] }}</div>
            <div class="unit">
                LABORATORIUM KALIBRASI<br>
                WILAYAH JAKARTA DAN WILAYAH GRESIK<br>
                UNIT MAINTENANCE REPAIR DAN OVERHAUL
            </div>
            <div class="date">{{ $tanggalAuditTeks ?: '-' }}</div>
        </div>
    </section>
</body>

</html>
