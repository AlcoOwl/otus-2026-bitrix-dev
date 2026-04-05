<?php

namespace Otus;

use Bitrix\Main\Diag\ExceptionHandlerFormatter;

class OtusExceptionLog extends \Bitrix\Main\Diag\FileExceptionHandlerLog
{
    const string DEFAULT_LOG_FILE = "local/logs/otus_exceptions.log";
    protected int $level = 0;

    /**
     * Инициализирует объект с заданными параметрами.
     *
     * @param array $options Ассоциативный массив параметров настройки. Может содержать ключ 'level',
     * где значение — положительное целое число для определения уровня.
     * @return void
     */
    public function initialize(array $options): void
    {
        parent::initialize($options);

        if (isset($options['level']) && $options['level'] > 0) {
            $this->level = (int)$options['level'];
        }
    }

    /**
     * Записывает информацию об исключении в лог с указанным типом.
     *
     * @param \Exception $exception Исключение, информация о котором будет записана в лог.
     * @param mixed $logType Тип лога, используемый для определения уровня логирования и формата сообщения.
     * @return void
     */
    public function write($exception, $logType): void
    {
        $text = ExceptionHandlerFormatter::format($exception, false, $this->level);

        $context = [
            'type' => static::logTypeToString($logType),
        ];

        $logLevel = static::logTypeToLevel($logType);

        $message = "OTUS - {date} - Host: {host} - {type} - {$text}\n";

        $this->logger->log($logLevel, $message, $context);
    }
}