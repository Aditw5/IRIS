<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Pakta Integritas Auditor</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16px 42px 28px 42px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            color: #080808;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
        }

        .header-table,
        .header-center,
        .header-meta {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .header-center td,
        .header-meta td {
            border: 1px solid #aaa;
        }

        .logo-cell {
            width: 16%;
            text-align: center;
            vertical-align: middle;
        }

        .center-cell {
            width: 68%;
            padding: 0;
        }

        .header-center td {
            padding: 2px 5px;
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            color: #777;
        }

        .header-meta td {
            padding: 2px 4px;
            text-align: center;
            font-size: 6.5pt;
            font-weight: normal;
            letter-spacing: .5px;
            color: #777;
        }

        .header-meta td:nth-child(1) { width: 34%; }
        .header-meta td:nth-child(2) { width: 24%; }
        .header-meta td:nth-child(3) { width: 27%; }
        .header-meta td:nth-child(4) { width: 15%; }

        .content {
            margin: 34px 54px 0 54px;
            line-height: 1.45;
        }

        .lead {
            margin-bottom: 20px;
            font-weight: bold;
        }

        .identity-table {
            width: 100%;
            margin-bottom: 17px;
            border-collapse: collapse;
        }

        .identity-table td {
            padding: 4px 0;
            vertical-align: top;
            font-weight: bold;
        }

        .identity-table td:first-child { width: 29%; }
        .identity-table td:nth-child(2) { width: 4%; }

        .assignment {
            margin: 0 0 22px 0;
            font-weight: bold;
        }

        .declaration {
            margin: 0 0 3px 0;
        }

        ol {
            margin: 3px 0 18px 28px;
            padding-left: 17px;
        }

        li {
            margin-bottom: 7px;
            padding-left: 7px;
            text-align: justify;
        }

        .closing {
            margin: 0 0 22px 0;
            text-align: justify;
        }

        .date-line {
            margin-bottom: 2px;
        }

        .signature-block {
            width: 230px;
            margin-top: 3px;
            text-align: left;
        }

        .signature-block img {
            display: block;
            width: 145px;
            height: 76px;
            object-fit: contain;
            margin: 0 0 0 -4px;
        }

        .signature-name {
            display: inline-block;
            min-width: 165px;
            border-bottom: 1px dotted #111;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('img/pln.png') }}" alt="PLN Nusantara Power" style="width:92px; height:auto;">
            </td>
            <td class="center-cell">
                <table class="header-center">
                    <tr><td>PT PLN NUSANTARA POWER</td></tr>
                    <tr><td>UNIT MAINTENANCE REPAIR &amp; OVERHAUL</td></tr>
                    <tr><td>PLN NP INTEGRATED MANAGEMENT SYSTEM</td></tr>
                    <tr><td style="font-size:13pt;">PAKTA INTEGRITAS AUDITOR</td></tr>
                </table>
                <table class="header-meta">
                    <tr>
                        <td>FMMO-163-14.4.3.b-88.8</td>
                        <td>Revisi: 00</td>
                        <td>Terbit: 02 Desember 2024</td>
                        <td>Halaman: 1 dari 1</td>
                    </tr>
                </table>
            </td>
            <td class="logo-cell">
                <img src="{{ public_path('img/UMRO.png') }}" alt="UMRO Laboratory" style="width:85px; height:auto;">
            </td>
        </tr>
    </table>

    <div class="content">
        <div class="lead">Yang bertanda tangan di bawah ini,</div>

        <table class="identity-table">
            <tr><td>Nama</td><td>:</td><td>{{ $pakta->nama }}</td></tr>
            <tr><td>NID</td><td>:</td><td>{{ $pakta->nid ?: '-' }}</td></tr>
            <tr><td>Jabatan</td><td>:</td><td>{{ $pakta->jabatan ?: '-' }}</td></tr>
        </table>

        <div class="assignment">Bertugas sebagai Auditor pada Laboratorium Kalibrasi KAN 284 IDN</div>

        <div class="declaration">Dengan ini menyatakan bahwa dalam melaksanakan tugas Audit, saya:</div>
        <ol>
            <li>Bersikap adil, bekerja dengan obyektif dan bertanggung jawab serta menjunjung tinggi kejujuran;</li>
            <li>Menjaga kerahasiaan data dan informasi yang diperoleh serta hasil pelaksanaan proses audit.</li>
            <li>Tidak melakukan perjanjian dan/atau kesepakatan sepihak atau bersama-sama dengan Laboratorium Kalibrasi yang diaudit baik secara individual maupun tim yang mengakibatkan tidak obyektifnya hasil audit dan menimbulkan keberpihakan;</li>
            <li>Tidak menerima apa pun dari Laboratorium Kalibrasi dan pihak lain baik secara tim maupun individual sehingga mempengaruhi hasil Audit; dan</li>
            <li>Mematuhi persyaratan umum yang berlaku pada ISO/IEC 17025:2017.</li>
        </ol>

        <div class="closing">
            Demikian pernyataan ini saya buat dengan sesungguhnya dan penuh rasa tanggung jawab dan apabila saya melanggar ketentuan-ketentuan pada butir 1 s.d 5 di atas, saya siap menerima sanksi sesuai dengan peraturan yang berlaku.
        </div>

        <div class="date-line">
            Pernyataan ini dibuat pada tanggal
            {{ \Carbon\Carbon::parse($pakta->tanggal)->translatedFormat('d F Y') }}
            di {{ $pakta->lokasi }}
        </div>
        <div>Auditor</div>

        <div class="signature-block">
            <img src="{{ $pakta->tandatangan }}" alt="Tanda tangan auditor">
            <span class="signature-name">{{ $pakta->nama }}</span>
        </div>
    </div>
</body>

</html>
