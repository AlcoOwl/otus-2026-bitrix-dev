<?php

namespace Otus\Iblock\Property;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Otus\Models\DoctorsPropertiesTable;
use Otus\Models\ProceduresPropertiesTable;

final class BookingProperty
{
    public const string USER_TYPE = 'otus_booking';

    /**
     * Возвращает описание пользовательского типа свойства инфоблока.
     *
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        Loc::loadMessages(__FILE__);

        return [
            'PROPERTY_TYPE' => PropertyTable::TYPE_STRING,
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => Loc::getMessage('OTUS_BOOKING_PROPERTY_DESCRIPTION'),
            'GetPropertyFieldHtml' => [self::class, 'getPropertyFieldHtml'],
        ];
    }

    /**
     * Выводит процедуры, связанные с редактируемым врачом.
     *
     * @param array $property
     * @param array $value
     * @param array $htmlControl
     *
     * @return string
     */
    public static function getPropertyFieldHtml(array $property, array $value, array $htmlControl): string
    {
        Loc::loadMessages(__FILE__);

        if ($htmlControl['VALUE'] === 'PROPERTY_DEFAULT_VALUE')
        {
            return '';
        }

        $doctorId = (int)$_REQUEST['ID'];
        if ($doctorId <= 0)
        {
            return htmlspecialcharsbx((string)Loc::getMessage('OTUS_BOOKING_PROPERTY_SAVE_DOCTOR'));
        }

        $doctor = DoctorsPropertiesTable::query()
            ->setSelect([
                'IBLOCK_ELEMENT_ID',
                'PROC_ID.ELEMENT.ID',
                'PROC_ID.ELEMENT.NAME',
            ])
            ->where('IBLOCK_ELEMENT_ID', $doctorId)
            ->fetchObject();

        $buttons = [];
        foreach ($doctor->get('PROC_ID')->getAll() as $procedure)
        {
            $procedureElement = $procedure->get('ELEMENT');
            $procedureProperties = ProceduresPropertiesTable::getByPrimary(
                (int)$procedureElement->getId(),
                ['select' => ['DURATION']]
            )->fetch();
            $buttons[] = '<button'
                . ' type="button"'
                . ' data-role="otus-booking-procedure"'
                . ' data-doctor-id="' . $doctorId . '"'
                . ' data-procedure-id="' . (int)$procedureElement->getId() . '"'
                . ' data-procedure-name="' . htmlspecialcharsbx((string)$procedureElement->get('NAME')) . '"'
                . ' data-procedure-duration="' . (int)$procedureProperties['DURATION'] . '"'
                . '>'
                . htmlspecialcharsbx((string)$procedureElement->get('NAME'))
                . '</button>';
        }

        if ($buttons === [])
        {
            return htmlspecialcharsbx((string)Loc::getMessage('OTUS_BOOKING_PROPERTY_NO_PROCEDURES'));
        }

        Extension::load('otus.booking');

        return '<div data-role="otus-booking-procedures">' . implode(' ', $buttons) . '</div>';
    }
}
