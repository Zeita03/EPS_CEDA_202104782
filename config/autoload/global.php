<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */
return [
    // Session configuration.
    'session_config' => [
        // Session cookie will expire in 8 hours.
        'cookie_lifetime' => 60 * 60 * 8,
        // Session data will be stored on server maximum for 30 days.
        'gc_maxlifetime' => 60 * 60 * 24 * 30,
        // Remember me cookie will expire in 7 days.
        'remember_me_seconds' => 60 * 60 * 24 * 7, // 7 días para remember me
    ],
    // Session manager configuration.
    'session_manager' => [
        // Session validators (used for security).
        'validators' => [
            //\Laminas\Session\Validator\RemoteAddr::class,
            \Laminas\Session\Validator\HttpUserAgent::class,
        ]
    ],
    // Session storage configuration.
    'session_storage' => [
        'type' => Laminas\Session\Storage\SessionArrayStorage::class
    ],
    'db' => [
        'adapters' => [
            'WriteAdapter' => [
                'driver' => 'Pdo',
                'driver_options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
                ],
            ],
        ],
    ],
];
