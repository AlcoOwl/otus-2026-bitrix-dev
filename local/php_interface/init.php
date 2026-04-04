<?php
// global debug file
define('DEBUG_FILE_NAME', $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . 'otus_log' . '.log');

// composer
if (file_exists(__DIR__ . '/../../vendor/autoload.php'))
    require_once __DIR__ . '/../../vendor/autoload.php';