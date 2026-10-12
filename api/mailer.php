<?php
// api/mailer.php — Отправка email через современный API Resend (без SMTP)

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

// ============================================================================
// НАСТРОЙКИ RESEND API (Загружаются из .env)
// ============================================================================
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: '');
define('MAIL_FROM',      getenv('MAIL_FROM')      ?: 'noreply@wrkspace.pro');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'TradeOS');
define('APP_URL',        getenv('APP_URL')        ?: 'https://wrkspace.pro');

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array
{
    // Защита от незаполненного ключа
    if (empty(RESEND_API_KEY) || RESEND_API_KEY === 're_ваш_ключ_здесь') {
        error_log("[TradeOS Resend] Ошибка: Не указан API ключ Resend в mailer.php");
        return ['sent' => false, 'simulated' => true, 'error' => 'API ключ Resend не настроен.'];
    }

    // Формируем тело запроса для Resend API
    $payload = [
        'from'    => MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'to'      => [$toEmail],
        'subject' => $subject,
        'html'    => $htmlBody
    ];

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Проверяем результат выполнения запроса
    if ($curlError) {
        error_log("[TradeOS Resend] cURL Error: " . $curlError);
        return ['sent' => false, 'simulated' => false, 'error' => 'Ошибка соединения с почтовым сервисом.'];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['sent' => true, 'simulated' => false, 'error' => null];
    } else {
        error_log("[TradeOS Resend] API Error (HTTP {$httpCode}): " . $response);
        $responseData = json_decode($response, true);
        $errorMessage = $responseData['message'] ?? 'Не удалось отправить письмо через Resend.';
        return ['sent' => false, 'simulated' => false, 'error' => $errorMessage];
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