"""Chapter 2 figures for SMART-DTR (live system). Coordinates are inches on a 13.33 x 7.5 slide."""

from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import Circle, Ellipse, FancyArrowPatch, FancyBboxPatch, Rectangle

OUT = Path(r"c:\xampp\htdocs\INHS SMART-DTR\diagrams")
OUT.mkdir(exist_ok=True)

NAVY = "#0B3D91"
NAVY_DARK = "#07285C"
GOLD = "#D4A017"
CREAM = "#F7F4EC"
INK = "#1A2332"
WHITE = "#FFFFFF"
PROCESS = "#E8EEF9"
ENTITY = "#FFF6D9"
STORE = "#F4F1EA"
W, H = 13.33, 7.5
FONT = "DejaVu Sans"


def new_fig(kicker, title):
    fig, ax = plt.subplots(figsize=(W, H), dpi=140)
    fig.patch.set_facecolor(CREAM)
    ax.set_xlim(0, W)
    ax.set_ylim(0, H)
    ax.set_aspect("equal")
    ax.axis("off")
    ax.set_facecolor(CREAM)
    ax.add_patch(Rectangle((0, 7.18), W, 0.32, color=NAVY_DARK, zorder=0, clip_on=False))
    ax.add_patch(Rectangle((0, 7.12), W, 0.06, color=GOLD, zorder=1, clip_on=False))
    ax.text(0.28, 7.33, kicker, color=GOLD, fontsize=9, fontweight="bold", va="center", fontname=FONT, clip_on=False)
    ax.text(0.28, 6.82, title, color=NAVY, fontsize=15, fontweight="bold", va="center", fontname=FONT, clip_on=False)
    ax.text(W - 0.28, 0.16, "SMART-DTR  ·  Isabela National High School – SHS", color=NAVY, fontsize=8, ha="right", va="center", fontname=FONT, clip_on=False)
    return fig, ax


def box(ax, x, y, w, h, lines, fill=ENTITY):
    ax.add_patch(FancyBboxPatch((x, y), w, h, boxstyle="square,pad=0", lw=1.5, edgecolor=NAVY, facecolor=fill, zorder=2, clip_on=False))
    ax.text(x + w / 2, y + h / 2, "\n".join(lines), ha="center", va="center", fontsize=9, color=INK, fontname=FONT, zorder=3, linespacing=1.25, clip_on=False)


def bubble(ax, x, y, r, num, lines):
    ax.add_patch(Circle((x, y), r, lw=1.6, edgecolor=NAVY, facecolor=PROCESS, zorder=2, clip_on=False))
    ax.text(x, y + 0.28, num, ha="center", va="center", fontsize=10, color=GOLD, fontweight="bold", fontname=FONT, zorder=3, clip_on=False)
    ax.text(x, y - 0.22, "\n".join(lines), ha="center", va="center", fontsize=8, color=INK, fontname=FONT, zorder=3, linespacing=1.2, clip_on=False)


def store(ax, x, y, w, h, lines):
    ax.add_patch(Rectangle((x, y), w, h, lw=0, facecolor=STORE, zorder=2, clip_on=False))
    ax.plot([x, x], [y, y + h], color=NAVY, lw=1.8, zorder=3, clip_on=False)
    ax.plot([x, x + w], [y + h, y + h], color=NAVY, lw=1.8, zorder=3, clip_on=False)
    ax.plot([x, x + w], [y, y], color=NAVY, lw=1.8, zorder=3, clip_on=False)
    ax.text(x + w / 2, y + h / 2, "\n".join(lines), ha="center", va="center", fontsize=8.5, color=INK, fontname=FONT, zorder=4, linespacing=1.2, clip_on=False)


