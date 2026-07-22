<?php

use Bitrix\Disk\File;
use Bitrix\Main\Loader;

const SHORT_RECORDING_COMMENT = 'Длительность разговора <10 сек. Расшифровке не подлежит.';
const NO_RECORDING_COMMENT = 'Нет сохраненной записи разговора. Расшифровке не подлежит.';

require_once __DIR__ . '/functions.php';

function loadEndCallConfig(): array
{
    return \Airecogn\Config::getIntegrationConfig();
}

function writeEndCallLog(array $payload): void
{
    \Airecogn\Service\Logger::write(\Airecogn\Service\Logger::CHANNEL_END_CALL, $payload, (($payload['type'] ?? '') === 'error' ? 'error' : 'info'));
}

function writeEndCallError($message, array $context = array()): void
{
    $activityId = $context['activity_id'] ?? null;
    unset($context['activity_id']);

    $record = array(
        'receivedAt' => date('Y-m-d H:i:s'),
        'type' => 'error',
        'message' => $message,
        'context' => $context,
    );
    if (is_int($activityId) && $activityId > 0)
    {
        $record['activity_id'] = $activityId;
    }

    writeEndCallLog($record);
}

function getCrmActivityStorageElementId(array $activity): ?int
{
    $storageElementIds = @unserialize((string)($activity['STORAGE_ELEMENT_IDS'] ?? ''), array('allowed_classes' => false));

    if (!is_array($storageElementIds) || empty($storageElementIds))
    {
        return null;
    }

    $storageElementId = (int)reset($storageElementIds);

    return $storageElementId > 0 ? $storageElementId : null;
}

function getBitrixDiskFileInfo(int $storageElementId): array
{
    if ($storageElementId <= 0)
    {
        throw new RuntimeException('STORAGE_ELEMENT_ID is empty');
    }

    if (!class_exists('Bitrix\\Main\\Loader') || !Loader::includeModule('disk'))
    {
        throw new RuntimeException('Disk module load failed');
    }

    if (!class_exists('Bitrix\\Disk\\File'))
    {
        throw new RuntimeException('Bitrix Disk File class not found');
    }

    $diskFile = File::loadById($storageElementId);

    if (!$diskFile instanceof File)
    {
        throw new RuntimeException('Disk file not found');
    }

    $fileId = (int)$diskFile->getFileId();

    if ($fileId <= 0)
    {
        throw new RuntimeException('Disk file b_file ID is empty');
    }

    $fileArray = CFile::GetFileArray($fileId);

    if (!is_array($fileArray))
    {
        throw new RuntimeException('Bitrix file not found');
    }

    $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    $subDir = (string)($fileArray['SUBDIR'] ?? '');
    $storedFileName = (string)($fileArray['FILE_NAME'] ?? '');
    $absolutePath = $subDir !== '' && $storedFileName !== ''
        ? $documentRoot . '/upload/' . trim($subDir, '/\\') . '/' . $storedFileName
        : '';

    if ($absolutePath === '' || !is_file($absolutePath))
    {
        throw new RuntimeException('Bitrix file path not found');
    }

    return array(
        'fileId' => $fileId,
        'storageElementId' => $storageElementId,
        'name' => (string)($fileArray['ORIGINAL_NAME'] ?: $diskFile->getName()),
        'absolutePath' => $absolutePath,
        'size' => (int)($fileArray['FILE_SIZE'] ?? filesize($absolutePath)),
        'contentType' => (string)($fileArray['CONTENT_TYPE'] ?? 'application/octet-stream'),
    );
}

function normalizeRemotePath(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    $path = preg_replace('#/+#', '/', $path);

    if ($path === '' || $path === '/')
    {
        return '';
    }

    return '/' . trim($path, '/');
}

function encodeWebDavPath(string $path): string
{
    $path = normalizeRemotePath($path);

    if ($path === '')
    {
        return '';
    }

    $segments = array_map('rawurlencode', explode('/', trim($path, '/')));

    return '/' . implode('/', $segments);
}

