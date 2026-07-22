<?php

use Bitrix\Main\Loader;

const RECOGNITION_ERROR_COMMENT = 'Ошибка распознования. Обратитесь к администратору';
const AI_CALL_RECOGNISER_PENDING_MARKER = '[AI_CALL_RECOGNISER_PENDING]';

function loadInboundConfig(): array
{
    return \Airecogn\Config::getIntegrationConfig();
}

function buildActivityDescription(array $payload, array $activity, array $transferRoute, string $transferResult, array $callMetrics, array $callUsers): string
{
    $activityId = trim((string)($payload['call_id'] ?? ''));
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

    if (!empty($transferRoute))
    {
        $transferTitle = $transferResult === 'success' ? 'Переводы+' : 'Переводы-';
        $lines[] = '[B]' . $transferTitle . ':[/B] ' . implode(' > ', $transferRoute);
    }

    if (!empty($callMetrics))
    {
        $metricsParts = array();

        if (isset($callMetrics['recordSeconds']))
        {
            $metricsParts[] = 'записи ' . $callMetrics['recordSeconds'] . ' сек';
        }

        if (isset($callMetrics['secondsToFirstAnswer']))
        {
            $metricsParts[] = 'до ответа ' . $callMetrics['secondsToFirstAnswer'] . ' сек';
        }

        if (isset($callMetrics['transferSeconds']))
        {
            $metricsParts[] = 'переводы ' . $callMetrics['transferSeconds'] . ' сек';
        }

        if (!empty($metricsParts))
        {
            $lines[] = '[B]Время:[/B] ' . implode(' | ', $metricsParts);
        }
    }

    $lines[] = '────────────────────────────';

    if ((string)($payload['call_status'] ?? '') !== 'success')
    {
        $lines[] = buildRecognitionErrorBlock($payload);
        return trim(implode(PHP_EOL, $lines));
    }

    $lines[] = '[B]Вопросы:[/B]';
    $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : array();

    foreach ($questions as $question)
    {
        if (!is_array($question))
        {
            continue;
        }

        $id = trim((string)($question['id'] ?? ''));
        $text = trim((string)($question['text'] ?? ''));
        $isPositiveAnswer = (string)($question['answer'] ?? '') === 'true';
        $answerMark = $isPositiveAnswer ? '+' : '-';

        if ($id === '' && $text === '')
        {
            continue;
        }

        $lines[] = '[B]' . $id . $answerMark . '[/B]: ' . $text;
    }

    $lines[] = '────────────────────────────';
    $lines[] = '[B]Кратко:[/B]';
    $lines[] = trim((string)($payload['summary'] ?? ''));

    return trim(implode(PHP_EOL, $lines));
}

function buildRecognitionErrorBlock(array $payload): string
{
    $lines = array(RECOGNITION_ERROR_COMMENT);
    $summary = trim((string)($payload['summary'] ?? ''));

    if ($summary !== '')
    {
        $lines[] = '────────────────────────────';
        $lines[] = '[B]Кратко:[/B]';
        $lines[] = $summary;
    }

    return implode(PHP_EOL, $lines);
}

function buildRecognitionResultBlock(array $payload): string
{
    if ((string)($payload['call_status'] ?? '') !== 'success')
    {
        return buildRecognitionErrorBlock($payload);
    }

    $lines = array('[B]Вопросы:[/B]');
    $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : array();

    foreach ($questions as $question)
    {
        if (!is_array($question))
        {
            continue;
        }

        $id = trim((string)($question['id'] ?? ''));
        $text = trim((string)($question['text'] ?? ''));
        $answerMark = (string)($question['answer'] ?? '') === 'true' ? '+' : '-';

        if ($id === '' && $text === '')
        {
            continue;
        }

        $lines[] = '[B]' . $id . $answerMark . '[/B]: ' . $text;
    }

    $lines[] = '────────────────────────────';
    $lines[] = '[B]Кратко:[/B]';
    $lines[] = trim((string)($payload['summary'] ?? ''));

    return trim(implode(PHP_EOL, $lines));
}

function replacePendingRecognitionBlock(string $description, array $payload): ?string
{
    $lines = preg_split('/\R/', $description);

    foreach ($lines as $index => $line)
    {
        if (strpos($line, AI_CALL_RECOGNISER_PENDING_MARKER) === false)
        {
            continue;
        }

        array_splice($lines, $index, 1, array(buildRecognitionResultBlock($payload)));

        return trim(implode(PHP_EOL, $lines));
    }

    return null;
}

