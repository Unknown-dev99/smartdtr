"""SMART-DTR defense PPT: Calisto MT, 40pt, RRL problem/solution, speaker notes."""

from lxml import etree
from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.oxml.ns import qn
from pptx.util import Emu, Inches, Pt

NAVY = RGBColor(0x0B, 0x3D, 0x91)
NAVY_DARK = RGBColor(0x07, 0x28, 0x5C)
GOLD = RGBColor(0xD4, 0xA0, 0x17)
GOLD_SOFT = RGBColor(0xF3, 0xE0, 0xA7)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
INK = RGBColor(0x1A, 0x23, 0x32)
CREAM = RGBColor(0xF7, 0xF4, 0xEC)
CARD = RGBColor(0xFF, 0xFF, 0xFF)

FONT = "Calisto MT"
SIZE = 40
OUT = r"c:\xampp\htdocs\INHS SMART-DTR\SMART-DTR-Defense-Presentation.pptx"

FULL_TITLE = (
    "SMART-DTR: A Web-Based Attendance Monitoring and Human Resource "
    "Information System for Efficient Personnel Management of Senior High "
    "School Personnel at Isabela National High School"
)


def font(run, size=SIZE, bold=False, color=INK):
    run.font.name = FONT
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.color.rgb = color
    rPr = run._r.get_or_add_rPr()
    for tag in ("a:latin", "a:ea", "a:cs"):
        el = rPr.find(qn(tag))
        if el is None:
            el = etree.SubElement(rPr, qn(tag))
        el.set("typeface", FONT)


def no_bullet(paragraph):
    pPr = paragraph._p.get_or_add_pPr()
    for child in list(pPr):
        if "bu" in child.tag:
            pPr.remove(child)
    etree.SubElement(pPr, qn("a:buNone"))


def rect(slide, l, t, w, h, fill, line=None):
    sh = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, l, t, w, h)
    sh.fill.solid()
    sh.fill.fore_color.rgb = fill
    if line is None:
        sh.line.fill.background()
    else:
        sh.line.color.rgb = line
        sh.line.width = Emu(12700)
    return sh


def round_rect(slide, l, t, w, h, fill):
    sh = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, l, t, w, h)
    sh.fill.solid()
    sh.fill.fore_color.rgb = fill
    sh.line.fill.background()
    sh.adjustments[0] = 0.08
    return sh


def textbox(slide, l, t, w, h, text, size=SIZE, bold=False, color=INK, align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP):
    box = slide.shapes.add_textbox(l, t, w, h)
    tf = box.text_frame
    tf.word_wrap = True
    tf.auto_size = None
    try:
        tf._txBody.bodyPr.set("anchor", {MSO_ANCHOR.TOP: "t", MSO_ANCHOR.MIDDLE: "ctr", MSO_ANCHOR.BOTTOM: "b"}[anchor])
    except Exception:
        pass
    p = tf.paragraphs[0]
    p.alignment = align
    p.space_before = Pt(0)
    p.space_after = Pt(0)
    no_bullet(p)
    r = p.add_run()
    r.text = text
    font(r, size, bold, color)
    return box


def lines(slide, l, t, w, h, items, size=SIZE, color=INK, space=10):
    box = slide.shapes.add_textbox(l, t, w, h)
    tf = box.text_frame
    tf.word_wrap = True
    for i, item in enumerate(items):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = PP_ALIGN.LEFT
        p.space_before = Pt(4)
        p.space_after = Pt(space)
        p.line_spacing = 1.05
        no_bullet(p)
        r = p.add_run()
        r.text = item
        font(r, size, False, color)
    return box


def add_notes(slide, text):
    ns = slide.notes_slide
    tf = ns.notes_text_frame
    parts = [p.strip() for p in text.strip().split("\n") if p.strip()]
    tf.text = parts[0] if parts else ""
    for extra in parts[1:]:
        p = tf.add_paragraph()
        p.text = extra
    for p in tf.paragraphs:
        no_bullet(p)
        p.space_after = Pt(8)
        p.line_spacing = 1.15
        for run in p.runs:
            run.font.name = FONT
            run.font.size = Pt(16)
            rPr = run._r.get_or_add_rPr()
            for tag in ("a:latin", "a:ea", "a:cs"):
                el = rPr.find(qn(tag))
                if el is None:
                    el = etree.SubElement(rPr, qn(tag))
                el.set("typeface", FONT)


