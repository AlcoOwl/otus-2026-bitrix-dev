<?php

$classes = [
    [
        'id' => 'debug',
        'name' => 'Otus\\Debug',
        'file' => 'Debug.php',
        'description' => 'Класс для записи отладочной информации в файлы каталога /local/logs и для очистки этих логов. Если имя файла не передано, использует DEBUG_FILE_NAME или otus_log по умолчанию.',
        'methods' => [
            'writeToLog($data = "", $title = "OTUS_DEBUG", $fileName = ""): bool',
            'cleanLog(string $fileName = "otus_log"): bool',
        ],
    ],
    [
        'id' => 'otus-exception-log',
        'name' => 'Otus\\OtusExceptionLog',
        'file' => 'OtusExceptionLog.php',
        'description' => 'Пользовательский логгер системных исключений Битрикс. Наследуется от FileExceptionHandlerLog, задает собственный файл лога по умолчанию и добавляет префикс OTUS в строки лога.',
        'methods' => [
            'initialize(array $options): void',
            'write($exception, $logType): void',
        ],
        'constants' => [
            'DEFAULT_LOG_FILE = "/local/logs/otus_exceptions.log"',
        ],
    ],
    [
        'id' => 'helper',
        'name' => 'Otus\\Helper',
        'file' => 'Helper.php',
        'description' => 'Вспомогательный класс для работы с сущностями Битрикс. Сейчас содержит метод поиска ID инфоблока по символьному коду.',
        'methods' => [
            'getIblockIdByCode(?string $code): ?int',
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Описание классов local/php_interface/src</title>
    <style>
        body {
            margin: 40px;
            font: 16px/1.5 Arial, sans-serif;
            color: #1f2937;
            background: #f8fafc;
        }

        main {
            max-width: 900px;
            padding: 32px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        h1, h2 {
            margin-top: 0;
            color: #111827;
        }

        .class-card {
            margin-top: 24px;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #dbe3ee;
            border-radius: 12px;
        }

        .meta {
            color: #4b5563;
        }

        code {
            padding: 2px 6px;
            background: #eef2ff;
            border-radius: 6px;
        }

        ul {
            padding-left: 20px;
        }
    </style>
</head>
<body>
<main>
    <h1>Классы в local/php_interface/src</h1>
    <p>
        Страница с кратким описанием пользовательских классов в директории
        <code>/local/php_interface/src</code>.
    </p>

    <?php foreach ($classes as $class): ?>
        <section class="class-card" id="<?= htmlspecialchars($class['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <h2><?= htmlspecialchars($class['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
            <p class="meta">
                Файл: <code><?= htmlspecialchars($class['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code>
            </p>
            <p><?= htmlspecialchars($class['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Методы:</strong></p>
            <ul>
                <?php foreach ($class['methods'] as $method): ?>
                    <li><code><?= htmlspecialchars($method, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></li>
                <?php endforeach; ?>
            </ul>
            <?php if (!empty($class['constants'])): ?>
                <p><strong>Константы:</strong></p>
                <ul>
                    <?php foreach ($class['constants'] as $constant): ?>
                        <li><code><?= htmlspecialchars($constant, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>
