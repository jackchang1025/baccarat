<?php

declare(strict_types=1);

return [
    'host' => 'wss://fx8ec8.3l3b0um9.com/fxLive/fxLB?gameType=h5multi3',
    'token' => 'bga6bae4ded83e29aeca77c7c7a9c5a7bad228d878',
    'connectionTimeout' => 600,
    'remainingTimeOut' => 10,
    'handLogin' =>
        ["dev" =>
            ["rd" => "fx",
                "ua" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
                "os" => "Windows 10",
                "srs" => "2560x1440",
                "wrs" => "1440x900",
                "dpr" => 1,
                "pl" => "H5",
                "pf" => "Chrome 122.0.0.0",
                "wv" => "false",
                "aio" => false,
                "vga" => "ANGLE (AMD, AMD Radeon R5 340 (0x00006611) Direct3D11 vs_5_0 ps_5_0, D3D11)",
                "tablet" => false,
                "cts" => 1710668957844,
                "mua" => "",
                "dtp" => "",
                "newaio" => "",
                "ub" => "",
                "pwa" => false,
                "ui" => 16
            ],
            "lang" => "cn",
            "vType" => "wss",
            "vtMode" => true,
            "subscription" => ["shit"],
            "action" => "login",
            "sid" => 'bga6bae4ded83e29aeca77c7c7a9c5a7bad228d878'
        ],
    'login' => ['lang' => 'cn', 'vType' => 'wss', 'site' => '1', 'sid' => 'bga6bae4ded83e29aeca77c7c7a9c5a7bad228d878', 'action' => 'hallLogin',],
    'connectionPool' => [
        'min_connections' => 3,
        'max_connections' => 5,
        'connect_timeout' => 10.0
    ],
    'messageChannel' => [
        'size' => 1000
    ]
];

