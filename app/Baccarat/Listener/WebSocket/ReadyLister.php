<?php

namespace App\Baccarat\Listener\WebSocket;

use App\Baccarat\Event\WebSocket\HallLoginEvent;
use App\Baccarat\Event\WebSocket\ReadyEvent;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener]
class ReadyLister implements ListenerInterface
{

    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [ReadyEvent::class];
    }

    public function process(object $event): void
    {
        /** @var ReadyEvent $event */
        $config = $this->container->get(ConfigInterface::class);
        $loginData = $config->get('websocket.login');
        $event->connection->push($loginData);
    }
}