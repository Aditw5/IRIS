<!DOCTYPE html>
<html>

<head>
    <!-- <link rel="stylesheet" href="css/paper.css "> -->
    <!-- <link rel="stylesheet" href="css/table-v2.css"> -->
    <link rel="stylesheet" href="css/style.css">
    <title>Permintaan Barang dan Jasa</title>
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
        padding: 25px;
        margin: 0;
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

    .page-break {
        page-break-before: always;
    }

    .keep-together,
    .keep-together table,
    .keep-together tr,
    .keep-together td {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        -webkit-page-break-inside: avoid !important;
    }

    .pbj-sign-package {
        display: block;
    }

    .pbj-sign-package .sign-table {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        -webkit-page-break-inside: avoid !important;
    }

    .pbj-sign-package .pbj-table {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    .pbj-sign-package .sign-table {
        page-break-before: avoid !important;
        break-before: avoid !important;
    }

    .sign-table {
        margin-top: 12px;
    }

    .sign-cell {
        font-size: 7pt;
        line-height: 1.15;
    }

    .sign-cell .cap {
        margin: 0 0 1px;
    }

    .sign-cell img {
        height: 60px;
        margin: 2px 0;
    }

    .sign-cell .name {
        margin: 2px 0 0;
    }

    .sign-placeholder {
        height: 60px;
    }

    .page-now:before {
        content: counter(page);
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .text-left {
        text-align: left;
    }

    .fw-bold {
        font-weight: 700;
    }

    .currency-nowrap {
        white-space: nowrap;
    }

    .fixed-wrap {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .fixed-wrap-anywhere {
        white-space: normal !important;
        word-break: break-all;
        overflow-wrap: anywhere;
    }

    .pbj-table {
        width: 100%;
        max-width: 100%;
        border-collapse: collapse;
        table-layout: fixed !important;
        font-family: Arial, Helvetica, sans-serif;
        margin-top: 8px;
        font-size: 6.2pt;
        line-height: 1.12;
    }

    .pbj-table thead {
        display: table-header-group;
    }

    .pbj-table tr {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .pbj-table th,
    .pbj-table td {
        border: 1px solid #000;
        vertical-align: middle;
        overflow: hidden;
        box-sizing: border-box;
    }

    .pbj-table th {
        background: #e4ebf4;
        text-align: center;
        font-weight: 700;
        padding: 4px 2px;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .pbj-table td {
        padding: 5px 4px;
    }

    .pbj-small-cell {
        padding-left: 1px !important;
        padding-right: 1px !important;
        text-align: center;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .pbj-no-cell {
        padding-left: 1px !important;
        padding-right: 1px !important;
        text-align: center;
        white-space: nowrap;
    }

    .pbj-wo-cell {
        padding-left: 1px !important;
        padding-right: 1px !important;
        text-align: center;
        white-space: normal !important;
        word-break: break-all;
        overflow-wrap: anywhere;
    }

    .pbj-header-small {
        font-size: 5.8pt;
        line-height: 1.05;
        padding-left: 1px !important;
        padding-right: 1px !important;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .pbj-satuan-cell {
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
        font-size: 6.1pt;
    }

    /*
     * SUMMARY DI DALAM TABEL UTAMA
     * Catatan dibuat colspan 6 agar melebar dari No. sampai Satuan.
     * Tidak pakai rowspan, supaya Dompdf tidak mengacak garis.
     */
    .summary-note-wide-top {
        border-bottom: none !important;
        vertical-align: top !important;
        padding: 7px 8px 2px 45px !important;
        white-space: normal !important;
        word-break: normal !important;
        overflow-wrap: break-word !important;
        line-height: 1.2;
    }

    .summary-note-wide-middle {
        border-top: none !important;
        border-bottom: none !important;
        padding: 2px 8px !important;
    }

    .summary-note-wide-bottom {
        border-top: none !important;
        padding: 2px 8px !important;
    }

    .summary-label-cell {
        font-weight: 700;
        text-align: left;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: break-word;
        line-height: 1.1;
        padding: 6px 7px !important;
    }

    .summary-value-cell {
        font-weight: 700;
        text-align: right;
        white-space: nowrap !important;
        padding: 6px 7px !important;
    }

    .summary-empty-left {
        border-right: none !important;
    }

    .summary-empty-right {
        border-left: none !important;
    }

    .summary-total-cell {
        background: #c9c9c9;
        font-weight: 700;
    }
</style>

<body>
    <div class="pdf-header">
        <table width="100%" cellspacing="0" cellpadding="0" border="1"
            style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;">
            <tr>
                <td style="width:140px;vertical-align:top;">
                    <img src="img/pln.png" alt="PLN Nusantara Power" style="margin:8px;height:42px;">
                </td>

                <td style="padding:0;vertical-align:top;">
                    <div
                        style="background:#2F3A8F;color:#fff;text-align:center;font-weight:700;font-size:9pt;line-height:1.15;padding:4px 8px;letter-spacing:0.3px;">
                        PT.PLN NUSANTARA POWER<br>
                        UNIT MAINTENANCE REPAIR &amp; OVERHOUL
                    </div>
                    <div
                        style="text-align:center;font-weight:700;font-size:8pt;color:#000;padding:4px 8px;border-top:1px solid #000;line-height:1.2;">
                        FORMULIR<br>
                        PERMINTAAN BARANG/JASA
                    </div>
                </td>

                <td style="width:250px;vertical-align:top;padding:0;">
                    <table width="100%" cellspacing="0" cellpadding="0" border="none"
                        style="border-collapse:collapse;font-size:6pt;line-height:1.4;">
                        <tr>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;width:48%;white-space:nowrap;">
                                No. Dokumen
                            </td>
                            <td
                                style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;width:6%;text-align:center;">
                                :
                            </td>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;width:46%;">
                                FMH-9.3.1.03.02
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;">
                                Tgl Terbit
                            </td>
                            <td
                                style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;text-align:center;">
                                :
                            </td>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;">
                                16 - 03 - 2023
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;">
                                Revisi
                            </td>
                            <td
                                style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;text-align:center;">
                                :
                            </td>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;">
                                03
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right:1px solid #000;padding:3px 6px;">
                                Halaman
                            </td>
                            <td style="border-right:1px solid #000;padding:3px 6px;text-align:center;">
                                :
                            </td>
                            <td style="padding:3px 6px;white-space:nowrap;">
                                <span class="page-now"></span>/{{ $jumlahHalaman ?? 1 }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @php
        $pbj = $res['pbj'][0] ?? null;

        $permintaanKe = '-';
        if ($pbj && !empty($pbj->nosuratpbj)) {
            $p = explode('/', $pbj->nosuratpbj);
            $permintaanKe = $p[0] ?? '-';
        }

        try {
            $tahun = \Carbon\Carbon::parse($pbj->tglpengajuan)->format('Y');
        } catch (\Throwable $e) {
            $tahun = date('Y');
        }
    @endphp

    <table width="100%" cellspacing="0" cellpadding="0" border="0"
        style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;margin-top:-30px;">
        <tr>
            <td style="width:60%;vertical-align:top;padding:0 8px 0 8px;">
                <table width="100%" cellspacing="0" cellpadding="0" border="0"
                    style="border-collapse:collapse;font-size:7pt;line-height:1.25;">
                    <tr>
                        <td style="width:36%;padding:2px 0;font-weight:700;">Judul permintaan</td>
                        <td style="width:3%;padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->judulpermintaan ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Material / Jasa</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ strtoupper($pbj->jenispbj ?? ($pbj->kebutuhan ?? '-')) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Dasar Anggaran</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ ucfirst(strtolower($pbj->kebutuhan ?? '-')) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Bidang Pemohon</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->pemohonpbj ?? ($pbj->namalengkap ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Prioritas</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->prioritaspbj ?? '-' ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">User Unit</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ !empty($pbj->user) ? $pbj->user : '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Tahun</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $tahun }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Permintaan ke</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $permintaanKe }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Nomer Proyek</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->noproyek ?? '-' }}</td>
                    </tr>
                </table>
            </td>

            <td style="width:40%;vertical-align:top;padding:0 8px;">
                <table width="100%" cellspacing="0" cellpadding="0" border="0"
                    style="border-collapse:collapse;font-size:7pt;line-height:1.25;">
                    <tr>
                        <td style="width:35%;padding:2px 0;font-weight:700;">Nomor Surat</td>
                        <td style="width:3%;padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->nosuratpbj ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Manager Bidang</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->bidang ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Kepada</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->kepada ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">PRK</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->prk ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">WO</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ ($pbj->wo ?? '') !== '' ? $pbj->wo : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Pengadaan</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->pengadaanpbj ?? ($pbj->pengadaan ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 0;font-weight:700;">Cost Code</td>
                        <td style="padding:2px 0;text-align:center;">:</td>
                        <td style="padding:2px 0;">{{ $pbj->costcode ?? '-' }}</td>
                    </tr>
                    @if (!empty($pbj->nopr))
                        <tr>
                            <td style="padding:2px 0;font-weight:700;">No. PR</td>
                            <td style="padding:2px 0;text-align:center;">:</td>
                            <td style="padding:2px 0;">{{ $pbj->nopr ?? '-' }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @php
        $fmtRp = fn($n) => number_format((float) $n, 0, ',', '.');

        $parseHarga = function ($v) {
            $s = trim((string) $v);

            if ($s === '') {
                return 0;
            }

            if (preg_match('/^\d+$/', $s)) {
                return (float) $s;
            }

            $s = str_replace(['Rp', 'rp', ' ', ',', '.'], '', $s);

            return is_numeric($s) ? (float) $s : 0;
        };

        $items = $res['pbjdetail'] ?? [];
        $grand = 0;
        $ppnRate = $pbj->ppn ?? 0;

        $cw = [
            'no' => 'width:2.5%;min-width:2.5%;max-width:2.5%;',
            'nama' => 'width:16%;min-width:16%;max-width:16%;',
            'uraian' => 'width:31%;min-width:31%;max-width:31%;',
            'stock' => 'width:3.2%;min-width:3.2%;max-width:3.2%;',
            'qty' => 'width:3.2%;min-width:3.2%;max-width:3.2%;',
            'sat' => 'width:4.8%;min-width:4.8%;max-width:4.8%;',
            'hs' => 'width:9.5%;min-width:9.5%;max-width:9.5%;',
            'ht' => 'width:10.5%;min-width:10.5%;max-width:10.5%;',
            'ket' => 'width:16.3%;min-width:16.3%;max-width:16.3%;',
            'wo' => 'width:3%;min-width:3%;max-width:3%;',
        ];

        $padSmall = 'padding:4px 1px;';
        $padNormal = 'padding:5px 4px;';
        $tailSplitAt = 8;
        $shouldSplitTable = count($items) > $tailSplitAt;
    @endphp

    <table class="pbj-table" cellspacing="0" cellpadding="0" border="0">
        <colgroup>
            <col style="{{ $cw['no'] }}">
            <col style="{{ $cw['nama'] }}">
            <col style="{{ $cw['uraian'] }}">
            <col style="{{ $cw['stock'] }}">
            <col style="{{ $cw['qty'] }}">
            <col style="{{ $cw['sat'] }}">
            <col style="{{ $cw['hs'] }}">
            <col style="{{ $cw['ht'] }}">
            <col style="{{ $cw['ket'] }}">
            <col style="{{ $cw['wo'] }}">
        </colgroup>

        <thead>
            <tr>
                <th class="pbj-header-small" style="{{ $cw['no'] }}{{ $padSmall }}">No.</th>
                <th style="{{ $cw['nama'] }}{{ $padNormal }}">Nama Item</th>
                <th style="{{ $cw['uraian'] }}{{ $padNormal }}">Uraian Item</th>
                <th class="pbj-header-small" style="{{ $cw['stock'] }}{{ $padSmall }}">Stock<br>Code</th>
                <th class="pbj-header-small" style="{{ $cw['qty'] }}{{ $padSmall }}">Banyak</th>
                <th class="pbj-header-small" style="{{ $cw['sat'] }}{{ $padSmall }}">Satuan</th>
                <th style="{{ $cw['hs'] }}{{ $padNormal }}">Harga<br>Satuan</th>
                <th style="{{ $cw['ht'] }}{{ $padNormal }}">Harga Total</th>
                <th style="{{ $cw['ket'] }}{{ $padNormal }}">Keterangan</th>
                <th class="pbj-header-small" style="{{ $cw['wo'] }}{{ $padSmall }}">No.<br>WO</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($items as $i => $it)
                @if ($shouldSplitTable && $i === $tailSplitAt)
        </tbody>
    </table>

    <div class="page-break"></div>
    <div class="pbj-sign-package">
        <table class="pbj-table" cellspacing="0" cellpadding="0" border="0">
            <colgroup>
                <col style="{{ $cw['no'] }}">
                <col style="{{ $cw['nama'] }}">
                <col style="{{ $cw['uraian'] }}">
                <col style="{{ $cw['stock'] }}">
                <col style="{{ $cw['qty'] }}">
                <col style="{{ $cw['sat'] }}">
                <col style="{{ $cw['hs'] }}">
                <col style="{{ $cw['ht'] }}">
                <col style="{{ $cw['ket'] }}">
                <col style="{{ $cw['wo'] }}">
            </colgroup>

            <thead>
                <tr>
                    <th class="pbj-header-small" style="{{ $cw['no'] }}{{ $padSmall }}">No.</th>
                    <th style="{{ $cw['nama'] }}{{ $padNormal }}">Nama Item</th>
                    <th style="{{ $cw['uraian'] }}{{ $padNormal }}">Uraian Item</th>
                    <th class="pbj-header-small" style="{{ $cw['stock'] }}{{ $padSmall }}">Stock<br>Code</th>
                    <th class="pbj-header-small" style="{{ $cw['qty'] }}{{ $padSmall }}">Banyak</th>
                    <th class="pbj-header-small" style="{{ $cw['sat'] }}{{ $padSmall }}">Satuan</th>
                    <th style="{{ $cw['hs'] }}{{ $padNormal }}">Harga<br>Satuan</th>
                    <th style="{{ $cw['ht'] }}{{ $padNormal }}">Harga Total</th>
                    <th style="{{ $cw['ket'] }}{{ $padNormal }}">Keterangan</th>
                    <th class="pbj-header-small" style="{{ $cw['wo'] }}{{ $padSmall }}">No.<br>WO</th>
                </tr>
            </thead>

            <tbody>
                @endif
                @php
                    $nama = $it->namaitem ?? '-';

                    $uraianRaw = trim($it->uraiainitem ?? ($it->uraianitem ?? '-'));
                    $uraian = nl2br(e($uraianRaw !== '' ? $uraianRaw : '-'));

                    $stock = $it->stockcode ?? '-';

                    $qty = is_numeric($it->banyak ?? null) ? (float) $it->banyak : 0;
                    $qtyView = fmod($qty, 1) == 0 ? number_format($qty, 0, ',', '.') : number_format($qty, 2, ',', '.');

                    $satRaw = trim((string) ($it->satuan ?? '-'));
                    $sat = $satRaw !== '' ? strtoupper($satRaw) : '-';

                    $hs = $parseHarga($it->hargasatuan ?? 0);
                    $ht = $qty * $hs;

                    $ketRaw = trim($it->keterangan ?? '-');
                    $ket = $ketRaw !== '' ? $ketRaw : '-';

                    $woView = ($pbj->wo ?? '') !== '' ? $pbj->wo : '-';

                    $grand += $ht;
                @endphp

                <tr>
                    <td class="pbj-no-cell" style="{{ $cw['no'] }}{{ $padSmall }}">
                        {{ $i + 1 }}
                    </td>

                    <td class="text-center fixed-wrap" style="{{ $cw['nama'] }}{{ $padNormal }}">
                        {{ $nama }}
                    </td>

                    <td class="text-left fixed-wrap" style="{{ $cw['uraian'] }}{{ $padNormal }}">
                        {!! $uraian !!}
                    </td>

                    <td class="pbj-small-cell" style="{{ $cw['stock'] }}{{ $padSmall }}">
                        {{ $stock }}
                    </td>

                    <td class="pbj-small-cell" style="{{ $cw['qty'] }}{{ $padSmall }}">
                        {{ $qty > 0 ? $qtyView : '' }}
                    </td>

                    <td class="pbj-small-cell pbj-satuan-cell" style="{{ $cw['sat'] }}{{ $padSmall }}">
                        {{ $sat }}
                    </td>

                    <td class="text-right currency-nowrap" style="{{ $cw['hs'] }}{{ $padNormal }}">
                        Rp&nbsp;{{ $fmtRp($hs) }}
                    </td>

                    <td class="text-right currency-nowrap" style="{{ $cw['ht'] }}{{ $padNormal }}">
                        Rp&nbsp;{{ $fmtRp($ht) }}
                    </td>

                    <td class="text-left fixed-wrap-anywhere" style="{{ $cw['ket'] }}{{ $padNormal }}">
                        {{ $ket }}
                    </td>

                    <td class="pbj-wo-cell" style="{{ $cw['wo'] }}{{ $padSmall }}">
                        {{ $woView }}
                    </td>
                </tr>
            @endforeach

            @if (count($items) === 0)
                <tr>
                    <td colspan="10" class="text-center" style="padding:8px;">
                        Tidak ada item.
                    </td>
                </tr>
            @endif

            @php
                $subTotal = $grand;
                $ppn = round($subTotal * ((float) $ppnRate / 100));
                $totalEstimasi = $subTotal + $ppn;
            @endphp

            <!-- SUMMARY ROW 1: CATATAN + SUB TOTAL -->
            <tr class="keep-together">
                <td colspan="6" class="summary-note-wide-top">
                    @if (!empty($pbj->notes))
                        <b>Catatan:</b><br>
                        {!! nl2br(e($pbj->notes)) !!}
                    @endif
                </td>

                <td class="summary-label-cell" style="{{ $cw['hs'] }}">
                    Sub Total
                </td>

                <td class="summary-value-cell" style="{{ $cw['ht'] }}">
                    Rp&nbsp;{{ $fmtRp($subTotal) }}
                </td>

                <td class="summary-empty-left" style="{{ $cw['ket'] }}"></td>
                <td class="summary-empty-right" style="{{ $cw['wo'] }}"></td>
            </tr>

            <!-- SUMMARY ROW 2: PPN -->
            <tr class="keep-together">
                <td colspan="6" class="summary-note-wide-middle"></td>

                <td class="summary-label-cell" style="{{ $cw['hs'] }}">
                    PPN {{ $ppnRate }}%
                </td>

                <td class="summary-value-cell" style="{{ $cw['ht'] }}">
                    Rp&nbsp;{{ $fmtRp($ppn) }}
                </td>

                <td class="summary-empty-left" style="{{ $cw['ket'] }}"></td>
                <td class="summary-empty-right" style="{{ $cw['wo'] }}"></td>
            </tr>

            <!-- SUMMARY ROW 3: TOTAL ESTIMASI -->
            <tr class="keep-together">
                <td colspan="6" class="summary-note-wide-bottom"></td>

                <td class="summary-label-cell summary-total-cell" style="{{ $cw['hs'] }}">
                    TOTAL ESTIMASI BIAYA
                </td>

                <td class="summary-value-cell summary-total-cell" style="{{ $cw['ht'] }}">
                    Rp&nbsp;{{ $fmtRp($totalEstimasi) }}
                </td>

                <td class="summary-empty-left" style="{{ $cw['ket'] }}"></td>
                <td class="summary-empty-right" style="{{ $cw['wo'] }}"></td>
            </tr>
        </tbody>
    </table>

    @php
        try {
            $tglId = \Carbon\Carbon::parse($res['pbj'][0]->tglpengajuan)
                ->locale('id')
                ->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            $tglId = date('d F Y');
        }
    @endphp

    <div class="keep-together">
        <table class="sign-table" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center" width="40%" class="sign-cell">
                    <div class="cap">Menyetujui,</div>
                    <div class="cap">Manager Repair Unit Maintenance Repair dan Overhaul</div>

                    @if (!empty($res['pbj'][0]->tglverifmanager) && $res['pbj'][0]->statusordermanager == 3)
                        <img src="data:image/png;base64, {!! $res['ttdManager'] !!}">
                    @else
                        <div class="sign-placeholder"></div>
                    @endif

                    <div class="name">WAHYU HANDOYO SUWARSO</div>
                </td>

                <td align="center" width="30%" class="sign-cell">
                    <div class="cap">Mengetahui,</div>
                    <div class="cap">Assistant Manager Non Mechanical Workshop Unit</div>
                    <div class="cap">Maintenance Repair dan Overhaul</div>

                    @if (!empty($res['pbj'][0]->tglverifasman) && $res['pbj'][0]->statusorderasman == 1)
                        <img src="data:image/png;base64, {!! $res['ttdAsman'] !!}">
                    @else
                        <div class="sign-placeholder"></div>
                    @endif

                    <div class="name">MAHZUMI</div>
                </td>

                <td align="center" width="30%" class="sign-cell">
                    <div class="cap">{{ $res['pbj'][0]->lokasipbj ?? 'Jakarta' }}, {{ $tglId }}</div>
                    <div class="cap">Pemohon,</div>
                    <div class="cap">
                        {{ $res['pbj'][0]->namajaban ?? '' }}
                    </div>

                    @if (!empty($res['ttdPenyelia']))
                        <img src="data:image/png;base64, {!! $res['ttdPenyelia'] !!}">
                    @else
                        <div class="sign-placeholder"></div>
                    @endif

                    <div class="name">{{ $res['pbj'][0]->namalengkap ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>
    @if ($shouldSplitTable)
    </div>
    @endif
</body>

</html>
