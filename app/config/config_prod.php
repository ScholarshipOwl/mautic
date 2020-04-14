<?php

$loader->import('config.php');

if (file_exists(__DIR__.'/security_local.php')) {
    $loader->import('security_local.php');
} else {
    $loader->import('security.php');
}

// Setup memcache as the session storage
$memcacheHost = $container->hasParameter('mautic.memcache_host') ? $container->getParameter('mautic.memcache_host') : null;
$memcachePort = $container->hasParameter('mautic.memcache_port') ? $container->getParameter('mautic.memcache_port') : null;

$container->loadFromExtension('framework', [
  'session' => [
    'handler_id' => 'session.handler.memcached'
  ],
  'validation' => [
    'cache' => 'apc'
  ]
]);

$container->loadFromExtension('services', [
  'session.memcached' => [
    'class' => 'Memcached',
    'calls' => [ 'addServer', [ $memcacheHost, $memcachePort ]],
  ],

  'session.handler.memcached' => [
    'class' => Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler,
    'arguments' => [
      '@session.memcached',
      [
        'prefix' => 'sess_',
        'expiretime' => 1440,
      ],
    ],
  ],
]);

// Setup memcache as the ORM storage
$container->loadFromExtension('doctrine', [
  'orm' => [
    'metadata_cache_driver' => [
      'type' => 'memcached',
      'host' => $memcacheHost,
      'port' => $memcachePort,
      'instance_class' => 'Memcached',
    ],
    'result_cache_driver'   => [
      'type' => 'memcached',
      'host' => $memcacheHost,
      'port' => $memcachePort,
      'instance_class' => 'Memcached',
    ],
    'query_cache_driver'    => [
      'type' => 'memcached',
      'host' => $memcacheHost,
      'port' => $memcachePort,
      'instance_class' => 'Memcached',
    ],
  ]
]);

// Add support for slave reading
$dbHostRO = $container->hasParameter('mautic.db_host_ro') ? $container->getParameter('mautic.db_host_ro') : null;
$dbPortRO = $container->hasParameter('mautic.db_port_ro') ? $container->getParameter('mautic.db_port_ro') : null;

if (!empty($dbHostRO)) {
  // Default from config.php
  $dbalSettings = [
    'driver'   => '%mautic.db_driver%',
    'host'     => '%mautic.db_host%',
    'port'     => '%mautic.db_port%',
    'dbname'   => '%mautic.db_name%',
    'user'     => '%mautic.db_user%',
    'password' => '%mautic.db_password%',
    'charset'  => 'UTF8',
    'types'    => [
      'array'    => 'Mautic\CoreBundle\Doctrine\Type\ArrayType',
      'datetime' => 'Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType',
    ],
    // Prevent Doctrine from crapping out with "unsupported type" errors due to it examining all tables in the database and not just Mautic's
    'mapping_types' => [
      'enum'  => 'string',
      'point' => 'string',
      'bit'   => 'string',
    ],
    'server_version' => '%mautic.db_server_version%',
  ];

  // Add a slave slave
  $dbalSettings['keep_slave'] = true;
  $dbalSettings['slaves'] = [
    'slave1' => [
      'host'     => $dbHostRO,
      'port'     => $dbPortRO,
      'dbname'   => '%mautic.db_name%',
      'user'     => '%mautic.db_user%',
      'password' => '%mautic.db_password%',
      'charset'  => 'UTF8',
    ]
  ];

  $container->loadFromExtension('doctrine', [
    'dbal' => $dbalSettings
  ]);
}

$debugMode = $container->hasParameter('mautic.debug') ? $container->getParameter('mautic.debug') : $container->getParameter('kernel.debug');

$container->loadFromExtension('monolog', [
    'channels' => [
        'mautic',
    ],
    'handlers' => [
        'main' => [
            'formatter'    => $debugMode ? 'mautic.monolog.fulltrace.formatter' : null,
            'type'         => 'fingers_crossed',
            'buffer_size'  => '200',
            'action_level' => ($debugMode) ? 'debug' : 'error',
            'handler'      => 'nested',
            'channels'     => [
                '!mautic',
            ],
        ],
        'nested' => [
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/%kernel.environment%.php',
            'level'     => ($debugMode) ? 'debug' : 'error',
            'max_files' => 7,
        ],
        'mautic' => [
            'formatter' => $debugMode ? 'mautic.monolog.fulltrace.formatter' : null,
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/mautic_%kernel.environment%.php',
            'level'     => ($debugMode) ? 'debug' : 'notice',
            'channels'  => [
                'mautic',
            ],
            'max_files' => 7,
        ],
    ],
]);

//Twig Configuration
$container->loadFromExtension('twig', [
    'cache'       => '%mautic.tmp_path%/%kernel.environment%/twig',
    'auto_reload' => true,
]);

// Allow overriding config without a requiring a full bundle or hacks
if (file_exists(__DIR__.'/config_override.php')) {
    $loader->import('config_override.php');
}

// Allow local settings without committing to git such as swift mailer delivery address overrides
if (file_exists(__DIR__.'/config_local.php')) {
    $loader->import('config_local.php');
}
