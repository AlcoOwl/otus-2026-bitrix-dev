<?php

namespace Airecogn\Http;

use Airecogn\Service\ContactResolver;
use Airecogn\Service\Logger;
use Airecogn\Service\RecognitionResultRepository;
use InvalidArgumentException;
use Throwable;

final class InboundHandler
{
    public static function run(): void
    {
        require_once dirname(__DIR__) . '/legacy/functions.php';
        self::registerErrorHandlers();

        try
        {
            $payload = self::readJsonPayload();
            $config = \loadInboundConfig();
        }
        catch (InvalidArgumentException $exception)
        {
            self::writeError('Invalid request', [
                'message' => $exception->getMessage(),
            ]);
            self::sendJsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 400);
            return;
        }

        try
        {
            $result = self::process($payload, $config);
        }
        catch (Throwable $throwable)
        {
            self::writeError('CRM activity update exception', [
                'activity_id' => $payload['call_id'],
                'exception' => get_class($throwable),
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);
            self::sendJsonResponse([
                'status' => 'error',
                'message' => $throwable->getMessage(),
            ], 500);
            return;
        }

        self::writeLog([
            'receivedAt' => date('Y-m-d H:i:s'),
            'activity_id' => $result['activity_id'],
            'CREATED' => $result['activity']['CREATED'] ?? null,
            'SUBJECT' => $result['activity']['SUBJECT'] ?? null,
            'TYPE' => 'activity_processed',
            'call_status' => $payload['call_status'],
            'activity_completed' => $result['activity_completed'],
        ]);
        self::sendJsonResponse([
            'status' => 'ok',
            'updated' => true,
        ]);
    }

    private static function process(array $payload, array $config): array
    {
        $activityId = $payload['call_id'];
        $activity = \getCrmActivity($activityId);
        $contactId = ContactResolver::resolveByActivityId($activityId, $activity);
        $activityCompleted = false;
        $description = \replacePendingRecognitionBlock((string)($activity['DESCRIPTION'] ?? ''), $payload);
        $callLogInfo = null;

        if ($description === null)
        {
            try
            {
                $callLogInfo = \buildCallLogInfo($activityId, $activity);
            }
            catch (Throwable $throwable)
            {
                self::writeError('Transfer route parse exception', [
                    'activity_id' => $activityId,
                    'CREATED' => $activity['CREATED'] ?? null,
                    'SUBJECT' => $activity['SUBJECT'] ?? null,
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                ]);
            }

            $callLogInfo = is_array($callLogInfo) ? $callLogInfo : [];
            $description = \buildActivityDescription(
                $payload,
                $activity,
                $callLogInfo['transferRoute'] ?? [],
                (string)($callLogInfo['transferResult'] ?? ''),
                $callLogInfo['callMetrics'] ?? [],
                $callLogInfo['callUsers'] ?? []
            );
        }

        \updateCrmActivityDescription($activityId, $description);
        RecognitionResultRepository::saveInbound($payload);

        try
        {
            $recognitionSaved = \saveOracleRecognitionResult($config, $payload, $contactId);
            if ($recognitionSaved === null)
            {
                if (!is_array($callLogInfo))
                {
                    $callLogInfo = \buildCallLogInfo($activityId, $activity);
                }

                \saveOracleActivity(
                    $config,
                    $payload,
                    $activity,
                    (string)($callLogInfo['transferResult'] ?? ''),
                    $callLogInfo['callMetrics'] ?? [],
                    $callLogInfo['callUsers'] ?? [],
                    (string)($callLogInfo['callLogUrl'] ?? ''),
                    $callLogInfo['oracleRouteItems'] ?? []
                );
            }
        }
        catch (Throwable $throwable)
        {
            self::writeError('Oracle activity save exception', [
                'activity_id' => $activityId,
                'CREATED' => $activity['CREATED'] ?? null,
                'SUBJECT' => $activity['SUBJECT'] ?? null,
                'exception' => get_class($throwable),
                'message' => $throwable->getMessage(),
            ]);
        }

        if ($payload['call_status'] === 'success')
        {
            $activityCompleted = \completeCrmActivity($activityId);
        }

        return [
            'activity_id' => $activityId,
            'activity' => $activity,
            'activity_completed' => $activityCompleted,
        ];
    }

    private static function readJsonPayload(): array
    {
        $rawBody = file_get_contents('php://input');
        if (!is_string($rawBody) || trim($rawBody) === '')
        {
            throw new InvalidArgumentException('Request body is empty');
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload))
        {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        if (array_is_list($payload))
        {
            throw new InvalidArgumentException('Request body must be a JSON object');
        }

        $payload['call_id'] = self::requirePositiveInteger($payload['call_id'] ?? null, 'call_id');
        if (!isset($payload['call_status'])
            || !is_string($payload['call_status'])
            || !in_array($payload['call_status'], ['success', 'error'], true))
        {
            throw new InvalidArgumentException('call_status must be success or error');
        }
        if (!isset($payload['summary']) || !is_string($payload['summary']))
        {
            throw new InvalidArgumentException('summary must be a string');
        }
        if (isset($payload['questions'])
            && (!is_array($payload['questions']) || !array_is_list($payload['questions'])))
        {
            throw new InvalidArgumentException('questions must be an array');
        }

        foreach ($payload['questions'] ?? [] as $index => $question)
        {
            if (!is_array($question)
                || !isset($question['id'], $question['text'], $question['answer'])
                || !is_string($question['text'])
                || !is_string($question['answer'])
                || !in_array($question['answer'], ['true', 'false'], true))
            {
                throw new InvalidArgumentException('Each question must contain id, string text and true/false answer');
            }

            $payload['questions'][$index]['id'] = self::requirePositiveInteger($question['id'], 'question.id');
        }

        return $payload;
    }

    private static function requirePositiveInteger($value, string $field): int
    {
        if (is_int($value) && $value > 0)
        {
            return $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', $value))
        {
            $digits = ltrim($value, '0');
            if ($digits !== '')
            {
                $integer = filter_var($digits, FILTER_VALIDATE_INT);
                if ($integer !== false)
                {
                    return $integer;
                }
            }
        }

        throw new InvalidArgumentException($field . ' must be a positive integer or digit string');
    }

    private static function registerErrorHandlers(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            self::writeError('PHP error', [
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
            ]);
            return false;
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error === null
                || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true))
            {
                return;
            }

            self::writeError('PHP fatal error', $error);
        });
    }

    private static function writeLog(array $payload): void
    {
        Logger::write(
            Logger::CHANNEL_INBOUND,
            $payload,
            ($payload['type'] ?? '') === 'error' ? 'error' : 'info'
        );
    }

    private static function writeError(string $message, array $context = []): void
    {
        $activityId = $context['activity_id'] ?? null;
        unset($context['activity_id']);

        $record = [
            'receivedAt' => date('Y-m-d H:i:s'),
            'type' => 'error',
            'message' => $message,
            'context' => $context,
        ];
        if (is_int($activityId) && $activityId > 0)
        {
            $record['activity_id'] = $activityId;
        }

        self::writeLog($record);
    }

    private static function sendJsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
