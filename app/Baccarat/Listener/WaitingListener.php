<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Event\WaitingEvent;
use App\Baccarat\Service\BaccaratWaiting\BaccaratWaiting;
use App\Baccarat\Service\LoggerFactory;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

#[Listener(priority:9999)]
class WaitingListener implements ListenerInterface
{
    public function __construct(
        protected ContainerInterface         $container,
        protected BaccaratWaiting  $waiting
    )
    {
    }

    public function listen(): array
    {
        return [
            WaitingEvent::class,
        ];
    }

    public function process(object $event): void
    {
        /**
         * @var WaitingEvent $event
         */

        try {
            $this->waiting->handleWaiting($event->baccaratBettingWaitingResult);


        } catch (\Exception $e) {
            $this->container->get(LoggerFactory::class)->create()->error($e);
        }
    }
}
