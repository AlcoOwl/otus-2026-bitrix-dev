<?php

namespace Airecogn\Service;

use Bitrix\Main\Type\DateTime;
use Throwable;
use UnexpectedValueException;

final class EndCallProcessor
{
    public static function process(array $data): array
    {
        require_once dirname(__DIR__) . '/legacy/end_call_functions.php';

        $activityId = self::normalizeActivityId($data['CRM_ACTIVITY_ID'] ?? null);
        if ($activityId === null)
        {
            throw new UnexpectedValueException('CRM_ACTIVITY_ID must be a positive integer or digit string');
        }

        try
        {
            $activity = getCrmActivity($activityId);
        }
        catch (Throwable $throwable)
        {
            self::writeFatal($activityId, 'activity', $throwable);
            throw $throwable;
        }

        $config = null;
        $configError = null;
        $failure = null;
        try
        {
            $config = loadEndCallConfig();
        }
        catch (Throwable $throwable)
        {
            $configError = 'Ошибка загрузки конфигурации: ' . $throwable->getMessage();
            $failure = self::makeFailure('config', $throwable);
        }

        $storageElementId = getCrmActivityStorageElementId($activity);
        $hasSavedRecording = $storageElementId !== null;
        $callStartDate = null;
        $recordSeconds = null;
        $callDataError = null;
        $callLogInfo = self::emptyCallLogInfo(null);

        try
        {
            $statistic = getVoximplantStatistic($activityId);
            $callStartDate = $statistic['CALL_START_DATE'] ?? null;
            $recordSeconds = getRecordDurationSeconds($statistic);
            $callLogInfo = self::emptyCallLogInfo($recordSeconds, (string)($statistic['CALL_LOG'] ?? ''));

            if ($hasSavedRecording && $recordSeconds === null)
            {
                $callDataError = 'Не удалось определить длительность записи разговора';
                $failure = array('stage' => 'duration', 'type' => null, 'message' => $callDataError);
            }
            elseif ($hasSavedRecording && $recordSeconds >= 11)
            {
                $callLogInfo = buildCallLogInfo($activityId, $activity, $statistic);
            }
        }
        catch (Throwable $throwable)
        {
            $callDataError = 'Ошибка получения данных звонка: ' . $throwable->getMessage();
            $failure = self::makeFailure('call_data', $throwable);
        }

        $status = RecognitionResultRepository::STATUS_ERROR;
        $summary = '';
        $description = '';
        $fileInfo = null;
        $uploadResult = null;
        $nextcloudError = null;

        if (!$hasSavedRecording)
        {
            $status = RecognitionResultRepository::STATUS_SKIPPED_NO_RECORDING;
            $summary = NO_RECORDING_COMMENT;
            $description = buildNoRecordingActivityDescription($activityId, $callLogInfo);
        }
        elseif ($recordSeconds !== null && $recordSeconds < 11)
        {
            $status = RecognitionResultRepository::STATUS_SKIPPED_SHORT;
            $summary = SHORT_RECORDING_COMMENT;
            $description = buildShortRecordingActivityDescription($activityId, $callLogInfo);
        }
        elseif ($callDataError !== null || $configError !== null)
        {
            $summary = $callDataError ?? $configError;
            $description = buildErrorActivityDescription($activityId, $callLogInfo, $summary);
        }
        else
        {
            try
            {
                $fileInfo = getBitrixDiskFileInfo($storageElementId);
                $uploadResult = uploadBitrixFileToNextcloud(
                    $fileInfo,
                    $config,
                    $activityId,
                    $callStartDate instanceof DateTime ? $callStartDate : null
                );
                $status = RecognitionResultRepository::STATUS_PENDING;
            }
            catch (Throwable $throwable)
            {
                $nextcloudError = $throwable->getMessage();
                $summary = 'Ошибка подготовки или передачи записи в Nextcloud: ' . $nextcloudError;
                $description = buildErrorActivityDescription($activityId, $callLogInfo, $summary);
                $failure = self::makeFailure('nextcloud', $throwable);
            }
        }

        [$localSaved, $localError] = self::saveLocalOutcome($activityId, $status, $summary);
        [$oracleSaved, $oracleError] = self::saveOracleOutcome(
            $config,
            $activityId,
            $status,
            $summary,
            $activity,
            $callLogInfo
        );
        $oracleError = $oracleError ?? ($config === null ? $configError : null);

        $activityDescriptionUpdated = null;
        $activityDescriptionError = null;
        try
        {
            $activityDescriptionUpdated = $status === RecognitionResultRepository::STATUS_PENDING
                ? rewriteCrmActivityDescription($activityId, $callLogInfo, (string)$uploadResult['remotePath'])
                : updateCrmActivityDescription($activityId, $description);
        }
        catch (Throwable $throwable)
        {
            $activityDescriptionError = $throwable->getMessage();
        }

        Logger::write(Logger::CHANNEL_END_CALL, array(
            'type' => 'result',
            'message' => 'Итог обработки: ' . $status,
            'activity_id' => $activityId,
            'status' => $status,
            'summary' => $summary,
            'call' => array(
                'started_at' => $callStartDate instanceof DateTime ? $callStartDate->format(DATE_ATOM) : null,
                'record_seconds' => $recordSeconds,
                'log_url' => $callLogInfo['callLogUrl'],
                'transfer_route' => $callLogInfo['transferRoute'],
                'metrics' => $callLogInfo['callMetrics'],
            ),
            'recording' => array(
                'exists' => $hasSavedRecording,
                'storage_element_id' => $storageElementId,
                'file_id' => is_array($fileInfo) ? $fileInfo['fileId'] : null,
                'file_name' => is_array($fileInfo) ? $fileInfo['name'] : null,
            ),
            'nextcloud' => array(
                'remote_path' => is_array($uploadResult) ? $uploadResult['remotePath'] : null,
                'http_code' => is_array($uploadResult) ? $uploadResult['httpCode'] : null,
            ),
            'persistence' => array(
                'local_saved' => $localSaved,
                'local_error' => $localError,
                'oracle_saved' => $oracleSaved,
                'oracle_error' => $oracleError,
                'activity_description_updated' => $activityDescriptionUpdated,
                'activity_description_error' => $activityDescriptionError,
            ),
            'error' => $status === RecognitionResultRepository::STATUS_ERROR ? $failure : null,
        ), $status === RecognitionResultRepository::STATUS_ERROR
            || $localError !== null
            || $oracleError !== null
            || $activityDescriptionError !== null
                ? 'error'
                : 'info');

        return array(
            'status' => $status === RecognitionResultRepository::STATUS_ERROR ? 'error' : 'ok',
            'activityId' => $activityId,
            'resultStatus' => $status,
            'recordingUploaded' => is_array($uploadResult),
            'nextcloudError' => $nextcloudError,
            'activityDescriptionError' => $activityDescriptionError,
        );
    }

