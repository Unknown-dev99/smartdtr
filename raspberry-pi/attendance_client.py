#!/usr/bin/env python3
"""
SMARTDTR Raspberry Pi Attendance Client
Face recognition + blink liveness → POST to Laravel IoT API
LCD shows match name, blink status, and scan result.
"""

import os
import pickle
import shutil
import socket
import subprocess
import sys
import threading
import time
import traceback
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import quote, urlparse

# Preview on the Pi LCD even when launched over SSH.
if not os.environ.get("DISPLAY"):
    os.environ["DISPLAY"] = ":0"

import cv2
import face_recognition
import numpy as np
import requests
from dotenv import load_dotenv

load_dotenv()

ENV_API_BASE = os.getenv("API_BASE", "").rstrip("/")
ENV_API_URL = os.getenv("API_URL", "").rstrip("/")
if not ENV_API_URL and ENV_API_BASE:
    ENV_API_URL = f"{ENV_API_BASE}/attendance/scan"
if not ENV_API_BASE and ENV_API_URL.endswith("/attendance/scan"):
    ENV_API_BASE = ENV_API_URL[: -len("/attendance/scan")]

API_BASE = ENV_API_BASE
API_URL = ENV_API_URL or (f"{API_BASE}/attendance/scan" if API_BASE else "")
API_DISCOVER = os.getenv("API_DISCOVER", "true").lower() == "true"
API_CACHE_FILE = Path(__file__).parent / ".api-endpoint"
_api_lock = threading.Lock()

KNOWN_FACES_DIR = os.getenv("KNOWN_FACES_DIR", "./known_faces")
FACE_RECOG_CONF_MIN = float(os.getenv("FACE_RECOG_CONF_MIN", "0.5"))
FACE_MATCH_DISTANCE_MAX = float(os.getenv("FACE_MATCH_DISTANCE_MAX", "0.45"))
FACE_MATCH_MARGIN = float(os.getenv("FACE_MATCH_MARGIN", "0.10"))
EAR_THRESHOLD = float(os.getenv("EAR_THRESHOLD", "0.24"))
REBUILD_ENCODINGS = os.getenv("REBUILD_ENCODINGS", "false").lower() == "true"
DATE_OVERRIDE = os.getenv("DATE_OVERRIDE", "")
LOCAL_COOLDOWN_SECONDS = int(os.getenv("LOCAL_COOLDOWN_SECONDS", "30"))
CAMERA_INDEX = int(os.getenv("CAMERA_INDEX", "0"))
CAMERA_SOURCE = os.getenv("CAMERA_SOURCE", "auto").strip().lower()
CAMERA_WIDTH = int(os.getenv("CAMERA_WIDTH", "1280"))
CAMERA_HEIGHT = int(os.getenv("CAMERA_HEIGHT", "720"))
RECOG_EVERY_N = max(1, int(os.getenv("RECOG_EVERY_N", "4")))
BLINK_EVERY_N = max(1, int(os.getenv("BLINK_EVERY_N", "1")))
BLINKS_REQUIRED = max(1, int(os.getenv("BLINKS_REQUIRED", "1")))
MATCH_HOLD_SECONDS = float(os.getenv("MATCH_HOLD_SECONDS", "2.0"))
FACE_SCALE = float(os.getenv("FACE_SCALE", "0.25"))
LOC_SCALE = max(1, int(round(1.0 / FACE_SCALE))) if FACE_SCALE > 0 else 4
LCD_FULLSCREEN = os.getenv("LCD_FULLSCREEN", "true").lower() == "true"
WINDOW_NAME = os.getenv("WINDOW_NAME", "SMARTDTR Attendance")
STREAM_PORT = int(os.getenv("STREAM_PORT", "8080"))
SYNC_FACES = os.getenv("SYNC_FACES", "true").lower() == "true"
FACE_SYNC_SECONDS = int(os.getenv("FACE_SYNC_SECONDS", "45"))

ENCODINGS_FILE = Path(__file__).parent / "encodings.pkl"


def set_api_endpoint(base):
    """Point every API call (scan, heartbeat, roster, faces) at this base URL."""
    global API_BASE, API_URL
    base = (base or "").rstrip("/")
    if not base:
        return
    with _api_lock:
        API_BASE = base
        API_URL = f"{base}/attendance/scan"
    try:
        API_CACHE_FILE.write_text(base + "\n", encoding="utf-8")
    except OSError:
        pass
    print(f"Using SMART-DTR at {base}")


def current_api_base():
    with _api_lock:
        return API_BASE


def current_api_url():
    with _api_lock:
        return API_URL


def cached_api_base():
    try:
        if API_CACHE_FILE.exists():
            value = API_CACHE_FILE.read_text(encoding="utf-8").strip()
            if value.startswith("http://") or value.startswith("https://"):
                return value.rstrip("/")
    except OSError:
        return ""
    return ""


