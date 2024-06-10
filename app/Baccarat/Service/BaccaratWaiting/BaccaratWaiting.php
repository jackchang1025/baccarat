<?php

namespace App\Baccarat\Service\BaccaratWaiting;

use App\Baccarat\Cache\DeckBettingCache;
use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\BaccaratBetting\BaccaratBettingWaitingResult;
use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use Psr\Log\LoggerInterface;

class BaccaratWaiting
{

    public function __construct(
        protected BaccaratSimulatedBettingLogMapper $baccaratSimulatedBettingLogMapper,
        protected DeckBettingCache $bettingCache,
    )
    {

    }

    /**
     * 处理开奖
     * @param BaccaratBettingWaitingResult $baccaratBettingWaitingResult
     * @return bool
     */
    public function handleWaiting(BaccaratBettingWaitingResult $baccaratBettingWaitingResult): bool
    {
        $lotteryResult = $baccaratBettingWaitingResult->getLotteryResult();
        //更新投注日志
        $baccaratSimulatedBettingLogList =  $this->baccaratSimulatedBettingLogMapper->getBettingLogListBettingResultWhereNull($lotteryResult->issue)
            ->each(function (BaccaratSimulatedBettingLog $bettingLog) use ($lotteryResult){

                //获取投注结果
                $bettingResult = $lotteryResult->checkLotteryResults($bettingLog->betting_value);

                //更新投注结果
                $bettingLog->update(['betting_result' => $bettingResult]);

                //首先筛选投注日志为赢的记录，然后将 投注日志中 terrace_deck_id 写入到 缓存中，表示该牌靴已经投注并且盈利
                //这里是为了配合 bacc 文档中每一局只赢一注就可以了，所以这一牌靴已经投注并且为 true 则不进行投注
                if ($bettingResult === LotteryResult::BETTING_WIN) {
                    $this->bettingCache->set($bettingLog->terrace_deck_id);
                }
            });

        return true;
    }
}