<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Lato';
            font-style: italic;
            font-weight: 700;
            src: url('{{ public_path('fonts/Lato/Lato-BoldItalic.ttf') }}') format('truetype');
        }
        @page { size: A4 landscape; margin: 0; }
        html, body { width: 297mm; height: 210mm; margin: 0; padding: 0; }
        body { font-family: 'Lato', sans-serif; }
        .page-decoration { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
        .report-header { position: absolute; top: 5.5mm; left: 12mm; width: 273mm; height: 16mm; }
        .report-header table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .report-header td { padding: 0; vertical-align: middle; }
        .header-copy {
            width: 53%;
            color: #7fa7b4;
            font-size: 6.8pt;
            font-style: italic;
            font-weight: 700;
            line-height: 1.25;
        }
        .header-pln { width: 27%; text-align: center; }
        .header-pln img { width: 42mm; height: auto; }
        .header-right { width: 20%; text-align: right; }
        .header-right-crop { display: inline-block; width: 36mm; height: 12mm; overflow: hidden; text-align: left; }
        .header-right-crop img { width: 55.4mm; height: auto; }
    </style>
</head>

<body>
    <img class="page-decoration" src="{{ public_path('img/lha-reference-page-decoration.png') }}" alt="">
    <div class="report-header">
        <table>
            <tr>
                <td class="header-copy">
                    LAPORAN HASIL AUDIT (LHA) SNI ISO/IEC 17025:2017 TAHUN {{ $res['tahun'] }}<br>
                    FMMO-163-14.4.3.b-88.6<br>
                    REV 00
                </td>
                <td class="header-pln"><img src="{{ public_path('img/lha-reference-pln.png') }}" alt="PLN Nusantara Power"></td>
                <td class="header-right">
                    <span class="header-right-crop"><img src="{{ public_path('img/lha-reference-umro-kan-pjb.png') }}" alt="Unit Laboratory dan KAN"></span>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
