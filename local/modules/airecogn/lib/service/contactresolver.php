<?php

namespace Airecogn\Service;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Main\Loader;

final class ContactResolver
{
    private static array $cache = [];

    public static function resolveByActivityId(int $activityId, ?array $activity = null): int
    {
        if ($activityId <= 0)
        {
            throw new \InvalidArgumentException('Activity ID must be a positive integer');
        }

        if (array_key_exists($activityId, self::$cache))
        {
            return self::$cache[$activityId];
        }

        if (!Loader::includeModule('crm'))
        {
            throw new \RuntimeException('CRM module load failed');
        }

        if ($activity === null && class_exists('CCrmActivity'))
        {
            $loadedActivity = \CCrmActivity::GetByID($activityId, false);
            $activity = is_array($loadedActivity) ? $loadedActivity : null;
        }

        if (is_array($activity)
            && (int)($activity['OWNER_TYPE_ID'] ?? 0) === \CCrmOwnerType::Contact)
        {
            $ownerId = max(0, (int)($activity['OWNER_ID'] ?? 0));
            if ($ownerId > 0)
            {
                return self::$cache[$activityId] = $ownerId;
            }
        }

        $binding = ActivityBindingTable::getList([
            'select' => ['OWNER_ID'],
            'filter' => [
                '=ACTIVITY_ID' => $activityId,
                '=OWNER_TYPE_ID' => \CCrmOwnerType::Contact,
            ],
            'limit' => 1,
        ])->fetch();

        $contactId = (int)($binding['OWNER_ID'] ?? 0);
        if ($contactId <= 0)
        {
            throw new \RuntimeException('Contact binding not found for activity ' . $activityId);
        }

        return self::$cache[$activityId] = $contactId;
    }
}
