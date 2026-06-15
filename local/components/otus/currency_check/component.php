<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arResult = [
    'ERROR' => '',
    'CURRENCY' => '',
    'FULL_NAME' => '',
    'AMOUNT_CNT' => 0,
    'AMOUNT' => 0.0,
    'UNIT_RATE' => 0.0,
    'BASE' => 'N',
];

$currencyCode = strtoupper(trim((string)($arParams['CURRENCY'] ?? '')));

if ($currencyCode === '') {
    $arResult['ERROR'] = 'Валюта не выбрана.';
    $this->IncludeComponentTemplate();
    return;
}

$currency = CCurrency::GetByID($currencyCode);

$amountCnt = (int)$currency['AMOUNT_CNT'];
$amount = (float)$currency['AMOUNT'];

$languageCurrency = CCurrency::GetList('currency', 'asc');
while ($item = $languageCurrency->Fetch()) {
    if ($item['CURRENCY'] === $currencyCode) {
        $currency['FULL_NAME'] = $item['FULL_NAME'];
        break;
    }
}

$arResult = [
    'ERROR' => '',
    'CURRENCY' => $currencyCode,
    'FULL_NAME' => (string)($currency['FULL_NAME'] ?? ''),
    'AMOUNT_CNT' => $amountCnt,
    'AMOUNT' => $amount,
    'UNIT_RATE' => $amountCnt > 0 ? $amount / $amountCnt : 0.0,
    'BASE' => (string)($currency['BASE'] ?? 'N'),
];

$this->IncludeComponentTemplate();
