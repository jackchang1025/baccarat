<?php

namespace App\Baccarat\Listener\WebSocket;

use App\Baccarat\Event\WebSocket\HallLoginEvent;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class HallLoginLister implements ListenerInterface
{


    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [HallLoginEvent::class];
    }

    public function process(object $event): void
    {
        $config = $this->container->get(ConfigInterface::class);

        /** @var HallLoginEvent $event */
        $handLoginData = $config->get('websocket.handLogin');
        $event->connection->push($handLoginData);
        $event->connection->setIsLoggedIn(true);
    }
}