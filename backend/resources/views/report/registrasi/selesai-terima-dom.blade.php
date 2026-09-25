<!DOCTYPE html>
<html>

<head>
    <!-- <link rel="stylesheet" href="css/paper.css "> -->
    <!-- <link rel="stylesheet" href="css/table-v2.css"> -->
    <link rel="stylesheet" href="css/style.css">
    <title>Formulir Tanda Terima Alat Selesai</title>
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
                                TANDA TERIMA ALAT SELESAI
                            </b></span>
                    </td>
                </tr>
                <tr>
                    <table width="80%" cellspacing="0" cellpadding="0" border="0"
                        style="border-collapse: collapse; margin-left: 70px">
                        <tbody>
                            <tr>
                                <td width="35%" style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    No.Dok : FMMO-163-14.4.3.b-74.6
                                </td>
                                <td width="15%" style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    Revisi : 01
                                </td>
                                <td width="30%" style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    Tanggal : {{ now()->locale('id')->isoFormat('D MMMM Y') }}
                                </td>
                                <td width="20%" style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
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

                {{-- INI YANG ANDA MAU: HALAMAN OTOMATIS --}}
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
                    <span style="font-size: 9pt;font-weight: 600;olor:#000000"><b>
                            Diterima Dari
                        </b></span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000"><b>

                        </b></span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000"><b>

                        </b></span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Nama
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->petugasterima }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Jabatan
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;;color:#000000">
                        {{ $res['identitas']->jabatanpetugas }}
                    </span>
                </td>
            </tr>
        </table>
        <table width="100%" style="margin-top: 5px; ">
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;font-weight: 600;olor:#000000"><b>
                            Penerima ALat
                        </b></span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000"><b>

                        </b></span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000"><b>

                        </b></span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Nama
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ $res['identitas']->penerima }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Jabatan
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;;color:#000000">
                        {{ $res['identitas']->jabatanpenerima ?? '-' }}
                    </span>
                </td>
            </tr>
        </table>
        <table width="100%" style="margin-top: 5px; ">
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;font-weight: 600;olor:#000000"><b>
                            Diteriima Pada
                        </b></span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000"><b>

                        </b></span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000"><b>

                        </b></span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Tanggal
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;color:#000000">
                        {{ \Carbon\Carbon::parse($res['identitas']->tanggalselesaiterima)->isoFormat('DD MMMM Y') }}
                    </span>
                </td>
            </tr>
            <tr>
                <td width="15%">
                    <span style="font-size: 9pt;color:#000000">
                        Tempat
                    </span>
                </td>
                <td width="2%">
                    <span style="font-size: 9pt;color:#000000">
                        :
                    </span>
                </td>
                <td>
                    <span style="font-size: 9pt;;color:#000000">
                        {{ $res['identitas']->tempatterima ?? '-' }}
                    </span>
                </td>
            </tr>
        </table>

        <table style="margin-top: 20px; " width="100%" border="1" cellspacing="0" cellpadding="5"
            style="font-size: 10pt; border-collapse: collapse;">
            <thead style="background-color: #f2f2f2;">
                <tr>
                    <th style="font-size: 9pt;">No</th>
                    <th style="font-size: 9pt;">Nama Barang</th>
                    <th style="font-size: 9pt;">Merk/Tipe</th>
                    <th style="font-size: 9pt;">S/N</th>
                    <th style="font-size: 9pt;">Jumlah</th>
                    <th style="font-size: 9pt;">Durasi Pengerjaan</th>
                    <th style="font-size: 9pt;">No.Sertifikat/Laporan</th>
                    <th style="font-size: 9pt;">Foto Alat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($res['alat'] as $index => $alat)
                    <tr>
                        <td> <span style="font-size: 9pt;" class="text-biasa">{{ $index + 1 }} </span></td>
                        <td width="15%">
                            <span
                                style="font-size: 9pt; {{ !empty($alat->alasanpenolakanregis) ? 'color: red;' : '' }}"
                                class="text-biasa">
                                {{ $alat->namaproduk }}
                                @if (!empty($alat->alasanpenolakanregis))
                                    (Ditolak)
                                @endif
                            </span>
                        </td>
                        <td> <span style="font-size: 9pt;" class="text-biasa">{{ trim($alat->namamerk) }}
                                {{ $alat->namatipe }}</span></td>
                        <td> <span style="font-size: 9pt;" class="text-biasa">{{ $alat->namaserialnumber }}</span>
                        </td>
                        <td> <span style="font-size: 9pt;" class="text-biasa">1 Set </span></td>
                        <td> <span style="font-size: 9pt;" class="text-biasa">
                                {{ $alat->durasi_proses ?? '-' }}
                            </span>
                        </td>
                        <td> <span style="font-size: 9pt;"
                                class="text-biasa">{{ $alat->nosertifikat ?? $alat->nolaporanrepair }} </span></td>
                        <td width="20%" style="text-align: center; vertical-align: top; padding: 6px;">
                            @php
                                $fotoFiles = [];

                                if (!empty($alat->foto_files)) {
                                    $decoded = json_decode($alat->foto_files, true);
                                    $fotoFiles = is_array($decoded) ? $decoded : [];
                                }
                                $fotoFiles = array_values(array_filter($fotoFiles, fn($x) => !empty($x)));
                                $fotoFiles = array_values(array_unique($fotoFiles));
                                $maxShow = 100;
                                $shownFiles = array_slice($fotoFiles, 0, $maxShow);
                                $remaining = count($fotoFiles) - count($shownFiles);
                            @endphp
                            @if (!empty($alat->fotoalatditerima))
                                <img src="{{ public_path('berkas-customer/' . basename(str_replace('\\', '/', (string) $alat->fotoalatditerima))) }}"
                                    style="width:120px; max-width:100%; height:auto; border:1px solid #ddd; border-radius:6px; margin:2px 0;" />
                            @elseif (!empty($shownFiles))
                                <table style="width:100%; border-collapse:collapse;">
                                    @foreach ($shownFiles as $f)
                                        <tr>
                                            <td style="padding:3px 0; text-align:center;">
                                                <img src="{{ public_path('berkas-customer/' . basename(str_replace('\\', '/', (string) $f))) }}"
                                                    style="width:120px; max-width:100%; height:auto; border:1px solid #ddd; border-radius:6px;" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>

                                @if ($remaining > 0)
                                    <div style="margin-top:4px; font-size:9pt; color:#666;">
                                        +{{ $remaining }} foto lainnya
                                    </div>
                                @endif
                            @else
                                <span style="color: gray; font-size: 12px;">Tidak ada file</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table width="100%" cellspacing="0" cellpadding="0" style="padding-top: 20px">
            <tbody style="font-size: 11pt">
                <tr>
                    <td width="50%">
                    </td>
                    <td width="50%" class="text-center">
                        <span style="font-size: 10pt;"
                            class="text-biasa">{{ $res['identitas']->tempatterima ?? '-' }},
                            {{ \Carbon\Carbon::parse($res['identitas']->tanggalselesaiterima)->isoFormat('DD MMMM Y') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td width="50%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa"> Pelanggan </span>
                    </td>
                    <td width="50%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa"> Diterima dari</span>
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        <img src="data:image/png;base64, {!! $res['ttdPelanggan'] !!}"
                            style="margin-top: 5px; margin-bottom: 5px">
                    </td>
                    <td align="center">
                        <img src="data:image/png;base64, {!! $res['ttdPetugas'] !!}"
                            style="margin-top: 5px; margin-bottom: 5px">
                    </td>
                </tr>
                <tr>
                    <td height="10" valign="bottom" height="100" width="15%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">(
                            {{ $res['identitas']->penerima }}
                            )</span>
                    </td>
                    <td height="10" valign="bottom" height="100" width="15%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">( {{ $res['identitas']->petugasterima }}
                            )</span>
                    </td>
                </tr>
            <tbody>
        </table>
</body>

</html>
