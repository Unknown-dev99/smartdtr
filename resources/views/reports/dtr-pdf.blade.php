<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DTR - {{ $personnel->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: center; }
        h2, h3 { text-align: center; margin: 4px 0; }
        .header { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Civil Service Form No. 48</h2>
        <h3>DAILY TIME RECORD</h3>
        <p><strong>Employee No.:</strong> {{ $personnel->employee_number ?? '' }}</p>
        <p><strong>Name:</strong> {{ $personnel->name }}</p>
        <p><strong>{{ $monthLabel }}</strong></p>
        <p><strong>Regular Hours:</strong> {{ $personnel->official_hours_regular }}</p>
        <p><strong>Saturday Hours:</strong> {{ $personnel->official_hours_saturday }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Day</th>
                <th>AM Arrival</th>
                <th>AM Departure</th>
                <th>PM Arrival</th>
                <th>PM Departure</th>
            </tr>
        </thead>
        <tbody>
            @for($day = 1; $day <= $daysInMonth; $day++)
            <tr>
                <td>{{ $day }}</td>
                <td>{{ $dayMap[$day]['am_arrival'] ?? '' }}</td>
                <td>{{ $dayMap[$day]['am_departure'] ?? '' }}</td>
                <td>{{ $dayMap[$day]['pm_arrival'] ?? '' }}</td>
                <td>{{ $dayMap[$day]['pm_departure'] ?? '' }}</td>
            </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
