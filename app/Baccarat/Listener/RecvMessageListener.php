<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Event\WaitingEvent;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use Carbon\Carbon;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Lysice\HyperfRedisLock\LockTimeoutException;
use Lysice\HyperfRedisLock\RedisLock;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

#[Listener]
class RecvMessageListener implements ListenerInterface
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    protected RedisProxy $redisProxy;

    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        protected ContainerInterface         $container,
        protected Output         $output,
        protected LoggerFactory $loggerFactory
    )
    {
        $this->redisProxy = make(RedisFactory::class)->get('default');
    }

    public function listen(): array
    {
        return [
            RecvMessageEvent::class,
        ];
    }

    public function process(object $event): void
    {
        /**
         * @var RecvMessageEvent $event
         */
        //$lotteryResult->status === 'waiting' && $lotteryResult->result && $lotteryResult->issue && $lotteryResult->isBaccarat()
        //$lotteryResult->terrace == '3001-80'

        $lotteryResult = $event->lotteryResult;

        if ($lotteryResult->terrace == '3001-80') {
            $this->output->info($lotteryResult);
        }

        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);

        if ($lotteryResult->isWaiting() || $lotteryResult->isBetting()){

            //使用时间戳避免锁值重复
            $redisLock = new RedisLock($this->redisProxy, "recv_message_lock_{$lotteryResult->status}_{$lotteryResult->issue}", 1);

            try {

                $redisLock->block(5, fn() => $this->dispatch($lotteryResult));

            } catch (LockTimeoutException $e) {

                $this->output->info("recv_message_lock_{$lotteryResult->status}_{$lotteryResult->issue} lock timeout");
            }
        }
    }

    public function dispatch(LotteryResult $lotteryResult): void
    {
        match (true){
            $lotteryResult->isWaiting() => $this->eventDispatcher ->dispatch(new WaitingEvent($lotteryResult)),
            $lotteryResult->isBetting() => $this->eventDispatcher ->dispatch(new BettingEvent($lotteryResult)),
            default => null,
        };
    }
}
