<!DOCTYPE html>
<html>

<head>
    <title>Sertifikat Lembar Kerja</title>
    <style>
        @page {
            margin-top: 110px;
            margin-bottom: 60px;
            margin-left: 35px;
            margin-right: 35px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .pdf-header {
            position: fixed;
            top: -90px;
            /* negatif dari margin-top */
            left: 0;
            right: 0;
            height: 100px;
            width: 100%;
            z-index: 10;
        }

        .pdf-footer {
            position: fixed;
            bottom: -50px;
            /* negatif dari margin-bottom */
            left: 0;
            right: 0;
            height: 70px;
            width: 100%;
            z-index: 10;
        }

        .pdf-content {
            /* konten mengikuti margin @page */
        }

        .page-break {
            page-break-before: always;
        }

        /* DomPDF: bantu jaga tabel tidak pecah aneh */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr,
        td,
        th {
            page-break-inside: avoid;
            vertical-align: middle;
        }

        /* value + unit rapat (yang kamu mau) */
        .cell-valunit {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
            gap: 3px;
            white-space: nowrap;
            line-height: 1;
        }

        .cell-valunit .val {
            display: inline-block;
            min-width: 0;
            text-align: right;
        }

        .cell-valunit .unit {
            display: inline-block;
            min-width: 0;
            text-align: left;
        }
    </style>
</head>

<body>

    {{-- @if ($res['alat']->setujuilembarkerjamanager == true) --}}
    @if ($res['alat']->statuskanfk == 1)
        <div style="margin-top: -90px; margin-bottom: 20px;">
            <img src="{{ public_path('img/header_cover_KAN_002.png') }}" alt="Header Halaman Pertama"
                style="width:100%; height:auto; display:block;">
            <div style="font-size: 7pt; color:#000000; margin-top: -17px; text-align:right">
                FMMO-163-14.4.3.b-78.1
            </div>
        </div>
    @endif
    @if ($res['alat']->statuskanfk == 2)
        <div style="margin-top: -90px; margin-bottom: 20px;">
            <img src="{{ public_path('img/header_cover_NON_KAN.png') }}" alt="Header Halaman Pertama"
                style="width:100%; height:auto; display:block;">
            <div style="font-size: 7pt; color:#000000; margin-top: -17px; text-align:right">
                FMMO-163-14.4.3.b-78.2
            </div>
        </div>
    @endif
    {{-- @endif --}}



    <div class="pdf-footer">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:100%;text-align:center;">
                    <img src="{{ public_path('img/footer_all.png') }}" alt="Footer Surat PLN"
                        style="height:110%; width: 100%">
                </td>
            </tr>
        </table>
    </div>

    {{-- {{ dd($res) }} --}}


    <div class="pdf-content">
        <table width="100%" style="margin-top: -35px; " border="0">
            <tr>
                <td width="35%">
                </td>
                <td width="30%">
                    <img src="{{ public_path('img/pln.png') }}" alt="Footer Surat PLN" style="height:5%; width: 95%">
                </td>
                <td width="35%">
                </td>
            </tr>
        </table>
        <table width="100%" style="margin-top: -5px; " border="0">
            <tr>
                <td width="35%">

                </td>
                <td align="center" width="30%" style="line-height: 1.1;">
                    <div style="font-size: 14pt; color:#000000; font-weight: bold; margin-bottom: 2px;">
                        Sertifikat Kalibrasi
                    </div>
                    <div
                        style="font-size: 9pt; color:#000000; font-style: italic; font-weight: bold; margin-bottom: 2px;">
                        Calibration Certificate
                    </div>
                    <div style="font-size: 7pt; color:#000000; margin-bottom: 1px;">
                        {{-- Nomor Sertifikat : {{ $res['alat']->nosertifikat }} --}}
                        Nomor Sertifikat : {{ $res['alat']->nosertifikatamandemen ?? $res['alat']->nosertifikat }}
                    </div>
                    <div style="font-size: 7pt; color:#000000;">
                        No Order : {{ $res['alat']->noorderalat }}
                    </div>
                </td>
                <td width="35%">
                </td>
            </tr>
        </table>


        <table width="100%" style="padding-left: 5px; margin-top: 20px; ">
            <tr>
                <td width="30%">
                    <div style="font-size: 9pt; color:#000000; font-weight: bold; margin-bottom: 1px;">
                        Identitas Alat
                    </div>
                    <div style="font-size: 8pt; color:#000000; font-style: italic;">
                        Instrument Details
                    </div>
                </td>
            </tr>
        </table>
        <table width="100%" style="padding-left: 45px; margin-top: 5px; border-collapse: collapse;">
            <tr style="line-height: 1.1;">
                <td width="10%" style="padding-bottom: 2px;">
                    <span style="font-size: 9pt; color: #000000;"><b>Nama Alat Ukur</b></span><br>
                    <span style="font-size: 8pt; color: #000000; font-style: italic;">Instrument</span>
                </td>
                <td width="1%" style="padding-bottom: 2px;">:</td>
                <td width="30%" style="padding-bottom: 2px;">
                    <span style="font-size: 9pt; color: #000000;">{{ $res['alat']->namaproduk }}</span>
                </td>
            </tr>
            <tr style="line-height: 1.1;">
                <td style="padding-bottom: 2px;">
                    <span style="font-size: 9pt; color: #000000;"><b>Merk Pabrik / Tipe</b></span><br>
                    <span style="font-size: 8pt; color: #000000; font-style: italic;">Manufacture/ type</span>
                </td>
                <td style="padding-bottom: 2px;">:</td>
                <td style="padding-bottom: 2px;">
                    <span style="font-size: 9pt; color: #000000;">{{ $res['alat']->namamerk }} /
                        {{ $res['alat']->namatipe }}</span>
                </td>
            </tr>
            <tr style="line-height: 1.1;">
                <td style="padding-bottom: 2px;">
                    <span style="font-size: 9pt; color: #000000;"><b>Nomor Seri</b></span><br>
                    <span style="font-size: 8pt; color: #000000; font-style: italic;">Serial number</span>
                </td>
                <td style="padding-bottom: 2px;">:</td>
                <td style="padding-bottom: 2px;">
                    <span style="font-size: 9pt; color: #000000;">{{ $res['alat']->namaserialnumber }}</span>
                </td>
            </tr>
            {{-- <tr style="line-height: 1.1;">
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;"><b>Identifikasi lain</b></span><br>
                        <span style="font-size: 8pt; color: #000000; font-style: italic;">Other identification</span>
                    </td>
                    <td style="padding-bottom: 2px;">:</td>
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;">-</span>
                    </td>
                </tr> --}}
        </table>
        @if ($res['halamanPertama'] == true && $res['alat']->setujuilembarkerjamanager == true)
            <table border="0" width="100%" style="padding-left: 5px; margin-top: 10px; ">
                <tr>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>
                                Idetitas Pemilik </b>
                        </span><br>
                        <span style="font-size: 8pt;color:#000000; font-style: italic;">
                            Owner's Identification
                        </span>
                    </td>
                </tr>
            </table>
            <table width="100%" style="padding-left: 45px; margin-top: 5px; border-collapse: collapse;">
                <tr style="line-height: 1.1;">
                    <td width="10%" style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;"><b>Nama</b></span><br>
                        <span style="font-size: 8pt; color: #000000; font-style: italic;">Designation</span>
                    </td>
                    <td width="1%" style="padding-bottom: 2px;">:</td>
                    <td width="30%" style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;">{{ $res['identitas']->namaperusahaan }}</span>
                    </td>
                </tr>
                <tr style="line-height: 1.1;">
                    <td width="10%" style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;"><b> Alamat</b></span><br>
                        <span style="font-size: 8pt; color: #000000; font-style: italic;">Address</span>
                    </td>
                    <td width="1%" style="padding-bottom: 2px;">:</td>
                    <td width="30%" style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;">{{ $res['identitas']->alamatktr }}</span>
                    </td>
                </tr>
            </table>

            <table border="0" width="100%" style="padding-left: 5px; margin-top: 10px; ">
                <tr>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>
                                Pengesahan </b>
                        </span><br>
                        <span style="font-size: 8pt;color:#000000; font-style: italic;">
                            Authorization
                        </span>
                    </td>
                </tr>
            </table>
            <table width="100%" style="margin-top: -10px;">
                <tr>
                    <!-- Kolom Kiri -->
                    <td width="60%" valign="top" style="padding-left: 15px;">
                        <!-- Bisa isi Pengesahan jika diperlukan -->
                    </td>

                    <!-- Kolom Kanan Tengah -->
                    <td width="40%" align="center">
                        <table cellspacing="0" cellpadding="0" style="line-height: 1.1;">
                            <tr>
                                <td style="font-size: 9pt; font-weight: bold; color:#000000;">
                                    Sertifikat ini terdiri dari
                                </td>
                                <td style="font-size: 9pt; font-weight: bold; color:#000000;" align="center"
                                    width="30">
                                    {{ $jumlahHalaman ?? '...' }}
                                </td>
                                <td style="font-size: 9pt; font-weight: bold; color:#000000;">
                                    Halaman
                                </td>
                            </tr>
                            <tr>
                                <td style="font-size: 8pt; font-style: italic; color:#000000;">
                                    This certificate consist of
                                </td>
                                <td style="font-size: 8pt; font-style: italic; color:#000000;" align="center">
                                    {{ $jumlahHalaman ?? '...' }}
                                </td>
                                <td style="font-size: 8pt; font-style: italic; color:#000000;">
                                    Pages
                                </td>
                            </tr>
                            <tr>
                                <td style="font-size: 9pt; font-weight: bold; color:#000000;">
                                    Diterbitkan tanggal
                                </td>
                                <td></td>
                                <td style="font-size: 9pt; color:#000000;">
                                    {{ \Carbon\Carbon::parse($res['alat']->tglsetujumanagerlembarkerja)->locale('id')->isoFormat('D MMMM Y') }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-size: 8pt; font-style: italic; color:#000000;">
                                    Date of Issue
                                </td>
                                <td></td>
                                <td style="font-size: 8pt; color:#000000;">
                                    {{ \Carbon\Carbon::parse($res['alat']->tglsetujumanagerlembarkerja)->format('F d, Y') }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>



            <table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding-top: 1px">
                <tbody style="font-size: 11pt">
                    <tr>
                        <td width="30%">
                        </td>
                        <td width="30%" class="text-center">
                        </td>
                        <td align="center" width="40%" class="text-center">
                            <span style="font-size: 9pt;color:#000000"><b>
                                    Manager Repair </b>
                            </span><br>
                            <span style="font-size: 8pt;color:#000000; font-style: italic;">
                                Manager Repair
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        </td>
                        <td>
                        </td>
                        <td align="center">
                            <img src="data:image/png;base64, {!! $res['ttdManager'] !!}"
                                style="margin-top: 5px; margin-bottom: 5px">
                        </td>
                    </tr>
                    <tr>
                        <td>
                        </td>
                        <td>
                        </td>
                        <td align="center" height="10" valign="bottom" height="100" width="15%"
                            class="text-center">
                            <span style="font-size: 10pt;" class="text-biasa">
                                <b> {{ $res['alat']->namamanager }}</span></b>
                        </td>
                    </tr>
                <tbody>
            </table>
        @endif
        <table border="0" width="100%" style="padding-left: 5px; margin-top: 5px; ">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Keterangan </b>
                    </span><br>
                    <span style="font-size: 8pt;color:#000000; font-style: italic;">
                        Notes
                    </span>
                </td>
            </tr>
        </table>
        <table border="0" width="100%" style="padding-left: 45px; margin-top: -5px;">
            <tr>
                <td width="100%">
                    @if ($res['alat']->statuskanfk == 1)
                        <div style="margin-bottom: 6px;">
                            <div style="font-size: 9pt; color:#000000;"><b>
                                    Kalibrasi atau pengukuran yang dilaporkan dalam sertifikat ini tercakup dalam
                                    lingkup
                                    akreditasi menurut SNI ISO/IEC 17025:2017 oleh Komite Akreditasi Nasional, kecuali
                                    dinyatakan lain dalam badan sertifikat.
                                </b></div>
                            <div style="font-size: 8pt; color:#000000; font-style: italic;">
                                The calibration or measurement reported in this certificate is covered in the
                                accreditation
                                scope according to SNI ISO/IEC 17025:2017 by the
                                National Accreditation Committee of Indonesia, unless marked otherwise in the body of
                                certificate.
                            </div>
                        </div>
                    @endif

                    <div style="margin-bottom: 6px;">
                        <div style="font-size: 9pt; color:#000000;"><b>
                                Sertifikat ini hanya berlaku untuk peralatan dengan spesifikasi yang dinyatakan di
                                atas.
                            </b></div>
                        <div style="font-size: 8pt; color:#000000; font-style: italic;">
                            This certificate applies only for the item specified above.
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 9pt; color:#000000;"><b>
                                Dilarang keras mengutip/memperbanyak dan/atau mempublikasikan sebagian isi
                                sertifikat
                                ini
                                tanpa ijin tertulis dari PT PLN NP UMRO.
                            </b></div>
                        <div style="font-size: 8pt; color:#000000; font-style: italic;">
                            It is prohibited to quote/reproduce and/or publish part of this certificate without
                            written
                            permission from PT PLN NP UMRO.
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
    </div>
    <div class="page-break"></div>
    {{-- Header untuk halaman kedua dan seterusnya --}}
    <div class="pdf-header">
        <img src="{{ public_path('img/Header_isi_002.png') }}" alt="Header Halaman Lainnya"
            style="width:100%; height:auto; display:block;">
        <hr style="border:none; border-top:3px double #000; margin:8px 0 2px 0;">
        <hr style="border:none; border-top:1.2px solid #000; margin:-5px 0 0 0;">
    </div>
    <div class="pdf-content">
        <table width="100%" style="margin-top: 4px; ">
            <tr>
                <td width="40%">
                    <span style="font-size: 9pt;olor:#000000">

                    </span>
                </td>
                <td width="20%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Hasil Kalibrasi: </b>
                    </span>
                    <div style="font-size:7pt; color:#000;">
                        Calibration Result :
                    </div>
                </td>
                <td width="40%">
                    <span style="font-size: 9pt;color:#000000">

                    </span>
                </td>
            </tr>
        </table>

        <hr style="border:none; border-top:3px double #000; margin:-2px 0 0 0;">
        <hr style="border:none; border-top:1.2px solid #000; margin:-4px 0 8px 0;">

        <table width="100%" style="margin-top: 30px; ">
            <tr>
                <td width="30%">
                    <span style="font-size: 8pt;color:#000000"><b>
                            Tanggal Kalibrasi /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        Calibration Date
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 8pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 8pt;color:#000000">
                        {{-- {{ $res['lembarKerja'][0]->tglkalibrasilembarkerja }} --}}
                        {{ \Carbon\Carbon::parse($res['lembarKerja'][0]->tglkalibrasilembarkerja)->format('d-m-Y') }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="30%">
                    <span style="font-size: 8pt;color:#000000"><b>
                            Tempat Kalibrasi /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        Calibration Place
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 8pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 8pt;color:#000000">
                        {{ $res['lembarKerja'][0]->tempatKalibrasilembarkerja }}
                    </span>
                </td>
            </tr>
        </table>
        <table width="100%" style="margin-top: 10px; ">
            @if ($res['alat']->lingkupkalibrasifk == 2 || $res['lembarKerja'][0]->sublingkupfk == null)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Range / Resolution</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Range / Resolution
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->rangelembarkerja }}
                            ({{ $res['lembarKerja'][0]->rangelembarkerjasatuan ?? 'bar' }} )
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Posisi Mounting </b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Mounting Possition
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->mounting }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Media Kalibrasi </b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Calibration Medium
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->mediakalibrasi }}
                        </span>
                    </td>
                </tr>
            @endif
            @if ($res['lembarKerja'][0]->sublingkupfk == 5 || $res['lembarKerja'][0]->sublingkupfk == 4)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Range /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Range
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->rangelembarkerja }} (Hz)
                        </span>
                    </td>
                </tr>
            @endif
            @if ($res['lembarKerja'][0]->sublingkupfk == 13 || $res['lembarKerja'][0]->sublingkupfk == 14 || $res['lembarKerja'][0]->sublingkupfk == 27 || $res['lembarKerja'][0]->sublingkupfk == 28)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Range /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Range
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->rangelembarkerja }}
                            ({{ $res['lembarKerja'][0]->rangelembarkerjasatuan ?? 'mm' }})
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Resolusi /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Resolution
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->resolusi }}
                            ({{ $res['lembarKerja'][0]->resolusilembarkerjasatuan ?? 'mm' }})
                        </span>
                    </td>
                </tr>
            @endif
            @if (
                $res['lembarKerja'][0]->sublingkupfk == 11 ||
                    $res['lembarKerja'][0]->sublingkupfk == 8 ||
                    $res['lembarKerja'][0]->sublingkupfk == 9)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Resolusi /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Temperature Resolution
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->resolusi }} (°C)
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Rentang Ukur Suhu /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Temperature Range Calibration
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->rentangukursuhu }} (°C)
                        </span>
                    </td>
                </tr>
            @endif
            @if ($res['lembarKerja'][0]->sublingkupfk == 10)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Resolusi /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Temperature Resolution
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->resolusi }} (°C) / {{ $res['lembarKerja'][0]->resolusipersen }}
                            (%rH)
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Rentang Ukur /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Temperature Range Calibration
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->rentangukursuhu }} (°C) /
                            {{ $res['lembarKerja'][0]->rentangukursuhupersen }} (%rH)
                        </span>
                    </td>
                </tr>
            @endif
            @if ($res['lembarKerja'][0]->sublingkupfk == 21)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Range /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Range
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->rangelembarkerja }} (N.m)
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Resolusi /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Resolution
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->resolusi }} (N.m)
                        </span>
                    </td>
                </tr>
            @endif
            @if ($res['lembarKerja'][0]->sublingkupfk == 22)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Range / Resolution </b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Range / Resolusi
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->resolusi }} (°C)
                        </span>
                    </td>
                </tr>
            @endif
            @if ($res['lembarKerja'][0]->sublingkupfk == 23 || $res['lembarKerja'][0]->sublingkupfk == 24 || $res['lembarKerja'][0]->sublingkupfk == 33)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Range / Kapasitas </b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Range / Capacity
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->rangelembarkerja }} (mm)
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Resolusi / </b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Resolution
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->resolusi }} (mm)
                        </span>
                    </td>
                </tr>
            @endif
            <tr>
                <td width="30%">
                    <span style="font-size: 8pt;color:#000000"><b>
                            Kondisi Ruangan /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        Environmental Condition
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 8pt;color:#000000">

                    </span>
                </td>
                <td>
                    <span style="font-size: 8pt;color:#000000">
                        {{ $res['lembarKerja'][0]->kondisiRuanganlembarkerja }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="25%">
                    <span style="font-size: 8pt;color:#000000"><b>
                            Suhu /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        Temperature
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 8pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 8pt;color:#000000">
                        {{ $res['lembarKerja'][0]->suhulembarkerja }} (°C)
                    </span>
                </td>
            </tr>
            <tr>
                <td width="25%">
                    <span style="font-size: 8pt;color:#000000"><b>
                            Kelembaban Relatif /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        Relative Humidity
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 8pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 8pt;color:#000000">
                        {{ $res['lembarKerja'][0]->kelembabanRelatiflembarkerja }} (% RH)
                    </span>
                </td>
            </tr>
            @if ($res['alat']->lingkupkalibrasifk == 2 || $res['lembarKerja'][0]->sublingkupfk == null)
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Tekanan Ruang /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Atmospheric Pressure
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->tekananruang }} (hPa)
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="25%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Gravitasi /</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Gravity
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->gravitasi }} (m/s2)
                        </span>
                    </td>
                </tr>
            @endif
        </table>

        @php
            $dataLembarKerja = collect($res['lembarKerja'] ?? []);

            $hasGroup = $dataLembarKerja->contains(function ($row) {
                return trim((string) data_get($row, 'group', '')) !== '';
            });

            if ($hasGroup) {
                $lembarKerjaGroups = $dataLembarKerja->groupBy(function ($row) {
                    $groupName = trim((string) data_get($row, 'group', ''));

                    return $groupName !== '' ? $groupName : '__TANPA_GROUP__';
                });
            } else {
                $lembarKerjaGroups = collect([
                    '__TANPA_GROUP__' => $dataLembarKerja,
                ]);
            }
        @endphp

        @foreach ($lembarKerjaGroups as $groupName => $dataGroup)
            @php
                $isTanpaGroup = $groupName === '__TANPA_GROUP__';
                $firstDataGroup = $dataGroup->first();
            @endphp
            <div style="page-break-inside:avoid; break-inside:avoid; -webkit-column-break-inside:avoid;">
                @if (!$isTanpaGroup)
                    <div
                        style="font-size:9pt; font-weight:700; margin-top:18px; font-family: DejaVu Sans, sans-serif; margin-bottom:2px; color:#111;">
                        {{ $groupName }}
                    </div>
                @endif
                @if ($res['alat']->lingkupkalibrasifk != 2)
                    @if ($res['lembarKerja'][0]->sublingkupfk == 1)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th colspan="2"
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar_2 ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan_2))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan_2 }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 13)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 2)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th colspan="2"
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penyetelan Sumber Arus<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            Current Source Setting
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penyetelan ?? '' }}</span>
                                                @if (!empty($row->penyetelan_satuan))
                                                    <span class="unit">{{ $row->penyetelan_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->keluaran ?? '' }}</span>
                                                @if (!empty($row->keluaran_satuan))
                                                    <span class="unit">{{ $row->keluaran_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_satuan))
                                                    <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 3)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th colspan="2"
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            Standard reading
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            Instrument Output
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_standar ?? '' }}</span>
                                                @if (!empty($row->pembacaan_standar_satuan))
                                                    <span class="unit">{{ $row->pembacaan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_standar_2 ?? '' }}</span>
                                                @if (!empty($row->pembacaan_standar_satuan_2))
                                                    <span class="unit">{{ $row->pembacaan_standar_satuan_2 }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_alat ?? '' }}</span>
                                                @if (!empty($row->penunjukan_alat_satuan))
                                                    <span class="unit">{{ $row->penunjukan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 4)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Frekuensi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Frequency</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Vibrasi Reference<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            Reference vibration
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        UUT<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            Unit Under Test
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->frekuensi ?? '' }}</span>
                                                @if (!empty($row->frekuensi_satuan))
                                                    <span class="unit">{{ $row->frekuensi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->vibrasi_reference ?? '' }}</span>
                                                @if (!empty($row->vibrasi_reference_satuan))
                                                    <span class="unit">{{ $row->vibrasi_reference_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->uut ?? '' }}</span>
                                                @if (!empty($row->uut_satuan))
                                                    <span class="unit">{{ $row->uut_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_satuan))
                                                    <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 5)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Frekuensi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Frequency</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Vibrasi Reference<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            Reference vibration
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        UUT<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            Unit Under Test
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->frekuensi ?? '' }}</span>
                                                @if (!empty($row->frekuensi_satuan))
                                                    <span class="unit">{{ $row->frekuensi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->vibrasi_reference ?? '' }}</span>
                                                @if (!empty($row->vibrasi_reference_satuan))
                                                    <span class="unit">{{ $row->vibrasi_reference_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->uut ?? '' }}</span>
                                                @if (!empty($row->uut_satuan))
                                                    <span class="unit">{{ $row->uut_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_satuan))
                                                    <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 6)
                        @if ($dataGroup->first()->jenis == 'clamp')
                            {{-- TABEL CLAMP --}}
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th colspan="2"
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penyetelan Sumber Arus<br><span style="font-style:italic;">Current Source
                                                Setting</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Alat<br><span style="font-style:italic;">Instrument
                                                Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penyetelan ?? '' }}</span>
                                                    @if (!empty($row->penyetelan_satuan))
                                                        <span class="unit">{{ $row->penyetelan_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->keluaran ?? '' }}</span>
                                                    @if (!empty($row->keluaran_satuan))
                                                        <span class="unit">{{ $row->keluaran_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @elseif ($dataGroup->first()->jenis == 'meter')
                            {{-- TABEL METER --}}
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th colspan="2"
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan Standar<br><span style="font-style:italic;">Calibrator
                                                Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Alat<br><span style="font-style:italic;">Instrument
                                                Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span
                                                        class="val">{{ $row->penunjukan_standar_2 ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan_2))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan_2 }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 7)
                        @if ($dataGroup->first()->jenis == 'meter')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th colspan="2"
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan Standar<br><span style="font-style:italic;">Calibrator
                                                Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Alat<br><span style="font-style:italic;">Instrument
                                                Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span
                                                        class="val">{{ $row->penunjukan_standar_2 ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan_2))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan_2 }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @elseif ($dataGroup->first()->jenis == 'sumber')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th colspan="2"
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Standar<br><span style="font-style:italic;">Standard
                                                reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan Alat<br><span style="font-style:italic;">Instrument
                                                Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_standar ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_standar_2 ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_standar_satuan_2))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_standar_satuan_2 }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_alat ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_alat_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 8)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 14)
                        @php
                            $jenisPenyimpanganDialIndicator = data_get($firstDataGroup, 'jenis_penyimpangan', 'naik_turun');
                            $isPenyimpanganMajuMundur = $jenisPenyimpanganDialIndicator === 'maju_mundur';
                            $labelPenyimpanganPertama = $isPenyimpanganMajuMundur ? 'Penyimpangan Maju' : 'Penyimpangan Naik';
                            $labelPenyimpanganKedua = $isPenyimpanganMajuMundur ? 'Penyimpangan Mundur' : 'Penyimpangan Turun';
                            $labelPenyimpanganPertamaEn = 'Deviation Up';
                            $labelPenyimpanganKeduaEn = 'Deviation Down';
                        @endphp
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        {{ $labelPenyimpanganPertama }}<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            {{ $labelPenyimpanganPertamaEn }}
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        {{ $labelPenyimpanganKedua }}<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            {{ $labelPenyimpanganKeduaEn }}
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Retrace Error<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Retrace
                                            Error</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penyimpangan_naik ?? '' }}</span>
                                                @if (!empty($row->penyimpangan_naik_satuan))
                                                    <span class="unit">{{ $row->penyimpangan_naik_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penyimpangan_turun ?? '' }}</span>
                                                @if (!empty($row->penyimpangan_turun_satuan))
                                                    <span class="unit">{{ $row->penyimpangan_turun_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->retrace_error ?? '' }}</span>
                                                @if (!empty($row->retrace_error_satuan))
                                                    <span class="unit">{{ $row->retrace_error_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_satuan))
                                                    <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 9)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Set Poin<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Set
                                            Point</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->set_poin ?? '' }}</span>
                                                @if (!empty($row->set_poin_satuan))
                                                    <span class="unit">{{ $row->set_poin_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 10)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Set Poin<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Set
                                            Point</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->set_poin ?? '' }}</span>
                                                @if (!empty($row->set_poin_satuan))
                                                    <span class="unit">{{ $row->set_poin_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                        @if ($groupName == 'Thermohygrometer Digital - Suhu')
                            <div style="font-size:8pt; text-align:left; margin-top:-20px;">
                                *Proses kalibrasi dilakukan pada kelembaban
                                <span style="font-style:italic;">
                                    {{ $res['lembarKerja'][0]->kondisipengukuransuhu }} % RH
                                </span>
                                / Calibration proceed at
                                <span style="font-style:italic;">
                                    humidity {{ $res['lembarKerja'][0]->kondisipengukuransuhu }} % RH
                                </span>
                            </div>
                        @elseif ($groupName == 'Thermohygrometer Digital - Kelembaban')
                            <div style="font-size:8pt; text-align:left; margin-top:-20px;">
                                *Proses kalibrasi dilakukan pada suhu
                                <span style="font-style:italic;">
                                    {{ $res['lembarKerja'][0]->kondisipengukurankelembaban }} °C
                                </span>
                                / Calibration proceed at
                                <span style="font-style:italic;">
                                    temperature {{ $res['lembarKerja'][0]->kondisipengukurankelembaban }} °C
                                </span>
                            </div>
                        @endif
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 15)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_satuan))
                                                    <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 11)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Set Poin<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Set
                                            Point</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    @if ($row->jenis == 'kalibrasi')
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->set_poin ?? '' }}</span>
                                                    @if (!empty($row->set_poin_satuan))
                                                        <span class="unit">{{ $row->set_poin_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                        <hr style="border:none; border-top:3px double #000; margin:8px 0 2px 0;">
                        <hr style="border:none; border-top:1.2px solid #000; margin:-5px 0 0 0;">
                        <table width="100%" style="margin-top: 4px; ">
                            <tr>
                                <td width="40%">
                                    <span style="font-size: 9pt;olor:#000000">

                                    </span>
                                </td>
                                <td width="20%">
                                    <span style="font-size: 9pt;color:#000000"><b>
                                            Hasil Karakteristik: </b>
                                    </span>
                                    <div style="font-size:7pt; color:#000;">
                                        Characterization Result :
                                    </div>
                                </td>
                                <td width="40%">
                                    <span style="font-size: 9pt;color:#000000">

                                    </span>
                                </td>
                            </tr>
                        </table>

                        <hr style="border:none; border-top:3px double #000; margin:0px 0 0 0;">
                        <hr style="border:none; border-top:1.2px solid #000; margin:-4px 0 8px 0;">
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Set Poin<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Set
                                            Point</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Kedalaman Pencelupan<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                            The Immersion Depth
                                        </span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Perbedaan Suhu Axial<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Axial
                                            Temperature
                                            Difference</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Keseragaman Suhu<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Radial
                                            temperature
                                            non-uniformity</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Kestabilan Suhu <br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Temperature
                                            Instability</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    @if ($row->jenis == 'karakteristik')
                                        <tr>
                                            <td
                                                style="padding:2px; text-align:center; vertical-align:middle; white-space:nowrap;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->set_poin ?? '' }}</span>
                                                    @if (!empty($row->set_poin_satuan))
                                                        <span class="unit">{{ $row->set_poin_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span
                                                        class="val">{{ $row->kedalaman_pencelupan ?? '' }}</span>
                                                    @if (!empty($row->kedalaman_pencelupan_satuan))
                                                        <span
                                                            class="unit">{{ $row->kedalaman_pencelupan_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->perbedaan_suhu ?? '' }}</span>
                                                    @if (!empty($row->perbedaan_suhu_satuan))
                                                        <span class="unit">{{ $row->perbedaan_suhu_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->keseragaman_suhu ?? '' }}</span>
                                                    @if (!empty($row->keseragaman_suhu_satuan))
                                                        <span
                                                            class="unit">{{ $row->keseragaman_suhu_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->kestabilan_suhu ?? '' }}</span>
                                                    @if (!empty($row->kestabilan_suhu_satuan))
                                                        <span
                                                            class="unit">{{ $row->kestabilan_suhu_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                        <table width="100%" style="margin-top: -10px;">
                            <tr>
                                <td width="60%" valign="top" style="padding-left: 15px;">
                                </td>

                                <td width="40%" align="center">
                                    <table cellspacing="0" cellpadding="0" style="line-height: 1.1;"
                                        border="0">
                                        <tr>
                                            <td style="font-size: 7pt; color:#000000;">
                                                Kedalaman lubang adalah {{ $res['alat']->kedalamalubang }} mm (The
                                                well
                                                depth is {{ $res['alat']->kedalamalubang }} mm)
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 7pt; color:#000000;">
                                                @php
                                                    // Fungsi untuk memastikan nilai benar-benar layak ditampilkan
                                                    $cekNilaiLubang = function ($value) {
                                                        if (!isset($value)) {
                                                            return null;
                                                        }

                                                        $value = trim((string) $value);

                                                        if (
                                                            $value === '' ||
                                                            $value === '-' ||
                                                            strtolower($value) === 'null'
                                                        ) {
                                                            return null;
                                                        }

                                                        return $value;
                                                    };

                                                    $lubangAcuan = $cekNilaiLubang($res['alat']->lubangacuan ?? null);
                                                    $lubang1 = $cekNilaiLubang($res['alat']->lubang1 ?? null);
                                                    $lubang2 = $cekNilaiLubang($res['alat']->lubang2 ?? null);

                                                    $listId = [];
                                                    $listEn = [];

                                                    if ($lubangAcuan !== null) {
                                                        $listId[] = 'lubang ukur acuan ' . $lubangAcuan . ' mm';
                                                        $listEn[] =
                                                            'reference measurement hole ' . $lubangAcuan . ' mm';
                                                    }

                                                    if ($lubang1 !== null) {
                                                        $listId[] = 'lubang 1 ' . $lubang1 . ' mm';
                                                        $listEn[] = 'hole 1 ' . $lubang1 . ' mm';
                                                    }

                                                    if ($lubang2 !== null) {
                                                        $listId[] = 'lubang 2 ' . $lubang2 . ' mm';
                                                        $listEn[] = 'hole 2 ' . $lubang2 . ' mm';
                                                    }

                                                    $gabungList = function ($items, $kataDan = 'dan') {
                                                        $jumlah = count($items);

                                                        if ($jumlah === 0) {
                                                            return '';
                                                        }

                                                        if ($jumlah === 1) {
                                                            return $items[0];
                                                        }

                                                        if ($jumlah === 2) {
                                                            return $items[0] . ' ' . $kataDan . ' ' . $items[1];
                                                        }

                                                        return implode(', ', array_slice($items, 0, -1)) .
                                                            ', ' .
                                                            $kataDan .
                                                            ' ' .
                                                            end($items);
                                                    };
                                                @endphp

                                                @if (count($listId) > 0)
                                                    Diameter {{ $gabungList($listId, 'dan') }}.<br>
                                                    (Diameter of {{ $gabungList($listEn, 'and') }})
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="right" style="font-size: 7pt; color:#000000;">
                                                @if (!empty($res['alat']->gambarsuhu))
                                                    <img src="{{ public_path('gambar-suhu/' . $res['alat']->gambarsuhu) }}"
                                                        alt="Gambar Suhu" style="height:10%; width:50%">
                                                @else
                                                    {{-- kosongkan atau tampilkan placeholder --}}
                                                    <span style="font-size:6pt;color:#888">No Image</span>
                                                @endif
                                            </td>

                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 12)
                        @if ($dataGroup->first()->jenis == 'accelometer')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Frekuensi<br>
                                            <span
                                                style="font-size:7pt; font-style:italic; font-weight:400;">Frequency</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Vibrasi Reference<br>
                                            <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                                Reference vibration
                                            </span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Sensitivity UUT<br>
                                            <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                                Unit Under Test
                                            </span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Ketidakpastian<br>
                                            <span
                                                style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->frekuensi ?? '' }}</span>
                                                    @if (!empty($row->frekuensi_satuan))
                                                        <span class="unit">{{ $row->frekuensi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->vibrasi_reference ?? '' }}</span>
                                                    @if (!empty($row->vibrasi_reference_satuan))
                                                        <span
                                                            class="unit">{{ $row->vibrasi_reference_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->sensitivity_uut ?? '' }}</span>
                                                    @if (!empty($row->sensitivity_uut_satuan))
                                                        <span
                                                            class="unit">{{ $row->sensitivity_uut_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @elseif ($dataGroup->first()->jenis == 'vibration')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Frekuensi<br>
                                            <span
                                                style="font-size:7pt; font-style:italic; font-weight:400;">Frequency</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Vibrasi Reference<br>
                                            <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                                Reference vibration
                                            </span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            UUT<br>
                                            <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                                Unit Under Test
                                            </span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Koreksi<br>
                                            <span
                                                style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                            Ketidakpastian<br>
                                            <span
                                                style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->frekuensi ?? '' }}</span>
                                                    @if (!empty($row->frekuensi_satuan))
                                                        <span class="unit">{{ $row->frekuensi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->vibrasi_reference ?? '' }}</span>
                                                    @if (!empty($row->vibrasi_reference_satuan))
                                                        <span
                                                            class="unit">{{ $row->vibrasi_reference_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->uut ?? '' }}</span>
                                                    @if (!empty($row->uut_satuan))
                                                        <span class="unit">{{ $row->uut_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 16)
                        @if ($dataGroup->first()->jenis == 'clamp')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th colspan="2"
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penyetelan Sumber Arus<br><span style="font-style:italic;">Current Source
                                                Setting</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Alat<br><span style="font-style:italic;">Instrument
                                                Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penyetelan_sumber ?? '' }}</span>
                                                    @if (!empty($row->penyetelan_sumber_satuan))
                                                        <span
                                                            class="unit">{{ $row->penyetelan_sumber_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span
                                                        class="val">{{ $row->penyetelan_sumber_2 ?? '' }}</span>
                                                    @if (!empty($row->penyetelan_sumber_satuan_2))
                                                        <span
                                                            class="unit">{{ $row->penyetelan_sumber_satuan_2 }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @elseif ($dataGroup->first()->jenis == 'meter')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th colspan="2"
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan Standar<br><span style="font-style:italic;">Calibrator
                                                Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Alat<br><span style="font-style:italic;">Instrument
                                                Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span
                                                        class="val">{{ $row->penunjukan_standar_2 ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan_2))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan_2 }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @elseif ($dataGroup->first()->jenis == 'sumber')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th colspan="2"
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Standar<br><span style="font-style:italic;">Standard
                                                reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan Alat<br><span style="font-style:italic;">Instrument
                                                Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_standar ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span
                                                        class="val">{{ $row->pembacaan_standar_2 ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_standar_satuan_2))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_standar_satuan_2 }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_alat ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_alat_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    @endif
                    @if (($res['alat']->namasublingkup ?? null) == 'KALIBRASI AI' || $res['lembarKerja'][0]->sublingkupfk == 17)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if (($res['alat']->namasublingkup ?? null) == 'KALIBRASI AI VIBRASI')
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Frekuensi<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Frequency</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Vibrasi Reference<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Reference
                                            Vibration</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        UUT<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Unit Under
                                            Test</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->frekuensi ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->vibrasi_reference ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->uut ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if (($res['alat']->namasublingkup ?? null) == 'KALIBRASI AI SUHU')
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Set Point<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Set Point</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->set_point ?? $row->rentang ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span
                                                    class="val">{{ $row->temperature_reference ?? $row->penunjukan_standar ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span
                                                    class="val">{{ $row->temperature_uut ?? $row->pembacaan_alat ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 18)
                        @if ($dataGroup->first()->jenis == 'resistance continuity')
                            {{-- TABEL resistance continuity --}}
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan Standar<br>
                                            <span style="font-style:italic;">Calibrator Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Alat<br><span style="font-style:italic;">Instrument
                                                Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @elseif ($dataGroup->first()->jenis == 'source')
                            {{-- TABEL source --}}
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan UUC<br>
                                            <span style="font-style:italic;">Instrument Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Standar<br>
                                            <span style="font-style:italic;">Calibrator Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_uuc ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_uuc_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_uuc_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_standar ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 19)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 20)
                        @php
                            $adaPenunjukanStandar2 = collect($dataGroup)->contains(function ($item) {
                                return !empty($item->penunjukan_standar_2);
                            });

                            $totalColspan = $adaPenunjukanStandar2 ? 6 : 5;
                        @endphp

                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Rentang<br><span style="font-style:italic;">Range</span>
                                    </th>

                                    <th @if ($adaPenunjukanStandar2) colspan="2" @endif
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Penunjukan Standar<br>
                                        <span style="font-style:italic;">Calibrator Output</span>
                                    </th>

                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Pembacaan Alat<br>
                                        <span style="font-style:italic;">Instrument Reading</span>
                                    </th>

                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Koreksi<br>
                                        <span style="font-style:italic;">Correction</span>
                                    </th>

                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Ketidakpastian<br>
                                        <span style="font-style:italic;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        @if ($adaPenunjukanStandar2)
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span
                                                        class="val">{{ $row->penunjukan_standar_2 ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan_2))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan_2 }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        @endif

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="{{ $totalColspan }}"
                                        style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 21)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Set Poin<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Set
                                            Point</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->set_poin ?? '' }}</span>
                                                @if (!empty($row->set_poin_satuan))
                                                    <span class="unit">{{ $row->set_poin_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 22)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Set Poin<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Set
                                            Point</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->set_poin ?? '' }}</span>
                                                @if (!empty($row->set_poin_satuan))
                                                    <span class="unit">{{ $row->set_poin_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 23 || $res['lembarKerja'][0]->sublingkupfk == 24 ||$res['lembarKerja'][0]->sublingkupfk == 33)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 25)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Rentang<br><span style="font-style:italic;">Range</span>
                                    </th>

                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Penunjukan Standar<br>
                                        <span style="font-style:italic;">Calibrator Output</span>
                                    </th>

                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Pembacaan Alat<br>
                                        <span style="font-style:italic;">Instrument Reading</span>
                                    </th>

                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Koreksi<br>
                                        <span style="font-style:italic;">Correction</span>
                                    </th>

                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Ketidakpastian<br>
                                        <span style="font-style:italic;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>

                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_satuan))
                                                    <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 26)
                        @if ($dataGroup->first()->jenis == 'insulation')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan Standar<br>
                                            <span style="font-style:italic;">Calibrator Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Alat<br><span style="font-style:italic;">Instrument
                                                Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_alat_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @elseif ($dataGroup->first()->jenis == 'source')
                            <table width="100%" border="0" cellpadding="2" cellspacing="0"
                                style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                                <thead>
                                    <tr>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Rentang<br><span style="font-style:italic;">Range</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Penunjukan UUC<br>
                                            <span style="font-style:italic;">Instrument Output</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Pembacaan Standar<br>
                                            <span style="font-style:italic;">Calibrator Reading</span>
                                        </th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Koreksi<br><span style="font-style:italic;">Correction</span></th>
                                        <th
                                            style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                            Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataGroup as $row)
                                        <tr>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->rentang ?? '' }}</span>
                                                    @if (!empty($row->rentang_satuan))
                                                        <span class="unit">{{ $row->rentang_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->penunjukan_uuc ?? '' }}</span>
                                                    @if (!empty($row->penunjukan_uuc_satuan))
                                                        <span
                                                            class="unit">{{ $row->penunjukan_uuc_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->pembacaan_standar ?? '' }}</span>
                                                    @if (!empty($row->pembacaan_standar_satuan))
                                                        <span
                                                            class="unit">{{ $row->pembacaan_standar_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                    @if (!empty($row->koreksi_satuan))
                                                        <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td style="padding:2px; text-align:center; vertical-align:middle;">
                                                <span class="cell-valunit">
                                                    <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                    @if (!empty($row->ketidakpastian_satuan))
                                                        <span
                                                            class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 27 || $res['lembarKerja'][0]->sublingkupfk == 28)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Rentang<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Range</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Penunjukan Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Calibrator
                                            Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Pembacaan Alat<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->rentang ?? '' }}</span>
                                                @if (!empty($row->rentang_satuan))
                                                    <span class="unit">{{ $row->rentang_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span
                                                        class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 30)
                        @php
                            $isSetpoint = $dataGroup->first()->jenis == 'setpoint';
                        @endphp
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        {{ $isSetpoint ? 'Set Point' : 'Rentang' }}<br>
                                        <span style="font-style:italic;">{{ $isSetpoint ? 'Set Point' : 'Range' }}</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Penunjukan Standar<br>
                                        <span style="font-style:italic;">Calibrator Output</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Pembacaan Alat<br>
                                        <span style="font-style:italic;">Instrument Reading</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Koreksi<br><span style="font-style:italic;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; text-align:center;">
                                        Ketidakpastian<br><span style="font-style:italic;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->setpoint ?? $row->rentang ?? '' }}</span>
                                                @php
                                                    $firstUnit = $row->setpoint_satuan ?? $row->rentang_satuan ?? '';
                                                @endphp
                                                @if (!empty($firstUnit))
                                                    <span class="unit">{{ $firstUnit }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->penunjukan_standar ?? '' }}</span>
                                                @if (!empty($row->penunjukan_standar_satuan))
                                                    <span class="unit">{{ $row->penunjukan_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->pembacaan_alat ?? '' }}</span>
                                                @if (!empty($row->pembacaan_alat_satuan))
                                                    <span class="unit">{{ $row->pembacaan_alat_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_satuan))
                                                    <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                    @if ($res['lembarKerja'][0]->sublingkupfk == 29)
                        <table width="100%" border="0" cellpadding="2" cellspacing="0"
                            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                            <thead>
                                <tr>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Skala Instrumen<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Scale</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Indikasi Standar<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Standard
                                            Indication</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Indikasi Instrumen<br>
                                        <span style="font-size:7pt; font-style:italic; font-weight:400;">Instrument
                                            Indication</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Koreksi<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Correction</span>
                                    </th>
                                    <th
                                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                        Ketidakpastian<br>
                                        <span
                                            style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody align="center">
                                @foreach ($dataGroup as $row)
                                    <tr>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->skala_instrumen ?? '' }}</span>
                                                @if (!empty($row->skala_instrumen_satuan))
                                                    <span class="unit">{{ $row->skala_instrumen_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->indikasi_standar ?? '' }}</span>
                                                @if (!empty($row->indikasi_standar_satuan))
                                                    <span class="unit">{{ $row->indikasi_standar_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->indikasi_instrumen ?? '' }}</span>
                                                @if (!empty($row->indikasi_instrumen_satuan))
                                                    <span
                                                        class="unit">{{ $row->indikasi_instrumen_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->koreksi ?? '' }}</span>
                                                @if (!empty($row->koreksi_satuan))
                                                    <span class="unit">{{ $row->koreksi_satuan }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                                            <span class="cell-valunit">
                                                <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                                @if (!empty($row->ketidakpastian_standar))
                                                    <span class="unit">{{ $row->ketidakpastian_standar }}</span>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif
                @endif
                @if ($res['alat']->lingkupkalibrasifk == 2 || $res['lembarKerja'][0]->sublingkupfk == null)
                    <table width="100%" border="0" cellpadding="2" cellspacing="0"
                        style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
                        <thead>
                            <tr>
                                <th
                                    style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                    Tekanan pada Standard<br>
                                    <span style="font-size:7pt; font-style:italic; font-weight:400;">Pressure at
                                        standard</span>
                                </th>
                                <th
                                    style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                    Tekanan pada UUT<br>
                                    <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                        Pressure at UUT
                                    </span>
                                </th>
                                <th
                                    style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                    Koreksi<br>
                                    <span style="font-size:7pt; font-style:italic; font-weight:400;">
                                        Correction
                                    </span>
                                </th>
                                <th
                                    style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                                    Ketidakpastian<br>
                                    <span
                                        style="font-size:7pt; font-style:italic; font-weight:400;">Uncertainty</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dataGroup as $row)
                                <tr>
                                    <td style="padding:2px; text-align:center;">
                                        <span class="cell-valunit">
                                            <span class="val">{{ $row->tekanan_pada_standard ?? '' }}</span>
                                            @if (!empty($row->tekanan_pada_standard_satuan))
                                                <span class="unit">{{ $row->tekanan_pada_standard_satuan }}</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td style="padding:2px; text-align:center;">
                                        <span class="cell-valunit">
                                            <span class="val">{{ $row->tekanan_pada_uut ?? '' }}</span>
                                            @if (!empty($row->tekanan_pada_uut_satuan))
                                                <span class="unit">{{ $row->tekanan_pada_uut_satuan }}</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td style="padding:2px; text-align:center;">
                                        <span class="cell-valunit">
                                            <span class="val">{{ $row->koreksi ?? '' }}</span>
                                            @if (!empty($row->koreksi_satuan))
                                                <span class="unit">{{ $row->koreksi_satuan }}</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td style="padding:2px; text-align:center;">
                                        <span class="cell-valunit">
                                            <span class="val">{{ $row->ketidakpastian ?? '' }}</span>
                                            @if (!empty($row->ketidakpastian_satuan))
                                                <span class="unit">{{ $row->ketidakpastian_satuan }}</span>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="border-bottom:2pt solid #000; height:2px;"></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        @endforeach
        @if ($res['lembarKerja'][0]->sublingkupfk == 1)
            <table width="100%" style="margin-top:-10px;" border="0">
                <tr>
                    <td align="center" style="font-size:7pt;color:#000">
                        @if (!empty($res['alat']->gambarsuhu))
                            <img src="{{ public_path('gambar-suhu/' . $res['alat']->gambarsuhu) }}" alt="Gambar"
                                style="height:20%; width:100%">
                        @else
                            <span style="font-size:6pt;color:#888"></span>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
        @if ($res['lembarKerja'][0]->sublingkupfk == 13)
            <table width="100%" style="margin-top:-10px;" border="0">
                <tr>
                    <td align="left" style="font-size:7pt;color:#000">
                        @if (!empty($res['alat']->kesejajaranluar))
                            Pengukuran Kesejajaran Luar : {{ $res['alat']->kesejajaranluar ?? '' }} mm
                        @else
                            <span style="font-size:6pt;color:#888"></span>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
        @if ($res['lembarKerja'][0]->sublingkupfk == 28)
            <table width="100%" style="margin-top:-10px;" border="0">
                <tr>
                    <td width="10%" align="left" style="font-size:7pt;color:#000">
                        @if (!empty($res['alat']->kesejajaran))
                            Kesejajaran
                        @else
                            <span style="font-size:6pt;color:#888"></span>
                        @endif
                    </td>
                    <td align="left" style="font-size:7pt;color:#000">
                        @if (!empty($res['alat']->kesejajaran))
                            : {{ $res['alat']->kesejajaran ?? '' }} mm
                        @else
                            <span style="font-size:6pt;color:#888"></span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="left" style="font-size:7pt;color:#000">
                        @if (!empty($res['alat']->kerataan))
                            Kerataan
                        @else
                            <span style="font-size:6pt;color:#888"></span>
                        @endif
                    </td>
                    <td align="left" style="font-size:7pt;color:#000">
                        @if (!empty($res['alat']->kerataan))
                            : {{ $res['alat']->kerataan ?? '' }} mm
                        @else
                            <span style="font-size:6pt;color:#888"></span>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
        @if ($res['lembarKerja'][0]->sublingkupfk == 15)
            <table width="100%" style="padding-left: 5px; margin-top: 20px; ">
                <tr>
                    <td width="30%">
                        <div style="font-size: 9pt; color:#000000; font-weight: bold; margin-bottom: 1px;">
                            Spesifikasi Dial Indicator
                        </div>
                        <div style="font-size: 8pt; color:#000000; font-style: italic;">
                            Dial Indicator Specifications
                        </div>
                    </td>
                </tr>
            </table>
            <table width="100%" style="padding-left: 45px; margin-top: 5px; border-collapse: collapse;">
                <tr style="line-height: 1.1;">
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;"><b>Merk Pabrik / Tipe</b></span><br>
                        <span style="font-size: 8pt; color: #000000; font-style: italic;">Manufacture/ type</span>
                    </td>
                    <td style="padding-bottom: 2px;">:</td>
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;">{{ $res['lembarKerja'][0]->merekdial }} /
                            {{ $res['lembarKerja'][0]->tipedial }}</span>
                    </td>
                </tr>
                <tr style="line-height: 1.1;">
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;"><b>Nomor Seri</b></span><br>
                        <span style="font-size: 8pt; color: #000000; font-style: italic;">Serial number</span>
                    </td>
                    <td style="padding-bottom: 2px;">:</td>
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;">{{ $res['lembarKerja'][0]->sndial }}</span>
                    </td>
                </tr>
                <tr style="line-height: 1.1;">
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;"><b>Range / Kapasitas</b></span><br>
                        <span style="font-size: 8pt; color: #000000; font-style: italic;">Range / Capacity</span>
                    </td>
                    <td style="padding-bottom: 2px;">:</td>
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;">{{ $res['lembarKerja'][0]->rangedial }}
                            mm</span>
                    </td>
                </tr>
                <tr style="line-height: 1.1;">
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;"><b>Resolusi</b></span><br>
                        <span style="font-size: 8pt; color: #000000; font-style: italic;">Resolusi</span>
                    </td>
                    <td style="padding-bottom: 2px;">:</td>
                    <td style="padding-bottom: 2px;">
                        <span style="font-size: 9pt; color: #000000;">{{ $res['lembarKerja'][0]->resolusidial }}
                            mm</span>
                    </td>
                </tr>
            </table>
        @endif
    </div>

    @if ($res['lembarKerja'][0]->sublingkupfk == 8 || $res['lembarKerja'][0]->sublingkupfk == 9)
        <div class="sheet">
            <table width="100%" style="margin-top: 3px; " border="0">
                <tr>
                    <td width="18%">
                        <span style="font-size: 8pt;color:#000000">
                            Dikalibrasi Pada Jarak
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->jarakKalibrasi }} (cm)
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div class="sheet">
            <table width="100%" style="margin-top: 3px; " border="0">
                <tr>
                    <td width="18%">
                        <span style="font-size: 8pt;color:#000000">
                            Emisivitas Black Body
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->emisivitas }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @if ($res['lembarKerja'][0]->sublingkupfk == 22)
        <div class="sheet">
            <table width="100%" style="margin-top: 3px; " border="0">
                <tr>
                    <td width="18%">
                        <span style="font-size: 8pt;color:#000000">
                            Jenis Sensor
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->jenissensor }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    @endif
    @if ($res['lembarKerja'][0]->sublingkupfk == 23)
        <div class="sheet">
            <table width="100%" style="margin-top: 3px; " border="0">
                <tr>
                    <td width="18%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Standar Material / </b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Material Standard
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->standarmaterial }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div class="sheet">
            <table width="100%" style="margin-top: 3px; " border="0">
                <tr>
                    <td width="18%">
                        <span style="font-size: 8pt;color:#000000"><b>
                                Kecepatan / </b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            Velocity
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 8pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 8pt;color:#000000">
                            {{ $res['lembarKerja'][0]->kecepatan }} (m/s)
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="page-break"></div>
    <div class="sheet" style="font-family: DejaVu Sans, sans-serif;">
        <table width="100%" style="margin-top: 3px; " border="0">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Catatan /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        Notes
                    </span>
                </td>
            </tr>
        </table>
        {{-- @if ($res['lembarKerja'][0]->lingkupkalibrasifk != 1) --}}
        @if (!empty($res['lembarKerja'][0]->noteslembarkerja))
            <table width="100%" style="margin-top: 5px;" border="0">
                <tr>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000; white-space: normal;">
                            <b>
                                {!! nl2br(e($res['lembarKerja'][0]->noteslembarkerja)) !!}
                            </b>
                        </span>
                        <br>
                        @if (!empty($res['lembarKerja'][0]->noteslembarkerja_en))
                            <span style="font-style:italic;font-size: 8pt;color:#000000; white-space: normal;">
                                {!! nl2br(e($res['lembarKerja'][0]->noteslembarkerja_en)) !!}
                            </span>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
        @if (empty($res['lembarKerja'][0]->noteslembarkerja))
            <table width="100%" style="margin-top: 5px; " border="0">
                <tr>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>
                                Hasil kalibrasi ini diperoleh berdasarkan daftar instruksi kerja dengan menggunakan
                                alat
                                standar
                                yang tertelusur ke SI
                                melalui SNSU-BSN./</b>
                        </span>
                        <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                            This calibration result was acquired based on the list of work instruction calibration
                            using
                            the
                            standard instrument that is
                            traceable to SI through SNSU-BSN.
                        </span>
                    </td>
                </tr>
            </table>
        @endif
        {{-- @endif --}}
        {{-- @if ($res['lembarKerja'][0]->lingkupkalibrasifk == 1)
            <table width="100%" style="margin-top: 5px;" border="0">
                <tr>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000; white-space: normal;">
                            @if (!empty($res['lembarKerja'][0]->noteslembarkerja) && strtolower(trim($res['lembarKerja'][0]->noteslembarkerja)) !== 'null')
                                <b>
                                    {!! nl2br(e($res['lembarKerja'][0]->noteslembarkerja)) !!}
                                </b><br>
                            @endif

                            <b>
                                Hasil kalibrasi ini diperoleh berdasarkan daftar instruksi kerja dengan menggunakan
                                alat
                                standar
                                yang tertelusur ke SI
                                melalui SNSU-BSN.
                            </b>
                        </span>
                        <br>
                        @if (!empty($res['lembarKerja'][0]->noteslembarkerja_en) && strtolower(trim($res['lembarKerja'][0]->noteslembarkerja_en)) !== 'null')
                            <span style="font-style:italic;font-size: 8pt;color:#000000; white-space: normal;">
                                {!! nl2br(e($res['lembarKerja'][0]->noteslembarkerja_en)) !!}
                            </span> <br>
                        @endif
                        <span style="font-style:italic;font-size: 8pt;color:#000000">
                            This calibration result was acquired based on the list of work instruction calibration
                            using
                            the
                            standard instrument that is
                            traceable to SI through SNSU-BSN.
                        </span>
                    </td>
                </tr>
            </table>
        @endif --}}

        <div style="margin-top:18px; margin-bottom:2px;">
            <span style="font-size: 9pt;color:#000000"><b>
                    Daftar Instruksi Kerja /</b>
            </span>
            <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                List of work instruction
            </span>
        </div>
        <table width="100%" border="0" cellpadding="2" cellspacing="0"
            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
            <thead>
                <tr>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        NO
                    </th>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        No Instruksi Kerja
                    </th>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        Nama Instruksi Kerja
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($res['instruksikerja'] as $row)
                    <tr>
                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:18px; text-align:center;">
                                {{ $loop->iteration }}
                            </span>
                        </td>
                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:160px; text-align:right;">
                                {{ $row->noisntruksikerja ?? '' }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:260px; text-align:right;">
                                {{ $row->namainstruksikerja ?? '' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="border-bottom:2pt solid #000; height:2px;"></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top:18px; margin-bottom:2px;">
            <span style="font-size: 9pt;color:#000000"><b>
                    Daftar Peralatan Standar /</b>
            </span>
            <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                List of Standard
            </span>
        </div>
        <table width="100%" border="0" cellpadding="2" cellspacing="0"
            style="border-collapse:collapse; font-size:7pt; margin-bottom:20px; font-family: DejaVu Sans, sans-serif;">
            <thead>
                <tr>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        NO</th>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        Nama Standard</th>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        Merk/ Tipe</th>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        No Serial</th>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        Cal Date</th>
                    <th
                        style="border-top:2pt solid #000; border-bottom:2pt solid #000; font-size:8pt; font-weight:bold; text-align:center; padding:3px;">
                        Due Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($res['alastandar'] as $row)
                    <tr>
                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:18px; text-align:center;">
                                {{ $loop->iteration }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:left; vertical-align:middle;">
                            <span style="display:inline-block; min-width:190px; text-align:left;">
                                {{ $row->namaalatstandar ?? '' }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:200px; text-align:center;">
                                {{ ($row->namamerk ?? '') . ' - ' . ($row->namatipe ?? '') }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:90px; text-align:center;">
                                {{ $row->namaserialnumber ?? '' }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:80px; text-align:center;">
                                {{ $row->calldate ? \Carbon\Carbon::parse($row->calldate)->format('d-m-Y') : '' }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:80px; text-align:center;">
                                {{ $row->duedate ? \Carbon\Carbon::parse($row->duedate)->format('d-m-Y') : '' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="border-bottom:2pt solid #000; height:2px;"></td>
                </tr>
            </tfoot>
        </table>
        <table width="100%" style="margin-top: -29px; text-align:center;  " border="0">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Akhir dari Sertifikat /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        End of certificate
                    </span>
                </td>
            </tr>
        </table>
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding-top: 50px">
            <tbody style="font-size: 11pt">
                <tr>
                    <td align="center" width="30%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            Asman NMW
                        </span>
                    </td>
                    <td align="center" width="30%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            Penyelia Kalibrasi
                        </span>
                    </td>
                    <td align="center" width="30%">
                        <span style="font-size: 10pt;" class="text-biasa">
                            Pelaksana Kalibrasi
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        @if ($res['alat']->setujuilembarkerjaasman)
                            <img src="data:image/png;base64, {!! $res['ttdAsman'] !!}"
                                style="margin-top: 5px; margin-bottom: 5px">
                        @endif
                    </td>
                    <td align="center">
                        @if ($res['alat']->setujuilembarkerjapenyelia)
                            <img src="data:image/png;base64, {!! $res['ttdPenyelia'] !!}"
                                style="margin-top: 5px; margin-bottom: 5px">
                        @endif
                    </td>
                    <td align="center">
                        <img src="data:image/png;base64, {!! $res['ttdPelaksana'] !!}"
                            style="margin-top: 5px; margin-bottom: 5px">
                    </td>
                </tr>
                <tr>
                    <td align="center" height="10" valign="bottom" height="100" width="15%"
                        class="text-center">
                        @if ($res['alat']->setujuilembarkerjaasman)
                            <span style="font-size: 10pt;" class="text-biasa">
                                ( {{ $res['alat']->asamanverifikasi }} )
                            </span>
                        @endif
                    </td>
                    <td align="center" height="10" valign="bottom" height="100" width="15%"
                        class="text-center">
                        @if ($res['alat']->setujuilembarkerjapenyelia)
                            <span style="font-size: 10pt;" class="text-biasa">
                                ( {{ $res['alat']->penyeliateknik }})
                            </span>
                        @endif
                    </td>
                    <td align="center" height="10" valign="bottom" height="100" width="15%"
                        class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">( {{ $res['alat']->pelaksanateknik }}
                            )</span>
                    </td>
                </tr>
            </tbody>
        </table>
        {{-- {{ dd($res) }} --}}

    </div>
</body>

</html>
