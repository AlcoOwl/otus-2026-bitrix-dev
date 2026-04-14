<?php

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\LoaderException;
use Bitrix\Crm\Model\Dynamic\TypeTable; //Получение ID смарта (b_crm_dynamic_type)
use Otus\Debug;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle('Пример CRUD операций с сущностями CRM');

try {
    Loader::includeModule('crm');
} catch (LoaderException $e) {
    Debug::writeToLog($e->getMessage(), 'CRM_MODULE_LOAD_ERROR', 'crud_test.log');
}
$itemFactory = Container::getInstance()->getFactory(1038);

$parentDealFieldCode = 'PARENT_ID_' . CCrmOwnerType::Deal;
$items = $itemFactory->getItems(
    [
        'select' => ['*'],
        'filter' => []
    ]
);

foreach ($items as $item) {
    dump($item->getData());
}

$dealFactory = Container::getInstance()->getFactory(CCrmOwnerType::Deal);

$deals = $dealFactory->getItems(
    [
        'select' => ['*'],
        'filter' => [
            'ID' => 10
        ]
    ]
);

foreach ($deals as $deal) {
    dump($deal->getData());
}

echo ($dealFactory->getItemsCount());

$contactFactory = Container::getInstance()->getFactory(CCrmOwnerType::Contact);

//$contactFactory->createItem();

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php";