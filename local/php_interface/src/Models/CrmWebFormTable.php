<?php

namespace Otus\Models;

use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;

class CrmWebFormTable extends FormTable
{
    public static function getMap()
    {
        return array_merge(parent::getMap(), [
            new OneToMany(
                'LINK_RESULTS',
                LinkResultFormTable::class,
                'FORM'
            ),
            (new ManyToMany(
                'CONTACTS',
                ContactWebFormTable::class
            )) ->configureMediatorEntity(LinkResultFormTable::class)
                ->configureLocalReference('FORM')
                ->configureRemoteReference('CONTACT'),
        ]);
    }
}
