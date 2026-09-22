$ErrorActionPreference = 'Stop'
$docx = 'C:\ulab-kalibrasi\docs\Panduan_Penggunaan_Fitur_Temuan_Ketidaksesuaian.docx'
$renderDir = 'C:\ulab-kalibrasi\docs\panduan-render'
$pdf = Join-Path $renderDir 'Panduan_Penggunaan_Fitur_Temuan_Ketidaksesuaian.pdf'
New-Item -ItemType Directory -Force -Path $renderDir | Out-Null
Remove-Item -LiteralPath $pdf -Force -ErrorAction SilentlyContinue
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
$document = $null
try {
    $document = $word.Documents.Open($docx, $false, $true)
    $document.SaveAs2($pdf, 17)
}
finally {
    if ($document -ne $null) { $document.Close($false) }
    $word.Quit()
}
Write-Output $pdf