function getCrmActivity(int $activityId): array
{
    if ($activityId <= 0)
    {
        throw new RuntimeException('call_id is empty');
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

    return $activity;
}

function getVoximplantStatistic(int $activityId): array
{
    if (!Loader::includeModule('voximplant') || !class_exists('Bitrix\\Voximplant\\StatisticTable'))
    {
        return array();
    }

    $result = Bitrix\Voximplant\StatisticTable::getList(array(
        'select' => array('ID', 'CALL_LOG', 'RECORD_DURATION', 'CALL_START_DATE'),
        'filter' => array('=CRM_ACTIVITY_ID' => $activityId),
        'order' => array('ID' => 'DESC'),
        'limit' => 1,
    ));

    $row = $result->fetch();

    return is_array($row) ? $row : array();
}

function getRecordDurationSeconds(array $statistic): ?int
{
    if (!array_key_exists('RECORD_DURATION', $statistic))
    {
        return null;
    }

    $recordDuration = $statistic['RECORD_DURATION'];

    if ($recordDuration === null || $recordDuration === '')
    {
        return 0;
    }

    return is_numeric($recordDuration) ? max(0, (int)$recordDuration) : null;
}

function readCallLog(string $callLogUrl): string
{
    if ($callLogUrl === '')
    {
        return '';
    }

    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 5,
        ),
        'https' => array(
            'timeout' => 5,
        ),
    ));

    $content = @file_get_contents($callLogUrl, false, $context);

    return is_string($content) ? $content : '';
}

