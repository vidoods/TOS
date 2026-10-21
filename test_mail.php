<?php
// test_mail.php — Консольный скрипт для быстрой проверки отправки почты через SMTP ukr.net

require_once __DIR__ . '/api/mailer.php';

$recipient = $argv[1] ?? null;

if (!$recipient) {
    echo "Использование: php test_mail.php ваш_email@example.com\n";
    exit(1);
}

echo "Отправка тестового письма...\n";
echo "От: " . MAIL_FROM . " (" . MAIL_FROM_NAME . ")\n";
echo "Кому: " . $recipient . "\n";
echo "SMTP Сервер: " . SMTP_HOST . ":" . SMTP_PORT . " (" . SMTP_SECURE . ")\n";
echo "Пользователь: " . SMTP_USER . "\n\n";

$result = sendMail(
    $recipient,
    'Tester',
    'Тестовое письмо от TradeOS (ukr.net)',
    '<h1>Тест пройден успешно!</h1><p>Это тестовое письмо для проверки работы SMTP сервера ukr.net в TradeOS.</p>'
);

if ($result['sent']) {
    echo " УСПЕХ: Письмо успешно отправлено!\n";
} else {
    echo " ОШИБКА: " . ($result['error'] ?? 'Неизвестная ошибка') . "\n";
}
