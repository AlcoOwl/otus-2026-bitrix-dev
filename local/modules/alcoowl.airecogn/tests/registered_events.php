<?php

use Bitrix\Main\EventManager;
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

$expectedEvents = [
    [
        'module' => 'voximplant',
        'event' => 'onCallEnd',
        'class' => Airecogn\Integration\VoximplantEventHandler::class,
        'method' => 'onCallEnd',
    ],
    [
        'module' => 'crm',
        'event' => 'onEntityDetailsTabsInitialized',
        'class' => Airecogn\Integration\CrmTabHandler::class,
        'method' => 'onTabsInitialized',
    ],
    [
        'module' => 'main',
        'event' => 'OnBuildGlobalMenu',
        'class' => Airecogn\Integration\AdminMenu::class,
        'method' => 'onBuildGlobalMenu',
    ],
];

$eventManager = EventManager::getInstance();

foreach ($expectedEvents as $expected)
{
    $handlers = $eventManager->findEventHandlers(
        $expected['module'],
        $expected['event'],
        ['alcoowl.airecogn']
    );

    $matchingHandlers = array_values(array_filter(
        $handlers,
        static function (array $handler) use ($expected): bool {
            return strcasecmp(ltrim((string)($handler['TO_CLASS'] ?? ''), '\\'), $expected['class']) === 0
                && strcasecmp((string)($handler['TO_METHOD'] ?? ''), $expected['method']) === 0
                && (string)($handler['TO_MODULE_ID'] ?? '') === 'alcoowl.airecogn'
                && ($handler['FROM_DB'] ?? false) === true;
        }
    ));

    if (count($matchingHandlers) !== 1)
    {
        throw new RuntimeException(sprintf(
            'Expected exactly one handler for %s:%s, found %d',
            $expected['module'],
            $expected['event'],
            count($matchingHandlers)
        ));
    }

    echo sprintf(
        "event: OK — %s:%s → %s::%s\n",
        $expected['module'],
        $expected['event'],
        $expected['class'],
        $expected['method']
    );
}

echo "registered events: OK\n";
