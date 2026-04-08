#!/bin/bash
# Запуск автоматической компиляции SCSS -> CSS
# Этот скрипт следит за изменениями в папке scss/ и автоматически обновляет style.css

echo "👓 Начинаем отслеживание изменений SCSS файлов..."
sass --watch assets/scss/style.scss:assets/style.css
