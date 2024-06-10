<?php

declare(strict_types=1);

return [
    'host' => 'wss://103.241.119.77/fxLive/fxLB?gameType=h5multi3',
//    'host' => 'wss://fx8ec8.3l3b0um9.com/fxLive/fxLB?gameType=h5multi3',
    'token' => 'bg2e5b7fb2e170c2639bded4f0d5dcb8ae81cbb2dc',
    //连接过期时间
    'connectionTimeout' => 600,
    //连接剩余多少时间重试
    'remainingTimeOut' => 30,
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
            "sid" => 'bg2e5b7fb2e170c2639bded4f0d5dcb8ae81cbb2dc'
        ],
    'login' => ['lang' => 'cn', 'vType' => 'wss', 'site' => '1', 'sid' => 'bg2e5b7fb2e170c2639bded4f0d5dcb8ae81cbb2dc', 'action' => 'hallLogin',],
    'connectionPool' => [
        'min_connections' => 3,
        'max_connections' => 5,
        'connect_timeout' => 10.0,
        //每个连接创建间隔时间
        'connection_creation_interval_time' => 300,
        //检查连接间隔时间
        'connection_check_interval_time' => 5,
    ],
    'messageChannel' => [
        'size' => 1000
    ]
];

