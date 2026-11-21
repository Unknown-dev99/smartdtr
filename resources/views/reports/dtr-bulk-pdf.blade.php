<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DTR All — {{ $monthLabel }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        .dtr-page { page-break-after: always; }
        .dtr-page:last-child { page-break-after: auto; }
        h1 { text-align: center; font-size: 12px; margin: 0 0 2px; font-weight: normal; }
        h2 { text-align: center; font-size: 14px; margin: 0 0 8px; letter-spacing: 0.5px; }
        .meta { margin-bottom: 8px; }
        .name { text-align: center; font-size: 13px; font-weight: bold; margin: 4px 0 0; }
        .name-sub { text-align: center; font-size: 8px; color: #444; margin: 0 0 6px; }
        .month { text-align: center; font-size: 11px; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #222; padding: 3px 4px; text-align: center; font-size: 9px; }
        th { background: #f0f0f0; font-weight: bold; }
        .cert { margin-top: 10px; font-size: 9px; line-height: 1.35; }
        .sign { text-align: center; margin-top: 14px; }
        .sign-line { margin-top: 18px; }
        .verify { margin-top: 12px; font-size: 9px; }
    </style>
</head>
<body>
@foreach($records as $record)
    <div class="dtr-page">
        <h1>Civil Service Form No. 48</h1>
        <h2>DAILY TIME RECORD</h2>
        <div class="meta">
            Employee No.: {{ $record['personnel']->employee_number ?: '—' }}
        </div>
        <div class="name">{{ $record['personnel']->name }}</div>
        <div class="name-sub">(Name)</div>
        <div class="month">{{ $monthLabel }}</div>
        <div class="meta">
            Official hours — Regular days: {{ $record['regular'] }}<br>
            Saturdays: {{ $record['saturday'] }}
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:10%">Day</th>
                    <th>AM Arrival</th>
                    <th>AM Departure</th>
                    <th>PM Arrival</th>
                    <th>PM Departure</th>
                </tr>
            </thead>
            <tbody>
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $d = $record['dayMap'][$day] ?? [];
                        $amIn = $d['am_arrival'] ?? '';
                        $amOut = $d['am_departure'] ?? '';
                        $pmIn = $d['pm_arrival'] ?? '';
                        $pmOut = $d['pm_departure'] ?? '';
                        if ($amIn === '' && $amOut === '' && $pmIn === '' && $pmOut === '' && !empty($d['label'])) {
                            if (! in_array($d['label'], ['Saturday', 'Sunday'], true)) {
                                $amIn = $d['label'];
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $day }}</td>
                        <td>{{ $amIn }}</td>
                        <td>{{ $amOut }}</td>
                        <td>{{ $pmIn }}</td>
                        <td>{{ $pmOut }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>
        <div class="cert">
            I certify on my honor that the above is a true and correct report of the hours of work performed,
            record of which was made daily at the time of arrival and departure from office.
        </div>
        <div class="sign">
            <div class="sign-line">{{ $record['personnel']->name }}</div>
            <div>______________________________</div>
        </div>
        <div class="verify">
            VERIFIED as to the prescribed office hours:<br><br>
            <div class="sign">
                <strong>{{ $verifier }}</strong><br>
                (In Charge)
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
