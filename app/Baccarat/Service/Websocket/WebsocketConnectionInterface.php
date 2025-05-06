<?php

namespace App\Baccarat\Service\Websocket;

use Hyperf\Contract\ConnectionInterface;
use Psr\Container\ContainerInterface;

interface WebsocketConnectionInterface extends ConnectionInterface
{

    public function recv(): mixed;

    public function retryRecvMessage(): array;

    public function isAuthenticated(): bool;

    public function isPing(): string|int|bool;

    public function push(array $data, int $opcode = WEBSOCKET_OPCODE_TEXT, ?int $flags = null): bool;

    public function isTimeout():bool;

    public function getCreatedAt():int;

    public function getRemainingTimeOut():int;

    public function getMessage(): array;

    public function isOnUpdateGameInfo(): bool;
}