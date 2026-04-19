<?php

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Otus\Debug;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle('Highload block test');

Loader::includeModule('highloadblock');

$highloadBlockCode = 'TestHLBlock';

$highloadBlockInfo = HighloadBlockTable::getList(
    [
        'filter' => [
            'NAME' => $highloadBlockCode
        ]
    ]
)->fetch();

dump($highloadBlockInfo);

//$highloadBlockId = $highloadBlockInfo['ID'];
//dump($highloadBlockId);

$highloadBlockEntity = HighloadBlockTable::compileEntity($highloadBlockInfo);

$highloadBlockClass = $highloadBlockEntity->getDataClass();
//$highloadBlockClass = $highloadBlockCode . 'Table';

$items = $highloadBlockClass::getList()->fetchCollection();

foreach ($items as $item) {
    dump($item->get('UF_NAME'));
}

dump($highloadBlockClass::getList()->fetchAll());

//$highloadBlockClass::addMulti(
//    [
//        [
//            'UF_NAME' => 'Желтый',
//            'UF_XML_ID' => CUtil::translit('Желтый', 'ru'),
//        ],
//        [
//            'UF_NAME' => 'Синий',
//            'UF_XML_ID' => CUtil::translit('Синий', 'ru'),
//        ]
//    ],
//    false
//);

//foreach ($items as $item)
//    $highloadBlockClass::update(
//        $item->get('ID'),
//        [
//            'UF_XML_ID' => CUtil::translit($item->get('UF_NAME'), 'ru')
//        ]
//    );

//$highloadBlockClass::delete(7);

//$highloadBlockClass::deleteMulti(
//    [1, 2]
//)

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");