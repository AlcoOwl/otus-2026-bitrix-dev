<?php

use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\WebForm\Internals\ResultEntityTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event as OrmEvent;
use Bitrix\Main\ORM\EventManager as OrmEventManager;
use Otus\Models\CrmWebFormResultTable;
use Otus\Models\LinkResultFormTable;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('', 'TestHLBlockOnBeforeAdd', [
    'Otus\Event',
    'onBeforeElementAdd'
]);

if (Loader::includeModule('crm')) {
    OrmEventManager::getInstance()->addEventHandler(
        ResultEntityTable::class,
        DataManager::EVENT_ON_AFTER_ADD,
        ['OtusWebFormResultSync', 'onAfterResultEntityAdd']
    );
}

class OtusWebFormResultSync
{
    private const ENTITY_NAME = 'CONTACT';
    private const PROVIDER_ID = 'CRM_WEBFORM';
    private const RETRY_DELAY = 5;
    private const MAX_ATTEMPTS = 5;

    public static function onAfterResultEntityAdd(OrmEvent $event): void
    {
        $fields = $event->getParameter('fields');

        if (($fields['ENTITY_NAME'] ?? '') !== self::ENTITY_NAME) {
            return;
        }

        self::queue((int)$fields['RESULT_ID'], (int)$fields['ITEM_ID'], 1);
    }

    public static function syncAgent(int $resultId, int $contactId, int $attempt): string
    {
        $resultEntity = ResultEntityTable::query()
            ->setSelect([
                'FORM_ID',
            ])
            ->setFilter([
                '=ENTITY_NAME' => self::ENTITY_NAME,
                '=RESULT_ID' => $resultId,
                '=ITEM_ID' => $contactId,
            ])
            ->setLimit(1)
            ->fetch();

        if (!$resultEntity) {
            return '';
        }

        $result = CrmWebFormResultTable::query()
            ->setSelect(['DATE_INSERT'])
            ->setFilter(['=ID' => $resultId])
            ->setLimit(1)
            ->fetch();

        $activity = ActivityTable::query()
            ->setSelect(['ID'])
            ->setFilter([
                '=PROVIDER_ID' => self::PROVIDER_ID,
                '=ASSOCIATED_ENTITY_ID' => $resultId,
            ])
            ->setOrder(['ID' => 'DESC'])
            ->setLimit(1)
            ->fetch();

        if ($activity) {
            self::save(
                $contactId,
                $resultId,
                (int)$activity['ID'],
                (int)$resultEntity['FORM_ID'],
                $result['DATE_INSERT'] ?? null
            );

            return '';
        }

        if ($attempt < self::MAX_ATTEMPTS) {
            self::queue($resultId, $contactId, $attempt + 1);

            return '';
        }

        self::save(
            $contactId,
            $resultId,
            -1,
            (int)$resultEntity['FORM_ID'],
            $result['DATE_INSERT'] ?? null
        );

        return '';
    }

    private static function queue(int $resultId, int $contactId, int $attempt): void
    {
        CAgent::AddAgent(
            sprintf(
                'OtusWebFormResultSync::syncAgent(%d, %d, %d);',
                $resultId,
                $contactId,
                $attempt
            ),
            '',
            'N',
            self::RETRY_DELAY,
            '',
            'Y',
            ConvertTimeStamp(time() + self::RETRY_DELAY, 'FULL')
        );
    }

    private static function save(
        int $contactId,
        int $resultId,
        int $activityId,
        int $formId,
        $createdAt
    ): void {
        $fields = [
            'CONTACT_ID' => $contactId,
            'RESULT_ID' => $resultId,
            'ACTIVITY_ID' => $activityId,
            'FORM_ID' => $formId,
            'CREATED_AT' => $createdAt,
        ];

        $existing = LinkResultFormTable::getRow([
            'select' => ['ID'],
            'filter' => [
                '=RESULT_ID' => $resultId,
                '=CONTACT_ID' => $contactId,
            ],
        ]);

        if ($existing) {
            LinkResultFormTable::update((int)$existing['ID'], $fields);

            return;
        }

        LinkResultFormTable::add($fields);
    }
}
