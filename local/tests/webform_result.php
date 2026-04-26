<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Тест на заполнение в форме опр. данных');

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

if (!Loader::includeModule('crm'))
{
	var_dump([]);
	return;
}

function dumpScreen($value): void
{
	echo '<pre>';
	var_dump($value);
	echo '</pre>';
}

//$formId = 4;
$formId = 590;

$doUpdate = -1;

//$contactField = 'UF_CRM_CONTACT_1777197545085';
$contactField = 'UF_CRM_1706859291';
$sourceField = 'CONTACT_' . $contactField;

//$targetField = 'UF_CRM_1777201306';
$targetField = 'UF_CRM_1706851895';

$connection = Application::getConnection();
$sqlHelper = $connection->getSqlHelper();
$qualifiedResultIds = [];
$contactDates = [];
$contactsToUpdate = [];
$updateResult = [];

$fieldSql = $sqlHelper->forSql($sourceField);
$activityDb = $connection->query("
	SELECT ASSOCIATED_ENTITY_ID, PROVIDER_PARAMS
	FROM b_crm_act
	WHERE PROVIDER_ID = 'CRM_WEBFORM'
		AND PROVIDER_TYPE_ID = '{$formId}'
		AND PROVIDER_PARAMS LIKE '%{$fieldSql}%'
");

$activityRows = $activityDb->fetchAll();
if ($doUpdate === -1)
{
	dumpScreen([
		'stage' => 'activity_rows',
		'count' => count($activityRows),
	]);
}

foreach ($activityRows as $row)
{
	$params = @unserialize($row['PROVIDER_PARAMS'], ['allowed_classes' => false]);

	foreach (($params['FIELDS'] ?? []) as $field)
	{
		if (($field['code'] ?? '') === $sourceField && (int)($field['value'][0] ?? 0) > 0)
		{
			$qualifiedResultIds[(int)$row['ASSOCIATED_ENTITY_ID']] = true;
			break;
		}
	}
}

if ($doUpdate === -1)
{
	dumpScreen([
		'stage' => 'qualified_result_ids',
		'count' => count($qualifiedResultIds),
	]);
}

$resultDb = $connection->query("
	SELECT r.ID AS RESULT_ID, e.ITEM_ID, r.DATE_INSERT
	FROM b_crm_webform_result r
	INNER JOIN b_crm_webform_result_entity e ON e.RESULT_ID = r.ID
	WHERE r.FORM_ID = {$formId}
		AND e.FORM_ID = {$formId}
		AND e.ENTITY_NAME = 'CONTACT'
	ORDER BY r.DATE_INSERT DESC, r.ID DESC
");

while ($row = $resultDb->fetch())
{
	$resultId = (int)$row['RESULT_ID'];
	$contactId = (int)$row['ITEM_ID'];

	if (!isset($qualifiedResultIds[$resultId]) || isset($contactDates[$contactId]))
	{
		continue;
	}

	$contactDates[$contactId] = $row['DATE_INSERT'];
}

if ($doUpdate === -1)
{
	dumpScreen([
		'stage' => 'contact_dates',
		'count' => count($contactDates),
	]);
}

foreach (array_chunk(array_keys($contactDates), 500) as $contactIds)
{
	$idsSql = implode(',', $contactIds);
	$filledContactIds = [];

	$contactDb = $connection->query("
		SELECT VALUE_ID, {$targetField}
		FROM b_uts_crm_contact
		WHERE VALUE_ID IN ({$idsSql})
			AND {$targetField} IS NOT NULL
	");

	while ($row = $contactDb->fetch())
	{
		if ((string)$row[$targetField] !== '')
		{
			$filledContactIds[(int)$row['VALUE_ID']] = true;
		}
	}

	if ($doUpdate === -1)
	{
		dumpScreen([
			'stage' => 'filled_contact_ids',
			'count' => count($filledContactIds),
		]);
	}

	foreach ($contactIds as $contactId)
	{
		if (isset($filledContactIds[$contactId]))
		{
			continue;
		}

		$contactsToUpdate[$contactId] = $contactDates[$contactId];
	}
}

$contactsForRun = $doUpdate > 0 ? array_slice($contactsToUpdate, 0, $doUpdate, true) : $contactsToUpdate;
$updatedContactIds = [];

if ($doUpdate >= 0)
{
	$contact = new CCrmContact(false);

	foreach ($contactsForRun as $contactId => $date)
	{
		$fields = [$targetField => $date];
		$updateResult[$contactId] = [
			'date' => $date,
			'updated' => $contact->Update($contactId, $fields),
			'error' => $contact->LAST_ERROR,
		];

		if (!empty($updateResult[$contactId]['updated']))
		{
			$updatedContactIds[] = $contactId;
		}
	}
}

dumpScreen([
	'doUpdate' => $doUpdate,
	'found' => count($contactDates),
	'readyToUpdate' => count($contactsToUpdate),
	'readyToUpdateIds' => $doUpdate === -1 ? array_keys($contactsToUpdate) : [],
	'runCount' => $doUpdate > 0 ? count($contactsForRun) : ($doUpdate === 0 ? count($contactsToUpdate) : 0),
	'updated' => $doUpdate >= 0 ? count($updatedContactIds) : 0,
]);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");