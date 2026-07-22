<?php

namespace Airecogn\Service;

use Airecogn\Model\RecognitionResultTable;
use Bitrix\Main\Type\DateTime;
use InvalidArgumentException;
use RuntimeException;

final class RecognitionResultRepository
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_SKIPPED_NO_RECORDING = 'skipped_no_recording';
    public const STATUS_SKIPPED_SHORT = 'skipped_short';

    public static function savePending(int $activityId): void
    {
        if ($activityId <= 0)
        {
            throw new InvalidArgumentException('Activity ID must be a positive integer');
        }

        $existing = RecognitionResultTable::getRow([
            'select' => ['STATUS'],
            'filter' => ['=ACTIVITY_ID' => $activityId],
        ]);
        if ($existing && in_array((string)$existing['STATUS'], [self::STATUS_PENDING, self::STATUS_SUCCESS], true))
        {
            return;
        }

        self::save($activityId, self::STATUS_PENDING, '');
    }

    public static function saveInbound(array $payload): void
    {
        $activityId = $payload['call_id'] ?? null;
        if (!is_int($activityId) || $activityId <= 0)
        {
            throw new InvalidArgumentException('call_id must be a positive integer');
        }

        $externalStatus = $payload['call_status'] ?? null;
        if (!is_string($externalStatus) || !in_array($externalStatus, [self::STATUS_SUCCESS, self::STATUS_ERROR], true))
        {
            throw new InvalidArgumentException('call_status must be success or error');
        }

        $summary = $payload['summary'] ?? null;
        if (!is_string($summary))
        {
            throw new InvalidArgumentException('summary must be a string');
        }

        $status = $externalStatus === self::STATUS_SUCCESS ? self::STATUS_SUCCESS : self::STATUS_ERROR;

        self::save($activityId, $status, trim($summary));
    }

    public static function save(int $activityId, string $status, string $summary): void
    {
        if ($activityId <= 0)
        {
            throw new InvalidArgumentException('Activity ID must be a positive integer');
        }
        if (!in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_SUCCESS,
            self::STATUS_ERROR,
            self::STATUS_SKIPPED_NO_RECORDING,
            self::STATUS_SKIPPED_SHORT,
        ], true))
        {
            throw new InvalidArgumentException('Unknown recognition status: ' . $status);
        }

        $now = new DateTime();
        $existing = RecognitionResultTable::getRow([
            'select' => ['ID'],
            'filter' => ['=ACTIVITY_ID' => $activityId],
        ]);

        $fields = [
            'STATUS' => mb_substr($status, 0, 20),
            'SUMMARY' => $summary,
            'UPDATED_AT' => $now,
            'PROCESSED_AT' => $status === self::STATUS_PENDING ? null : $now,
        ];

        $result = $existing
            ? RecognitionResultTable::update((int)$existing['ID'], $fields)
            : RecognitionResultTable::add($fields + [
                'ACTIVITY_ID' => $activityId,
                'CREATED_AT' => $now,
            ]);

        if (!$result->isSuccess())
        {
            throw new RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }
}
