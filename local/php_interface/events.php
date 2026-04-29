<?php

use Bitrix\Crm\WebForm\Internals\ResultEntityTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EventManager as OrmEventManager;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('', 'TestHLBlockOnBeforeAdd', [
    'Otus\Event',
    'onBeforeElementAdd'
]);
/** @noinspection PhpUnhandledExceptionInspection */
if (Loader::includeModule('crm')) {
    /** @noinspection PhpUnhandledExceptionInspection */
    OrmEventManager::getInstance()->addEventHandler(
        ResultEntityTable::class,
        DataManager::EVENT_ON_AFTER_ADD,
        ['Otus\WebFormResultSync', 'onAfterResultEntityAdd']
    );
}
