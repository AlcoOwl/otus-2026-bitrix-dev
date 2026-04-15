<?php

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\PhoneNumber\Parser;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle('Парсер телефона');

$parser = Parser::getInstance();

$raw = '8    800    555-35-35';
$parsed = $parser->parse($raw);
dump($parsed);
dump($parsed->getCountryCode());
dump($parsed->getNationalNumber());

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php";
