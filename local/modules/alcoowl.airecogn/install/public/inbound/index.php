<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Airecogn\Config;
use Airecogn\Http\InboundHandler;
use Bitrix\Main\Loader;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('alcoowl.airecogn'))
{
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Module alcoowl.airecogn is not installed']);
    return;
}

if (!Config::isEnabled())
{
    http_response_code(503);
    echo json_encode(['status' => 'disabled'], JSON_UNESCAPED_UNICODE);
    return;
}

InboundHandler::run();
