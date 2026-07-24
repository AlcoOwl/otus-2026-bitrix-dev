<?php

namespace Airecogn;

use Bitrix\Main\Config\Option;

final class Config
{
    public const MODULE_ID = 'alcoowl.airecogn';

    public static function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'enabled', 'Y') === 'Y';
    }

    public static function isLoggingEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'logging_enabled', 'Y') === 'Y';
    }

    public static function getLogLevel(): string
    {
        $level = Option::get(self::MODULE_ID, 'log_level', 'info');
        if (!in_array($level, ['debug', 'info', 'error'], true))
        {
            throw new \UnexpectedValueException('Invalid log_level option: ' . $level);
        }

        return $level;
    }

    public static function getLogTailSize(): int
    {
        $value = Option::get(self::MODULE_ID, 'log_tail_size', '200');
        if (!ctype_digit($value) || (int)$value < 10 || (int)$value > 2000)
        {
            throw new \UnexpectedValueException('log_tail_size must be an integer from 10 to 2000');
        }

        return (int)$value;
    }

    public static function getIntegrationConfig(): array
    {
        return [
            'nextcloud' => [
                'webdavUrl' => Option::get(self::MODULE_ID, 'nextcloud_webdav_url', ''),
                'username' => Option::get(self::MODULE_ID, 'nextcloud_username', ''),
                'password' => Option::get(self::MODULE_ID, 'nextcloud_password', ''),
                'remoteDir' => Option::get(self::MODULE_ID, 'nextcloud_remote_dir', '/Calls/{Y}/{m}'),
            ],
            'oracle' => [
                'enabled' => Option::get(self::MODULE_ID, 'oracle_enabled', 'N') === 'Y',
                'username' => Option::get(self::MODULE_ID, 'oracle_username', ''),
                'password' => Option::get(self::MODULE_ID, 'oracle_password', ''),
                'connectionString' => Option::get(self::MODULE_ID, 'oracle_connection_string', ''),
                'charset' => Option::get(self::MODULE_ID, 'oracle_charset', 'AL32UTF8'),
            ],
        ];
    }
}
