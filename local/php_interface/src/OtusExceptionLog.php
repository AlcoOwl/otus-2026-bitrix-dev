<?php

namespace Otus;

class OtusExceptionLog extends \Bitrix\Main\Diag\FileExceptionHandlerLog
{
    const string DEFAULT_LOG_FILE = "local/logs/otus_exceptions.log";

}