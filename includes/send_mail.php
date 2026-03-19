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

        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;'>
            <div style='background:#7c3aed;padding:24px;text-align:center;'>
                <h2 style='color:white;margin:0;'>Booking Confirmed! 🎉</h2>
            </div>
            <div style='padding:28px;'>
                <p style='font-size:16px;'>Dear <strong>{$to_name}</strong>,</p>
                <p style='color:#374151;'>Your booking has been <strong style='color:#059669;'>confirmed</strong>. Here are your booking details:</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
                    <tr style='background:#f9fafb;'>
                        <td style='padding:10px 14px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb;'>Booking ID</td>
                        <td style='padding:10px 14px;border:1px solid #e5e7eb;font-weight:700;color:#7c3aed;'>{$booking['booking_id']}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 14px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb;'>Hall</td>
                        <td style='padding:10px 14px;border:1px solid #e5e7eb;'>{$booking['hall_name']}</td>
                    </tr>
                    <tr style='background:#f9fafb;'>
                        <td style='padding:10px 14px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb;'>Event</td>
                        <td style='padding:10px 14px;border:1px solid #e5e7eb;'>{$booking['event_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 14px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb;'>Event Date</td>
                        <td style='padding:10px 14px;border:1px solid #e5e7eb;'>{$event_date}</td>
                    </tr>
                    <tr style='background:#f9fafb;'>
                        <td style='padding:10px 14px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb;'>Slot</td>
                        <td style='padding:10px 14px;border:1px solid #e5e7eb;'>{$slot_info}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 14px;font-weight:600;color:#6b7280;border:1px solid #e5e7eb;'>Advance Paid</td>
                        <td style='padding:10px 14px;border:1px solid #e5e7eb;font-weight:700;'>Rs. " . number_format($booking['advance_amount']) . "</td>
                    </tr>
                </table>
                <p style='color:#374151;'>Thank you for choosing us. We look forward to making your event memorable!</p>
                <p style='color:#6b7280;font-size:13px;margin-top:24px;'>For any queries, please contact us.<br><strong>Sri Lakshmi Residency & Mahal</strong></p>
            </div>
        </div>";

        $mail->AltBody = "Dear {$to_name}, Your booking {$booking['booking_id']} for {$booking['event_name']} on {$event_date} at {$booking['hall_name']} has been confirmed. Advance Paid: Rs." . number_format($booking['advance_amount']);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
