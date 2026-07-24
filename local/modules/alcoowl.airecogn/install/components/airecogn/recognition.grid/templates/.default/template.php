<?php

/** @var array $arResult */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

global $APPLICATION;
?>
<?php $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
        'GRID_ID' => $arResult['GRID_ID'],
        'COLUMNS' => $arResult['COLUMNS'],
        'ROWS' => $arResult['ROWS'],
        'NAV_OBJECT' => $arResult['NAVIGATION'],
        'TOTAL_ROWS_COUNT' => $arResult['TOTAL'],
        'SHOW_ROW_CHECKBOXES' => false,
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_PAGINATION' => true,
        'SHOW_PAGESIZE' => true,
        'SHOW_TOTAL_COUNTER' => true,
        'ALLOW_COLUMNS_SORT' => false,
        'ALLOW_ROWS_SORT' => false,
        'ALLOW_PIN_HEADER' => true,
        'AJAX_MODE' => $arResult['AJAX_MODE'],
        'AJAX_ID' => $arResult['AJAX_ID'],
        'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
        'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
        'AJAX_LOADER' => $arResult['AJAX_LOADER'],
    ]); ?>
