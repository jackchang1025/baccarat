<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Service\BaccaratService;
use App\Baccarat\Service\BaccaratSimulatedBettingService;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Lysice\HyperfRedisLock\RedisLock;
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
        $this->baccaratService->handleBetting($event->lotteryResult);
    }
}
