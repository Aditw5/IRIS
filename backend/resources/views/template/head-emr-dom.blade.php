<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
</head>
<style>
    @page {
        margin: 10px;
    }
    body{
        font-family:   Tahoma, Geneva, sans-serif; !important;
        font-size: 10pt;
        margin: 0;
    }
</style>
@stack('style')
<body>
    <section>

        <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td width="60%"></td>
                <td width="60%" style="text-align:right;font-size:9pt">@yield('koderme')</td>
            </tr>
        </table>

        <table width="100%" cellspacing="0" cellpadding="4" border="0">
            <tr>
                <td width="10%" style="vertical-align:top;">
                    <img src="img/logo-rs.png" style="width: 90px;">
                </td>
                <td style="text-align: left;vertical-align:top;">
                    <b>
                        <span style="font-size: 14pt">{{ $profile->namalengkap }}
                        </span><br>
                    </b>
                    <span style="font-size: 9pt;">{{ $profile->alamatlengkap }}</span>
                </td>
                <td width="50%" style="vertical-align:top;border: 2px solid #000;">
                    <div class="box" style="text-align: left">
                        <table style="padding: 2px;">
                            <tr>
                                <td style="width: 100px">No. RM</td>
                                <td>:</td>
                                <td><b>{{ $pasien['nocm'] }}</b></td>
                            </tr>
                            <tr>
                                <td>Nama</td>
                                <td>:</td>
                                <td><b>{{ $pasien['namapasien'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td>Jenis Kelamin</td>
                                <td>:</td>
                                <td>
                                    <b>{{ $pasien['jeniskelamin'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td>Tgl Lahir</td>
                                <td>:</td>
                                <td>
                                    <b>{{ $pasien['tgllahir'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td>Ruangan</td>
                                <td>:</td>
                                <td><b>{{ $pasien['namaruangan'] }}</b>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
        <hr style="border:2px solid #000;margin-bottom:0px">
        <hr style="border:0.5px solid #000;margin-top:2px">
        <table width="100%">
            <tr>
                <td>
                    <table style="text-align: center" width="100%">
                        <tr style="text-align: center">
                            <th style="font-size: 14pt;text-align: center !important">@yield('about')
                            </th>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <hr style="border:0.5px solid #000;margin-top:2px">

        @yield('content')


    </section>
</body>

</html>
