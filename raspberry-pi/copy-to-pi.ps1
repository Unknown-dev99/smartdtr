# Copy kiosk files to the Raspberry Pi and install the desktop / autostart app.
# Run from Windows PowerShell. You will be asked for the Pi password several times.

$ErrorActionPreference = "Stop"

$piUser = "rogeliojr"
$piHost = "raspberrypi.local"
$project = "C:\xampp\htdocs\INHS SMART-DTR"
$piDir = "C:\xampp\htdocs\INHS SMART-DTR\raspberry-pi"
$facesRoot = Join-Path $project "storage\app\known_faces"

$files = @(
    (Join-Path $piDir "attendance_client.py"),
    (Join-Path $piDir ".env"),
    (Join-Path $piDir "start-kiosk.sh"),
    (Join-Path $piDir "install-kiosk.sh")
)

foreach ($f in $files) {
    if (-not (Test-Path -LiteralPath $f)) {
        Write-Host "Missing file: $f"
        exit 1
    }
}

Write-Host "1/5  Creating folders on the Pi..."
ssh -6 -l $piUser $piHost "mkdir -p ~/smartdtr-pi/known_faces"

if (Test-Path -LiteralPath $facesRoot) {
    $personDirs = Get-ChildItem -LiteralPath $facesRoot -Directory | Where-Object { $_.Name -match '^\d+$' }
    Write-Host "2/5  Copying face photos for $($personDirs.Count) personnel..."
    foreach ($dir in $personDirs) {
        $id = $dir.Name
        Write-Host "     personnel $id"
        ssh -6 -l $piUser $piHost "mkdir -p ~/smartdtr-pi/known_faces/$id"
        scp.exe -6 -r -o "User=$piUser" $dir.FullName "${piHost}:~/smartdtr-pi/known_faces/$id"
    }
} else {
    Write-Host "2/5  No local face folder; Pi will download faces from the server."
}

Write-Host "3/5  Copying kiosk software..."
foreach ($f in $files) {
    $name = Split-Path $f -Leaf
    scp.exe -6 -o "User=$piUser" $f "${piHost}:~/smartdtr-pi/$name"
}

Write-Host "4/5  Installing desktop icon + autostart on the Pi..."
ssh -6 -l $piUser $piHost "chmod +x ~/smartdtr-pi/start-kiosk.sh ~/smartdtr-pi/install-kiosk.sh && ~/smartdtr-pi/install-kiosk.sh"

Write-Host "5/5  Done."
Write-Host ""
Write-Host "On the Pi LCD, double-click:  SMARTDTR Camera"
Write-Host "Or reboot the Pi after enabling Desktop Autologin in raspi-config."
