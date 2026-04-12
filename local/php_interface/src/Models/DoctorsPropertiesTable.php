<?php

namespace Otus\Models;

class DoctorsPropertiesTable extends AbstractIblockPropertyValuesTable
{
    const int IBLOCK_ID = 20;
    protected const array PROPERTY_FIELDS = [
        'FIO' => [
            'code' => 'FIO',
            'type' => 'string',
            'multiple' => false,
        ],
        'EXPERIENCE' => [
            'code' => 'EXPERIENCE',
            'type' => 'integer',
            'multiple' => false,
        ],
        'PROC_ID' => [
            'code' => 'PROC_ID',
            'type' => 'integer',
            'multiple' => true,
            'link_element' => true,
        ]
    ];
}
