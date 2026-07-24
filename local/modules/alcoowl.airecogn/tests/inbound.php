<?php

use Airecogn\Config;
use Airecogn\Model\RecognitionResultTable;
use Airecogn\Service\Logger;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';

if (!Loader::includeModule('alcoowl.airecogn'))
{
    throw new RuntimeException('Module alcoowl.airecogn is not available');
}
if (!Config::isEnabled())
{
    throw new RuntimeException('Module alcoowl.airecogn is disabled');
}
if (!Config::isLoggingEnabled())
{
    throw new RuntimeException('Module logging is disabled');
}

$endpoint = rtrim((string)(getenv('AIRECOGN_TEST_BASE_URL') ?: 'http://nginx'), '/')
    . '/local/handler/airecogn/inbound/index.php';
$missingActivityId = 2000000101;

if (RecognitionResultTable::getRow(['filter' => ['=ACTIVITY_ID' => $missingActivityId]]))
{
    throw new RuntimeException('Safe test ACTIVITY_ID is already present in the module table');
}

$cases = [
    [
        'name' => 'empty body',
        'body' => '',
        'expectedStatus' => 400,
        'expectedMessage' => 'Request body is empty',
    ],
    [
        'name' => 'invalid JSON',
        'body' => '{"call_id":',
        'expectedStatus' => 400,
        'expectedMessage' => 'Invalid JSON:',
    ],
    [
        'name' => 'missing call_id',
        'body' => json_encode([
            'call_status' => 'success',
            'summary' => 'Безопасный автономный тест без call_id',
        ], JSON_UNESCAPED_UNICODE),
        'expectedStatus' => 400,
        'expectedMessage' => 'call_id must be a positive integer or digit string',
    ],
    [
        'name' => 'numeric string call_id',
        'body' => json_encode([
            'call_id' => '00' . $missingActivityId,
            'call_status' => 'success',
            'summary' => 'Строковый цифровой ID принимается',
            'questions' => [
                [
                    'id' => '0007',
                    'text' => 'Строковый ID вопроса принимается',
                    'answer' => 'true',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE),
        'expectedStatus' => 500,
        'expectedMessage' => 'CRM activity not found',
    ],
    [
        'name' => 'invalid string call_id',
        'body' => json_encode([
            'call_id' => $missingActivityId . 'x',
            'call_status' => 'success',
            'summary' => 'Невалидный строковый ID',
        ], JSON_UNESCAPED_UNICODE),
        'expectedStatus' => 400,
        'expectedMessage' => 'call_id must be a positive integer or digit string',
    ],
    [
        'name' => 'invalid call_status',
        'body' => json_encode([
            'call_id' => $missingActivityId,
            'call_status' => 'unknown',
            'summary' => 'Неизвестный статус',
        ], JSON_UNESCAPED_UNICODE),
        'expectedStatus' => 400,
        'expectedMessage' => 'call_status must be success or error',
    ],
    [
        'name' => 'unknown call_id',
        'body' => json_encode([
            'call_id' => $missingActivityId,
            'call_status' => 'success',
            'summary' => 'Безопасный автономный тест несуществующей активити',
            'questions' => [],
        ], JSON_UNESCAPED_UNICODE),
        'expectedStatus' => 500,
        'expectedMessage' => 'CRM activity not found',
    ],
];

foreach ($cases as $case)
{
    $httpClient = new HttpClient([
        'socketTimeout' => 10,
        'streamTimeout' => 10,
        'redirect' => false,
    ]);
    $httpClient->setHeader('Content-Type', 'application/json; charset=utf-8');
    $responseBody = $httpClient->post($endpoint, $case['body']);
    $status = $httpClient->getStatus();
    $response = is_string($responseBody) ? json_decode($responseBody, true) : null;

    if ($status !== $case['expectedStatus'])
    {
        throw new RuntimeException(sprintf(
            '%s returned HTTP %d instead of %d. Body: %s',
            $case['name'],
            $status,
            $case['expectedStatus'],
            is_string($responseBody) ? $responseBody : '<no response>'
        ));
    }
    if (!is_array($response) || ($response['status'] ?? '') !== 'error')
    {
        throw new RuntimeException($case['name'] . ' returned invalid JSON error response');
    }
    if (!str_contains((string)($response['message'] ?? ''), $case['expectedMessage']))
    {
        throw new RuntimeException($case['name'] . ' returned an unexpected error message');
    }

    echo sprintf("%s: OK (HTTP %d)\n", $case['name'], $status);
}

$logRecords = Logger::tail(Logger::CHANNEL_INBOUND, 100);
foreach ($cases as $case)
{
    $logFound = false;
    foreach ($logRecords as $record)
    {
        $contextMessage = (string)($record['context']['message'] ?? '');
        if (str_contains($contextMessage, $case['expectedMessage']))
        {
            $logFound = true;
            break;
        }
    }

    if (!$logFound)
    {
        throw new RuntimeException('Inbound log record was not found for case: ' . $case['name']);
    }
}

if (RecognitionResultTable::getRow(['filter' => ['=ACTIVITY_ID' => $missingActivityId]]))
{
    throw new RuntimeException('Unknown CRM activity was unexpectedly saved to the module table');
}

$logFile = Logger::getLogFilePath(Logger::CHANNEL_INBOUND);
if (!is_file($logFile))
{
    throw new RuntimeException('Daily inbound log file was not created');
}

echo "inbound error logging: OK\n";
echo "module table unchanged: OK\n";
echo 'log file: ' . $logFile . "\n";
