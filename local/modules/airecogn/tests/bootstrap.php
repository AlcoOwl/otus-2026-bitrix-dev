<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';
require dirname(__DIR__) . '/include.php';

$classes = [
    \Airecogn\Config::class,
    \Airecogn\Model\RecognitionResultTable::class,
    \Airecogn\Service\RecognitionResultRepository::class,
    \Airecogn\Service\ContactResolver::class,
    \Airecogn\Service\Logger::class,
    \Airecogn\Service\EndCallProcessor::class,
    \Airecogn\Integration\CrmTabHandler::class,
    \Airecogn\Integration\VoximplantEventHandler::class,
];

foreach ($classes as $class)
{
    if (!class_exists($class))
    {
        throw new RuntimeException('Autoload failed: ' . $class);
    }
}

$fieldNames = array_map(
    static fn($field): string => $field->getName(),
    \Airecogn\Model\RecognitionResultTable::getMap()
);
$requiredFields = ['ID', 'ACTIVITY_ID', 'STATUS', 'SUMMARY', 'CREATED_AT', 'UPDATED_AT', 'PROCESSED_AT'];
if (array_diff($requiredFields, $fieldNames) !== [])
{
    throw new RuntimeException('ORM map is incomplete');
}

require dirname(__DIR__) . '/install/index.php';
$installer = new \airecogn();
if ($installer->MODULE_ID !== 'airecogn' || $installer->MODULE_VERSION === '')
{
    throw new RuntimeException('Installer metadata is invalid');
}

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
    throw new RuntimeException('CRM module is unavailable');
}

$event = new \Bitrix\Main\Event('crm', 'onEntityDetailsTabsInitialized', [
    'entityID' => 1,
    'entityTypeID' => \CCrmOwnerType::Contact,
    'guid' => 'TEST_CONTACT',
    'tabs' => [],
]);
$tabResult = \Airecogn\Integration\CrmTabHandler::onTabsInitialized($event);
$tabParameters = $tabResult->getParameters();
if (empty($tabParameters['tabs'][0]['loader']['serviceUrl']))
{
    throw new RuntimeException('CRM result tab was not created');
}

try
{
    \Airecogn\Integration\VoximplantEventHandler::onCallEnd(['CRM_ACTIVITY_ID' => '1x']);
    throw new RuntimeException('Invalid CRM_ACTIVITY_ID was accepted');
}
catch (UnexpectedValueException)
{
}

echo "airecogn bootstrap: OK\n";
