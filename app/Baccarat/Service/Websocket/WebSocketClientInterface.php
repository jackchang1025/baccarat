<?php

namespace App\Baccarat\Service\Websocket;

use Hyperf\Contract\ConnectionInterface;
use Psr\Container\ContainerInterface;

interface WebSocketClientInterface extends ConnectionInterface
{
    public function isTimeout():bool;

    public function getCreatedAt():int;

    public function getRemainingTimeOut():int;

    public function getMessage(): array;

    public function isOnUpdateGameInfo(): bool;
}