def api_path_candidates():
    paths = []
    hint = ENV_API_BASE or current_api_base()
    if hint:
        parsed = urlparse(hint if "://" in hint else f"http://placeholder{hint}")
        if parsed.path and parsed.path != "/":
            paths.append(parsed.path.rstrip("/"))
    extra = os.getenv("API_PATHS", "/INHS%20SMART-DTR/api,/api")
    for raw in extra.split(","):
        path = raw.strip().rstrip("/")
        if path and path not in paths:
            paths.append(path)
    return paths or ["/INHS%20SMART-DTR/api"]


def probe_base(base, timeout=0.6):
    base = (base or "").rstrip("/")
    if not base:
        return False
    url = f"{base}/device/ping"
    try:
        resp = requests.get(url, timeout=timeout)
        if resp.status_code != 200:
            return False
        data = resp.json()
        return data.get("service") == "smartdtr" or data.get("ok") is True
    except (requests.RequestException, ValueError):
        return False


def local_ipv4s():
    ips = []
    seen = set()

    def add(ip):
        if not ip or ip in seen or ip.startswith("127.") or ip.startswith("169.254."):
            return
        seen.add(ip)
        ips.append(ip)

    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.connect(("8.8.8.8", 80))
        add(sock.getsockname()[0])
        sock.close()
    except OSError:
        pass

    try:
        for info in socket.getaddrinfo(socket.gethostname(), None, socket.AF_INET):
            add(info[4][0])
    except OSError:
        pass

    return ips


def subnet_hosts(ip):
    parts = ip.split(".")
    if len(parts) != 4:
        return []
    prefix = ".".join(parts[:3])
    own = parts[3]
    hosts = []
    for last in range(1, 255):
        if str(last) == own:
            continue
        hosts.append(f"{prefix}.{last}")
    return hosts


def discover_api_base():
    """Find the laptop SMART-DTR API on the current LAN. No internet required."""
    hints = []
    cached = cached_api_base()
    if cached:
        hints.append(cached)
    if ENV_API_BASE:
        hints.append(ENV_API_BASE.rstrip("/"))
    current = current_api_base()
    if current:
        hints.append(current.rstrip("/"))

    for base in hints:
        if probe_base(base, timeout=1.2):
            return base

    if not API_DISCOVER:
        return ""

    paths = api_path_candidates()
    local_ips = local_ipv4s()
    hosts = []
    for extra in ("192.168.137.1",):
        if extra not in hosts:
            hosts.append(extra)
    for ip in local_ips:
        for host in subnet_hosts(ip):
            if host not in hosts:
                hosts.append(host)

    print(f"Searching this Wi-Fi for SMART-DTR ({len(hosts)} addresses)...")
    found = {"base": ""}

    def try_host(host):
        if found["base"]:
            return ""
        for path in paths:
            base = f"http://{host}{path}"
            if probe_base(base, timeout=0.45):
                return base
        return ""

    workers = min(48, max(8, len(hosts) or 8))
    with ThreadPoolExecutor(max_workers=workers) as pool:
        futures = [pool.submit(try_host, host) for host in hosts]
        for future in as_completed(futures):
            base = future.result()
            if base:
                found["base"] = base
                break

    return found["base"]


def discover_and_apply():
    base = discover_api_base()
    if not base:
        return False
    set_api_endpoint(base)
    return True


def eye_aspect_ratio_points(points):
    if len(points) < 6:
        return 0.0
    p1, p2, p3, p4, p5, p6 = [np.array(p, dtype=float) for p in points[:6]]
    vertical1 = np.linalg.norm(p2 - p6)
    vertical2 = np.linalg.norm(p3 - p5)
    horizontal = np.linalg.norm(p1 - p4)
    if horizontal == 0:
        return 0.0
    return (vertical1 + vertical2) / (2.0 * horizontal)


def faces_fingerprint(faces_dir):
    items = []
    if not faces_dir.exists():
        return ""
    for path in sorted(faces_dir.rglob("*")):
        if not path.is_file() or path.suffix.lower() not in [".jpg", ".jpeg", ".png"]:
            continue
        relative = path.relative_to(faces_dir).as_posix()
        stat = path.stat()
        items.append(f"{relative}:{stat.st_size}:{int(stat.st_mtime)}")
    return "|".join(items)


def load_encodings(force_rebuild=False):
    faces_dir = Path(KNOWN_FACES_DIR)
    if not faces_dir.exists():
        print(f"Known faces directory not found: {faces_dir}")
        return [], []

    fingerprint = faces_fingerprint(faces_dir)

    if ENCODINGS_FILE.exists() and not REBUILD_ENCODINGS and not force_rebuild:
        with open(ENCODINGS_FILE, "rb") as f:
            data = pickle.load(f)
            if data.get("fingerprint") == fingerprint:
                print(f"Loaded {len(data['ids'])} encodings from cache")
                return data["ids"], data["encodings"]
            print("Face files changed — rebuilding encodings")

    ids = []
    encodings = []

    person_dirs = []
    if faces_dir.exists():
        for path in faces_dir.iterdir():
            if not path.is_dir() or not path.name.isdigit():
                continue
            person_dirs.append(path)

    for person_dir in sorted(person_dirs, key=lambda p: int(p.name)):
        person_id = int(person_dir.name)
        for img_path in person_dir.glob("*"):
            if img_path.suffix.lower() not in [".jpg", ".jpeg", ".png"]:
                continue
            image = face_recognition.load_image_file(str(img_path))
            face_encs = face_recognition.face_encodings(image)
            if face_encs:
                ids.append(person_id)
                encodings.append(face_encs[0])
                print(f"Encoded {img_path.name} for personnel {person_id}")
            else:
                print(f"No face found in {img_path}")

    if ids:
        with open(ENCODINGS_FILE, "wb") as f:
            pickle.dump({"ids": ids, "encodings": encodings, "fingerprint": fingerprint}, f)

    print(f"Built {len(ids)} encodings for {len(set(ids))} personnel")
    return ids, encodings


