param(
    [Parameter(Mandatory = $true)][string]$Source,
    [Parameter(Mandatory = $true)][string]$Output,
    [Parameter(Mandatory = $true)][string]$Name,
    [Parameter(Mandatory = $true)][string]$Position,
    [Parameter(Mandatory = $true)][string]$Station,
    [Parameter(Mandatory = $true)][string]$Purpose,
    [Parameter(Mandatory = $true)][string]$HostActivity,
    [Parameter(Mandatory = $true)][string]$InclusiveDates,
    [Parameter(Mandatory = $true)][string]$Destination,
    [Parameter(Mandatory = $true)][string]$FundSource,
    [Parameter(Mandatory = $false)][string]$EmployeeDate = '',
    [Parameter(Mandatory = $false)][string]$SchoolHeadDate = ''
)

$ErrorActionPreference = "Stop"

function Set-ValueCell($cell, [string]$text) {
    $range = $cell.Range
    $range.MoveEnd(1, -1) | Out-Null
    $range.Text = $text
}

function Unlock-Document($document) {
    # wdNoProtection = -1
    if ($document.ProtectionType -ne -1) {
        try {
            $document.Unprotect()
        } catch {
            try { $document.Unprotect("") } catch {}
        }
    }

    # Some DepEd templates lock ranges / content controls even after Unprotect.
    try {
        foreach ($story in @($document.Content)) {
            $null = $story
        }
        $document.TrackRevisions = $false
    } catch {}
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

    $workDoc = Join-Path $workDir ("travel-order-work-" + [guid]::NewGuid().ToString() + [System.IO.Path]::GetExtension($Source))
    Copy-Item -Force $Source $workDoc

    # Clear read-only / restricted attribute on the working copy.
    try {
        $item = Get-Item -LiteralPath $workDoc
        if ($item.IsReadOnly) { $item.IsReadOnly = $false }
        attrib -R $workDoc 2>$null
    } catch {}

    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0

    # Open writable (not read-only).
    $doc = $word.Documents.Open(
        $workDoc,   # FileName
        $false,     # ConfirmConversions
        $false,     # ReadOnly
        $false      # AddToRecentFiles
    )

    Unlock-Document $doc

    foreach ($section in $doc.Sections) {
        $section.PageSetup.PaperSize = 7 # A4
        $section.PageSetup.PageWidth = $word.CentimetersToPoints(21.0)
        $section.PageSetup.PageHeight = $word.CentimetersToPoints(29.7)
        $section.PageSetup.Orientation = 0
    }

    if ($doc.Tables.Count -lt 1) {
        throw "Travel Order template has no tables to fill."
    }

    $t = $doc.Tables.Item(1)

    function Safe-SetCell($table, [int]$row, [int]$col, [string]$text) {
        try {
            Set-ValueCell $table.Cell($row, $col) $text
        } catch {
            # Retry after forcing unprotect again.
            Unlock-Document $script:doc
            Set-ValueCell $table.Cell($row, $col) $text
        }
    }

    Safe-SetCell $t 1 2 $Name
    Safe-SetCell $t 2 2 $Position
    Safe-SetCell $t 3 2 $Station
    Safe-SetCell $t 4 2 $Purpose
    Safe-SetCell $t 5 2 $HostActivity
    Safe-SetCell $t 6 2 $InclusiveDates
    Safe-SetCell $t 7 2 $Destination
    Safe-SetCell $t 8 2 $FundSource

    # Put typed name above the requesting-employee signature label (keep attestation text).
    if ($t.Rows.Count -ge 9) {
        try {
            Unlock-Document $doc
            $sigCell = $t.Cell(9, 1)
            $sigRange = $sigCell.Range
            $sigRange.MoveEnd(1, -1) | Out-Null
            $printed = $Name.ToUpper()
            $dateText = $(if ($EmployeeDate -ne '') { $EmployeeDate } else { '_______________' })
            $existing = $sigRange.Text
            $existing = $existing -replace 'Name and Signature of Requesting Employee', 'Signature over Printed Name'
            $existing = $existing -replace '_{3,}', $dateText
            if ($existing -notmatch [regex]::Escape($printed)) {
                $existing = $existing -replace '(I hereby attest[^\r\n]*\r?\n\r?\n)', ("`$1        $printed`r")
            }
            $sigRange.Text = $existing
        } catch {
            # Signature block is optional if the cell is specially locked.
        }
    }

    # School head approval date (first long blank in row 10 if provided).
    if ($SchoolHeadDate -ne '' -and $t.Rows.Count -ge 10) {
        try {
            Unlock-Document $doc
            $headCell = $t.Cell(10, 1)
            $headRange = $headCell.Range
            $headRange.MoveEnd(1, -1) | Out-Null
            $headText = $headRange.Text
            if ($headText -match '_{3,}') {
                $headRange.Text = [regex]::Replace($headText, '_{3,}', $SchoolHeadDate, 1)
            }
        } catch {}
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
