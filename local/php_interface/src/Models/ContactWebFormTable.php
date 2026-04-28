<?php

namespace Otus\Models;

use Bitrix\Crm\ContactTable;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;

class ContactWebFormTable extends ContactTable
{
    public static function getMap()
    {
        return array_merge(parent::getMap(), [
            new OneToMany(
                'LINK_RESULTS',
                LinkResultFormTable::class,
                'CONTACT'
            ),
            (new ManyToMany(
                'FORMS',
                CrmWebFormTable::class
            )) ->configureMediatorEntity(LinkResultFormTable::class)
                ->configureLocalReference('CONTACT')
                ->configureRemoteReference('FORM'),
        ]);
    }
}