def sync_faces_from_server():
    """Download enrolled face photos from SMART-DTR so the Pi matches Admin uploads."""
    base = current_api_base()
    if not SYNC_FACES or not base:
        print("Face sync skipped (SYNC_FACES=false or no API_BASE)")
        return False

    print(f"Syncing faces from {base}/device/faces ...")
    try:
        resp = requests.get(f"{base}/device/faces", timeout=20)
        resp.raise_for_status()
        people = resp.json().get("data") or []
    except requests.RequestException as e:
        print(f"Face sync failed (using local photos if any): {e}")
        return False

    faces_dir = Path(KNOWN_FACES_DIR)
    faces_dir.mkdir(parents=True, exist_ok=True)
    changed = False

    for person in people:
        pid = int(person["id"])
        dest = faces_dir / str(pid)
        dest.mkdir(parents=True, exist_ok=True)
        wanted = set()

        for item in person.get("files") or []:
            name = Path(str(item.get("name", ""))).name
            if not name:
                continue
            wanted.add(name)
            dest_file = dest / name
            size = item.get("size")
            if dest_file.exists() and size and dest_file.stat().st_size == size:
                continue
            url = f"{base}/device/faces/{pid}?file={quote(name)}"
            try:
                img = requests.get(url, timeout=30)
                img.raise_for_status()
            except requests.RequestException as e:
                print(f"Download failed for personnel {pid} / {name}: {e}")
                continue
            dest_file.write_bytes(img.content)
            changed = True
            print(f"Downloaded {name} for personnel {pid} ({person.get('name')})")

        for local in dest.iterdir():
            if local.is_file() and local.name not in wanted:
                local.unlink()
                changed = True

    server_ids = {str(int(person["id"])) for person in people}
    for path in faces_dir.iterdir():
        if path.is_dir() and path.name.isdigit() and path.name not in server_ids:
            shutil.rmtree(path)
            changed = True
            print(f"Removed local faces for inactive personnel {path.name}")

    if changed:
        print(f"Face sync updated: {len(people)} personnel with photos")
    return changed


def detect_blink(rgb_small, face_locations):
    if not face_locations:
        return False, 0.0

    landmarks_list = face_recognition.face_landmarks(rgb_small, face_locations)
    if not landmarks_list:
        return False, 0.0

    marks = landmarks_list[0]
    left = marks.get("left_eye") or []
    right = marks.get("right_eye") or []
    if len(left) < 6 or len(right) < 6:
        return False, 0.0

    ear = (eye_aspect_ratio_points(left) + eye_aspect_ratio_points(right)) / 2.0
    return ear < EAR_THRESHOLD, ear


class BlinkGate:
    """Count a real blink: eyes open, then closed, then open again.

    The old code required two consecutive closed-eye samples and reset on
    any open frame, so a normal blink (100–400 ms) never punched.
    """

    def __init__(self):
        self.reset()

    def reset(self):
        self.seen_open = False
        self.seen_closed = False
        self.blinks = 0

    def update(self, eyes_closed):
        if eyes_closed:
            if self.seen_open:
                self.seen_closed = True
        else:
            if self.seen_closed:
                self.blinks += 1
                self.seen_closed = False
            self.seen_open = True
        return self.blinks


def fetch_roster():
    names = {}
    base = current_api_base()
    if not base:
        return names
    try:
        resp = requests.get(f"{base}/device/roster", timeout=8)
        if resp.status_code == 200:
            for row in resp.json().get("data", []):
                names[int(row["id"])] = row.get("name") or f"ID {row['id']}"
            print(f"Roster loaded: {len(names)} personnel")
    except requests.RequestException as e:
        print(f"Roster fetch failed (LCD will show IDs only): {e}")
    return names


def match_person(known_ids, known_encodings, face_encoding):
    if not known_encodings:
        return None, 0.0, "no-gallery"

    distances = face_recognition.face_distance(known_encodings, face_encoding)
    best_by_person = {}
    for pid, dist in zip(known_ids, distances):
        prev = best_by_person.get(pid)
        if prev is None or dist < prev:
            best_by_person[int(pid)] = float(dist)

    ranked = sorted(best_by_person.items(), key=lambda item: item[1])
    best_id, best_dist = ranked[0]
    second_dist = ranked[1][1] if len(ranked) > 1 else 1.0
    confidence = max(0.0, 1.0 - best_dist)

    if best_dist > FACE_MATCH_DISTANCE_MAX or confidence < FACE_RECOG_CONF_MIN:
        return None, confidence, "weak"
    if len(ranked) > 1 and (second_dist - best_dist) < FACE_MATCH_MARGIN:
        return None, confidence, "ambiguous"
    return best_id, confidence, "ok"


