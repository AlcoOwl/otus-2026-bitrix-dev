<?php

namespace Otus;

class Debug extends \Bitrix\Main\Diag\Debug
{
    protected static $level = 'WARNING';
    public static function writeToLog($data, $title = ''): bool
    {
        if (!defined('DEBUG_FILE_NAME') || DEBUG_FILE_NAME === '')
            return false;
        $log = "\n--------------------";
        $log .= date('Y-m-d H:i:s') . "\n";
        $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
        $log .= print_r($data, 1);
        $log .= "\n--------------------\n";
        file_put_contents(DEBUG_FILE_NAME, $log, FILE_APPEND);
        return true;
    }
    public static function cleanLog(string $fileName = 'log'): void {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs' . $fileName . '.log';
        file_put_contents($logFile, '');
    }
}