    private static function normalizeActivityId($value): ?int
    {
        if (is_int($value) && $value > 0)
        {
            return $value;
        }
        if (!is_string($value) || !preg_match('/^\d+$/', $value))
        {
            return null;
        }

        $digits = ltrim($value, '0');
        $activityId = $digits === '' ? false : filter_var($digits, FILTER_VALIDATE_INT);

        return $activityId === false ? null : $activityId;
    }

    private static function emptyCallLogInfo(?int $recordSeconds, string $callLogUrl = ''): array
    {
        return array(
            'callLogUrl' => trim($callLogUrl),
            'transferRoute' => array(),
            'oracleRouteItems' => array(),
            'transferResult' => '',
            'callMetrics' => array('recordSeconds' => $recordSeconds),
            'callUsers' => array('first' => '', 'last' => ''),
        );
    }

    private static function saveLocalOutcome(int $activityId, string $status, string $summary): array
    {
        try
        {
            if ($status === RecognitionResultRepository::STATUS_PENDING)
            {
                RecognitionResultRepository::savePending($activityId);
            }
            else
            {
                RecognitionResultRepository::save($activityId, $status, $summary);
            }

            return array(true, null);
        }
        catch (Throwable $throwable)
        {
            return array(false, $throwable->getMessage());
        }
    }

    private static function saveOracleOutcome(
        ?array $config,
        int $activityId,
        string $status,
        string $summary,
        array $activity,
        array $callLogInfo
    ): array
    {
        if ($config === null)
        {
            return array(null, null);
        }

        try
        {
            return array(
                saveOracleActivity(
                    $config,
                    array(
                        'call_id' => $activityId,
                        'call_status' => $status,
                        'summary' => $summary,
                        'questions' => array(),
                    ),
                    $activity,
                    (string)$callLogInfo['transferResult'],
                    $callLogInfo['callMetrics'],
                    $callLogInfo['callUsers'],
                    (string)$callLogInfo['callLogUrl'],
                    $callLogInfo['oracleRouteItems']
                ),
                null,
            );
        }
        catch (Throwable $throwable)
        {
            return array(null, $throwable->getMessage());
        }
    }

    private static function makeFailure(string $stage, Throwable $throwable): array
    {
        return array(
            'stage' => $stage,
            'type' => get_class($throwable),
            'message' => $throwable->getMessage(),
        );
    }

    private static function writeFatal(int $activityId, string $stage, Throwable $throwable): void
    {
        Logger::write(Logger::CHANNEL_END_CALL, array(
            'type' => 'fatal',
            'message' => $throwable->getMessage(),
            'activity_id' => $activityId,
            'error' => self::makeFailure($stage, $throwable),
        ), 'error');
    }
}
