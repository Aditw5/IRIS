<!DOCTYPE html>
<html>
@php
    $profile = App\Http\Controllers\Controller::static_profile();
@endphp
<head>
    <title>@yield('title')</title>
    <meta name="robots" content="noindex">
    <meta name="googlebot" content="noindex">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    @if ((isset($res['pdf']) && $res['pdf']) || (isset($res['storage']) && $res['storage']))
    @else
        {{-- <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Montserrat:ital@0;1&family=Mukta&family=Poppins:wght@400&display=swap"
            rel="stylesheet"> --}}
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>

    <style>
        /* @font-face {
            font-family:'Poppins', sans-serif;
            src: url('fonts/Poppins-Bold.ttf');
        } */
        :root {
            /* font-family: 'Montserrat', sans-serif;
            font-family: 'Poppins', sans-serif; */
            /* --font:Tahoma,Verdana,Segoe,sans-serif;  */
            --font: Arial, Helvetica, sans-serif;
        }
    </style>

    @if ((isset($res['pdf']) && $res['pdf']) || (isset($res['storage']) && $res['storage']))
        <link rel="stylesheet" href="css/paper.css ">
        <link rel="stylesheet" href="css/table-v2.css">
        {{-- <link rel="stylesheet" href="css/tabel.css"> --}}
        <link rel="stylesheet" href="css/style.css">
        <style>
            body,
            td,
            th,
            span,
            p {
                font-family: Arial, Helvetica, sans-serif !important;
            }
        </style>
    @else
        {{-- @if (stripos(\Request::url(), 'localhost') !== false || stripos(\Request::url(), '127.0.0.1') !== false) --}}
        <link rel="stylesheet" href="{{ asset('css/paper.css') }} ">
        <link rel="stylesheet" href="{{ asset('css/table-v2.css') }}">
        {{-- <link rel="stylesheet" href="{{ asset('css/tabel.css') }}"> --}}
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
        {{-- @else
            <link rel="stylesheet" href="{{ asset('service/css/paper.css') }} ">
            <link rel="stylesheet" href="{{ asset('service/css/table-v2.css') }}">
            <link rel="stylesheet" href="{{ asset('service/css/tabel.css') }}">
            <link rel="stylesheet" href="{{ asset('service/css/style.css') }}">
        @endif --}}
    @endif


</head>
<style type="text/css" media="print">
    @media print {
        @page {
            size: 215 mm 140 mm landscape;
            margin: 0;
            /* size: portrait; */
        }

        footer {
            display: none
        }

        header {
            display: none
        }


    }
</style>
<style>
    body,
    td,
    th,
    span,
    p {
        font-family:   Tahoma, Geneva, sans-serif; !important;
        font-size: 11px;
    }

    tr td {
        padding: 2px 4px 2px 4px;
    }

    .borderss {
        border-bottom: 1px solid black;
    }

    .baris1 {
        border: 2px solid #000000;
    }

    .baris2 {
        border: 1px solid #000000;
    }

    .garishalus {
        border: 0.01em solid #9a9a9a;
    }

    .garishalus tr td {
        border: 0.01em solid #9a9a9a;
        /* border: thin solid #9a9a9a; */
    }



    .garis6 td {
        padding: 3px !important;
    }
</style>

@if ((isset($res['pdf']) && $res['pdf']) || (isset($res['storage']) && $res['storage']))

    <body style="margin: 0">
    @else
        @if (isset($print) && $print == true)

            <body style="background-color: #CCCCCC;margin: 0" onLoad="window.print()">
            @else

                <body style="background-color: #CCCCCC;margin: 0">
        @endif
@endif

<body>
    @yield('page-style')
    <div align="center">
        @if ((isset($res['pdf']) && $res['pdf']) || (isset($res['storage']) && $res['storage']))
            <table cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" border="0" style="padding:25px">
            @else
                <table cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" border="1" style="padding:25px;"
                    width="{{ $pageWidth }}">
                    <tr style="border: none">
                        <td align="right" style="border: none">{{$noDokumen}}</td>
                    </tr>
        @endif
        <tbody>
            <tr>
                <td>
                    <table width="100%" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td rowspan="5">
                                <p class="text-right">
                                    @if ((isset($res['pdf']) && $res['pdf']) || (isset($res['storage']) && $res['storage']))
                                        <img src="{{ 'img/logo-kota.png' }}" width="80px" border="0">
                                    @else
                                        {{-- @if (stripos(\Request::url(), 'localhost') !== false || stripos(\Request::url(), '127.0.0.1') !== false) --}}
                                        <img src="{{ asset('img/logo-kota.png') }}" width="80px" border="0">
                                        {{-- @else
                                            <img src="{{ asset('service/img/logo-rs.png') }}" width="80px" border="0">
                                        @endif --}}
                                    @endif
                                </p>
                            </td>
                            <td class="text-center" style="text-align: center">
                                <span style="font-size: 14pt;font-weight: 600;letter-spacing:5px;color:#000000">
                                    {!! strtoupper($profile->namapemerintahan) !!}
                                </span>
                            </td>
                            <td rowspan="5">
                                <div style="width: 80px;">
                            </td>
                        </tr>
                        {{-- <tr>
                            <td class="text-center">
                                <span style="font-size: 14pt;font-weight: 600;color:#000000">PEMERINTAH KOTA BANDUNG
                                </span>
                            </td>
                        </tr> --}}
                        <tr>
                            <td class="text-center" style="text-align: center">
                                <span style="font-size: 14pt;font-weight: 600;letter-spacing: 5px;color:#000000">
                                    {!! strtoupper("DINAS kesehatan") !!}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" style="text-align: center">
                                <span style="font-size: 16pt;font-weight: 600;letter-spacing: 0px;color:#000000">
                                    {!! strtoupper($profile->namalengkap) !!}

                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" style="text-align: center">
                                <span style="font-size: 12pt;color:#000000">
                                    {!! $profile->alamatlengkap !!}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" style="text-align: center">
                                <span style="font-size: 12pt; color:#000000">
                                    Telepon. {{ $profile->fixedphone . '/' . $profile->faksimile }}
                                    Email : {!! $profile->alamatemail !!}
                                </span>
                            </td>
                        </tr>
                    </table>
                    {{-- <hr class="baris1">
                    <hr class="baris2"> --}}
                </td>
            </tr>
            <tr>
                @yield('content')
            </tr>

        </tbody>
        </table>
    </div>
</body>

</html>
