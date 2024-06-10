<?php

namespace App\Baccarat\Service\Websocket\MessageHandler;

use App\Baccarat\Event\WebSocket\HallLoginEvent;
use App\Baccarat\Event\WebSocket\PingEvent;
use App\Baccarat\Event\WebSocket\ReadyEvent;
use App\Baccarat\Event\WebSocket\RecvMessageEvent;
use App\Baccarat\Event\WebSocket\UpdateGameInfoEvent;
use App\Baccarat\Service\Websocket\Connection;
use App\Baccarat\Service\Websocket\Message\Message;
use Psr\EventDispatcher\EventDispatcherInterface;

class MessageHandler implements MessageHandlerInterface
{

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    public function handle(Connection $connection, Message $message): void
    {
        match (true) {
            $message->isPing() => $this->eventDispatcher->dispatch(new PingEvent($connection, $message)),
            $message->isReady() => $this->eventDispatcher->dispatch(new ReadyEvent($connection, $message)),
            $message->isOnHallLogin() => $this->eventDispatcher->dispatch(new HallLoginEvent($connection, $message)),
            $message->isOnUpdateGameInfo() => $this->eventDispatcher->dispatch(new UpdateGameInfoEvent($connection, $message)),
            default => $this->eventDispatcher->dispatch(new RecvMessageEvent($connection, $message))
        };
    }
}