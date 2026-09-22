<!DOCTYPE html>
<html lang="en">
@php
    $profile = App\Http\Controllers\Controller::static_profile();
@endphp
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
</head>
<style>
    @page {
        margin: 30px;
    }
    body{
        font-family:   Tahoma, Geneva, sans-serif; !important;
        font-size: 10pt;
        margin: 0;
    }
</style>
@stack('style')
<body>
    <table width="100%" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF" border="0">
        <tr>
            <td>
                <table width="100%" cellspacing="0">
                    <tr>
                        <td align="center" width="15%" style="vertical-align:top;">
                            <img src="img/logo-rs.png" style="width: 90px;">
                        </td>
                        <td align="center" style="vertical-align:top;">
                            <span style="font-size: 12pt;font-weight:bold;">
                                {!! strtoupper($profile->namapemerintahan) !!}
                            </span><br>
                            <span style="font-size: 14pt;font-weight:bold;">
                                {!! strtoupper($profile->namalengkap) !!}
                            </span><br>
                            <span style="font-size: 8pt;color:#000000">
                                {!! $profile->alamatlengkap !!}

                                {{ $profile->fixedphone . '/' . $profile->faksimile }}
                            </span><br>
                            <span style="font-size: 8pt; color:#000000">
                                Email : <a href="#"> {!! $profile->alamatemail !!} </a>
                                Website : <a href="#"> {!! $profile->website !!} </a>
                                Whatsapp : <a href="#"> {!! $profile->whatsapp !!} </a>
                            </span>
                        </td>
                        <td width="15%" style="vertical-align:top;">
                            {{-- <img src="img/logo-rs.png" style="width: 90px;"> --}}
                        </td>
                    </tr>
                </table>
                <hr style="border:2px solid #000;margin-bottom:0px">
                <hr style="border:0.5px solid #000;margin-top:2px">
            </td>
        </tr>
        @yield('content')
    </table>
</body>
</html>
