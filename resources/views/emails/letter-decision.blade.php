<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $letterType }} {{ $status === 'approved' ? 'Approved' : 'Rejected' }}</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #263238; max-width: 600px; margin: 0 auto; padding: 24px; background: #F5F7FA;">
    <div style="background: #0D47A1; color: white; padding: 20px; border-radius: 12px 12px 0 0;">
        <h1 style="margin: 0; font-size: 18px;">INHS SMART-DTR</h1>
        <p style="margin: 4px 0 0; opacity: 0.85; font-size: 13px;">Isabela National High School</p>
    </div>
    <div style="background: #FFFFFF; border: 1px solid rgba(13,71,161,0.12); border-top: none; padding: 24px; border-radius: 0 0 12px 12px;">
        <p style="margin: 0 0 12px;">Dear {{ $employeeName }},</p>
        @if($status === 'approved')
            <p style="margin: 0 0 16px; padding: 10px 14px; background: #E8F5E9; color: #1B5E20; border-radius: 8px; font-weight: 700;">
                Your {{ $letterType }} has been approved.
            </p>
        @else
            <p style="margin: 0 0 16px; padding: 10px 14px; background: #FFEBEE; color: #B71C1C; border-radius: 8px; font-weight: 700;">
                Your {{ $letterType }} was not approved.
            </p>
        @endif
        <p style="margin: 0 0 8px; color: #455A64;">{{ $details }}</p>
        @if($adminNotes)
            <p style="margin: 12px 0 0; color: #455A64;"><strong>Note from admin:</strong> {{ $adminNotes }}</p>
        @endif
        @if($status === 'approved')
            <p style="margin: 16px 0; color: #455A64;">You may now open the employee portal to view or print the approved form.</p>
        @endif
        @if($url)
            <a href="{{ $url }}" style="display: inline-block; background: #0D47A1; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold;">Open SMART-DTR</a>
        @endif
    </div>
    <p style="text-align: center; font-size: 11px; color: #90A4AE; margin-top: 16px;">This is an automated Gmail notice from SMART-DTR. Please do not reply.</p>
</body>
</html>
