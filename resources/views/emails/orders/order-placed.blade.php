<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>New Order Placed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f2f4f8;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #0052cc;
            color: #fff;
            padding: 24px 32px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .section {
            padding: 24px 32px;
            font-size: 15px;
            color: #333;
        }

        .section h2 {
            font-size: 16px;
            color: #0052cc;
            margin-bottom: 12px;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .col {
            flex: 1 1 250px;
        }

        .footer {
            padding: 20px 32px;
            font-size: 12px;
            color: #888;
            text-align: center;
            background-color: #fafbfc;
        }

        @media only screen and (max-width: 600px) {
            .section {
                padding: 20px;
            }

            .header,
            .footer {
                padding: 20px;
            }

            .row {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <h1>New Order Placed</h1>
            <p style="margin-top: 8px; font-size: 14px;">for {{ $branch_name }}</p>
        </div>

        <div class="section">
            <p>Hello <strong>{{ $branch_name }}</strong>,</p>
            <p>A new order has been placed. Please review the order details below:</p>
        </div>

        <div class="section row">
            <div class="col">
                <h2>Order Details</h2>
                <p><strong>Title:</strong> {{ $title }}</p>
                <p><strong>Delivery Date:</strong> {{ \Carbon\Carbon::parse($delivery_date)->format('Y-m-d') }}</p>
                <p><strong>Delivery Time:</strong> {{ \Carbon\Carbon::parse($delivery_time)->format('H:i') }}</p>
                <p><strong>Total Amount:</strong> ₹{{ number_format($total_amount, 2) }}</p>
                <p><strong>Advance Paid:</strong> ₹{{ number_format($advance_amount ?? 0, 2) }}</p>
            </div>
            <div class="col">
                <h2>Customer Details</h2>
                <p><strong>Name:</strong> {{ $customer_name }}</p>
                <p><strong>Email:</strong> {{ $customer_email }}</p>
                <p><strong>Mobile:</strong> {{ $customer_mobile }}</p>
            </div>
        </div>

        <div class="section">
            <h2>Payment Summary</h2>
            <p><strong>Total:</strong> ₹{{ number_format($total_amount, 2) }}</p>
            <p><strong>Advance:</strong> ₹{{ number_format($advance_amount ?? 0, 2) }}</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} TBMS. All rights reserved.
        </div>

    </div>
</body>

</html>