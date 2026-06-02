<!DOCTYPE html>
<html>
<head>
    <title>Your Verification Code</title>
</head>
<body>
    <h1>Email Verification</h1>
    <p>Dear {{ $name }},</p>
    <p>Your verification code is:</p>
    <p style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #007bff;">{{ $otp }}</p>
    <p>This code will expire in <strong>10 minutes</strong>.</p>
    <p>If you did not create an account, no further action is required.</p>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>

