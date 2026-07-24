<?php

use Airecogn\Model\RecognitionResultTable;
use Airecogn\Service\RecognitionResultRepository;
use Bitrix\Main\Loader;

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

$overflowArtifact = RecognitionResultTable::getRow([
    'filter' => [
        '=ACTIVITY_ID' => 2147483647,
        '=STATUS' => RecognitionResultRepository::STATUS_PENDING,
        '=SUMMARY' => '',
    ],
]);
if ($overflowArtifact)
{
    $cleanupResult = RecognitionResultTable::delete((int)$overflowArtifact['ID']);
    if (!$cleanupResult->isSuccess())
    {
        throw new RuntimeException('Previous test artifact cleanup failed');
    }
    echo "previous overflow artifact removed: OK\n";
}

$testActivityId = 2000000001;
$createdRowId = null;

try
{
    $existing = RecognitionResultTable::getRow([
        'select' => ['ID'],
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);
    if ($existing)
    {
        throw new RuntimeException('Test ACTIVITY_ID is already occupied: ' . $testActivityId);
    }

    RecognitionResultRepository::savePending($testActivityId);
    $pendingRow = RecognitionResultTable::getRow([
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);

    if (!$pendingRow || (string)$pendingRow['STATUS'] !== RecognitionResultRepository::STATUS_PENDING)
    {
        throw new RuntimeException('Pending row was not created correctly');
    }

    $createdRowId = (int)$pendingRow['ID'];
    echo "create pending: OK (ID={$createdRowId})\n";
    echo "read pending: OK\n";

    $successSummary = 'Тестовое успешное распознавание';
    RecognitionResultRepository::saveInbound([
        'call_id' => $testActivityId,
        'call_status' => 'success',
        'summary' => $successSummary,
        'questions' => [['id' => 1, 'text' => 'Не должно попасть в таблицу', 'answer' => 'true']],
        'route' => [['from' => 1, 'to' => 2]],
    ]);

    $successRow = RecognitionResultTable::getRow([
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);
    if (!$successRow
        || (string)$successRow['STATUS'] !== RecognitionResultRepository::STATUS_SUCCESS
        || (string)$successRow['SUMMARY'] !== $successSummary)
    {
        throw new RuntimeException('Success update was not saved correctly');
    }

    echo "update to success: OK\n";
    echo "summary saved: OK\n";

    $errorSummary = 'Тестовая ошибка распознавания';
    RecognitionResultRepository::saveInbound([
        'call_id' => $testActivityId,
        'call_status' => 'error',
        'summary' => $errorSummary,
    ]);

    $errorRow = RecognitionResultTable::getRow([
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);
    if (!$errorRow
        || (string)$errorRow['STATUS'] !== RecognitionResultRepository::STATUS_ERROR
        || (string)$errorRow['SUMMARY'] !== $errorSummary)
    {
        throw new RuntimeException('Error update was not saved correctly');
    }

    echo "update to error: OK\n";

    RecognitionResultRepository::savePending($testActivityId);
    $retryRow = RecognitionResultTable::getRow([
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);
    if (!$retryRow
        || (string)$retryRow['STATUS'] !== RecognitionResultRepository::STATUS_PENDING
        || (string)$retryRow['SUMMARY'] !== ''
        || $retryRow['PROCESSED_AT'] !== null)
    {
        throw new RuntimeException('Retry pending update was not saved correctly');
    }

    echo "retry error to pending: OK\n";

    RecognitionResultRepository::save(
        $testActivityId,
        RecognitionResultRepository::STATUS_SKIPPED_NO_RECORDING,
        'Нет записи'
    );
    $skippedRow = RecognitionResultTable::getRow([
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);
    if (!$skippedRow || (string)$skippedRow['STATUS'] !== RecognitionResultRepository::STATUS_SKIPPED_NO_RECORDING)
    {
        throw new RuntimeException('Skipped no recording update was not saved correctly');
    }

    RecognitionResultRepository::save(
        $testActivityId,
        RecognitionResultRepository::STATUS_SKIPPED_SHORT,
        'Короткая запись'
    );
    $skippedRow = RecognitionResultTable::getRow([
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);
    if (!$skippedRow || (string)$skippedRow['STATUS'] !== RecognitionResultRepository::STATUS_SKIPPED_SHORT)
    {
        throw new RuntimeException('Skipped short update was not saved correctly');
    }

    echo "update skipped statuses: OK\n";

    RecognitionResultRepository::savePending($testActivityId);
    $retryRow = RecognitionResultTable::getRow([
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);
    if (!$retryRow || (string)$retryRow['STATUS'] !== RecognitionResultRepository::STATUS_PENDING)
    {
        throw new RuntimeException('Retry skipped to pending was not saved correctly');
    }

    echo "retry skipped to pending: OK\n";
}
finally
{
    $testRow = RecognitionResultTable::getRow([
        'select' => ['ID'],
        'filter' => ['=ACTIVITY_ID' => $testActivityId],
    ]);

    if ($testRow)
    {
        $deleteResult = RecognitionResultTable::delete((int)$testRow['ID']);
        if (!$deleteResult->isSuccess())
        {
            throw new RuntimeException('Test row cleanup failed: ' . implode('; ', $deleteResult->getErrorMessages()));
        }

        echo "delete test row: OK\n";
    }
}

if (RecognitionResultTable::getRow(['filter' => ['=ACTIVITY_ID' => $testActivityId]]))
{
    throw new RuntimeException('Test row still exists after cleanup');
}

echo "cleanup verified: OK\n";
