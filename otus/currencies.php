<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetTitle("Курс валюты");
?><h1><?php $APPLICATION->ShowTitle(); ?></h1>
<?$APPLICATION->IncludeComponent(
	"otus:currency_check",
	"",
	Array(
		"CURRENCY" => "USD"
	)
);?><?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>