<?php

namespace App\Baccarat\Service;

use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingRule;
use Hyperf\Database\Model\Collection;
use Psr\Log\LoggerInterface;
use App\Baccarat\Service\RoomManager\RoomManager;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Listener\BettingBaccListener;
class BaccaratService
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    public function __construct(
        protected BaccaratTerraceService             $baccaratTerraceService,
        protected BaccaratTerraceDeckService         $baccaratTerraceDeckService,
        protected BaccaratLotteryLogService          $baccaratLotteryLogService,
        protected BaccaratSimulatedBettingLogService $baccaratSimulatedBettingLogService,
        protected BaccaratSimulatedBettingService    $baccaratSimulatedBettingService,
        protected LoggerFactory                      $loggerFactory,
        protected RoomManager                        $roomManager,
        protected Output                             $output
    )
    {

    }

    /**
     * 处理投注
     * @param LotteryResult $lotteryResult
     * @return mixed|null
     */
    public function handleBetting(LotteryResult $lotteryResult): mixed
    {

        if (!$lotteryResult->isBetting() || !$lotteryResult->getDeckNumber()) {
            return null;
        }

        ($this->logger = $this->loggerFactory->create($lotteryResult->terrace, 'baccarat')) && $this->logger->debug($lotteryResult);

        //根据 code 获取台号且不存在就创建
        $baccaratTerrace = $this->baccaratTerraceService->getBaccaratTerraceOrCreateByCode($lotteryResult->terrace);

        //根据台号获取今日台靴不存在则创建
        $baccaratTerraceDeck = $this->baccaratTerraceDeckService->getBaccaratTerraceDeckWithTodayOrCreate($baccaratTerrace->id, $lotteryResult->getDeckNumber());

        //根据期号获取开奖日志不存在则创建
        $this->baccaratLotteryLogService->getLotteryLogOrCreate($baccaratTerraceDeck->id, $lotteryResult);

        //获取牌面所有开奖结果
        if (!$transformationResult = $baccaratTerraceDeck->baccaratLotterySequence) {
            return null;
        }

        var_dump($transformationResult);

        //获取所有投注信息
        $baccaratSimulatedBettingList = $this->baccaratSimulatedBettingService->getBaccaratSimulatedBettingList();
        if ($baccaratSimulatedBettingList->isEmpty()) {
            return null;
        }

        return $baccaratSimulatedBettingList->each(function (BaccaratSimulatedBetting $simulatedBetting) use ($lotteryResult, $transformationResult, $baccaratTerraceDeck) {

            $simulatedBetting->baccaratSimulatedBettingRule
                ->filter(fn(BaccaratSimulatedBettingRule $bettingRule) => $bettingRule->rule && $bettingRule->betting_value)
                ->first(function (BaccaratSimulatedBettingRule $bettingRule) use ($simulatedBetting, $lotteryResult, $transformationResult, $baccaratTerraceDeck) {

                    $matches = $this->pregMatch($bettingRule->rule, $transformationResult);

                    $this->logger->info("preg_match title:{$bettingRule->title} betting_value:{$bettingRule->betting_value} rule:{$bettingRule->rule} matches:{$matches} transformationResult:{$transformationResult}", $lotteryResult->toArray());

                    return transform($matches ?: null, function () use ($simulatedBetting, $lotteryResult, $bettingRule, $transformationResult, $baccaratTerraceDeck) {

                        $this->logger->info("开始投注 baccarat_simulated_betting_id: {$simulatedBetting->id} rule:{$bettingRule->rule} betting_value:{$bettingRule->betting_value} transformationResult:{$transformationResult}", $lotteryResult->toArray());

                        //判断是否已经投注
                        $getBaccaratSimulatedBettingLog = $this->baccaratSimulatedBettingLogService->getBaccaratSimulatedBettingLog($lotteryResult->issue,$simulatedBetting->id);
                        if (!$getBaccaratSimulatedBettingLog) {
                            return $this->baccaratSimulatedBettingLogService->createBettingLogAndRuleLog([
                                'betting_id' => $simulatedBetting->id,
                                'terrace_deck_id' => $baccaratTerraceDeck->id,
                                'issue' => $lotteryResult->issue,
                                'betting_value' => $bettingRule->betting_value,
                            ], $bettingRule);
                        }

                        return false;
                    }, false);
                });
        });
    }

    public function pregMatch(string $rule, string $transformationResult): bool|int
    {
        return preg_match($rule, $transformationResult);
    }

    /**
     * 处理开奖
     * @param LotteryResult $lotteryResult
     * @return Collection|null
     * @throws \JsonException
     */
    public function handleWaiting(LotteryResult $lotteryResult): ?Collection
    {
        if ($lotteryResult->issue && ($lotteryResult->isWaiting() || !$lotteryResult->needDrawCard()) && $lotteryResult->getTransformationResult()) {

            //更新开奖日志
            $lotteryLog = $this->baccaratLotteryLogService->updateLotteryLog($lotteryResult);

            //更新投注日志
            $baccaratSimulatedBettingLogList = $this->baccaratSimulatedBettingLogService->updateBettingResult($lotteryResult);


            if ($lotteryLog) {

                $validateRoom = $this->roomManager->checkRoom($lotteryLog->baccaratTerraceDeck->terrace_id,$lotteryLog->terrace_deck_id);


                //判断这一次开奖是否投注
                $baccaratSimulatedBettingLog = BaccaratSimulatedBettingLog::query()
                ->where('issue',$lotteryResult->issue)
                ->where('betting_id',BettingBaccListener::BETTING_ID)
                ->first();

                $room = json_encode(
                    $this->roomManager->getCurrentRoom() ?? [],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                );

                $this->output->info("handleWaiting deck:{$lotteryResult->getTerrainTableName()} deck_id:{$lotteryLog->baccaratTerraceDeck->terrace_id} deck_id:{$lotteryLog->terrace_deck_id} issue:{$lotteryResult->issue} isRoom:{$validateRoom} room:{$room} br:{$baccaratSimulatedBettingLog?->betting_result}");

                //判断是否在当前房间
                if ($validateRoom) {

                    //判断上一次是否投注或者投注结果是否赢了
                    if (!$baccaratSimulatedBettingLog || $baccaratSimulatedBettingLog->betting_result === LotteryResult::BETTING_WIN) {
                        $this->roomManager->exitRoom();
                        $this->output->error("exit room terrace_deck:{$lotteryResult->getTerrainTableName()} issue:{$lotteryResult->issue}");
                    }

                }
            }
            //更新投注日志列表
            return $baccaratSimulatedBettingLogList;

        }
        return null;
    }
}