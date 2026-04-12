<?php

namespace Otus\Models;

class ProceduresPropertiesTable extends AbstractIblockPropertyValuesTable
{
    const int IBLOCK_ID = 21;
    protected const array PROPERTY_FIELDS = [
        'PRICE' => [
            'code' => 'PRICE',
            'type' => 'integer',
            'multiple' => false,
        ]
    ];
}