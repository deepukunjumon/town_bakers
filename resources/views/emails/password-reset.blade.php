<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body>
    <h1>Password Reset Request</h1>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    
    <p>Your password reset token is: <strong>{{ $token }}</strong></p>
    
    <p>To reset your password, use this token in the password reset form.</p>
    
    <p>This password reset token will expire in 60 minutes.</p>
    
    <p>If you did not request a password reset, no further action is required.</p>
    
    <br>
    <p>Best regards,</p>
    <p>TBMS Team</p>
</body>
</html> 