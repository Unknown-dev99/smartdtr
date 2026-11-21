<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Leave Card - {{ $personnel->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        h1, h2, p { margin: 0; }
        h1 { font-size: 16px; letter-spacing: 0.04em; text-align: center; }
        .center { text-align: center; }
        .muted { color: #444; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        th, td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; }
        th { background: #f3f4f6; }
        .meta { width: 100%; margin-top: 12px; font-size: 12px; }
        .meta td { border: none; padding: 2px 8px 2px 0; }
        .signs { width: 100%; margin-top: 28px; font-size: 12px; }
        .signs td { border: none; width: 33%; vertical-align: top; }
        .line { margin-top: 28px; font-weight: bold; }
        @page { size: A4 landscape; margin: 8mm; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <p class="center muted">Republic of the Philippines</p>
    <p class="center muted">Department of Education</p>
    <p class="center muted">Region II - Schools Division of the City of Ilagan</p>
    <p class="center muted">Isabela National High School</p>
    <h1 style="margin-top:10px;">EMPLOYEE'S LEAVE CARDS</h1>

    <table class="meta">
        <tr>
            <td>Name: <strong>{{ $personnel->name }}</strong></td>
            <td>Civil status: <strong>{{ $card->civil_status }}</strong></td>
            <td>GSIS Policy No.: <strong>{{ $card->gsis_policy_no }}</strong></td>
        </tr>
        <tr>
            <td>Position: <strong>{{ $personnel->role }}</strong></td>
            <td>Entrance to duty: <strong>{{ $card->entrance_to_duty?->format('F j, Y') }}</strong></td>
            <td>TIN: <strong>{{ $card->tin }}</strong></td>
        </tr>
        <tr>
            <td>Status: <strong>{{ $card->field('employment_status') }}</strong></td>
            <td>Unit: <strong>{{ $card->field('unit') }}</strong></td>
            <td>National Reference Card No.: <strong>{{ $card->national_reference_no }}</strong></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2">PERIOD</th>
                <th rowspan="2">PARTICULARS</th>
                <th colspan="4">VACATION LEAVE</th>
                <th colspan="4">SICK LEAVE</th>
                <th rowspan="2">REMARKS</th>
            </tr>
            <tr>
                <th>EARNED</th>
                <th>Absence Undertime W/ Pay</th>
                <th>BALANCE</th>
                <th>Absence Undertime W/o Pay</th>
                <th>EARNED</th>
                <th>Absence Undertime W/ Pay</th>
                <th>BALANCE</th>
                <th>Absence Undertime W/o Pay</th>
            </tr>
        </thead>
        <tbody>
            @forelse($months as $row)
            <tr>
                <td>{{ $row['period'] }}</td>
                <td>{{ $row['particulars'] }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($row['earned']) }}</td>
                <td>{{ $row['vl_with_pay'] > 0 ? \App\Services\LeaveCardService::formatCredit($row['vl_with_pay']) : '' }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($row['vl_balance']) }}</td>
                <td>{{ $row['vl_without_pay'] > 0 ? \App\Services\LeaveCardService::formatCredit($row['vl_without_pay']) : '' }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($row['earned']) }}</td>
                <td>{{ $row['sl_with_pay'] > 0 ? \App\Services\LeaveCardService::formatCredit($row['sl_with_pay']) : '' }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($row['sl_balance']) }}</td>
                <td>{{ $row['sl_without_pay'] > 0 ? \App\Services\LeaveCardService::formatCredit($row['sl_without_pay']) : '' }}</td>
                <td>{{ $row['remarks'] }}</td>
            </tr>
            @empty
            <tr><td colspan="11">Set the entrance to duty before printing.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 style="margin-top:22px; font-size:14px; text-align:center;">RECORD OF LEAVE CREDITS</h2>
    <table>
        <thead>
            <tr>
                <th colspan="2">PERIOD</th>
                <th colspan="3">VACATION LEAVE</th>
                <th colspan="3">SICK LEAVE</th>
                <th rowspan="2">REMARKS</th>
            </tr>
            <tr>
                <th>FROM</th>
                <th>TO</th>
                <th>EARNED</th>
                <th>USED</th>
                <th>BALANCE</th>
                <th>EARNED</th>
                <th>USED</th>
                <th>BALANCE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($years as $year)
            <tr>
                <td>{{ $year['from']->format('m/d/Y') }}</td>
                <td>{{ $year['to']->format('m/d/Y') }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($year['vl_earned']) }}</td>
                <td>{{ $year['vl_used'] > 0 ? \App\Services\LeaveCardService::formatCredit($year['vl_used']) : '' }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($year['vl_balance']) }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($year['sl_earned']) }}</td>
                <td>{{ $year['sl_used'] > 0 ? \App\Services\LeaveCardService::formatCredit($year['sl_used']) : '' }}</td>
                <td>{{ \App\Services\LeaveCardService::formatCredit($year['sl_balance']) }}</td>
                <td>{{ $year['remarks'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signs">
        <tr>
            <td>Prepared by:<div class="line">{{ $card->field('prepared_by') }}</div><div>{{ $card->field('prepared_title') }}</div></td>
            <td>Approved:<div class="line">{{ $card->field('approved_by') }}</div><div>{{ $card->field('approved_title') }}</div></td>
            <td>Noted:<div class="line">{{ $card->field('noted_by') }}</div><div>{{ $card->field('noted_title') }}</div></td>
        </tr>
    </table>
</body>
</html>
