<?php

namespace App\Baccarat\Listener\WebSocket;

use App\Baccarat\Event\WebSocket\HallLoginEvent;
use App\Baccarat\Event\WebSocket\PingEvent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class PingLister implements ListenerInterface
{

    public function listen(): array
    {
        return [PingEvent::class];
    }

    public function process(object $event): void
    {
        /** @var PingEvent $event */
        $event->connection->push($event->message->toArray());
    }
}