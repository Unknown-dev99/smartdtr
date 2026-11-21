param(
    [Parameter(Mandatory = $true)][string]$Source,
    [Parameter(Mandatory = $true)][string]$Output,
    [int]$Orientation = 2,
    [int]$PaperSize = 14,
    [string]$PrintArea = "A1:AF51"
)

$ErrorActionPreference = "Stop"

$excel = $null
$workbook = $null

try {
    $Source = [System.IO.Path]::GetFullPath($Source)
    $Output = [System.IO.Path]::GetFullPath($Output)

    if (-not (Test-Path -LiteralPath $Source)) {
        throw "Excel source not found: $Source"
    }

    $outDir = Split-Path -Parent $Output
    if ($outDir -and -not (Test-Path -LiteralPath $outDir)) {
        New-Item -ItemType Directory -Force -Path $outDir | Out-Null
    }

    if (Test-Path -LiteralPath $Output) {
        Remove-Item -LiteralPath $Output -Force
    }

    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    $excel.ScreenUpdating = $false

    $workbook = $excel.Workbooks.Open($Source)

    foreach ($sheet in $workbook.Worksheets) {
        $sheet.PageSetup.PaperSize = $PaperSize
        $sheet.PageSetup.Orientation = $Orientation
        $sheet.PageSetup.Zoom = $false
        $sheet.PageSetup.FitToPagesWide = 1
        $sheet.PageSetup.FitToPagesTall = 1
        $sheet.PageSetup.LeftMargin = $excel.InchesToPoints(0.25)
        $sheet.PageSetup.RightMargin = $excel.InchesToPoints(0.25)
        $sheet.PageSetup.TopMargin = $excel.InchesToPoints(0.25)
        $sheet.PageSetup.BottomMargin = $excel.InchesToPoints(0.25)
        $sheet.PageSetup.HeaderMargin = 0
        $sheet.PageSetup.FooterMargin = 0
        $sheet.PageSetup.CenterHorizontally = $true
        $sheet.PageSetup.CenterVertically = $true
        if ($PrintArea) {
            $sheet.PageSetup.PrintArea = $PrintArea
        }
    }

    $workbook.ExportAsFixedFormat(0, $Output)

    if (-not (Test-Path -LiteralPath $Output)) {
        throw "PDF was not created: $Output"
    }

    Write-Output "OK|$Output"
}
catch {
    Write-Output ("ERROR|" + $_.Exception.Message)
    exit 1
}
finally {
    if ($workbook -ne $null) {
        $workbook.Close($false) | Out-Null
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($workbook) | Out-Null
    }
    if ($excel -ne $null) {
        $excel.Quit() | Out-Null
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($excel) | Out-Null
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
