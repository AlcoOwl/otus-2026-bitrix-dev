<?php

namespace Airecogn\Integration;

use Airecogn\Config;
use Airecogn\Service\EndCallProcessor;
use Airecogn\Service\Logger;
use Bitrix\Main\Application;

final class VoximplantEventHandler
{
    public static function onCallEnd(array $callData): void
    {
        if (!Config::isEnabled())
        {
            return;
        }

        $activityId = self::normalizeActivityId($callData['CRM_ACTIVITY_ID'] ?? null);
        if ($activityId === null)
        {
            throw new \UnexpectedValueException('CRM_ACTIVITY_ID must be a positive integer or digit string');
        }
        $callData['CRM_ACTIVITY_ID'] = $activityId;

        Logger::write(Logger::CHANNEL_END_CALL, [
            'type' => 'queued',
            'message' => 'Звонок поставлен в очередь обработки',
            'activity_id' => $activityId,
            'call_id' => $callData['CALL_ID'] ?? null,
        ]);

        Application::getInstance()->addBackgroundJob(
            [EndCallProcessor::class, 'process'],
            [$callData],
            Application::JOB_PRIORITY_LOW
        );
    }

    private static function normalizeActivityId($value): ?int
    {
        if (is_int($value) && $value > 0)
        {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value))
        {
            $digits = ltrim($value, '0');
            if ($digits === '')
            {
                return null;
            }

            $integer = filter_var($digits, FILTER_VALIDATE_INT);
            return $integer === false ? null : $integer;
        }

        return null;
    }
}
