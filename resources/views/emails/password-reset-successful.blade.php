<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">

                    <!-- Greeting -->
                    <tr>
                        <td style="font-size: 24px; font-weight: bold; color: #222222;">
                            Hello {{ $user->name }},
                        </td>
                    </tr>

                    <!-- Message -->
                    <tr>
                        <td style="padding-top: 15px; font-size: 16px; color: #555555; line-height: 1.6;">
                            Your password has been <strong>successfully reset</strong>!<br>
                            If this was not done by you, please contact support immediately.<br><br>
                        </td>
                    </tr>

                    <!-- Signature -->
                    <tr>
                        <td style="padding-top: 25px; font-size: 16px; color: #555555;">
                            Thanks,<br>
                            <strong>Town Bakers</strong>
                        </td>
                    </tr>

                    <!-- Spacer -->
                    <tr>
                        <td style="height: 30px;"></td>
                    </tr>

                    <!-- Footer -->
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