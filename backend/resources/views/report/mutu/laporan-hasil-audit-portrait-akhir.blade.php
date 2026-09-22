<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Kesimpulan Laporan Hasil Audit {{ $res['tahun'] }}</title>
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
        @font-face {
            font-family: 'Lato Black';
            font-style: normal;
            font-weight: 900;
            src: url('{{ public_path('fonts/Lato/Lato-Black.ttf') }}') format('truetype');
        }
        @page { size: A4 portrait; margin: 29mm 20mm 28mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #000;
            font-family: 'Lato', sans-serif;
            font-size: 10pt;
            line-height: 1.55;
        }
        .page-decoration {
            display: none;
            position: fixed;
            z-index: -1000;
            top: -29mm;
            left: -20mm;
            width: 210mm;
            height: 297mm;
        }
        .report-header {
            display: none;
            position: fixed;
            top: -23.5mm;
            left: 0;
            right: 0;
            height: 17mm;
        }
        .report-header table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .report-header td { padding: 0; vertical-align: middle; }
        .header-copy {
            width: 47%;
            color: #7fa7b4;
            font-size: 6.8pt;
            font-style: italic;
            font-weight: 700;
            line-height: 1.25;
        }
        .header-pln { width: 31%; text-align: center; }
        .header-pln img { width: 42mm; height: auto; }
        .header-right { width: 22%; text-align: right; }
        .header-right-crop {
            display: inline-block;
            width: 36mm;
            height: 12mm;
            overflow: hidden;
            text-align: left;
        }
        .header-right-crop img { width: 55.4mm; height: auto; }
        .page-after { page-break-after: always; }
        h1.chapter {
            margin: 2mm 0 12mm;
            color: #000;
            font-family: 'Lato Black', 'Lato', sans-serif;
            font-size: 20pt;
            font-weight: 900;
            line-height: 1.35;
            text-align: center;
        }
        p { margin: 0 0 4mm; color: #000; text-align: justify; }
        ul { margin: 2mm 0 0 7mm; padding-left: 5mm; }
        li { margin: 0 0 3mm; color: #000; text-align: justify; }
        .closing-copy { margin-bottom: 6mm; }
        .signature-date { margin: 4mm 0 7mm; text-align: center; }
        .signature-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 10pt; }
        .signature-table td { width: 50%; padding: 0 6mm; color: #000; text-align: center; vertical-align: top; }
        .role-title { min-height: 14mm; font-weight: 700; line-height: 1.35; }
        .signature-space { height: 31mm; }
        .signature-name { font-weight: 700; text-decoration: underline; text-transform: uppercase; }
        .signature-nid { margin-top: 1.5mm; font-weight: 700; }
        .single-signature {
            width: 62%;
            margin: 9mm auto 0;
            color: #000;
            text-align: center;
            page-break-inside: avoid;
        }
        .single-signature .signature-space { height: 28mm; }
        .approval { margin-top: 10mm; }
        .approval .signature-space { height: 29mm; }
        .penutup { font-size: 9.5pt; line-height: 1.45; }
        .penutup h1.chapter { margin-bottom: 4mm; font-size: 16pt; }
        .penutup .closing-copy { margin-bottom: 3mm; }
        .penutup .signature-date { margin: 2mm 0 4mm; }
        .penutup .signature-space { height: 20mm; }
        .penutup .single-signature { margin-top: 3mm; }
        .penutup .single-signature .signature-space { height: 17mm; }
        .penutup .approval { margin-top: 4mm; }
        .penutup .approval .signature-space { height: 17mm; }
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

    <section class="page-after">
        <h1 class="chapter">BAB III<br>KESIMPULAN</h1>
        <p>Dari hasil pelaksanaan Audit Internal atas implementasi SNI ISO/IEC 17025:2017 pada Laboratorium Kalibrasi PT. PLN NP UMRO (U-LAB), dapat disimpulkan bahwa secara umum implementasi SNI ISO/IEC 17025:2017 pada Laboratorium Kalibrasi PT PLN NP UMRO (U-LAB) sudah memenuhi kriteria, standar, acuan yang dipersyaratkan. Namun masih terdapat beberapa hal yang masih perlu untuk diperbaiki dan ditingkatkan lagi diantaranya yaitu:</p>
        <ul>
            <li>Laboratorium harus cepat beradaptasi terhadap&nbsp; transformasi struktur organisasi induk sehingga dapat menjamin mutu laboratorium sesuai SNI ISO/IEC 17025:2017.</li>
            <li>Kosistensi terkait validasi penyelia dan pengisian formulir yang kosisten perlu ditingkatkan.</li>
            <li>Konsistensi perubahan dokumen ke format dokumen merger termutakhir.</li>
            <li>Pemahaman personel laboratorium dalam proses bisnis kalibrasi masih perlu ditingkatkan lagi.</li>
        </ul>
    </section>

    <section class="penutup">
        <h1 class="chapter">BAB IV<br>PENUTUP</h1>
        <p class="closing-copy">Demikian Laporan Hasil Audit (LHA) implementasi SNI ISO/IEC 17025:2017 pada Laboratorium Kalibrasi UMRO (U-LAB) ini dibuat agar dapat dipergunakan sebagaimana mestinya dan untuk ditindaklanjuti oleh Manajemen Laboratorium Kalibrasi UMRO (U-LAB).</p>

        <div class="signature-date">Jakarta, {{ \Carbon\Carbon::parse($res['tanggalPenetapan'])->locale('id')->translatedFormat('d F Y') }}</div>

        <table class="signature-table">
            <tr>
                <td><div class="role-title">Assistant Manajer<br>Non Mechanical Workshop</div></td>
                <td><div class="role-title">Lead Auditor,</div></td>
            </tr>
            <tr><td class="signature-space">&nbsp;</td><td class="signature-space">&nbsp;</td></tr>
            <tr>
                <td>
                    <div class="signature-name">{{ $res['asman']->namapegawai ?? '-' }}</div>
                    <div class="signature-nid">{{ $res['asman']->nid ?? '-' }}</div>
                </td>
                <td>
                    <div class="signature-name">{{ $res['leadAuditor']->namapegawai ?? '-' }}</div>
                    <div class="signature-nid">{{ $res['leadAuditor']->nid ?? '-' }}</div>
                </td>
            </tr>
        </table>

        <div class="single-signature">
            <div class="role-title">Diketahui oleh,<br>Manajer Repair</div>
            <div class="signature-space">&nbsp;</div>
            <div class="signature-name">{{ $res['managerRepair']->namapegawai ?? '-' }}</div>
            <div class="signature-nid">{{ $res['managerRepair']->nid ?? '-' }}</div>
        </div>

        <div class="single-signature approval">
            <div class="role-title">Disetujui oleh,<br>Senior Manajer Jasa Inspeksi<br>Repair &amp; Expertise</div>
            <div class="signature-space">&nbsp;</div>
            <div class="signature-name">{{ $res['seniorManagerJire']->namapegawai ?? '-' }}</div>
            <div class="signature-nid">{{ $res['seniorManagerJire']->nid ?? '-' }}</div>
        </div>
    </section>
</body>

</html>
