<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Event\WaitingEvent;
use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Service\BaccaratService;
use App\Baccarat\Service\BaccaratSimulatedBettingService;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use App\Baccarat\Service\BaccaratTerraceService;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Event\Annotation\Listener;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class WaitingListener implements ListenerInterface
{
    public function __construct(
        protected BaccaratService  $baccaratService
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
         * @var BettingEvent $event
         */
        $this->baccaratService->handleWaiting($event->lotteryResult);
    }
}
