<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    @if ((isset($res['pdf']) && $res['pdf']) || (isset($res['storage']) && $res['storage']))
        <link rel="stylesheet" href="css/report/paper.css">
        <link rel="stylesheet" href="css/report/table.css">
        <link rel="stylesheet" href="css/report/tabel.css">
    @else
        <link rel="stylesheet" href="{{ asset('css/report/paper.css') }} ">
        <link rel="stylesheet" href="{{ asset('css/report/table.css') }}">
        <link rel="stylesheet" href="{{ asset('css/report/tabel.css') }}">
    @endif

    <style>
        @media print {
            td.merah {
                background-color: #d54242 !important;
                -webkit-print-color-adjust: exact;
            }

            td.kuning {
                background-color: #c5d542 !important;
                -webkit-print-color-adjust: exact;
            }

            td.hijau {
                background-color: #42d55b !important;
                -webkit-print-color-adjust: exact;
            }

            td.hitam {
                background-color: #000000 !important;
                -webkit-print-color-adjust: exact;
            }
        }

        @page {
            size: A4;
        }

        /*@media print {*/
        /*    body {margin:0}*/
        /*}*/
        .double-border {

            border: 4px solid #000;

        }

        .double-border:before {

            border: 4px solid #fff;

        }

        .box {
            border: 2px solid black;
            /*border-radius: 6px;*/
        }

        .garis6 td {
            padding: 3px;
        }

        .bold {
            font-weight: bold;
        }

        .f-s-15 {
            font-size: 12px;
        }

        .top-height {
            height: 50px;
            vertical-align: text-top;
            width: 15%;
        }

        .text-top {
            vertical-align: text-top;
        }

        table {
            width: 100%;
            height: 100%;
        }

        .kotak {
            width: 50px;
            height: 20px;
        }

        .merah {
            background-color: #d54242 !important;
        }

        .kuning {
            background-color: #c5d542 !important;
        }

        .hijau {
            background-color: #42d55b !important;
        }

        .hitam {
            background-color: #000000 !important;
        }

        .bmerah {
            border: thin solid #d54242;
        }

        .bkuning {
            border: thin solid #c5d542;
        }

        .bhijau {
            border: thin solid #42d55b;
        }

        .bhitam {
            border: thin solid #000000;
        }

        .border-lr {
            border-collapse: collapse;
        }

        .border-lr td {
            border: thin solid #000;
        }

        .border-doang {
            border-collapse: collapse;
            border: thin solid #000;
            border-top: none;
        }

        .border-doang td {
            padding: 5px;
        }

    </style>

    @stack('style')

</head>

<body class="A4" style="font-family:Tahoma;height: auto" onload="window.print()">
    <section class="sheet padding-10mm" style="font-family:Tahoma;height: auto;overflow: hidden;">
        <table style="border: 1px solid black;width: auto;padding: 2px 17px;">
            <thead>
                <tr>
                    <td><span style="font-weight:bold;font-size:10pt">SALINAN</span></td>
                </tr>
            </thead>
        </table>
        {{-- <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td width="60%"></td>
                <td width="60%" style="text-align:right">002.1/FORM/7/RMIK/2022/Rev. 00</td>
            </tr>
        </table> --}}

        {{-- <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td width="10%" style="padding: 5px">
                    @if ((isset($res['pdf']) && $res['pdf']) || (isset($res['storage']) && $res['storage']))
                        <img src="img/logo-rs.png" style="width: 90px;">
                    @else
                        <img src="{{ asset('img/logo-rs.png') }}" style="width: 90px;">
                    @endif
                </td>
                <td style="text-align: left">
                    <b>
                        <span style="font-size: 16px">{{ $profile->namalengkap }}
                        </span><br>
                        <br>
                        <span style="font-size: 13px;">{{ $profile->alamatlengkap }}</span>
                    </b>
                </td>
                <td width="50%" style="padding: 10px">
                    <div class="box" style="text-align: left">
                        <table style="padding: 3px;">
                            <tr>
                                <td class="f-s-15 bold  text-top" style="width: 100px">No. RM</td>
                                <td class="f-s-15 bold  text-top">:</td>
                                <td class="f-s-15 bold text-top"><b>{{ $pasien['nocm'] }}</b></td>
                            </tr>
                            <tr>
                                <td class="f-s-15 bold  text-top">Nama</td>
                                <td class="f-s-15 bold  text-top">:</td>
                                <td class="f-s-15 bold  text-top"><b>{{ $pasien['namapasien'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td class="f-s-15 bold  text-top">Jenis Kelamin</td>
                                <td class="f-s-15 bold  text-top">:</td>
                                <td class="f-s-15 bold  text-top">
                                    <b>{{ $pasien['jeniskelamin'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td class="f-s-15 bold  text-top">Tgl Lahir</td>
                                <td class="f-s-15 bold  text-top">:</td>
                                <td class="f-s-15 bold  text-top">
                                    <b>{{ $pasien['tgllahir'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td class="f-s-15 bold  text-top">Ruangan</td>
                                <td class="f-s-15 bold  text-top">:</td>
                                <td class="f-s-15 bold  text-top"><b>{{ $pasien['namaruangan'] }}</b>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table> --}}
        {{-- <hr style="border:2px solid #000;margin-bottom:0px">
        <hr style="border:0.5px solid #000;margin-top:2px">
        <table>
            <tr>
                <td>
                    <table style="text-align: center">
                        <tr style="text-align: center">
                            <th style="font-size: 20px;text-align: center">@yield('about')
                            </th>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <hr style="border:0.5px solid #000;margin-top:2px"> --}}

        @yield('content')


    </section>
</body>

</html>
<script type="text/javascript">
    $(document).ready(function() {
        window.print();
    });
</script>
