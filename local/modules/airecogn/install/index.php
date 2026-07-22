<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class airecogn extends CModule
{
    public $MODULE_ID = 'airecogn';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '0.1.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '';
        $this->MODULE_NAME = Loc::getMessage('AIRECOGN_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('AIRECOGN_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('AIRECOGN_PARTNER_NAME');
        $this->PARTNER_URI = 'https://';
    }

    public function DoInstall(): void
    {
        global $APPLICATION;

        if (!check_bitrix_sessid())
        {
            return;
        }

        if (!IsModuleInstalled($this->MODULE_ID))
        {
            if (!$this->InstallDB())
            {
                return;
            }

            RegisterModule($this->MODULE_ID);
            $this->InstallEvents();
            $this->InstallFiles();
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('AIRECOGN_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    public function DoUninstall(): void
    {
        global $APPLICATION;

        if (!check_bitrix_sessid())
        {
            return;
        }

        if (!$this->UnInstallDB())
        {
            return;
        }

        $this->UnInstallEvents();
        $this->UnInstallFiles();
        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('AIRECOGN_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );
    }

    public function InstallDB(): bool
    {
        global $DB, $APPLICATION;

        $errors = $DB->RunSQLBatch(__DIR__ . '/db/mysql/install.sql');
        if ($errors !== false)
        {
            $APPLICATION->ThrowException(implode("\n", $errors));
            return false;
        }

        return true;
    }

    public function UnInstallDB(): bool
    {
        global $DB, $APPLICATION;

        $errors = $DB->RunSQLBatch(__DIR__ . '/db/mysql/uninstall.sql');
        if ($errors !== false)
        {
            $APPLICATION->ThrowException(implode("\n", $errors));
            return false;
        }

        return true;
    }

    public function InstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'voximplant',
            'onCallEnd',
            $this->MODULE_ID,
            '\\Airecogn\\Integration\\VoximplantEventHandler',
            'onCallEnd'
        );
        $eventManager->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Airecogn\\Integration\\CrmTabHandler',
            'onTabsInitialized'
        );
        $eventManager->registerEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID,
            '\\Airecogn\\Integration\\AdminMenu',
            'onBuildGlobalMenu'
        );

        return true;
    }

    public function UnInstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'voximplant',
            'onCallEnd',
            $this->MODULE_ID,
            '\\Airecogn\\Integration\\VoximplantEventHandler',
            'onCallEnd'
        );
        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Airecogn\\Integration\\CrmTabHandler',
            'onTabsInitialized'
        );
        $eventManager->unRegisterEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID,
            '\\Airecogn\\Integration\\AdminMenu',
            'onBuildGlobalMenu'
        );

        return true;
    }

    public function InstallFiles(): bool
    {
        CopyDirFiles(
            __DIR__ . '/components',
            Application::getDocumentRoot() . '/local/components',
            true,
            true
        );
        CopyDirFiles(
            __DIR__ . '/public',
            Application::getDocumentRoot() . '/local/handler/airecogn',
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles(): bool
    {
        DeleteDirFilesEx('/local/components/airecogn');
        DeleteDirFilesEx('/local/handler/airecogn');

        return true;
    }
}
