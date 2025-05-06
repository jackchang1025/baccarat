<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Service\BaccaratService;
use Hyperf\Event\Annotation\Listener;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class BettingListener implements ListenerInterface
{

    public function __construct(
        protected ContainerInterface         $container,
        protected BaccaratService  $baccaratService
    )
    {
    }

    public function listen(): array
    {
        return [
            BettingEvent::class,
        ];
    }

    public function process(object $event): void
    {
        /**
         * @var BettingEvent $event
         */
        // $this->baccaratService->handleBetting($event->lotteryResult);
    }
}
