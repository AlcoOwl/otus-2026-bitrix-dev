<?php

namespace Otus\Models;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

abstract class AbstractIblockPropertyValuesTable extends DataManager
{
    const IBLOCK_ID = null;
    protected const PROPERTY_FIELDS = [];
    protected static array $propertyIdCache = [];
    protected static array $propertyColumnNameCache = [];
    protected static array $multiplePropertyEntityCache = [];

    /**
     * Возвращает название таблицы, сформированное с использованием идентификатора инфоблока.
     *
     * @return string Название таблицы.
     */
    public static function getTableName(): string
    {
        return 'b_iblock_element_prop_s' . static::IBLOCK_ID;
    }

    /**
     * Возвращает описание карты полей базы данных.
     *
     * @return array Массив объектов полей, определяющий структуру карты базы данных.
     */
    public static function getMap(): array
    {
        return array_merge([
            (new IntegerField('IBLOCK_ELEMENT_ID'))
                ->configurePrimary(),
            (new Reference(
                'ELEMENT',
                ElementTable::class,
                Join::on('this.IBLOCK_ELEMENT_ID', 'ref.ID')
            ))->configureJoinType(Join::TYPE_INNER),
        ], static::getPropertyMap());
    }

    /**
     * Формирует карту свойств, используемых для описания структуры базы данных.
     *
     * @return array Массив объектов полей, описывающий свойства и их связи с элементами инфоблока.
     */
    protected static function getPropertyMap(): array
    {
        $map = [];

        foreach (static::PROPERTY_FIELDS as $fieldName => $propertyConfig) {
            $propertyCode = $propertyConfig['code'];
            $fieldType = $propertyConfig['type'] ?? 'string';
            $isMultiple = $propertyConfig['multiple'] ?? false;

            if ($isMultiple) {
                $referenceName = $fieldName . '_REF';

                $map[] = (new Reference(
                    $referenceName,
                    static::getMultiplePropertyEntity($fieldName, $propertyCode, $fieldType),
                    Join::on('this.IBLOCK_ELEMENT_ID', 'ref.IBLOCK_ELEMENT_ID')
                        ->where('ref.IBLOCK_PROPERTY_ID', static::getPropertyId($propertyCode))
                ))->configureJoinType(Join::TYPE_LEFT);
                $map[] = new ExpressionField($fieldName, '%s', [$referenceName . '.VALUE']);

                continue;
            }

            $map[] = static::createPropertyField($fieldName, $fieldType)
                ->configureColumnName(static::getSinglePropertyColumnName($propertyCode));
        }

        return $map;
    }

    protected static function createPropertyField(string $fieldName, string $fieldType): Field
    {
        return match ($fieldType) {
            'integer' => new IntegerField($fieldName),
            'float' => new FloatField($fieldName),
            'decimal' => new DecimalField($fieldName),
            'string' => new StringField($fieldName),
            'text' => new TextField($fieldName),
            'boolean' => new BooleanField($fieldName),
            'date' => new DateField($fieldName),
            'datetime' => new DatetimeField($fieldName),
            default => throw new SystemException('Unknown property field type: ' . $fieldType),
        };
    }

    protected static function getMultiplePropertyEntity(
        string $fieldName,
        string $propertyCode,
        string $fieldType
    ): Entity {
        $propertyId = static::getPropertyId($propertyCode);
        $cacheKey = static::IBLOCK_ID . ':' . $propertyId;

        if (!isset(static::$multiplePropertyEntityCache[$cacheKey])) {
            $entityName = 'Iblock' . static::IBLOCK_ID . $fieldName . 'MultiplePropertyValue';

            static::$multiplePropertyEntityCache[$cacheKey] = Entity::compileEntity(
                $entityName,
                [
                    (new IntegerField('ID'))
                        ->configurePrimary()
                        ->configureAutocomplete(),
                    new IntegerField('IBLOCK_ELEMENT_ID'),
                    new IntegerField('IBLOCK_PROPERTY_ID'),
                    static::createPropertyField('VALUE', $fieldType),
                ],
                [
                    'namespace' => __NAMESPACE__,
                    'table_name' => 'b_iblock_element_prop_m' . static::IBLOCK_ID,
                ]
            );
        }

        return static::$multiplePropertyEntityCache[$cacheKey];
    }

    /**
     * Возвращает название колонки таблицы, соответствующей указанному коду свойства.
     *
     * @param string $propertyCode Код свойства.
     * @return string Название колонки в таблице, связанной с указанным свойством.
     * @throws SystemException Если свойство с указанным кодом не найдено.
     */
    protected static function getSinglePropertyColumnName(string $propertyCode): string
    {
        $cacheKey = static::IBLOCK_ID . ':' . $propertyCode;

        if (!isset(static::$propertyColumnNameCache[$cacheKey])) {
            static::$propertyColumnNameCache[$cacheKey] = 'PROPERTY_' . static::getPropertyId($propertyCode);
        }

        return static::$propertyColumnNameCache[$cacheKey];
    }

    protected static function getPropertyId(string $propertyCode): int
    {
        $cacheKey = static::IBLOCK_ID . ':' . $propertyCode;

        if (!isset(static::$propertyIdCache[$cacheKey])) {
            $property = PropertyTable::getRow([
                'select' => ['ID'],
                'filter' => [
                    '=IBLOCK_ID' => static::IBLOCK_ID,
                    '=CODE' => $propertyCode,
                ],
            ]);

            if (!$property) {
                throw new SystemException(
                    'Property not found: ' . $propertyCode . ' for iblock ' . static::IBLOCK_ID
                );
            }

            static::$propertyIdCache[$cacheKey] = (int)$property['ID'];
        }

        return static::$propertyIdCache[$cacheKey];
    }

    public static function delete($primary): DeleteResult
    {
        throw new NotImplementedException();
    }
}
