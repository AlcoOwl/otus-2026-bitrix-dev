<?php

namespace Otus;

class Debug extends \Bitrix\Main\Diag\Debug
{
    protected static $level = 'WARNING';

    /**
     * Записывает данные в лог-файл.
     *
     * @param mixed $data Данные для записи в лог. Если не указаны, в лог будет записана текущая дата и время.
     * @param string $title Заголовок записи в логе. По умолчанию "OTUS_DEBUG".
     * @param string $fileName Имя лог-файла. Если не указано, используется значение константы DEBUG_FILE_NAME или "otus_log".
     * @return bool Возвращает true при успешной записи в лог, иначе false.
     */
    public static function writeToLog($data = '', $title = 'OTUS_DEBUG', $fileName = ''): bool {
        if ($fileName == ''
            && defined('DEBUG_FILE_NAME')
            && DEBUG_FILE_NAME != '')
            $fileName = DEBUG_FILE_NAME;
        if ($fileName == '')
            $fileName = 'otus_log';
        $fileName = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . $fileName . '.log';
        if (!is_dir(dirname($fileName)))
            return false;
        if (!$data)
            $data = 'Now is: ' . date('Y-m-d H:i:s');
        $log = "\n-----";
        $log .= date('Y-m-d H:i:s') . "-----\n";
        $log .= $title . "\n";
        $log .= print_r($data, 1);
        $log .= "\n-----------------------------\n";
        file_put_contents($fileName, $log, FILE_APPEND);
        return true;
    }

    /**
     * Очищает содержимое указанного лог-файла.
     *
     * @param string $fileName Имя лог-файла для очистки. По умолчанию "otus_log".
     * @return bool Возвращает true при успешной очистке файла, иначе false.
     */
    public static function cleanLog(string $fileName = 'otus_log'): bool {
        $fileName = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . $fileName . '.log';
        if (!is_dir(dirname($fileName)))
            return false;
        file_put_contents($fileName, '');
        return true;
    }
}
