<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="css/style.css">
    <title>Curriculum Vitae Personel</title>
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

    .footer {
        position: fixed;
        bottom: -55px;
        left: 0;
        right: 0;
        height: 55px;
    }

    .page-break {
        page-break-before: always;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-fixed {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .table-fixed th,
    .table-fixed td {
        border: 1px solid #000;
        padding: 5px 6px;
        vertical-align: top;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .small {
        font-size: 9pt;
    }

    .xsmall {
        font-size: 8pt;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .section-title {
        margin-top: 14px;
        margin-bottom: 8px;
        font-size: 11pt;
        font-weight: 700;
    }

    .bio-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    .bio-table td {
        padding: 3px 4px;
        vertical-align: top;
        font-size: 10pt;
    }

    .avoid-break {
        page-break-inside: avoid;
    }

    .signature-wrap {
        width: 100%;
        margin-top: 18px;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .signature-box {
        width: 260px;
        margin-left: auto;
        text-align: center;
    }

    .w-periode {
        width: 22%;
    }

    .w-instansi {
        width: 35%;
    }

    .w-jurusan {
        width: 20%;
    }

    .w-jenjang {
        width: 23%;
    }

    .w-tahun {
        width: 14%;
    }

    .w-lembaga {
        width: 32%;
    }

    .w-ket {
        width: 54%;
    }

    .w-periode-kerja {
        width: 27%;
    }

    .w-instansi-kerja {
        width: 38%;
    }

    .w-posisi {
        width: 35%;
    }
</style>

<body style="padding:25px">
    <script type="text/php">
        if (isset($pdf)) {
            $x = 460;
            $y = 65;
            $text = "Halaman : {PAGE_NUM} dari {PAGE_COUNT}";
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "bold");
            $size = 9;
            $color = array(0,0,0);
            $word_space = 0.0;
            $char_space = 0.0;
            $angle = 0.0;
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>

    <div class="pdf-header">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <thead style="display: none"></thead>
            <tbody>
                <tr>
                    <td rowspan="5" style="width: 90px;">
                        <img src="img/pln.png" width="100px" border="0" style="margin-top:-20px">
                    </td>
                    <td class="text-center">
                        <span style="font-size: 13pt;font-weight: 600;color:#000000">
                            <b>LABORATORIUM KALIBRASI PT PLN NP UMRO</b>
                        </span>
                    </td>
                    <td rowspan="5" style="width: 90px;">
                        <img src="img/UMRO.png" width="80px" border="0" style="margin-top:-20px">
                    </td>
                </tr>

                <tr>
                    <td class="text-center">
                        <span style="font-size: 10pt;font-weight: 600;color:#000000">
                            <b>FORMULIR</b>
                        </span>
                    </td>
                </tr>

                <tr>
                    <td class="text-center">
                        <span style="font-size: 10pt;font-weight: 600;color:#000000">
                            <b>CURRICULUM VITAE PERSONEL</b>
                        </span>
                    </td>
                </tr>

                <tr>
                    <td>
                        <table width="80%" cellspacing="0" cellpadding="0" border="0"
                            style="border-collapse: collapse; margin-left: 70px">
                            <tbody>
                                <tr>
                                    <td width="35%"
                                        style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                        FMMO-163-14.4.3.b-62.2
                                    </td>
                                    <td width="15%"
                                        style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                        Revisi : 00
                                    </td>
                                    <td width="30%"
                                        style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                        Tanggal : 30-01-2025
                                    </td>
                                    <td width="20%"
                                        style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;text-align:center;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <hr class="baris1" style="margin-top:1px">
        <hr class="baris1" style="margin-top:-5px">
    </div>

    <div class="footer">
        <hr style="border: 0; border-top: 1px solid #000; margin-bottom: 6px;">
        <table class="table">
            <tr>
                <td class="xsmall">
                    Dicetak: {{ \Carbon\Carbon::now()->format('d-m-Y H:i') }}
                </td>
                <td class="text-center" style="font-size:9pt;color:#000;font-weight:600;padding:2px 4px;">
                </td>
                <td class="xsmall text-right">
                    {{ $profile->website ?? 'https://ulabumro.id/' }}
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top:-16px;">
        <div class="section-title">I. Data Pribadi</div>
        <table class="bio-table">
            <tr>
                <td width="38%">1. Nama</td>
                <td width="3%">:</td>
                <td>{{ $res['cv']->nama ?? '' }}</td>
            </tr>
            <tr>
                <td>2. Tempat dan Tanggal Lahir</td>
                <td>:</td>
                <td>
                    {{ $res['cv']->tempatlahir ?? '' }}{{ !empty($res['cv']->tempatlahir) && !empty($res['cv']->tanggallahir) ? ', ' : '' }}
                    {{ !empty($res['cv']->tanggallahir) ? \Carbon\Carbon::parse($res['cv']->tanggallahir)->isoFormat('D MMMM Y') : '' }}
                </td>
            </tr>
            <tr>
                <td>3. Jenis Kelamin</td>
                <td>:</td>
                <td>{{ $res['cv']->jeniskelamin ?? '' }}</td>
            </tr>
            <tr>
                <td>4. Agama</td>
                <td>:</td>
                <td>{{ $res['cv']->agama ?? '' }}</td>
            </tr>
            <tr>
                <td>5. Status Pernikahan</td>
                <td>:</td>
                <td>{{ $res['cv']->statuspernikahan ?? '' }}</td>
            </tr>
            <tr>
                <td>6. Warga Negara</td>
                <td>:</td>
                <td>{{ $res['cv']->warganegara ?? '' }}</td>
            </tr>
            <tr>
                <td>7. Penguasaan Bahasa</td>
                <td>:</td>
                <td>{{ $res['cv']->penguasaanbahasa ?? '' }}</td>
            </tr>
            <tr>
                <td>8. Alamat KTP</td>
                <td>:</td>
                <td>{{ $res['cv']->alamatktp ?? '' }}</td>
            </tr>
            <tr>
                <td>9. Alamat Sekarang</td>
                <td>:</td>
                <td>{{ $res['cv']->alamatsekarang ?? '' }}</td>
            </tr>
            <tr>
                <td>10. Nomor Telepon / HP</td>
                <td>:</td>
                <td>{{ $res['cv']->nohp ?? '' }}</td>
            </tr>
            <tr>
                <td>11. E-mail</td>
                <td>:</td>
                <td>{{ $res['cv']->email ?? '' }}</td>
            </tr>
            <tr>
                <td>12. Akun Media Sosial</td>
                <td>:</td>
                <td>{{ $res['cv']->akunmediasosial ?? '' }}</td>
            </tr>
            <tr>
                <td>13. Jabatan Saat Ini</td>
                <td>:</td>
                <td>{{ $res['cv']->jabatansaatini ?? '' }}</td>
            </tr>
        </table>

        <div class="section-title">II. Pendidikan Formal</div>
        <table class="table-fixed avoid-break">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th class="w-periode">Periode</th>
                    <th class="w-instansi">Sekolah/Institusi/Universitas</th>
                    <th class="w-jurusan">Jurusan</th>
                    <th class="w-jenjang">Jenjang Pendidikan</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($res['cv']->pendidikanformal ?? []) as $row)
                    <tr>
                        <td class="text-center">{{ $row->tahunMulai ?? '' }} - {{ $row->tahunSelesai ?? '' }}</td>
                        <td>{{ $row->institusi ?? '' }}</td>
                        <td>{{ $row->jurusan ?? '' }}</td>
                        <td>{{ $row->jenjang ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">III. Pendidikan Non Formal / Training – Seminar (5 Tahun terakhir)</div>
        <table class="table-fixed">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th class="w-tahun">Tahun</th>
                    <th class="w-lembaga">Lembaga / Instansi</th>
                    <th class="w-ket">Keterampilan</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($res['pelatihan'] ?? []) as $row)
                    <tr>
                        <td class="text-center">{{ $row->tahun ?? '' }}</td>
                        <td>{{ $row->lembaga ?? '' }}</td>
                        <td>{{ $row->keterampilan ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Belum ada data pelatihan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">IV. Prestasi / Penghargaan (5 Tahun Terakhir)</div>
        <table class="table-fixed avoid-break">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th class="w-tahun">Tahun</th>
                    <th class="w-lembaga">Lembaga / Instansi</th>
                    <th class="w-ket">Pencapaian</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($res['cv']->prestasi ?? []) as $row)
                    <tr>
                        <td class="text-center">{{ $row->tahun ?? '' }}</td>
                        <td>{{ $row->lembaga ?? '' }}</td>
                        <td>{{ $row->pencapaian ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">V. Riwayat Pengalaman Kerja</div>
        <table class="table-fixed avoid-break">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th class="w-periode-kerja">Periode</th>
                    <th class="w-instansi-kerja">Instansi / Perusahaan</th>
                    <th class="w-posisi">Posisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($res['cv']->pengalamankerja ?? []) as $row)
                    <tr>
                        <td class="text-center">{{ $row->periodeMulai ?? '' }} - {{ $row->periodeSelesai ?? '' }}</td>
                        <td>{{ $row->instansi ?? '' }}</td>
                        <td>{{ $row->posisi ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="signature-wrap">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tbody style="font-size: 10pt">
                    <tr>
                        <td width="50%" style="vertical-align: top; text-align: left; padding-top: 10px;">
                            <span class="small">Demikian CV ini saya buat dengan sebenarnya.</span>
                        </td>
                        <td width="50%" class="text-center">
                            <span class="small">
                                {{ $res['cv']->kotattd ?? '' }}{{ !empty($res['cv']->kotattd) && !empty($res['cv']->tanggalttd) ? ', ' : '' }}
                                {{ !empty($res['cv']->tanggalttd) ? \Carbon\Carbon::parse($res['cv']->tanggalttd)->isoFormat('D MMMM Y') : '' }}
                            </span>
                            <br><br>
                            <span class="small">Yang Membuat</span>
                            <br><br>
                            <img src="data:image/png;base64, {!! $res['ttdPegawai'] !!}"
                                style="margin-top: 5px; margin-bottom: 5px; max-width: 120px; height: auto;">
                            <br>
                            <span class="small">( {{ $res['cv']->namattd ?? ($res['cv']->nama ?? '') }} )</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px; text-align:center; font-size:9pt;">
            Informasi pada dokumen ini adalah milik PT PLN NP UMRO<br>
            Tidak boleh digunakan untuk komersialisasi tanpa persetujuan PT PLN NP UMRO
        </div>
    </div>
</body>

</html>