def post_scan(personnel_id, confidence):
    now = datetime.now()
    payload = {
        "personnel_id": personnel_id,
        "date": DATE_OVERRIDE or now.strftime("%Y-%m-%d"),
        "time": now.strftime("%H:%M:%S"),
        "blink_detected": True,
        "face_confidence": round(float(confidence), 4),
    }

    try:
        resp = requests.post(current_api_url(), json=payload, timeout=10)
        print(f"API response [{resp.status_code}]: {resp.text}")
        if resp.status_code == 200:
            body = resp.json()
            data = body.get("data") or {}
            return True, data
        return False, {"error": short_api_error(resp.text)}
    except requests.RequestException as e:
        print(f"API error: {e}")
        return False, {"error": short_api_error(e)}


def api_host():
    return urlparse(current_api_url() or current_api_base() or "").hostname or "server"


def short_api_error(err):
    text = str(err or "").replace("\n", " ")
    low = text.lower()
    host = api_host()
    if any(token in text for token in ("HTTPConnectionPool", "NewConnectionError", "Failed to establish", "Max retries")):
        return f"Cannot reach PC ({host})"
    if "timed out" in low or "timeout" in low:
        return f"Server timeout ({host})"
    if "name or service not known" in low or "nodename nor servname" in low:
        return f"Cannot reach PC ({host})"
    return (text[:36] or "API error")


def probe_server():
    base = current_api_base()
    if not base:
        return False
    return probe_base(base, timeout=2.0)


def heartbeat_loop(stop_event, link):
    last_discover = 0.0
    while not stop_event.is_set():
        base = current_api_base()
        if base:
            try:
                requests.post(
                    f"{base}/device/heartbeat",
                    json={"device_name": "raspberry-pi-kiosk"},
                    timeout=5,
                )
                link["ok"] = True
            except requests.RequestException:
                link["ok"] = False
        else:
            link["ok"] = False

        if not link.get("ok") and API_DISCOVER:
            now = time.time()
            if now - last_discover >= 15:
                last_discover = now
                print("Server offline — searching this Wi-Fi again...")
                if discover_and_apply():
                    link["ok"] = True

        if stop_event.wait(20):
            return


class LiveRoster:
    def __init__(self, ids, encodings, names):
        self.lock = threading.Lock()
        self.ids = ids
        self.encodings = encodings
        self.names = names

    def snapshot(self):
        with self.lock:
            return list(self.ids), list(self.encodings), dict(self.names)

    def apply(self, ids, encodings, names):
        with self.lock:
            self.ids = ids
            self.encodings = encodings
            self.names = names


def face_sync_loop(stop_event, roster):
    while not stop_event.wait(FACE_SYNC_SECONDS):
        try:
            changed = sync_faces_from_server()
            names = fetch_roster()
            if changed:
                ids, encodings = load_encodings(force_rebuild=True)
                roster.apply(ids, encodings, names or roster.names)
                print(f"Live update ready: {len(set(ids))} personnel")
            elif names:
                with roster.lock:
                    roster.names = names
        except Exception as e:
            print(f"Live face sync error: {e}")


def put_lcd_text(frame, text, x, y, scale, color, thickness, max_width=None):
    font = cv2.FONT_HERSHEY_SIMPLEX
    if max_width:
        while scale > 0.7:
            (tw, _), _ = cv2.getTextSize(str(text), font, scale, thickness)
            if tw <= max_width:
                break
            scale -= 0.08
    cv2.putText(frame, str(text), (x, y), font, scale, color, thickness, cv2.LINE_AA)


def draw_banner(frame, text, color, huge=False):
    h, w = frame.shape[:2]
    bar_h = 180 if huge else 100
    cv2.rectangle(frame, (0, h - bar_h), (w, h), color, -1)
    max_w = w - 36
    lines = str(text).split("\n")
    if huge and len(lines) >= 2:
        put_lcd_text(frame, lines[0], 18, h - 108, 1.45, (255, 255, 255), 4, max_w)
        put_lcd_text(frame, lines[1], 18, h - 28, 1.85, (255, 255, 255), 5, max_w)
    elif huge:
        put_lcd_text(frame, lines[0], 18, h - 48, 1.7, (255, 255, 255), 5, max_w)
    else:
        put_lcd_text(frame, lines[0], 16, h - 32, 1.2, (255, 255, 255), 3, max_w)


