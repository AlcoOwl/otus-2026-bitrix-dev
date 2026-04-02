<?php

use Otus\Helper;

require_once  $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

global $APPLICATION;
$APPLICATION->SetTitle('Демонстрация работы вардампера');

dump((object) [
    'DODO' => '565456256265',
    'Ya' => '156145612312'
]);

$iblockCode = 'clients_s1';
dump([
    'iblockId' => Helper::getIblockIdByCode($iblockCode),
    'iblockCode' => $iblockCode,
]);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';