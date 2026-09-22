<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Label Order</title>

    <style>
        /* ==============================
           KERTAS / LABEL: 40 x 30 mm
           ============================== */
        @page {
            size: 40mm 30mm;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            -webkit-print-color-adjust: exact;
        }

        /* 1 label = 1 page */
        .label-page {
            width: 40mm;
            height: 30mm;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
        }

        /* Area dalam dengan "tepi" */
        .label-inner {
            position: absolute;
            top: 2mm;
            right: 2mm;
            bottom: 2mm;
            left: 2mm;

            /* Vertical center yang kompatibel Dompdf */
            display: table;
            width: 100%;
            height: 100%;
        }

        .vcenter {
            display: table-cell;
            vertical-align: middle;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 5pt;
            line-height: 1.12;
        }

        td {
            vertical-align: top;
            padding: 0.2mm 0;
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 5.6pt;
            border-bottom: 0.25mm solid #000;
            padding-bottom: 0.6mm;
            line-height: 1.15;
        }

        .doc-code {
            display: block;
            font-size: 4pt;
            font-weight: normal;
            margin-top: 0.3mm;
        }

        .label {
            width: 12mm;
            font-weight: bold;
            white-space: nowrap;
        }

        .value {
            white-space: normal;
            word-wrap: break-word;
        }

        /* Logo */
        .qr {
            position: absolute;
            right: 2mm;
            bottom: 2mm;
            width: 8mm;
            height: 8mm;
            opacity: 0.95;
        }

        .qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Break antar label */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @foreach ($res['alat'] as $item)
        <div class="label-page @if (!$loop->last) page-break @endif">

            <div class="qr">
                <img src="{{ public_path('img/UMRO.png') }}" alt="Logo">
            </div>

            <div class="label-inner">
                <div class="vcenter">

                    <table>
                        <tr>
                            <td colspan="2" class="header">
                                LAB KALIBRASI (LK-284-IDN)<br>
                                @if ($item->lokasi_insitu == '1')
                                    PT PLN NP UMRO JKT
                                @else
                                    PT PLN NP UMRO GRK
                                @endif
                                <span class="doc-code">FMMO-163-14.4.3.b-74.3</span>
                            </td>
                        </tr>

                        <tr>
                            <td class="label">No Order</td>
                            <td class="value">: {{ $item->noorderalat }}</td>
                        </tr>

                        <tr>
                            <td class="label">Tgl. Terima</td>
                            <td class="value">: {{ \Carbon\Carbon::parse($item->tglkajiulang)->format('d M Y') }}</td>
                        </tr>

                        @if ($item->jenisorder == 'repair')
                            <tr>
                                <td class="label">Asal Unit</td>
                                <td class="value">: {{ $item->namaperusahaan }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td class="label">Nama Alat</td>
                            <td class="value">: {{ $item->namaproduk }}</td>
                        </tr>

                        <tr>
                            <td class="label">Merk/Tipe</td>
                            <!-- padding-right agar tidak ketabrak logo -->
                            <td class="value" style="padding-right: 9mm;">: {{ $item->namamerk }}/{{ $item->namatipe }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">SN</td>
                            <td class="value" style="padding-right: 9mm;">: {{ $item->namaserialnumber }}</td>
                        </tr>
                    </table>

                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
