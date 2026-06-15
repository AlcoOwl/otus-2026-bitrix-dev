<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$currencyList = [];

if (Loader::includeModule('currency')) {
    $currencyIterator = CCurrency::GetList('sort', 'asc');

    while ($currency = $currencyIterator->Fetch()) {
        $currencyCode = (string)$currency['CURRENCY'];
        $currencyName = trim((string)($currency['FULL_NAME'] ?? ''));

        $currencyList[$currencyCode] = $currencyName !== ''
            ? $currencyCode . ' - ' . $currencyName
            : $currencyCode;
    }
}

$arComponentParameters = [
    'PARAMETERS' => [
        'CURRENCY' => [
            'PARENT' => 'BASE',
            'NAME' => 'Валюта',
            'TYPE' => 'LIST',
            'VALUES' => $currencyList,
            'DEFAULT' => key($currencyList) ?: '',
            'REFRESH' => 'N',
        ],
    ],
];
