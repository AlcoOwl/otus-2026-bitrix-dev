<?php

use Otus\Models\HospitalClientsTable;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$collection = HospitalClientsTable::getList([
        'select' => [
            '*',
            'CONTACT'
        ]
    ]
)->fetchCollection();

dump($collection);

foreach ($collection as $item) {
    $info = array(
        'id' => $item->getId(),
        'firstName' => $item->getFirstName(),
        'lastName' => $item->getLastName(),
        'contactId' => $item->get('CONTACT_ID'),
        'contact_name' => $item->get('CONTACT')->get('NAME'),
    );
    dump($info);
}

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");