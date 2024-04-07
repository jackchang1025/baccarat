<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Service\Output\Output;
use Hyperf\Engine\Contract\ChannelInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\WebSocketClient\ClientFactory;
use Lysice\HyperfRedisLock\RedisLock;


class WebsocketClientFactory
{

    public function __construct(protected ChannelInterface $channel, protected string $host, protected string $token, protected int $connectionTimeout = 600)
    {
    }

    public function create(): WebsocketClientInterface
    {
        $redis = make(RedisFactory::class)->get('default');

        //使用时间戳避免锁值重复
        $redisLock = new RedisLock($redis, 'lock', 600);

        return make(WebsocketClient::class, [
            'clientFactory' => make(ClientFactory::class),
            'output' => make(Output::class),
            'redisLock' => $redisLock,
            'channel' => $this->channel,
            'host' => $this->host,
            'token' => $this->token,
            'connectionTimeout' => $this->connectionTimeout,
        ]);
    }
}