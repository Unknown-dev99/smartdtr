@php
    $staff = $request->staffUser;
    $position = $request->position_designation
        ?: ($staff->department ?: ($staff->personnel?->role ?? $staff->roleLabel()));
    $schoolHead = config('smartdtr.locator_school_head', 'PRISCA ROMELA C. BUENAVENTURA');
    $sdsName = config('smartdtr.locator_sds_name', 'EDUARDO C. ESCORPISO JR., EdD, CESO V');
    $sdsTitle = config('smartdtr.locator_sds_title', 'Schools Division Superintendent');
    $approvedDate = $request->reviewed_at?->format('F d, Y') ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Authority for Official Local Travel</title>
    <style>
        @page { size: A4; margin: 12mm 14mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 8px 12px 10px;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            background: #fff;
        }
        .header { text-align: center; margin-bottom: 6px; }
        .header img.seal {
            width: 78px;
            height: auto;
            display: block;
            margin: 0 auto 2px;
        }
        .header .republic {
            font-size: 11pt;
            margin: 0;
            line-height: 1.15;
        }
        .header .deped {
            font-family: "Old English Text MT", "Times New Roman", serif;
            font-size: 18pt;
            font-weight: bold;
            margin: 0;
            line-height: 1.15;
        }
        .header .office {
            font-size: 10.5pt;
            margin: 0;
            line-height: 1.25;
        }
        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 14px 0 12px;
            letter-spacing: 0.3px;
        }
        table.form {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        table.form td {
            border: 1px solid #000;
            padding: 7px 9px;
            vertical-align: top;
            font-size: 11pt;
        }
        table.form .label {
            width: 34%;
            font-weight: bold;
        }
        .purpose-sub {
            font-weight: normal;
            font-size: 9.5pt;
            display: block;
        }
        .attest {
            margin: 14px 0 8px;
            font-style: italic;
            text-align: justify;
            font-size: 10.5pt;
            line-height: 1.35;
        }
        .sig-block {
            margin: 18px 0 8px;
            width: 100%;
        }
        .sig-line {
            display: inline-block;
            width: 62%;
            border-bottom: 1px solid #000;
            text-align: center;
            min-height: 20px;
            font-weight: bold;
            padding-bottom: 2px;
        }
        .sig-date {
            display: inline-block;
            width: 30%;
            float: right;
            border-bottom: 1px solid #000;
            text-align: center;
            min-height: 20px;
            padding-bottom: 2px;
        }
        .sig-labels {
            clear: both;
            margin-top: 3px;
            font-size: 10pt;
        }
        .sig-labels .left { display: inline-block; width: 62%; }
        .sig-labels .right { display: inline-block; width: 30%; float: right; text-align: left; }
        .clear { clear: both; }
        .approved {
            margin-top: 18px;
            font-weight: bold;
            font-size: 12pt;
        }
        .sds {
            text-align: center;
            margin-top: 22px;
        }
        .sds-name {
            font-weight: bold;
            font-size: 12pt;
            margin: 0;
        }
        .sds-title {
            margin: 0 0 10px;
            font-size: 11pt;
        }
        .sds-date-wrap {
            width: 42%;
            margin-left: auto;
            text-align: left;
        }
        .sds-date-line {
            border-bottom: 1px solid #000;
            min-height: 18px;
            text-align: center;
            margin-bottom: 2px;
        }
        .footer {
            margin-top: 28px;
            width: 100%;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            border-collapse: collapse;
        }
        .footer td { border: none; vertical-align: bottom; padding: 0; }
        .footer-logos img {
            height: 54px;
            width: auto;
            max-width: 300px;
            object-fit: contain;
        }
        .footer-info {
            text-align: right;
            font-size: 9pt;
            line-height: 1.45;
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
        }
        .footer-info .row { margin: 0; }
        .no-print { margin-bottom: 10px; }
        .no-print button {
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 8px 14px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .footer { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="header">
        <img class="seal" src="{{ asset('images/deped-seal.png') }}" alt="DepEd Seal">
        <p class="republic">Republic of the Philippines</p>
        <p class="deped">Department of Education</p>
        <p class="office">REGION II – CAGAYAN VALLEY</p>
        <p class="office">SCHOOLS DIVISION OF THE CITY OF ILAGAN</p>
        <p class="office"><strong>ISABELA NATIONAL HIGH SCHOOL</strong></p>
        <p class="office">SAN VICENTE, CITY OF ILAGAN, ISABELA</p>
    </div>

    <p class="title">TRAVEL AUTHORITY FOR OFFICIAL LOCAL TRAVEL</p>

    <table class="form">
        <tr>
            <td class="label">NAME</td>
            <td>{{ $staff->name }}</td>
        </tr>
        <tr>
            <td class="label">Position/Designation</td>
            <td>{{ $position }}</td>
        </tr>
        <tr>
            <td class="label">Permanent Station</td>
            <td>Isabela National High School</td>
        </tr>
        <tr>
            <td class="label">
                Purpose of Travel
                <span class="purpose-sub">(must be supported by attachments)</span>
            </td>
            <td>{{ $request->purpose }}</td>
        </tr>
        <tr>
            <td class="label">Host of Activity</td>
            <td>{{ $request->host_of_activity ?: '' }}</td>
        </tr>
        <tr>
            <td class="label">Inclusive Dates</td>
            <td>{{ $request->inclusiveDatesLabel() }}</td>
        </tr>
        <tr>
            <td class="label">Destination</td>
            <td>{{ $request->destination }}</td>
        </tr>
        <tr>
            <td class="label">Fund Source</td>
            <td>{{ $request->fund_source ?: '' }}</td>
        </tr>
    </table>

    <p class="attest">
        I hereby attest that the information in this form and in the supporting documents attached hereto are true and correct.
    </p>

    <div class="sig-block">
        <div class="sig-line">{{ $staff->name }}</div>
        <div class="sig-date">{{ $request->created_at?->format('F d, Y') }}</div>
        <div class="clear"></div>
        <div class="sig-labels">
            <span class="left">Name and Signature of Requesting Employee</span>
            <span class="right">Date</span>
        </div>
        <div class="clear"></div>
    </div>

    <p class="attest">
        This is to certify that the trip of the requesting employee satisfies all the minimum conditions for authorized official travel and that alternatives to travel are insufficient for purpose stated herein.
    </p>

    <div class="sig-block">
        <div class="sig-line">{{ $schoolHead }}</div>
        <div class="sig-date">{{ $approvedDate }}</div>
        <div class="clear"></div>
        <div class="sig-labels">
            <span class="left">School Head</span>
            <span class="right">Date</span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="approved">APPROVED</div>

    <div class="sds">
        <p class="sds-name">{{ $sdsName }}</p>
        <p class="sds-title">{{ $sdsTitle }}</p>
        <div class="sds-date-wrap">
            <div class="sds-date-line">{{ $approvedDate }}</div>
            <div>Date</div>
        </div>
    </div>

    <table class="footer">
        <tr>
            <td class="footer-logos">
                <img src="{{ asset('images/travel-footer-logos.png') }}" alt="DepEd MATATAG, Bagong Pilipinas, INHS">
            </td>
            <td class="footer-info">
                <p class="row">Claravall St., San Vicente, City of Ilagan, Isabela</p>
                <p class="row">Telephone No.: (078) 624-1159</p>
                <p class="row">Isabela National High School – City of Ilagan</p>
                <p class="row">300542@deped.gov.ph</p>
            </td>
        </tr>
    </table>
</body>
</html>
