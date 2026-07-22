<?php

use Airecogn\Config;
use Airecogn\Service\Logger;
use Bitrix\Main\Loader;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';

if (!Loader::includeModule('airecogn'))
{
    throw new RuntimeException('Module airecogn is not available');
}
if (!Config::isLoggingEnabled())
{
    throw new RuntimeException('Module logging is disabled in options');
}

$marker = 'airecogn-test-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
$level = Config::getLogLevel() === 'error' ? 'error' : 'info';
$payload = [
    'type' => 'autonomous_test',
    'message' => 'Проверка ежедневного логирования модуля airecogn',
    'test_marker' => $marker,
    'activity_id' => 2000000201,
];

Logger::write(Logger::CHANNEL_END_CALL, $payload, $level);

$matchingRecord = null;
foreach (Logger::tail(Logger::CHANNEL_END_CALL, 100) as $record)
{
    if (($record['test_marker'] ?? '') === $marker)
    {
        $matchingRecord = $record;
        break;
    }
}

if (!is_array($matchingRecord))
{
    throw new RuntimeException('Test log record was not found by Logger::tail()');
}
if (($matchingRecord['type'] ?? '') !== 'autonomous_test')
{
    throw new RuntimeException('Test log record has invalid payload');
}

$logFile = Logger::getLogFilePath(Logger::CHANNEL_END_CALL);
if (!is_file($logFile) || !is_readable($logFile))
{
    throw new RuntimeException('Daily log file was not created');
}

echo "daily log write: OK\n";
echo "daily log read: OK\n";
echo 'log file: ' . $logFile . "\n";
echo 'test marker: ' . $marker . "\n";
