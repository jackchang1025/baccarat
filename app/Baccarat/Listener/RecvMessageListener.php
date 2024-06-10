<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Event\WaitingEvent;
use App\Baccarat\Mapper\BaccaratLotteryLogMapper;
use App\Baccarat\Mapper\BaccaratTerraceDeckMapper;
use App\Baccarat\Mapper\BaccaratTerraceMapper;
use App\Baccarat\Model\BaccaratTerraceDeck as BaccaratTerraceDeckModel;
use App\Baccarat\Service\BaccaratBetting\BaccaratBettingWaitingResult;
use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Service\Locker\LockerFactory;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Event\Annotation\Listener;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

#[Listener(priority: 99999)]
class RecvMessageListener implements ListenerInterface
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    protected ?EventDispatcherInterface $eventDispatcher = null;



    public function __construct(
        protected ContainerInterface         $container,
        protected Output                     $output,
        protected LoggerFactory              $loggerFactory,
        protected BaccaratTerraceMapper     $baccaratTerraceMapper,
        protected BaccaratTerraceDeckMapper $baccaratTerraceDeckMapper,
        protected BaccaratLotteryLogService  $baccaratLotteryLogService,
        protected BaccaratLotteryLogMapper   $lotteryLogMapper,
        protected LockerFactory              $lockerFactory,
    )
    {
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
        $this->eventDispatcher ??= $this->container->get(EventDispatcherInterface::class);

        $this->getLogger($lotteryResult->terrace)->info((string)$lotteryResult);

        if (!$lotteryResult->getDeckNumber() || !$lotteryResult->issue) {
            return;
        }

        //根据 code 获取台号且不存在就创建
        $baccaratTerrace = $this->baccaratTerraceMapper->getBaccaratTerraceOrCreateByCode($lotteryResult->terrace);

        //根据台号获取今日台靴不存在则创建
        // 如果是 20 局及以上，且是凌晨 0 到 1 点，则取昨天的数据 否则获取今天的数据
        $baccaratTerraceDeck = $this->baccaratTerraceDeckMapper->getBaccaratTerraceDeckOfTodayAndYesterdayOrCreates($baccaratTerrace->id, $lotteryResult->getDeckNumber());
        if (is_null($baccaratTerraceDeck)){
            return;
        }

        //使用内存锁在高并发时防止数据重复
        $locker = $this->lockerFactory->get("recvMessage:{$lotteryResult->issue}");

        if ($lotteryResult->isBetting()) {

            $locker->block(5, function () use ($lotteryResult, $baccaratTerraceDeck) {
                $baccaratBettingWaitingResult = $this->handleBetting($lotteryResult, $baccaratTerraceDeck);
                $this->eventDispatcher->dispatch(new BettingEvent($baccaratBettingWaitingResult));
            });
            return;
        }

        if ($lotteryResult->isWaiting()) {

            $locker->block(5, function () use ($lotteryResult, $baccaratTerraceDeck) {
                $baccaratBettingWaitingResult = $this->handleWaiting($lotteryResult, $baccaratTerraceDeck);
                $this->eventDispatcher->dispatch(new WaitingEvent($baccaratBettingWaitingResult));
            });
        }

    }

    protected function getLogger(string $terrace): LoggerInterface
    {
        return $this->logger ??= $this->loggerFactory->create($terrace);
    }

    /**
     * 处理投注
     * @param LotteryResult $lotteryResult
     * @param BaccaratTerraceDeckModel $baccaratTerraceDeck
     * @return BaccaratBettingWaitingResult
     */
    public function handleBetting(LotteryResult $lotteryResult, BaccaratTerraceDeckModel $baccaratTerraceDeck): BaccaratBettingWaitingResult
    {
        //根据期号获取开奖日志不存在则创建
        $lotteryLog = $this->lotteryLogMapper->firstOrCreate(
            attributes: [
                'issue'           => $lotteryResult->issue,
                'terrace_deck_id' => $baccaratTerraceDeck->id
            ],
            date: $baccaratTerraceDeck->created_at
        );

        return BaccaratBettingWaitingResult::fromLotteryResult(lotteryResult: $lotteryResult, lotteryLog: $lotteryLog, deck: $baccaratTerraceDeck);
    }

    /**
     * 处理开奖
     * @param LotteryResult $lotteryResult
     * @param BaccaratTerraceDeckModel $baccaratTerraceDeck
     * @return BaccaratBettingWaitingResult
     */
    public function handleWaiting(LotteryResult $lotteryResult, BaccaratTerraceDeckModel $baccaratTerraceDeck): BaccaratBettingWaitingResult
    {
        // 睡眠 0.5 秒
        Coroutine::sleep(0.5);

        //更新开奖日志
        $lotteryLog = $this->lotteryLogMapper->updateOrCreate(
            attributes: [
                'issue'           => $lotteryResult->issue,
                'terrace_deck_id' => $baccaratTerraceDeck->id
            ],
            data: [
                'result'               => $lotteryResult->result,
                'transformationResult' => $lotteryResult->getTransformationResult(),
                'RawData'              => $lotteryResult->data,
            ], date: $baccaratTerraceDeck->created_at);

        return BaccaratBettingWaitingResult::fromLotteryResult(lotteryResult: $lotteryResult, lotteryLog: $lotteryLog, deck: $baccaratTerraceDeck);
    }
}
