<?php

namespace App\Baccarat\Event\WebSocket;

use App\Baccarat\Service\Websocket\Connection;
use App\Baccarat\Service\Websocket\Message\Message;

class PingEvent
{

    public function __construct(public Connection $connection, public Message $message)
    {
    }
}