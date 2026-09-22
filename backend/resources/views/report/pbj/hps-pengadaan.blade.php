<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $res['hps']->nomorhps }}</title>
    <style>
        @page {
            margin-top: 110px;
            margin-bottom: 60px;
            margin-left: 35px;
            margin-right: 35px;
        }

        body {
            margin: 0;
            padding: 25px;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
        }

        .pdf-header {
            position: fixed;
            z-index: 10;
            top: -90px;
            right: 0;
            left: 0;
            width: 100%;
            height: 100px;
        }

        .page-now::before {
            content: counter(page);
        }

        .document-content {
            margin-top: -30px;
        }

        .memo {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            line-height: 1.25;
        }

        .memo td {
            padding: 2px 0;
            vertical-align: top;
        }

        .memo-label {
            width: 12%;
        }

        .memo-separator {
            width: 3%;
            text-align: center;
        }

        .introduction {
            margin: 15px 0 8px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            line-height: 1.25;
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

        .currency-nowrap {
            white-space: nowrap;
        }

        .fixed-wrap {
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .hps-table {
            width: 100%;
            max-width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
            table-layout: fixed !important;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 6.2pt;
            line-height: 1.12;
        }

        .hps-table thead {
            display: table-header-group;
        }

        .hps-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .hps-table th,
        .hps-table td {
            box-sizing: border-box;
            border: 1px solid #000;
            vertical-align: middle;
            overflow: hidden;
        }

        .hps-table th {
            padding: 4px 2px;
            background: #fff2cc;
            font-weight: 700;
            text-align: center;
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .hps-table td {
            padding: 5px 4px;
        }

        .hps-small-cell {
            padding-right: 1px !important;
            padding-left: 1px !important;
            text-align: center;
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .hps-header-small {
            padding-right: 1px !important;
            padding-left: 1px !important;
            font-size: 5.8pt;
            line-height: 1.05;
        }

        .specification {
            display: table;
            width: 100%;
        }

        .specification-number,
        .specification-copy {
            display: table-cell;
            vertical-align: top;
        }

        .specification-number {
            width: 22px;
            padding-right: 4px;
            text-align: center;
        }

        .specification-name {
            margin-bottom: 2px;
        }

        .template-spacer td {
            height: 15px;
            padding: 0;
        }

        .summary-label-cell {
            padding: 5px 4px !important;
            font-weight: 400;
            text-align: right;
            white-space: nowrap !important;
        }

        .summary-value-cell {
            padding: 5px 4px !important;
            text-align: right;
            white-space: nowrap !important;
        }

        .summary-grand td {
            font-weight: 700;
        }

        .keep-together,
        .keep-together table,
        .keep-together tr,
        .keep-together td {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .closing-table {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            line-height: 1.15;
        }

        .closing-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .notes-title {
            margin-bottom: 3px;
            text-decoration: underline;
        }

        .notes-table {
            width: 96%;
            border-collapse: collapse;
        }

        .notes-table td:first-child {
            width: 18px;
        }

        .signature {
            width: 245px;
            margin-left: auto;
            text-align: left;
        }

        .signature-space {
            height: 36px;
        }

        .signer-name {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    @php
        $logoPath = public_path('img/pln.png');
        $logoSource = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $cw = [
            'no' => 'width:2.5%;min-width:2.5%;max-width:2.5%;',
            'stock' => 'width:7.5%;min-width:7.5%;max-width:7.5%;',
            'spec' => 'width:40%;min-width:40%;max-width:40%;',
            'qty' => 'width:4.5%;min-width:4.5%;max-width:4.5%;',
            'sat' => 'width:4.5%;min-width:4.5%;max-width:4.5%;',
            'hs' => 'width:15.5%;min-width:15.5%;max-width:15.5%;',
            'ht' => 'width:15.5%;min-width:15.5%;max-width:15.5%;',
            'ket' => 'width:10%;min-width:10%;max-width:10%;',
        ];

        $padSmall = 'padding:4px 1px;';
        $padNormal = 'padding:5px 4px;';
    @endphp

    <div class="pdf-header">
        <table width="100%" cellspacing="0" cellpadding="0" border="1"
            style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;">
            <tr>
                <td style="width:140px;vertical-align:top;">
                    @if ($logoSource)
                        <img src="{{ $logoSource }}" alt="PLN Nusantara Power" style="margin:8px;height:42px;">
                    @endif
                    <div style="margin:0 0 5px 18px;font-size:8pt;font-weight:700;">UMRO</div>
                </td>

                <td style="padding:0;vertical-align:top;">
                    <div
                        style="background:#2F3A8F;color:#fff;text-align:center;font-weight:700;font-size:9pt;line-height:1.15;padding:4px 8px;letter-spacing:0.3px;">
                        PT PLN NUSANTARA POWER<br>
                        UNIT MAINTENANCE REPAIR &amp; OVERHAUL
                    </div>
                    <div
                        style="text-align:center;font-weight:700;font-size:8pt;color:#000;padding:3px 8px;border-top:1px solid #000;line-height:1.2;">
                        PERMINTAAN PENAWARAN
                    </div>
                    <table width="100%" cellspacing="0" cellpadding="0" border="0"
                        style="border-collapse:collapse;border-top:1px solid #000;font-size:6pt;line-height:1.2;font-weight:700;">
                        <tr>
                            <td style="width:10%;padding:3px 5px;white-space:nowrap;">Nomor</td>
                            <td style="width:3%;padding:3px 2px;text-align:center;">:</td>
                            <td style="width:44%;padding:3px 5px;white-space:nowrap;">{{ $res['hps']->nomorhps }}</td>
                            <td style="width:13%;padding:3px 5px;text-align:right;white-space:nowrap;">TANGGAL</td>
                            <td style="width:3%;padding:3px 2px;text-align:center;">:</td>
                            <td style="width:27%;padding:3px 5px;white-space:nowrap;">{{ $res['tanggalHps'] }}</td>
                        </tr>
                    </table>
                </td>

                <td style="width:250px;vertical-align:top;padding:0;">
                    <table width="100%" cellspacing="0" cellpadding="0" border="0"
                        style="border-collapse:collapse;font-size:6pt;line-height:1.4;">
                        <tr>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;width:48%;white-space:nowrap;">No. Dokumen</td>
                            <td style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;width:6%;text-align:center;">:</td>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;width:46%;"></td>
                        </tr>
                        <tr>
                            <td style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;">Tgl. Berlaku</td>
                            <td style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;text-align:center;">:</td>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;"></td>
                        </tr>
                        <tr>
                            <td style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;">No. Revisi</td>
                            <td style="border-right:1px solid #000;border-bottom:1px solid #000;padding:3px 6px;text-align:center;">:</td>
                            <td style="border-bottom:1px solid #000;padding:3px 6px;"></td>
                        </tr>
                        <tr>
                            <td style="border-right:1px solid #000;padding:3px 6px;">Halaman</td>
                            <td style="border-right:1px solid #000;padding:3px 6px;text-align:center;">:</td>
                            <td style="padding:3px 6px;white-space:nowrap;"><span class="page-now"></span></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="document-content">
        <table class="memo" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td class="memo-label">Nomor</td>
                <td class="memo-separator">:</td>
                <td>{{ $res['hps']->nomorhps }}</td>
            </tr>
            <tr>
                <td>Kepada</td>
                <td class="memo-separator">:</td>
                <td>SENIOR MANAGER</td>
            </tr>
            <tr>
                <td>Dari</td>
                <td class="memo-separator">:</td>
                <td>FUNGSI PELAKSANA PENGADAAN</td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td class="memo-separator">:</td>
                <td>HARGA PERKIRAAN SENDIRI (HPS)</td>
            </tr>
            <tr>
                <td>Dasar Usulan</td>
                <td class="memo-separator">:</td>
                <td>{{ $res['hps']->nosuratpbj ?: '-' }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td>{{ $res['hps']->judulpermintaan ?: '-' }}</td>
            </tr>
        </table>

        <div class="introduction">
            Dengan ini kami sampaikan usulan Harga Perkiraan Sendiri (HPS) untuk
            {{ $res['hps']->judulpermintaan ?: '-' }}:
        </div>

        <table class="hps-table" cellspacing="0" cellpadding="0" border="0">
            <colgroup>
                <col style="{{ $cw['no'] }}">
                <col style="{{ $cw['stock'] }}">
                <col style="{{ $cw['spec'] }}">
                <col style="{{ $cw['qty'] }}">
                <col style="{{ $cw['sat'] }}">
                <col style="{{ $cw['hs'] }}">
                <col style="{{ $cw['ht'] }}">
                <col style="{{ $cw['ket'] }}">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" class="hps-header-small" style="{{ $cw['no'] }}{{ $padSmall }}">NO</th>
                    <th rowspan="2" style="{{ $cw['stock'] }}{{ $padNormal }}">STOCK<br>CODE</th>
                    <th rowspan="2" style="{{ $cw['spec'] }}{{ $padNormal }}">SPESIFIKASI</th>
                    <th rowspan="2" class="hps-header-small" style="{{ $cw['qty'] }}{{ $padSmall }}">QTY</th>
                    <th rowspan="2" class="hps-header-small" style="{{ $cw['sat'] }}{{ $padSmall }}">SAT</th>
                    <th colspan="2" style="width:31%;min-width:31%;max-width:31%;padding:4px 2px;">HARGA (Rp)</th>
                    <th rowspan="2" style="{{ $cw['ket'] }}{{ $padNormal }}">KETERANGAN</th>
                </tr>
                <tr>
                    <th style="{{ $cw['hs'] }}{{ $padNormal }}">Harga Satuan</th>
                    <th style="{{ $cw['ht'] }}{{ $padNormal }}">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                <tr class="template-spacer">
                    <td style="{{ $cw['no'] }}"></td>
                    <td style="{{ $cw['stock'] }}"></td>
                    <td style="{{ $cw['spec'] }}"></td>
                    <td style="{{ $cw['qty'] }}"></td>
                    <td style="{{ $cw['sat'] }}"></td>
                    <td style="{{ $cw['hs'] }}"></td>
                    <td style="{{ $cw['ht'] }}"></td>
                    <td style="{{ $cw['ket'] }}"></td>
                </tr>

                @forelse ($res['details'] as $index => $detail)
                    <tr>
                        <td class="hps-small-cell" style="{{ $cw['no'] }}{{ $padSmall }}"></td>
                        <td class="hps-small-cell" style="{{ $cw['stock'] }}{{ $padSmall }}">{{ $detail->stockcode ?: '' }}</td>
                        <td class="text-left fixed-wrap" style="{{ $cw['spec'] }}{{ $padNormal }}">
                            <div class="specification">
                                <div class="specification-number">{{ $index + 1 }}</div>
                                <div class="specification-copy">
                                    <div class="specification-name">{{ $detail->namaitem ?: '-' }}</div>
                                    @if (!empty($detail->uraianitem))
                                        <div>{!! nl2br(e($detail->uraianitem)) !!}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="hps-small-cell" style="{{ $cw['qty'] }}{{ $padSmall }}">
                            {{ number_format((float) ($detail->banyak ?? 0), 0, ',', '.') }}
                        </td>
                        <td class="hps-small-cell currency-nowrap" style="{{ $cw['sat'] }}{{ $padSmall }}">
                            {{ strtoupper($detail->satuan ?: '-') }}
                        </td>
                        <td class="text-right currency-nowrap" style="{{ $cw['hs'] }}{{ $padNormal }}">
                            {{ number_format($detail->hargaangka, 0, ',', '.') }}
                        </td>
                        <td class="text-right currency-nowrap" style="{{ $cw['ht'] }}{{ $padNormal }}">
                            {{ number_format($detail->totalharga, 0, ',', '.') }}
                        </td>
                        <td class="text-left fixed-wrap" style="{{ $cw['ket'] }}{{ $padNormal }}">
                            {{ $detail->keterangan ?: ($index === 0 ? 'HPS disusun berdasar RAB' : '') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding:8px;">Tidak ada item PBJ.</td>
                    </tr>
                @endforelse

                <tr>
                    <td colspan="6" class="summary-label-cell">Total Harga</td>
                    <td class="summary-value-cell" style="{{ $cw['ht'] }}">{{ number_format($res['subtotal'], 0, ',', '.') }}</td>
                    <td style="{{ $cw['ket'] }}"></td>
                </tr>
                <tr>
                    <td colspan="6" class="summary-label-cell">DPP Nilai Lain</td>
                    <td class="summary-value-cell" style="{{ $cw['ht'] }}">{{ number_format($res['dppNilaiLain'], 0, ',', '.') }}</td>
                    <td style="{{ $cw['ket'] }}"></td>
                </tr>
                <tr>
                    <td colspan="6" class="summary-label-cell">PPN</td>
                    <td class="summary-value-cell" style="{{ $cw['ht'] }}">{{ number_format($res['nilaiPpn'], 0, ',', '.') }}</td>
                    <td style="{{ $cw['ket'] }}"></td>
                </tr>
                <tr class="summary-grand">
                    <td colspan="6" class="summary-label-cell">GRAND TOTAL</td>
                    <td class="summary-value-cell" style="{{ $cw['ht'] }}">{{ number_format($res['grandTotal'], 0, ',', '.') }}</td>
                    <td style="{{ $cw['ket'] }}"></td>
                </tr>
            </tbody>
        </table>

        <div class="keep-together">
            <table class="closing-table" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="width:64%;">
                        <div class="notes-title">KETERANGAN</div>
                        <table class="notes-table" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td>1.</td>
                                <td>PPN yang berlaku sebesar {{ number_format($res['ppnPersen'], 0) }}%</td>
                            </tr>
                            <tr>
                                <td>2.</td>
                                <td>Dasar Perhitungan PPN : 11/12 x Harga Barang/Jasa x {{ number_format($res['ppnPersen'], 0) }}%</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:36%;">
                        <div class="signature">
                            <div>Ditetapkan di</div>
                            <div>Jakarta, {{ $res['tanggalHps'] }}</div>
                            <div style="margin-top:6px;">Senior Manager</div>
                            <div>Jasa Inspeksi, Repair &amp; Exise</div>
                            <div>PT. PLN Nusantara Power UMRO</div>
                            <div class="signature-space"></div>
                            <div class="signer-name">WAN VARIANI PERMATASARI</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
