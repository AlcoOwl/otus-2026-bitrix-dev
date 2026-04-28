<?php

namespace Otus\Models;

use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\WebForm\Internals\ResultTable;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

class CrmWebFormResultTable extends ResultTable
{
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new DatetimeField('DATE_INSERT'))
                ->configureRequired()
                ->configureDefaultValue(static fn () => new DateTime()),
            (new IntegerField('FORM_ID'))
                ->configureRequired(),
            new StringField('ORIGIN_ID'),
            new Reference(
                'FORM',
                FormTable::class,
                Join::on('this.FORM_ID', 'ref.ID')
            ),
        ];
    }
}
