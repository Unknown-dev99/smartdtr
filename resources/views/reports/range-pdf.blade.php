<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report {{ $from }} — {{ $to }}</title>
    <style>
        @page { margin: 18px 16px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111; margin: 0; }
        h1 { font-size: 14px; margin: 0 0 2px; text-align: center; }
        h2 { font-size: 11px; margin: 0 0 8px; text-align: center; font-weight: normal; }
        .meta { text-align: center; color: #333; margin: 0 0 10px; font-size: 9px; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        table.matrix {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.matrix th, table.matrix td {
            border: 0.6px solid #222;
            padding: 4px 3px;
            vertical-align: middle;
        }
        table.matrix th {
            font-weight: bold;
            text-align: center;
            background: #efefef;
            font-size: 8px;
        }
        table.matrix th.name-head { width: 22%; }
        table.matrix td.name { text-align: left; font-size: 8.5px; font-weight: bold; }
        table.matrix td.role { font-size: 7px; color: #444; font-weight: normal; }
        table.matrix td.time { text-align: center; font-size: 8px; white-space: nowrap; }
        table.summary { width: 70%; margin: 12px auto; border-collapse: collapse; }
        table.summary th, table.summary td {
            border: 0.6px solid #222;
            padding: 6px 10px;
            font-size: 10px;
        }
        table.summary th { background: #efefef; text-align: left; width: 70%; }
        table.summary td { text-align: right; font-weight: bold; }
        .note { width: 70%; margin: 14px auto 0; font-size: 8px; color: #444; line-height: 1.4; }
        .sheet-head { margin-bottom: 8px; }
        .sheet-head .left { float: left; font-size: 9px; }
        .sheet-head .right { float: right; font-size: 9px; }
        .sheet-head:after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>
    <div class="page">
        <h1>{{ $layout['school'] }}</h1>
        <h2>SMART-DTR Attendance Report — School Summary</h2>
        <p class="meta">{{ $layout['from_label'] }} — {{ $layout['to_label'] }} &nbsp;|&nbsp; {{ $layout['personnel_count'] }} personnel</p>
        <table class="summary">
            <tr><th>Present (person-days)</th><td>{{ $layout['summary']['Present'] }}</td></tr>
            <tr><th>Late</th><td>{{ $layout['summary']['Late'] }}</td></tr>
            <tr><th>Absent</th><td>{{ $layout['summary']['Absent'] }}</td></tr>
            <tr><th>On Leave</th><td>{{ $layout['summary']['On Leave'] }}</td></tr>
            <tr><th>Official Travel</th><td>{{ $layout['summary']['Official Travel'] }}</td></tr>
            <tr><th>Official Business</th><td>{{ $layout['summary']['Official Business'] }}</td></tr>
            <tr><th>Off-duty</th><td>{{ $layout['summary']['Off-duty'] }}</td></tr>
        </table>
        <p class="note">
            This PDF is a weekly print sheet for school monitoring. Each following page covers up to 7 days
            and 28 names so 85 employees stay readable on legal landscape paper.
            Codes with no punch: A = Absent, L = On Leave, OT = Official Travel, OB = Official Business, OD = Off-duty.
            Official signed copies remain Civil Service Form No. 48 (DTR Export).
        </p>
    </div>

    @foreach($layout['sheets'] as $sheet)
    <div class="page">
        <h1>{{ $layout['school'] }}</h1>
        <h2>Weekly Attendance &nbsp;·&nbsp; {{ $sheet['week_label'] }}</h2>
        <div class="sheet-head">
            <span class="left">{{ $sheet['name_label'] }}</span>
            <span class="right">Week {{ $sheet['week_no'] }} of {{ $sheet['week_count'] }}</span>
        </div>
        <table class="matrix">
            <thead>
                <tr>
                    <th class="name-head" rowspan="2">NAME</th>
                    @foreach($sheet['dates'] as $day)
                        <th colspan="2">{{ $day['weekday'] }}<br>{{ $day['short'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($sheet['dates'] as $day)
                        <th>IN</th>
                        <th>OUT</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($sheet['people'] as $person)
                <tr>
                    <td class="name">
                        {{ $person['name'] }}
                        @if($person['role'])
                            <div class="role">{{ $person['role'] }}</div>
                        @endif
                    </td>
                    @foreach($sheet['dates'] as $day)
                        @php $cell = $person['days'][$day['date']] ?? ['in' => '', 'out' => '']; @endphp
                        <td class="time">{{ $cell['in'] }}</td>
                        <td class="time">{{ $cell['out'] }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</body>
</html>
