<?php

use Otus\Debug;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

Debug::cleanLog('otus_exceptions');

LocalRedirect('/local/homeworks/homework2/');
