<?php

use Airecogn\Integration\VoximplantEventHandler;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';

if (!ModuleManager::isModuleInstalled('alcoowl.airecogn') || !Loader::includeModule('alcoowl.airecogn'))
{
    throw new RuntimeException('Module alcoowl.airecogn is not installed');
}

$eventManager = EventManager::getInstance();
$handlers = $eventManager->findEventHandlers('voximplant', 'onCallEnd', ['alcoowl.airecogn']);
$matchingHandlers = array_values(array_filter($handlers, static function (array $handler): bool {
    return strcasecmp(
        ltrim((string)($handler['TO_CLASS'] ?? ''), '\\'),
        VoximplantEventHandler::class
    ) === 0
        && strcasecmp((string)($handler['TO_METHOD'] ?? ''), 'onCallEnd') === 0;
}));

if (count($matchingHandlers) > 1)
{
    throw new RuntimeException('Duplicate voximplant:onCallEnd handlers found');
}

if ($matchingHandlers === [])
{
    $eventManager->registerEventHandler(
        'voximplant',
        'onCallEnd',
        'alcoowl.airecogn',
        '\\Airecogn\\Integration\\VoximplantEventHandler',
        'onCallEnd'
    );
    echo "voximplant:onCallEnd registered: OK\n";
}
else
{
    echo "voximplant:onCallEnd already registered: OK\n";
}
