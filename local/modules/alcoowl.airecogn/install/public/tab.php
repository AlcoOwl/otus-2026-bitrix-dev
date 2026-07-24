<?php

define('PUBLIC_AJAX_MODE', 'Y');
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$siteId = isset($_REQUEST['site'])
    ? mb_substr(preg_replace('/[^a-z0-9_]/i', '', (string)$_REQUEST['site']), 0, 2)
    : '';
if ($siteId !== '')
{
    define('SITE_ID', $siteId);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

global $APPLICATION, $USER;

if (!$USER->IsAuthorized() || !check_bitrix_sessid() || !Loader::includeModule('crm'))
{
    http_response_code(403);
    die('Access denied');
}

$mode = (string)($_REQUEST['mode'] ?? '');
$signatureSalt = match ($mode)
{
    'results' => 'airecogn.recognition.grid',
    'logs' => 'airecogn.log.viewer',
    default => '',
};
$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS'])
    ? $_REQUEST['PARAMS']
    : [];
$componentParams = $signatureSalt !== '' && isset($componentData['signedParameters'])
    ? CCrmInstantEditorHelper::unsignComponentParams((string)$componentData['signedParameters'], $signatureSalt)
    : null;
$contactId = is_array($componentParams) ? (int)($componentParams['CONTACT_ID'] ?? 0) : 0;

if ($signatureSalt === ''
    || !is_array($componentParams)
    || (string)($componentParams['MODE'] ?? '') !== $mode
    || $contactId <= 0
    || !Container::getInstance()->getUserPermissions()->item()->canRead(CCrmOwnerType::Contact, $contactId))
{
    http_response_code(403);
    die('Access denied');
}

header('Content-Type: text/html; charset=' . LANG_CHARSET);
$APPLICATION->ShowAjaxHead();

if ($mode === 'logs')
{
    $APPLICATION->IncludeComponent(
        'airecogn:log.viewer',
        '',
        ['CONTACT_ID' => $contactId],
        false,
        ['HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y']
    );
}
else
{
    $ajaxLoader = [
        'url' => '/local/handler/airecogn/tab.php?mode=results&site=' . SITE_ID . '&' . bitrix_sessid_get(),
        'method' => 'POST',
        'dataType' => 'ajax',
        'data' => ['PARAMS' => $componentData],
    ];
    $APPLICATION->IncludeComponent(
        'airecogn:recognition.grid',
        '',
        [
            'CONTACT_ID' => $contactId,
            'AJAX_MODE' => 'Y',
            'AJAX_OPTION_JUMP' => 'N',
            'AJAX_OPTION_HISTORY' => 'N',
            'AJAX_LOADER' => $ajaxLoader,
        ],
        false,
        ['HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y']
    );
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
die();
