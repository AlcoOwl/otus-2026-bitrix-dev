<?php

namespace Airecogn\Service;

use Airecogn\Config;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

final class Logger
{
    public const CHANNEL_END_CALL = 'end_call';
    public const CHANNEL_INBOUND = 'inbound';
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    public static function write(string $channel, array $payload, string $level = 'info'): void
    {
        if (!Config::isLoggingEnabled() || !self::acceptLevel($level))
        {
            return;
        }

        $directory = Application::getDocumentRoot() . '/local/logs/airecogn';
        Directory::createDirectory($directory);

        unset($payload['contact_id'], $payload['CONTACT_ID']);
        $activityId = $payload['activity_id'] ?? null;
        if ($activityId !== null && (!is_int($activityId) || $activityId <= 0))
        {
            throw new \InvalidArgumentException('activity_id must be a positive integer');
        }
        $contactId = 0;
        if ($activityId !== null)
        {
            try
            {
                $contactId = ContactResolver::resolveByActivityId($activityId);
            }
            catch (\Throwable)
            {
                // A logging failure must not interrupt call processing.
            }
        }

        $record = [
            'timestamp' => date('c'),
            'level' => $level,
            'channel' => $channel,
        ];
        if ($contactId > 0)
        {
            $record['contact_id'] = $contactId;
        }
        $record += $payload;

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if (strlen($line) > self::MAX_FILE_SIZE)
        {
            $line = json_encode([
                'timestamp' => $record['timestamp'],
                'level' => 'error',
                'channel' => $channel,
                'message' => 'Log record was omitted because it exceeds the 5 MiB file limit.',
                'original_size' => strlen($line),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }

        self::appendWithRotation($channel, $line);
    }

    public static function tail(string $channel, ?int $limit = null): array
    {
        $limit = $limit ?? Config::getLogTailSize();
        if ($limit <= 0)
        {
            throw new \InvalidArgumentException('Log tail limit must be positive');
        }

        $records = [];
        foreach (self::getLogFiles($channel) as $path)
        {
            foreach (self::readLastLines($path, $limit - count($records)) as $line)
            {
                $decoded = json_decode($line, true);
                $records[] = is_array($decoded) ? $decoded : ['message' => $line];
            }

            if (count($records) >= $limit)
            {
                break;
            }
        }

        return $records;
    }

    public static function getLogFilePath(string $channel, ?string $date = null): string
    {
        $date = $date ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
        {
            throw new \InvalidArgumentException('Log date must use YYYY-MM-DD format');
        }

        return Application::getDocumentRoot()
            . '/local/logs/airecogn/'
            . self::normalizeChannel($channel)
            . '_'
            . $date
            . '.log';
    }

    private static function acceptLevel(string $level): bool
    {
        $weights = ['debug' => 10, 'info' => 20, 'error' => 30];
        if (!isset($weights[$level]))
        {
            throw new \InvalidArgumentException('Unknown log level: ' . $level);
        }

        $configuredLevel = Config::getLogLevel();
        if (!isset($weights[$configuredLevel]))
        {
            throw new \UnexpectedValueException('Invalid configured log level: ' . $configuredLevel);
        }

        return $weights[$level] >= $weights[$configuredLevel];
    }

    private static function normalizeChannel(string $channel): string
    {
        if (!in_array($channel, [self::CHANNEL_END_CALL, self::CHANNEL_INBOUND], true))
        {
            throw new \InvalidArgumentException('Unknown log channel: ' . $channel);
        }

        return $channel;
    }

    private static function appendWithRotation(string $channel, string $line): void
    {
        $basePath = self::getLogFilePath($channel);
        $lock = fopen(dirname($basePath) . '/.' . self::normalizeChannel($channel) . '.lock', 'c');
        if ($lock === false)
        {
            return;
        }

        try
        {
            if (!flock($lock, LOCK_EX))
            {
                return;
            }

            $path = self::findWritablePath($basePath, strlen($line));
            file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        }
        finally
        {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function findWritablePath(string $basePath, int $recordSize): string
    {
        for ($part = 0; ; $part++)
        {
            $path = $part === 0
                ? $basePath
                : mb_substr($basePath, 0, -4) . '_' . str_pad((string)$part, 3, '0', STR_PAD_LEFT) . '.log';

            clearstatcache(true, $path);
            $size = is_file($path) ? (int)filesize($path) : 0;
            if ($size + $recordSize <= self::MAX_FILE_SIZE)
            {
                return $path;
            }
        }
    }

    private static function getLogFiles(string $channel): array
    {
        $directory = Application::getDocumentRoot() . '/local/logs/airecogn';
        $channel = self::normalizeChannel($channel);
        $files = array_merge(
            glob($directory . '/' . $channel . '_????-??-??.log') ?: [],
            glob($directory . '/' . $channel . '_????-??-??_*.log') ?: []
        );

        $legacyFile = $directory . '/' . $channel . '.log';
        if (is_file($legacyFile))
        {
            $files[] = $legacyFile;
        }

        $files = array_values(array_unique($files));
        usort($files, static function (string $left, string $right): int {
            $byTime = (filemtime($right) ?: 0) <=> (filemtime($left) ?: 0);
            return $byTime !== 0 ? $byTime : strnatcmp($right, $left);
        });

        return $files;
    }

    private static function readLastLines(string $path, int $limit): array
    {
        if ($limit <= 0 || !is_file($path) || !is_readable($path))
        {
            return [];
        }

        $file = fopen($path, 'rb');
        if ($file === false)
        {
            return [];
        }

        $lines = [];
        $buffer = '';
        $blockSize = 65536;

        try
        {
            flock($file, LOCK_SH);
            fseek($file, 0, SEEK_END);
            $position = ftell($file);

            while ($position > 0 && count($lines) < $limit)
            {
                $readSize = min($blockSize, $position);
                $position -= $readSize;
                fseek($file, $position);
                $buffer = (string)fread($file, $readSize) . $buffer;

                $parts = explode("\n", $buffer);
                $buffer = array_shift($parts);
                for ($index = count($parts) - 1; $index >= 0 && count($lines) < $limit; $index--)
                {
                    $line = trim($parts[$index]);
                    if ($line !== '')
                    {
                        $lines[] = $line;
                    }
                }
            }

            $buffer = trim($buffer);
            if ($buffer !== '' && count($lines) < $limit)
            {
                $lines[] = $buffer;
            }
        }
        finally
        {
            flock($file, LOCK_UN);
            fclose($file);
        }

        return $lines;
    }
}
