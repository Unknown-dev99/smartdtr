#!/bin/bash
# SMARTDTR camera kiosk — run on the Pi desktop / LCD.
# Stays open. A crash or a closed window starts the camera again.
# EXIT on the screen is the way to stop it.
cd "$(dirname "$(readlink -f "$0")")"
export DISPLAY="${DISPLAY:-:0}"
unset SSH_CONNECTION SSH_CLIENT SSH_TTY

if command -v xset >/dev/null 2>&1; then
    xset s off >/dev/null 2>&1 || true
    xset -dpms >/dev/null 2>&1 || true
    xset s noblank >/dev/null 2>&1 || true
fi

if [ ! -x venv/bin/python ]; then
    echo "Missing venv. On the Pi run: python3 -m venv venv && source venv/bin/activate && pip install -r requirements.txt"
    exit 1
fi

LOG="kiosk.log"
while true; do
    echo "----- $(date) starting -----" >> "$LOG"
    venv/bin/python -u attendance_client.py >> "$LOG" 2>&1
    code=$?
    echo "----- $(date) exited ${code} -----" >> "$LOG"
    if [ "$code" -eq 0 ]; then
        exit 0
    fi
    sleep 2
done
