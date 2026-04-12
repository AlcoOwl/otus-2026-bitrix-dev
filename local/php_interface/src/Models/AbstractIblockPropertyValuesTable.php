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
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
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
     * Возвращает имя таблицы базы данных с единичными свойствами инфоблока (по крайней мере списка).
     *
     * @return string Имя таблицы базы данных.
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
                $map[] = static::createMultiplePropertyRelation($fieldName, $propertyCode, $fieldType, $propertyConfig);
                continue;
            }

            $map[] = static::createPropertyField($fieldName, $fieldType)
                ->configureColumnName(static::getSinglePropertyColumnName($propertyCode));
        }

        return $map;
    }

    /**
     * Возвращает карту значений множественных свойств для заданного набора элементов.
     *
     * @param array $elementIds Массив идентификаторов элементов, для которых необходимо получить значения свойств.
     * @param string $fieldName Название поля свойства, значения которого нужно извлечь.
     *
     * @return array Ассоциативный массив, где ключами являются идентификаторы элементов,
     * а значениями — массивы значений множественного свойства.
     */
    public static function getMultiplePropertyValuesMap(array $elementIds, string $fieldName): array
    {
        $fieldName = strtoupper($fieldName);
        $elementIds = array_values(array_unique(array_filter(array_map('intval', $elementIds))));

        if (!$elementIds || empty(static::PROPERTY_FIELDS[$fieldName]['multiple'])) {
            return [];
        }

        $objects = static::query()
            ->setSelect([
                'IBLOCK_ELEMENT_ID',
            ])
            ->whereIn('IBLOCK_ELEMENT_ID', $elementIds)
            ->fetchCollection();

        $objects->fill($fieldName);

        $valuesMap = [];

        foreach ($objects as $object) {
            $values = [];
            $collection = $object->get($fieldName);

            if ($collection) {
                foreach ($collection->getAll() as $item) {
                    $values[] = $item->get('VALUE');
                }
            }

            $valuesMap[$object->get('IBLOCK_ELEMENT_ID')] = $values;
        }

        return $valuesMap;
    }

    /**
     * Создает объект поля базы данных на основе заданного типа.
     *
     * @param string $fieldName Имя поля, которое будет использоваться в базе данных.
     * @param string $fieldType Тип поля, определяющий его конфигурацию (например, integer, float, string и др.).
     * @return Field Объект поля, соответствующий указанному типу.
     * @throws SystemException Исключение выбрасывается, если указан неизвестный тип поля.
     */
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

    /**
     * Создает отношение "один ко многим" для свойства с несколькими значениями.
     *
     * @param string $fieldName Название поля, представляющего связь.
     * @param string $propertyCode Код свойства, используемого в связи.
     * @param string $fieldType Тип поля, определяющий тип связи.
     * @return OneToMany Объект отношения "один ко многим", настроенный для свойства с несколькими значениями.
     */
    protected static function createMultiplePropertyRelation(
        string $fieldName,
        string $propertyCode,
        string $fieldType,
        array $propertyConfig = []
    ): OneToMany {
        return new OneToMany(
            $fieldName,
            static::getMultiplePropertyEntity($fieldName, $propertyCode, $fieldType, $propertyConfig),
            'OWNER'
        );
    }

    /**
     * Создает и кэширует сущность для обработки множественных значений свойства инфоблока.
     *
     * @param string $fieldName Имя поля, связанного со значением свойства.
     * @param string $propertyCode Символьный код свойства.
     * @param string $fieldType Тип данных значения свойства.
     * @return Entity Скомпилированная сущность, соответствующая множественным значениям указанного свойства.
     */
    protected static function getMultiplePropertyEntity(
        string $fieldName,
        string $propertyCode,
        string $fieldType,
        array $propertyConfig = []
    ): Entity {
        $propertyId = static::getPropertyId($propertyCode);
        $cacheKey = static::IBLOCK_ID . ':' . $propertyId;

        if (!isset(static::$multiplePropertyEntityCache[$cacheKey])) {
            $entityName = 'Iblock' . static::IBLOCK_ID . $fieldName . 'MultiplePropertyValue';
            $fields = [
                (new IntegerField('ID'))
                    ->configurePrimary()
                    ->configureAutocomplete(),
                new IntegerField('IBLOCK_ELEMENT_ID'),
                new IntegerField('IBLOCK_PROPERTY_ID'),
                static::createPropertyField('VALUE', $fieldType),
                static::createMultiplePropertyOwnerReference($propertyId),
            ];

            if (!empty($propertyConfig['link_element'])) {
                $fields[] = static::createMultiplePropertyElementReference();
            }

            static::$multiplePropertyEntityCache[$cacheKey] = Entity::compileEntity(
                $entityName,
                $fields,
                [
                    'namespace' => __NAMESPACE__,
                    'table_name' => 'b_iblock_element_prop_m' . static::IBLOCK_ID,
                ]
            );
        }

        return static::$multiplePropertyEntityCache[$cacheKey];
    }

    /**
     * Создает объект связи (reference) для связывания значения свойства с элементом инфоблока.
     *
     * @return Reference Объект связи, определяющий соединение значения свойства с элементом инфоблока через таблицу элементов.
     */
    protected static function createMultiplePropertyElementReference(): Reference
    {
        return (new Reference(
            'ELEMENT',
            ElementTable::class,
            Join::on('this.VALUE', 'ref.ID')
        ))->configureJoinType(Join::TYPE_LEFT);
    }

    /**
     * Создает ссылку на владельцев свойства с учетом нескольких значений.
     *
     * @param int $propertyId Идентификатор свойства.
     * @return Reference Объект ссылки, настроенный для соединения с владельцами свойства.
     */
    protected static function createMultiplePropertyOwnerReference(int $propertyId): Reference
    {
        return (new Reference(
            'OWNER',
            static::class,
            Join::on('this.IBLOCK_ELEMENT_ID', 'ref.IBLOCK_ELEMENT_ID')
                ->where('this.IBLOCK_PROPERTY_ID', $propertyId)
        ))->configureJoinType(Join::TYPE_LEFT);
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

    /**
     * Возвращает идентификатор свойства инфоблока по его символьному коду.
     *
     * @param string $propertyCode Символьный код свойства.
     * @return int Идентификатор свойства.
     * @throws SystemException Если свойство не найдено.
     */
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

    /**
     * Удаляет сущность по первичному ключу.
     *
     * @param mixed $primary Первичный ключ сущности, которую необходимо удалить.
     * @return DeleteResult Результат операции удаления.
     */
    public static function delete($primary): DeleteResult
    {
        throw new NotImplementedException();
    }
}
