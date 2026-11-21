#!/usr/bin/env python3
"""Validate enrollment photos using a CNN detector and strict identity checks."""

import json
import os
import sys
from pathlib import Path
from typing import List, Optional, Sequence, Tuple

import cv2
import face_recognition
import numpy as np


def fail(message: str) -> None:
    print(json.dumps({"ok": False, "message": message}))
    raise SystemExit(1)


def known_names() -> dict:
    raw = os.getenv("FACE_KNOWN_NAMES_JSON") or "{}"
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        return {}
    return {str(key): str(value) for key, value in data.items()}


def detect_faces(rgb, prefer: Optional[str] = None) -> Tuple[list, str]:
    requested = (prefer or os.getenv("FACE_ENROLLMENT_DETECTOR") or "cnn").strip().lower() or "cnn"
    models = [requested]
    if "hog" not in models:
        models.append("hog")

    for model in models:
        upsample = 0 if model == "cnn" else 1
        try:
            locations = face_recognition.face_locations(
                rgb,
                number_of_times_to_upsample=upsample,
                model=model,
            )
        except Exception:
            continue
        if locations:
            return locations, model
    return [], requested


def encode_faces(rgb, locations, jitters: int) -> list:
    return face_recognition.face_encodings(
        rgb,
        locations,
        num_jitters=max(1, jitters),
        model="large",
    )


def identity_conflict(
    new_encodings: Sequence[np.ndarray],
    gallery_encodings: Sequence[np.ndarray],
    match_tolerance: float,
    min_votes: int = 2,
) -> bool:
    """True only when most new samples clearly match the gallery person."""
    if not new_encodings or not gallery_encodings:
        return False

    gallery = np.vstack(gallery_encodings)
    bests = []
    votes = 0
    for new_enc in new_encodings:
        best = float(np.min(np.linalg.norm(gallery - new_enc, axis=1)))
        bests.append(best)
        if best <= match_tolerance:
            votes += 1

    return votes >= min_votes and float(np.median(bests)) <= match_tolerance


def encodings_for_person(person_dir: Path) -> List[np.ndarray]:
    encodings: List[np.ndarray] = []
    for img_path in sorted(person_dir.iterdir()):
        if img_path.suffix.lower() not in {".jpg", ".jpeg", ".png"}:
            continue
        try:
            rgb = face_recognition.load_image_file(str(img_path))
        except Exception:
            continue
        locations, _ = detect_faces(rgb, prefer="hog")
        if len(locations) != 1:
            continue
        found = encode_faces(rgb, locations, jitters=1)
        if found:
            encodings.append(found[0])
    return encodings


def conflict_with_existing(new_encodings: List[np.ndarray], match_tolerance: float) -> Optional[str]:
    known_dir = Path(os.getenv("FACE_KNOWN_DIR") or "")
    exclude_id = str(os.getenv("FACE_EXCLUDE_ID") or "")
    enrolling = os.getenv("FACE_ENROLLING_NAME") or "this employee"
    names = known_names()
    min_votes = max(2, int(os.getenv("FACE_ENROLLMENT_CONFLICT_VOTES", "2")))

    if not known_dir.is_dir():
        return None

    for person_dir in known_dir.iterdir():
        if not person_dir.is_dir() or not person_dir.name.isdigit():
            continue
        if person_dir.name == exclude_id:
            continue

        existing = encodings_for_person(person_dir)
        if not identity_conflict(new_encodings, existing, match_tolerance, min_votes=min_votes):
            continue

        other = names.get(person_dir.name, f"personnel #{person_dir.name}")
        return (
            f"This face is already enrolled as {other}. "
            f"You are saving samples for {enrolling}. "
            f"Put {enrolling} in front of the camera — not someone who is already registered."
        )
    return None


def main() -> None:
    paths = [Path(value) for value in sys.argv[1:]]
    if len(paths) != 3:
        fail("Exactly three face captures are required.")

    encodings = []
    for index, path in enumerate(paths, start=1):
        if not path.is_file():
            fail(f"Capture {index} could not be read.")

        bgr = cv2.imread(str(path))
        if bgr is None:
            fail(f"Capture {index} is not a valid image.")

        gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
        brightness = float(np.mean(gray))
        sharpness = float(cv2.Laplacian(gray, cv2.CV_64F).var())
        if brightness < 45:
            fail(f"Capture {index} is too dark. Improve the lighting and try again.")
        if brightness > 225:
            fail(f"Capture {index} is overexposed. Reduce direct light and try again.")
        if sharpness < 35:
            fail(f"Capture {index} is blurry. Hold still and try again.")

        rgb = cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB)
        locations, _ = detect_faces(rgb)
        if len(locations) == 0:
            fail(f"No recognizable face was found in capture {index}.")
        if len(locations) > 1:
            fail(f"More than one face was found in capture {index}.")

        top, right, bottom, left = locations[0]
        face_area = max(0, right - left) * max(0, bottom - top)
        image_area = bgr.shape[0] * bgr.shape[1]
        if image_area == 0 or face_area / image_area < 0.08:
            fail(f"The face is too far away in capture {index}. Move closer and try again.")

        found = encode_faces(rgb, locations, jitters=3)
        if len(found) != 1:
            fail(f"Capture {index} could not produce a reliable face encoding.")
        encodings.append(found[0])

    distances = [
        float(np.linalg.norm(encodings[i] - encodings[j]))
        for i in range(3)
        for j in range(i + 1, 3)
    ]
    tolerance = float(os.getenv("FACE_ENROLLMENT_TOLERANCE", "0.55"))
    maximum = max(distances)
    if maximum > tolerance:
        fail("The three captures do not appear to show the same person. Please retake all photos.")

    conflict_tolerance = float(os.getenv("FACE_ENROLLMENT_CONFLICT_TOLERANCE", "0.38"))
    conflict = conflict_with_existing(encodings, conflict_tolerance)
    if conflict:
        fail(conflict)

    print(json.dumps({
        "ok": True,
        "message": "Three compatible face encodings verified.",
        "encodings": len(encodings),
        "max_distance": round(maximum, 4),
    }))


if __name__ == "__main__":
    main()
