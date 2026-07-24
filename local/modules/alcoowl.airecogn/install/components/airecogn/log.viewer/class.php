<?php

use Airecogn\Service\Logger;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

class AirecognLogViewerComponent extends CBitrixComponent
{
    public function executeComponent(): void
    {
        global $USER;
        if (!is_object($USER) || !$USER->IsAdmin())
        {
            ShowError('Просмотр логов разрешён только администраторам.');
            return;
        }

        if (!Loader::includeModule('alcoowl.airecogn'))
        {
            ShowError('Не удалось подключить модуль alcoowl.airecogn.');
            return;
        }

        $contactId = (int)($this->arParams['CONTACT_ID'] ?? 0);
        $records = [];

        foreach ([Logger::CHANNEL_END_CALL, Logger::CHANNEL_INBOUND] as $channel)
        {
            foreach (Logger::tail($channel) as $record)
            {
                if ($contactId > 0 && (int)($record['contact_id'] ?? 0) !== $contactId)
                {
                    continue;
                }

                $records[] = $record;
            }
        }

        usort($records, static fn(array $a, array $b): int => strcmp((string)($b['timestamp'] ?? ''), (string)($a['timestamp'] ?? '')));
        $this->arResult['RECORDS'] = $records;
        $this->includeComponentTemplate();
    }
}
