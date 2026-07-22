<?php

use Airecogn\Model\RecognitionResultTable;
use Bitrix\Main\Application;
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

$connection = Application::getConnection();
$tableName = RecognitionResultTable::getTableName();

if (!$connection->isTableExists($tableName))
{
    throw new RuntimeException('Table ' . $tableName . ' does not exist');
}

$expectedFields = array_map(
    static fn($field): string => $field->getName(),
    RecognitionResultTable::getMap()
);
$databaseFields = array_keys($connection->getTableFields($tableName));
$missingFields = array_diff($expectedFields, $databaseFields);

if ($missingFields !== [])
{
    throw new RuntimeException('Missing table fields: ' . implode(', ', $missingFields));
}

$uniqueActivityIndexFound = false;
$indexResult = $connection->query('SHOW INDEX FROM ' . $tableName);
while ($index = $indexResult->fetch())
{
    $indexName = (string)($index['Key_name'] ?? $index['KEY_NAME'] ?? '');
    $columnName = strtoupper((string)($index['Column_name'] ?? $index['COLUMN_NAME'] ?? ''));
    $nonUnique = (int)($index['Non_unique'] ?? $index['NON_UNIQUE'] ?? 1);

    if ($indexName === 'UX_AIRECOGN_RESULT_ACTIVITY'
        && $columnName === 'ACTIVITY_ID'
        && $nonUnique === 0)
    {
        $uniqueActivityIndexFound = true;
        break;
    }
}

if (!$uniqueActivityIndexFound)
{
    throw new RuntimeException('Unique ACTIVITY_ID index was not found');
}

echo "table exists: OK ({$tableName})\n";
echo 'ORM fields: OK (' . implode(', ', $expectedFields) . ")\n";
echo "unique ACTIVITY_ID index: OK\n";

