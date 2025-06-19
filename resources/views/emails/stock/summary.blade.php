<!DOCTYPE html>
<html>

<head>
    <title>Stock Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Stock Summary Report</h2>
        </div>

        <div class="content">
            <p>Dear Admin,</p>

            <p>Please find attached the stock summary report for <strong>{{ $branchName }}</strong> as of <strong>{{ $date }}</strong>.</p>

            <p>The report contains detailed information about the current stock levels and inventory status.</p>

            <p>If you have any questions or need further assistance, please don't hesitate to contact us.</p>

            <p>Best regards,<br>
                TBMS System</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>

</html>