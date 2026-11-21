<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DTR — {{ $monthLabel }}</title>
    <style>
        @page { margin: 8px 10px; size: 13in 8.5in landscape; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Times New Roman, serif;
            font-size: 5.8px;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .sheet {
            page-break-after: always;
            width: 100%;
        }
        .sheet:last-child { page-break-after: auto; }
        .forms {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .forms > tbody > tr > td.form {
            width: 25%;
            vertical-align: top;
            padding: 0 3px;
            border-left: 1px dashed #c62828;
        }
        .forms > tbody > tr > td.form:first-child { border-left: none; }
        .forms > tbody > tr > td.empty {
            width: 25%;
            border: none;
            padding: 0;
        }
        .title-sm { font-size: 5.5px; margin: 0; text-transform: uppercase; }
        .title-lg {
            font-family: Times New Roman, DejaVu Serif, serif;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            margin: 1px 0 2px;
            letter-spacing: 0.4px;
        }
        .name { font-size: 7px; font-weight: bold; text-align: center; margin: 2px 0 0; }
        .name-sub { font-size: 5px; text-align: center; margin: 0 0 2px; }
        .month { font-size: 6px; text-align: left; margin: 0 0 1px; }
        .hours { font-size: 5.2px; margin: 0 0 1px; line-height: 1.15; }
        table.grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        table.grid th, table.grid td {
            border: 0.55px solid #000;
            padding: 0 1px;
            text-align: center;
            font-size: 5px;
            height: 8.2px;
            vertical-align: middle;
        }
        table.grid th { font-weight: bold; }
        .day-col { width: 8%; }
        .cert { font-size: 4.6px; margin-top: 3px; line-height: 1.2; text-align: justify; }
        .sign { text-align: center; margin-top: 6px; font-size: 5.5px; font-weight: bold; }
        .line { text-align: center; margin-top: 1px; font-size: 5px; }
        .verify { font-size: 4.8px; margin-top: 5px; }
        .incharge { text-align: center; margin-top: 6px; font-size: 5.5px; font-weight: bold; }
        .incharge-sub { text-align: center; font-size: 5px; }
    </style>
</head>
<body>
@foreach($records as $record)
@php
    $p = $record['personnel'];
    $dayMap = $record['dayMap'];
@endphp
<div class="sheet">
    <table class="forms">
        <tr>
            @for($copy = 0; $copy < 2; $copy++)
            <td class="form">
                <div class="title-sm">Civil Service Form No. 48</div>
                <div class="title-lg">DAILY TIME RECORD</div>
                <div class="name">{{ $p->name }}</div>
                <div class="name-sub">(Name)</div>
                <div class="month">{{ $monthLabel }}</div>
                <div class="hours">Official hours of arrival and departure</div>
                <div class="hours">Regular days: {{ $record['regular'] }} &nbsp;&nbsp; Saturdays: {{ $record['saturday'] }}</div>

                <table class="grid">
                    <thead>
                        <tr>
                            <th class="day-col" rowspan="2">Day</th>
                            <th colspan="2">A.M.</th>
                            <th colspan="2">P.M.</th>
                            <th colspan="2">OVERTIME</th>
                        </tr>
                        <tr>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($day = 1; $day <= 31; $day++)
                            @php
                                $amIn = $amOut = $pmIn = $pmOut = $otIn = $otOut = '';
                                if ($day <= $daysInMonth) {
                                    $d = $dayMap[$day] ?? [];
                                    $amIn = $d['am_arrival'] ?? '';
                                    $amOut = $d['am_departure'] ?? '';
                                    $pmIn = $d['pm_arrival'] ?? '';
                                    $pmOut = $d['pm_departure'] ?? '';
                                    $otIn = $d['ot_arrival'] ?? '';
                                    $otOut = $d['ot_departure'] ?? '';
                                    if ($amIn === '' && $amOut === '' && $pmIn === '' && $pmOut === '' && !empty($d['label'])) {
                                        if (! in_array($d['label'], ['Saturday', 'Sunday'], true)) {
                                            $amIn = $d['label'];
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $day <= $daysInMonth ? $day : '' }}</td>
                                <td>{{ $amIn }}</td>
                                <td>{{ $amOut }}</td>
                                <td>{{ $pmIn }}</td>
                                <td>{{ $pmOut }}</td>
                                <td>{{ $otIn ?? '' }}</td>
                                <td>{{ $otOut ?? '' }}</td>
                            </tr>
                        @endfor
                        <tr>
                            <td colspan="7" style="text-align:left;padding-left:3px;"><strong>TOTAL :</strong></td>
                        </tr>
                    </tbody>
                </table>

                <div class="cert">
                    I CERTIFY on my honor that the above is a true and correct report of hours of worked performed, record of which was made daily at the time of arrival at and departure from office.
                </div>
                <div class="sign">{{ $p->name }}</div>
                <div class="line">____________________________</div>
                <div class="verify">Verified as to the prescribed office hours.</div>
                <div class="incharge">{{ $verifier }}</div>
                <div class="incharge-sub">(In Charge)</div>
            </td>
            @endfor
            <td class="empty"></td>
            <td class="empty"></td>
        </tr>
    </table>
</div>
@endforeach
</body>
</html>
