<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Mapper\BaccaratSimulatedBettingMapper;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Service\BaccaratBetting\BaccaratBetting;
use App\Baccarat\Service\BaccaratBetting\BaccaratBettingWaitingResult;
use App\Baccarat\Service\Exception\RoomException;
use App\Baccarat\Service\Exception\RuleMatchingException;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Redis\RedisLock;
use Hyperf\Event\Annotation\Listener;
use Lysice\HyperfRedisLock\LockTimeoutException;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class BettingListener implements ListenerInterface
{
    protected RedisLock $redisLock;
    public function __construct(
        protected ContainerInterface         $container,
        protected BaccaratSimulatedBettingMapper  $bettingMapper,
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
//        $this->bettingMapper->getModel()
//            ->with(['baccaratSimulatedBettingRule'])
//            ->where('status',1)
//            ->get()
//            ->each(fn(BaccaratSimulatedBetting $betting) => $this->placeBet($betting,$event->baccaratBettingWaitingResult));
    }

    public function placeBet(BaccaratSimulatedBetting $betting,BaccaratBettingWaitingResult $baccaratBettingWaitingResult): void
    {
        try {

            $this->redisLock = RedisLock::make("baccarat:betting:lock:{$betting->id}", 3);

            $this->redisLock->block(30, fn() => $this->container->make(BaccaratBetting::class, ['betting' => $betting])->placeBet($baccaratBettingWaitingResult));

        } catch (RuleMatchingException|RoomException|LockTimeoutException $exception) {

            $this->container->get(Output::class)->error($exception->getMessage());
            $this->container->get(LoggerFactory::class)->create()->error($exception);
        }
    }
}
