<?php

namespace Airecogn\Integration;

use Airecogn\Config;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Web\Uri;

final class CrmTabHandler
{
    public static function onTabsInitialized(Event $event): EventResult
    {
        $tabs = $event->getParameter('tabs');
        if (!is_array($tabs) || !Config::isEnabled())
        {
            return new EventResult(EventResult::SUCCESS, ['tabs' => is_array($tabs) ? $tabs : []]);
        }

        $entityTypeId = (int)$event->getParameter('entityTypeID');
        $contactTypeId = class_exists('CCrmOwnerType') ? (int)\CCrmOwnerType::Contact : 3;
        if ($entityTypeId !== $contactTypeId)
        {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        $contactId = (int)$event->getParameter('entityID');
        $tabs[] = self::buildTab(
            'airecogn_results',
            'Распознавание звонков',
            'results',
            $contactId
        );

        global $USER;
        if (is_object($USER) && $USER->IsAdmin())
        {
            $tabs[] = self::buildTab(
                'airecogn_logs',
                'Логи распознавания',
                'logs',
                $contactId
            );
        }

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }

    private static function buildTab(string $id, string $name, string $mode, int $contactId): array
    {
        $signatureSalt = $mode === 'logs'
            ? 'airecogn.log.viewer'
            : 'airecogn.recognition.grid';
        $uri = new Uri('/local/handler/airecogn/tab.php');
        $uri->addParams([
            'mode' => $mode,
            'site' => defined('SITE_ID') ? SITE_ID : '',
            'sessid' => bitrix_sessid(),
        ]);

        return [
            'id' => $id,
            'name' => $name,
            'enabled' => true,
            'loader' => [
                'serviceUrl' => $uri->getUri(),
                'componentData' => [
                    'template' => '',
                    'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
                        'CONTACT_ID' => $contactId,
                        'MODE' => $mode,
                    ], $signatureSalt),
                ],
            ],
        ];
    }
}
