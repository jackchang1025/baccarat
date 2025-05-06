<?php

use App\Baccarat\Notifications\HyperfTransport;
use App\Baccarat\Notifications\WeChatWorkChannel;

return [
    'wechat_work' => [
        'webhook_url' => 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=081cbd43-f174-4490-90e6-37ed67754519',
        'class' => WeChatWorkChannel::class,
        'transport' => HyperfTransport::class,
    ]
];