def draw_face_overlay(frame, overlay, names):
    """Reuse last match on skipped frames so the LCD still looks live."""
    locations = overlay.get("face_locations") or []
    matched_id = overlay.get("matched_id")
    confidence = overlay.get("confidence") or 0.0
    match_reason = overlay.get("match_reason") or "none"
    blinked = overlay.get("blinked")
    now_clock = datetime.now().strftime("%H:%M:%S")
    put_lcd_text(frame, now_clock, 12, 48, 1.35, (255, 255, 255), 3)

    if locations:
        top, right, bottom, left = locations[0]
        scale = int(overlay.get("loc_scale") or LOC_SCALE)
        cv2.rectangle(
            frame,
            (left * scale, top * scale),
            (right * scale, bottom * scale),
            (0, 180, 0) if matched_id else (0, 0, 220),
            3,
        )

    if matched_id:
        label = names.get(matched_id, f"ID {matched_id}")
        blinks = int(overlay.get("blinks") or 0)
        put_lcd_text(frame, f"{label}  {confidence:.0%}", 12, 102, 1.25, (0, 255, 0), 3)
        if blinks >= BLINKS_REQUIRED:
            blink_label, blink_color = "Blink OK — sending", (0, 255, 255)
        elif blinked:
            blink_label, blink_color = "Eyes closed — open them", (0, 255, 255)
        else:
            blink_label, blink_color = f"Blink once  ({blinks}/{BLINKS_REQUIRED})", (200, 200, 200)
        put_lcd_text(frame, blink_label, 12, 148, 1.1, blink_color, 3)
    elif locations:
        unknown_label = "Uncertain match" if match_reason == "ambiguous" else f"Unknown face  {confidence:.0%}"
        put_lcd_text(frame, unknown_label, 12, 102, 1.2, (0, 0, 255), 3)
    else:
        put_lcd_text(frame, "No face", 12, 102, 1.2, (180, 180, 180), 3)


def _load_picamera2():
    try:
        from picamera2 import Picamera2

        return Picamera2
    except ImportError:
        pass
    for extra in (
        "/usr/lib/python3/dist-packages",
        "/usr/lib/python3.13/dist-packages",
        "/usr/lib/python3.12/dist-packages",
        "/usr/lib/python3.11/dist-packages",
    ):
        if extra not in sys.path and os.path.isdir(extra):
            sys.path.append(extra)
    try:
        from picamera2 import Picamera2

        return Picamera2
    except ImportError:
        return None


def open_camera():
    use_picamera = CAMERA_SOURCE in ("auto", "picamera", "csi", "pi")
    use_usb = CAMERA_SOURCE in ("auto", "usb", "webcam", "v4l2")

    Picamera2 = _load_picamera2() if use_picamera else None
    if Picamera2 is not None:
        try:
            cam = Picamera2()
            cam.configure(cam.create_preview_configuration(
                main={"size": (CAMERA_WIDTH, CAMERA_HEIGHT), "format": "RGB888"}
            ))
            # The pygame window is the kiosk. A second DRM preview fights that
            # window and the desktop closes it after a few minutes.
            cam.start()
            time.sleep(0.4)
            test = cam.capture_array()
            if test is not None:
                print("Camera: Picamera2 (ribbon / CSI)")
                return "picamera2", cam
            cam.stop()
        except Exception as e:
            print(f"Picamera2 skipped: {e}")

    if not use_usb:
        return None, None

    tried = []
    for idx in (CAMERA_INDEX, 0, 1, 2):
        if idx in tried:
            continue
        tried.append(idx)
        cap = cv2.VideoCapture(idx, cv2.CAP_V4L2)
        if not cap.isOpened():
            cap.release()
            cap = cv2.VideoCapture(idx)
        if cap.isOpened():
            cap.set(cv2.CAP_PROP_FRAME_WIDTH, CAMERA_WIDTH)
            cap.set(cv2.CAP_PROP_FRAME_HEIGHT, CAMERA_HEIGHT)
            cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            ok, frame = cap.read()
            if ok and frame is not None:
                print(f"Camera: OpenCV / V4L2 index {idx} ({frame.shape[1]}x{frame.shape[0]})")
                return "cv2", cap
            cap.release()

    return None, None


def read_frame(kind, cam):
    try:
        if kind == "picamera2":
            rgb = cam.capture_array()
            if rgb is None:
                return False, None
            if rgb.ndim == 3 and rgb.shape[2] >= 3:
                bgr = cv2.cvtColor(rgb[:, :, :3], cv2.COLOR_RGB2BGR)
            else:
                return False, None
            return True, bgr
        ok, frame = cam.read()
        return bool(ok and frame is not None), frame
    except Exception as exc:
        print(f"Camera read error: {exc}")
        return False, None


def release_camera(kind, cam):
    if cam is None:
        return
    if kind == "picamera2":
        try:
            cam.stop()
        except Exception:
            pass
        return
    cam.release()


def keep_display_awake():
    """Stop the Pi desktop from blanking the kiosk window."""
    display = os.environ.get("DISPLAY", ":0")
    env = os.environ.copy()
    env["DISPLAY"] = display
    for args in (
        ["xset", "s", "off"],
        ["xset", "-dpms"],
        ["xset", "s", "noblank"],
    ):
        try:
            subprocess.run(args, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=3)
        except (OSError, subprocess.TimeoutExpired):
            pass