function parseLogTimestamp(string $line): ?float
{
    if (!preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\.(\d{3})/', $line, $matches))
    {
        return null;
    }

    $timestamp = strtotime($matches[1]);

    if ($timestamp === false)
    {
        return null;
    }

    return $timestamp + ((int)$matches[2] / 1000);
}

function getNormalizedCallLogLines(string $callLog): array
{
    return preg_split('/\R/', str_replace('\"', '"', $callLog));
}

function parseStartTransferLine(string $line): array
{
    if (strpos($line, '"COMMAND":"startTransfer"') === false)
    {
        return array();
    }

    if (!preg_match('/"CALL_ID":"(transfer\.[^"]+)"/', $line, $callMatches)
        || !preg_match('/"OPERATOR_ID":(\d+)/', $line, $operatorMatches))
    {
        return array();
    }

    return array(
        'callId' => $callMatches[1],
        'operatorId' => (int)$operatorMatches[1],
    );
}

function parseInviteUserIds(string $line): array
{
    if (strpos($line, '"COMMAND":"invite"') === false)
    {
        return array();
    }

    if (!preg_match('/"USERS":\[(.*?)\]/', $line, $inviteMatches))
    {
        return array();
    }

    if (!preg_match_all('/"USER_ID":"?(\d+)"?/', $inviteMatches[1], $userMatches))
    {
        return array();
    }

    return array_values(array_unique(array_map('intval', $userMatches[1])));
}

function parsePstnPhoneNumber(string $line): string
{
    if (strpos($line, '"COMMAND":"pstn"') === false)
    {
        return '';
    }

    if (!preg_match('/"PHONE_NUMBER":"([^"]+)","USER_ID":null/', $line, $pstnMatches))
    {
        return '';
    }

    return trim($pstnMatches[1]);
}

function addRouteItem(array &$routeItems, string $type, $value): void
{
    $lastRouteItem = end($routeItems);

    if (is_array($lastRouteItem)
        && $lastRouteItem['type'] === $type
        && $lastRouteItem['value'] === $value)
    {
        return;
    }

    $routeItems[] = array('type' => $type, 'value' => $value);
}

function isUserTransferCancelLine(string $line): bool
{
    return strpos($line, '"COMMAND":"CancelTransfer"') !== false
        && strpos($line, '"REASON":"Call session terminating"') === false;
}

function isFirstAnswerStartLine(string $line): bool
{
    return strpos($line, 'Executing JS command: CallSIP') !== false
        || strpos($line, '"COMMAND":"enqueue"') !== false
        || strpos($line, '"COMMAND":"invite"') !== false
        || strpos($line, '"COMMAND":"InviteUsers"') !== false;
}

function isMainStartCallLine(string $line): bool
{
    return strpos($line, '"COMMAND":"StartCall"') !== false
        && strpos($line, '"CALL_ID":"transfer.') === false;
}

function isTransferWaitEndLine(string $line): bool
{
    return strpos($line, 'name = Call.Connected') !== false
        || strpos($line, '"COMMAND":"CancelTransfer"') !== false;
}

function isTransferCompleteLine(string $line): bool
{
    return strpos($line, '"COMMAND":"CompleteTransfer"') !== false;
}

function parseTransferRouteFromCallLog(string $callLog): array
{
    $lines = getNormalizedCallLogLines($callLog);
    $started = false;
    $transferOperators = array();
    $activeTransferCallId = '';
    $activeTransferHasRouteItem = false;
    $routeItems = array();
    $userIds = array();
    $firstUserId = null;
    $transferResult = '';

    foreach ($lines as $line)
    {
        if ($firstUserId === null
            && isMainStartCallLine($line)
            && preg_match('/"USER_ID":(\d+)/', $line, $startCallUserMatches))
        {
            $firstUserId = (int)$startCallUserMatches[1];
            $userIds[] = $firstUserId;
        }

        $startTransfer = parseStartTransferLine($line);

        if (!empty($startTransfer))
        {
            $started = true;
            $activeTransferCallId = $startTransfer['callId'];
            $activeTransferHasRouteItem = false;
            $transferOperators[$activeTransferCallId] = $startTransfer['operatorId'];

            continue;
        }

        if (!$started)
        {
            continue;
        }

        $inviteUserIds = parseInviteUserIds($line);

        if (!empty($inviteUserIds))
        {
            foreach ($inviteUserIds as $userId)
            {
                if ($userId <= 0)
                {
                    continue;
                }

                addRouteItem($routeItems, 'user', $userId);
                $activeTransferHasRouteItem = true;
                $userIds[] = $userId;
            }

            continue;
        }

        $phoneNumber = parsePstnPhoneNumber($line);

        if ($phoneNumber !== '')
        {
            addRouteItem($routeItems, 'phone', $phoneNumber);
            $activeTransferHasRouteItem = true;

            continue;
        }

        if ($activeTransferHasRouteItem && isTransferCompleteLine($line))
        {
            $lastRouteItem = end($routeItems);

            if (is_array($lastRouteItem) && $lastRouteItem['type'] !== 'operator')
            {
                $transferResult = 'success';
            }

            continue;
        }

        if ($activeTransferHasRouteItem && isUserTransferCancelLine($line))
        {
            if ($activeTransferCallId !== '' && isset($transferOperators[$activeTransferCallId]))
            {
                $operatorId = $transferOperators[$activeTransferCallId];
                addRouteItem($routeItems, 'operator', $operatorId);

                $userIds[] = $operatorId;
                $transferResult = 'fail';
            }
        }
    }

    return array(
        'routeItems' => $routeItems,
        'userIds' => array_values(array_unique($userIds)),
        'firstUserId' => $firstUserId,
        'transferResult' => $transferResult,
    );
}

function parseCallMetricsFromCallLog(string $callLog): array
{
    $lines = getNormalizedCallLogLines($callLog);
    $firstCallStartAt = null;
    $firstAnswerAt = null;
    $activeTransferStartAt = null;
    $lastTimestamp = null;
    $transferSeconds = 0;

    foreach ($lines as $line)
    {
        $lineTimestamp = parseLogTimestamp($line);

        if ($lineTimestamp !== null)
        {
            $lastTimestamp = $lineTimestamp;
        }
        elseif (strpos($line, '"COMMAND":') !== false)
        {
            $lineTimestamp = $lastTimestamp;
        }

        if ($lineTimestamp === null)
        {
            continue;
        }

        if ($firstCallStartAt === null && isFirstAnswerStartLine($line))
        {
            $firstCallStartAt = $lineTimestamp;
        }

        if ($firstCallStartAt !== null
            && $firstAnswerAt === null
            && strpos($line, 'name = Call.Connected') !== false)
        {
            $firstAnswerAt = $lineTimestamp;
        }

        if (!empty(parseStartTransferLine($line)))
        {
            $activeTransferStartAt = $lineTimestamp;
            continue;
        }

        if ($activeTransferStartAt !== null && isTransferWaitEndLine($line))
        {
            if ($lineTimestamp >= $activeTransferStartAt)
            {
                $transferSeconds += (int)ceil($lineTimestamp - $activeTransferStartAt);
            }

            $activeTransferStartAt = null;
        }
    }

    $metrics = array();

    if ($firstCallStartAt !== null && $firstAnswerAt !== null && $firstAnswerAt >= $firstCallStartAt)
    {
        $metrics['secondsToFirstAnswer'] = (int)ceil($firstAnswerAt - $firstCallStartAt);
    }

    if ($transferSeconds > 0)
    {
        $metrics['transferSeconds'] = $transferSeconds;
    }

    return $metrics;
}

function getUserLastNamesByIds(array $userIds): array
{
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    $names = array();

    if (empty($userIds) || !class_exists('Bitrix\\Main\\UserTable'))
    {
        return $names;
    }

    $result = Bitrix\Main\UserTable::getList(array(
        'select' => array('ID', 'LAST_NAME', 'NAME', 'SECOND_NAME'),
        'filter' => array('@ID' => $userIds),
    ));

    while ($user = $result->fetch())
    {
        $userId = (int)($user['ID'] ?? 0);
        $lastName = trim((string)($user['LAST_NAME'] ?? ''));

        if ($lastName === '')
        {
            $lastName = trim(implode(' ', array_filter(array(
                trim((string)($user['NAME'] ?? '')),
                trim((string)($user['SECOND_NAME'] ?? '')),
            ))));
        }

        if ($userId > 0 && $lastName !== '')
        {
            $names[$userId] = $lastName;
        }
    }

    return $names;
}

function buildCallLogInfo(int $activityId, array $activity, ?array $statistic = null): array
{
    $statistic = is_array($statistic) ? $statistic : getVoximplantStatistic($activityId);
    $callLogUrl = trim((string)($statistic['CALL_LOG'] ?? ''));
    $callLog = readCallLog($callLogUrl);
    $route = parseTransferRouteFromCallLog($callLog);
    $metrics = parseCallMetricsFromCallLog($callLog);

    $recordSeconds = getRecordDurationSeconds($statistic);

    if ($recordSeconds !== null)
    {
        $metrics = array('recordSeconds' => $recordSeconds) + $metrics;
    }

    $userLastNames = getUserLastNamesByIds($route['userIds']);
    $responsibleId = (int)($activity['RESPONSIBLE_ID'] ?? 0);

    if ($responsibleId > 0 && !isset($userLastNames[$responsibleId]))
    {
        $responsibleNames = getUserLastNamesByIds(array($responsibleId));
        $userLastNames = $userLastNames + $responsibleNames;
    }

    $items = array();
    $oracleRouteItems = array();
    $sort = 1;

    foreach ($route['routeItems'] as $routeItem)
    {
        if (!is_array($routeItem))
        {
            continue;
        }

        if ($routeItem['type'] === 'user' || $routeItem['type'] === 'operator')
        {
            $userId = (int)$routeItem['value'];
            $userLastName = $userLastNames[$userId] ?? ('ID ' . $userId);
            $items[] = $userLastName;
            $oracleRouteItems[] = array(
                'SORT' => $sort++,
                'ITEM_TYPE' => $routeItem['type'],
                'USER_ID' => $userId,
                'USER_LAST_NAME' => $userLastName,
                'PHONE_NUMBER' => null,
            );
            continue;
        }

        $phoneNumber = (string)$routeItem['value'];
        $items[] = $phoneNumber;
        $oracleRouteItems[] = array(
            'SORT' => $sort++,
            'ITEM_TYPE' => 'phone',
            'USER_ID' => null,
            'USER_LAST_NAME' => null,
            'PHONE_NUMBER' => $phoneNumber,
        );
    }

    return array(
        'callLogUrl' => $callLogUrl,
        'transferRoute' => $items,
        'oracleRouteItems' => $oracleRouteItems,
        'transferResult' => $route['transferResult'],
        'callMetrics' => $metrics,
        'callUsers' => array(
            'first' => $route['firstUserId'] !== null ? ($userLastNames[$route['firstUserId']] ?? ('ID ' . $route['firstUserId'])) : '',
            'last' => $responsibleId > 0 ? ($userLastNames[$responsibleId] ?? ('ID ' . $responsibleId)) : '',
        ),
    );
}

function updateCrmActivityDescription(int $activityId, string $description): bool
{
    if ($activityId <= 0)
    {
        throw new InvalidArgumentException('Activity ID must be a positive integer');
    }

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

function completeCrmActivity(int $activityId): bool
{
    if ($activityId <= 0)
    {
        throw new RuntimeException('CRM activity ID is empty');
    }

    $result = CCrmActivity::Update(
        $activityId,
        array('COMPLETED' => 'Y'),
        false,
        false
    );

    if (!$result)
    {
        throw new RuntimeException('CRM activity completion failed');
    }

    return true;
}

function getOracleErrorMessage($resource = null): string
{
    $error = $resource === null ? oci_error() : oci_error($resource);

    return is_array($error) ? (string)($error['message'] ?? 'Unknown Oracle error') : 'Unknown Oracle error';
}

function executeOracleActivityStatement($connection, string $sql, array $row): int
{
    $statement = oci_parse($connection, $sql);

    if (!$statement)
    {
        throw new RuntimeException(getOracleErrorMessage($connection));
    }

    $activityId = $row['ACTIVITY_ID'];
    $contactId = $row['CONTACT_ID'];
    $activityCreatedAt = $row['ACTIVITY_CREATED_AT'];
    $typeId = $row['TYPE_ID'];
    $providerId = $row['PROVIDER_ID'];
    $providerTypeId = $row['PROVIDER_TYPE_ID'];
    $direction = $row['DIRECTION'];
    $subject = $row['SUBJECT'];
    $recognitionStatus = $row['RECOGNITION_STATUS'];
    $summary = $row['SUMMARY'];
    $firstOperator = $row['FIRST_OPERATOR'];
    $lastOperator = $row['LAST_OPERATOR'];
    $recordSeconds = $row['RECORD_SECONDS'];
    $secondsToFirstAnswer = $row['SECONDS_TO_FIRST_ANSWER'];
    $transferSeconds = $row['TRANSFER_SECONDS'];
    $transferResult = $row['TRANSFER_RESULT'];
    $callLogUrl = $row['CALL_LOG_URL'];
    $summaryDescriptor = null;

    try
    {
        if (strpos($sql, ':summary') !== false)
        {
            $summaryDescriptor = oci_new_descriptor($connection, OCI_D_LOB);

            if (!$summaryDescriptor)
            {
                throw new RuntimeException(getOracleErrorMessage($connection));
            }

            if (!$summaryDescriptor->writeTemporary($summary, OCI_TEMP_CLOB))
            {
                throw new RuntimeException(getOracleErrorMessage($connection));
            }
        }

        oci_bind_by_name($statement, ':activity_id', $activityId);
        oci_bind_by_name($statement, ':contact_id', $contactId);
        oci_bind_by_name($statement, ':activity_created_at', $activityCreatedAt, 19);
        oci_bind_by_name($statement, ':type_id', $typeId);
        oci_bind_by_name($statement, ':provider_id', $providerId, 100);
        oci_bind_by_name($statement, ':provider_type_id', $providerTypeId, 100);
        oci_bind_by_name($statement, ':direction', $direction);
        oci_bind_by_name($statement, ':subject', $subject, 500);
        oci_bind_by_name($statement, ':recognition_status', $recognitionStatus, 50);
        if ($summaryDescriptor)
        {
            oci_bind_by_name($statement, ':summary', $summaryDescriptor, -1, OCI_B_CLOB);
        }
        oci_bind_by_name($statement, ':first_operator', $firstOperator, 255);
        oci_bind_by_name($statement, ':last_operator', $lastOperator, 255);
        oci_bind_by_name($statement, ':record_seconds', $recordSeconds);
        oci_bind_by_name($statement, ':seconds_to_first_answer', $secondsToFirstAnswer);
        oci_bind_by_name($statement, ':transfer_seconds', $transferSeconds);
        oci_bind_by_name($statement, ':transfer_result', $transferResult, 50);
        oci_bind_by_name($statement, ':call_log_url', $callLogUrl, 2000);

        if (!oci_execute($statement, OCI_NO_AUTO_COMMIT))
        {
            throw new RuntimeException(getOracleErrorMessage($statement));
        }

        return oci_num_rows($statement);
    }
    finally
    {
        if ($summaryDescriptor)
        {
            $summaryDescriptor->free();
        }

        oci_free_statement($statement);
    }
}

function buildOracleActivityRow(array $payload, array $activity, string $transferResult, array $callMetrics, array $callUsers, string $callLogUrl): array
{
    $routeResult = $transferResult !== '' ? $transferResult : null;

    return array(
        'ACTIVITY_ID' => $payload['call_id'],
        'CONTACT_ID' => \Airecogn\Service\ContactResolver::resolveByActivityId(
            $payload['call_id'],
            $activity
        ),
        'ACTIVITY_CREATED_AT' => trim((string)($activity['CREATED'] ?? '')),
        'TYPE_ID' => isset($activity['TYPE_ID']) ? (int)$activity['TYPE_ID'] : null,
        'PROVIDER_ID' => trim((string)($activity['PROVIDER_ID'] ?? '')),
        'PROVIDER_TYPE_ID' => trim((string)($activity['PROVIDER_TYPE_ID'] ?? '')),
        'DIRECTION' => isset($activity['DIRECTION']) ? (int)$activity['DIRECTION'] : null,
        'SUBJECT' => trim((string)($activity['SUBJECT'] ?? '')),
        'RECOGNITION_STATUS' => trim((string)($payload['call_status'] ?? '')),
        'SUMMARY' => (string)($payload['summary'] ?? ''),
        'FIRST_OPERATOR' => trim((string)($callUsers['first'] ?? '')),
        'LAST_OPERATOR' => trim((string)($callUsers['last'] ?? '')),
        'RECORD_SECONDS' => isset($callMetrics['recordSeconds']) ? (int)$callMetrics['recordSeconds'] : null,
        'SECONDS_TO_FIRST_ANSWER' => isset($callMetrics['secondsToFirstAnswer']) ? (int)$callMetrics['secondsToFirstAnswer'] : null,
        'TRANSFER_SECONDS' => isset($callMetrics['transferSeconds']) ? (int)$callMetrics['transferSeconds'] : null,
        'TRANSFER_RESULT' => $routeResult,
        'CALL_LOG_URL' => $callLogUrl,
    );
}

function replaceOracleActivityRoute($connection, int $activityId, array $routeItems): void
{
    // language=SQL
    $deleteSql = 'DELETE FROM PBI_ABITURIENT.CRM_ACTIVITY_ROUTE WHERE ACTIVITY_ID = :activity_id';
    $statement = oci_parse($connection, $deleteSql);

    if (!$statement)
    {
        throw new RuntimeException(getOracleErrorMessage($connection));
    }

    oci_bind_by_name($statement, ':activity_id', $activityId);

    try
    {
        if (!oci_execute($statement, OCI_NO_AUTO_COMMIT))
        {
            throw new RuntimeException(getOracleErrorMessage($statement));
        }
    }
    finally
    {
        oci_free_statement($statement);
    }

    // language=SQL
    $insertSql = "
        INSERT INTO PBI_ABITURIENT.CRM_ACTIVITY_ROUTE (
            ACTIVITY_ID,
            SORT,
            ITEM_TYPE,
            USER_ID,
            USER_LAST_NAME,
            PHONE_NUMBER,
            CREATED_AT,
            UPDATED_AT
        )
        VALUES (
            :activity_id,
            :sort,
            :item_type,
            :user_id,
            :user_last_name,
            :phone_number,
            SYSDATE,
            SYSDATE
        )
    ";

    foreach ($routeItems as $routeItem)
    {
        if (!is_array($routeItem))
        {
            continue;
        }

        $sort = (int)($routeItem['SORT'] ?? 0);
        $itemType = (string)($routeItem['ITEM_TYPE'] ?? '');
        $userId = $routeItem['USER_ID'];
        $userLastName = $routeItem['USER_LAST_NAME'];
        $phoneNumber = $routeItem['PHONE_NUMBER'];

        if ($sort <= 0 || $itemType === '')
        {
            continue;
        }

        $statement = oci_parse($connection, $insertSql);

        if (!$statement)
        {
            throw new RuntimeException(getOracleErrorMessage($connection));
        }

        try
        {
            oci_bind_by_name($statement, ':activity_id', $activityId);
            oci_bind_by_name($statement, ':sort', $sort);
            oci_bind_by_name($statement, ':item_type', $itemType, 20);
            oci_bind_by_name($statement, ':user_id', $userId);
            oci_bind_by_name($statement, ':user_last_name', $userLastName, 255);
            oci_bind_by_name($statement, ':phone_number', $phoneNumber, 50);

            if (!oci_execute($statement, OCI_NO_AUTO_COMMIT))
            {
                throw new RuntimeException(getOracleErrorMessage($statement));
            }
        }
        finally
        {
            oci_free_statement($statement);
        }
    }
}

function replaceOracleActivityQuestions($connection, int $activityId, array $questions): void
{
    // language=SQL
    $deleteSql = 'DELETE FROM PBI_ABITURIENT.CRM_ACTIVITY_QUESTION WHERE ACTIVITY_ID = :activity_id';
    $statement = oci_parse($connection, $deleteSql);

    if (!$statement)
    {
        throw new RuntimeException(getOracleErrorMessage($connection));
    }

    oci_bind_by_name($statement, ':activity_id', $activityId);

    try
    {
        if (!oci_execute($statement, OCI_NO_AUTO_COMMIT))
        {
            throw new RuntimeException(getOracleErrorMessage($statement));
        }
    }
    finally
    {
        oci_free_statement($statement);
    }

    // language=SQL
    $insertSql = "
        INSERT INTO PBI_ABITURIENT.CRM_ACTIVITY_QUESTION (
            ACTIVITY_ID,
            QUESTION_ID,
            QUESTION_TEXT,
            ANSWER,
            CREATED_AT,
            UPDATED_AT
        )
        VALUES (
            :activity_id,
            :question_id,
            :question_text,
            :answer,
            SYSDATE,
            SYSDATE
        )
    ";

    foreach ($questions as $question)
    {
        if (!is_array($question))
        {
            continue;
        }

        $questionId = (int)($question['id'] ?? 0);
        $questionText = (string)($question['text'] ?? '');
        $answer = (string)($question['answer'] ?? '');

        if ($questionId <= 0)
        {
            continue;
        }

        $statement = oci_parse($connection, $insertSql);

        if (!$statement)
        {
            throw new RuntimeException(getOracleErrorMessage($connection));
        }

        $questionTextDescriptor = null;

        try
        {
            $questionTextDescriptor = oci_new_descriptor($connection, OCI_D_LOB);

            if (!$questionTextDescriptor)
            {
                throw new RuntimeException(getOracleErrorMessage($connection));
            }

            if (!$questionTextDescriptor->writeTemporary($questionText, OCI_TEMP_CLOB))
            {
                throw new RuntimeException(getOracleErrorMessage($connection));
            }

            oci_bind_by_name($statement, ':activity_id', $activityId);
            oci_bind_by_name($statement, ':question_id', $questionId);
            oci_bind_by_name($statement, ':question_text', $questionTextDescriptor, -1, OCI_B_CLOB);
            oci_bind_by_name($statement, ':answer', $answer, 5);

            if (!oci_execute($statement, OCI_NO_AUTO_COMMIT))
            {
                throw new RuntimeException(getOracleErrorMessage($statement));
            }
        }
        finally
        {
            if ($questionTextDescriptor)
            {
                $questionTextDescriptor->free();
            }

            oci_free_statement($statement);
        }
    }
}

function saveOracleActivity(array $config, array $payload, array $activity, string $transferResult, array $callMetrics, array $callUsers, string $callLogUrl, array $routeItems): bool
{
    $oracle = is_array($config['oracle'] ?? null) ? $config['oracle'] : array();

    if (($oracle['enabled'] ?? false) !== true)
    {
        return false;
    }

    if (!extension_loaded('oci8'))
    {
        throw new RuntimeException('OCI8 extension is not loaded');
    }

    $username = trim((string)($oracle['username'] ?? ''));
    $password = (string)($oracle['password'] ?? '');
    $connectionString = trim((string)($oracle['connectionString'] ?? ''));
    $charset = trim((string)($oracle['charset'] ?? 'AL32UTF8'));

    if ($username === '' || $password === '' || $connectionString === '')
    {
        throw new RuntimeException('Oracle config is incomplete');
    }

    $connection = oci_connect($username, $password, $connectionString, $charset);

    if (!$connection)
    {
        throw new RuntimeException(getOracleErrorMessage());
    }

    try
    {
        $row = buildOracleActivityRow($payload, $activity, $transferResult, $callMetrics, $callUsers, $callLogUrl);
        $isPendingRecognition = (string)($payload['call_status'] ?? '') === 'pending';
        $recognitionStatusAssignment = $isPendingRecognition
            ? 'RECOGNITION_STATUS = NVL(RECOGNITION_STATUS, :recognition_status),'
            : 'RECOGNITION_STATUS = :recognition_status,';
        $summaryAssignment = $isPendingRecognition
            ? 'SUMMARY = SUMMARY,'
            : 'SUMMARY = :summary,';
        $insertSummaryValue = $isPendingRecognition ? 'EMPTY_CLOB()' : ':summary';

        // language=SQL
        $updateSql = "
            UPDATE PBI_ABITURIENT.CRM_ACTIVITY
            SET
                CONTACT_ID = :contact_id,
                ACTIVITY_CREATED_AT = TO_DATE(:activity_created_at, 'DD.MM.YYYY HH24:MI:SS'),
                TYPE_ID = :type_id,
                PROVIDER_ID = :provider_id,
                PROVIDER_TYPE_ID = :provider_type_id,
                DIRECTION = :direction,
                SUBJECT = :subject,
                {$recognitionStatusAssignment}
                {$summaryAssignment}
                FIRST_OPERATOR = :first_operator,
                LAST_OPERATOR = :last_operator,
                RECORD_SECONDS = :record_seconds,
                SECONDS_TO_FIRST_ANSWER = :seconds_to_first_answer,
                TRANSFER_SECONDS = :transfer_seconds,
                TRANSFER_RESULT = :transfer_result,
                CALL_LOG_URL = :call_log_url,
                UPDATED_AT = SYSDATE
            WHERE ACTIVITY_ID = :activity_id
        ";

        $updatedRows = executeOracleActivityStatement($connection, $updateSql, $row);

        if ($updatedRows === 0)
        {
            // language=SQL
            $insertSql = "
                INSERT INTO PBI_ABITURIENT.CRM_ACTIVITY (
                    ACTIVITY_ID,
                    CONTACT_ID,
                    ACTIVITY_CREATED_AT,
                    TYPE_ID,
                    PROVIDER_ID,
                    PROVIDER_TYPE_ID,
                    DIRECTION,
                    SUBJECT,
                    RECOGNITION_STATUS,
                    SUMMARY,
                    FIRST_OPERATOR,
                    LAST_OPERATOR,
                    RECORD_SECONDS,
                    SECONDS_TO_FIRST_ANSWER,
                    TRANSFER_SECONDS,
                    TRANSFER_RESULT,
                    CALL_LOG_URL,
                    CREATED_AT,
                    UPDATED_AT
                )
                VALUES (
                    :activity_id,
                    :contact_id,
                    TO_DATE(:activity_created_at, 'DD.MM.YYYY HH24:MI:SS'),
                    :type_id,
                    :provider_id,
                    :provider_type_id,
                    :direction,
                    :subject,
                    :recognition_status,
                    {$insertSummaryValue},
                    :first_operator,
                    :last_operator,
                    :record_seconds,
                    :seconds_to_first_answer,
                    :transfer_seconds,
                    :transfer_result,
                    :call_log_url,
                    SYSDATE,
                    SYSDATE
                )
            ";

            executeOracleActivityStatement($connection, $insertSql, $row);
        }

        replaceOracleActivityRoute($connection, (int)$row['ACTIVITY_ID'], $routeItems);
        if (!$isPendingRecognition)
        {
            replaceOracleActivityQuestions($connection, (int)$row['ACTIVITY_ID'], is_array($payload['questions'] ?? null) ? $payload['questions'] : array());
        }

        if (!oci_commit($connection))
        {
            throw new RuntimeException(getOracleErrorMessage($connection));
        }
    }
    catch (Throwable $throwable)
    {
        oci_rollback($connection);
        oci_close($connection);

        throw $throwable;
    }

    oci_close($connection);

    return true;
}

function saveOracleRecognitionResult(array $config, array $payload, int $contactId): ?bool
{
    $oracle = is_array($config['oracle'] ?? null) ? $config['oracle'] : array();

    if (($oracle['enabled'] ?? false) !== true)
    {
        return false;
    }

    if (!extension_loaded('oci8'))
    {
        throw new RuntimeException('OCI8 extension is not loaded');
    }

    $username = trim((string)($oracle['username'] ?? ''));
    $password = (string)($oracle['password'] ?? '');
    $connectionString = trim((string)($oracle['connectionString'] ?? ''));
    $charset = trim((string)($oracle['charset'] ?? 'AL32UTF8'));

    if ($username === '' || $password === '' || $connectionString === '')
    {
        throw new RuntimeException('Oracle config is incomplete');
    }

    $activityId = $payload['call_id'] ?? null;
    if (!is_int($activityId) || $activityId <= 0)
    {
        throw new InvalidArgumentException('call_id must be a positive integer');
    }
    if ($contactId <= 0)
    {
        throw new InvalidArgumentException('Contact ID must be a positive integer');
    }

    $connection = oci_connect($username, $password, $connectionString, $charset);

    if (!$connection)
    {
        throw new RuntimeException(getOracleErrorMessage());
    }

    $statement = null;
    $summaryDescriptor = null;

    try
    {
        $recognitionStatus = trim((string)($payload['call_status'] ?? ''));
        $summary = (string)($payload['summary'] ?? '');
        $summaryValue = $summary === '' ? 'EMPTY_CLOB()' : ':summary';

        // language=SQL
        $sql = "
            UPDATE PBI_ABITURIENT.CRM_ACTIVITY
            SET
                CONTACT_ID = :contact_id,
                RECOGNITION_STATUS = :recognition_status,
                SUMMARY = {$summaryValue},
                UPDATED_AT = SYSDATE
            WHERE ACTIVITY_ID = :activity_id
        ";
        $statement = oci_parse($connection, $sql);

        if (!$statement)
        {
            throw new RuntimeException(getOracleErrorMessage($connection));
        }

        if ($summary !== '')
        {
            $summaryDescriptor = oci_new_descriptor($connection, OCI_D_LOB);

            if (!$summaryDescriptor || !$summaryDescriptor->writeTemporary($summary, OCI_TEMP_CLOB))
            {
                throw new RuntimeException(getOracleErrorMessage($connection));
            }
        }

        oci_bind_by_name($statement, ':activity_id', $activityId);
        oci_bind_by_name($statement, ':contact_id', $contactId);
        oci_bind_by_name($statement, ':recognition_status', $recognitionStatus, 50);
        if ($summaryDescriptor)
        {
            oci_bind_by_name($statement, ':summary', $summaryDescriptor, -1, OCI_B_CLOB);
        }

        if (!oci_execute($statement, OCI_NO_AUTO_COMMIT))
        {
            throw new RuntimeException(getOracleErrorMessage($statement));
        }

        if (oci_num_rows($statement) === 0)
        {
            oci_rollback($connection);
            return null;
        }

        replaceOracleActivityQuestions(
            $connection,
            $activityId,
            is_array($payload['questions'] ?? null) ? $payload['questions'] : array()
        );

        if (!oci_commit($connection))
        {
            throw new RuntimeException(getOracleErrorMessage($connection));
        }
    }
    catch (Throwable $throwable)
    {
        oci_rollback($connection);
        throw $throwable;
    }
    finally
    {
        if ($summaryDescriptor)
        {
            $summaryDescriptor->free();
        }

        if ($statement)
        {
            oci_free_statement($statement);
        }

        oci_close($connection);
    }

    return true;
}
