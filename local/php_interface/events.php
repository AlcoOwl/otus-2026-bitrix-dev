<?php

use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('', 'TestHLBlockOnBeforeAdd', [
    'Otus\Event',
    'onBeforeElementAdd'
    ]
);