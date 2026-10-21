<?php
// api/mailer.php — Отправка email через SMTP (PHPMailer / ukr.net)

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================================================
// НАСТРОЙКИ SMTP (Загружаются из .env)
// ============================================================================
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.ukr.net');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_SECURE', strtolower(getenv('SMTP_SECURE') ?: 'ssl'));
define('SMTP_USER', getenv('SMTP_USER') ?: 'wrkspace@ukr.net');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: (getenv('SMTP_USER') ?: 'wrkspace@ukr.net'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'TradeOS');
define('APP_URL', getenv('APP_URL') ?: 'https://wrkspace.pro');

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array
{
    // Защита от незаполненного пароля SMTP
    if (empty(SMTP_PASS)) {
        error_log("[TradeOS Mailer] Внимание: Не указан SMTP_PASS в файле .env");
        return [
            'sent' => false,
            'simulated' => true,
            'error' => 'SMTP пароль не настроен в .env'
        ];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;

        if (SMTP_SECURE === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        // Отправитель и получатель (Для ukr.net адрес From обязан совпадать с логином SMTP)
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        // Содержимое письма
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $mail->send();

        return ['sent' => true, 'simulated' => false, 'error' => null];
    } catch (Exception $e) {
        $errMsg = $mail->ErrorInfo ?: $e->getMessage();
        error_log("[TradeOS Mailer] Ошибка отправки PHPMailer: " . $errMsg);
        return [
            'sent' => false,
            'simulated' => false,
            'error' => 'Ошибка отправки почты: ' . $errMsg
        ];
    }
}

function buildResetPasswordEmail(string $resetLink, string $username): string
{
  return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Reset Your Password</title></head>
<body style="margin:0;padding:0;background:#090c14;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#090c14;padding:40px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:20px;overflow:hidden;">
        <tr>
          <td style="background:linear-gradient(135deg,rgba(41,151,255,0.2),rgba(0,214,111,0.1));padding:36px 40px;text-align:center;">
            <h1 style="margin:0;font-size:26px;font-weight:800;color:#ffffff;">TradeOS</h1>
            <p style="margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.5);letter-spacing:1px;text-transform:uppercase;">Trading Operating System</p>
          </td>
        </tr>
        <tr>
          <td style="padding:40px;">
            <h2 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#ffffff;">Password Reset</h2>
            <p style="margin:0 0 24px;font-size:15px;color:rgba(255,255,255,0.6);line-height:1.6;">Hi <strong style="color:#fff;">{$username}</strong>, we received a request to reset your TradeOS password.</p>
            <p style="margin:0 0 32px;font-size:14px;color:rgba(255,255,255,0.5);line-height:1.6;">This link expires in <strong style="color:#fff;">1 hour</strong>.</p>
            <table cellpadding="0" cellspacing="0" width="100%">
              <tr><td align="center">
                <a href="{$resetLink}" style="display:inline-block;padding:16px 40px;background:linear-gradient(135deg,#2997ff,#0070e0);color:#fff;text-decoration:none;font-weight:700;font-size:15px;border-radius:12px;">Reset My Password</a>
              </td></tr>
            </table>
            <p style="margin:32px 0 0;font-size:12px;color:rgba(255,255,255,0.35);text-align:center;">If you didn't request this, ignore this email. Link: <span style="color:#2997ff;">{$resetLink}</span></p>
          </td>
        </tr>
        <tr><td style="padding:20px 40px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;"><p style="margin:0;font-size:12px;color:rgba(255,255,255,0.25);">&copy; TradeOS</p></td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function buildVerificationEmail(string $verifyLink, string $username): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Verify Your TradeOS Account</title></head>
<body style="margin:0;padding:0;background:#090c14;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#090c14;padding:40px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:20px;overflow:hidden;">
        <tr>
          <td style="background:linear-gradient(135deg,rgba(41,151,255,0.2),rgba(0,214,111,0.1));padding:36px 40px;text-align:center;">
            <h1 style="margin:0;font-size:26px;font-weight:800;color:#ffffff;">TradeOS</h1>
            <p style="margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.5);letter-spacing:1px;text-transform:uppercase;">Trading Operating System</p>
          </td>
        </tr>
        <tr>
          <td style="padding:40px;">
            <h2 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#ffffff;">Verify Your Email</h2>
            <p style="margin:0 0 24px;font-size:15px;color:rgba(255,255,255,0.6);line-height:1.6;">Hi <strong style="color:#fff;">{$username}</strong>, thanks for registering on TradeOS! Please confirm your email address to activate your account.</p>
            <p style="margin:0 0 32px;font-size:14px;color:rgba(255,255,255,0.5);line-height:1.6;">This verification link expires in <strong style="color:#fff;">24 hours</strong>.</p>
            <table cellpadding="0" cellspacing="0" width="100%">
              <tr><td align="center">
                <a href="{$verifyLink}" style="display:inline-block;padding:16px 40px;background:linear-gradient(135deg,#00d66f,#00a854);color:#fff;text-decoration:none;font-weight:700;font-size:15px;border-radius:12px;box-shadow:0 8px 24px rgba(0,214,111,0.25);">Verify Email Address</a>
              </td></tr>
            </table>
            <p style="margin:32px 0 0;font-size:12px;color:rgba(255,255,255,0.35);text-align:center;">If you didn't sign up for TradeOS, please ignore this email.<br>Link: <span style="color:#00d66f;">{$verifyLink}</span></p>
          </td>
        </tr>
        <tr><td style="padding:20px 40px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;"><p style="margin:0;font-size:12px;color:rgba(255,255,255,0.25);">&copy; TradeOS</p></td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}