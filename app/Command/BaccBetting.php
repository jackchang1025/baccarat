<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Event\HistoricalDataSimulatedBetting;
use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Mapper\BaccaratTerraceDeckMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratBetting\BettingLog;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use App\Baccarat\Service\Locker\LockerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Platform\Bacc\Bacc;
use App\Baccarat\Service\Platform\Bacc\Exception\EvaluateException;
use App\Baccarat\Service\Rule\CustomizeRules;
use App\Baccarat\Service\Rule\RuleInterface;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Coroutine\Concurrent;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Database\Model\Collection;
use Psr\Container\ContainerInterface;
use App\Baccarat\Service\BaccaratBetting\BaccaratBetting;

#[Command]
class BaccBetting extends HyperfCommand
{
    protected Concurrent $concurrent;
    protected BettingLog $bettingLog;

    public function __construct(
        protected ContainerInterface                         $container,
        protected readonly BaccaratTerraceDeckService        $terraceDeckService,
        protected readonly BaccaratTerraceDeckMapper         $deckMapper,
        protected readonly BaccaratBetting                   $baccaratBetting,
        protected readonly LockerFactory                     $lockerFactory,
        protected readonly BaccaratSimulatedBettingLogMapper $bettingLogMapper,
        protected readonly Bacc                              $bacc,
    )
    {
        parent::__construct('bacc:betting');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {

        //查询牌桌牌堆按照日期分组
        $BaccaratTerraceDeckDateList = $this->terraceDeckService->getBaccaratTerraceDeckGroupDate();
        if ($BaccaratTerraceDeckDateList->isEmpty()) {
            return null;
        }

        $this->concurrent = new Concurrent(100);

        //遍历牌桌日期
        foreach ($BaccaratTerraceDeckDateList as $BaccaratTerraceDeckDate) {
            $s = microtime(true);
            //遍历牌桌
            $baccaratTerraceDeckList = $this->deckMapper->getModel()
                ->whereDate('created_at', $BaccaratTerraceDeckDate->date)
                ->chunk(1000, function (Collection $baccaratTerraceDeckList) use ($BaccaratTerraceDeckDate) {

                    $baccaratTerraceDeckList->each(function (BaccaratTerraceDeck $baccaratTerraceDeck) use ($BaccaratTerraceDeckDate) {

                        if ($baccaratTerraceDeck->baccaratLotteryLog && $baccaratTerraceDeck->baccaratLotteryLog->isNotEmpty()) {

                            //判断规则是否已经存在投注记录
                            $this->concurrent->create(function () use ($baccaratTerraceDeck, $BaccaratTerraceDeckDate) {
                                try {

                                    $this->createBettingLog($baccaratTerraceDeck, $this->getBettingLog());

                                } catch (EvaluateException $e) {

                                    $this->error($e->getMessage());
                                }
                            });
                        }
                    });
                });

            //将投注单的投注记录缓存 避免重复投注
            $this->output->info("date:{$BaccaratTerraceDeckDate->date} use s:" . number_format(microtime(true) - $s, 8));
            unset($BaccaratTerraceDeckDate);
            unset($baccaratTerraceDeckList);
        }
        $this->line('Hello Hyperf!', 'info');
    }

    public function getBettingLog():BettingLog {
        return new BettingLog(new CustomizeRules(pattern: '//', bettingValue: '', name: 'bacc'));
    }

    /**
     * @param BaccaratTerraceDeck $baccaratTerraceDeck
     * @param BettingLog $bettingLog
     * @return void
     */
    public function createBettingLog(BaccaratTerraceDeck $baccaratTerraceDeck, BettingLog $bettingLog): void
    {
        $baccaratTerraceDeck->baccaratLotteryLog->filter(fn(BaccaratLotteryLog $baccaratLotteryLog) => $baccaratLotteryLog->transformationResult && $baccaratLotteryLog->transformationResult !== LotteryResult::TIE)
            ->reduce(function (BettingLog $carry, BaccaratLotteryLog $baccaratLotteryLog) use ($baccaratTerraceDeck) {

                return $this->processBettingLog($carry, $baccaratLotteryLog, $baccaratTerraceDeck);
            }, $bettingLog);
    }

    /**
     * @param BettingLog $carry
     * @param BaccaratLotteryLog $baccaratLotteryLog
     * @param BaccaratTerraceDeck $baccaratTerraceDeck
     * @return BettingLog
     * @throws EvaluateException
     */
    public function processBettingLog(BettingLog $carry, BaccaratLotteryLog $baccaratLotteryLog, BaccaratTerraceDeck $baccaratTerraceDeck): BettingLog
    {
        if ($carry->isBetting) {

            $this->lockerFactory->get($this->getLockName( (string) $baccaratLotteryLog->issue))
                ->get(function () use ($carry, $baccaratLotteryLog, $baccaratTerraceDeck) {

                    $this->warn("开始模拟投注 sequence:{$carry->lotterySequence} title:{$carry->rule->getName()} rule:{$carry->rule->getRule()} betting_value:{$carry->rule->getBettingValue()}");

                    $betting_result = $baccaratLotteryLog->getLotteryResult()->checkLotteryResults($carry->rule->getBettingValue());
                    $this->bettingLogMapper->getBaccaratSimulatedBettingLogOrCreate(
                        rule: $carry->rule,
                        attributes: [
                            'issue'      => $baccaratLotteryLog->issue,
                            'betting_id' => 10000,
                        ],
                        values: [
                            'terrace_deck_id' => $baccaratTerraceDeck->id,
                            'betting_value'   => $carry->rule->getBettingValue(),
                            'created_at'      => $baccaratLotteryLog->created_at,
                            'betting_result'  => $betting_result,
                            'remark'          => $carry->response->getCredibility(),
                        ]
                    );
                    if ($betting_result === LotteryResult::BETTING_WIN){
                        throw new EvaluateException('The betting result is win');
                    }
                });
            $carry->isBetting = false;
        }

        $carry->lotterySequence .= $baccaratLotteryLog->transformationResult;
        $baccaratLotterySequenceString = str_replace(['B', 'P'], ['1', '0'], $carry->lotterySequence);
        $baccaratLotterySequence = array_map('intval', str_split($baccaratLotterySequenceString));

        if (count($baccaratLotterySequence) >= 50){
            throw new EvaluateException('There are more than 50 numbers');
        }

        if (count($baccaratLotterySequence) > 15) {
            // 计算结果
            $response = $this->bacc->calculate($baccaratLotterySequence);
            $this->info("convert {$baccaratLotterySequenceString} message:{$response->getMessage()}");

            // 判断是否需要投注
            if ($response->getBets()) {
                $carry->isBetting = true;
                $carry->response = $response;
                // 开始投注
                $rule = new CustomizeRules(pattern: '//', bettingValue: $response->convertBets(), name: 'bacc');
                $carry->rule = $rule;
            }
        }

        Coroutine::sleep(1);
        return $carry;
    }

    protected function getLockName(string $name): string
    {
        return sprintf('%s_%s', BaccaratBetting::LOCK_PREFIX, $name);
    }
}
