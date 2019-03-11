<?php
$serverHost = \Yaconf::get('syserver.base.server.host');

return [
    0 => [
        'module_name' => 'a01api',
        'listens' => [
            0 => [
                'host' => $serverHost,
                'port' => 7100,
            ],
        ],
    ],
];