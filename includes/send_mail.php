<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendBookingConfirmationMail($to_email, $to_name, $booking) {
    $mail = new PHPMailer(true);
    try {
        // SMTP Configuration - update these with your SMTP details
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thirukumaran18102006@gmail.com'; // Change this
        $mail->Password   = 'sqdi hluc nhsg sben';     // Change this (Gmail App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('thirukumaran18102006@gmail.com', 'Sri Lakshmi Residency & Mahal');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmed - ' . $booking['booking_id'];

        $event_date = date('d M Y', strtotime($booking['event_date']));
        $slot_info  = $booking['is_full_day'] ? 'Full Day' : ($booking['slot_name'] ?? 'N/A');

        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            color: #333; 
            line-height: 1.6; 
            margin: 0; 
            padding: 0; 
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .header {
            background: linear-gradient(135deg, #e91e63 0%, #ff4081 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between !important;
            gap: 15px;
        }
       
        .header img { 
            width: 80px; 
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table tr td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        table tr td:first-child {
            background: #f3f4f6;
            font-weight: bold;
            width: 40%;
        }
        table tr td:last-child {
            background: white;
        }
        .status-badge {
            background: #22c55e;
            color: #fff;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        @media screen and (max-width: 600px) {
            table tr td:first-child {
                width: 35%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <div>
            <img src="https://srilakshmiresidencymahal.saegroup.in/assets/images/wedding_illust.svg" alt="Booking">
            </div>
            <div>
            <h1 style="margin-left: 45px;">Booking Confirmed</h1>
            </div>
            <div style="margin-left: 35px;">
            <img src="https://srilakshmiresidencymahal.saegroup.in/assets/images/wedding_illust.svg" alt="Booking">
            </div>
        </div>

        <div class="content">
            <p>Dear <strong>' . $to_name . '</strong>,</p>
            <p>Your booking has been successfully confirmed. Here are your details:</p>

            <table>
                <tr>
                    <td>Booking ID</td>
                    <td><strong>' . $booking['booking_id'] . '</strong></td>
                </tr>
                <tr>
                    <td>Hall</td>
                    <td><strong>' . $booking['hall_name'] . '</strong></td>
                </tr>
                <tr>
                    <td>Event</td>
                    <td><strong>' . $booking['event_name'] . '</strong></td>
                </tr>
                <tr>
                    <td>Event Date</td>
                    <td><strong>' . $event_date . '</strong></td>
                </tr>
                <tr>
                    <td>Time Slot</td>
                    <td><strong>' . $slot_info . '</strong></td>
                </tr>
                <tr>
                    <td>Advance Paid</td>
                    <td><strong>₹' . number_format($booking['advance_amount'], 2) . '</strong></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span class="status-badge">✅ CONFIRMED</span></td>
                </tr>
            </table>

            <p>Thank you for choosing us. We look forward to making your event memorable!</p>
        </div>

        <div class="footer">
            <p>For any queries, please contact us.</p>
            <p><strong>Sri Lakshmi Residency & Mahal</strong></p>
            <p>&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>

    </div>
</body>
</html>
';

        $mail->AltBody = "Dear {$to_name}, Your booking {$booking['booking_id']} for {$booking['event_name']} on {$event_date} at {$booking['hall_name']} has been confirmed. Advance Paid: Rs." . number_format($booking['advance_amount']);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendOTPMail($to_email, $to_name, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thirukumaran18102006@gmail.com'; 
        $mail->Password   = 'sqdi hluc nhsg sben';     
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('thirukumaran18102006@gmail.com', 'Sri Lakshmi Residency & Mahal');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset OTP';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height:1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius:10px; }
                .otp { font-size: 28px; font-weight: bold; color: #e91e63; letter-spacing: 5px; text-align:center; padding: 20px; background: #f9fafb; border-radius: 5px; margin: 20px 0;}
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Password Reset Request</h2>
                <p>Hello <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
                <p>We received a request to reset your password. Use the OTP below to proceed.</p>
                <div class="otp">' . $otp . '</div>
                <p>This OTP is valid for 10 minutes. If you did not request a password reset, please ignore this email.</p>
            </div>
        </body>
        </html>';

        $mail->AltBody = "Hello {$to_name},\n\Your Password Reset OTP is: {$otp}\nIt is valid for 10 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
