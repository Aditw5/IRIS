$ErrorActionPreference = 'Stop'
$docx = 'C:\ulab-kalibrasi\docs\Panduan_Penggunaan_Fitur_Temuan_Ketidaksesuaian.docx'
$outDir = 'C:\ulab-kalibrasi\docs\panduan-render-clipboard'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
$ppt = New-Object -ComObject PowerPoint.Application
$presentation = $null
$document = $null
try {
    $document = $word.Documents.Open($docx, $false, $true)
    $document.Repaginate()
    $pages = $document.ComputeStatistics(2)
    $presentation = $ppt.Presentations.Add()
    $presentation.PageSetup.SlideWidth = 612
    $presentation.PageSetup.SlideHeight = 792
    for ($i = 1; $i -le $pages; $i++) {
        $start = $document.GoTo(1, 1, $i).Start
        if ($i -lt $pages) {
            $end = $document.GoTo(1, 1, $i + 1).Start - 1
        } else {
            $end = $document.Content.End - 1
        }
        $range = $document.Range($start, $end)
        $range.Select()
        $word.Selection.CopyAsPicture()
        Start-Sleep -Milliseconds 600
        $slide = $presentation.Slides.Add($presentation.Slides.Count + 1, 12)
        $shapeRange = $slide.Shapes.Paste()
        $shape = $shapeRange.Item(1)
        $shape.LockAspectRatio = -1
        $shape.Left = 18
        $shape.Top = 18
        $shape.Width = 576
        if ($shape.Height -gt 756) { $shape.Height = 756 }
        $png = Join-Path $outDir ('page-{0}.png' -f $i)
        $slide.Export($png, 'PNG', 1224, 1584)
    }
    Write-Output ("pages=" + $pages)
}
finally {
    if ($presentation -ne $null) { $presentation.Close() }
    $ppt.Quit()
    if ($document -ne $null) { $document.Close($false) }
    $word.Quit()
}
