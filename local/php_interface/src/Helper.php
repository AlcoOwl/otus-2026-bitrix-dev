<?php

namespace Otus;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;

class Helper
{
    /**
     * Возвращает идентификатор информационного блока по его символьному коду.
     *
     * @param string|null $code Символьный код инфоблока.
     * @return int|null Идентификатор инфоблока, либо null, если модуль не загружен
     * или инфоблок не найден.
     */
    public static function getIblockIdByCode(?string $code): ?int
    {
        if (!Loader::includeModule('iblock')) {
            return null;
        }
        return IblockTable::getList([
            'filter' => [
                'CODE' => $code
            ],
            'select' => [
                'ID'
            ],
            'cache' => [
                'ttl' => 86400,
            ],
        ]) -> fetch()['ID'] ?? null;
    }
}