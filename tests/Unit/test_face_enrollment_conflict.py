#!/usr/bin/env python3
"""Conflict votes must require a clear match, not one similar frame."""

import importlib.util
from pathlib import Path

import numpy as np

SCRIPT = Path(__file__).resolve().parents[2] / "scripts" / "validate_face_enrollment.py"
spec = importlib.util.spec_from_file_location("validate_face_enrollment", SCRIPT)
mod = importlib.util.module_from_spec(spec)
assert spec.loader is not None
spec.loader.exec_module(mod)


def test_different_people_do_not_conflict_on_one_close_sample():
    gallery = [np.zeros(128, dtype=float)]
    new_encodings = [
        np.zeros(128, dtype=float),
        np.ones(128, dtype=float) * 0.08,
        np.ones(128, dtype=float) * 0.08,
    ]
    # First sample is identical; the other two are far (distance ~ 0.90).
    assert not mod.identity_conflict(new_encodings, gallery, 0.38, min_votes=2)


def test_same_person_conflicts_when_most_samples_match():
    gallery = [np.zeros(128, dtype=float), np.zeros(128, dtype=float)]
    new_encodings = [
        np.zeros(128, dtype=float),
        np.zeros(128, dtype=float),
        np.zeros(128, dtype=float),
    ]
    new_encodings[0][0] = 0.20
    new_encodings[1][0] = 0.22
    new_encodings[2][0] = 0.25
    assert mod.identity_conflict(new_encodings, gallery, 0.38, min_votes=2)


def test_loose_old_threshold_would_false_match_but_new_threshold_does_not():
    gallery = [np.zeros(128, dtype=float)]
    almost = np.zeros(128, dtype=float)
    almost[0] = 0.48
    new_encodings = [almost, almost, almost]
    assert mod.identity_conflict(new_encodings, gallery, 0.50, min_votes=1)
    assert not mod.identity_conflict(new_encodings, gallery, 0.38, min_votes=2)


if __name__ == "__main__":
    test_different_people_do_not_conflict_on_one_close_sample()
    test_same_person_conflicts_when_most_samples_match()
    test_loose_old_threshold_would_false_match_but_new_threshold_does_not()
    print("ok")
