<?php

namespace App\Baccarat\Service\Websocket\MessageHandler;

use App\Baccarat\Service\Websocket\Connection;
use App\Baccarat\Service\Websocket\Message\Message;

interface MessageHandlerInterface
{
    public function handle(Connection $connection,Message $message): void;
}