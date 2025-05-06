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

    public function create(): WebsocketConnectionInterface
    {

        return make(WebsocketConnection::class, [
            'clientFactory' => make(ClientFactory::class),
            'host' => $this->host,
            'token' => $this->token,
            'connectionTimeout' => $this->connectionTimeout,
        ]);
    }
}