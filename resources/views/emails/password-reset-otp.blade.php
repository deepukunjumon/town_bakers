<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Your Password Reset OTP</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="font-size: 24px; font-weight: bold; color: #222222;">
                            Hello {{ $user->name }},
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 15px; font-size: 16px; color: #555555; line-height: 1.6;">
                            You requested a password reset. Use the following OTP to reset your password. The OTP is valid for 10 minutes.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 20px; font-size: 28px; font-weight: bold; color: #222222; text-align: center; letter-spacing: 4px;">
                            {{ $otp }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 20px; font-size: 16px; color: #555555; line-height: 1.6;">
                            If you did not request a password reset, please ignore this email.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 25px; font-size: 16px; color: #555555;">
                            Thanks,<br>
                            <strong>Town Bakers</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="height: 30px;"></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #aaaaaa; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                            &copy; {{ date('Y') }} TBMS. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>