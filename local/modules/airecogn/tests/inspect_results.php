<?php

use Airecogn\Model\RecognitionResultTable;
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

$rows = RecognitionResultTable::getList([
    'select' => ['ID', 'ACTIVITY_ID', 'STATUS', 'SUMMARY'],
    'order' => ['ID' => 'DESC'],
    'limit' => 20,
])->fetchAll();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

