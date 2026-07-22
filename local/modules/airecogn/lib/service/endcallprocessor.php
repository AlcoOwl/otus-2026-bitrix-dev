<?php

namespace Airecogn\Service;

use Throwable;

final class EndCallProcessor
{
    public static function process(array $data): array
    {
        require_once dirname(__DIR__) . '/legacy/end_call_functions.php';

        $activityId = $data['CRM_ACTIVITY_ID'] ?? null;
        if (is_string($activityId) && preg_match('/^\d+$/', $activityId))
        {
            $activityId = ltrim($activityId, '0');
            $activityId = $activityId === '' ? false : filter_var($activityId, FILTER_VALIDATE_INT);
        }
        if (!is_int($activityId) || $activityId <= 0)
        {
            throw new \UnexpectedValueException('CRM_ACTIVITY_ID must be a positive integer or digit string');
        }
        $data['CRM_ACTIVITY_ID'] = $activityId;

        $basePayload = array(
            'receivedAt' => date('Y-m-d H:i:s'),
            'CALL_START_DATE' => $data['CALL_START_DATE'] ?? null,
            'activity_id' => $activityId,
        );
        
        writeEndCallLog($basePayload);
        
        $activity = null;
        $config = null;
        $callLogInfo = array();
        $oracleSaved = null;
        $oracleError = null;
        $storageElementId = null;
        $fileInfo = null;
        $uploadResult = null;
        $nextcloudError = null;
        $activityDescriptionUpdated = null;
        $activityDescriptionError = null;
        $recordSeconds = null;
        $isShortRecording = false;
        $hasSavedRecording = false;
        
        try
        {
            $activity = getCrmActivity($activityId);
        }
        catch (Throwable $throwable)
        {
            writeEndCallError('CRM activity read exception', array(
                'activity_id' => $activityId,
                'exception' => get_class($throwable),
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ));
            throw $throwable;
        }
        
        try
        {
            $config = loadEndCallConfig();
        }
        catch (Throwable $throwable)
        {
            writeEndCallError('End call config exception', array(
                'activity_id' => $activityId,
                'exception' => get_class($throwable),
                'message' => $throwable->getMessage(),
            ));
            throw $throwable;
        }
        
        if (is_array($activity))
        {
            $storageElementId = getCrmActivityStorageElementId($activity);
            $hasSavedRecording = $storageElementId !== null;
        
            try
            {
                $statistic = getVoximplantStatistic($activityId);
                $recordSeconds = getRecordDurationSeconds($statistic);
                $isShortRecording = $recordSeconds !== null && $recordSeconds < 11;
        
                if (!$hasSavedRecording || $isShortRecording)
                {
                    $callLogInfo = array(
                        'callLogUrl' => trim((string)($statistic['CALL_LOG'] ?? '')),
                        'transferRoute' => array(),
                        'oracleRouteItems' => array(),
                        'transferResult' => '',
                        'callMetrics' => array('recordSeconds' => $recordSeconds),
                        'callUsers' => array('first' => '', 'last' => ''),
                    );
                }
                else
                {
                    $callLogInfo = buildCallLogInfo($activityId, $activity, $statistic);
                }
            }
            catch (Throwable $throwable)
            {
                writeEndCallError('Call log processing exception', array(
                    'activity_id' => $activityId,
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                ));
            }
        
            if (is_array($config))
            {
                try
                {
                    $oracleSaved = saveOracleActivity(
                        $config,
                        array(
                            'call_id' => $activityId,
                            'call_status' => !$hasSavedRecording
                                ? 'skipped_no_recording'
                                : ($isShortRecording ? 'skipped_short' : 'pending'),
                            'summary' => !$hasSavedRecording
                                ? NO_RECORDING_COMMENT
                                : ($isShortRecording ? SHORT_RECORDING_COMMENT : ''),
                            'questions' => array(),
                        ),
                        $activity,
                        (string)($callLogInfo['transferResult'] ?? ''),
                        $callLogInfo['callMetrics'] ?? array(),
                        $callLogInfo['callUsers'] ?? array(),
                        (string)($callLogInfo['callLogUrl'] ?? ''),
                        $callLogInfo['oracleRouteItems'] ?? array()
                    );
        
                }
                catch (Throwable $throwable)
                {
                    $oracleError = $throwable->getMessage();
                    writeEndCallError('Oracle base activity save exception', array(
                        'activity_id' => $activityId,
                        'exception' => get_class($throwable),
                        'message' => $throwable->getMessage(),
                    ));
                }
            }
        }
        
        if (!$hasSavedRecording && is_array($activity))
        {
            try
            {
                $activityDescriptionUpdated = updateCrmActivityDescription(
                    $activityId,
                    buildNoRecordingActivityDescription($activityId, $callLogInfo)
                );
            }
            catch (Throwable $throwable)
            {
                $activityDescriptionError = $throwable->getMessage();
                writeEndCallError('Missing recording activity description update exception', array(
                    'activity_id' => $activityId,
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                ));
            }
        }
        elseif ($isShortRecording)
        {
            try
            {
                $activityDescriptionUpdated = updateCrmActivityDescription(
                    $activityId,
                    buildShortRecordingActivityDescription($activityId, $callLogInfo)
                );
            }
            catch (Throwable $throwable)
            {
                $activityDescriptionError = $throwable->getMessage();
                writeEndCallError('Short recording activity description update exception', array(
                    'activity_id' => $activityId,
                    'RECORD_SECONDS' => $recordSeconds,
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                ));
            }
        }
        else
        {
            if ($storageElementId !== null && is_array($config))
            {
                try
                {
                    $fileInfo = getBitrixDiskFileInfo($storageElementId);
                    $uploadResult = uploadBitrixFileToNextcloud($fileInfo, $config, $data);
                }
                catch (Throwable $throwable)
                {
                    $nextcloudError = $throwable->getMessage();
                    writeEndCallError('Nextcloud upload exception', array(
                        'activity_id' => $activityId,
                        'STORAGE_ELEMENT_ID' => $storageElementId,
                        'exception' => get_class($throwable),
                        'message' => $throwable->getMessage(),
                    ));
                }
            }
        
            if (is_array($uploadResult))
            {
                try
                {
                    \Airecogn\Service\RecognitionResultRepository::savePending($activityId);
                }
                catch (Throwable $throwable)
                {
                    writeEndCallError('Recognition result pending save exception', array(
                        'activity_id' => $activityId,
                        'message' => $throwable->getMessage(),
                    ));
                }
        
                try
                {
                    $activityDescriptionUpdated = rewriteCrmActivityDescription(
                        $activityId,
                        $callLogInfo,
                        (string)$uploadResult['remotePath']
                    );
                }
                catch (Throwable $throwable)
                {
                    $activityDescriptionError = $throwable->getMessage();
                    writeEndCallError('CRM activity description update exception', array(
                        'activity_id' => $activityId,
                        'exception' => get_class($throwable),
                        'message' => $throwable->getMessage(),
                    ));
                }
            }
        }
        
        writeEndCallLog(array(
            'receivedAt' => date('Y-m-d H:i:s'),
            'activity_id' => $activityId,
            'CALL_LOG_URL' => $callLogInfo['callLogUrl'] ?? null,
            'CALL_METRICS' => $callLogInfo['callMetrics'] ?? array(),
            'TRANSFER_ROUTE' => $callLogInfo['transferRoute'] ?? array(),
            'SHORT_RECORDING_SKIPPED' => $isShortRecording && $hasSavedRecording,
            'NO_RECORDING_SKIPPED' => !$hasSavedRecording && is_array($activity),
            'SAVED_RECORDING_EXISTS' => $hasSavedRecording,
            'ORACLE_SAVED' => $oracleSaved,
            'ORACLE_ERROR' => $oracleError,
            'STORAGE_ELEMENT_ID' => $storageElementId,
            'BITRIX_FILE_ID' => is_array($fileInfo) ? $fileInfo['fileId'] : null,
            'BITRIX_FILE_NAME' => is_array($fileInfo) ? $fileInfo['name'] : null,
            'NEXTCLOUD_REMOTE_PATH' => is_array($uploadResult) ? $uploadResult['remotePath'] : null,
            'NEXTCLOUD_HTTP_CODE' => is_array($uploadResult) ? $uploadResult['httpCode'] : null,
            'NEXTCLOUD_ERROR' => $nextcloudError,
            'CRM_ACTIVITY_DESCRIPTION_UPDATED' => $activityDescriptionUpdated,
            'CRM_ACTIVITY_DESCRIPTION_ERROR' => $activityDescriptionError,
        ));
        
        return array(
            'status' => 'ok',
            'activityId' => $activityId,
            'recordingUploaded' => is_array($uploadResult),
            'recognitionPending' => is_array($uploadResult),
            'nextcloudError' => $nextcloudError,
            'activityDescriptionError' => $activityDescriptionError,
        );
    }
}