def init_lcd_preview():
    """Draw on the Pi LCD with pygame (avoids OpenCV Qt/xcb crash over SSH)."""
    try:
        import pygame
    except ImportError:
        print("pygame is not installed. On the Pi run:")
        print("  sudo apt install -y python3-pygame")
        print("  pip install pygame")
        return None

    keep_display_awake()
    os.environ["SDL_VIDEO_ALLOW_SCREENSAVER"] = "0"
    last_err = None
    drivers = ["x11", "wayland", "kmsdrm", "fbcon"]
    for driver in drivers:
        os.environ["SDL_VIDEODRIVER"] = driver
        try:
            pygame.display.quit()
        except Exception:
            pass
        try:
            pygame.display.init()
            pygame.font.init()
            flags = pygame.FULLSCREEN if LCD_FULLSCREEN else 0
            screen = pygame.display.set_mode((0, 0), flags) if LCD_FULLSCREEN else pygame.display.set_mode((800, 480))
            pygame.display.set_caption(WINDOW_NAME)
            pygame.mouse.set_visible(True)
            print(f"LCD preview: SDL {driver} {screen.get_size()}")
            print("Look at the Pi screen — tap EXIT on the LCD to close the kiosk.")
            return pygame, screen
        except Exception as e:
            last_err = e
            print(f"SDL {driver} failed: {e}")

    print(f"Could not open LCD preview: {last_err}")
    print("On the Pi run:  sudo raspi-config")
    print("  Advanced Options → Wayland → X11, then reboot.")
    return None


def lcd_exit_rect(pygame, screen):
    w, h = screen.get_size()
    bw = max(132, int(w * 0.16))
    bh = max(56, int(h * 0.10))
    margin = max(14, int(min(w, h) * 0.02))
    return pygame.Rect(w - bw - margin, margin, bw, bh)


def event_screen_pos(pygame, event, screen):
    if event.type == pygame.MOUSEBUTTONDOWN:
        return event.pos
    finger_down = getattr(pygame, "FINGERDOWN", None)
    if finger_down is not None and event.type == finger_down:
        w, h = screen.get_size()
        return int(event.x * w), int(event.y * h)
    return None


def draw_lcd_exit_button(pygame, screen):
    rect = lcd_exit_rect(pygame, screen)
    fill = (176, 36, 36)
    border = (255, 255, 255)
    try:
        pygame.draw.rect(screen, fill, rect, border_radius=14)
        pygame.draw.rect(screen, border, rect, 3, border_radius=14)
    except TypeError:
        pygame.draw.rect(screen, fill, rect)
        pygame.draw.rect(screen, border, rect, 3)
    font_size = max(26, int(rect.height * 0.48))
    font = pygame.font.Font(None, font_size + 8)
    for name in ("DejaVuSans", "FreeSans", "Piboto", "Liberation Sans"):
        try:
            font = pygame.font.SysFont(name, font_size, bold=True)
            break
        except Exception:
            continue
    label = font.render("EXIT", True, (255, 255, 255))
    screen.blit(label, label.get_rect(center=rect.center))
    return rect


def show_on_lcd(pygame, screen, bgr):
    """Return ok, exit, or lost. A lost window is reopened; only EXIT closes the kiosk."""
    try:
        rgb = cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB)
        size = screen.get_size()
        rgb = cv2.resize(rgb, size)
        rgb = np.ascontiguousarray(rgb)
        # convert() copies the pixels. frombuffer() alone can free the bytes and
        # crash the window after it has been open for a while.
        surface = pygame.image.frombuffer(rgb, size, "RGB").convert()
        screen.blit(surface, (0, 0))
        exit_rect = draw_lcd_exit_button(pygame, screen)
        pygame.display.flip()
        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                print("Display closed the window. Opening it again.")
                return "lost"
            if event.type == pygame.KEYDOWN and event.key in (pygame.K_q, pygame.K_ESCAPE):
                return "exit"
            pos = event_screen_pos(pygame, event, screen)
            if pos is not None and exit_rect.collidepoint(pos):
                print("LCD EXIT tapped — closing kiosk.")
                return "exit"
        return "ok"
    except Exception as exc:
        print(f"LCD draw error: {exc}")
        return "lost"


class JpegBroker:
    def __init__(self):
        self._jpg = None
        self._cond = threading.Condition()
        self._viewers = 0

    def add_viewer(self):
        with self._cond:
            self._viewers += 1

    def remove_viewer(self):
        with self._cond:
            self._viewers = max(0, self._viewers - 1)

    def update(self, bgr):
        with self._cond:
            if self._viewers <= 0:
                return
        ok, buf = cv2.imencode(".jpg", bgr, [int(cv2.IMWRITE_JPEG_QUALITY), 60])
        if not ok:
            return
        with self._cond:
            if self._viewers <= 0:
                return
            self._jpg = buf.tobytes()
            self._cond.notify_all()

    def wait_jpg(self):
        with self._cond:
            self._cond.wait(timeout=1.0)
            return self._jpg


