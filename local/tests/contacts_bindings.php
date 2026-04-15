<?php

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\LoaderException;
use Otus\Debug;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle('Привяка нескольких контактов к СП');

try {
    Loader::includeModule('crm');
} catch (LoaderException $e) {
    Debug::writeToLog($e->getMessage(), 'CRM_MODULE_LOAD_ERROR', 'crud_test.log');
}

$itemFactory = Container::getInstance()->getFactory(1038);
//$item = $itemFactory->getItem(1, ['*']);
//$item->setContactBindings(
//    [
//        ['CONTACT_ID' => 1, 'IS_PRIMARY' => 'Y','SORT' => 100],
//        ['CONTACT_ID' => 2, 'IS_PRIMARY' => 'N','SORT' => 200],
//        ['CONTACT_ID' => 3, 'IS_PRIMARY' => 'N','SORT' => 300]
//    ]
//);
//$result = $itemFactory->getUpdateOperation($item)->launch();
//
//dump($result);

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php";