<?php

namespace App\Baccarat\Service\Websocket;

use Hyperf\WebSocketClient\Client;
use Hyperf\WebSocketClient\ClientFactory;

class ConnectionState
{
    private Client $client;
    private int $createdAt = 0;

    public function __construct(
        private readonly ClientFactory $clientFactory,
        private readonly string        $host,
    )
    {
        $this->reconnect();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function isTimeOut(int $connectionTimeout): bool
    {
        return time() - $this->getCreatedAt() >= $connectionTimeout;
    }

    public function close(): bool
    {
        return $this->client->close();
    }

    public function getRemainingTimeOut(int $connectionTimeout): int
    {
        return max(0, $connectionTimeout - (time() - $this->getCreatedAt()));
    }

    public function reconnect(): bool
    {
        $this->createdAt = time();
        $this->client = $this->clientFactory->create($this->host);

        return true;
    }
}