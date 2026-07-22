<?php

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';

if (!ModuleManager::isModuleInstalled('airecogn'))
{
    throw new RuntimeException('Module airecogn is not registered');
}

if (!Loader::includeModule('airecogn'))
{
    throw new RuntimeException('Loader::includeModule("airecogn") returned false');
}

if (!class_exists(\Airecogn\Config::class))
{
    throw new RuntimeException('Airecogn classes are not autoloaded');
}

echo "registration: OK\n";
echo "loader: OK\n";
echo "autoload: OK\n";