function makeSafeRemoteFileName(string $fileName): string
{
    $fileName = trim(str_replace(array('/', '\\'), '_', $fileName));

    if ($fileName === '')
    {
        return 'call-file';
    }

    return $fileName;
}

function requestNextcloudWebDav(string $method, string $webdavUrl, string $remotePath, array $config, $fileHandle = null, int $fileSize = 0): array
{
    if (!function_exists('curl_init'))
    {
        return array(
            'success' => false,
            'httpCode' => 0,
            'error' => 'PHP cURL extension is not available',
        );
    }

    $url = rtrim($webdavUrl, '/') . encodeWebDavPath($remotePath);
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_USERPWD, (string)$config['username'] . ':' . (string)$config['password']);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 120);

    if ($fileHandle !== null)
    {
        curl_setopt($curl, CURLOPT_UPLOAD, true);
        curl_setopt($curl, CURLOPT_INFILE, $fileHandle);
        curl_setopt($curl, CURLOPT_INFILESIZE, $fileSize);
    }

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return array(
        'success' => $curlError === '' && $httpCode >= 200 && $httpCode < 300,
        'httpCode' => $httpCode,
        'error' => $curlError !== '' ? $curlError : null,
        'response' => is_string($response) ? substr($response, 0, 500) : null,
        'url' => $url,
    );
}

function uploadBitrixFileToNextcloud(array $fileInfo, array $config, array $data): array
{
    $nextcloud = is_array($config['nextcloud'] ?? null) ? $config['nextcloud'] : array();
    $webdavUrl = rtrim((string)($nextcloud['webdavUrl'] ?? ''), '/');
    $username = (string)($nextcloud['username'] ?? '');
    $password = (string)($nextcloud['password'] ?? '');

    if ($webdavUrl === '' || $username === '' || $password === '')
    {
        throw new RuntimeException('Nextcloud config is incomplete');
    }

    $callDate = strtotime((string)($data['CALL_START_DATE'] ?? ''));
    if ($callDate === false)
    {
        throw new UnexpectedValueException('CALL_START_DATE has invalid format');
    }

    $remoteDirTemplate = (string)($nextcloud['remoteDir'] ?? '/Calls/{Y}/{m}');
    $remoteDir = strtr($remoteDirTemplate, array(
        '{Y}' => date('Y', $callDate),
        '{m}' => date('m', $callDate),
        '{d}' => date('d', $callDate),
    ));

    $activityId = (string)$data['CRM_ACTIVITY_ID'];
    $extension = pathinfo((string)$fileInfo['name'], PATHINFO_EXTENSION);
    $remoteFileName = makeSafeRemoteFileName(
        $activityId . '_' . $fileInfo['storageElementId'] . ($extension !== '' ? '.' . $extension : '')
    );
    $remotePath = normalizeRemotePath($remoteDir . '/' . $remoteFileName);

    $requestConfig = array(
        'username' => $username,
        'password' => $password,
    );

    $fileHandle = fopen($fileInfo['absolutePath'], 'rb');

    if ($fileHandle === false)
    {
        throw new RuntimeException('Bitrix file open failed');
    }

    $result = requestNextcloudWebDav(
        'PUT',
        $webdavUrl,
        $remotePath,
        $requestConfig,
        $fileHandle,
        (int)$fileInfo['size']
    );

    fclose($fileHandle);

    if (!$result['success'])
    {
        throw new RuntimeException(
            'Nextcloud upload failed: HTTP ' . $result['httpCode'] . ($result['error'] ? ', ' . $result['error'] : '')
        );
    }

    return array(
        'remotePath' => $remotePath,
        'httpCode' => $result['httpCode'],
    );
}

