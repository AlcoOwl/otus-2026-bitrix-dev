<?php

namespace Airecogn\Model;

use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\TextField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class RecognitionResultTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_airecogn_result';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new IntegerField('ACTIVITY_ID'))->configureRequired(),
            (new StringField('STATUS'))
                ->configureRequired()
                ->configureDefaultValue('pending')
                ->addValidator(new LengthValidator(null, 20)),
            new TextField('SUMMARY'),
            (new DatetimeField('CREATED_AT'))->configureRequired(),
            (new DatetimeField('UPDATED_AT'))->configureRequired(),
            new DatetimeField('PROCESSED_AT'),
        ];
    }
}

