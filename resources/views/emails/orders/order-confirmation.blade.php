<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" style="max-width: 600px; background: #ffffff; padding: 40px; border-radius: 8px;">
                    <!-- Greeting -->
                    <tr>
                        <td style="font-size: 24px; font-weight: bold; color: #222;">Hello {{ $customer_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding-top: 10px; font-size: 16px; color: #888888;">
                            Your order has been successfully placed! Please find the below order details.
                        </td>
                    </tr>

                    <!-- Spacer -->
                    <tr>
                        <td style="height: 30px;"></td>
                    </tr>

                    <!-- Two Column Section -->
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <!-- Items -->
                                    <td width="50%" valign="top" style="padding-right: 10px;">
                                        <p style="font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px;">Items</p>
                                        <p style="margin: 5px 0;">{{ $title }}</p>
                                    </td>

                                    <!-- Delivery -->
                                    <td width="50%" valign="top" style="padding-left: 10px;">
                                        <p style="font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px;">Delivery Date and Time</p>
                                        <p style="margin: 5px 0;">
                                            {{ \Carbon\Carbon::parse($delivery_date)->format('Y-m-d') }}
                                            {{ \Carbon\Carbon::parse($delivery_time)->format('H:i') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Spacer -->
                    <tr>
                        <td style="height: 30px;"></td>
                    </tr>

                    <!-- Payment Section -->
                    <tr>
                        <td>
                            <p style="font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px;">Payment Details</p>
                            <p style="margin: 5px 0;">Total Amount: ₹{{ number_format($total_amount, 2) }}</p>
                            <p style="margin: 5px 0;">Advance Amount: ₹{{ number_format($advance_amount ?? 0, 2) }}</p>
                        </td>
                    </tr>

                    <!-- Spacer -->
                    <tr>
                        <td style="height: 30px;"></td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="font-size: 12px; color: #aaaaaa; text-align: center;">
                            &copy; {{ date('Y') }} TBMS. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>