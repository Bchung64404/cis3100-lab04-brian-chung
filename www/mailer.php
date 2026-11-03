<?php
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendSystemMail($toEmail, $toName, $subject, $htmlBody)
{
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = getenv('SMTP_PORT') ?: 465;
    $smtpUser = getenv('SMTP_USER') ?: '';
    $smtpPass = getenv('SMTP_PASSWORD') ?: '';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->Port = $smtpPort;

        // Enable SSL encryption specifically for Gmail Port 465
        if ($smtpPort == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtpPort == 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Sender must match your authenticated Gmail address
        $mail->setFrom($smtpUser, 'Docker Lab Infrastructure');
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>