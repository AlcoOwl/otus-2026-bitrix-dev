<?php
return[
    'exception_handling' => [
        'value' => array(
            'debug' => true,
            'handled_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING & ~E_DEPRECATED &
                ~E_USER_DEPRECATED & ~E_USER_NOTICE,
            'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
            'ignore_silence' => false,
            'assertion_throws_exception' => true,
            'assertion_error_type' => 256,
            'log' => [
                'class_name' => \Otus\Debug::class,
            ]
        )
    ]
];