class StreamHandler(BaseHTTPRequestHandler):
    broker = None

    def log_message(self, format, *args):
        return

    def do_GET(self):
        if self.path in ("/", "/index.html"):
            html = (
                b"<!doctype html><title>SMARTDTR Camera</title>"
                b"<body style='margin:0;background:#111;color:#fff;font-family:sans-serif;text-align:center'>"
                b"<h2>SMARTDTR live camera</h2>"
                b"<img src='/stream' style='max-width:100%;height:auto'>"
                b"</body>"
            )
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Content-Length", str(len(html)))
            self.end_headers()
            self.wfile.write(html)
            return
        if self.path.startswith("/stream"):
            self.send_response(200)
            self.send_header("Cache-Control", "no-cache, private")
            self.send_header("Content-Type", "multipart/x-mixed-replace; boundary=frame")
            self.end_headers()
            self.broker.add_viewer()
            try:
                while True:
                    jpg = self.broker.wait_jpg()
                    if not jpg:
                        continue
                    self.wfile.write(
                        b"--frame\r\nContent-Type: image/jpeg\r\nContent-Length: "
                        + str(len(jpg)).encode()
                        + b"\r\n\r\n"
                        + jpg
                        + b"\r\n"
                    )
                    self.wfile.flush()
            except (BrokenPipeError, ConnectionResetError, ConnectionAbortedError, TimeoutError):
                return
            finally:
                self.broker.remove_viewer()
        self.send_error(404)


def start_mjpeg_server(broker):
    StreamHandler.broker = broker
    httpd = ThreadingHTTPServer(("0.0.0.0", STREAM_PORT), StreamHandler)
    thread = threading.Thread(target=httpd.serve_forever, daemon=True)
    thread.start()
    print("")
    print("Open this on your LAPTOP browser (not SSH):")
    for ip in local_ipv4s() or ["10.152.53.126"]:
        print(f"  http://{ip}:{STREAM_PORT}")
    print("")
    return httpd


