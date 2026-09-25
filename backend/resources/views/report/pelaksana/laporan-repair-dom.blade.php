<!DOCTYPE html>
<html>

<head>
    <title>FMMO-163-14.4.3.b-71.7 Investigasi Kerusakan Alat Pelanggan</title>
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
            font-size: 10pt;
        }

        .pdf-header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 100px;
            width: 100%;
            z-index: 10;
        }

        .pdf-footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 70px;
            width: 100%;
            z-index: 10;
        }

        .page-break {
            page-break-before: always;
        }

        .section-title {
            font-size: 9pt;
            color: #000000;
            font-weight: bold;
            border-bottom: 2px solid #000;
            display: inline-block;
            padding-bottom: 2px;
        }

        .section-subtitle {
            font-style: italic;
            font-size: 7pt;
            font-family: 'Courier New', monospace;
            color: #000000;
        }

        .text-biasa {
            font-size: 9pt;
            color: #000;
        }

        .tbl-data {
            border-collapse: collapse;
            width: 100%;
            font-size: 9pt;
        }

        /*
            PENTING:
            table-header-group membuat header tabel otomatis tampil ulang
            saat tabel lanjut ke halaman berikutnya.
        */
        .tbl-data thead {
            display: table-header-group;
            background-color: #f2f2f2;
        }

        .tbl-data tfoot {
            display: table-footer-group;
        }

        .tbl-data tbody {
            display: table-row-group;
        }

        /*
            Jangan dibuat avoid.
            Jika avoid, row/tabel panjang akan dipaksa tetap satu halaman
            dan bisa menyebabkan blank page.
        */
        .tbl-data tr {
            page-break-inside: auto;
            break-inside: auto;
        }

        .tbl-data th {
            font-size: 9pt;
            text-align: center;
            vertical-align: middle;
            padding: 5px;
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .tbl-data td {
            font-size: 9pt;
            vertical-align: top;
            padding: 5px;
            line-height: 1.35;
        }

        .photo-stack {
            width: 100%;
            text-align: center;
        }

        .photo-card {
            width: 100%;
            text-align: center;
            margin-bottom: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .photo-card:last-child {
            margin-bottom: 0;
        }

        .photo-box {
            width: 100%;
            text-align: center;
            vertical-align: middle;
        }

        .photo-img {
            max-width: 245px;
            max-height: 165px;
            width: auto;
            height: auto;
            display: inline-block;
            margin: 0 auto;
        }

        .photo-caption {
            font-size: 8pt;
            margin-top: 4px;
            text-align: center;
            line-height: 1.25;
            font-family: 'Courier New', monospace;
            color: #000;
            word-wrap: break-word;
        }

        /*
            Dokumentasi compact untuk tabel yang kolomnya sempit.
            Tetap satu per satu ke bawah.
        */
        .photo-stack-compact {
            width: 100%;
            text-align: center;
        }

        .photo-card-compact {
            width: 100%;
            text-align: center;
            margin-bottom: 9px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .photo-card-compact:last-child {
            margin-bottom: 0;
        }

        .photo-img-compact {
            max-width: 185px;
            max-height: 120px;
            width: auto;
            height: auto;
            display: inline-block;
            margin: 0 auto;
        }

        .photo-caption-compact {
            font-size: 7pt;
            margin-top: 3px;
            text-align: center;
            line-height: 1.1;
            font-family: 'Courier New', monospace;
            color: #000;
            word-wrap: break-word;
        }

        /*
            Khusus Hasil Repair.
            Gambar dibuat per baris agar DOMPDF tidak membuat blank page.
            Border antar baris lanjutan dihilangkan agar tetap terlihat satu data.
        */
        .hasil-repair-section {
            page-break-before: always;
        }

        .hasil-repair-table {
            page-break-inside: auto;
            break-inside: auto;
            border-collapse: collapse;
            width: 100%;
        }

        .hasil-repair-table thead {
            display: table-header-group;
        }

        .hasil-repair-table tbody {
            display: table-row-group;
        }

        .hasil-repair-table tr {
            page-break-inside: auto;
            break-inside: auto;
        }

        .hasil-repair-table th,
        .hasil-repair-table td {
            border: 1px solid #000;
        }

        .hasil-photo-item {
            width: 100%;
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
            margin: 0;
            padding: 0;
        }

        .hasil-photo-img {
            max-width: 230px;
            max-height: 145px;
            width: auto;
            height: auto;
            display: inline-block;
            margin: 0 auto;
        }

        .hasil-photo-caption {
            font-size: 7.5pt;
            margin-top: 3px;
            text-align: center;
            line-height: 1.15;
            font-family: 'Courier New', monospace;
            color: #000;
            word-wrap: break-word;
        }

        /*
            Baris pertama data hasil repair:
            border bawah dihilangkan agar tidak terlihat terpisah
            dengan gambar lanjutan di bawahnya.
        */
        .hasil-row-main td {
            border-bottom: none !important;
        }

        /*
            Baris lanjutan gambar:
            border atas dan bawah dihilangkan agar terlihat masih satu data.
        */
        .hasil-row-photo td {
            border-top: none !important;
            border-bottom: none !important;
        }

        /*
            Baris gambar terakhir:
            border bawah dikembalikan untuk menutup tabel.
        */
        .hasil-row-photo-last td {
            border-top: none !important;
            border-bottom: 1px solid #000 !important;
        }

        /*
            Kalau data hanya 1 foto atau tidak punya foto,
            border bawah tetap normal.
        */
        .hasil-row-single td,
        .hasil-row-no-photo td {
            border-bottom: 1px solid #000 !important;
        }

        .no-photo {
            font-size: 8pt;
            color: #555;
            font-style: italic;
            text-align: center;
        }

        .rich-text {
            font-size: 9pt;
            line-height: 1.35;
            color: #000;
        }

        .rich-text p {
            margin: 0 0 5px 0;
        }

        .rich-text ul,
        .rich-text ol {
            margin: 0 0 5px 15px;
            padding-left: 8px;
        }

        .rich-text li {
            margin: 0 0 3px 0;
        }

        .rich-text blockquote {
            margin: 0 0 5px 0;
            padding-left: 8px;
            border-left: 2px solid #777;
        }

        .repair-action-section {
            page-break-before: always;
        }

        .paragraph-text {
            font-size: 9pt;
            color: #000000;
            text-align: justify;
            margin: 0 auto;
            max-width: 95%;
            font-weight: bold;
        }

        .paragraph-text-en {
            font-size: 9pt;
            color: #000000;
            text-align: justify;
            margin: 20px auto 0 auto;
            max-width: 95%;
            font-style: italic;
            font-weight: normal;
        }
    </style>
</head>

<body>
    @php
        $fotoPath = function ($filename) {
            if (empty($filename)) {
                return null;
            }

            $filename = trim((string) $filename);

            if ($filename === '') {
                return null;
            }

            if (strpos($filename, 'http://') === 0 || strpos($filename, 'https://') === 0) {
                return $filename;
            }

            $filename = basename(str_replace('\\', '/', $filename));

            $candidatePaths = [
                public_path('berkas-laporan-repair/' . $filename),
                public_path('berkas-mitra/' . $filename),
                public_path('storage/berkas-laporan-repair/' . $filename),
                public_path('storage/berkas-mitra/' . $filename),
                public_path('storage/' . $filename),
                public_path($filename),
            ];

            foreach ($candidatePaths as $path) {
                if (!empty($path) && file_exists($path)) {
                    return $path;
                }
            }

            return public_path('berkas-laporan-repair/' . $filename);
        };

        $fotoExists = function ($filename) use ($fotoPath) {
            if (empty($filename)) {
                return false;
            }

            $path = $fotoPath($filename);

            if (empty($path)) {
                return false;
            }

            if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
                return true;
            }

            return file_exists($path);
        };

        $getValidFotoList = function ($fotos, $legacyFoto = null) use ($fotoExists) {
            $listFoto = collect();

            if (!empty($fotos) && count($fotos) > 0) {
                $listFoto = collect($fotos);
            }

            if ($listFoto->count() === 0 && !empty($legacyFoto)) {
                $listFoto = collect([
                    (object) [
                        'namafile' => basename($legacyFoto),
                        'keterangan_gambar' => null,
                        'urut' => 1,
                        'is_legacy' => true,
                    ],
                ]);
            }

            $validFoto = [];

            foreach ($listFoto as $foto) {
                $filename = $foto->namafile ?? null;

                if (empty($filename)) {
                    continue;
                }

                if (!$fotoExists($filename)) {
                    continue;
                }

                $validFoto[] = $foto;
            }

            return collect($validFoto);
        };

        $renderFotoStack = function ($fotos, $legacyFoto = null) use ($fotoPath, $getValidFotoList) {
            $listFoto = $getValidFotoList($fotos, $legacyFoto);

            if ($listFoto->count() === 0) {
                return '<div class="no-photo">Tidak ada dokumentasi</div>';
            }

            $html = '<div class="photo-stack">';

            foreach ($listFoto as $foto) {
                $filename = $foto->namafile ?? null;
                $caption = $foto->keterangan_gambar ?? '';

                if (trim($caption) === '') {
                    $caption = '-';
                }

                $html .= '<div class="photo-card">';
                $html .= '<div class="photo-box">';
                $html .= '<img src="' . e($fotoPath($filename)) . '" class="photo-img">';
                $html .= '</div>';
                $html .= '<div class="photo-caption">' . e($caption) . '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';

            return $html;
        };

        $renderFotoStackCompact = function ($fotos, $legacyFoto = null) use ($fotoPath, $getValidFotoList) {
            $listFoto = $getValidFotoList($fotos, $legacyFoto);

            if ($listFoto->count() === 0) {
                return '<div class="no-photo">Tidak ada dokumentasi</div>';
            }

            $html = '<div class="photo-stack-compact">';

            foreach ($listFoto as $foto) {
                $filename = $foto->namafile ?? null;
                $caption = $foto->keterangan_gambar ?? '';

                if (trim($caption) === '') {
                    $caption = '-';
                }

                $html .= '<div class="photo-card-compact">';
                $html .= '<img src="' . e($fotoPath($filename)) . '" class="photo-img-compact">';
                $html .= '<div class="photo-caption-compact">' . e($caption) . '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';

            return $html;
        };

        $renderHasilRepairPhoto = function ($foto) use ($fotoPath) {
            $filename = $foto->namafile ?? null;
            $caption = $foto->keterangan_gambar ?? '';

            if (trim($caption) === '') {
                $caption = '-';
            }

            $html = '<div class="hasil-photo-item">';
            $html .= '<img src="' . e($fotoPath($filename)) . '" class="hasil-photo-img">';
            $html .= '<div class="hasil-photo-caption">' . e($caption) . '</div>';
            $html .= '</div>';

            return $html;
        };

        $renderRichText = function ($value) {
            if ($value === null || trim((string) $value) === '') {
                return '<div class="rich-text">-</div>';
            }

            $value = trim((string) $value);
            $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4>';
            $clean = strip_tags($value, $allowedTags);
            $clean = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $clean);
            $plainText = trim(str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

            if ($plainText === '') {
                return '<div class="rich-text">-</div>';
            }

            if ($clean === strip_tags($clean)) {
                $clean = nl2br(e($clean));
            }

            return '<div class="rich-text">' . $clean . '</div>';
        };

        $tanggalTerbit = !empty($res['alat']->tglsetujumanagerlaporanrepair)
            ? $res['alat']->tglsetujumanagerlaporanrepair
            : now();
    @endphp

    <div style="margin-top: -90px; margin-bottom: 20px;">
        <img src="{{ public_path('img/header_cover_NON_KAN.png') }}" alt="Header Halaman Pertama"
            style="width:100%; height:auto; display:block;">
        <div style="font-size: 7pt; color:#000000; margin-top: -17px; text-align:right">
            FMMO-163-14.4.3.b-71.7
        </div>
    </div>

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

    <div style="margin-top: 11px; margin-bottom: 5px; margin-left: 25px">
        <table width="100%" style="margin-top: -35px;" border="0">
            <tr>
                <td width="35%"></td>
                <td width="30%">
                    <img src="{{ public_path('img/pln.png') }}" alt="Logo PLN" style="height:5%; width: 95%">
                </td>
                <td width="35%"></td>
            </tr>
        </table>

        <table width="100%" style="margin-top: -5px;" border="0">
            <tr>
                <td width="35%"></td>
                <td align="center" width="40%" style="line-height: 1.1;">
                    <div style="font-size: 11pt; color:#000000; font-weight: bold; margin-bottom: 2px;">
                        FORMULIR
                    </div>
                    <div style="font-size: 10pt; color:#000000; font-weight: bold; margin-bottom: 2px;">
                        Investigasi Kerusakan Alat Pelanggan
                    </div>
                    <div style="font-size: 7pt; color:#000000; margin-bottom: 1px;">
                        {{ $res['alat']->nolaporanrepair ?? '-' }}
                    </div>
                </td>
                <td width="35%"></td>
            </tr>
        </table>

        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td style="line-height: 1;">
                    <div
                        style="font-size: 10pt; font-weight: bold; color: #000000; border-bottom: double 2px #000; display: inline-block; padding-bottom: 1px; font-family: Arial, sans-serif;">
                        IDENTITAS ALAT
                    </div>
                    <br>
                    <span
                        style="font-style: italic; font-size: 10pt; font-family: 'Courier New', monospace; color: #000000;">
                        INSTRUMENT DETAILS
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 5px; margin-bottom: 5px; margin-left: 50px">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td width="25%" style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Nama Alat Ukur
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Instrument
                    </div>
                </td>
                <td width="3%">:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['alat']->namaproduk ?? '-' }}
                </td>
            </tr>

            <tr>
                <td style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Merek/Tipe
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Manufacture
                    </div>
                </td>
                <td>:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['alat']->namamerk ?? '-' }} / {{ $res['alat']->namatipe ?? '-' }}
                </td>
            </tr>

            <tr>
                <td style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Nomor Seri
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Serial Number
                    </div>
                </td>
                <td>:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['alat']->namaserialnumber ?? '-' }}
                </td>
            </tr>

            <tr>
                <td style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Tempat Repair
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Reparation Place
                    </div>
                </td>
                <td>:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['alat']->lokasirepair ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 10px; margin-bottom: 5px; margin-left: 25px">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td style="line-height: 1;">
                    <div
                        style="font-size: 10pt; font-weight: bold; color: #000000; border-bottom: double 2px #000; display: inline-block; padding-bottom: 1px; font-family: Arial, sans-serif;">
                        IDENTITAS PEMILIK
                    </div>
                    <br>
                    <span
                        style="font-style: italic; font-size: 10pt; font-family: 'Courier New', monospace; color: #000000;">
                        OWNER IDENTIFICATION
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 5px; margin-bottom: 5px; margin-left: 50px">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td width="25%" style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Nama
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Designation
                    </div>
                </td>
                <td width="3%">:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['identitas']->namaperusahaan ?? '-' }}
                </td>
            </tr>

            <tr>
                <td style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Alamat
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Address
                    </div>
                </td>
                <td>:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['identitas']->alamatktr ?? '-' }}
                </td>
            </tr>

            <tr>
                <td style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Tanggal Order
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Order Date
                    </div>
                </td>
                <td>:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['identitas']->tglregistrasi ?? '-' }}
                </td>
            </tr>

            <tr>
                <td style="line-height: 1.4;">
                    <div style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                        Nomor Order
                    </div>
                    <div
                        style="font-size: 8pt; font-style: italic; font-family: 'Courier New', monospace; color: #000;">
                        Order Number
                    </div>
                </td>
                <td>:</td>
                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    {{ $res['alat']->noorderalat ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px; margin-bottom: 5px; margin-left: 25px">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td width="28%" style="line-height: 1;">
                    <div
                        style="font-size: 10pt; font-weight: bold; color: #000000; border-bottom: double 2px #000; display: inline-block; padding-bottom: 1px; font-family: Arial, sans-serif;">
                        STATUS REPAIR
                    </div>
                    <br>
                    <span
                        style="font-style: italic; font-size: 10pt; font-family: 'Courier New', monospace; color: #000000;">
                        REPAIR STATUS
                    </span>
                </td>

                <td width="3%" style="font-size: 9pt; font-weight: bold; color: #000;">:</td>

                <td style="font-size: 9pt; font-weight: bold; color: #000; font-family: Arial, sans-serif;">
                    @php
                        $statusRepair = strtolower(trim($res['alat']->statusrepair ?? ''));
                    @endphp

                    @if ($statusRepair === strtolower('Repair Berhasil'))
                        Repaired
                    @else
                        Unrepairable
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px; margin-bottom: 5px; margin-left: 25px">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td style="line-height: 1;">
                    <div
                        style="font-size: 10pt; font-weight: bold; color: #000000; border-bottom: double 2px #000; display: inline-block; padding-bottom: 1px; font-family: Arial, sans-serif;">
                        PENGESAHAN
                    </div>
                    <br>
                    <span
                        style="font-style: italic; font-size: 10pt; font-family: 'Courier New', monospace; color: #000000;">
                        Authorization
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 20px; margin-bottom: 5px; margin-left: 25px">
        <table width="100%" style="margin-top: -10px;">
            <tr>
                <td width="60%" valign="top" style="padding-left: 15px;"></td>
                <td width="40%" align="center">
                    <table cellspacing="0" cellpadding="0" style="line-height: 1.1;">
                        <tr>
                            <td style="font-size: 10pt; font-weight: bold; color:#000000;">
                                Laporan ini terdiri dari
                            </td>
                            <td style="font-size: 10pt; font-weight: bold; color:#000000;" align="center"
                                width="30">
                                {{ $jumlahHalaman ?? '...' }}
                            </td>
                            <td style="font-size: 10pt; font-weight: bold; color:#000000;">
                                Halaman
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size: 10pt; font-style: italic; color:#000000;">
                                This report consist of
                            </td>
                            <td style="font-size: 10pt; font-style: italic; color:#000000;" align="center">
                                {{ $jumlahHalaman ?? '...' }}
                            </td>
                            <td style="font-size: 10pt; font-style: italic; color:#000000;">
                                Pages
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size: 10pt; font-weight: bold; color:#000000;">
                                Diterbitkan tanggal
                            </td>
                            <td></td>
                            <td style="font-size: 10pt; color:#000000;">
                                {{ \Carbon\Carbon::parse($tanggalTerbit)->locale('id')->isoFormat('D MMMM Y') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size: 10pt; font-style: italic; color:#000000;">
                                Date of Issue
                            </td>
                            <td></td>
                            <td style="font-size: 10pt; color:#000000;">
                                {{ \Carbon\Carbon::parse($tanggalTerbit)->format('F d, Y') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if (!empty($res['alat']->managersetujulaporanrepairfk) && ($res['halamanPertama'] ?? false) == true)
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 30px">
            <tbody style="font-size: 11pt">
                <tr>
                    <td width="60%"></td>
                    <td align="center" width="40%" class="text-center">
                        <span style="font-size: 10pt;color:#000000"><b>Manager Repair</b></span><br>
                        <span style="font-size: 10pt;color:#000000; font-style: italic;">Manager Repair</span>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td align="center">
                        <img src="data:image/png;base64, {!! $res['ttdManager'] !!}"
                            style="margin-top: 5px; margin-bottom: 5px">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td align="center" height="10" valign="bottom" width="15%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            <b>{{ $res['alat']->namamanager ?? '-' }}</b>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>

    <div class="pdf-header">
        <img src="{{ public_path('img/Header_isi_Repair.png') }}" alt="Header Halaman Lainnya"
            style="width:100%; height:auto; display:block;">
    </div>

    <div class="pdf-content">
        <table width="100%" style="margin-top: 30px;" border="0">
            <tr>
                <td width="30%">
                    <div class="section-title">STATUS ALAT</div><br>
                    <span class="section-subtitle">Instrument Status</span>
                </td>
            </tr>
        </table>

        <table class="tbl-data" style="margin-top: 20px;" width="100%" border="1" cellspacing="0"
            cellpadding="5">
            <thead>
                <tr>
                    <th width="25%">Bagian Alat</th>
                    <th width="45%">Dokumentasi</th>
                    <th width="30%">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($res['statusRepair'] as $status)
                    <tr>
                        <td>
                            {!! $renderRichText($status->bagianalat ?? '-') !!}
                        </td>
                        <td align="center">
                            {!! $renderFotoStack($status->fotos ?? collect(), $status->fotoalatstatus ?? null) !!}
                        </td>
                        <td>
                            {!! $renderRichText($status->kondisi ?? '-') !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" align="center">
                            <span class="no-photo">Data status alat belum tersedia.</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="repair-action-section">
            <table width="100%" style="margin-top: 30px;" border="0">
                <tr>
                    <td width="30%">
                        <div class="section-title">TINDAKAN TEKNIS</div><br>
                        <span class="section-subtitle">Repair Action</span>
                    </td>
                </tr>
            </table>

            <table class="tbl-data" style="margin-top: 20px;" width="100%" border="1" cellspacing="0"
                cellpadding="5">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="21%">Bagian Alat</th>
                        <th width="24%">Dokumentasi</th>
                        <th width="18%">Penanganan</th>
                        <th width="14%">Status</th>
                        <th width="18%">Sparepart & Material Consumable</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($res['laporanRepair'] as $index => $alat)
                        <tr>
                            <td align="center">
                                {{ $index + 1 }}
                            </td>
                            <td>
                                {!! $renderRichText($alat->bagianalatlaporan ?? '-') !!}
                            </td>
                            <td align="center">
                                {!! $renderFotoStackCompact($alat->fotos ?? collect(), $alat->fotoalatrepair ?? null) !!}
                            </td>
                            <td>
                                {!! $renderRichText($alat->penanganan ?? '-') !!}
                            </td>
                            <td>
                                {!! $renderRichText($alat->status ?? '-') !!}
                            </td>
                            <td>
                                {!! $renderRichText($alat->sparepart ?? '-') !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" align="center">
                                <span class="no-photo">Data tindakan teknis belum tersedia.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="hasil-repair-section">
            <table width="100%" style="margin-top: 30px;" border="0">
                <tr>
                    <td width="30%">
                        <div class="section-title">HASIL REPAIR</div><br>
                        <span class="section-subtitle">Repair Result</span>
                    </td>
                </tr>
            </table>

            <table class="tbl-data hasil-repair-table" style="margin-top: 20px;" width="100%" border="1"
                cellspacing="0" cellpadding="5">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="35%">Hasil</th>
                        <th width="40%">Dokumentasi</th>
                        <th width="20%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($res['hasilRepair'] as $index => $hasil)
                        @php
                            $listFotoHasil = $getValidFotoList(
                                $hasil->fotos ?? collect(),
                                $hasil->fotohasilrepair ?? null,
                            );

                            $jumlahFotoHasil = $listFotoHasil->count();
                        @endphp

                        @if ($jumlahFotoHasil > 0)
                            @foreach ($listFotoHasil as $fotoIndex => $foto)
                                @php
                                    $isFirstPhoto = $fotoIndex === 0;
                                    $isLastPhoto = $fotoIndex === $jumlahFotoHasil - 1;

                                    if ($isFirstPhoto && $isLastPhoto) {
                                        $rowClass = 'hasil-row-single';
                                    } elseif ($isFirstPhoto) {
                                        $rowClass = 'hasil-row-main';
                                    } elseif ($isLastPhoto) {
                                        $rowClass = 'hasil-row-photo-last';
                                    } else {
                                        $rowClass = 'hasil-row-photo';
                                    }
                                @endphp

                                @if ($isFirstPhoto)
                                    <tr class="{{ $rowClass }}">
                                        <td align="center" width="5%">
                                            {{ $index + 1 }}
                                        </td>

                                        <td width="35%">
                                            {!! $renderRichText($hasil->hasil ?? '-') !!}
                                        </td>

                                        <td align="center" width="40%">
                                            {!! $renderHasilRepairPhoto($foto) !!}
                                        </td>

                                        <td width="20%">
                                            {!! $renderRichText($hasil->status ?? '-') !!}
                                        </td>
                                    </tr>
                                @else
                                    <tr class="{{ $rowClass }}">
                                        <td width="5%"></td>

                                        <td width="35%"></td>

                                        <td align="center" width="40%">
                                            {!! $renderHasilRepairPhoto($foto) !!}
                                        </td>

                                        <td width="20%"></td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            <tr class="hasil-row-no-photo">
                                <td align="center" width="5%">
                                    {{ $index + 1 }}
                                </td>

                                <td width="35%">
                                    {!! $renderRichText($hasil->hasil ?? '-') !!}
                                </td>

                                <td align="center" width="40%">
                                    <span class="no-photo">Tidak ada dokumentasi</span>
                                </td>

                                <td width="20%">
                                    {!! $renderRichText($hasil->status ?? '-') !!}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" align="center">
                                <span class="no-photo">Data hasil repair belum tersedia.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="sheet">
        <table width="100%" style="margin-top: 30px;" border="0">
            <tr>
                <td width="30%">
                    <div class="section-title">KESIMPULAN</div><br>
                    <span class="section-subtitle">Conclusion</span>
                </td>
            </tr>
        </table>

        <table width="100%" style="margin-top: 5px;" border="0">
            <tr>
                <td style="width: 100%;">
                    <div class="paragraph-text">
                        {!! $renderRichText($res['alat']->kesimpulanrepair ?? '-') !!}
                    </div>

                    <div class="paragraph-text-en">
                        @foreach (explode("\n", trim($res['alat']->kesimpulanrepair_en ?? '')) as $paragraph)
                            @if (trim($paragraph) !== '')
                                <p style="margin: 6px 0;">
                                    {{ $paragraph }}
                                </p>
                            @endif
                        @endforeach
                    </div>
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
                            Penyelia Repair
                        </span>
                    </td>
                    <td align="center" width="30%">
                        <span style="font-size: 10pt;" class="text-biasa">
                            Pelaksana Repair
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        @if (!empty($res['alat']->asmansetujulaporanrepairfk))
                            <img src="data:image/png;base64, {!! $res['ttdAsman'] !!}"
                                style="margin-top: 5px; margin-bottom: 5px">
                        @endif
                    </td>
                    <td align="center">
                        @if (!empty($res['alat']->penyeliasetujulaporanrepairfk))
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
                    <td align="center" height="10" valign="bottom" width="15%" class="text-center">
                        @if (!empty($res['alat']->asmansetujulaporanrepairfk))
                            <span style="font-size: 10pt;" class="text-biasa">
                                ( {{ $res['alat']->asamanverifikasi ?? '-' }} )
                            </span>
                        @endif
                    </td>
                    <td align="center" height="10" valign="bottom" width="15%" class="text-center">
                        @if (!empty($res['alat']->penyeliasetujulaporanrepairfk))
                            <span style="font-size: 10pt;" class="text-biasa">
                                ( {{ $res['alat']->penyeliateknik ?? '-' }} )
                            </span>
                        @endif
                    </td>
                    <td align="center" height="10" valign="bottom" width="15%" class="text-center">
                        <span style="font-size: 10pt;" class="text-biasa">
                            ( {{ $res['alat']->pelaksanateknik ?? '-' }} )
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