def footer(slide, page, total):
    rect(slide, Inches(0), Inches(7.22), Inches(13.333), Inches(0.28), NAVY)
    textbox(slide, Inches(0.45), Inches(7.22), Inches(10), Inches(0.28),
            "SMART-DTR  ·  Isabela National High School – Senior High School", 12, False, GOLD_SOFT, PP_ALIGN.LEFT, MSO_ANCHOR.MIDDLE)
    textbox(slide, Inches(10.6), Inches(7.22), Inches(2.3), Inches(0.28),
            f"{page}  /  {total}", 12, True, WHITE, PP_ALIGN.RIGHT, MSO_ANCHOR.MIDDLE)


def chrome(slide):
    rect(slide, Inches(0), Inches(0), Inches(13.333), Inches(7.5), CREAM)
    rect(slide, Inches(0), Inches(0), Inches(0.14), Inches(7.5), GOLD)


def header(slide, kicker, title):
    rect(slide, Inches(0), Inches(0), Inches(13.333), Inches(1.62), NAVY)
    rect(slide, Inches(0), Inches(1.62), Inches(13.333), Inches(0.07), GOLD)
    textbox(slide, Inches(0.45), Inches(0.10), Inches(12.4), Inches(0.32), kicker, 14, True, GOLD)
    textbox(slide, Inches(0.45), Inches(0.40), Inches(12.4), Inches(1.14), title, 40, True, WHITE)


def content(prs, kicker, title, items, page, total, notes, space=8):
    s = prs.slides.add_slide(prs.slide_layouts[6])
    chrome(s)
    header(s, kicker, title)
    round_rect(s, Inches(0.35), Inches(1.88), Inches(12.65), Inches(5.18), CARD)
    lines(s, Inches(0.6), Inches(2.05), Inches(12.15), Inches(4.85), items, SIZE, INK, space)
    footer(s, page, total)
    add_notes(s, notes)
    return s


