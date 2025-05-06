<?php

declare(strict_types=1);

namespace App\Baccarat\Listener;

use App\Baccarat\Event\BettingEvent;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BaccaratLotteryLogService;
use App\Baccarat\Service\BaccaratSimulatedBettingLogService;
use App\Baccarat\Service\BaccaratSimulatedBettingService;
use App\Baccarat\Service\BaccaratTerraceDeckService;
use App\Baccarat\Service\BaccaratTerraceService;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Platform\Bacc\Bacc;
use App\Baccarat\Service\Platform\Bacc\Response;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;
use Hyperf\Redis\RedisFactory;
use App\Baccarat\Service\RoomManager\RoomManager;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratLotteryLog;

#[Listener]
class BettingBaccListener implements ListenerInterface
{

    protected LoggerInterface $logger;

    public  const BETTING_ID = 666666;

    public function __construct(
        protected Bacc $bacc,
        protected LoggerFactory $loggerFactory,
        protected BaccaratTerraceService $baccaratTerraceService,
        protected BaccaratTerraceDeckService $baccaratTerraceDeckService,
        protected BaccaratLotteryLogService $baccaratLotteryLogService,
        protected BaccaratSimulatedBettingLogService $baccaratSimulatedBettingLogService,
        protected BaccaratSimulatedBettingService $baccaratSimulatedBettingService,
        protected RedisFactory $redisFactory,
        protected RoomManager $roomManager,
        protected Output                   $output,
    ) {
    }

    public function listen(): array
    {
        return [
            BettingEvent::class,
        ];
    }

    public function process(object $event): void
    {
        /** @var BettingEvent $event */
        $lotteryResult = $event->lotteryResult;

        if (!$lotteryResult->isBetting() || !$lotteryResult->getDeckNumber()) {
            return;
        }

        // 创建基础数据部分不需要加锁
        // ($this->logger = $this->loggerFactory->create($lotteryResult->terrace, 'baccarat')) && $this->logger->debug($lotteryResult);

        // 根据code获取台号且不存在就创建
        $baccaratTerrace = $this->baccaratTerraceService->getBaccaratTerraceOrCreateByCode($lotteryResult->terrace);

        // 根据台号获取今日台靴不存在则创建
        $baccaratTerraceDeck = $this->baccaratTerraceDeckService->getBaccaratTerraceDeckWithTodayOrCreate(
            $baccaratTerrace->id,
            $lotteryResult->getDeckNumber()
        );

        // 根据期号获取开奖日志不存在则创建
        $baccaratLotteryLog = $this->baccaratLotteryLogService->getLotteryLogOrCreate(
            $baccaratTerraceDeck->id,
            $lotteryResult
        );

          // 获取牌面所有开奖结果
        if (!$transformationResult = $baccaratTerraceDeck->baccaratLotterySequence) {
            return;
        }

         //判断是否有为 null 的开奖,有则说明此局开奖数据不完整
         $lotteryLog = BaccaratLotteryLog::query()
         ->whereNull('result')
         ->whereNotIn('issue',[$lotteryResult->issue])
         ->where('terrace_deck_id',$baccaratTerraceDeck->id)
         ->exists();

        if($lotteryLog){
            $this->output->error(
                sprintf(
                    "terrace:%s terrace_id:%d deck_id:%d issue:%s result is null",
                    $lotteryResult->getTerrainTableName(),
                    $baccaratTerrace->id,
                    $baccaratTerraceDeck->id,
                    $lotteryResult->issue,
                )
            );
            return;
        }

        // 计算结果和投注操作
        $baccaratLotterySequenceString = str_replace(['B', 'P', 'T'], ['1', '0', ''], $transformationResult);
        $baccaratLotterySequence = array_map('intval', str_split($baccaratLotterySequenceString));
        
        if (count($baccaratLotterySequence) < 10) {
            return;
        }


        $response = $this->bacc->calculate($baccaratLotterySequence);
        
        if (!$response->getBets()) {
            return;
        }

        // 记录计算结果
        $this->logCalculationResult($lotteryResult, $baccaratTerrace, $baccaratTerraceDeck, $baccaratLotterySequenceString, $response);


        if($response->isHigh()){
            $this->bettingHigh($lotteryResult, $baccaratTerrace, $baccaratTerraceDeck, $response, $baccaratLotterySequence);
        }


        // $this->betting($lotteryResult, $baccaratTerraceDeck, $response);
    }

