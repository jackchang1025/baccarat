<?php

namespace App\Baccarat\Service\BaccaratBetting;

use App\Baccarat\Event\HistoricalDataSimulatedBetting;
use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Mapper\BaccaratTerraceDeckMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratSimulatedBettingLogService;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use App\Baccarat\Service\Exception\RoomException;
use App\Baccarat\Service\Exception\RuleMatchingException;
use App\Baccarat\Service\Locker\LockerFactory;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Room\RoomManager;
use App\Baccarat\Service\Room\RoomMapper;
use App\Baccarat\Service\Rule\RuleInterface;
use Hyperf\Coroutine\Concurrent;
use Hyperf\Database\Model\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class BaccaratBetting
{
    public const LOCK_PREFIX = 'betting_lock';

    protected ?RoomManager $roomManager = null;

    protected ?CacheData $cacheData = null;
    protected Concurrent $concurrent;

    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        protected readonly ContainerInterface                 $container,
        protected readonly BaccaratSimulatedBettingLogService $bettingLogService,
        protected readonly BaccaratSimulatedBetting           $betting,
        protected readonly Output                             $output,
        protected readonly BaccaratTerraceDeckService         $terraceDeckService,
        protected readonly BaccaratBettingCache               $baccaratBettingCache,
        protected readonly BaccaratSimulatedBettingLogMapper  $bettingLogMapper,
        protected readonly BaccaratTerraceDeckMapper          $deckMapper,
        protected readonly LockerFactory                      $lockerFactory,
    )
    {
    }

    /**
     * @param BaccaratBettingWaitingResult $baccaratBettingWaitingResult
     * @return BaccaratSimulatedBettingLog|null
     * @throws RoomException
     * @throws RuleMatchingException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RedisException
     */
    public function placeBet(BaccaratBettingWaitingResult $baccaratBettingWaitingResult): ?BaccaratSimulatedBettingLog
    {
        //开奖消息
        $lotteryResult = $baccaratBettingWaitingResult->getLotteryResult();

        $logger = $this->container->get(LoggerFactory::class)->create($lotteryResult->terrace);

        //牌靴 房间
        $deck = $baccaratBettingWaitingResult->getDeck();

        //开奖日志
        $baccaratLotteryLog = $baccaratBettingWaitingResult->getLotteryLog();

        $this->roomManager = $this->container->make(RoomManager::class, [
            'mapper'    => make(RoomMapper::class),
            'bettingId' => (string)$this->betting->id,
            'terraceId' => $lotteryResult->terrace,
            'deckId'    => (string)$deck->id
        ]);

        $baccaratLotterySequence = $deck->baccaratLotterySequence;
        //获取投注单规则并匹配规则
        $rule = $this->betting->getRuleEngine()->applyRulesOnce($baccaratLotterySequence);

        $logger->info('match rule', ['baccaratLotterySequence' => $baccaratLotterySequence, 'rule' => (string)$rule, 'lotteryResult' => $lotteryResult->toArray()]);

        if (!$rule) {
            //判断当前当前开奖房间与房间一致 就退出房间
            $this->roomManager->checkRoom() && $this->roomManager->exitRoom();
            return null;
        }

        //判断当前是否在房间中 并且当前开奖房间与房间不一致
        $room = $this->roomManager->getRoom();
        $logger->info("match rule:{$rule} CurrentRoomId:{$room->getBettingId()} bettingId:{$this->betting->id} deckId:{$deck->id} checkRoom:{$this->roomManager->checkRoom()}");
        if (!$this->roomManager->checkRoom()) {
            return null;
        }

        //投注
        $bettingLog = $this->bettingLogMapper->getBaccaratSimulatedBettingLogOrCreate(
            rule: $rule,
            attributes: [
                'issue'      => $lotteryResult->issue,
                'betting_id' => $this->betting->id,
            ],
            values: [
                'terrace_deck_id' => $deck->id,
                'betting_value'   => $rule->getBettingValue(),
                'created_at'      => $baccaratLotteryLog->created_at,
            ]
        );

        //延长房间过期时间
        $this->roomManager->extendExpiration();
        $logger->info('投注成功', $bettingLog->toArray());

        return $bettingLog;
    }

    protected function deleteBaccaratSimulatedBettingLog(int $bettingId): void
    {
        $s = microtime(true);
        //首先清除投注单投注日志
        $this->bettingLogMapper->getModel()
            ->where('betting_id', $bettingId)
            ->delete();

        $this->output->info("BaccaratBettingRuleLog delete use s:" . number_format(microtime(true) - $s, 8));
    }

    public function betting()
    {
        //规则列表
        $ruleList = $this->betting->getRuleEngine()
            ->getRules();
        if ($ruleList->isEmpty()) {
            return null;
        }

        //查询牌桌牌堆按照日期分组
        $BaccaratTerraceDeckDateList = $this->terraceDeckService->getBaccaratTerraceDeckGroupDate();
        if ($BaccaratTerraceDeckDateList->isEmpty()) {
            return null;
        }

        $this->concurrent = new Concurrent(100);

        $this->eventDispatcher ??= $this->container->get(EventDispatcherInterface::class);

        //遍历牌桌日期
        foreach ($BaccaratTerraceDeckDateList as $BaccaratTerraceDeckDate) {

            $s = microtime(true);

            $this->cacheData = $this->baccaratBettingCache->get($this->betting->title, $BaccaratTerraceDeckDate->date);

            //遍历牌桌
            $baccaratTerraceDeckList = $this->deckMapper->getModel()
                ->whereDate('created_at', $BaccaratTerraceDeckDate->date)
                ->chunk(1000, function (Collection $baccaratTerraceDeckList) use ($ruleList, $BaccaratTerraceDeckDate) {

                    $baccaratTerraceDeckList->each(function (BaccaratTerraceDeck $baccaratTerraceDeck) use ($ruleList, $BaccaratTerraceDeckDate) {

                        if ($baccaratTerraceDeck->baccaratLotteryLog && $baccaratTerraceDeck->baccaratLotteryLog->isNotEmpty()) {

                            $event = new HistoricalDataSimulatedBetting($baccaratTerraceDeck);

                            $this->eventDispatcher->dispatch($event);

                            //遍历规则
                            foreach ($ruleList as $rule) {
                                if (!$rule instanceof RuleInterface) {
                                    throw new \Exception("规则必须实现 RuleInterface");
                                }
                                //判断规则是否已经存在投注记录
                                if (!$this->isBettingLogExists(rule: $rule->getRule(), deckId: $baccaratTerraceDeck->id)) {
                                    $this->concurrent->create(function () use ($baccaratTerraceDeck, $rule, $BaccaratTerraceDeckDate) {
                                        $this->createBettingLog($baccaratTerraceDeck, new BettingLog($rule));
                                    });

                                    $this->cacheData->add(rule: $rule->getRule(), deckId: (string)$baccaratTerraceDeck->id);
                                }
                            }
                        }
                    });
                });

            //将投注单的投注记录缓存 避免重复投注
            $this->baccaratBettingCache->set($this->betting->title, $BaccaratTerraceDeckDate->date, $this->cacheData);
            $this->output->info("date:{$BaccaratTerraceDeckDate->date} use s:" . number_format(microtime(true) - $s, 8));
            unset($BaccaratTerraceDeckDate);
            unset($baccaratTerraceDeckList);
            $this->cacheData = null;
        }
    }

    protected function isBettingLogExists(string $rule, int|string $deckId): bool
    {
        return $this->cacheData->exists(rule: $rule, deckId: (string)$deckId);
    }

    public function createBettingLog(BaccaratTerraceDeck $baccaratTerraceDeck, BettingLog $bettingLog): void
    {
        $baccaratTerraceDeck->baccaratLotteryLog->filter(fn(BaccaratLotteryLog $baccaratLotteryLog) => $baccaratLotteryLog->transformationResult && $baccaratLotteryLog->transformationResult !== LotteryResult::TIE)
            ->reduce(function (BettingLog $carry, BaccaratLotteryLog $baccaratLotteryLog) use ($baccaratTerraceDeck) {

                return $this->processBettingLog($carry, $baccaratLotteryLog, $baccaratTerraceDeck);
            }, $bettingLog);
    }

    public function processBettingLog(BettingLog $carry, BaccaratLotteryLog $baccaratLotteryLog, BaccaratTerraceDeck $baccaratTerraceDeck): BettingLog
    {
        if ($carry->isBetting) {

            $this->lockerFactory->get($this->getLockName($baccaratLotteryLog->issue))
                ->get(function () use ($carry, $baccaratLotteryLog, $baccaratTerraceDeck) {

                    $this->output->warn("开始模拟投注 sequence:{$carry->lotterySequence} title:{$carry->rule->getName()} rule:{$carry->rule->getRule()} betting_value:{$carry->rule->getBettingValue()}");

                    return $this->bettingLogMapper->getBaccaratSimulatedBettingLogOrCreate(
                        rule: $carry->rule,
                        attributes: [
                            'issue'      => $baccaratLotteryLog->issue,
                            'betting_id' => $this->betting->id,
                        ],
                        values: [
                            'terrace_deck_id' => $baccaratTerraceDeck->id,
                            'betting_value'   => $carry->rule->getBettingValue(),
                            'created_at'      => $baccaratLotteryLog->created_at,
                            'betting_result'  => $baccaratLotteryLog->getLotteryResult()->checkLotteryResults($carry->rule->getBettingValue())
                        ]
                    );
                });
        }

        $carry->lotterySequence .= $baccaratLotteryLog->transformationResult;
        $carry->isBetting = $carry->rule->match($carry->lotterySequence);
        return $carry;
    }

    protected function getLockName(string $name): string
    {
        return sprintf('%s_%s', self::LOCK_PREFIX, $name);
    }
}