def flow(ax, p1, p2, label="", dy=0.12):
    ax.add_patch(FancyArrowPatch(p1, p2, arrowstyle="-|>", mutation_scale=11, lw=1.2, color=NAVY, zorder=1, clip_on=False))
    if label:
        mx, my = (p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2 + dy
        ax.text(mx, my, label, ha="center", va="center", fontsize=7.5, color=NAVY_DARK, fontname=FONT, zorder=5, clip_on=False,
                bbox=dict(boxstyle="round,pad=0.12", fc=CREAM, ec="none", alpha=0.95))


def oval(ax, x, y, w, h, text, fill=PROCESS):
    ax.add_patch(Ellipse((x, y), w, h, lw=1.3, edgecolor=NAVY, facecolor=fill, zorder=2, clip_on=False))
    ax.text(x, y, text, ha="center", va="center", fontsize=8, color=INK, fontname=FONT, zorder=3, linespacing=1.15, clip_on=False)


def actor(ax, x, y, name, note=""):
    ax.add_patch(Circle((x, y + 1.05), 0.18, fill=False, lw=1.5, edgecolor=NAVY, zorder=3, clip_on=False))
    ax.plot([x, x], [y + 0.87, y + 0.42], color=NAVY, lw=1.5, clip_on=False)
    ax.plot([x - 0.28, x + 0.28], [y + 0.72, y + 0.72], color=NAVY, lw=1.5, clip_on=False)
    ax.plot([x, x - 0.22], [y + 0.42, y + 0.08], color=NAVY, lw=1.5, clip_on=False)
    ax.plot([x, x + 0.22], [y + 0.42, y + 0.08], color=NAVY, lw=1.5, clip_on=False)
    ax.text(x, y - 0.22, name, ha="center", va="top", fontsize=9, color=NAVY, fontweight="bold", fontname=FONT, clip_on=False)
    if note:
        ax.text(x, y - 0.52, note, ha="center", va="top", fontsize=7, color="#3D4F66", fontname=FONT, clip_on=False, linespacing=1.15)


def save(fig, name):
    path = OUT / name
    fig.savefig(path, facecolor=fig.get_facecolor(), bbox_inches="tight", pad_inches=0.12)
    plt.close(fig)
    print(path)


def make_attendance_dfd():
    fig, ax = new_fig("FIGURE 3", "Data flow diagram — personnel attendance recording")
    box(ax, 0.35, 4.55, 2.35, 1.25, ["Personnel", "(teacher / staff)"])
    box(ax, 0.35, 2.15, 2.35, 1.25, ["Raspberry Pi", "kiosk + camera"])
    bubble(ax, 5.15, 4.05, 1.22, "1.0", ["Capture and verify", "face + blink"])
    bubble(ax, 8.55, 4.05, 1.22, "2.0", ["Record attendance", "time-in / time-out"])
    store(ax, 10.35, 5.55, 2.65, 0.95, ["D1  Personnel", "and face templates"])
    store(ax, 10.35, 1.35, 2.65, 0.95, ["D2  Attendance", "logs and daily DTR"])

    flow(ax, (2.70, 5.35), (4.05, 4.55), "Face and blink", 0.22)
    flow(ax, (2.70, 2.90), (4.10, 3.45), "Camera image", -0.22)
    flow(ax, (10.35, 6.00), (6.10, 4.85), "Face templates", 0.28)
    flow(ax, (6.37, 4.05), (7.33, 4.05), "Verified ID + time", 0.28)
    flow(ax, (9.55, 3.20), (10.35, 2.30), "Time-in / time-out", -0.18)
    flow(ax, (7.55, 3.05), (2.70, 2.55), "LCD result", -0.28)
    ax.text(W / 2, 0.48, "A punch is stored only after a face match and an open–close–open blink.", ha="center", fontsize=9, color=INK, fontname=FONT, clip_on=False)
    save(fig, "Figure-3-Attendance-DFD.png")


def make_dashboard_dfd():
    fig, ax = new_fig("FIGURE 3.1", "Data flow diagram — administrator dashboard modules")
    box(ax, 0.30, 3.15, 2.45, 1.55, ["Administrator", "HR / Super Admin", "Principal (view)"])
    bubble(ax, 4.55, 5.55, 1.05, "4.0", ["User accounts", "and 201 files"])
    bubble(ax, 4.55, 3.70, 1.05, "3.0", ["Personnel status", "monitoring"])
    bubble(ax, 4.55, 1.85, 1.05, "5.0", ["Dashboard", "and reports"])
    store(ax, 8.15, 5.70, 4.70, 0.78, ["D1  Personnel / staff accounts / 201 files"])
    store(ax, 8.15, 4.55, 4.70, 0.78, ["D2  Attendance records"])
    store(ax, 8.15, 3.40, 4.70, 0.78, ["D3  Leave, locator, travel"])
    store(ax, 8.15, 2.25, 4.70, 0.78, ["D4  Schedule and holidays"])
    store(ax, 8.15, 1.10, 4.70, 0.78, ["D5  Daily status and audit log"])

    flow(ax, (2.75, 4.50), (3.65, 5.55), "Manage accounts", 0.18)
    flow(ax, (2.75, 3.95), (3.50, 3.70), "View status", 0.16)
    flow(ax, (2.75, 3.35), (3.60, 2.15), "Dashboard / Form 48", -0.22)
    flow(ax, (5.60, 5.55), (8.15, 6.05), "Read / write", 0.16)
    flow(ax, (5.60, 4.00), (8.15, 4.90), "Punches", 0.14)
    flow(ax, (5.60, 3.55), (8.15, 3.75), "Letters", 0.14)
    flow(ax, (5.60, 3.20), (8.15, 2.60), "Duty week", -0.12)
    flow(ax, (5.60, 1.85), (8.15, 1.50), "Counts, Form 48, audit", -0.14)
    ax.text(W / 2, 0.48, "Status is computed from punches, schedule, leave, locator, travel records, and holidays.", ha="center", fontsize=9, color=INK, fontname=FONT, clip_on=False)
    save(fig, "Figure-3.1-Dashboard-Modules-DFD.png")


def make_usecase():
    fig, ax = new_fig("FIGURE 4", "Use case diagram — SMART-DTR")
    ax.add_patch(FancyBboxPatch((2.70, 0.72), 7.95, 5.95, boxstyle="round,pad=0.08,rounding_size=0.18",
                                lw=1.5, edgecolor=NAVY, facecolor=WHITE, zorder=0, clip_on=False))
    ax.text(6.67, 6.40, "SMART-DTR", ha="center", fontsize=13, color=NAVY, fontweight="bold", fontname=FONT, clip_on=False)
    actor(ax, 1.05, 3.55, "Personnel", "Employee Portal + Pi")
    actor(ax, 12.28, 3.55, "Administrator", "HR / Super Admin\nPrincipal: view DTR")
    left = [
        (4.60, 5.80, "Time in / time out\n(face + blink on Pi)"),
        (4.60, 5.02, "Log in employee portal"),
        (4.60, 4.25, "View own dashboard"),
        (4.60, 3.48, "View own schedule"),
        (4.60, 2.70, "Submit CS Form 6"),
        (4.60, 1.92, "Submit locator / travel"),
        (4.60, 1.15, "View notices / password"),
    ]
    right = [
        (8.75, 5.80, "Manage personnel\n& 201 file"),
        (8.75, 5.02, "Enroll faces /\nactivate staff"),
        (8.75, 4.25, "Assign schedule\n& holidays"),
        (8.75, 3.48, "Monitor dashboard\n& personnel status"),
        (8.75, 2.70, "Approve leave,\nlocator, travel"),
        (8.75, 1.92, "Generate Form 48\n& reports PDF"),
        (8.75, 1.15, "Correct attendance\n& view audit log"),
    ]
    for x, y, t in left:
        oval(ax, x, y, 2.40, 0.70, t)
        ax.plot([1.05, x - 1.20], [4.27, y], color="#8A93A3", lw=0.85, zorder=1, clip_on=False)
    for x, y, t in right:
        oval(ax, x, y, 2.40, 0.70, t, fill=ENTITY)
        ax.plot([12.28, x + 1.20], [4.27, y], color="#8A93A3", lw=0.85, zorder=1, clip_on=False)
    save(fig, "Figure-4-Use-Case-Diagram.png")


def panel(ax, x, y, w, h, header, lines, header_fill=NAVY, body_fill=WHITE):
    ax.add_patch(FancyBboxPatch((x, y), w, h, boxstyle="square,pad=0", lw=1.5, edgecolor=NAVY, facecolor=body_fill, zorder=2, clip_on=False))
    ax.add_patch(Rectangle((x, y + h - 0.48), w, 0.48, color=header_fill, zorder=3, clip_on=False))
    ax.text(x + w / 2, y + h - 0.24, header, ha="center", va="center", fontsize=9, color=GOLD, fontweight="bold", fontname=FONT, zorder=4, clip_on=False)
    ax.text(x + 0.18, y + h - 0.68, "\n".join(lines), ha="left", va="top", fontsize=8.4, color=INK, fontname=FONT, zorder=4, linespacing=1.45, clip_on=False)


def make_conceptual():
    fig, ax = new_fig("CONCEPTUAL FRAMEWORK", "Independent, intervening, and dependent variables")

    iv = [
        "Raspberry Pi kiosk and camera",
        "Face recognition",
        "Blink liveness detection",
        "Web system (Laravel and MySQL)",
        "Personnel records and 201 file",
        "Leave, locator, and travel letters",
        "Schedule, holidays, and Form 48",
        "Login, roles, and audit trail",
    ]
    dv = [
        "Accuracy of attendance recording",
        "Efficiency of monitoring",
        "System reliability",
        "Security against fraud",
        "User satisfaction",
    ]

    panel(ax, 0.28, 1.10, 3.35, 5.00, "INDEPENDENT VARIABLES", [f"•  {t}" for t in iv])
    panel(ax, 9.70, 1.10, 3.35, 5.00, "DEPENDENT VARIABLES", [f"•  {t}" for t in dv])

    ax.add_patch(FancyBboxPatch((5.05, 1.55), 3.23, 4.10, boxstyle="square,pad=0", lw=1.5, edgecolor=NAVY, facecolor=PROCESS, zorder=2, clip_on=False))
    ax.add_patch(Rectangle((5.05, 5.17), 3.23, 0.48, color=NAVY_DARK, zorder=3, clip_on=False))
    ax.text(6.67, 5.41, "INTERVENING VARIABLE", ha="center", va="center", fontsize=8.5, color=GOLD, fontweight="bold", fontname=FONT, zorder=4, clip_on=False)
    ax.text(6.67, 4.62, "SMART-DTR", ha="center", va="center", fontsize=15, color=NAVY, fontweight="bold", fontname=FONT, zorder=5, clip_on=False)
    ax.text(
        6.67, 3.15,
        "IoT web attendance and HRIS\n"
        "Face and blink punch\n"
        "Live personnel status\n"
        "Official letters and Form 48\n"
        "Centralized school database",
        ha="center", va="center", fontsize=8, color=INK, fontname=FONT, zorder=5, linespacing=1.55, clip_on=False,
    )
    flow(ax, (3.63, 3.60), (5.05, 3.60), "", 0)
    flow(ax, (8.28, 3.60), (9.70, 3.60), "", 0)
    ax.text(4.34, 3.95, "processed by", ha="center", fontsize=8, color=NAVY_DARK, fontname=FONT, clip_on=False)
    ax.text(8.99, 3.95, "produces", ha="center", fontsize=8, color=NAVY_DARK, fontname=FONT, clip_on=False)
    save(fig, "Conceptual-Framework.png")


def layered(ax, x, y, w, h, header, lines, fill=WHITE):
    ax.add_patch(FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.02,rounding_size=0.08", lw=1.4, edgecolor=NAVY, facecolor=fill, zorder=2, clip_on=False))
    ax.text(x + 0.16, y + h - 0.28, header, ha="left", va="center", fontsize=10, color=NAVY, fontweight="bold", fontname=FONT, zorder=3, clip_on=False)
    ax.text(x + 0.16, y + h - 0.58, "\n".join(lines), ha="left", va="top", fontsize=8.2, color=INK, fontname=FONT, zorder=3, linespacing=1.35, clip_on=False)


def make_architecture():
    fig, ax = new_fig("FIGURE 1", "System architecture of SMART-DTR")

    box(ax, 0.28, 5.20, 2.55, 1.28, ["Personnel", "face + blink"], ENTITY)
    box(ax, 0.28, 3.35, 2.55, 1.40, ["Raspberry Pi kiosk", "camera, LCD, Python"], PROCESS)
    box(ax, 0.28, 1.55, 2.55, 1.28, ["Admin / HR / staff", "web browser"], ENTITY)
    ax.text(1.55, 1.22, "School LAN", ha="center", fontsize=8, color=NAVY_DARK, fontname=FONT, clip_on=False)

    flow(ax, (1.55, 5.20), (1.55, 4.75), "face + blink", 0.22)
    flow(ax, (2.83, 4.05), (3.90, 3.95), "", 0)
    ax.text(3.36, 4.36, "POST scan", ha="center", fontsize=7.5, color=NAVY_DARK, fontname=FONT, clip_on=False)

    layered(ax, 3.90, 5.05, 8.90, 1.42, "Presentation layer", [
        "Admin portal  ·  Employee portal  ·  Pi LCD result",
        "Login, dashboard, leave, locator, travel, DTR, reports",
    ], WHITE)
    layered(ax, 3.90, 3.20, 8.90, 1.52, "Business logic layer  —  Laravel", [
        "Face and blink validation, time-in / time-out, daily status",
        "Leave (CS Form 6), locator, travel, schedule, holidays",
        "Roles, hashed passwords (bcrypt), audit log, Form 48 PDF",
    ], PROCESS)
    layered(ax, 3.90, 1.35, 8.90, 1.52, "Data layer  —  MySQL (smartdtr)", [
        "Personnel, staff accounts, 201 files, face templates",
        "Attendance logs, daily status, leave credits, locator, travel",
        "Schedules, holidays, notifications, audit trail",
    ], STORE)

    flow(ax, (8.35, 5.05), (8.35, 4.72), "", 0)
    flow(ax, (8.35, 3.20), (8.35, 2.87), "", 0)
    save(fig, "Figure-1-System-Architecture.png")


def make_ipo():
    fig, ax = new_fig("RESEARCH PARADIGM", "Input–Process–Output (IPO) model")

    panel(ax, 0.28, 0.85, 3.85, 5.70, "INPUT", [
        "Existing process",
        "•  DTR machine and USB dump",
        "•  Leave, locator, and Form 48 by hand",
        "•  HR monitoring difficulties",
        "",
        "Hardware",
        "•  Laptop server (XAMPP)",
        "•  Raspberry Pi and camera",
        "•  School LAN / hotspot",
        "",
        "Software",
        "•  Laravel, PHP, MySQL",
        "•  Python face recognition",
        "",
        "Knowledge",
        "•  Web, database, SDLC",
        "•  Face and blink liveness",
    ])
    panel(ax, 4.74, 0.85, 3.85, 5.70, "PROCESS", [
        "Iterative Waterfall Model",
        "",
        "1.  Requirements gathering",
        "2.  System design",
        "3.  Development",
        "4.  Testing and debugging",
        "5.  Implementation",
        "6.  Evaluation and feedback",
        "",
        "Each phase can return to",
        "an earlier phase when the",
        "school process or a module",
        "needs a correction.",
    ], header_fill=NAVY_DARK, body_fill=PROCESS)
    panel(ax, 9.20, 0.85, 3.85, 5.70, "OUTPUT", [
        "Functional SMART-DTR",
        "",
        "•  Face and blink attendance",
        "•  Live personnel status",
        "•  CS Form 6, locator, travel",
        "•  Civil Service Form 48 PDF",
        "•  Centralized MySQL records",
        "•  Login, roles, and audit",
        "",
        "IoT-enabled web-based",
        "attendance and personnel",
        "status monitoring system",
        "for INHS–SHS.",
    ], header_fill=NAVY, body_fill=ENTITY)

    flow(ax, (4.13, 3.70), (4.74, 3.70), "", 0)
    flow(ax, (8.59, 3.70), (9.20, 3.70), "", 0)
    save(fig, "Research-Paradigm-IPO.png")


if __name__ == "__main__":
    make_conceptual()
    make_architecture()
    make_ipo()
    make_attendance_dfd()
    make_dashboard_dfd()
    make_usecase()
