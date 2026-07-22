<?php

use Airecogn\Config;
use Bitrix\Main\Config\Option;
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

$enabled = Option::get('airecogn', 'enabled', 'Y');
$loggingEnabled = Option::get('airecogn', 'logging_enabled', 'Y');
$logLevel = Option::get('airecogn', 'log_level', 'info');
$logTailSize = (int)Option::get('airecogn', 'log_tail_size', '200');

if (!in_array($enabled, ['Y', 'N'], true))
{
    throw new RuntimeException('Invalid enabled option');
}
if (!in_array($loggingEnabled, ['Y', 'N'], true))
{
    throw new RuntimeException('Invalid logging_enabled option');
}
if (!in_array($logLevel, ['debug', 'info', 'error'], true))
{
    throw new RuntimeException('Invalid log_level option');
}
if ($logTailSize < 10 || $logTailSize > 2000)
{
    throw new RuntimeException('log_tail_size is outside the allowed range');
}
if (Config::isEnabled() !== ($enabled === 'Y'))
{
    throw new RuntimeException('Config::isEnabled() does not match the stored option');
}
if (Config::isLoggingEnabled() !== ($loggingEnabled === 'Y'))
{
    throw new RuntimeException('Config::isLoggingEnabled() does not match the stored option');
}
if (Config::getLogLevel() !== $logLevel || Config::getLogTailSize() !== $logTailSize)
{
    throw new RuntimeException('Config log settings do not match the stored options');
}

$integrationConfig = Config::getIntegrationConfig();
foreach (['nextcloud', 'oracle'] as $section)
{
    if (!isset($integrationConfig[$section]) || !is_array($integrationConfig[$section]))
    {
        throw new RuntimeException('Missing integration config section: ' . $section);
    }
}

echo 'module enabled option: OK (' . $enabled . ")\n";
echo 'logging enabled option: OK (' . $loggingEnabled . ")\n";
echo 'log level option: OK (' . $logLevel . ")\n";
echo 'log tail size option: OK (' . $logTailSize . ")\n";
echo "integration config structure: OK (secret values hidden)\n";

