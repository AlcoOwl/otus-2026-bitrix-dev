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
            'MANUFACTURER_COUNTRY' => 'MANUFACTURER.COUNTRY.VALUE',
        ])
        ->setOrder(['MANUFACTURER.ELEMENT.NAME' => 'desc'])
        ->fetchAll();
} catch (Throwable $e) {
    print_r($e->getMessage());
}

print_r(dump($cars));

try {
    $carsCollection = CarsTable::query()
        ->registerRuntimeField(
            (new Reference(
                'MANUFACTURER',
                Manufacturer::class,
                Join::on('this.MANUFACTURER_ID', 'ref.IBLOCK_ELEMENT_ID')
            ))->configureJoinType(Join::TYPE_INNER)
        )
        ->setSelect([
            '*',
            'ELEMENT',
            'MANUFACTURER',
            'MANUFACTURER.ELEMENT',
        ])
        ->setOrder(['MANUFACTURER.ELEMENT.NAME' => 'desc'])
        ->fetchCollection();
    $manufacturerCountryMap = Manufacturer::getMultiplePropertyValuesMap(
        array_map(
            static fn($car) => $car->get('MANUFACTURER_ID'),
            $carsCollection->getAll()
        ),
        'COUNTRY'
    );

    $collectionData = [];

    foreach ($carsCollection as $car) {
        $collectionData[] = [
            'IBLOCK_ELEMENT_ID' => $car->get('IBLOCK_ELEMENT_ID'),
            'MANUFACTURER_ID' => $car->get('MANUFACTURER_ID'),
            'NAME' => $car->get('ELEMENT')?->get('NAME'),
            'MANUFACTURER_NAME' => $car->get('MANUFACTURER')?->get('ELEMENT')?->get('NAME'),
            'MANUFACTURER_COUNTRY' => $manufacturerCountryMap[$car->get('MANUFACTURER_ID')] ?? [],
        ];
    }
} catch (Throwable $e) {
    print_r($e->getMessage());
}
print_r(dump($collectionData ?? []));
