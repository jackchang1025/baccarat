<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Service\Platform\Bacc\Bacc;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Database\Model\Collection;
use Hyperf\Coroutine\Parallel;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Model\BaccaratDeckStatistics;
use App\Baccarat\Service\Statistics\DeckStatisticsService;
use Hyperf\Coroutine\Coroutine;

#[Command]
class BaccaratBetting extends HyperfCommand
{
    private const CACHE_KEY_PREFIX = 'baccarat:betting:deck:';
    private const CHUNK_SIZE = 100;

    protected RedisProxy $redis;

    public function __construct(
        protected ContainerInterface $container,
        protected Bacc $bacc,
        protected RedisFactory $factory,
        private readonly DeckStatisticsService $statisticsService
    ) {
        parent::__construct('baccarat:betting:bacc');

        $this->redis = $this->factory->get('default');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Process baccarat betting based on lottery records');
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $this->process();
            
            $duration = number_format(microtime(true) - $startTime, 8);
            $this->info("Baccarat betting completed successfully. Duration: {$duration}s");
            
        } catch (\Throwable $e) {
            $this->error("Error processing baccarat betting: " . $e->getMessage());

        }
    }

    protected function process(): void
    {
        BaccaratTerraceDeck::query()
            ->whereRaw('DATE(created_at) != ?', [date('Y-m-d')])
            ->with(['baccaratLotteryLog'])
            ->chunk(self::CHUNK_SIZE, function(Collection $collection) {


                
                // && !$this->isDeckProcessed($deck->id)
                $collection->filter(fn(BaccaratTerraceDeck $deck) => $deck->baccaratLotteryLog->isNotEmpty() && !$this->isDeckProcessed($deck->id))
                    ->map(function (BaccaratTerraceDeck $deck) {
                        try {


                            $this->baccaratLotteryLog($deck);
                        } catch (\Throwable $e) {
                            $this->error("Error processing deck {$deck->id}: " . $e->getMessage());
                        }
                });
        });

        
    }

    protected function baccaratLotteryLog(BaccaratTerraceDeck $deck): void
    {
        $s = microtime(true);

        $deck->baccaratLotteryLog->reduce(function (string $baccaratLotterySequence,BaccaratLotteryLog $lotteryLog){

            $lotteryResult = $lotteryLog->getLotteryResult();

            if ($lotteryResult->getTransformationResult() === 'T' || !$lotteryResult->getTransformationResult()) {
                return $baccaratLotterySequence;
            }

            
            // 将 $deck->baccaratLotterySequence 字符串中 B 替换为 0，P 替换为 1
            $baccaratLotterySequenceString = str_replace(['B', 'P','T'], ['1', '0',''], $baccaratLotterySequence);

            // 将 $baccaratLotterySequence 切割为数组
            $baccaratLotterySequenceArray = array_map('intval', str_split($baccaratLotterySequenceString));

            if (count($baccaratLotterySequenceArray) >= 6) {

                // 计算结果
                $response = $this->bacc->calculate($baccaratLotterySequenceArray);
                $this->info("sequence:{$baccaratLotterySequence} convert {$baccaratLotterySequenceString} message:{$response->getMessage()} confidence:{$response->getConfidence()} credibility:{$response->getCredibility()}");


                // 判断是否需要投注
                if ($response->getBets()) {
                    // 模拟投注
                    $baccaratSimulatedBettingLog =  BaccaratSimulatedBettingLog::updateOrCreate(
                        [
                            'issue' => $lotteryLog->issue,
                        ],
                        [
                            'terrace_deck_id' => $lotteryLog->terrace_deck_id,
                            'betting_value' => $response->convertBets(),
                            'credibility' => $response->getCredibility(),
                            'confidence' => $response->getConfidence(),
                            'betting_result' => $lotteryResult->checkLotteryResults($response->convertBets()),
                            'remark' => $response->toJson()
                        ]);

                    $this->info("开始模拟投注 sequence:{$baccaratLotterySequence} convert:{$baccaratLotterySequenceString} betting_value:{$baccaratSimulatedBettingLog->betting_value} lottery_result:{$lotteryResult->getTransformationResult()} betting_result:{$baccaratSimulatedBettingLog->betting_result}");
                }


                Coroutine::sleep(1);

            }

            if($lotteryLog->transformationResult != $lotteryResult->getTransformationResult()){

                $this->info("更新 transformationResult:{$lotteryLog->transformationResult} to {$lotteryResult->getTransformationResult()}");

                $lotteryLog->transformationResult = $lotteryResult->getTransformationResult();
                $lotteryLog->save();
                
            }
        
            $baccaratLotterySequence .= $lotteryResult->getTransformationResult();


            return $baccaratLotterySequence;

        },'');

        // 统计并保存数据
        $this->saveDeckStatistics($deck);
        
        $this->markDeckAsProcessed($deck->id);
        
        $this->output->info("baccaratTerraceDeck:{$deck->id} use s:".number_format(microtime(true) - $s, 8));
    }

    protected function saveDeckStatistics(BaccaratTerraceDeck $deck): void
    {
        // 计算统计数据
        $stats = $this->statisticsService->getStatistics($deck->baccaratLotteryLog);
        
        // 保存到数据库
        BaccaratDeckStatistics::updateOrCreate(
            ['terrace_deck_id' => $deck->id],
            [
                'terrace_id' => $deck->terrace_id,
                'deck_number' => $deck->deck_number,
                'total_bets' => $stats['total_bets'],
                'total_wins' => $stats['total_wins'],
                'total_losses' => $stats['total_losses'],
                'total_ties' => $stats['total_ties'],
                'total_win_rate' => $stats['total_win_rate'],
                'credibility_stats' => $stats['credibility_stats'],
                'betting_sequence' => $stats['betting_string']
            ]
        );
    }

    protected function isDeckProcessed(int $deckId): bool
    {
        return (bool)$this->redis->get(self::CACHE_KEY_PREFIX . $deckId);
    }

    protected function markDeckAsProcessed(int $deckId): void
    {
        $this->redis->set(self::CACHE_KEY_PREFIX . $deckId, '1');
    }

    public function deleteDeckCache(int $deckId): bool
    {
       return (bool) $this->redis->del(self::CACHE_KEY_PREFIX . $deckId);
    }
}
