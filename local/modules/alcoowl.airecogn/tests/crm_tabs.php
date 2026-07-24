<?php

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';

if (!Loader::includeModule('alcoowl.airecogn') || !Loader::includeModule('crm'))
{
    throw new RuntimeException('Modules alcoowl.airecogn and crm are required');
}

function dispatchTabsEvent(int $entityTypeId, int $entityId, array $tabs): array
{
    $event = new Event('crm', 'onEntityDetailsTabsInitialized', [
        'entityID' => $entityId,
        'entityTypeID' => $entityTypeId,
        'guid' => 'AIRECOGN_AUTONOMOUS_TEST',
        'tabs' => $tabs,
    ]);

    EventManager::getInstance()->send($event);
    foreach ($event->getResults() as $result)
    {
        if ($result->getType() !== EventResult::SUCCESS)
        {
            continue;
        }

        $parameters = $result->getParameters();
        if (is_array($parameters) && isset($parameters['tabs']) && is_array($parameters['tabs']))
        {
            $tabs = $parameters['tabs'];
        }
    }

    return $tabs;
}

function findTabById(array $tabs, string $id): ?array
{
    foreach ($tabs as $tab)
    {
        if (is_array($tab) && (string)($tab['id'] ?? '') === $id)
        {
            return $tab;
        }
    }

    return null;
}

$contactId = 2000000102;
$baseTabs = [
    ['id' => 'base_test_tab', 'name' => 'Исходная тестовая вкладка'],
];

$contactTabs = dispatchTabsEvent(CCrmOwnerType::Contact, $contactId, $baseTabs);
if (findTabById($contactTabs, 'base_test_tab') === null)
{
    throw new RuntimeException('The original CRM tab was not preserved');
}

$resultTab = findTabById($contactTabs, 'airecogn_results');
if ($resultTab === null)
{
    throw new RuntimeException('The airecogn result tab was not added for a contact');
}
if (($resultTab['enabled'] ?? false) !== true)
{
    throw new RuntimeException('The airecogn result tab is not enabled');
}

$serviceUrl = (string)($resultTab['loader']['serviceUrl'] ?? '');
if ($serviceUrl === '')
{
    throw new RuntimeException('The airecogn result tab has no loader URL');
}

parse_str((string)parse_url($serviceUrl, PHP_URL_QUERY), $queryParameters);
if (($queryParameters['mode'] ?? '') !== 'results'
    || empty($queryParameters['sessid']))
{
    throw new RuntimeException('The airecogn result tab loader URL is invalid');
}

$componentData = $resultTab['loader']['componentData'] ?? null;
$signedParameters = is_array($componentData) ? ($componentData['signedParameters'] ?? null) : null;
$componentParams = is_string($signedParameters)
    ? CCrmInstantEditorHelper::unsignComponentParams($signedParameters, 'airecogn.recognition.grid')
    : null;
if (!is_array($componentParams)
    || (int)($componentParams['CONTACT_ID'] ?? 0) !== $contactId
    || (string)($componentParams['MODE'] ?? '') !== 'results')
{
    throw new RuntimeException('The airecogn result tab parameters are not signed correctly');
}

echo "contact result tab: OK\n";
echo "original tab preserved: OK\n";
echo "result loader URL: OK\n";
echo "result loader signature: OK\n";

$companyTabs = dispatchTabsEvent(CCrmOwnerType::Company, 2000000103, $baseTabs);
if (findTabById($companyTabs, 'airecogn_results') !== null
    || findTabById($companyTabs, 'airecogn_logs') !== null)
{
    throw new RuntimeException('Airecogn tabs were unexpectedly added for a company');
}
if ($companyTabs !== $baseTabs)
{
    throw new RuntimeException('Company tabs were unexpectedly changed');
}

echo "non-contact entity unchanged: OK\n";
