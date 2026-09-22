$ErrorActionPreference = 'Stop'
$p = 'C:\ulab-kalibrasi\docs\word-script-test.docx'
Remove-Item -LiteralPath $p -Force -ErrorAction SilentlyContinue
$w = New-Object -ComObject Word.Application
$w.Visible = $false
$w.DisplayAlerts = 0
$d = $w.Documents.Add()
Write-Output 'before-save'
$d.SaveAs2($p, 12)
Write-Output 'after-save'
$d.Close($false)
$w.Quit()
