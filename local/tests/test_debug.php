<?php
use Otus\Debug;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
global $APPLICATION;
$APPLICATION->SetTitle('TEST DEBUG');

//print_r('<pre>');
//print_r('$_SERVER: ');
//print_r($_SERVER);
//print_r('</pre>');
//
//print_r('<pre>');
//print_r('var_dump($_SERVER): ');
//print_r(dump($_SERVER));
//print_r('</pre>');

//Bitrix\Main\Diag\Debug::writeToFile($_SERVER, 'SERVER', 'local/logs/server.log');

Bitrix\Main\Diag\Debug::startTimeLabel('TEST_DEBUG');
Bitrix\Main\Diag\Debug::writeToFile($_SERVER, 'TEST DEBUG', 'local/logs/test_debug.log');
sleep(5);
Bitrix\Main\Diag\Debug::endTimeLabel('TEST_DEBUG');
$TimeLabels = Bitrix\Main\Diag\Debug::getTimeLabels();
Debug::writeToLog($TimeLabels);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");