def main():
    print("SMARTDTR Attendance Client starting...")
    print(f"Faces dir: {KNOWN_FACES_DIR}")
    if discover_and_apply():
        print(f"API scan URL: {current_api_url()}")
    else:
        print("WARNING: cannot find SMART-DTR on this Wi-Fi.")
        print("Connect the Pi to the same Wi-Fi as the laptop, start XAMPP Apache,")
        print("and allow port 80 in Windows Firewall. Guest Wi-Fi with AP isolation will not work.")
    link = {"ok": probe_server()}
    if link["ok"]:
        print(f"SMART-DTR server reachable at {api_host()}")

    synced = sync_faces_from_server()
    known_ids, known_encodings = load_encodings(force_rebuild=synced)
    if not known_encodings:
        print("No faces yet. Camera stays on and will pick up new personnel automatically.")

    names = fetch_roster()
    roster = LiveRoster(known_ids, known_encodings, names)
    stop_hb = threading.Event()
    threading.Thread(target=heartbeat_loop, args=(stop_hb, link), daemon=True).start()
    threading.Thread(target=face_sync_loop, args=(stop_hb, roster), daemon=True).start()
    print(f"Auto face update every {FACE_SYNC_SECONDS}s")

    kind, cam = open_camera()
    if cam is None:
        print("Cannot open camera.")
        print("If this is a Raspberry Pi Camera Module: sudo apt install -y python3-picamera2")
        print("If this is a USB webcam: try CAMERA_INDEX=1 in .env")
        stop_hb.set()
        return 1

    broker = JpegBroker()
    try:
        httpd = start_mjpeg_server(broker)
    except OSError as e:
        print(f"Live stream port {STREAM_PORT} failed: {e}")
        httpd = None

    pygame = screen = None
    if os.environ.get("SSH_CONNECTION") or os.environ.get("SSH_CLIENT"):
        print("SSH session: skipping Pi LCD window. Use the laptop browser URL above.")
    else:
        preview = init_lcd_preview()
        if preview is not None:
            pygame, screen = preview
        else:
            print("Pi LCD window unavailable — use the laptop browser URL above.")

    last_scan = {}
    blink_gate = BlinkGate()
    pending_match = None
    match_until = 0.0
    banner = ("Look at camera — blink to clock in/out", (40, 40, 40), False)
    banner_until = 0.0
    frame_i = 0
    overlay = {
        "face_locations": [],
        "matched_id": None,
        "confidence": 0.0,
        "match_reason": "none",
        "blinked": False,
        "blinks": 0,
        "loc_scale": LOC_SCALE,
    }
    print(
        f"Kiosk: {CAMERA_WIDTH}x{CAMERA_HEIGHT}, match every {RECOG_EVERY_N} frame(s), "
        f"blink every {BLINK_EVERY_N}, EAR<{EAR_THRESHOLD}"
    )

    frame_misses = 0
    next_awake = time.time() + 60
    while True:
        if time.time() >= next_awake:
            keep_display_awake()
            next_awake = time.time() + 60
        if pygame is not None:
            try:
                pygame.event.pump()
            except Exception:
                pygame, screen = None, None

        ret, frame = read_frame(kind, cam) if cam is not None else (False, None)
        if not ret:
            frame_misses += 1
            if frame_misses >= 15:
                print("Camera stalled. Reopening it.")
                release_camera(kind, cam)
                kind, cam = open_camera()
                frame_misses = 0
                if cam is None:
                    print("Camera still closed. Waiting and trying again.")
                    time.sleep(2)
            else:
                time.sleep(0.05)
            continue

        frame_misses = 0
        try:
            known_ids, known_encodings, names = roster.snapshot()
            do_blink = (frame_i % BLINK_EVERY_N) == 0
            do_recog = (frame_i % RECOG_EVERY_N) == 0
            frame_i += 1

            if do_blink or do_recog:
                small = cv2.resize(frame, (0, 0), fx=FACE_SCALE, fy=FACE_SCALE)
                rgb_small = cv2.cvtColor(small, cv2.COLOR_BGR2RGB)
                matched_id = overlay.get("matched_id")
                confidence = overlay.get("confidence") or 0.0
                match_reason = overlay.get("match_reason") or "none"
                face_locations = overlay.get("face_locations") or []

                if do_recog:
                    face_locations = face_recognition.face_locations(rgb_small)
                    matched_id = None
                    confidence = 0.0
                    match_reason = "none"
                    if face_locations and known_encodings:
                        face_encs = face_recognition.face_encodings(rgb_small, face_locations)
                        if face_encs:
                            matched_id, confidence, match_reason = match_person(
                                known_ids, known_encodings, face_encs[0]
                            )
                    if matched_id:
                        pending_match = (matched_id, confidence)
                        match_until = time.time() + MATCH_HOLD_SECONDS
                    elif time.time() >= match_until:
                        pending_match = None
                        matched_id = None

                if pending_match and time.time() < match_until and face_locations:
                    matched_id, confidence = pending_match
                elif not face_locations:
                    matched_id = None
                    pending_match = None
                    blink_gate.reset()

                blinked, ear = detect_blink(rgb_small, face_locations) if face_locations else (False, 0.0)

                if matched_id:
                    blinks = blink_gate.update(blinked)
                    if blinks >= BLINKS_REQUIRED:
                        pid, conf = pending_match if pending_match else (matched_id, confidence)
                        now_ts = time.time()
                        if now_ts - last_scan.get(pid, 0) >= LOCAL_COOLDOWN_SECONDS:
                            print(f"Blink accepted for personnel {pid} (EAR {ear:.3f})")
                            ok, data = post_scan(pid, conf)
                            name = data.get("name") or names.get(pid, f"ID {pid}")
                            if ok:
                                last_scan[pid] = now_ts
                                status = data.get("status") or "Recorded"
                                time_in = data.get("time_in") or "—"
                                time_out = data.get("time_out") or "—"
                                already = bool(data.get("already_attended")) or str(status).lower().startswith("already attended")
                                if already:
                                    banner = (f"{name}\nALREADY ATTENDED", (0, 110, 200), True)
                                elif time_in == time_out:
                                    banner = (f"{name}\nTIME IN  {time_in}", (0, 140, 0), True)
                                else:
                                    banner = (f"{name}\nIN {time_in}    OUT {time_out}", (0, 140, 0), True)
                            else:
                                banner = (f"Not saved:\n{data.get('error', 'API error')}", (0, 0, 180), True)
                            banner_until = time.time() + 4
                        blink_gate.reset()
                        pending_match = None
                        match_until = 0.0
                        matched_id = None
                else:
                    blink_gate.reset()

                overlay = {
                    "face_locations": face_locations,
                    "matched_id": matched_id,
                    "confidence": confidence,
                    "match_reason": match_reason,
                    "blinked": blinked,
                    "blinks": blink_gate.blinks,
                    "loc_scale": LOC_SCALE,
                }

            draw_face_overlay(frame, overlay, names)

            if time.time() < banner_until:
                draw_banner(frame, banner[0], banner[1], huge=banner[2] if len(banner) > 2 else False)
            elif link.get("ok") is False:
                draw_banner(frame, f"SERVER OFFLINE\n{api_host()}", (0, 0, 180), huge=True)
            else:
                draw_banner(frame, "SMARTDTR  |  Face the camera and blink", (50, 50, 50))

            broker.update(frame)
            if pygame is not None and screen is not None:
                shown = show_on_lcd(pygame, screen, frame)
                if shown == "exit":
                    break
                if shown == "lost":
                    preview = init_lcd_preview()
                    if preview is not None:
                        pygame, screen = preview
                    else:
                        pygame, screen = None, None
        except Exception:
            print("Kiosk frame error (staying open):")
            traceback.print_exc()
            time.sleep(0.2)

    stop_hb.set()
    release_camera(kind, cam)
    if httpd is not None:
        httpd.shutdown()
    if pygame is not None:
        try:
            pygame.quit()
        except Exception:
            pass
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main() or 0)
    except Exception:
        traceback.print_exc()
        sys.exit(1)
