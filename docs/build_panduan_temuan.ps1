$ErrorActionPreference = 'Stop'

$workspace = 'C:\ulab-kalibrasi'
$docsDir = Join-Path $workspace 'docs'
$assetDir = Join-Path $docsDir 'panduan-temuan-assets'
$outputDocx = Join-Path $docsDir 'Panduan_Penggunaan_Fitur_Temuan_Ketidaksesuaian.docx'
$outputPdf = Join-Path $docsDir 'Panduan_Penggunaan_Fitur_Temuan_Ketidaksesuaian_QA.pdf'
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'

New-Item -ItemType Directory -Force -Path $docsDir, $assetDir | Out-Null
Remove-Item -LiteralPath $outputDocx, $outputPdf -Force -ErrorAction SilentlyContinue

function Write-Utf8File([string]$Path, [string]$Content) {
    [System.IO.File]::WriteAllText($Path, $Content, [System.Text.UTF8Encoding]::new($false))
}

function New-SvgAsset([string]$Name, [string]$Svg) {
    $svgPath = Join-Path $assetDir ($Name + '.svg')
    $pngPath = Join-Path $assetDir ($Name + '.png')
    Write-Utf8File $svgPath $Svg
    if (Test-Path -LiteralPath $pngPath) { return $pngPath }
    $uri = 'file:///' + ($svgPath -replace '\\','/')
    $chromeArgs = @('--headless=new','--disable-gpu','--hide-scrollbars','--force-device-scale-factor=1','--window-size=1400,760',"--screenshot=$pngPath",$uri)
    $proc = Start-Process -FilePath $chrome -ArgumentList $chromeArgs -WindowStyle Hidden -Wait -PassThru
    if ($proc.ExitCode -ne 0) { throw "Chrome gagal merender visual $Name (exit $($proc.ExitCode))" }
    if (-not (Test-Path -LiteralPath $pngPath)) { throw "Gagal membuat visual: $Name" }
    return $pngPath
}

