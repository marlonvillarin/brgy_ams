<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);

define('SMTP_USER', 'thechrowl@gmail.com');
define('SMTP_PASS', 'zqrhbupeqjjhgozs');

define('MAIL_NAME', 'Barangay AMS');
// ======================
// SEND VERIFICATION EMAIL
// ======================
function sendVerificationEmail($to_email, $to_name, $code)
{
  $subject = "Your Verification Code - Barangay AMS";

  $html = '
<div style="font-family: Arial, sans-serif; background-color:#f4f6f8; padding:20px;">
  
  <div style="max-width:500px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    
    <!-- Header -->
    <div style="background:#003087; color:#ffffff; padding:15px; text-align:center;">
      <h2 style="margin:0;">Barangay AMS</h2>
    </div>

    <!-- Body -->
    <div style="padding:20px; color:#333;">
      <p style="font-size:16px;">Hello <strong>' . $to_name . '</strong>,</p>
      
      <p>Your verification code is:</p>

      <div style="text-align:center; margin:20px 0;">
        <span style="
          display:inline-block;
          font-size:28px;
          letter-spacing:6px;
          background:#f1f3f5;
          padding:12px 20px;
          border-radius:6px;
          font-weight:bold;
          color:#003087;
        ">
          ' . $code . '
        </span>
      </div>

      <p style="font-size:14px; color:#666;">
        This code will expire in <strong>15 minutes</strong>.
      </p>

      <p style="font-size:14px; color:#666;">
        If you did not request this, you can safely ignore this email.
      </p>
    </div>

    <!-- Footer -->
    <div style="background:#f1f3f5; padding:12px; text-align:center; font-size:12px; color:#888;">
      &copy; ' . date('Y') . ' Barangay Appointment Management System
    </div>

  </div>

</div>
// ';

  return sendSmtp($to_email, $to_name, $subject, $html);
}

function sendStatusEmail($to_email, $to_name, $status, $document, $date, $note = '')
{
  $subject = "Appointment Update - " . ucfirst($status);

  $html = '
  <div style="font-family: Arial, sans-serif; background:#f4f6f8; padding:20px;">
    <div style="max-width:500px; margin:auto; background:#fff; border-radius:8px; overflow:hidden;">

      <div style="background:#003087; color:#fff; padding:15px; text-align:center;">
        <h2 style="margin:0;">Barangay AMS</h2>
      </div>

      <div style="padding:20px; color:#333;">
        <p>Hello <strong>' . $to_name . '</strong>,</p>

        <p>Your appointment for <strong>' . $document . '</strong> on <strong>' . $date . '</strong> has been:</p>

        <h3 style="color:#003087; text-transform:uppercase;">
          ' . $status . '
        </h3>

        ' . (!empty($note) ? '<p><strong>Admin Note:</strong> ' . $note . '</p>' : '') . '

        <p style="font-size:13px;color:#666;margin-top:20px;">
          Thank you for using Barangay AMS.
        </p>
      </div>

      <div style="background:#f1f3f5; padding:10px; text-align:center; font-size:12px;">
        © ' . date('Y') . ' Barangay AMS
      </div>

    </div>
  </div>';

  return sendSmtp($to_email, $to_name, $subject, $html);
}



function sendSmtp($to, $name, $subject, $html)
{
  $mail = new PHPMailer(true);

  try {
    $mail->isSMTP();

    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(SMTP_USER, MAIL_NAME);
    $mail->addAddress($to, $name);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;

    $mail->send();
    return true;

  } catch (Exception $e) {
    echo "❌ Mailer Error: " . $mail->ErrorInfo;
    return false;
  }
}