    private function betting(LotteryResult $lotteryResult,BaccaratTerraceDeck $baccaratTerraceDeck,Response $response): void
    {
        // 检查是否存在模拟投注记录
        $baccaratSimulatedBettingLog = BaccaratSimulatedBettingLog::where('issue',$lotteryResult->issue)->where('betting_id',999999)->exists();
        if($baccaratSimulatedBettingLog){
            return;
        }

        // 创建模拟投注记录
        BaccaratSimulatedBettingLog::create([
            'terrace_deck_id' => $baccaratTerraceDeck->id,
            'betting_id'      => 999999,
            'issue'           => $lotteryResult->issue,
            'betting_value'   => $response->convertBets(),
            'remark'          => $response->toJson(),
            'credibility'     => $response->getCredibility(),
            'confidence'     => $response->getConfidence(),
        ]);
    }


    private function bettingHigh(LotteryResult $lotteryResult,BaccaratTerrace $baccaratTerrace,BaccaratTerraceDeck $baccaratTerraceDeck,Response $response,array $baccaratLotterySequence): void
    {
        // 检查是否存在模拟投注记录
        $baccaratSimulatedBettingLog = BaccaratSimulatedBettingLog::where('issue',$lotteryResult->issue)->where('betting_id',99999)->exists();
        if($baccaratSimulatedBettingLog){
            return;
        }

        // 检查开奖长度是否大于40且是否存在房间
        if(count($baccaratLotterySequence) > 40 && !$this->roomManager->checkRoom($baccaratTerrace->id,$baccaratTerraceDeck->id)){
            return;
        }

        // 进入房间
        if(!$this->roomManager->enterRoom($baccaratTerrace->id,$baccaratTerraceDeck->id)){
            return;
        }

        // 创建模拟投注记录
        BaccaratSimulatedBettingLog::create([
            'terrace_deck_id' => $baccaratTerraceDeck->id,
            'betting_id'      => self::BETTING_ID,
            'issue'           => $lotteryResult->issue,
            'betting_value'   => $response->convertBets(),
            'remark'          => $response->toJson(),
            'credibility'     => $response->getCredibility(),
            'confidence'     => $response->getConfidence(),
        ]);

        // 记录投注成功
        $this->logBettingSuccess($lotteryResult, $baccaratTerrace, $baccaratTerraceDeck, $response);
    }

    private function logBettingSuccess(LotteryResult $result,BaccaratTerrace $terrace,BaccaratTerraceDeck $deck,Response $response): void
    {
        $this->output->error(sprintf(
            "enter room terrace_id:%d deck_id:%d deck:%s issue:%s credibility:%s confidence:%s betting_value:%s",
            $terrace->id,
            $deck->id,
            $result->getTerrainTableName(),
            $result->issue,
            $response->getCredibility(),
            $response->getConfidence(),
            $response->convertBets()
        ));
    }

    private function logCalculationResult(LotteryResult $result,BaccaratTerrace $terrace,BaccaratTerraceDeck $deck, string $baccaratLotterySequenceString,Response $response): void
    {
        $this->output->warn(sprintf(
            "deck:%s terrace_id:%d deck_id:%d issue:%s convert %s message:%s",
            $result->getTerrainTableName(),
            $terrace->id,
            $deck->id,
            $result->issue,
            $baccaratLotterySequenceString,
            $response->getMessage()
        ));
    }
}
