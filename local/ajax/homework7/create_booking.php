<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Otus\Models\ProceduresPropertiesTable;

define('NO_KEEP_STATISTIC', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(__FILE__);

header('Content-Type: application/json; charset=utf-8');

const BOOKING_IBLOCK_ID = 22;

if (!check_bitrix_sessid()) {
    echo json_encode([
        'success' => false,
        'message' => Loc::getMessage('OTUS_BOOKING_SESSION_ERROR'),
    ]);
    exit;
}

$patientFio = trim((string)$_POST['patient_fio']);
$appointmentAt = (string)$_POST['appointment_at'];
$procedureId = (int)$_POST['procedure_id'];
$doctorId = (int)$_POST['doctor_id'];

if ($patientFio === '' || $appointmentAt === '' || $procedureId <= 0 || $doctorId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => Loc::getMessage('OTUS_BOOKING_FIELDS_REQUIRED'),
    ]);
    exit;
}

$appointmentDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $appointmentAt);

if ($appointmentDate === false || $appointmentDate->format('i') !== '00') {
    echo json_encode([
        'success' => false,
        'message' => Loc::getMessage('OTUS_BOOKING_FULL_HOUR_REQUIRED'),
    ]);
    exit;
}

Loader::includeModule('iblock');

$procedure = ProceduresPropertiesTable::getByPrimary($procedureId, [
    'select' => ['DURATION'],
])->fetch();
$duration = (int)$procedure['DURATION'];

if ($duration <= 0) {
    echo json_encode([
        'success' => false,
        'message' => Loc::getMessage('OTUS_BOOKING_DURATION_REQUIRED'),
    ]);
    exit;
}

$bookings = CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => BOOKING_IBLOCK_ID,
        'ACTIVE' => 'Y',
        'PROPERTY_DOCTOR_ID' => $doctorId,
    ],
    false,
    false,
    [
        'ID',
        'PROPERTY_APPOINTMENT_AT',
        'PROPERTY_PROCEDURE_ID',
    ]
);

$appointmentEnd = $appointmentDate->modify('+' . $duration . ' hours');

while ($booking = $bookings->Fetch()) {
    $existingStart = DateTimeImmutable::createFromFormat(
        'd.m.Y H:i:s',
        (string)$booking['PROPERTY_APPOINTMENT_AT_VALUE']
    );
    $existingProcedure = ProceduresPropertiesTable::getByPrimary(
        (int)$booking['PROPERTY_PROCEDURE_ID_VALUE'],
        ['select' => ['DURATION']]
    )->fetch();
    $existingDuration = (int)$existingProcedure['DURATION'];
    $existingEnd = $existingStart->modify('+' . $existingDuration . ' hours');

    if ($appointmentDate < $existingEnd && $appointmentEnd > $existingStart) {
        echo json_encode([
            'success' => false,
            'message' => Loc::getMessage('OTUS_BOOKING_TIME_BUSY'),
        ]);
        exit;
    }
}

$element = new CIBlockElement();
$bookingId = $element->Add([
    'IBLOCK_ID' => BOOKING_IBLOCK_ID,
    'NAME' => $patientFio,
    'PROPERTY_VALUES' => [
        'PATIENT_FIO' => $patientFio,
        'APPOINTMENT_AT' => $appointmentDate->format('d.m.Y H:i:s'),
        'PROCEDURE_ID' => $procedureId,
        'DOCTOR_ID' => $doctorId,
    ],
]);

echo json_encode([
    'success' => $bookingId !== false,
    'message' => $bookingId !== false
        ? Loc::getMessage('OTUS_BOOKING_CREATED')
        : Loc::getMessage('OTUS_BOOKING_CREATE_ERROR', ['#ERROR#' => $element->LAST_ERROR]),
]);