$svgStyle = @'
<defs>
  <style>
    .bg{fill:#f4f6f8}.panel{fill:#fff;stroke:#d8dee6;stroke-width:2}.navy{fill:#0b2545}
    .muted{fill:#667085}.green{fill:#18b779}.orange{fill:#f5a623}.blue{fill:#168bd2}.red{fill:#e74c3c}
    .t{font-family:Calibri,Arial,sans-serif;fill:#172b4d}.h{font:700 28px Calibri,Arial,sans-serif;fill:#172b4d}
    .sh{font:700 22px Calibri,Arial,sans-serif;fill:#172b4d}.b{font:700 18px Calibri,Arial,sans-serif;fill:#172b4d}
    .m{font:16px Calibri,Arial,sans-serif;fill:#667085}.s{font:14px Calibri,Arial,sans-serif;fill:#667085}
    .btn{fill:#fff;stroke:#b9c2cf;stroke-width:2}.btnGreen{fill:#18b779}.btnOrange{fill:#f5a623}.btnBlue{fill:#168bd2}
    .mark{fill:none;stroke:#e23d3d;stroke-width:6;stroke-linejoin:round}.dash{fill:none;stroke:#e23d3d;stroke-width:4;stroke-dasharray:12 8}
    .num{fill:#e23d3d}.numText{font:700 20px Calibri,Arial,sans-serif;fill:#fff;text-anchor:middle;dominant-baseline:middle}
    .line{stroke:#cfd6df;stroke-width:2}.arrow{stroke:#e23d3d;stroke-width:4;fill:none;marker-end:url(#a)}
  </style>
  <marker id="a" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto"><path d="M0,0 L0,6 L9,3 z" fill="#e23d3d"/></marker>
</defs>
'@

$visuals = @{}

$visuals.navigation = New-SvgAsset '01-navigation' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect class="bg" width="1400" height="760"/><rect class="panel" x="55" y="50" width="1290" height="660" rx="20"/>
<rect x="760" y="72" width="310" height="86" rx="14" fill="#fff" stroke="#18b779" stroke-width="3"/><text x="915" y="112" text-anchor="middle" class="b">[MENU] NAVIGATION</text><text x="915" y="140" text-anchor="middle" class="s">klik setelah login</text>
<rect class="mark" x="745" y="58" width="340" height="115" rx="18"/><circle class="num" cx="1120" cy="78" r="22"/><text class="numText" x="1120" y="79">1</text>
<rect x="100" y="205" width="1200" height="430" rx="16" fill="#fbfcfd" stroke="#e2e6ec" stroke-width="2"/>
<text class="m" x="150" y="250">Cari menu...</text><line class="line" x1="470" y1="285" x2="470" y2="590"/><line class="line" x1="850" y1="285" x2="850" y2="590"/>
<text class="sh" x="515" y="330">PROGRAM MUTU</text>
<rect x="500" y="360" width="320" height="64" rx="10" fill="#edf8f4"/><text class="b" x="540" y="400">Mapping Auditor Internal</text>
<rect x="500" y="450" width="320" height="82" rx="10" fill="#e9f5fb"/><text class="b" x="540" y="486">Temuan</text><text class="b" x="540" y="515">Ketidaksesuaian</text>
<rect class="mark" x="490" y="440" width="340" height="102" rx="14"/><circle class="num" cx="860" cy="492" r="22"/><text class="numText" x="860" y="493">2</text>
<path class="arrow" d="M1120 102 C1240 180,1040 320,850 476"/>
<text class="m" x="920" y="620">Pilih menu Temuan Ketidaksesuaian.</text>
</svg>
"@

$visuals.toolbar = New-SvgAsset '02-toolbar' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect class="bg" width="1400" height="760"/><rect class="panel" x="55" y="55" width="1290" height="650" rx="20"/>
<text class="h" x="95" y="115">TEMUAN KETIDAKSESUAIAN</text><text class="b" x="95" y="150">FMMO-163-14.4.3.b-88.5 | Laporan Ringkas dan Lembar Temuan</text>
<rect class="btn" x="605" y="190" width="180" height="62" rx="10"/><text class="b" x="695" y="228" text-anchor="middle">Mapping Auditor</text>
<rect class="btn" x="800" y="190" width="180" height="62" rx="10"/><text class="b" x="890" y="228" text-anchor="middle">Pakta Integritas</text>
<rect class="btn" x="995" y="190" width="155" height="62" rx="10" stroke="#f5a623"/><text class="b" x="1072" y="228" text-anchor="middle">Cetak Pakta</text>
<rect x="1165" y="190" width="150" height="62" rx="10" fill="#f5a623"/><text x="1240" y="228" text-anchor="middle" style="font:700 18px Calibri,Arial;fill:#172b4d">Cetak Laporan</text>
<rect class="mark" x="790" y="178" width="200" height="86" rx="12"/><circle class="num" cx="890" cy="292" r="22"/><text class="numText" x="890" y="293">1</text>
<rect class="mark" x="985" y="178" width="175" height="86" rx="12"/><circle class="num" cx="1072" cy="292" r="22"/><text class="numText" x="1072" y="293">2</text>
<rect class="mark" x="1155" y="178" width="170" height="86" rx="12"/><circle class="num" cx="1240" cy="292" r="22"/><text class="numText" x="1240" y="293">3</text>
<rect x="95" y="350" width="1220" height="235" rx="18" fill="#fbfcfd" stroke="#d8dee6" stroke-width="2"/>
<text class="b" x="130" y="400">PERAN ANDA PADA TAHUN 2026</text>
<rect x="130" y="430" width="340" height="45" rx="22" fill="#16c784"/><text x="300" y="458" text-anchor="middle" style="font:16px Calibri,Arial;fill:#fff">Anggota Auditor Kelistrikan Jakarta</text>
<rect x="485" y="430" width="300" height="45" rx="22" fill="#f5a623"/><text x="635" y="458" text-anchor="middle" style="font:16px Calibri,Arial;fill:#fff">Auditee Kelistrikan Gresik</text>
<text class="m" x="130" y="525">Label peran berasal dari mapping tahunan. Pengguna tidak memilih jenis audit sendiri.</text>
<text class="m" x="130" y="557">1 Pakta pribadi  |  2 Dokumen Pakta  |  3 Laporan gabungan seluruh lingkup pada tahun terpilih</text>
</svg>
"@

$visuals.pakta = New-SvgAsset '03-pakta' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect width="1400" height="760" fill="#47505c"/><rect class="panel" x="155" y="35" width="1090" height="690" rx="24"/>
<text class="h" x="195" y="90">FMMO-163-14.4.3.b-88.8 Pakta Integritas Auditor</text>
<rect x="195" y="120" width="1010" height="74" rx="12" fill="#e9f7fb" stroke="#9ad7e7"/><text class="b" x="225" y="150">Pakta integritas wajib diisi sebelum mengelola temuan.</text><text class="m" x="225" y="176">Nama, NID, dan jabatan Tim Audit Internal diambil otomatis.</text>
<rect x="195" y="220" width="1010" height="90" rx="12" fill="#fbfcfd" stroke="#d8dee6"/><text class="s" x="220" y="250">Nama / NID / Jabatan Tim Audit Internal</text><text class="b" x="220" y="282">otomatis dari login dan mapping tahun</text>
<rect class="mark" x="185" y="210" width="1030" height="110" rx="14"/><circle class="num" cx="1185" cy="225" r="22"/><text class="numText" x="1185" y="226">1</text>
<rect class="btn" x="195" y="345" width="430" height="62" rx="8"/><text class="m" x="220" y="370">Tanggal Pernyataan</text><text class="b" x="220" y="396">01 September 2026</text>
<rect class="btn" x="650" y="345" width="250" height="62" rx="8"/><text class="m" x="675" y="370">Lokasi</text><text class="b" x="675" y="396">Jakarta / Gresik</text>
<rect class="mark" x="185" y="335" width="725" height="82" rx="12"/><circle class="num" cx="930" cy="376" r="22"/><text class="numText" x="930" y="377">2</text>
<rect x="195" y="455" width="1010" height="145" rx="12" fill="#fff" stroke="#8ab1c1" stroke-width="3" stroke-dasharray="10 6"/><text class="m" x="700" y="525" text-anchor="middle">Tanda tangan di area ini dengan mouse, stylus, atau sentuhan</text>
<rect class="mark" x="185" y="445" width="1030" height="165" rx="14"/><circle class="num" cx="1185" cy="465" r="22"/><text class="numText" x="1185" y="466">3</text>
<rect x="930" y="635" width="275" height="56" rx="10" fill="#18b779"/><text x="1067" y="670" text-anchor="middle" style="font:700 18px Calibri,Arial;fill:#fff">Simpan Pakta Integritas</text>
<circle class="num" cx="905" cy="663" r="22"/><text class="numText" x="905" y="664">4</text>
</svg>
"@

$visuals.roles = New-SvgAsset '04-peran-auditor-auditee' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect class="bg" width="1400" height="760"/><text class="h" x="70" y="70">Tampilan otomatis berdasarkan mapping login</text>
<rect class="panel" x="70" y="105" width="1260" height="250" rx="18"/><circle cx="115" cy="150" r="28" fill="#e8f2f5"/><text class="sh" x="160" y="150">Audit Internal Teknik Kelistrikan Jakarta</text><rect x="1050" y="125" width="220" height="58" rx="10" fill="#18b779"/><text x="1160" y="161" text-anchor="middle" style="font:700 18px Calibri,Arial;fill:#fff">+ Tambah Temuan</text>
<text class="b" x="105" y="230">TIM AUDIT (ditampilkan lebih dahulu)</text><rect x="105" y="255" width="285" height="42" rx="21" fill="#e7f3f6"/><text class="m" x="247" y="282" text-anchor="middle">Lead Auditor | tetap satu orang</text><rect x="405" y="255" width="355" height="42" rx="21" fill="#e7f3f6"/><text class="m" x="582" y="282" text-anchor="middle">Anggota Auditor | sesuai lingkup</text>
<rect class="mark" x="90" y="205" width="1200" height="112" rx="14"/><circle class="num" cx="1280" cy="210" r="22"/><text class="numText" x="1280" y="211">1</text>
<rect class="panel" x="70" y="400" width="1260" height="270" rx="18"/><circle cx="115" cy="448" r="28" fill="#e8f2f5"/><text class="sh" x="160" y="448">Audit Internal Teknik Kelistrikan Gresik</text><rect x="780" y="422" width="235" height="44" rx="22" fill="#f5a623"/><text x="897" y="450" text-anchor="middle" style="font:16px Calibri,Arial;fill:#fff">Mode Auditee | Lihat Saja</text>
<text class="b" x="105" y="530">AUDITEE (ditampilkan di bawah)</text><rect x="105" y="555" width="350" height="42" rx="21" fill="#e8f6ef"/><text class="m" x="280" y="582" text-anchor="middle">Auditee | sesuai mapping login</text><text class="m" x="105" y="630">Dapat melihat temuan yang ditujukan kepada lingkupnya, tanpa tombol tambah/edit/hapus.</text>
<rect class="mark" x="90" y="495" width="1200" height="150" rx="14"/><circle class="num" cx="1280" cy="500" r="22"/><text class="numText" x="1280" y="501">2</text>
</svg>
"@

$visuals.finding = New-SvgAsset '05-tambah-temuan' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect width="1400" height="760" fill="#47505c"/><rect class="panel" x="145" y="55" width="1110" height="650" rx="24"/><text class="h" x="190" y="110">Tambah Temuan Ketidaksesuaian</text>
<text class="s" x="190" y="155">Jenis Audit</text><text class="b" x="190" y="182">Audit Internal Teknik Kelistrikan Jakarta</text>
<rect class="btn" x="190" y="220" width="390" height="70" rx="8"/><text class="s" x="215" y="247">Bagian (otomatis dari lingkup)</text><text class="b" x="215" y="277">Kelistrikan Jakarta</text>
<rect class="btn" x="605" y="220" width="230" height="70" rx="8"/><text class="s" x="630" y="247">Klausul *</text><text class="m" x="630" y="277">Contoh: 6.4.2</text>
<rect class="btn" x="860" y="220" width="210" height="70" rx="8"/><text class="s" x="885" y="247">Kategori Temuan *</text><text class="b" x="885" y="277">2 - Minor</text>
<rect class="mark" x="590" y="205" width="495" height="100" rx="12"/><circle class="num" cx="1080" cy="205" r="22"/><text class="numText" x="1080" y="206">1</text>
<rect class="btn" x="190" y="345" width="880" height="150" rx="8"/><text class="s" x="215" y="375">Uraian Ketidaksesuaian *</text><text class="m" x="215" y="415">Tuliskan kondisi, bukti objektif, dan dokumen/rekaman terkait secara lengkap.</text>
<rect class="mark" x="180" y="335" width="900" height="170" rx="12"/><circle class="num" cx="1090" cy="345" r="22"/><text class="numText" x="1090" y="346">2</text>
<rect class="btn" x="190" y="545" width="880" height="62" rx="8"/><text class="s" x="215" y="570">Auditor / Pengisi (otomatis)</text><text class="b" x="215" y="596">Nama pengguna yang sedang login</text>
<rect class="mark" x="180" y="535" width="900" height="82" rx="12"/><circle class="num" cx="1090" cy="545" r="22"/><text class="numText" x="1090" y="546">3</text>
<rect x="925" y="635" width="300" height="50" rx="9" fill="#18b779"/><text x="1075" y="666" text-anchor="middle" style="font:700 18px Calibri,Arial;fill:#fff">Simpan Temuan</text><circle class="num" cx="895" cy="660" r="22"/><text class="numText" x="895" y="661">4</text>
</svg>
"@

$visuals.mapping = New-SvgAsset '06-mapping' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect class="bg" width="1400" height="760"/><text class="h" x="65" y="65">Mapping Auditor Internal | periode tahunan</text>
<rect class="panel" x="65" y="95" width="1270" height="105" rx="16"/><text class="s" x="95" y="130">LEAD AUDITOR</text><text class="sh" x="95" y="168">Ditampilkan satu kali dan berlaku untuk seluruh lingkup audit pada tahun tersebut</text><rect class="mark" x="55" y="85" width="1290" height="125" rx="18"/><circle class="num" cx="1315" cy="95" r="22"/><text class="numText" x="1315" y="96">1</text>
<rect class="panel" x="65" y="240" width="600" height="405" rx="18"/><text class="sh" x="95" y="285">Audit Internal Mutu</text><text class="s" x="95" y="315">Mutu | Jakarta &amp; Gresik</text>
<line class="line" x1="95" y1="345" x2="635" y2="345"/><text class="b" x="115" y="385">Auditor Observer</text><rect x="430" y="360" width="150" height="40" rx="20" fill="#168bd2"/><text x="505" y="386" text-anchor="middle" style="font:15px Calibri,Arial;fill:#fff">Auditor</text><text class="s" x="115" y="415">OK - Pakta sudah diisi</text><text class="b" x="115" y="465">Auditee Mutu</text><rect x="430" y="440" width="150" height="40" rx="20" fill="#f5a623"/><text x="505" y="466" text-anchor="middle" style="font:15px Calibri,Arial;fill:#fff">Auditee</text><text class="s" x="115" y="495">Tidak wajib mengisi Pakta Auditor</text><rect class="mark" x="85" y="335" width="560" height="175" rx="14"/><circle class="num" cx="625" cy="345" r="22"/><text class="numText" x="625" y="346">2</text>
<rect class="panel" x="700" y="240" width="635" height="405" rx="18"/><text class="sh" x="730" y="285">Audit Internal Teknik Kelistrikan Jakarta</text><text class="s" x="730" y="315">Kelistrikan | Jakarta</text>
<line class="line" x1="730" y1="345" x2="1305" y2="345"/><text class="b" x="750" y="390">Anggota Auditor</text><text class="s" x="750" y="420">OK - Pakta sudah diisi</text><rect x="1170" y="360" width="70" height="55" rx="8" fill="#fff" stroke="#18b779" stroke-width="3"/><text x="1205" y="395" text-anchor="middle" style="font:700 16px Calibri,Arial;fill:#18b779">PRINT</text><rect class="mark" x="1155" y="345" width="100" height="85" rx="12"/><circle class="num" cx="1275" cy="386" r="22"/><text class="numText" x="1275" y="387">3</text><text class="m" x="750" y="505">Ikon printer muncul bagi Auditor yang sudah mengisi Pakta.</text><text class="m" x="750" y="538">Mapping tahun berikutnya dapat disiapkan kembali oleh pengelola.</text>
</svg>
"@

$visuals.report = New-SvgAsset '07-cetak-laporan' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect class="bg" width="1400" height="760"/><text class="h" x="70" y="70">Cetak Laporan = satu PDF gabungan per tahun</text>
<rect x="80" y="120" width="240" height="75" rx="12" fill="#f5a623"/><text x="200" y="165" text-anchor="middle" style="font:700 20px Calibri,Arial;fill:#172b4d">Cetak Laporan</text><rect class="mark" x="65" y="105" width="270" height="105" rx="14"/><circle class="num" cx="335" cy="115" r="22"/><text class="numText" x="335" y="116">1</text>
<path class="arrow" d="M345 158 L520 158"/><rect class="panel" x="540" y="100" width="780" height="560" rx="18"/><text class="sh" x="580" y="145">TEMUAN KETIDAKSESUAIAN 2026</text><text class="m" x="580" y="177">Dokumen bertambah mengikuti lingkup yang sudah memiliki data temuan.</text>
<rect x="580" y="215" width="700" height="50" rx="8" fill="#e9f5fb"/><text class="b" x="610" y="247">1. Audit Internal Mutu</text>
<rect x="580" y="275" width="700" height="50" rx="8" fill="#edf8f4"/><text class="b" x="610" y="307">2. Audit Internal Teknik Kelistrikan Jakarta</text>
<rect x="580" y="335" width="700" height="50" rx="8" fill="#edf8f4"/><text class="b" x="610" y="367">3. Audit Internal Teknik Tekanan Jakarta</text>
<rect x="580" y="395" width="700" height="50" rx="8" fill="#edf8f4"/><text class="b" x="610" y="427">4. Audit Internal Teknik Suhu &amp; Kelembapan Jakarta</text>
<text class="m" x="610" y="485">... dilanjutkan menurut urutan lingkup standar, bukan urutan siapa yang mengisi.</text>
<rect class="dash" x="565" y="200" width="730" height="310" rx="14"/><circle class="num" cx="1295" cy="210" r="22"/><text class="numText" x="1295" y="211">2</text>
<rect x="580" y="545" width="700" height="75" rx="10" fill="#fff4dd" stroke="#f5a623"/><text class="b" x="610" y="575">Syarat tombol aktif</text><text class="m" x="610" y="602">Minimal satu temuan tersimpan pada tahun yang dipilih.</text>
</svg>
"@

$visuals.flow = New-SvgAsset '00-alur-ringkas' @"
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="760" viewBox="0 0 1400 760">
$svgStyle
<rect class="bg" width="1400" height="760"/><text class="h" x="70" y="75">Alur singkat penggunaan</text>
<rect class="panel" x="70" y="125" width="230" height="130" rx="18"/><circle class="num" cx="110" cy="165" r="24"/><text class="numText" x="110" y="166">1</text><text class="sh" x="145" y="170">Login</text><text class="m" x="100" y="215">Identitas dikenali sistem</text>
<path class="arrow" d="M310 190 L385 190"/><rect class="panel" x="400" y="125" width="260" height="130" rx="18"/><circle class="num" cx="440" cy="165" r="24"/><text class="numText" x="440" y="166">2</text><text class="sh" x="475" y="170">Navigation</text><text class="m" x="430" y="215">Program Mutu -&gt; Temuan</text>
<path class="arrow" d="M670 190 L745 190"/><rect class="panel" x="760" y="125" width="260" height="130" rx="18"/><circle class="num" cx="800" cy="165" r="24"/><text class="numText" x="800" y="166">3</text><text class="sh" x="835" y="170">Mapping</text><text class="m" x="790" y="215">Peran &amp; lingkup otomatis</text>
<path class="arrow" d="M1030 190 L1105 190"/><rect class="panel" x="1120" y="125" width="220" height="130" rx="18"/><circle class="num" cx="1160" cy="165" r="24"/><text class="numText" x="1160" y="166">4</text><text class="sh" x="1195" y="170">Pakta</text><text class="m" x="1150" y="215">Auditor wajib isi</text>
<path class="arrow" d="M1230 270 C1230 340,1000 350,1000 405"/><rect class="panel" x="805" y="420" width="395" height="150" rx="18"/><circle class="num" cx="850" cy="465" r="24"/><text class="numText" x="850" y="466">5</text><text class="sh" x="890" y="470">Auditor mengisi temuan</text><text class="m" x="845" y="520">Tanggal dan nama pengisi otomatis</text>
<path class="arrow" d="M790 495 L690 495"/><rect class="panel" x="335" y="420" width="340" height="150" rx="18"/><circle class="num" cx="380" cy="465" r="24"/><text class="numText" x="380" y="466">6</text><text class="sh" x="420" y="470">Auditee melihat</text><text class="m" x="375" y="520">Mode lihat saja, tanpa edit</text>
<path class="arrow" d="M320 495 L230 495"/><rect class="panel" x="70" y="420" width="145" height="150" rx="18"/><circle class="num" cx="110" cy="465" r="24"/><text class="numText" x="110" y="466">7</text><text class="b" x="95" y="510">Cetak</text><text class="s" x="95" y="540">Pakta / Laporan</text>
<rect x="70" y="625" width="1270" height="70" rx="12" fill="#e9f7fb"/><text class="b" x="105" y="668">Prinsip utama: pengguna tidak memilih jenis audit; sistem mengikuti mapping tahun dan akun login.</text>
</svg>
"@

Write-Output 'PROGRESS visuals-ready'

function To-WordColor([string]$Hex) {
    $h = $Hex.TrimStart('#')
    $r = [Convert]::ToInt32($h.Substring(0,2),16)
    $g = [Convert]::ToInt32($h.Substring(2,2),16)
    $b = [Convert]::ToInt32($h.Substring(4,2),16)
    return $r + (256 * $g) + (65536 * $b)
}

$wdStyleNormal = -1
$wdStyleHeading1 = -2
$wdStyleHeading2 = -3
$wdStyleHeading3 = -4
$wdStyleTitle = -63
$wdStyleSubtitle = -75
$wdAlignLeft = 0
$wdAlignCenter = 1
$wdAlignRight = 2
$wdLineSpaceMultiple = 5
$wdPageBreak = 7
$wdCollapseEnd = 0
$wdFieldPage = 33
$wdExportFormatPDF = 17
$wdBorderTop = -1
$wdBorderLeft = -2
$wdBorderBottom = -3
$wdBorderRight = -4
$wdLineStyleSingle = 1
$wdCellAlignVerticalCenter = 1
$wdNumberGallery = 2
$wdBulletGallery = 1

$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Add()
    Write-Output 'PROGRESS word-ready'
    Write-Output ("PROGRESS path=" + [string]$outputDocx + " type=" + $outputDocx.GetType().FullName)
    $doc.SaveAs2($outputDocx, 12)
    Write-Output 'PROGRESS blank-save'
    $sel = $word.Selection
    $section = $doc.Sections.Item(1)
    $section.PageSetup.PageWidth = 612
    $section.PageSetup.PageHeight = 792
    $section.PageSetup.TopMargin = 72
    $section.PageSetup.BottomMargin = 72
    $section.PageSetup.LeftMargin = 72
    $section.PageSetup.RightMargin = 72
    $section.PageSetup.HeaderDistance = 35.4
    $section.PageSetup.FooterDistance = 35.4
    $section.PageSetup.DifferentFirstPageHeaderFooter = 0

    $normal = $doc.Styles.Item($wdStyleNormal)
    $normal.Font.Name = 'Calibri'
    $normal.Font.Size = 11
    $normal.Font.Color = To-WordColor '#1F2937'
    $normal.ParagraphFormat.SpaceBefore = 0
    $normal.ParagraphFormat.SpaceAfter = 6
    $normal.ParagraphFormat.LineSpacingRule = $wdLineSpaceMultiple
    $normal.ParagraphFormat.LineSpacing = 13.75

    $title = $doc.Styles.Item($wdStyleTitle)
    $title.Font.Name = 'Calibri'
    $title.Font.Size = 30
    $title.Font.Bold = -1
    $title.Font.Color = To-WordColor '#0B2545'
    $title.ParagraphFormat.Alignment = $wdAlignCenter
    $title.ParagraphFormat.SpaceAfter = 8

    $subtitle = $doc.Styles.Item($wdStyleSubtitle)
    $subtitle.Font.Name = 'Calibri'
    $subtitle.Font.Size = 14
    $subtitle.Font.Color = To-WordColor '#52677D'
    $subtitle.ParagraphFormat.Alignment = $wdAlignCenter
    $subtitle.ParagraphFormat.SpaceAfter = 8

    $h1 = $doc.Styles.Item($wdStyleHeading1)
    $h1.Font.Name = 'Calibri'
    $h1.Font.Size = 16
    $h1.Font.Bold = -1
    $h1.Font.Color = To-WordColor '#2E74B5'
    $h1.ParagraphFormat.SpaceBefore = 18
    $h1.ParagraphFormat.SpaceAfter = 10
    $h1.ParagraphFormat.KeepWithNext = -1

    $h2 = $doc.Styles.Item($wdStyleHeading2)
    $h2.Font.Name = 'Calibri'
    $h2.Font.Size = 13
    $h2.Font.Bold = -1
    $h2.Font.Color = To-WordColor '#2E74B5'
    $h2.ParagraphFormat.SpaceBefore = 14
    $h2.ParagraphFormat.SpaceAfter = 7
    $h2.ParagraphFormat.KeepWithNext = -1

    $h3 = $doc.Styles.Item($wdStyleHeading3)
    $h3.Font.Name = 'Calibri'
    $h3.Font.Size = 12
    $h3.Font.Bold = -1
    $h3.Font.Color = To-WordColor '#1F4D78'
    $h3.ParagraphFormat.SpaceBefore = 10
    $h3.ParagraphFormat.SpaceAfter = 5
    $h3.ParagraphFormat.KeepWithNext = -1

    $header = $section.Headers.Item(1).Range
    $header.Text = 'PANDUAN FITUR TEMUAN KETIDAKSESUAIAN'
    $header.Font.Name = 'Calibri'
    $header.Font.Size = 8.5
    $header.Font.Bold = -1
    $header.Font.Color = To-WordColor '#667085'
    $header.ParagraphFormat.Alignment = $wdAlignLeft
    $footer = $section.Footers.Item(1).Range
    $footer.Text = 'ULAB | Panduan Pengguna Fitur Temuan Ketidaksesuaian'
    $footer.Font.Name = 'Calibri'
    $footer.Font.Size = 8.5
    $footer.Font.Color = To-WordColor '#667085'
    $footer.ParagraphFormat.Alignment = $wdAlignCenter

    $numTemplate = $word.ListGalleries.Item($wdNumberGallery).ListTemplates.Item(1)
    $bulletTemplate = $word.ListGalleries.Item($wdBulletGallery).ListTemplates.Item(1)

    function Add-Paragraph([string]$Text, [int]$Align = 0, [double]$After = 6, [bool]$Bold = $false, [string]$Color = '#1F2937') {
        $sel.Style = $doc.Styles.Item($wdStyleNormal)
        $sel.ParagraphFormat.Alignment = $Align
        $sel.ParagraphFormat.SpaceBefore = 0
        $sel.ParagraphFormat.SpaceAfter = $After
        $sel.Font.Name = 'Calibri'
        $sel.Font.Size = 11
        $sel.Font.Bold = $(if ($Bold) { -1 } else { 0 })
        $sel.Font.Color = To-WordColor $Color
        $sel.TypeText($Text)
        $sel.TypeParagraph()
    }

    function Add-Heading([string]$Text, [int]$Level = 1) {
        $styleId = switch ($Level) { 1 {$wdStyleHeading1} 2 {$wdStyleHeading2} default {$wdStyleHeading3} }
        $sel.Style = $doc.Styles.Item($styleId)
        $sel.TypeText($Text)
        $sel.TypeParagraph()
    }

    function Add-Numbered([string[]]$Items) {
        $first = $true
        foreach ($item in $Items) {
            $sel.Style = $doc.Styles.Item($wdStyleNormal)
            $start = $sel.Start
            $sel.TypeText($item)
            $sel.TypeParagraph()
            $range = $doc.Range($start, $sel.Start - 1)
            $continueList = -not $first
            $range.ListFormat.ApplyListTemplateWithLevel($numTemplate, $continueList, 0, 0, 1)
            $range.ParagraphFormat.LeftIndent = 27
            $range.ParagraphFormat.FirstLineIndent = -13.5
            $range.ParagraphFormat.SpaceAfter = 4
            $range.ParagraphFormat.LineSpacingRule = $wdLineSpaceMultiple
            $range.ParagraphFormat.LineSpacing = 13.75
            $first = $false
        }
    }

    function Add-Bullets([string[]]$Items) {
        $first = $true
        foreach ($item in $Items) {
            $sel.Style = $doc.Styles.Item($wdStyleNormal)
            $start = $sel.Start
            $sel.TypeText($item)
            $sel.TypeParagraph()
            $range = $doc.Range($start, $sel.Start - 1)
            $continueList = -not $first
            $range.ListFormat.ApplyListTemplateWithLevel($bulletTemplate, $continueList, 0, 0, 1)
            $range.ParagraphFormat.LeftIndent = 27
            $range.ParagraphFormat.FirstLineIndent = -13.5
            $range.ParagraphFormat.SpaceAfter = 4
            $range.ParagraphFormat.LineSpacingRule = $wdLineSpaceMultiple
            $range.ParagraphFormat.LineSpacing = 13.75
            $first = $false
        }
    }

    function Add-Callout([string]$Label, [string]$Text, [string]$Fill = '#E9F7FB', [string]$Border = '#7EC8DA') {
        $start = $sel.Start
        $sel.Style = $doc.Styles.Item($wdStyleNormal)
        $sel.Font.Name = 'Calibri'
        $sel.Font.Size = 10.5
        $sel.Font.Color = To-WordColor '#172B4D'
        $sel.Font.Bold = -1
        $sel.TypeText($Label + '  ')
        $sel.Font.Bold = 0
        $sel.TypeText($Text)
        $sel.TypeParagraph()
        $p = $doc.Range($start, $sel.Start - 1).Paragraphs.Item(1)
        $p.Range.ParagraphFormat.LeftIndent = 8
        $p.Range.ParagraphFormat.RightIndent = 8
        $p.Range.ParagraphFormat.SpaceBefore = 6
        $p.Range.ParagraphFormat.SpaceAfter = 10
        $p.Range.Shading.BackgroundPatternColor = To-WordColor $Fill
        foreach ($idx in @($wdBorderTop,$wdBorderLeft,$wdBorderBottom,$wdBorderRight)) {
            $p.Borders.Item($idx).LineStyle = $wdLineStyleSingle
            $p.Borders.Item($idx).Color = To-WordColor $Border
        }
    }

    function Add-Figure([string]$Path, [string]$Caption, [double]$Width = 6.45) {
        $sel.ParagraphFormat.Alignment = $wdAlignCenter
        $shape = $sel.InlineShapes.AddPicture($Path, $false, $true)
        $shape.LockAspectRatio = -1
        $shape.Width = $word.InchesToPoints($Width)
        $shape.AlternativeText = $Caption
        $sel.EndKey(6) | Out-Null
        $sel.TypeParagraph()
        $sel.Style = $doc.Styles.Item($wdStyleNormal)
        $sel.ParagraphFormat.Alignment = $wdAlignCenter
        $sel.ParagraphFormat.SpaceBefore = 3
        $sel.ParagraphFormat.SpaceAfter = 10
        $sel.Font.Name = 'Calibri'
        $sel.Font.Size = 9
        $sel.Font.Italic = -1
        $sel.Font.Color = To-WordColor '#667085'
        $sel.TypeText($Caption)
        $sel.TypeParagraph()
        $sel.Font.Italic = 0
    }

    function Add-PageBreak { $sel.InsertBreak($wdPageBreak) }

    function Add-AccessTable {
        $range = $sel.Range
        $table = $doc.Tables.Add($range, 5, 3)
        $table.AllowAutoFit = $false
        $table.Columns.Item(1).Width = $word.InchesToPoints(1.25)
        $table.Columns.Item(2).Width = $word.InchesToPoints(2.55)
        $table.Columns.Item(3).Width = $word.InchesToPoints(2.70)
        $table.Rows.LeftIndent = 6
        $table.TopPadding = 4
        $table.BottomPadding = 4
        $table.LeftPadding = 6
        $table.RightPadding = 6
        $table.Borders.Enable = 1
        $data = @(
            @('Peran','Yang terlihat','Yang dapat dilakukan'),
            @('Auditor','Lingkup audit tempat akun ditugaskan sebagai Auditor','Tambah, lihat, edit, dan hapus temuan pada lingkupnya'),
            @('Auditee','Lingkup audit tempat akun ditugaskan sebagai Auditee','Melihat temuan yang ditujukan kepada lingkupnya (lihat saja)'),
            @('Lead Auditor','Ditampilkan satu kali sebagai ketua Tim Audit Internal','Mendukung seluruh lingkup sesuai penetapan tahun'),
            @('Pengelola','Mapping Auditor Internal per tahun','Menetapkan anggota, Auditee, status Pakta, dan mencetak Pakta yang sudah diisi')
        )
        for ($r=1; $r -le 5; $r++) {
            for ($c=1; $c -le 3; $c++) {
                $cell = $table.Cell($r,$c)
                $cell.Range.Text = $data[$r-1][$c-1]
                $cell.Range.Font.Name = 'Calibri'
                if ($r -eq 1) {
                    $cell.Range.Font.Size = [single]10.5
                    $cell.Range.Font.Bold = -1
                } else {
                    $cell.Range.Font.Size = [single]10.0
                    $cell.Range.Font.Bold = 0
                }
                $cell.Range.ParagraphFormat.SpaceAfter = 0
                $cell.Range.ParagraphFormat.LineSpacingRule = $wdLineSpaceMultiple
                $cell.Range.ParagraphFormat.LineSpacing = 12.5
                $cell.VerticalAlignment = $wdCellAlignVerticalCenter
                if ($r -eq 1) { $cell.Shading.BackgroundPatternColor = To-WordColor '#E8EEF5' }
            }
        }
        $sel.SetRange($table.Range.End, $table.Range.End)
        $sel.TypeParagraph()
    }

    # Cover
    $sel.TypeParagraph(); $sel.TypeParagraph(); $sel.TypeParagraph(); $sel.TypeParagraph()
    $sel.Style = $doc.Styles.Item($wdStyleNormal)
    $sel.ParagraphFormat.Alignment = $wdAlignCenter
    $sel.Font.Name = 'Calibri'; $sel.Font.Size = 11; $sel.Font.Bold = -1; $sel.Font.Color = To-WordColor '#18A878'
    $sel.TypeText('PANDUAN PENGGUNA'); $sel.TypeParagraph()
    $sel.Style = $doc.Styles.Item($wdStyleTitle)
    $sel.TypeText('Fitur Temuan Ketidaksesuaian'); $sel.TypeParagraph()
    $sel.Style = $doc.Styles.Item($wdStyleSubtitle)
    $sel.TypeText('Panduan praktis untuk Auditor Internal, Auditee, dan Pengelola Mapping'); $sel.TypeParagraph()
    $sel.TypeParagraph()
    $sel.Style = $doc.Styles.Item($wdStyleNormal)
    $sel.ParagraphFormat.Alignment = $wdAlignCenter
    $sel.Font.Name = 'Calibri'; $sel.Font.Size = 12; $sel.Font.Bold = -1; $sel.Font.Color = To-WordColor '#0B2545'
    $sel.TypeText('FMMO-163-14.4.3.b-88.5'); $sel.TypeParagraph()
    $sel.Font.Size = 10.5; $sel.Font.Bold = 0; $sel.Font.Color = To-WordColor '#667085'
    $sel.TypeText('Laporan Ringkas dan Lembar Temuan Ketidaksesuaian'); $sel.TypeParagraph()
    $sel.TypeParagraph(); $sel.TypeParagraph()
    Add-Callout 'INTI PENGGUNAAN' 'Setelah login, sistem langsung mengikuti mapping tahunan. Auditor mengisi temuan pada lingkup tugasnya; Auditee melihat hasil pada lingkup tempat ia dipetakan sebagai Auditee.' '#E9F7FB' '#7EC8DA'
    Add-Paragraph 'Versi panduan: 01 September 2026' $wdAlignCenter 0 $false '#667085'
    Write-Output 'PROGRESS cover'
    $doc.Save()
    Write-Output 'PROGRESS initial-save'

    Add-PageBreak
    Add-Heading '1. Ringkasan Alur' 1
    Add-Paragraph 'Gunakan alur berikut sebagai gambaran cepat sebelum membaca langkah rinci.'
    Add-Figure $visuals.flow 'Gambar 1. Alur penggunaan dari login sampai pencetakan.'
    Add-Callout 'PENTING' 'Jenis audit tidak dipilih manual oleh pengguna. Lingkup yang muncul ditentukan oleh akun login, mapping peran, dan tahun laporan yang dipilih.' '#FFF4DD' '#F5A623'
    Write-Output 'PROGRESS section-1'

    Add-PageBreak
    Add-Heading '2. Masuk ke Fitur Setelah Login' 1
    Add-Numbered @(
        'Login ke aplikasi ULAB dengan akun masing-masing.',
        'Klik NAVIGATION pada bagian atas aplikasi.',
        'Pada kelompok PROGRAM MUTU, klik Temuan Ketidaksesuaian.'
    )
    Add-Figure $visuals.navigation 'Gambar 2. Jalur menu: Navigation -> Program Mutu -> Temuan Ketidaksesuaian.'
    Add-Callout 'CATATAN PENGELOLA' 'Menu Mapping Auditor Internal digunakan untuk menyiapkan penugasan per tahun. Auditor dan Auditee biasa tidak perlu memilih perannya sendiri.' '#F4F6F9' '#B9C2CF'
    Write-Output 'PROGRESS section-2'

    Add-PageBreak
    Add-Heading '3. Peran Sudah Dimapping Sesuai Tugas' 1
    Add-Paragraph 'Sesudah halaman terbuka, sistem membaca mapping pada tahun yang dipilih. Satu akun dapat menjadi Auditor pada suatu lingkup dan sekaligus menjadi Auditee pada lingkup lain.'
    Add-AccessTable
    Add-Callout 'URUTAN TAMPILAN' 'Lingkup tempat pengguna menjadi Auditor selalu ditampilkan lebih dahulu. Lingkup tempat pengguna menjadi Auditee ditampilkan di bawah dengan label Mode Auditee | Lihat Saja.' '#E9F7FB' '#7EC8DA'
    Add-Figure $visuals.roles 'Gambar 3. Perbedaan tampilan Auditor (atas) dan Auditee (bawah).'
    Write-Output 'PROGRESS section-3'

    Add-PageBreak
    Add-Heading '4. Kenali Tombol Utama dan Periode Laporan' 1
    Add-Paragraph 'Pastikan tahun pada Periode Laporan sesuai dengan tahun audit. Gunakan Muat Ulang setelah mengganti tahun agar mapping dan data ditampilkan kembali.'
    Add-Figure $visuals.toolbar 'Gambar 4. Tombol utama Pakta, pencetakan, dan label peran akun.'
    Add-Numbered @(
        'Pakta Integritas: membuka atau mengisi Pakta Auditor untuk akun yang sedang login.',
        'Cetak Pakta: mencetak Pakta milik akun login setelah Pakta tersimpan.',
        'Cetak Laporan: membuka satu laporan gabungan seluruh lingkup pada tahun terpilih.'
    )
    Add-Callout 'SYARAT CETAK LAPORAN' 'Tombol Cetak Laporan aktif setelah minimal satu data temuan tersimpan pada tahun yang dipilih, termasuk temuan yang dibuat oleh Auditor lain.' '#FFF4DD' '#F5A623'
    Write-Output 'PROGRESS section-4'

    Add-PageBreak
    Add-Heading '5. Auditor: Isi Pakta Integritas Terlebih Dahulu' 1
    Add-Paragraph 'Pakta Integritas wajib bagi Auditor sebelum mengelola temuan. Auditee murni tidak wajib mengisi Pakta Auditor.'
    Add-Figure $visuals.pakta 'Gambar 5. Form Pakta Integritas dan area tanda tangan digital.'
    Add-Numbered @(
        'Periksa Nama, NID, dan Jabatan Tim Audit Internal. Data ini terisi otomatis dari akun login dan mapping.',
        'Isi Tanggal Pernyataan dan pilih Lokasi Jakarta atau Gresik.',
        'Bubuhkan tanda tangan pada area tanda tangan menggunakan mouse, stylus, atau layar sentuh.',
        'Klik Simpan Pakta Integritas.'
    )
    Add-Callout 'JANGAN TERTUKAR' 'Jabatan yang tampil pada Pakta adalah jabatan di Tim Audit Internal (misalnya Anggota Auditor atau Auditor Observer), bukan jabatan struktural pegawai.' '#FDECEC' '#E76A6A'
    Write-Output 'PROGRESS section-5'

    Add-PageBreak
    Add-Heading '6. Auditor: Tambah Temuan Ketidaksesuaian' 1
    Add-Paragraph 'Pada kartu lingkup tempat Anda menjadi Auditor, klik Tambah Temuan. Tombol ini tidak muncul pada kartu Mode Auditee.'
    Add-Figure $visuals.finding 'Gambar 6. Form tambah temuan; bagian dan nama pengisi mengikuti mapping/login.'
    Add-Numbered @(
        'Isi Klausul sesuai standar acuan dan pilih Kategori Temuan: 1 - Major, 2 - Minor, atau 3 - Observasi.',
        'Tuliskan uraian ketidaksesuaian secara lengkap: kondisi, bukti objektif, dan dokumen/rekaman terkait.',
        'Pastikan Auditor/Pengisi sudah menunjukkan nama akun yang sedang login.',
        'Klik Simpan Temuan.'
    )
    Add-Bullets @(
        'Nama LPK dan Standar Acuan diisi otomatis oleh sistem.',
        'Tanggal Audit Internal ditetapkan otomatis saat temuan pertama pada lingkup tersebut disimpan.',
        'Data yang baru disimpan langsung menambah ringkasan Major, Minor, Observasi, dan Total.'
    )
    Write-Output 'PROGRESS section-6'

    Add-PageBreak
    Add-Heading '7. Auditee: Melihat Temuan yang Ditujukan kepada Anda' 1
    Add-Paragraph 'Jika akun Anda juga dipetakan sebagai Auditee, kartu lingkup tersebut muncul di bawah bagian Auditor. Kartu diberi label Mode Auditee | Lihat Saja.'
    Add-Numbered @(
        'Cari kartu dengan label Mode Auditee | Lihat Saja.',
        'Lihat daftar temuan, bagian, klausul, kategori, uraian, serta Auditor/Pengisi.',
        'Tidak ada tombol Tambah Temuan, Edit, atau Hapus pada mode Auditee.'
    )
    Add-Callout 'CONTOH' 'Seseorang dapat menjadi Auditor Kelistrikan Jakarta dan sekaligus menjadi Auditee Kelistrikan Gresik. Temuan sebagai Auditor tampil di bagian atas; temuan yang ditujukan kepadanya sebagai Auditee tampil di bagian bawah.' '#E9F7FB' '#7EC8DA'
    Add-Heading 'Batas akses yang perlu dipahami' 2
    Add-Bullets @(
        'Auditor hanya mengelola temuan pada lingkup tempat ia ditugaskan sebagai Auditor.',
        'Auditee dapat melihat seluruh temuan pada lingkup tempat ia ditetapkan sebagai Auditee.',
        'Pengaturan ini mengikuti mapping tahun dan akun login, bukan siapa yang membuat data lebih dahulu.'
    )
    Write-Output 'PROGRESS section-7'

    Add-PageBreak
    Add-Heading '8. Melihat Mapping dan Mencetak Pakta' 1
    Add-Figure $visuals.mapping 'Gambar 7. Mapping tahunan, status pengisian Pakta, dan ikon cetak per Auditor.'
    Add-Heading 'Untuk pengguna' 2
    Add-Bullets @(
        'Klik Cetak Pakta pada halaman Temuan Ketidaksesuaian untuk mencetak Pakta milik akun login.',
        'Cetak Pakta hanya tersedia setelah Pakta Integritas berhasil disimpan.'
    )
    Write-Output 'PROGRESS section-8'
    Add-Heading 'Untuk pengelola mapping' 2
    Add-Bullets @(
        'Lead Auditor ditampilkan satu kali dan tidak perlu diulang pada setiap kartu lingkup.',
        'Setiap kartu lingkup berisi anggota Auditor dan Auditee sesuai penugasan.',
        'Status Pakta sudah diisi/belum diisi terlihat pada baris Auditor.',
        'Klik ikon printer pada Auditor yang sudah mengisi Pakta untuk membuka cetakan Pakta orang tersebut.',
        'Mapping diperiodekan per tahun sehingga dapat disiapkan kembali untuk tahun berikutnya.'
    )

    Add-PageBreak
    Add-Heading '9. Mencetak Laporan Keseluruhan' 1
    Add-Paragraph 'Klik Cetak Laporan untuk melihat hasil keseluruhan pada tahun yang dipilih. Cetakan tidak dibatasi oleh lingkup yang sedang terlihat pada akun login.'
    Add-Figure $visuals.report 'Gambar 8. Cetak Laporan menghasilkan satu PDF gabungan seluruh lingkup.'
    Add-Bullets @(
        'Semua lingkup yang sudah memiliki data temuan digabung dalam satu PDF.',
        'Isi PDF bertambah mengikuti data yang telah dimasukkan oleh para Auditor.',
        'Urutan lingkup mengikuti urutan laporan baku, bukan urutan waktu pengisian atau nama pengisi.',
        'Kolom tanda tangan pada laporan disediakan kosong untuk penandatanganan manual.'
    )
    Add-Heading 'Urutan lingkup pada laporan' 2
    Add-Numbered @(
        'Audit Internal Mutu',
        'Audit Internal Teknik Kelistrikan Jakarta',
        'Audit Internal Teknik Tekanan Jakarta',
        'Audit Internal Teknik Suhu dan Kelembapan Jakarta',
        'Audit Internal Teknik Vibrasi Jakarta',
        'Audit Internal Teknik Kelistrikan Gresik',
        'Audit Internal Teknik Tekanan Gresik',
        'Audit Internal Teknik Suhu Gresik',
        'Audit Internal Teknik Dimensi Gresik'
    )
    Write-Output 'PROGRESS section-9'

    Add-PageBreak
    Add-Heading '10. Checklist Cepat Sebelum Selesai' 1
    Add-Heading 'Checklist Auditor' 2
    Add-Bullets @(
        'Tahun laporan sudah benar.',
        'Peran Auditor dan lingkup tugas yang tampil sudah sesuai.',
        'Pakta Integritas sudah disimpan dan tanda tangan tersimpan.',
        'Klausul, kategori, dan uraian temuan sudah benar.',
        'Temuan sudah muncul pada tabel dan ringkasan jumlah sudah bertambah.'
    )
    Add-Heading 'Checklist Auditee' 2
    Add-Bullets @(
        'Kartu Mode Auditee | Lihat Saja sudah muncul pada bagian bawah.',
        'Temuan yang ditujukan kepada lingkup Auditee dapat dibaca.',
        'Tidak ada kebutuhan untuk menambah atau mengubah temuan dari mode Auditee.'
    )
    Add-Heading 'Jika tombol atau data belum muncul' 2
    Add-Numbered @(
        'Periksa tahun pada Periode Laporan.',
        'Klik Muat Ulang.',
        'Pastikan mapping akun sudah dibuat pada tahun tersebut.',
        'Untuk Cetak Pakta, pastikan Pakta sudah disimpan.',
        'Untuk Cetak Laporan, pastikan minimal satu temuan sudah tersimpan pada tahun tersebut.'
    )
    Add-Callout 'RINGKAS' 'Auditor mengisi Pakta dan temuan. Auditee membaca temuan pada lingkupnya. Cetak Pakta mencetak dokumen per orang, sedangkan Cetak Laporan menampilkan rekap gabungan seluruh lingkup dalam satu tahun.' '#E9F7FB' '#7EC8DA'
    Write-Output 'PROGRESS section-10'

    # Built-in properties are not exposed consistently by all Office COM builds;
    # content and layout are authoritative for this deliverable.

    Write-Output 'PROGRESS saving-docx'
    $doc.Save()
    Write-Output 'PROGRESS exporting-pdf'
    $doc.ExportAsFixedFormat($outputPdf, $wdExportFormatPDF)
}
finally {
    if ($doc -ne $null) { $doc.Close($false) }
    if ($word -ne $null) { $word.Quit() }
    if ($sel -ne $null) { [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($sel) }
    if ($doc -ne $null) { [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($doc) }
    if ($word -ne $null) { [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) }
    [GC]::Collect(); [GC]::WaitForPendingFinalizers()
}

Write-Output $outputDocx
Write-Output $outputPdf
