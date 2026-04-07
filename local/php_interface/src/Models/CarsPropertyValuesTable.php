<?php

namespace Otus\Models;

class CarsPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const int IBLOCK_ID = 18;
    protected const array PROPERTY_FIELDS = [
        'MANUFACTURER_ID' => [
            'code' => 'MANUFACTURER_ID',
            'type' => 'integer',
        ],
    ];
}
