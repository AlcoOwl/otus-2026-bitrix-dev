<?php

namespace Otus;

use Bitrix\Main\ORM\Event as OrmEvent;
use Bitrix\Main\ORM\EventResult;

class Event
{
    public static function onBeforeElementAdd(OrmEvent $event): EventResult
    {
        $params = $event->getParameters();
        $fields = $params['fields'];
        $fields['UF_NAME'] = 'NAME: ' . $fields['UF_NAME'];

        $result = new EventResult();
        $result->modifyFields($fields);
        $event->getEntity()->cleanCache();
        return $result;
    }
}