def main():
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    total = 34

    # 1 Title
    s = prs.slides.add_slide(prs.slide_layouts[6])
    rect(s, Inches(0), Inches(0), Inches(13.333), Inches(7.5), NAVY_DARK)
    rect(s, Inches(0), Inches(0), Inches(0.18), Inches(7.5), GOLD)
    rect(s, Inches(0), Inches(6.95), Inches(13.333), Inches(0.55), GOLD)
    textbox(s, Inches(0.55), Inches(0.38), Inches(12.2), Inches(0.38), "THESIS DEFENSE PRESENTATION", 16, True, GOLD)
    textbox(s, Inches(0.55), Inches(0.95), Inches(12.2), Inches(5.6), FULL_TITLE, 40, True, WHITE)
    textbox(s, Inches(0.55), Inches(7.02), Inches(12.2), Inches(0.4),
            "Title  ·  Problem  ·  Solution  ·  Objectives  ·  Scope  ·  Demonstration", 16, True, NAVY_DARK)
    add_notes(s, """
Good afternoon, panel members.

This is SMART-DTR: a web-based attendance monitoring and human resource information system for Senior High School personnel of Isabela National High School.

In one sentence: we took attendance off a DTR machine and a flash drive, and put it on a live school system that also supports leave, locator, travel, and Civil Service Form 48.

Today I will present the problem, the solution, the objectives, the scope and limitations, and then a live demonstration.
""")

    content(
        prs, "CURRENT PROCESS", "The Problem at INHS-SHS",
        [
            "INHS-SHS still uses a DTR machine for attendance.",
            "Records stay in the device until copied with a flash drive.",
            "HR prepares CS Form 48 only after that USB dump.",
            "Monitoring is not live on a school server.",
            "Leave, locator, and travel are separate from the machine.",
        ],
        2, total, space=6,
        notes="""
At INHS Senior High, attendance still starts at a DTR machine.

The records do not become official until someone copies them with a flash drive. Only after that dump can HR prepare Civil Service Form 48.

Monitoring is not live. Leave, locator, and travel live outside the machine. HR is stitching USB, paper, and DTR together by hand.

That is the actual school process we studied. Objective 1 is this process, not a questionnaire.
""",
    )

    content(
        prs, "CHAPTER 1  ·  BACKGROUND", "HR challenges in the paper",
        [
            "Attendance is checked and consolidated by hand.",
            "Report preparation is slow and labor-intensive.",
            "Leave credits and personnel files are hard to keep current.",
            "Wrong entries, missing logs, and delayed updates happen.",
            "A growing workforce makes the same process even heavier.",
        ],
        3, total, space=6,
        notes="""
Chapter 1 of the manuscript is bigger than USB.

The HR office must monitor teaching and non-teaching personnel. Records are still checked and consolidated manually, so reports take time.

HR also struggles to see attendance in real time, track leave credits, keep personnel files updated, and know who is present, on leave, or on official business.

Human error follows: wrong entries, missing records, delayed updates. Delayed reports, inconsistent files, and a heavier workload.

Do not quote foreign percentages as if we measured them at INHS. Those numbers live in the related literature. Land every line back on this school.
""",
    )

    content(
        prs, "REVIEW OF RELATED LITERATURE", "Problems the studies already named",
        [
            "Manual logs are slow, error-prone, and open to proxy punches.",
            "Delayed records have no live data for decisions.",
            "RFID, QR, and fingerprint still allow sharing or contact issues.",
            "Face systems without a blink fall to a printed photo.",
            "Most attendance boxes stop at time-in and time-out.",
        ],
        4, total, space=4,
        notes="""
Our Review of Related Literature explains why this problem is not only INHS.

Ali and others show attendance moving from paper logs to RFID, barcode, biometrics, and the web. Manual methods are time-consuming and inefficient.

Related studies also warn about proxy attendance, clerical error, lost paper files, and no real-time view, so administrators act late.

Fingerprint and RFID help, but a card can be borrowed and a print can be dirty or worn. Face recognition without liveness can be beaten by a photograph. Spoofing studies found high success for photo and video attacks.

Chaudhary and Chouhan said biometrics make records more reliable. Perez Siguas said automation speeds reports. Nurfaizi and Hindarto said a web system centralizes data. Many systems still only record in and out, and leave, 201 files, and status stay separate.

If asked for citations: Ali et al., 2022; Chaudhary and Chouhan, 2022; Perez Siguas et al., 2023; Nurfaizi and Hindarto, 2023; Oyerinde et al. on spoofing. Do not read the years unless they ask.
""",
    )

    content(
        prs, "RESEARCH GAP", "What existing systems still miss",
        [
            "Recognition, dashboard, and HR paper are rarely one system.",
            "Accurate systems often skip anti-spoofing.",
            "Secure systems can be too slow for a school doorway.",
            "Status is often only Present or Absent.",
            "INHS still walks a flash drive to make Form 48.",
        ],
        5, total, space=6,
        notes="""
The synthesis in our paper names the research gap.

Many studies cover face recognition, or IoT hardware, or a web dashboard. Few put face, blink, live status, official letters, and Civil Service Form 48 in one school system, on a low-power Raspberry Pi, without a USB walk.

The gap is also this: systems that chase accuracy skip liveness. Systems that chase security get slow at the door. And status is often only present or absent, not On Leave, Official Business, Official Travel, or Off-duty.

That is the gap SMART-DTR was built to close.

Do not claim a hallway television display, automatic monthly leave accrual, or internet-wide cloud hosting. Those are not the live system.
""",
    )

    content(
        prs, "THE SYSTEM", "The Solution: SMART-DTR",
        [
            "Raspberry Pi kiosk records time-in and time-out by face and blink.",
            "The laptop, Laravel and MySQL, is the official centralized record.",
            "Each accepted punch syncs in real time. No flash-drive dump.",
            "Approved leave, locator, and travel connect to the DTR.",
            "Official papers: Civil Service Form 48 and CS Form 6.",
        ],
        6, total, space=4,
        notes="""
SMART-DTR closes that gap.

A Raspberry Pi kiosk records time-in and time-out by face and blink. The laptop, Laravel and MySQL, is the official centralized record. Every accepted punch syncs in real time. No USB dump.

When HR approves leave or a locator slip, that status connects to the DTR. Official output is Civil Service Form 48 and CS Form 6.

From a flash drive, to a live database.

If asked: yes, we used Laravel. The Pi is the face kiosk. The laptop on the school LAN is the server.
""",
    )

    content(
        prs, "RRL TO SYSTEM", "How SMART-DTR answers the literature",
        [
            "Live punch on the Pi. Official record on the laptop. No USB.",
            "Blink liveness rejects photos, unknown faces, and weak scans.",
            "One web system: 201 file, letters, status, and Form 48.",
            "Status from punches, schedule, leave, locator, and holidays.",
            "Login, roles, hashed passwords, and an audit trail.",
        ],
        7, total, space=4,
        notes="""
Map the literature to what we actually built. Five answers.

Against the USB dump: the Pi is an IoT kiosk. Face plus blink, then the punch goes to Laravel and MySQL in real time. Edge capture, central store.

Against proxy and spoof: we do not accept a photo. Face recognition identifies the person. Blink detection using Eye Aspect Ratio proves a live person. No blink, low confidence, unknown ID: rejected.

Against attendance-only boxes: the same platform holds the 201 file, weekly schedule, CS Form 6, locator, travel order, and Form 48.

Against delayed monitoring: the dashboard is live. Present, Late, Absent, On Leave, Official Travel, Official Business, Off-duty, and whether the Pi is online.

Against weak control: separate Admin and Employee portals. Super Admin, HR, Principal, and staff. Passwords hashed. No module without login. Critical actions go to the audit log.

Catchy line if you need it: literature moved from logbooks to RFID, QR, and biometrics. INHS was still on a machine and a flash drive.

Do not say travel-order Approve writes Official Travel on the DTR. Locator approval writes Official Business and keeps the punches. Official Travel on the DTR comes from the leave or travel record. Leave credits are initialized and deducted on approval. There is no automatic monthly accrual.
""",
    )

    content(
        prs, "OBJECTIVES OF THE STUDY", "This study aims to:",
        [
            "1. Analyze the current DTR-machine and flash-drive process at INHS-SHS.",
            "2. Design and develop SMART-DTR with face attendance, live status, letters, and HR support.",
            "3. Implement security controls for login, roles, face and blink, and audit.",
        ],
        8, total, space=12,
        notes="""
This study has three aims.

One. Analyze the current DTR-machine and flash-drive process.

Two. Design and develop SMART-DTR: face attendance, live status, official letters, and HR support.

Three. Implement security: login, roles, face and blink, and an audit trail.

Those three are the backbone of this defense. I will now walk them in order.
""",
    )

    content(
        prs, "OBJECTIVE 1", "Analyze the current process",
        [
            "Existing DTR machine at INHS-SHS",
            "Attendance records retrieved through a flash drive",
            "Daily monitoring and personnel status tracking",
            "Leave and official-business handling",
            "DTR and report preparation",
            "Difficulties in managing personnel records",
        ],
        9, total, space=4,
        notes="""
Objective 1 is not a survey. It is the actual school process.

They already have a DTR machine. Attendance is retrieved by USB. Daily status is hard to see in real time. Leave and official business are handled separately. Form 48 is prepared after the dump. Personnel records are difficult to keep together.

That process is what SMART-DTR replaces. The related literature explains why USB-and-paper processes fail. The school process is what we analyzed.
""",
    )

    content(
        prs, "OBJECTIVE 2", "Design and develop SMART-DTR",
        [
            "Intelligent face-based attendance recording",
            "Real-time personnel status monitoring",
            "Official letter processing: leave, locator, and travel",
            "Human Resource support functions",
            "Delivered through modules 2.1 to 2.13",
        ],
        10, total, space=6,
        notes="""
Objective 2 is the system itself.

Four promises: intelligent face attendance, real-time status, online leave, locator, and travel, and HR support, delivered in modules 2.1 to 2.13.

I will walk through each module in one line. I will not read every sub-number. We will see it live in the demonstration.
""",
    )

    modules = [
        ("2.1 Employee Profiling and 201 File", [
            "2.1.1 Personnel registration and staff account creation",
            "2.1.2 Profile: name, role, official hours; department on the staff account",
            "2.1.3 201 file upload, storage, and retrieval",
            "2.1.4 Personnel activation, deactivation, and record maintenance",
        ], """
Every employee has a personnel record and a staff account. Names, role, official hours. Department sits on the staff account. The 201 file is uploaded and retrieved in the system, not only in a cabinet.

This answers the paper's problem of outdated personnel records and fragmented files.
"""),
        ("2.2 Face Recognition and Liveness", [
            "2.2.1 Facial registration and enrollment of personnel",
            "2.2.2 Face recognition on the Raspberry Pi kiosk",
            "2.2.3 Blinking detection as liveness verification",
            "2.2.4 Reject scans without a valid blink or enough face confidence",
        ], """
We do not accept a photo taped to the camera. The Pi must recognize the face and see a blink. No blink, low confidence, unknown person: rejected.

The RRL said face systems without liveness fall to printed photos. Blink detection using Eye Aspect Ratio is our anti-spoof answer. Contactless, so we also avoid fingerprint hygiene and shared RFID cards.
"""),
        ("2.3 Real-Time Attendance Recording", [
            "2.3.1 Automated time-in through the Raspberry Pi kiosk",
            "2.3.2 Automated time-out through succeeding scans",
            "2.3.3 Real-time sync to the centralized database",
            "2.3.4 Logging in attendance logs and daily records",
        ], """
First accepted scan is time-in. The next valid scan is time-out. It is written immediately to the central database, attendance logs and daily records.

That is the end of the flash drive. Literature called for real-time processing instead of delayed collation. This is that punch line.
"""),
        ("2.4 Attendance Monitoring Dashboard", [
            "2.4.1 Daily attendance by selected date",
            "2.4.2 Present, Late, Absent, On Leave, Official Travel, Official Business, Off-duty",
            "2.4.3 Raspberry Pi online status and scan-count monitoring",
            "2.4.4 Attendance history for administrators and employees",
        ], """
HR does not wait for USB. They open a date and see Present, Late, Absent, On Leave, Official Travel, Official Business, and Off-duty. They also see if the Pi is online and how many scans it has sent.

Web dashboards in the RRL improve remote access and faster inquiry. Ours runs on the school LAN. Pi Online is a real heartbeat, not a thirty-minute guess.
"""),
        ("2.5 Personnel Status Monitoring", [
            "2.5.1 Status from punches, schedule, leave, locator, travel records, and holidays",
            "2.5.2 Present and Late",
            "2.5.3 Absent and Off-duty",
            "2.5.4 On Leave, Official Travel, and Official Business",
        ], """
Status is not a guess. It is computed from punches, schedule, leave, locator, travel records, and holidays.

That is why a teacher on an approved locator shows Official Business, not simply absent. The research gap said most systems only show present or absent. We go further.

Do not promise a television on the wall. Status is on the logged-in dashboard.
"""),
        ("2.6 Schedule and Holiday Management", [
            "2.6.1 Weekly duty schedule encoding per personnel",
            "2.6.2 One-day override: special class or off-duty",
            "2.6.3 Holiday, suspension, and non-working day recording",
            "2.6.4 Schedule and holidays used for late and off-duty",
        ], """
Late only makes sense if we know when they should be on duty. HR encodes the week, can override one day, and records holidays or suspensions. Schedule plus holidays drive late and off-duty.
"""),
        ("2.7 Leave Request, Approval, and Credits", [
            "2.7.1 Online CS Form No. 6 leave applications",
            "2.7.2 HR approval and disapproval",
            "2.7.3 Initialization and monitoring of VL and SL credits",
            "2.7.4 Deduct credits and post On Leave to the DTR upon approval",
            "2.7.5 Export Application for Leave in Excel and PDF",
        ], """
Leave is CS Form Number 6, online. HR approves or disapproves. VL and SL start at fifteen each and are deducted on approval. We do not invent monthly accrual. Approved leave posts On Leave on the DTR. Export is Excel and PDF.

This answers the Chapter 1 problem of tracking leave credits by hand, separate from the DTR machine.
"""),
        ("2.8 Locator Slip and Travel Order", [
            "2.8.1 Online locator slips and travel orders",
            "2.8.2 HR approval or rejection",
            "2.8.3 Official Business on DTR for approved locator dates; punches kept",
            "2.8.4 Official locator slip and travel order documents",
        ], """
Locator slips and travel orders are also online.

Important: an approved locator marks Official Business on those dates, and the punches stay. A travel order is the document and the workflow. Official Travel on the DTR comes from a leave or travel record, not from clicking Approve on the travel order alone.

If they press this, say it once, clearly, then move on.
"""),
        ("2.9 Automated DTR Generation", [
            "2.9.1 Punches and statuses into Civil Service Form No. 48",
            "2.9.2 Monthly DTR generation per personnel",
            "2.9.3 Bulk DTR for all active personnel",
            "2.9.4 Official PDF layout for printing",
        ], """
Punches and statuses become Civil Service Form Number 48. Monthly, per person, or bulk for all active personnel. Official PDF for printing.

The related literature asked for faster report generation. This is the official DepEd and CSC paper from the live record, not from a dumped machine file.
"""),
        ("2.10 Reporting and Visualization", [
            "2.10.1 Date filters on the dashboard; personnel and month filters on DTR",
            "2.10.2 Leave Excel and PDF; attendance reports in PDF",
            "2.10.3 14-day trend for Present, Late, and Absent",
            "2.10.4 Monthly summary for Present, Late, Absent, and On Leave",
        ], """
Dashboard filters by date. DTR filters by person and month. Leave exports Excel and PDF. Attendance reports are PDF. Charts: a fourteen-day Present, Late, Absent trend, and a monthly doughnut of Present, Late, Absent, and On Leave.

The RRL asked for dashboards that show trends, not only tables. This is our analytics. We do not claim predictive absenteeism or a payroll engine. Those are related studies, not our modules.
"""),
        ("2.11 Notification and Alert", [
            "2.11.1 In-app notices for leave, locator, and travel",
            "2.11.2 Email when a letter is approved or rejected",
            "2.11.3 Personnel status and attendance-related alerts",
            "2.11.4 Staff and admin inbox with read tracking",
        ], """
Staff see in-app notices. When a letter is approved or rejected, they also get email. Inbox tracks what has been read.
"""),
        ("2.12 User Account and Access Control", [
            "2.12.1 Separate Admin and Employee portals",
            "2.12.2 Super Admin, HR Officer, Principal, and staff roles",
            "2.12.3 Staff registration with HR activation or rejection",
            "2.12.4 Password change and password reset",
        ], """
Two doors: Admin and Employee. Roles: Super Admin, HR Officer, Principal, and staff. New staff register; HR activates or rejects. Password change and reset are in the system. Passwords are hashed. We do not display them.

Nurfaizi and Hindarto warned that web attendance systems without access control fail. This module is that control. It also opens Objective 3.
"""),
        ("2.13 Correction, History, and Audit", [
            "2.13.1 Manual attendance correction by authorized administrators",
            "2.13.2 Attendance history and transaction review",
            "2.13.3 Audit logging of leave, locator, travel, and HR actions",
            "2.13.4 Accountability through the audit log",
        ], """
Mistakes happen. Authorized admins can correct attendance. Everything critical, leave, locator, travel, HR actions, goes to the audit log. That is accountability.

The literature talked about tampering and disputes. An audit trail is how the official record stays honest after a human correction.
"""),
    ]

    for i, (title, items, notes) in enumerate(modules, start=11):
        space = 2 if "2.7" in title else 4
        content(prs, "OBJECTIVE 2  ·  MODULES", title, items, i, total, notes, space=space)

    content(
        prs, "OBJECTIVE 3", "Security controls of SMART-DTR",
        [
            "3.1 User authentication and session management",
            "3.2 Role-based authorization",
            "3.3 Biometric attendance security",
            "3.4 Data integrity and centralized storage",
        ],
        24, total, space=8,
        notes="""
A live DTR is useless if anyone can open it.

Objective 3 is security in four layers: login, roles, face and blink, and data integrity. The RRL said attendance data may be used for payroll, compliance, or disputes, so the record must be protected at capture and in storage.
""",
    )

    content(
        prs, "OBJECTIVE 3.1", "User authentication and session",
        [
            "3.1.1 Secure login for admin and staff through separate portals",
            "3.1.2 Hashed password storage and verification",
            "3.1.3 Session handling and logout",
            "3.1.4 No module access without a valid login",
        ],
        25, total, space=8,
        notes="""
Admin and staff have separate portals. Passwords are hashed. Session in, logout out. No login, no module.

If they ask to see passwords or Gmail lists: no. Hashed storage is the point of this objective.
""",
    )

    content(
        prs, "OBJECTIVE 3.2", "Role-based authorization",
        [
            "3.2.1 Super Admin full-access management",
            "3.2.2 HR: personnel, letters, 201 files, DTR, correction, and audit",
            "3.2.3 Principal views monitoring and DTR; staff use the Employee Portal",
            "3.2.4 Permission checks block functions outside the assigned role",
        ],
        26, total, space=8,
        notes="""
Super Admin manages the whole system. HR handles personnel, letters, 201 files, DTR, correction, and audit. The Principal monitors and views DTR. Staff use the Employee Portal. Permission checks block anything outside the role.

Role-based access is named in our definition of terms and in the related security literature.
""",
    )

    content(
        prs, "OBJECTIVE 3.3", "Biometric attendance security",
        [
            "3.3.1 Face recognition on the Pi before a punch is recorded",
            "3.3.2 Blink detection to confirm a live person",
            "3.3.3 Reject no-blink, low confidence, unknown ID, or photo attempts",
            "3.3.4 Each accepted scan is bound to a personnel record",
        ],
        27, total, space=8,
        notes="""
The Pi must match a face and a blink before a punch is saved. Photo, unknown ID, weak confidence: no record. Every accepted scan is bound to a real personnel ID.

This is the spoofing gap from the RRL, closed at the kiosk, not after the fact.
""",
    )

    content(
        prs, "OBJECTIVE 3.4", "Data integrity and centralized storage",
        [
            "3.4.1 Validate blink, confidence, and personnel ID before saving",
            "3.4.2 Central storage of personnel, attendance, leave, locator, and travel",
            "3.4.3 Only permitted roles may change personnel and attendance records",
            "3.4.4 Audit trail of critical HR and attendance changes",
        ],
        28, total, space=8,
        notes="""
The server validates blink, confidence, and personnel ID again before saving. Personnel, attendance, leave, locator, and travel live in one database. Only permitted roles can change them. The audit trail remembers who changed what.

Marcella and Hocky and similar studies argued for centralization so departments stop reconciling conflicting copies. One school database is our answer. We are not a public cloud. We run on the school LAN.
""",
    )

    content(
        prs, "SCOPE AND LIMITATIONS", "What this study covers",
        [
            "INHS Senior High teaching and non-teaching personnel only",
            "Design, develop, and test SMART-DTR for the HR office",
            "Raspberry Pi kiosk plus the web system on the school LAN",
            "Face recognition with blink liveness at the doorway",
            "Attendance monitoring and selected HRIS functions",
        ],
        29, total, space=4,
        notes="""
After the objectives, we bound the work. Scope first.

This study is for Isabela National High School, Senior High School, teaching and non-teaching personnel. Not other campuses. Not other DepEd offices.

We analyzed the current process, then designed, developed, and tested SMART-DTR for the HR office. Hardware is a Raspberry Pi with a camera. Software is the web system on the school LAN. The kiosk uses face recognition and blink liveness. The laptop holds the official record.

The paper is clear: this is attendance monitoring plus selected HRIS functions, not a full government payroll suite.

Catchy line: we built a school DTR and HR desk, not a system for the entire division.
""",
    )

    content(
        prs, "SCOPE OF THE SYSTEM", "What SMART-DTR actually includes",
        [
            "201 file, weekly schedule, holidays, and daily status",
            "Online CS Form 6, locator slip, and travel order",
            "Live dashboard and Civil Service Form 48 PDF",
            "Admin and Employee portals with assigned roles",
            "Hashed login, blink check, and an audit trail",
        ],
        30, total, space=4,
        notes="""
Say what is in the live system. Do not read the manuscript's extra module list if it is not built.

In scope: personnel and 201 files, weekly schedule and holidays, daily status from punches and letters, CS Form 6 leave, locator and travel documents, live dashboard, Form 48 PDF, leave Excel and PDF, Admin and Employee portals, roles, hashed passwords, face and blink, and the audit log.

If a panelist opens the old scope paragraph: we do not claim a hallway television display, a backup module, automatic monthly leave accrual, payroll, or department Excel dumps. Those lines in the draft manuscript are not the delivered system.

Leave credits are initialized, then deducted when leave is approved. Department sits on the staff account. Attendance reports are PDF.
""",
    )

    content(
        prs, "LIMITATIONS", "People, payroll, and old records",
        [
            "Other schools and other offices are not included",
            "No payroll, tax, SSS, PhilHealth, Pag-IBIG, or benefits",
            "No dedicated mobile application",
            "Old DTR-machine and USB files are not auto-imported",
            "Leave credits are initialized, not auto-accrued monthly",
        ],
        31, total, space=4,
        notes="""
Limitations from the paper, stated as the live system really works.

One. INHS-SHS only.

Two. No payroll, salary, tax, SSS, PhilHealth, Pag-IBIG, benefits, or remittances. The RRL talks about payroll errors in other countries. We did not build a payroll engine.

Three. Web application only. No dedicated mobile app. Staff may open the site in a phone browser on the school network, but that is not a separate app.

Four. History from before SMART-DTR is out of scope. Old machine dumps and USB files are not imported automatically. An authorized admin may encode needed history by hand.

Five. VL and SL start at fifteen. They move when leave is approved. There is no automatic monthly accrual module.

If they ask why: we stayed on Form 48, Form 6, and live attendance, the school's real bottleneck.
""",
    )

    content(
        prs, "LIMITATIONS", "Device, network, and the face",
        [
            "Lighting, camera angle, and face cover can affect recognition",
            "Needs the Pi, camera, power, and the school LAN",
            "Power cuts or Wi-Fi drops can delay a punch",
            "The laptop is the official server, not a public cloud",
            "Travel-order Approve does not by itself write Official Travel",
        ],
        32, total, space=4,
        notes="""
Technical limits, from both the paper and the hardware we actually run.

Face and blink can fail in poor light, a bad camera angle, masks, thick glare on glasses, or a sudden change in appearance. The person should face the kiosk and blink.

The system needs the Raspberry Pi, the camera, power, and the school network. If the power dies or Wi-Fi drops, a punch can wait until the kiosk finds the laptop again.

We are not a public cloud. The laptop is the official server on the LAN or a phone hotspot.

Last line, say it once: approving a travel order prints the document. Official Travel on the DTR comes from the leave or travel record. An approved locator writes Official Business and keeps the punches.

Then go to the demonstration. Scope told them the fence. Demo shows what is inside it.
""",
    )

    content(
        prs, "LIVE DEMONSTRATION", "System demo in objective order",
        [
            "1. Accounts and dashboard",
            "2. Face and blink punch — no USB dump",
            "3. Schedule and holidays",
            "4. Leave or locator approval",
            "5. Civil Service Form 48 PDF",
            "6. Audit log and logout",
        ],
        33, total, space=4,
        notes="""
I will now show the system in the same order as the objectives. Keep each step under a minute.

One. Accounts and dashboard. This is the Admin portal. I am logged in as HR. Today's board is live, not last week's USB file. If the kiosk is running, Pi Online is a real heartbeat.

Two. On the kiosk: look, blink, done. That punch is already in the database. Nobody walks a flash drive across the office.

Three. Here is the duty week. Late is measured against this schedule, not against a handwritten note.

Four. If leave: CS Form 6. On approve, credits move, and the DTR shows On Leave. If locator: approved locator. Those dates become Official Business. If they still punched, the punches remain.

Five. This is the official DTR PDF, Civil Service Form 48, from the live record.

Six. That approval is in the audit log. I will now log out. Without a session, the modules close.
""",
    )

    s = prs.slides.add_slide(prs.slide_layouts[6])
    rect(s, Inches(0), Inches(0), Inches(13.333), Inches(7.5), NAVY_DARK)
    rect(s, Inches(0), Inches(0), Inches(0.18), Inches(7.5), GOLD)
    rect(s, Inches(0), Inches(6.95), Inches(13.333), Inches(0.55), GOLD)
    textbox(s, Inches(0.55), Inches(2.15), Inches(12.2), Inches(0.9), "Thank You", 40, True, WHITE)
    textbox(
        s, Inches(0.55), Inches(3.2), Inches(12.2), Inches(2.2),
        "From DTR machine and flash drive\nto a live database and Civil Service Form 48",
        40, False, GOLD,
    )
    textbox(s, Inches(0.55), Inches(7.02), Inches(12.2), Inches(0.4),
            "Questions are welcome.", 18, True, NAVY_DARK)
    add_notes(s, """
SMART-DTR is the school's answer to a simple problem: attendance trapped in a machine until a flash drive sets it free.

We analyzed that process, built a live system around face, blink, letters, and Form 48, and locked it with login, roles, and audit.

From DTR machine and flash drive, to a live database and Civil Service Form 48.

Thank you. I am ready for your questions.

If they interrupt later:
Laravel? Yes. Official record is Laravel and MySQL on the laptop. The Pi is the face kiosk.
Internet? School LAN. The laptop is the server.
Travel order equals Official Travel on DTR? No. Travel order is the paper trail. Official Travel on DTR comes from the leave or travel record. Locator approval becomes Official Business. Punches stay.
Monthly leave credits? No automatic monthly accrual. VL and SL are initialized at fifteen and deducted when leave is approved.
Questionnaire for Objective 1? No. Objective 1 is the existing DTR machine and USB retrieval.
Passwords? No. They are hashed. That is Objective 3.
""")

    prs.save(OUT)
    print(OUT, "slides:", total)


if __name__ == "__main__":
    main()
