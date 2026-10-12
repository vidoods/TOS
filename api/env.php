<?php
// api/env.php — Легковесный загрузчик переменных окружения из .env файла

if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void
    {
        if (!file_exists($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                list($name, $value) = explode('=', $line, 2);
                $name  = trim($name);
                $value = trim($value);

                // Удаляем внешние кавычки, если они указаны
                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                // Заполняем getenv, $_ENV и $_SERVER, если переменная еще не определена на уровне ОС
                if (getenv($name) === false) {
                    putenv("{$name}={$value}");
                }
                if (!isset($_ENV[$name])) {
                    $_ENV[$name] = $value;
                }
                if (!isset($_SERVER[$name])) {
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    loadEnv(__DIR__ . '/../.env');
}
