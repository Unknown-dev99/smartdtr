param(
    [Parameter(Mandatory = $true)][string]$Source,
    [Parameter(Mandatory = $true)][string]$Output,
    [Parameter(Mandatory = $true)][string]$Name,
    [Parameter(Mandatory = $true)][string]$Position,
    [Parameter(Mandatory = $true)][string]$Purpose,
    [Parameter(Mandatory = $true)][string]$DateTime,
    [Parameter(Mandatory = $true)][string]$Destination,
    [Parameter(Mandatory = $true)][ValidateSet('Official Business','Official Time')][string]$Nature,
    [Parameter(Mandatory = $true)][string]$SchoolHead,
    [Parameter(Mandatory = $true)][string]$SchoolHeadTitle
)

$ErrorActionPreference = "Stop"

function Set-ValueCell($cell, [string]$text) {
    $range = $cell.Range
    $range.MoveEnd(1, -1) | Out-Null
    $range.Text = $text
}

$word = $null
$doc = $null
$workDoc = $null

try {
    if (-not (Test-Path $Source)) {
        throw "Source template not found: $Source"
    }

    $workDir = Split-Path -Parent $Output
    if (-not (Test-Path $workDir)) {
        New-Item -ItemType Directory -Force -Path $workDir | Out-Null
    }

    # Work on a fresh copy so the official file is never modified.
    $workDoc = Join-Path $workDir ("locator-work-" + [guid]::NewGuid().ToString() + [System.IO.Path]::GetExtension($Source))
    Copy-Item -Force $Source $workDoc

    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0

    $doc = $word.Documents.Open($workDoc)

    # Keep official letterhead header + footer logos; force A4.
    foreach ($section in $doc.Sections) {
        $section.PageSetup.PaperSize = 7
        $section.PageSetup.PageWidth = $word.CentimetersToPoints(21.0)
        $section.PageSetup.PageHeight = $word.CentimetersToPoints(29.7)
        $section.PageSetup.Orientation = 0
    }

    # Official form shows ONE locator slip (like the school letterhead sample).
    # Remove the second blank slip if present.
    if ($doc.Tables.Count -ge 2) {
        $start = $doc.Tables.Item(1).Range.End
        $end = $doc.Content.End - 1
        if ($end -gt $start) {
            $doc.Range($start, $end).Delete() | Out-Null
        }
    }

    $checksBusiness = if ($Nature -eq 'Official Business') { [char]0x2611 } else { [char]0x2610 }
    $checksTime = if ($Nature -eq 'Official Time') { [char]0x2611 } else { [char]0x2610 }
    $checks = "$checksBusiness Official Business`t`t$checksTime Official Time"

    $t = $doc.Tables.Item(1)
    Set-ValueCell $t.Cell(1, 2) $Name
    Set-ValueCell $t.Cell(2, 2) $Position
    Set-ValueCell $t.Cell(3, 2) 'Isabela National High School'
    Set-ValueCell $t.Cell(4, 2) $Purpose
    Set-ValueCell $t.Cell(5, 2) $checks
    Set-ValueCell $t.Cell(6, 2) $DateTime
    Set-ValueCell $t.Cell(7, 2) $Destination

    # Replace the template's old school-head details without modifying the source file.
    foreach ($replacement in @(
        @{ Find = 'PRISCA ROMELA C. BUENAVENTURA'; Value = $SchoolHead },
        @{ Find = 'Head of Office'; Value = $SchoolHeadTitle }
    )) {
        $find = $doc.Content.Find
        $find.ClearFormatting()
        $find.Replacement.ClearFormatting()
        $null = $find.Execute(
            $replacement.Find,
            $false, $false, $false, $false, $false,
            $true, 1, $false,
            $replacement.Value,
            2
        )
    }

    if (Test-Path $Output) { Remove-Item -Force $Output }
    $wdFormatXMLDocument = 16
    $null = $doc.SaveAs([ref]$Output, [ref]$wdFormatXMLDocument)
    $doc.Close($false)
    $doc = $null

    Write-Output "OK"
}
catch {
    Write-Output ("ERR:" + $_.Exception.Message)
    exit 1
}
finally {
    if ($doc -ne $null) {
        try { $doc.Close($false) } catch {}
    }
    if ($word -ne $null) {
        try { $word.Quit() } catch {}
        [System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    if ($workDoc -and (Test-Path $workDoc)) {
        try { Remove-Item -Force $workDoc } catch {}
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
