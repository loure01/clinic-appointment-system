<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function sendClinicPassEmail($ticket) {
    $mail = new PHPMailer(true);

    $passNumber   = str_pad($ticket['id'], 4, '0', STR_PAD_LEFT);
    $friendlyDate = date('l, F j, Y', strtotime($ticket['date']));

    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host      = $_SERVER['HTTP_HOST'];
    $baseDir   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $magicLink = "{$protocol}://{$host}{$baseDir}/patient/my_appointments.php?email=" . urlencode($ticket['email']);

    $startTs   = strtotime($ticket['date'] . ' ' . $ticket['time']);
    $endTs     = $startTs + 3600;
    $gcalDates = gmdate('Ymd\THis\Z', $startTs) . '/' . gmdate('Ymd\THis\Z', $endTs);
    $gcalUrl   = "https://calendar.google.com/calendar/render?action=TEMPLATE"
               . "&text=" . urlencode("Clinic Consultation - " . $ticket['doctor'])
               . "&dates=" . $gcalDates
               . "&details=" . urlencode("Clinic Pass #{$passNumber}\nDepartment: {$ticket['dept']}\nFee Paid: PHP " . number_format($ticket['amount'], 2));

    $htmlBody = "
    <div style='font-family: Segoe UI, Arial, sans-serif; max-width: 560px; margin: 0 auto; border: 1px solid #d1fae5; border-radius: 14px; overflow: hidden; background: #ffffff;'>
        <div style='background: #064e3b; color: #ffffff; padding: 24px; text-align: center;'>
            <div style='font-size: 12px; letter-spacing: 1px; color: #6ee7b7; font-weight: 700;'>CONFIRMED CLINIC PASS</div>
            <h1 style='margin: 6px 0 0 0; font-size: 26px;'>Pass #{$passNumber}</h1>
        </div>
        <div style='padding: 24px; color: #1e293b;'>
            <p style='margin-top: 0;'>Hi <strong>" . htmlspecialchars($ticket['patient']) . "</strong>,</p>
            <p style='color: #475569; font-size: 14px;'>Your consultation schedule is confirmed. Present this pass at the clinic reception desk upon arrival.</p>
            
            <div style='background: #f8fafc; border: 1px dashed #10b981; border-radius: 10px; padding: 16px; margin: 20px 0;'>
                <table style='width: 100%; font-size: 14px; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Specialist</td>
                        <td style='padding: 6px 0; text-align: right; font-weight: 700;'>" . htmlspecialchars($ticket['doctor']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Department</td>
                        <td style='padding: 6px 0; text-align: right; font-weight: 600; color: #059669;'>" . htmlspecialchars($ticket['dept']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Date</td>
                        <td style='padding: 6px 0; text-align: right; font-weight: 700;'>{$friendlyDate}</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Time Slot</td>
                        <td style='padding: 6px 0; text-align: right; font-weight: 700;'>" . htmlspecialchars($ticket['time']) . " (1 hr)</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0 0 0; border-top: 1px solid #e2e8f0; color: #064e3b; font-weight: 700;'>Consultation Fee</td>
                        <td style='padding: 10px 0 0 0; border-top: 1px solid #e2e8f0; text-align: right; font-weight: 800; color: #064e3b;'>₱" . number_format($ticket['amount'], 2) . " (Paid)</td>
                    </tr>
                </table>
            </div>

            <div style='text-align: center; margin-top: 24px;'>
                <a href='{$gcalUrl}' style='display: inline-block; background: #059669; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 700; font-size: 14px; margin: 4px;'>
                    📅 Add to Google Calendar
                </a>
                <a href='{$magicLink}' style='display: inline-block; background: #ffffff; color: #064e3b; border: 1px solid #064e3b; text-decoration: none; padding: 11px 20px; border-radius: 8px; font-weight: 700; font-size: 14px; margin: 4px;'>
                    Manage / Cancel Visit
                </a>
            </div>
        </div>
        <div style='background: #f1f5f9; padding: 14px; text-align: center; font-size: 12px; color: #64748b;'>
            Need to cancel? Use the button above at least 2 hours before your scheduled time.
        </div>
    </div>";

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'christemae.castillon@hcdc.edu.ph';
        $mail->Password   = '12242004cm!!';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('christemae.castillon@hcdc.edu.ph', 'Clinic Appointment System');
        $mail->addAddress($ticket['email'], $ticket['patient']);

        $mail->isHTML(true);
        $mail->Subject = "Clinic Pass #{$passNumber} - Confirmed ({$friendlyDate})";
        $mail->Body    = $htmlBody;

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
?>