#!/bin/bash
# Install a desktop icon + boot autostart for the SMARTDTR camera kiosk.
set -e
APP="$HOME/smartdtr-pi"
ICON_NAME="SMARTDTR Camera"

if [ ! -f "$APP/attendance_client.py" ]; then
    echo "Client not found at $APP"
    echo "Copy the raspberry-pi folder to $APP first."
    exit 1
fi

chmod +x "$APP/start-kiosk.sh" "$APP/install-kiosk.sh" 2>/dev/null || true

mkdir -p "$HOME/.config/autostart" "$HOME/Desktop"

DESKTOP_FILE="$HOME/.config/autostart/smartdtr-kiosk.desktop"
cat > "$DESKTOP_FILE" <<EOF
[Desktop Entry]
Type=Application
Name=$ICON_NAME
Comment=SMARTDTR face attendance kiosk
Exec=$APP/start-kiosk.sh
Path=$APP
Terminal=false
X-GNOME-Autostart-enabled=true
StartupNotify=false
EOF

cp "$DESKTOP_FILE" "$HOME/Desktop/smartdtr-kiosk.desktop"
chmod +x "$HOME/Desktop/smartdtr-kiosk.desktop"

# Allow launching the Desktop icon without a "Execute?" prompt on Raspberry Pi OS
if command -v gio >/dev/null 2>&1; then
    gio set "$HOME/Desktop/smartdtr-kiosk.desktop" metadata::trusted true 2>/dev/null || true
fi

echo ""
echo "Installed."
echo "  Desktop icon: $ICON_NAME"
echo "  Autostart:    $DESKTOP_FILE"
echo ""
echo "So it opens when you power the Pi:"
echo "  sudo raspi-config"
echo "  System Options → Boot / Auto Login → Desktop Autologin"
echo ""
echo "Double-click the desktop icon now, or reboot."
