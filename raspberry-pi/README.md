# SMARTDTR Raspberry Pi IoT kiosk

The Pi camera recognizes enrolled personnel, requires a **blink**, then POSTs the scan to SMARTDTR. Time-in / time-out is stored on the server. The LCD shows the name and result.

```
Pi camera  →  face match + blink  →  POST /api/attendance/scan  →  attendance + attendance_logs
Pi          →  POST /api/device/heartbeat every 20s            →  Admin dashboard “Online”
```

## 1) PC (XAMPP) — make the API reachable on Wi‑Fi

Pi and this PC must be on the **same Wi‑Fi**. The Pi **auto-finds** the laptop IP, so you do **not** edit `.env` when you join another network.

1. Start Apache + MySQL in XAMPP.
2. Allow Apache through Windows Firewall (port **80**, Private network).
3. From a browser on the PC, confirm the site opens:
   - `http://localhost/INHS%20SMART-DTR/`
4. Enroll faces: **Admin → Personnel** → upload 3–10 photos per person.  
   Files land in `storage/app/known_faces/{personnel_id}/`.

The Pi calls `GET /api/device/ping` (`service: smartdtr`) while scanning the LAN, then saves the working address in `.api-endpoint`. If the laptop IP changes, it searches again and the SERVER OFFLINE banner clears.

**Guest / “AP isolation” / client-isolation Wi‑Fi will not work.** The router must allow the Pi to talk to the laptop. Use the same SSID, not a guest network.

Internet is not required. Same local Wi‑Fi is enough.

Test the scan API from the PC (PowerShell):

```powershell
curl.exe -X POST "http://192.168.1.10/INHS%20SMART-DTR/api/attendance/scan" `
  -H "Content-Type: application/json" `
  -d "{\"personnel_id\":1,\"blink_detected\":true,\"face_confidence\":0.9}"
```

A valid person id should return `"Scan recorded"`.

## 2) Copy faces to the Pi

On the PC (PowerShell), replace `pi@raspberrypi` if needed:

```powershell
scp -r "C:\xampp\htdocs\INHS SMART-DTR\storage\app\known_faces" pi@raspberrypi:~/smartdtr-pi/known_faces
```

Or from the Pi:

```bash
scp -r USER@192.168.1.10:"/c/xampp/htdocs/INHS SMART-DTR/storage/app/known_faces" ~/smartdtr-pi/known_faces
```

Folder names **must be numeric personnel IDs** (`1`, `2`, `3`…).

## 3) Install the client on the Pi

```bash
sudo apt update
sudo apt install -y python3-venv python3-pip cmake g++ libopenblas-dev liblapack-dev libjpeg-dev libpng-dev
mkdir -p ~/smartdtr-pi
# copy this raspberry-pi folder onto the Pi, then:
cd ~/smartdtr-pi
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
cp .env.example .env
```

Keep `API_DISCOVER=true`. You do **not** need to type the laptop IP. Optional `API_BASE` is only a first hint.

```env
API_DISCOVER=true
KNOWN_FACES_DIR=./known_faces
REBUILD_ENCODINGS=true
CAMERA_INDEX=0
CAMERA_WIDTH=1280
CAMERA_HEIGHT=720
RECOG_EVERY_N=4
BLINK_EVERY_N=1
BLINKS_REQUIRED=1
LCD_FULLSCREEN=true
```

First run with `REBUILD_ENCODINGS=true` builds `encodings.pkl`. After that set it to `false` unless you added new photos.

## 4) Run the kiosk

Desktop session on the LCD (not SSH-only, or use `DISPLAY=:0`):

```bash
cd ~/smartdtr-pi
source venv/bin/activate
python attendance_client.py
```

- Look at the camera → green box + name  
- **Blink** → scan is sent  
- LCD banner: name, status, time in  
- Tap **EXIT** on the LCD (top-right) to close the kiosk, or press `q` / Esc  

Admin dashboard should show the Pi **Online** (heartbeat) and **Scans today** after a successful punch.

## 5) Autostart (optional)

`~/.config/autostart/smartdtr.desktop`:

```ini
[Desktop Entry]
Type=Application
Name=SMARTDTR Kiosk
Exec=/home/pi/smartdtr-pi/venv/bin/python /home/pi/smartdtr-pi/attendance_client.py
```

## Troubleshooting

| Symptom | Fix |
|--------|-----|
| `SERVER OFFLINE` then it finds the PC | Normal after a Wi‑Fi change — wait for “Using SMART-DTR at …” |
| Stays `SERVER OFFLINE` | Same Wi‑Fi? Apache started? Firewall port 80? Not a guest/isolated network? |
| `API error: Connection refused` | Apache not running, or Windows Firewall blocking port 80 |
| `404` on `/api/attendance/scan` | Use `/INHS%20SMART-DTR/api/...` not `/INHS SMART-DTR/public/api` if using the project root `index.php` |
| `No face encodings` | Copy `known_faces` folders named by personnel id; rebuild encodings |
| `Personnel not found` | Folder id does not exist in the database |
| `Blink not detected` / many blinks but no punch | Copy the new `attendance_client.py`. A real blink is **open → close → open**. Raise `EAR_THRESHOLD` to `0.26` if still missed; lower it to `0.22` if photos punch. Stand closer, face the camera, then blink once. |
| Camera error | Try `CAMERA_INDEX=1` |
| LCD blank | Run from the Pi desktop, `LCD_FULLSCREEN=true`, HDMI/DSI connected |

Do not change the JSON body of `/api/attendance/scan` (`personnel_id`, `blink_detected`, `face_confidence`).
