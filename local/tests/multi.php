<?php

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\ORM\EO_ElementV2;
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
global $APPLICATION;

$APPLICATION->SetTitle("Test relations and Multi");
$countryId = 38;
$apiCode = 'strana';

try {
    Loader::includeModule('iblock');

    $entity = IblockTable::compileEntity($apiCode);

    if (!$entity) {
        throw new RuntimeException('Iblock entity not found by API_CODE=strana');
    }

    $elementClass = $entity->getDataClass();

    /** @var EO_ElementV2 $country */
    $country = $elementClass::getByPrimary($countryId, [
        'select' => [
            '*',
            'CURRENCY',
            'CITIES.ELEMENT.NAME',
            'CAPITAL.ELEMENT.NAME',
        ],
    ])->fetchObject();

    if (!$country) {
        throw new RuntimeException('Country element not found by ID='.$countryId);
    }

    print_r('CAPITAL: ' . $country->get('CAPITAL')->getElement()->get('ID') . ' ' . $country->get('CAPITAL')->getElement()->get('NAME'));
    print_r('<br>' . 'CITIES:');
    foreach ($country->get('CITIES')->getAll() as $city) {
        print_r('<br>' . $city->getElement()->get('ID') . ' ' . $city->getElement()->get('NAME'));
    }

    $country = $elementClass::getByPrimary($countryId, [
        'select' => [
//            '*',
            'CURRENCY',
            'CITIES.ELEMENT.NAME',
            'CAPITAL.ELEMENT.NAME',
        ],
    ])->fetchall();
    print_r(dump($country));

    $country = $elementClass::getList([
        'select' => [
//            '*',
            'CURRENCY',
            'CITIES.ELEMENT.NAME',
            'CAPITAL.ELEMENT.NAME',
        ],
        'count_total' => true,
    ]);
    print_r('<br>' . dump($country -> getCount()));
} catch (Throwable $e) {
    print_r($e->getMessage());
}
