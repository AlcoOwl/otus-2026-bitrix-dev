<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') || die();

Loc::loadMessages(__FILE__);

global $APPLICATION, $USER;
if (!$USER->IsAdmin())
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule('airecogn');

$moduleId = 'airecogn';
$checkboxes = ['enabled', 'logging_enabled', 'oracle_enabled'];
$fields = [
    'log_level',
    'log_tail_size',
    'nextcloud_webdav_url',
    'nextcloud_username',
    'nextcloud_password',
    'nextcloud_remote_dir',
    'oracle_username',
    'oracle_password',
    'oracle_connection_string',
    'oracle_charset',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid())
{
    $logLevel = $_POST['log_level'] ?? null;
    $logTailSize = $_POST['log_tail_size'] ?? null;
    $validLogSettings = is_string($logLevel)
        && in_array($logLevel, ['debug', 'info', 'error'], true)
        && is_string($logTailSize)
        && ctype_digit($logTailSize)
        && (int)$logTailSize >= 10
        && (int)$logTailSize <= 2000;

    if (!$validLogSettings)
    {
        CAdminMessage::ShowMessage(Loc::getMessage('AIRECOGN_OPTIONS_INVALID'));
    }
    else
    {
        foreach ($checkboxes as $name)
        {
            Option::set($moduleId, $name, isset($_POST[$name]) ? 'Y' : 'N');
        }

        foreach ($fields as $name)
        {
            Option::set($moduleId, $name, trim((string)($_POST[$name] ?? '')));
        }

        CAdminMessage::ShowMessage([
            'MESSAGE' => Loc::getMessage('AIRECOGN_OPTIONS_SAVED'),
            'TYPE' => 'OK',
        ]);
    }
}

$tabs = [
    ['DIV' => 'airecogn_main', 'TAB' => Loc::getMessage('AIRECOGN_TAB_MAIN'), 'TITLE' => Loc::getMessage('AIRECOGN_TAB_MAIN_TITLE')],
    ['DIV' => 'airecogn_integrations', 'TAB' => Loc::getMessage('AIRECOGN_TAB_INTEGRATIONS'), 'TITLE' => Loc::getMessage('AIRECOGN_TAB_INTEGRATIONS_TITLE')],
];
$tabControl = new CAdminTabControl('airecognOptions', $tabs);
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=airecogn&amp;lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%"><?= Loc::getMessage('AIRECOGN_OPTION_ENABLED') ?></td>
        <td><input type="checkbox" name="enabled" value="Y" <?= Option::get($moduleId, 'enabled', 'Y') === 'Y' ? 'checked' : '' ?>></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('AIRECOGN_OPTION_LOGGING_ENABLED') ?></td>
        <td><input type="checkbox" name="logging_enabled" value="Y" <?= Option::get($moduleId, 'logging_enabled', 'Y') === 'Y' ? 'checked' : '' ?>></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('AIRECOGN_OPTION_LOG_LEVEL') ?></td>
        <td>
            <?php $logLevel = Option::get($moduleId, 'log_level', 'info'); ?>
            <select name="log_level">
                <?php foreach (['debug', 'info', 'error'] as $level): ?>
                    <option value="<?= $level ?>" <?= $logLevel === $level ? 'selected' : '' ?>><?= $level ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('AIRECOGN_OPTION_LOG_TAIL_SIZE') ?></td>
        <td><input type="number" min="10" max="2000" name="log_tail_size" value="<?= (int)Option::get($moduleId, 'log_tail_size', '200') ?>"></td>
    </tr>

    <?php $tabControl->BeginNextTab(); ?>
    <?php
    $textOptions = [
        'nextcloud_webdav_url' => 'AIRECOGN_OPTION_NEXTCLOUD_URL',
        'nextcloud_username' => 'AIRECOGN_OPTION_NEXTCLOUD_USERNAME',
        'nextcloud_password' => 'AIRECOGN_OPTION_NEXTCLOUD_PASSWORD',
        'nextcloud_remote_dir' => 'AIRECOGN_OPTION_NEXTCLOUD_REMOTE_DIR',
        'oracle_username' => 'AIRECOGN_OPTION_ORACLE_USERNAME',
        'oracle_password' => 'AIRECOGN_OPTION_ORACLE_PASSWORD',
        'oracle_connection_string' => 'AIRECOGN_OPTION_ORACLE_CONNECTION',
        'oracle_charset' => 'AIRECOGN_OPTION_ORACLE_CHARSET',
    ];
    ?>
    <tr>
        <td><?= Loc::getMessage('AIRECOGN_OPTION_ORACLE_ENABLED') ?></td>
        <td><input type="checkbox" name="oracle_enabled" value="Y" <?= Option::get($moduleId, 'oracle_enabled', 'N') === 'Y' ? 'checked' : '' ?>></td>
    </tr>
    <?php foreach ($textOptions as $name => $message): ?>
        <tr>
            <td width="40%"><?= Loc::getMessage($message) ?></td>
            <td>
                <input
                    type="<?= str_contains($name, 'password') ? 'password' : 'text' ?>"
                    size="60"
                    name="<?= $name ?>"
                    value="<?= htmlspecialcharsbx(Option::get($moduleId, $name, $name === 'nextcloud_remote_dir' ? '/Calls/{Y}/{m}' : '')) ?>"
                    autocomplete="off"
                >
            </td>
        </tr>
    <?php endforeach; ?>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= Loc::getMessage('AIRECOGN_OPTIONS_SAVE') ?>" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form>
