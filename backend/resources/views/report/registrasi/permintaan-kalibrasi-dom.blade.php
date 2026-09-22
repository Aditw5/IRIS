<!DOCTYPE html>
<html>

<head>
    <!-- <link rel="stylesheet" href="css/paper.css "> -->
    <!-- <link rel="stylesheet" href="css/table-v2.css"> -->
    <link rel="stylesheet" href="css/style.css">
    <title>Formulir Peremintaan Kalibrasi dan Kontrak</title>
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

<body style="padding-top:25px">
    <script type="text/php">
        if (isset($pdf)) {
            // Koordinat X (Horizontal): Geser ke kanan (sekitar 460-480 point)
            $x = 460; 
            
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
                                Permintaan Kalibrasi dan Kontrak
                            </b></span>
                    </td>
                </tr>

                <tr>

                    <table width="75%" cellspacing="0" cellpadding="0" border="0"
                        style="border-collapse: collapse; margin-left: 90px">
                        <tbody>
                            <tr>
                                <td width="35%"
                                    style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    No.Dok : FMMO-163-14.4.3.b-71.1
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

    <table width="100%" style="margin-top: -10px; ">
        <table border="0" width="100%" style="margin-top: 15px; ">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000">
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
                        {{ $res['identitas']->nopendaftaran ?? null }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Nama Perusahaan
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->namaperusahaan }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Alamat Perusahaan
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;;color:#000000">
                        {{ $res['identitas']->alamatktr }}
                    </span>
                </td>
            </tr>
        </table>
        <table border="0" width="100%" style="margin-top: 15px; ">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000">
                        Telp./Fax
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->nohp }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Contact Person/Penanggung Jawab
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->nohppenanggungjawab }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Email
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;;color:#000000">
                        {{ $res['identitas']->email ?? '-' }}
                    </span>
                </td>
            </tr>
            @if ($res['identitas']->jenisorder == 'kalibrasi')
                <tr>
                    <td width="15%">
                        <span style="font-size: 9pt;color:#000000">
                            Reantang Ukur Kalibrasi Yang Diminta
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <table width="100%">
                            <tr>
                                <td width="15%">
                                    <input type="checkbox"
                                        {{ isset($res['identitas']->rentangUkur) && $res['identitas']->rentangUkur == 'standarLab' ? 'checked' : '' }} />
                                    <span style="font-size: 9pt;position: relative;top:-7px">Standar Lab</span>
                                </td>
                                <td width="15%">
                                    <input type="checkbox"
                                        {{ isset($res['identitas']->rentangUkur) && $res['identitas']->rentangUkur == 'permintaanPelanggan' ? 'checked' : '' }} />
                                    <span style="font-size: 9pt;position: relative;top:-7px">Permintaan
                                        Pelanggan</span>
                                </td>
                                <td width="15%">
                                    <input type="checkbox"
                                        {{ isset($res['identitas']->rentangUkur) && $res['identitas']->rentangUkur == 'lainLain' ? 'checked' : '' }} />
                                    <span style="font-size: 9pt;position: relative;top:-7px">Lain-Lain</span>
                                </td>
                            </tr>
                            @if ($res['identitas']->rentangUkur == 'permintaanPelanggan')
                                <tr>
                                    <td width="15%">
                                        <span style="font-size: 9pt;color:#000000">
                                            {{ $res['identitas']->rentangUkurketPermintaanPelanggan ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            @endif
        </table>
        {{-- <table border="0" width="100%" style="margin-top: 10px; ">
                <tr>
                    <td width="30%">
                        <span style="font-size: 9pt;color:#000000"><b>
                                Denagn menandatangani formulir ini saya nyatakan data diatas benar </b>
                        </span><br>
                    </td>
                </tr>
            </table> --}}
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding-top: -30px">
            <tbody style="font-size: 11pt">
                <tr>
                    <td width="90%">
                        <span style="font-size: 9pt;color:#000000"><b>
                                Dengan menandatangani formulir ini saya nyatakan data diatas benar </b>
                        </span>
                    </td>
                    <td align="center" width="40%" class="text-center">
                        <span style="font-size: 10pt;"
                            class="text-biasa">{{ $res['identitas']->lokasi ?? $res['identitas']->lokasirepair }},
                            {{ \Carbon\Carbon::parse($res['identitas']->tglregistrasi)->isoFormat('DD MMMM Y') }}</span>
                    </td>
                </tr>
                {{-- <tr>
                    <td  width="90%">
                    </td>
                    <td align="center">
                        <img src="data:image/png;base64, {!! $res['ttdPelanggan'] !!}"
                            style="margin-top: 5px; margin-bottom: 5px">
                    </td>
                </tr> --}}
                {{-- @php
                    $ttdData = json_decode($res['identitas']->ttdpenanggungjawab);
                @endphp --}}
                <tr>
                    <td width="90%">
                    </td>
                    <td align="center">
                        {{-- @if (!empty($ttdData->ttdPenanggungJawab))
                            <img src="{{ $ttdData->ttdPenanggungJawab }}"
                                style="margin-top: 5px; margin-bottom: 5px; max-height: 80px;" />
                        @endif --}}
                        <img src="data:image/png;base64, {!! $res['ttdPelanggan'] !!}"
                            style="margin-top: 5px; margin-bottom: 5px">
                    </td>
                </tr>
                <tr>
                    <td width="90%">
                    </td>
                    <td align="center" height="8" valign="bottom" height="100" width="15%"
                        class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            <b> ({{ $res['identitas']->namapenanggungjawab }})</span></b>
                    </td>
                </tr>
            <tbody>
        </table>
        <table border="0" width="100%" style="margin-top: 10px; ">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Status Pendaftaran</b>
                    </span>
                    <span style="font-size: 8pt;color:#000000">
                        (diisi oleh petuugas U-Lab)
                    </span>
                </td>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000"><b>
                            Status Alat</b>
                    </span>
                    <span style="font-size: 9pt;color:#000000"><b>
                            Diisi saat alat diterima oleh U-Lab</b>
                    </span>

                </td>
            </tr>
            <tr>
                <td width="30%" style="vertical-align: top;">
                    {{-- Pendaftaran diterima --}}
                    <input type="checkbox"
                        {{ isset($res['identitas']->statuspendaftaran) && $res['identitas']->statuspendaftaran == 'diterima' ? 'checked' : '' }} />
                    <span style="font-size: 9pt;position: relative;top:-7px">
                        Pendaftaran diterima, Alat harus diserahkan ke U-Lab
                    </span><br>

                    <span style="font-size: 9pt;position: relative;top:-7px; margin-left: 24px">
                        Tanggal
                        {{ !empty($res['identitas']->tanggalserahdari)
                            ? \Carbon\Carbon::parse($res['identitas']->tanggalserahdari)->format('d-m-Y')
                            : '....' }}
                        s/d
                        {{ !empty($res['identitas']->tanggalserahsd)
                            ? \Carbon\Carbon::parse($res['identitas']->tanggalserahsd)->format('d-m-Y')
                            : '....' }}
                    </span><br>

                    {{-- Layanan ditangguhkan --}}
                    <input type="checkbox"
                        {{ isset($res['identitas']->statuspendaftaran) && $res['identitas']->statuspendaftaran == 'ditangguhkan' ? 'checked' : '' }} />
                    <span style="font-size: 9pt;position: relative;top:-7px">
                        Layanan ditangguhkan karena
                    </span><br>

                    {{-- alasan ditangguhkan --}}
                    <input type="checkbox" style="margin-left: 24px"
                        {{ isset($res['identitas']->alasanditangguhkan) && $res['identitas']->alasanditangguhkan == 'kapasitasPenuh' ? 'checked' : '' }} />
                    <span style="font-size: 9pt;position: relative;top:-7px">
                        Kapasitas penuh
                    </span><br>

                    <input type="checkbox" style="margin-left: 24px"
                        {{ isset($res['identitas']->alasanditangguhkan) && $res['identitas']->alasanditangguhkan == 'diluarLingkup' ? 'checked' : '' }} />
                    <span style="font-size: 9pt;position: relative;top:-7px">
                        Diluar lingkup pelayanan
                    </span><br>

                    <input type="checkbox" style="margin-left: 24px"
                        {{ isset($res['identitas']->alasanditangguhkan) && $res['identitas']->alasanditangguhkan == 'pemilihanStandar' ? 'checked' : '' }} />
                    <span style="font-size: 9pt;position: relative;top:-7px">
                        Sedang dalam pemilihan standar
                    </span><br>

                    <input type="checkbox" style="margin-left: 24px"
                        {{ isset($res['identitas']->alasanditangguhkan) && $res['identitas']->alasanditangguhkan == 'lainLain' ? 'checked' : '' }} />
                    <span style="font-size: 9pt;position: relative;top:-7px">
                        Lain-lain :
                        {{ isset($res['identitas']->alasanditangguhkanlain) ? $res['identitas']->alasanditangguhkanlain : '' }}
                    </span><br>
                </td>
                <td width="30%" style="vertical-align: top;">
                    <table border="0" width="100%" style="margin-top: -10px; ">
                        <tr>
                            <td width="40%">
                                <span style="font-size: 9pt;color:#000000">
                                    No. Pendaftaran
                                </span>
                            </td>
                            <td width="2%">
                                <span style="font-size: 9pt;color:#000000">
                                    :
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 9pt;color:#000000">
                                    {{ $res['identitas']->nopendaftaran ?? null }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 9pt;color:#000000">
                                    Tanggal Penyerahan
                                </span>
                            </td>
                            <td width="2%">
                                <span style="font-size: 9pt;color:#000000">
                                    :
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 9pt;color:#000000">
                                    {{ \Carbon\Carbon::parse($res['identitas']->tglregistrasi)->isoFormat('DD MMMM Y') }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 9pt;color:#000000">
                                    Kelengkapan alat
                                </span>
                            </td>
                            <td width="2%">
                                <span style="font-size: 9pt;color:#000000">
                                    :
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 9pt;color:#000000">
                                    Terlampir
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 9pt;color:#000000">
                                    Catatan Kondisi alat
                                </span>
                            </td>
                            <td width="2%">
                                <span style="font-size: 9pt;color:#000000">
                                    :
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 9pt;color:#000000">
                                    Terlampir
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 8pt;color:#000000">
                                    (visual dan fungsional cek)
                                </span>
                            </td>
                            <td width="2%">
                                <span style="font-size: 8pt;color:#000000">
                                    :
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 8pt;color:#000000">
                                    Terlampir
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 9pt;color:#000000">
                                    Tanda tangan penerima
                                </span>
                            </td>
                            <td width="2%">
                                <span style="font-size: 9pt;color:#000000">
                                    :
                                </span>
                            </td>
                            <td>
                                <img src="data:image/png;base64, {!! $res['ttdPetugas'] !!}"
                                    style="margin-top: 5px; margin-bottom: 5px">
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 9pt;color:#000000">
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
                                    {{ $res['identitas']->namapetugaskaji ?? '-' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <span style="font-size: 9pt;color:#000000">
                                    Catatan penyimpangan hasil obeservasi teknis
                                </span>
                            </td>
                            <td width="2%">
                                <span style="font-size: 9pt;color:#000000">
                                    :
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 9pt;color:#000000">

                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table border="0" width="100%" style="margin-top: 15px; ">
            <tr>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        <b>Kontrak kesepakatan</b>
                    </span>
                    <span style="font-size: 7pt;color:#000000">
                        (diisi oleh petugas U-Lab dan ttd oleh pelanggan saat penyerahan alat)
                    </span>
                </td>
            </tr>
        </table>
        <table border="0" width="100%" style="margin-top: 1px; ">
            <tr>
                <td width="30%">
                    <span style="font-size: 9pt;color:#000000">
                        Tanggal Order
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->tglregistrasi ?? '-' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Tanggal selesai
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['alat'][0]->tanggalSelesai ?? '-' }}
                    </span>
                </td>
            </tr>
            @if ($res['identitas']->jenisorder == 'kalibrasi')
                <tr>
                    <td width="15%">
                        <span style="font-size: 9pt;color:#000000">
                            Durasi Pekerjaan
                        </span>
                    </td>
                    <td width="2%">
                        <span style="font-size: 9pt;color:#000000">
                            :
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 9pt;;color:#000000">
                            {{ $res['alat'][0]->totalDurasi ?? '-' }} Hari Kerja
                        </span>
                    </td>
                </tr>
            @endif
        </table>
        <table border="0" width="100%" style="margin-top: 0px; ">
            <tr>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        Kontrak kesepakatan telah disepakati oleh kedua belah pihak, apabila ada perubahan dalam kontrak
                        akan diberitahukan sebelumnya.
                    </span>
                </td>
            </tr>
        </table>
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding-top: -30px">
            <tbody style="font-size: 11pt">
                @php
                    $ttdData = json_decode($res['identitas']->ttdpenanggungjawab);
                @endphp
                <tr>
                    <td width="50%" align="center">

                    </td>
                    <td width="50%" align="center">
                        <span style="font-size: 10pt;"
                            class="text-biasa">{{ $res['identitas']->lokasi ?? $res['identitas']->lokasirepair }},
                            {{ \Carbon\Carbon::parse($res['identitas']->tglregistrasi)->isoFormat('DD MMMM Y') }}</span>
                    </td>
                </tr>
                <tr>
                    <td width="50%" align="center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            <b> Pelanggan</span></b>
                    </td>
                    <td width="50%" align="center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            <b> Asman</span></b>
                    </td>
                </tr>
                <tr>
                    <td width="50%" align="center">
                        {{-- @if (!empty($ttdData->ttdPenanggungJawab))
                            <img src="{{ $ttdData->ttdPenanggungJawab }}"
                                style="margin-top: 5px; margin-bottom: 5px; max-height: 80px;" />
                        @endif --}}
                        <img src="data:image/png;base64, {!! $res['ttdPelanggan'] !!}"
                            style="margin-top: 5px; margin-bottom: 5px">
                    </td>
                    <td width="50%" align="center">
                        @if (!empty($res['alat']) && !empty($res['alat'][0]->asamanverifikasi))
                            <img src="data:image/png;base64,{!! $res['ttdAsman'] !!}"
                                style="margin-top: 5px; margin-bottom: 5px">
                        @endif
                    </td>
                </tr>
                <tr>
                    <td width="50%" align="center" height="8" valign="bottom" height="100" width="15%"
                        class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            <b> ({{ $res['identitas']->namapenanggungjawab ?? '-' }})</span></b>
                    </td>
                    <td width="50%" align="center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            <b> ({{ $res['alat'][0]->asamanverifikasi ?? '-' }})</span></b>
                    </td>
                </tr>
            <tbody>
        </table>
        <div class="page-break"></div>
        <div class="sheet" style="padding:25px">
            <table style="margin-top: 20px; " width="100%" border="1" cellspacing="0" cellpadding="5"
                style="font-size: 10pt; border-collapse: collapse;">
                <thead style="background-color: #f2f2f2;">
                    <tr>
                        <th style="font-size: 9pt;">No</th>
                        <th style="font-size: 9pt;">Nama Barang</th>
                        <th style="font-size: 9pt;">Merk/Tipe</th>
                        <th style="font-size: 9pt;">S/N</th>
                        <th style="font-size: 9pt;">Jumlah</th>
                        <th style="font-size: 9pt;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($res['alat'] as $index => $alat)
                        <tr>
                            <td width="5%"> <span style="font-size: 9pt;" class="text-biasa">{{ $index + 1 }}
                                </span>
                            </td>
                            <td width="25%">
                                <span
                                    style="font-size: 9pt; {{ !empty($alat->alasanpenolakanregis) ? 'color: red;' : '' }}"
                                    class="text-biasa">
                                    {{ $alat->namaproduk }}
                                    @if (!empty($alat->alasanpenolakanregis))
                                        (Ditolak)
                                    @endif
                                </span>
                            </td>
                            <td width="15%"> <span style="font-size: 9pt;"
                                    class="text-biasa">{{ trim($alat->namamerk) }}
                                    {{ $alat->namatipe }}</span></td>
                            <td width="15%"> <span style="font-size: 9pt;"
                                    class="text-biasa">{{ $alat->namaserialnumber }}</span>
                            </td>
                            <td width="15%"> <span style="font-size: 9pt;" class="text-biasa">1 Set </span></td>
                            <td width="25%" style="text-align: center">
                                @if (!empty($alat->namafile))
                                    <img src="{{ 'berkas-mitra/' . $alat->namafile }}" width="170px"
                                        style="margin:2px 0;">
                                @elseif (!empty($alat->alasanpenolakanregis))
                                    <div style="color: red; font-size: 12px; margin-top: 10px;">
                                        {{ $alat->alasanpenolakanregis }}
                                    </div>
                                @else
                                    <span style="color: gray; font-size: 12px;">Tidak ada file atau alasan
                                        penolakan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

</html>
