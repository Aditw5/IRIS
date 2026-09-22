<!DOCTYPE html>
<html>

<head>
    <title>Laporan Verifikasi</title>
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
        }

        .pdf-header {
            position: fixed;
            top: -90px;
            /* Negatif dari margin-top, biar header pas */
            left: 0;
            right: 0;
            height: 100px;
            width: 100%;
            z-index: 10;
        }

        .pdf-footer {
            position: fixed;
            bottom: -50px;
            /* Negatif dari margin-bottom */
            left: 0;
            right: 0;
            height: 70px;
            width: 100%;
            z-index: 10;
        }

        /* Supaya konten tidak nabrak header/footer */
        .pdf-content {
            /* kosong, gunakan margin @page */
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    {{-- @if ($res['alat']->setujuilembarkerjamanager == true && $res['halamanPertama'] == true) --}}
    {{-- @if ($res['halamanPertama'] == true) --}}
    {{-- @if ($res['alat']->statuskanfk == 1) --}}
    {{-- <div style="margin-top: -90px; margin-bottom: 20px;">
            <img src="{{ public_path('img/header_cover_KAN_002.png') }}" alt="Header Halaman Pertama"
                style="width:100%; height:auto; display:block;">
        </div> --}}
    <div style="margin-top: -90px; margin-bottom: 20px;">
        <img src="{{ public_path('img/header_cover_NON_KAN.png') }}" alt="Header Halaman Pertama"
            style="width:100%; height:auto; display:block;">
    </div>
    {{-- @endif
        @if ($res['alat']->statuskanfk == 2)
            <div style="margin-top: -90px; margin-bottom: 20px;">
                <img src="{{ public_path('img/header_cover_NON_KAN.png') }}" alt="Header Halaman Pertama"
                    style="width:100%; height:auto; display:block;">
            </div>
        @endif --}}
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


    {{-- @if ($res['alat']->setujuilembarkerjamanager == true && $res['halamanPertama'] == true) --}}
    {{-- @if ($res['halamanPertama'] == true) --}}
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
                        Laporan Verifikasi
                    </div>
                    <div
                        style="font-size: 9pt; color:#000000; font-style: italic; font-weight: bold; margin-bottom: 2px;">
                        Verification Report
                    </div>
                    <div style="font-size: 7pt; color:#000000; margin-bottom: 1px;">
                        Nomor Sertifikat : {{ $res['alat']->nosertifikat }}
                    </div>
                    <div style="font-size: 7pt; color:#000000;">
                        No Order : {{ $res['alat']->noorderalat }}
                    </div>
                </td>
                <td width="35%">
                </td>
            </tr>
        </table>

        <table width="100%" style="padding-left: 5px margin-top: 20px; ">
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
        @if ($res['alat']->setujuilembarkerjamanager == true && $res['halamanPertama'] == true)
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

    </div>
    <div class="page-break"></div>
    {{-- @endif --}}
    {{-- Header untuk halaman kedua dan seterusnya --}}
    <div class="pdf-header">
        <img src="{{ public_path('img/header_isi_verifikasi.png') }}" alt="Header Halaman Lainnya"
            style="width:100%; height:auto; display:block;">
        <hr style="border:none; border-top:3px double #000; margin:8px 0 2px 0;">
        <hr style="border:none; border-top:1.2px solid #000; margin:-5px 0 0 0;">
    </div>
    <div class="pdf-content" style="text-align:center;">
        {{-- @if (!empty($res['alat']->excellaporanverif_imgs)) --}}
        @foreach ($res['alat']->excellaporanverif_imgs as $i => $img)
            <div @if ($i > 0) style="page-break-before: always;" @endif>
                <img src="{{ $img }}" alt="Lampiran Excel halaman {{ $i + 1 }}"
                    style="width:90%; max-width: {{ $pageWidth }}px; height:auto; display:inline-block; image-rendering:auto;" />
            </div>
        @endforeach
        {{-- @else --}}
        {{-- <p style="font-size:10pt; text-align:center; margin-top:20px; color:red">
                File lampiran belum tersedia sebagai gambar / gagal dikonversi.
            </p> --}}
        {{-- @endif --}}
    </div>
    <div class="page-break"></div>
    {{-- <div class="sheet">
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
        <table width="100%" style="margin-top: 5px; " border="0">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Hasil kalibrasi ini diperoleh berdasarkan daftar instruksi kerja dengan menggunakan alat
                            standar
                            yang tertelusur ke SI
                            melalui SNSU-BSN./</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        This calibration result was acquired based on the list of work instruction calibration using the
                        standard instrument that is
                        traceable to SI through SNSU-BSN.
                    </span>
                </td>
            </tr>
        </table>
        <table width="100%" style="margin-top: 5px; " border="0">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Ketidakpastian pengukuran dihitung dengan tingkat kepercayaan tidak kurang dari 95% dan
                            faktor
                            cakupan k = 2./
                        </b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        uncertainty of measurement was calculated with the confidence level not less than 95% and
                        coverage
                        factor of k = 2.
                    </span>
                </td>
            </tr>
        </table> --}}
    <table width="100%" style="margin-top: 0px; ">
        <tr>
            <td width="30%">
                <span style="font-size: 8pt;color:#000000"><b>
                        Tanggal Verifikasi /</b>
                </span>
                <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                    Verification Date
                </span>
            </td>
            <td width="2%">
                <span style="font-size: 8pt;color:#000000">
                    :
                </span>
            </td>
            <td>
                <span style="font-size: 8pt;color:#000000">
                    {{ \Carbon\Carbon::parse($res['alat']->tglkalibrasilembarkerja)->format('d-m-Y') }}
                </span>
            </td>
        </tr>
        <tr>
            <td width="30%">
                <span style="font-size: 8pt;color:#000000"><b>
                        Tempat Verifikasi /</b>
                </span>
                <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                    Verification Place
                </span>
            </td>
            <td width="2%">
                <span style="font-size: 8pt;color:#000000">
                    :
                </span>
            </td>
            <td>
                <span style="font-size: 8pt;color:#000000">
                    {{ $res['alat']->tempatKalibrasilembarkerja }}
                </span>
            </td>
        </tr>
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
                    {{ $res['alat']->kondisiRuanganlembarkerja }}
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
                    {{ $res['alat']->suhulembarkerja }} (°C)
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
                    {{ $res['alat']->kelembabanRelatiflembarkerja }} (% RH)
                </span>
            </td>
        </tr>
    </table>
    @if (!empty($res['alastandar']) && count($res['alastandar']) > 0)
        <div style="margin-top:18px; margin-bottom:2px;">
            <span style="font-size: 9pt;color:#000000"><b>
                    Daftar Peralatan Standar Verifikasi /</b>
            </span>
            <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                List of Standard Verification
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

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:190px; text-align:right;">
                                {{ $row->namaalatstandar ?? '' }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:200px; text-align:right;">
                                {{ ($row->namamerk ?? '') . ' - ' . ($row->namatipe ?? '') }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:90px; text-align:right;">
                                {{ $row->namaserialnumber ?? '' }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:80px; text-align:right;">
                                {{ $row->calldate ? \Carbon\Carbon::parse($row->calldate)->format('d-m-Y') : '' }}
                            </span>
                        </td>

                        <td style="padding:2px; text-align:center; vertical-align:middle;">
                            <span style="display:inline-block; min-width:80px; text-align:right;">
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
    @endif
    <div class="sheet" style="font-family: DejaVu Sans, sans-serif;">
        <table width="100%" style="margin-top: 3px; " border="0">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Evaluasi Verifikasi /</b>
                    </span>
                    <span style="margin-left:-10px ;font-style:italic;font-size: 8pt;color:#000000">
                        Verification Evaluation
                    </span>
                </td>
            </tr>
        </table>
        @if (!empty($res['alat']->evalverifikasi))
            <table width="100%" style="margin-top: 5px; margin-left: 2px" border="0">
                <tr>
                    <td width="100%">
                        <span style="font-size: 8pt;color:#000000; white-space: normal;">
                            <b>
                                {!! nl2br(e($res['alat']->evalverifikasi)) !!}
                            </b>
                        </span>
                        <br>
                        @if (!empty($res['alat']->evalverifikasi_en))
                            <span style="font-style:italic;font-size: 8pt;color:#000000; white-space: normal;">
                                {!! nl2br(e($res['alat']->evalverifikasi_en)) !!}
                            </span>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
        <table width="100%" style="margin-top: 3px;" border="0">
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
        @if (!empty($res['alat']->catatanverifikasi))
            <table width="100%" style="margin-top: 5px; margin-left: 5px" border="0">
                <tr>
                    <td width="100%">
                        <span style="font-size: 8pt;color:#000000; white-space: normal;">
                            <b>
                                {!! nl2br(e($res['alat']->catatanverifikasi)) !!}
                            </b>
                        </span>
                        <br>
                        @if (!empty($res['alat']->catatanverifikasi_en))
                            <span style="font-style:italic;font-size: 8pt;color:#000000; white-space: normal;">
                                {!! nl2br(e($res['alat']->catatanverifikasi_en)) !!}
                            </span>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
    </div>

    <table width="100%" style="text-align:center;  " border="0">
        <tr>
            <td width="100%">
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
        <tbody>
    </table>
    {{-- {{ dd($res) }} --}}

    </div>
</body>

</html>
