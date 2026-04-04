<?php
// global debug file
define('DEBUG_FILE_NAME', $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . date('Y-m-d') . '.log');

// composer
if (file_exists(__DIR__ . '/../../vendor/autoload.php'))
    require_once __DIR__ . '/../../vendor/autoload.php';