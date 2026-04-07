<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Вывод связанных полей');
use Otus\Models\CarsPropertyValuesTable as CarsTable;
use Otus\Models\ManufacturerPropertyValuesTable as Manufacturer;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

try {
    $cars = CarsTable::getList([
        'select' => [
            'ID' => 'IBLOCK_ELEMENT_ID',
            'NAME' => 'ELEMENT.NAME',
            'MANUFACTURER_ID' => 'MANUFACTURER_ID',
        ],
    ])->fetchAll();
} catch (Throwable $e) {
    print_r($e->getMessage());
}

print_r(dump($cars));

try {
    $cars = CarsTable::query()
        ->registerRuntimeField(
            (new Reference(
                'MANUFACTURER',
                Manufacturer::class,
                Join::on('this.MANUFACTURER_ID', 'ref.IBLOCK_ELEMENT_ID')
            ))->configureJoinType(Join::TYPE_INNER)
        )
        ->setSelect([
            '*',
            'NAME' => 'ELEMENT.NAME',
            'MANUFACTURER_NAME' => 'MANUFACTURER.ELEMENT.NAME',
            'MANUFACTURER_COUNTRY' => 'MANUFACTURER.COUNTRY',
        ])
        ->setOrder(['MANUFACTURER.COUNTRY' => 'desc'])
        ->fetchAll();
} catch (Throwable $e) {
    print_r($e->getMessage());
}

print_r(dump($cars));