function buildTechnicalActivityDescription(int $activityId, array $callLogInfo, string $resultLine): string
{
    $callUsers = is_array($callLogInfo['callUsers'] ?? null) ? $callLogInfo['callUsers'] : array();
    $firstLine = '[B]ID:[/B] ' . $activityId;

    if (trim((string)($callUsers['first'] ?? '')) !== '')
    {
        $firstLine .= ' | [B]Первый:[/B] ' . trim((string)$callUsers['first']);
    }

    if (trim((string)($callUsers['last'] ?? '')) !== '')
    {
        $firstLine .= ' | [B]Последний:[/B] ' . trim((string)$callUsers['last']);
    }

    $lines = array($firstLine);
    $transferRoute = is_array($callLogInfo['transferRoute'] ?? null) ? $callLogInfo['transferRoute'] : array();

    if (!empty($transferRoute))
    {
        $transferTitle = (string)($callLogInfo['transferResult'] ?? '') === 'success' ? 'Переводы+' : 'Переводы-';
        $lines[] = '[B]' . $transferTitle . ':[/B] ' . implode(' > ', $transferRoute);
    }

    $callMetrics = is_array($callLogInfo['callMetrics'] ?? null) ? $callLogInfo['callMetrics'] : array();
    $metricsParts = array();

    if (isset($callMetrics['recordSeconds']))
    {
        $metricsParts[] = 'записи ' . (int)$callMetrics['recordSeconds'] . ' сек';
    }

    if (isset($callMetrics['secondsToFirstAnswer']))
    {
        $metricsParts[] = 'до ответа ' . (int)$callMetrics['secondsToFirstAnswer'] . ' сек';
    }

    if (isset($callMetrics['transferSeconds']))
    {
        $metricsParts[] = 'переводы ' . (int)$callMetrics['transferSeconds'] . ' сек';
    }

    if (!empty($metricsParts))
    {
        $lines[] = '[B]Время:[/B] ' . implode(' | ', $metricsParts);
    }

    $lines[] = '────────────────────────────';
    $lines[] = $resultLine;

    return trim(implode(PHP_EOL, $lines));
}

function buildPendingActivityDescription(int $activityId, array $callLogInfo, string $remotePath): string
{
    $fileName = basename(str_replace('\\', '/', $remotePath));

    return buildTechnicalActivityDescription(
        $activityId,
        $callLogInfo,
        AI_CALL_RECOGNISER_PENDING_MARKER . ' Отправлено на расшифровку: ' . $fileName
    );
}

function buildShortRecordingActivityDescription(int $activityId, array $callLogInfo): string
{
    return buildTechnicalActivityDescription($activityId, $callLogInfo, SHORT_RECORDING_COMMENT);
}

function buildNoRecordingActivityDescription(int $activityId, array $callLogInfo): string
{
    return buildTechnicalActivityDescription($activityId, $callLogInfo, NO_RECORDING_COMMENT);
}

function rewriteCrmActivityDescription(int $activityId, array $callLogInfo, string $remotePath): bool
{
    if ($activityId <= 0)
    {
        throw new RuntimeException('CRM_ACTIVITY_ID is empty');
    }

    if (!class_exists('Bitrix\\Main\\Loader') || !Loader::includeModule('crm'))
    {
        throw new RuntimeException('CRM module load failed');
    }

    if (!class_exists('CCrmActivity'))
    {
        throw new RuntimeException('CCrmActivity class not found');
    }

    $activity = CCrmActivity::GetByID($activityId, false);

    if (!is_array($activity))
    {
        throw new RuntimeException('CRM activity not found');
    }

    $currentDescription = (string)($activity['DESCRIPTION'] ?? '');
    $hasCompletedRecognition = strpos($currentDescription, '[B]ID:[/B] ' . $activityId) !== false
        && strpos($currentDescription, AI_CALL_RECOGNISER_PENDING_MARKER) === false
        && (strpos($currentDescription, '[B]Вопросы:[/B]') !== false
            || strpos($currentDescription, RECOGNITION_ERROR_COMMENT) !== false);

    if ($hasCompletedRecognition)
    {
        return true;
    }

    $description = buildPendingActivityDescription($activityId, $callLogInfo, $remotePath);

    $result = CCrmActivity::Update(
        $activityId,
        array('DESCRIPTION' => $description),
        false,
        false
    );

    if (!$result)
    {
        throw new RuntimeException('CRM activity description update failed');
    }

    return true;
}
