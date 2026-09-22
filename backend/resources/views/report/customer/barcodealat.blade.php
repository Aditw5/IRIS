<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Label Alat</title>
    <style>
        @page {
            size: 158.74pt 51.02pt;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            position: relative;
            width: 158.74pt;
            height: 51.02pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 4pt;
            line-height: 1.1;
            table-layout: fixed;
        }

        td {
            padding: 0;
            vertical-align: top;
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 4pt;
            padding-top: 1pt;
            padding-bottom: 0.5pt;
        }

        .label {
            padding-left: 2pt;
            white-space: nowrap;
            width: 38pt;
        }

        .value {
            width: 58pt;
            word-break: break-word;
            white-space: normal;
        }

        .qr {
            position: absolute;
            right: 10pt;
            bottom: 10pt;
            width: 25pt;
            height: 25pt;
        }
    </style>
</head>

<body>
    @php $item = $res['alat'][0]; @endphp
    <table>
        <tr>
            <td colspan="3" class="header">
                LABORATORIUM KALIBRASI (LK-284-IDN)<br>
                PT PLN NP UMRO JKT
            </td>
        </tr>
        <tr>
            <td class="label">Serial Number</td>
            <td class="value">: {{ $item->namaserialnumber }}</td>
        </tr>
    </table>
    <div class="qr" style="margin-right: 20pt; margin-bottom: 10pt">
        <img src="data:image/png;base64,{{ base64_encode(
            QrCode::format('svg')->size(120)->margin(0)->generate(url('https://ulabumro.id/module/customer/detail-alat?id_alat=' . $item->idalat)),
        ) }}"
            style="width: 40pt; height: 40pt; display: block; margin-right: 20pt;" alt="QR Code" />
        {{-- <img src="data:image/png;base64, {!! $res['barcodeAlat'] !!}" style="margin-top: 5px; margin-bottom: 5px"> --}}
    </div>
</body>

</html>
