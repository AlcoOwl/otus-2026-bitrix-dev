<?php

namespace Otus\Models;

use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\WebForm\Internals\ResultEntityTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

/**
 * Class LinkResultFormTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONTACT_ID int optional
 * <li> RESULT_ID int optional
 * <li> ACTIVITY_ID int optional
 * <li> FORM_ID int optional
 * <li> CREATED_AT datetime optional
 * </ul>
 *
 * @package Bitrix\Form
 **/
class LinkResultFormTable extends DataManager
{
    public const string ENTITY_NAME_CONTACT = 'CONTACT';
    public const string ACTIVITY_PROVIDER_WEBFORM = 'CRM_WEBFORM';

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'link_form_result';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ID' => (new IntegerField('ID'))
                ->configureTitle(Loc::getMessage('RESULT_ENTITY_ID_FIELD') ?: 'ID')
                ->configurePrimary()
                ->configureAutocomplete(),
            'CONTACT_ID' => (new IntegerField('CONTACT_ID'))
                ->configureTitle(Loc::getMessage('RESULT_ENTITY_CONTACT_ID_FIELD') ?: 'CONTACT_ID'),
            'RESULT_ID' => (new IntegerField('RESULT_ID'))
                ->configureTitle(Loc::getMessage('RESULT_ENTITY_RESULT_ID_FIELD') ?: 'RESULT_ID'),
            'ACTIVITY_ID' => (new IntegerField('ACTIVITY_ID'))
                ->configureTitle(Loc::getMessage('RESULT_ENTITY_ACTIVITY_ID_FIELD') ?: 'ACTIVITY_ID'),
            'FORM_ID' => (new IntegerField('FORM_ID'))
                ->configureTitle(Loc::getMessage('RESULT_ENTITY_FORM_ID_FIELD') ?: 'FORM_ID'),
            'CREATED_AT' => (new DatetimeField('CREATED_AT'))
                ->configureTitle(Loc::getMessage('RESULT_ENTITY_CREATED_AT_FIELD') ?: 'CREATED_AT'),
            (new Reference(
                'CONTACT',
                ContactWebFormTable::class,
                Join::on('this.CONTACT_ID', 'ref.ID')
            )) -> configureJoinType(Join::TYPE_INNER),
            (new Reference(
                'RESULT',
                CrmWebFormResultTable::class,
                Join::on('this.RESULT_ID', 'ref.ID')
            )) -> configureJoinType(Join::TYPE_INNER),
            (new Reference(
                'RESULT_ENTITY',
                ResultEntityTable::class,
                Join::on('this.RESULT_ID', 'ref.RESULT_ID')
                    ->whereColumn('this.CONTACT_ID', 'ref.ITEM_ID')
                    ->whereColumn('this.FORM_ID', 'ref.FORM_ID')
                    ->where('ref.ENTITY_NAME', self::ENTITY_NAME_CONTACT)
            )) -> configureJoinType(Join::TYPE_INNER),
            (new Reference(
                'ACTIVITY',
                ActivityTable::class,
                Join::on('this.ACTIVITY_ID', 'ref.ID')
                    ->where('ref.PROVIDER_ID', self::ACTIVITY_PROVIDER_WEBFORM)
                    ->whereColumn('this.RESULT_ID', 'ref.ASSOCIATED_ENTITY_ID')
            )) -> configureJoinType(Join::TYPE_INNER),
            (new Reference(
                'FORM',
                CrmWebFormTable::class,
                Join::on('this.FORM_ID', 'ref.ID')
            )) -> configureJoinType(Join::TYPE_INNER),
        ];
    }
}
