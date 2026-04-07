<?php

namespace Otus\Models;

class ManufacturerPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const int IBLOCK_ID = 19;
    protected const array PROPERTY_FIELDS = [
        'COUNTRY' => [
            'code' => 'COUNTRY',
            'type' => 'string',
            'multiple' => true,
        ],
    ];
}
