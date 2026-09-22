<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Hasil Audit {{ $res['tahun'] }}</title>
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
        .chapter-page { padding-top: 2mm; }
        h1.chapter {
            margin: 0 0 12mm;
            color: #000;
            font-family: 'Lato Black', 'Lato', sans-serif;
            font-size: 20pt;
            font-weight: 900;
            line-height: 1.35;
            text-align: center;
        }
        h2 {
            margin: 6mm 0 2.5mm;
            color: #000;
            font-size: 11pt;
            font-weight: 700;
            page-break-after: avoid;
        }
        p { margin: 0 0 3mm; color: #000; text-align: justify; }
        ol, ul { margin: 1mm 0 4mm 7mm; padding-left: 5mm; }
        li { margin: 0 0 1.8mm; color: #000; text-align: justify; }
        .front-title {
            margin: 18mm 0 15mm;
            color: #000;
            font-family: 'Lato Black', 'Lato', sans-serif;
            font-size: 22pt;
            font-weight: 900;
            text-align: center;
        }
        .toc-line { width: 100%; border-collapse: collapse; table-layout: auto; font-size: 10pt; }
        .toc-line td { padding: 1.3mm 0; color: #000; vertical-align: baseline; }
        .toc-line .label { width: 1%; padding-right: 1.2mm; white-space: nowrap; }
        .toc-line .leader {
            width: 99%;
            max-width: 0;
            overflow: hidden;
            color: #000;
            font-size: 9pt;
            line-height: 1;
            letter-spacing: 0.2pt;
            white-space: nowrap;
        }
        .toc-line .leader::after { content: '................................................................................................................................................................................................................................................................'; }
        .toc-line .page-number { width: 1%; padding-left: 1.5mm; text-align: right; white-space: nowrap; }
        .toc-line.indented .label { padding-left: 7mm; }
        .toc-line.section td { padding-top: 3mm; font-weight: 700; }
        .toc-line.compact { font-size: 8pt; }
        .toc-line.compact .leader { min-width: 10mm; }
        .figure { margin: 5mm 0 4mm; text-align: center; page-break-inside: avoid; }
        .figure img { width: 100%; height: auto; }
        .caption { margin: 2mm 0 4mm; color: #000; font-size: 9pt; font-weight: 700; text-align: center; }
        .category-list { margin-top: 1mm; }
        .category-list li { margin-bottom: 2.5mm; }
        .category-list b { display: block; }
        .summary-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8pt; }
        .summary-table thead { display: table-header-group; }
        .summary-table tr { page-break-inside: avoid; }
        .summary-table th, .summary-table td {
            padding: 2mm 1.5mm;
            border: 1px solid #000;
            color: #000;
            vertical-align: middle;
        }
        .summary-table th { font-weight: 700; text-align: center; }
        .summary-table td.center { text-align: center; }
        .summary-table .total td { font-weight: 700; }
        .small-copy { font-size: 9.3pt; line-height: 1.47; }
        .overview { font-size: 9.4pt; line-height: 1.45; }
        .overview h1.chapter { margin-bottom: 8mm; }
        .overview h2 { margin-top: 3mm; }
        .overview .figure { margin: 3mm 0 2mm; }
        .overview .figure img { width: 90%; }
        .overview .caption { margin: 1mm 0 2mm; }
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
        <h1 class="front-title">DAFTAR ISI</h1>
        <table class="toc-line"><tr><td class="label">Daftar Isi</td><td class="leader"></td><td class="page-number">ii</td></tr></table>
        <table class="toc-line"><tr><td class="label">Daftar Gambar</td><td class="leader"></td><td class="page-number">iii</td></tr></table>
        <table class="toc-line"><tr><td class="label">Daftar Tabel</td><td class="leader"></td><td class="page-number">iv</td></tr></table>
        <table class="toc-line section"><tr><td class="label">BAB I PENDAHULUAN</td><td class="leader"></td><td class="page-number">1</td></tr></table>
        <table class="toc-line indented"><tr><td class="label">1.1&nbsp;&nbsp;&nbsp;&nbsp;LANDASAN AUDIT</td><td class="leader"></td><td class="page-number">1</td></tr></table>
        <table class="toc-line indented"><tr><td class="label">1.2&nbsp;&nbsp;&nbsp;&nbsp;TUJUAN AUDIT</td><td class="leader"></td><td class="page-number">1</td></tr></table>
        <table class="toc-line indented"><tr><td class="label">1.3&nbsp;&nbsp;&nbsp;&nbsp;LINGKUP AUDIT</td><td class="leader"></td><td class="page-number">2</td></tr></table>
        <table class="toc-line indented"><tr><td class="label">1.4&nbsp;&nbsp;&nbsp;&nbsp;PELAKSANAAN AUDIT</td><td class="leader"></td><td class="page-number">2</td></tr></table>
        <table class="toc-line indented"><tr><td class="label">1.5&nbsp;&nbsp;&nbsp;&nbsp;METODE AUDIT</td><td class="leader"></td><td class="page-number">2</td></tr></table>
        <table class="toc-line section"><tr><td class="label">BAB II URAIAN HASIL AUDIT</td><td class="leader"></td><td class="page-number">3</td></tr></table>
        <table class="toc-line indented"><tr><td class="label">A.&nbsp;&nbsp;&nbsp;&nbsp;GAMBARAN UMUM LABORATORIUM KALIBRASI UMRO (U-LAB)</td><td class="leader"></td><td class="page-number">3</td></tr></table>
        <table class="toc-line indented"><tr><td class="label">B.&nbsp;&nbsp;&nbsp;&nbsp;HASIL AUDIT INTERNAL PERIODE {{ $res['tahun'] }}</td><td class="leader"></td><td class="page-number">4</td></tr></table>
        <table class="toc-line section"><tr><td class="label">BAB III KESIMPULAN</td><td class="leader"></td><td class="page-number">{{ $res['tocBab3'] ?? 13 }}</td></tr></table>
        <table class="toc-line section"><tr><td class="label">BAB IV PENUTUP</td><td class="leader"></td><td class="page-number">{{ $res['tocBab4'] ?? 14 }}</td></tr></table>
    </section>

    <section class="page-after">
        <h1 class="front-title">DAFTAR GAMBAR</h1>
        <table class="toc-line">
            <tr><td class="label">Gambar A. 1 Struktur Tim Laboratorium Kalibrasi UMRO (U-LAB)</td><td class="leader"></td><td class="page-number">3</td></tr>
        </table>
    </section>

    <section class="page-after">
        <h1 class="front-title">DAFTAR TABEL</h1>
        <table class="toc-line">
            <tr><td class="label">Tabel A. 1 Rekap Hasil Audit Internal Laboratorium Kalibrasi UMRO (U-LAB)</td><td class="leader"></td><td class="page-number">4</td></tr>
        </table>
        <table class="toc-line compact">
            <tr><td class="label">Tabel A. 2 Resume Tindak Lanjut Hasil Audit Internal Laboratorium Kalibrasi (U-LAB) Bidang Mutu dan Teknik</td><td class="leader"></td><td class="page-number">6</td></tr>
        </table>
    </section>

    <section class="page-after chapter-page small-copy">
        <h1 class="chapter">BAB I<br>PENDAHULUAN</h1>

        <h2>1.1 LANDASAN AUDIT</h2>
        <ol>
            <li>Program Kerja Audit dan Kepatuhan Tahunan (PKAKT) PT PLN UMRO Tahun {{ $res['tahun'] }}.</li>
            <li>Program Audit Internal ISO 17025:2017 Laboratorium Kalibrasi UMRO Tahun {{ $res['tahun'] }}.</li>
            <li>Surat Tugas Senior Manager PT PLN NP Jasa Inspeksi, Repair dan Ekspertise melalui nota dinas 1772/LIT.02.012024 tanggal 29 November 2024 perihal Pelaksanaan Audit Internal Laboratorium Kalibrasi LK-284-IDN</li>
            <li>Sertifikat akreditasi SNI ISO/IEC 17025:2017 PT PLN NP UMRO LK-284-IDN oleh KAN tanggal 25 September 2024 pada ruang lingkup Akreditasi Lokasi Satu dan Lokasi Dua.</li>
        </ol>

        <h2>1.2 TUJUAN AUDIT</h2>
        <p>Adapun tujuan dari Audit Internal ISO 17025:2017 ini adalah sebagai berikut:</p>
        <ol>
            <li>Memastikan manajemen mendukung dan memfasilitasi kegiatan laboratorium kalibrasi sesuai dengan standar SNI ISO/IEC 17025:2017 dan selaras dengan tujuan dan sasaran yang ditetapkan PT PLN NP UMRO.</li>
            <li>Menjamin kebijakan, program, sasaran maupun proses sesuai kondisi dan kebutuhan dalam pemenuhan persyaratan klausul standar SNI ISO/IEC 17025:2017.</li>
            <li>Memastikan persyaratan standar SNI ISO/IEC 17025:2017 yang telah ditetapkan, telah diimplementasikan, dikelola dan dipelihara dengan baik sesuai dengan ruang lingkup laboratorium kalibrasi UMRO (U-LAB).</li>
            <li>Memastikan tindak lanjut perbaikan temuan ketidaksesuaian terhadap pemenuhan persyaratan standar pada periode sebelumnya telah dilakukan.</li>
        </ol>

        <h2>1.3 LINGKUP AUDIT</h2>
        <p>Ruang lingkup Audit Internal SNI ISO/IEC 17025:2017 meliputi proses atas aktivitas kegiatan operasional yang dilakukan pada Laboratorium Kalibrasi UMRO (U-LAB) baik pada bidang Mutu maupun bidang Teknik pada ruang lingkup kalibrasi kelistrikan, tekanan dan suhu.</p>

        <h2>1.4 PELAKSANAAN AUDIT</h2>
        <p>Audit Internal dilaksanakan melalui Audit Teknik dan Audit Mutu melalui tatap muka (on site dan secara offline untuk keperluan witness) per Wilayah Jakarta dan Gresik.</p>

        <h2>1.5 METODE AUDIT</h2>
        <p>Metode Rotasi Auditor dengan tujuan untuk menghasilkan kualitas dan menegakkan ketidakberpihakan antara Laboratorium Kalibrasi KAN-284 Wilayah Jakarta dan Gresik. Auditor Eksternal serta Auditor Observer di luar BSO Laboratorium Kalibrasi di tugaskan untuk melaksanakan proses Audit. Adapun Auditor pada Wilayah Jakarta dan Gresik telah melakukan penandatanganan Pakta Integritas Auditor sesuai FMMO- 303-13.1.5.h-08.</p>
    </section>

    <section class="page-after chapter-page overview">
        <h1 class="chapter">BAB II<br>URAIAN HASIL AUDIT</h1>
        <h2>A. GAMBARAN UMUM LABORATORIUM KALIBRASI UMRO (U-LAB)</h2>
        <p>PT PJB melalui Peraturan Direksi No. Perdir No 0084.P.019.DIR.2022 tanggal 02 Desember 2022 tentang Peraturan Pelaksanan Susunan Organisasi Unit Maintenance, Repair &amp; Overhaul sebagaimana dapat ditunjukkan dalam gambar A.1. UMRO memiliki fungsi Non Mechanical Workshop yang memiliki kegiatan operasional kalibrasi dengan mengacu pada Standar SNI ISO/IEC 17025:2017. Fungsi Non Mechanical Workshop bertanggung jawab ke General Manager UMRO melalui Manajer Repair dan Senior Manajer JIRE (Jasa Inspeksi, Repair &amp; Expertise).</p>

        <div class="figure">
            <img src="{{ public_path('img/struktur-organisasi-umro.png') }}" alt="Struktur Organisasi UMRO">
            <div class="caption">Gambar A. 1 Struktur Organisasi UMRO</div>
        </div>

        <p>Fungsi Non Mechanical Workshop dipimpin oleh seorang Assitant Manager dengan dibantu oleh beberapa bawahannya untuk memperlancar kegiatan operasional laboratorium kalibrasi setiap hari.</p>
        <p>Salah satu tugas dari fungsi Non Mechanical Workshop adalah untuk melakukan kegiatan kalibrasi dengan ruang lingkup kelistrikan, suhu dan tekanan maupun dimensi serta menerbitkan sertifikat hasil kalibrasi dalam rangka mendukung pemeliharaan pembangkit listrik di lingkungan PLN.</p>
    </section>

    <section>
        <h2>B. HASIL AUDIT INTERNAL PERIODE {{ $res['tahun'] }}</h2>
        <p>Hasil Audit Internal Laboratorium Kalibrasi UMRO (U-LAB) atas implementasi SNI ISO/IEC 17025:2017 selanjutnya dituangkan dalam form FMMO- 303-13.1.5.h-05 mengenai Laporan Ringkas dan Lembar Temuan Ketidaksesuaian dengan disertai tiga kategori hasil audit, yaitu:</p>
        <ol class="category-list">
            <li><b>Major</b>Bersifat menimbulkan dampak serius terhadap pencapaian sasaran mutu, efektivitas sistem manajemen dan mengancam kredibilitas laboratorium. Penyebab temuan major antara lain tidak terpenuhinya peraturan perundangan, tidak adanya upaya tindakan perbaikan atas ketidaksesuaian, dan kegiatan operasional kalibrasi tidak dapat dijalankan.</li>
            <li><b>Minor</b>Bersifat sementara dan tidak menimbulkan dampak serius terhadap pencapaian sasaran mutu. Umumnya bersifat administrasi dan disebabkan oleh human error.</li>
            <li><b>Saran/ Rekomendasi</b>Bersifat peluang yang dapat berpotensi meningkatkan kinerja sistem dalam laboratorium kalibrasi PTPLN NP UMRO.</li>
        </ol>
        <p>Berdasarkan hasil Audit internal atas implementasi SNI ISO/IEC 17025:2017 pada Laboratorium Kalibrasi UMRO (U-LAB) dapat diketahui bahwa masih terdapat beberapa ketidaksesuaian yang perlu untuk ditindaklanjuti oleh masing-masing penanggung jawab. Rekapitulasi atas hasil audit disajikan sebagaimana pada tabel berikut ini:</p>

        <div class="caption">Tabel A. 1 Rekap Hasil Audit Internal Laboratorium Kalibrasi UMRO (U-LAB)</div>
        <table class="summary-table">
            <colgroup>
                <col style="width:5%;"><col style="width:28%;"><col style="width:19%;">
                <col style="width:11%;"><col style="width:11%;"><col style="width:11%;"><col style="width:15%;">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2">No</th><th rowspan="2">Lingkup Audit</th><th rowspan="2">Lokasi</th>
                    <th colspan="3">Temuan</th><th rowspan="2">Jumlah</th>
                </tr>
                <tr><th>Kategori 1</th><th>Kategori 2</th><th>Kategori 3</th></tr>
            </thead>
            <tbody>
                @foreach ($res['audits'] as $audit)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td><td>{{ $audit->lingkup }}</td><td>{{ $audit->lokasi }}</td>
                        <td class="center">{{ $audit->kategori1 }}</td><td class="center">{{ $audit->kategori2 }}</td>
                        <td class="center">{{ $audit->kategori3 }}</td><td class="center">{{ $audit->jumlahtemuan }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3" style="text-align:center;">Jumlah</td>
                    <td class="center">{{ $res['ringkasan']['kategori1'] }}</td><td class="center">{{ $res['ringkasan']['kategori2'] }}</td>
                    <td class="center">{{ $res['ringkasan']['kategori3'] }}</td><td class="center">{{ $res['ringkasan']['jumlahTemuan'] }}</td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top:5mm;">Adapun untuk detail rincian tindak lanjut hasil audit yang dituangkan dalam form PTPP dapat dilihat pada tabel resume berikut ini:</p>
    </section>
</body>

</html>
