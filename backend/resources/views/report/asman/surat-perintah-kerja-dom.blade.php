<!DOCTYPE html>
<html>

<head>
    <!-- <link rel="stylesheet" href="css/paper.css "> -->
    <!-- <link rel="stylesheet" href="css/table-v2.css"> -->
    <link rel="stylesheet" href="css/style.css">
    <title>Formulir Surat Perintah Kerja</title>
</head>
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

    /* Supaya konten tidak nabrak header/footer */
    .pdf-content {
        /* kosong, gunakan margin @page */
    }

    .page-break {
        page-break-before: always;
    }

    .footer {
        position: fixed;
        bottom: -55px;
        /* mendekati margin-bottom @page */
        left: 0;
        right: 0;
        height: 55px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .small {
        font-size: 9pt;
    }

    .xsmall {
        font-size: 8pt;
    }
</style>

<body style="padding:25px">
    <script type="text/php">
        if (isset($pdf)) {
            // Koordinat X (Horizontal): Geser ke kanan (sekitar 460-480 point)
            $x = 620; 
            
            // Koordinat Y (Vertikal): Sesuaikan dengan posisi baris tabel header
            // Header ada di top -90px, jadi y sekitar 35-45 point dari atas kertas
            $y = 75;  
            
            $text = "Halaman : {PAGE_NUM} dari {PAGE_COUNT}";
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "bold");
            $size = 9;
            $color = array(0,0,0);
            $word_space = 0.0;  
            $char_space = 0.0;  
            $angle = 0.0;   

            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>
    <div class="pdf-header">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <thead style="display: none"></thead>
            <tbody>
                <tr>
                    <td rowspan="5">
                        <img src="img/pln.png" width="80px" border="0" style="margin-top:-20px">
                    </td>
                    <td class="text-center">
                        <span style="font-size: 13pt;font-weight: 600;color:#000000"><b>
                                LABORATORIUM KALIBRASI PT PLN NP UMRO
                            </b></span>
                    </td>
                    <td rowspan="5">
                        <img src="img/UMRO.png" width="80px" border="0" style="margin-top:-20px">
                    </td>
                </tr>

                <tr>
                    <td class="text-center">
                        <span style="font-size: 10pt;font-weight: 600;color:#000000"><b>
                                FORMULIR
                            </b></span>
                    </td>
                </tr>
                <tr>
                    <td class="text-center">
                        <span style="font-size: 10pt;font-weight: 600;color:#000000"><b>
                                {{-- SURAT PERINTAH KERJA {{ strtoupper($res['alat'][0]->lingkupkalibrasi) }} --}}
                                SURAT PERINTAH KERJA 
                            </b></span>
                    </td>
                </tr>

                <tr>

                    <table width="70%" cellspacing="0" cellpadding="0" border="0"
                        style="border-collapse: collapse; margin-left: 150px">
                        <tbody>
                            <tr>
                                <td width="35%"
                                    style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    No.Dok : FMMO-163-14.4.3.b-74.4
                                </td>
                                <td width="15%"
                                    style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    Revisi : 01
                                </td>
                                <td width="30%"
                                    style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    Tanggal : {{ now()->locale('id')->isoFormat('D MMMM Y') }}
                                </td>
                                <td width="20%"
                                    style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </tr>
            </tbody>
        </table>

        <hr class="baris1" style="margin-top:1px">
        <hr class="baris1" style="margin-top:-5px">
    </div>

    <div class="footer">
        <hr style="border: 0; border-top: 1px solid #000; margin-bottom: 6px;">
        <table class="table footer-table">
            <tr>
                <td class="xsmall">
                    Dicetak: {{ \Carbon\Carbon::now()->format('d-m-Y H:i') }}
                </td>
                <td class="text-center" style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;">
                    {{-- Halaman: <span class="pagenum"></span> dari <span class="pagecount"></span> --}}
                </td>
                <td class="xsmall text-right">
                    {{ $profile->website ?? 'https://ulabumro.id/' }}
                </td>
            </tr>
        </table>
    </div>

    {{-- {{ dd($res) }} --}}

    <table width="100%" style="margin-top: -19px; ">
        <table width="100%" style="margin-top: 5px; ">
            <tr>
                <td width="15%">
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;olor:#000000">
                        No. Urut Pendaftaran
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->nopendaftaran }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Tanggal Order
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000"><b>
                            {{ $res['identitas']->tglregistrasi }}</b>
                    </span>
                </td>
            </tr>
        </table>
        @if ($res['penyelia'] == true)
            <table width="100%" style="margin-top: 5px; ">
                <tr>
                    <td width="40%">
                        <span style="font-size: 9pt;font-weight: 600;olor:#000000"><b>

                            </b></span>
                    </td>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>
                                Menugaskan Penyelia dibawah ini ,
                            </b></span>
                    </td>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>

                            </b></span>
                    </td>
                </tr>
            </table>
            <table width="100%" style="margin-top: 5px; ">
                <tr>
                    <td width="15%">
                    </td>
                    <td width="15%">
                        <span style="font-size: 9pt;olor:#000000">
                            Nama
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 9pt;color:#000000">
                            {{ $res['alat'][0]->penyeliateknik }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="15%">
                    </td>
                    <td width="15%">
                        <span style="font-size: 9pt;color:#000000">
                            NID
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 9pt;color:#000000">
                            {{ $res['alat'][0]->nid ?? '-' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="15%">
                    </td>
                    <td width="15%">
                        <span style="font-size: 9pt;color:#000000">
                            Jabatan
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 9pt;color:#000000">
                            {{ $res['alat'][0]->jabatanpenyelia }}
                        </span>
                    </td>
                </tr>
            </table>
        @endif
        @if ($res['penyelia'] == false)
            <table width="100%" style="margin-top: 5px; ">
                <tr>
                    <td width="40%">
                        <span style="font-size: 9pt;font-weight: 600;olor:#000000"><b>

                            </b></span>
                    </td>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>
                                Menugaskan karyawan dibawah ini ,
                            </b></span>
                    </td>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>

                            </b></span>
                    </td>
                </tr>
            </table>
            <table width="100%" style="margin-top: 5px; ">
                <tr>
                    <td width="15%">
                    </td>
                    <td width="15%">
                        <span style="font-size: 9pt;olor:#000000">
                            Nama
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 9pt;color:#000000">
                            {{ $res['alat'][0]->pelaksanateknik }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="15%">
                    </td>
                    <td width="15%">
                        <span style="font-size: 9pt;color:#000000">
                            NID
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 9pt;color:#000000">

                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="15%">
                    </td>
                    <td width="15%">
                        <span style="font-size: 9pt;color:#000000">
                            Jabatan
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 9pt;color:#000000">
                            {{ $res['alat'][0]->jabatanpelaksana }}
                        </span>
                    </td>
                </tr>
            </table>
        @endif

        @if ($res['penyelia'] == true)
            <table width="100%" border="1" cellspacing="0" cellpadding="5"
                style="font-size: 10pt; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr>
                        <th colspan="5" style="text-align: left; font-size: 11pt;">
                            Untuk melaksanakan pekerjaan dibawah ini,
                        </th>
                    </tr>
                    <tr style="background-color: #f2f2f2;">
                        <th style="font-size: 9pt;">No. Order</th>
                        <th style="font-size: 9pt;">Pelaksana</th>
                        <th style="font-size: 9pt;">Nama Alat</th>
                        <th style="font-size: 9pt;">Merk/Tipe</th>
                        <th style="font-size: 9pt;">S/N</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($res['alat'] as $alat)
                        <tr>
                            <td style="font-size: 9pt;">{{ $alat->noorderalat ?? '-' }}</td>
                            <td style="font-size: 9pt;">{{ $alat->pelaksanateknik ?? '-' }}</td>
                            <td style="font-size: 9pt;">{{ $alat->namaproduk }}</td>
                            <td style="font-size: 9pt;">{{ trim($alat->namamerk) }} {{ $alat->namatipe }}</td>
                            <td style="font-size: 9pt;">{{ $alat->namaserialnumber }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        @if ($res['penyelia'] == false)
            <table width="100%" border="1" cellspacing="0" cellpadding="5"
                style="font-size: 10pt; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr>
                        <th colspan="4" style="text-align: left; font-size: 11pt;">
                            Untuk melaksanakan pekerjaan dibawah ini,
                        </th>
                    </tr>
                    <tr style="background-color: #f2f2f2;">
                        <th style="font-size: 9pt;">No. Order</th>
                        <th style="font-size: 9pt;">Nama Alat</th>
                        <th style="font-size: 9pt;">Merk/Tipe</th>
                        <th style="font-size: 9pt;">S/N</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($res['alat'] as $alat)
                        <tr>
                            <td style="font-size: 9pt;">{{ $alat->noorderalat ?? '-' }}</td>
                            <td style="font-size: 9pt;">{{ $alat->namaproduk }}</td>
                            <td style="font-size: 9pt;">{{ trim($alat->namamerk) }} {{ $alat->namatipe }}</td>
                            <td style="font-size: 9pt;">{{ $alat->namaserialnumber }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <table width="100%" style="margin-top: 5px; ">
            <tr>
                <td width="40%">
                    <span style="font-size: 9pt;olor:#000000">

                    </span>
                </td>
                <td width="20%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Uraian Kesepakatan Pekerjaan </b>
                    </span>
                </td>
                <td width="40%">
                    <span style="font-size: 9pt;color:#000000">

                    </span>
                </td>
            </tr>
        </table>
        @php
            $totalDurasi = 0;
        @endphp

        @foreach ($res['alat'] as $alat)
            @php
                $totalDurasi += (int) $alat->durasikalbrasi; // atau $alat->durasikalbrasi jika object
            @endphp
            <!-- Baris tabel alat di sini -->
        @endforeach
        <table width="100%" style="margin-top: 5px; ">
            <tr>
                <td width="30%">
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;olor:#000000">
                        Tanggal Order
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->tglregistrasi }}
                    </span>
                </td>
                <td width="35%">
                    <span style="font-size: 9pt;color:#000000">

                    </span>
                </td>
            </tr>
            <tr>
                <td width="30%">
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;olor:#000000">
                        Rencana Tanggal Mulai
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['alat'][0]->tanggalmulaiestimasi }}
                    </span>
                </td>
                <td width="35%">
                    <span style="font-size: 9pt;color:#000000">

                    </span>
                </td>
            </tr>
            <tr>
                <td width="30%">
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;olor:#000000">
                        Rencana Selesai
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        {{ $totalDurasi }}
                    </span>
                </td>
                <td width="35%">
                    <span style="font-size: 9pt;color:#000000">

                    </span>
                </td>
            </tr>
            {{-- <tr>
                <td width="30%">
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;olor:#000000">
                        Realisasi Selesai
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">

                    </span>
                </td>
                <td width="35%">
                    <span style="font-size: 9pt;color:#000000">
                        Hari Kerja
                    </span>
                </td>
            </tr> --}}

        </table>

</body>

<div class="page-break"></div>

<!-- Halaman Kedua -->
<div class="sheet" style="padding:25px">
    <table width="100%" style="margin-top: 50px; ">
        <tr>
            <td width="40%">
                <span style="font-size: 9pt;olor:#000000">

                </span>
            </td>
            <td width="20%">
                <span style="font-size: 9pt;color:#000000"><b>
                        Catatan Pekerjaan </b>
                </span>
            </td>
            <td width="40%">
                <span style="font-size: 9pt;color:#000000">

                </span>
            </td>
        </tr>
    </table>
    <table width="100%" style="margin-top: 5px; ">
        <tr>
            <td width="25%">
            </td>
            <td width="50%">
                <span style="font-size: 9pt;color:#000000">
                    1. Semua pekerjaan dilakukan sesuai dengan prosedur Penanganan Alat Yang Dikalibrasi.
                </span>
            </td>
            <td width="25%">
                <span style="font-size: 9pt;color:#000000">

                </span>
            </td>
        </tr>
        <tr>
            <td width="25%">
            </td>
            <td width="50%">
                <span style="font-size: 9pt;color:#000000">
                    2. Wajib menggunakan Instruksi Keja (IK) saat kegiatan kalibrasi berlangsung.
                </span>
            </td>
            <td width="25%">
                <span style="font-size: 9pt;color:#000000">

                </span>
            </td>
        </tr>
        <tr>
            <td width="25%">
            </td>
            <td width="50%">
                <span style="font-size: 9pt;color:#000000">
                    3. Wajib menggunakan Lembar Kerja (LK) yang dibuat oleh staf teknik.
                </span>
            </td>
            <td width="25%">
                <span style="font-size: 9pt;color:#000000">

                </span>
            </td>
        </tr>
        <tr>
            <td width="25%">
            </td>
            <td width="50%">
                <span style="font-size: 9pt;color:#000000">
                    4. Segala bentuk penyimpanagan dicatat dalam Formulir Ketidaksesuaian Pekerjaa
                </span>
            </td>
            <td width="25%">
                <span style="font-size: 9pt;color:#000000">

                </span>
            </td>
        </tr>

    </table>
    <table width="100%" cellspacing="0" cellpadding="0" style="padding-top: 50px">
        <tbody style="font-size: 11pt">
            <tr>
                <td width="40%">
                </td>
                <td width="30%" class="text-center">
                    <span style="font-size: 10pt;" class="text-biasa">
                        Mengetahui
                    </span>
                </td>
                <td width="30%" class="text-center">
                    <span style="font-size: 10pt;" class="text-biasa">{{ $res['identitas']->lokasi }},
                        {{ \Carbon\Carbon::parse(date('Y-m-d H:i'))->isoFormat('DD MMMM Y') }}</span>
                </td>
            </tr>
            <tr>
                <td width="40%">
                </td>
                <td width="30%" class="text-center">
                    <span style="font-size: 10pt;" class="text-biasa"> Kepala Laboratorium Kalibrasi </span>
                </td>
                <td width="30%" class="text-center">
                    <span style="font-size: 10pt;" class="text-biasa"> Assistant Manager Non Mechanical Workshop
                    </span>
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td align="center">
                    <img src="data:image/png;base64, {!! $res['ttdManager'] !!}"
                        style="margin-top: 5px; margin-bottom: 5px">
                    {{-- <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data={{isset($res) ? $res['kepala']->namalengkap : ''}}" alt=""> --}}
                </td>
                <td align="center">
                    <img src="data:image/png;base64, {!! $res['ttdAsman'] !!}"
                        style="margin-top: 5px; margin-bottom: 5px">
                    {{-- <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data={{isset($res) ? $res['kepala']->namalengkap : ''}}" alt=""> --}}
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td height="10" valign="bottom" height="100" width="15%" class="text-center">
                    <span style="font-size: 10pt;" class="text-biasa">
                        ( {{ $res['manager']->namalengkap }} )</span>
                </td>
                <td height="10" valign="bottom" height="100" width="15%" class="text-center">
                    <span style="font-size: 10pt;" class="text-biasa">( {{ $res['asman']->namalengkap }}
                        )</span>
                </td>
            </tr>
        <tbody>
    </table>
</div>


</html>
