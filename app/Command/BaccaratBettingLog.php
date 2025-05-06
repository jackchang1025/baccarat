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
use App\Baccarat\Model\BaccaratStrategyBettingLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Listener\BettingBaccListener;

#[Command]
class BaccaratBettingLog extends HyperfCommand
{


    public function __construct(
        protected ContainerInterface $container,
    ) {
        parent::__construct('baccarat:betting:log');
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
        // BaccaratStrategyBettingLog::where('id','>',0)->delete();
        // BaccaratSimulatedBetting::where('id','>',0)->delete();


        // BaccaratSimulatedBettingLog::with(['baccaratLotteryLog.baccaratTerraceDeck.baccaratTerrace'])->where('betting_id', 99999)->whereNull('betting_result')->chunk(100,function(Collection $bettingLogs){

        //     $bettingLogs->each(function(BaccaratSimulatedBettingLog $bettingLog){

        //         $lotteryResult = $bettingLog->baccaratLotteryLog->getLotteryResult();

        //         $this->info(
        //             sprintf(
        //                 "terrace:%s rn:%s terrace_id:%d deck_id:%d issue:%s lottery_result:%s betting_value:%s betting_result:%s",
        //             $lotteryResult->getTerrainTableName(),
        //             $bettingLog->baccaratLotteryLog->baccaratTerraceDeck->baccaratTerrace->code,
        //             $bettingLog->baccaratLotteryLog->baccaratTerraceDeck->baccaratTerrace->id,
                    
        //             $bettingLog->baccaratLotteryLog->baccaratTerraceDeck->id,
        //             $bettingLog->issue,
        //             $bettingLog->baccaratLotteryLog->transformationResult,
        //             $bettingLog->betting_value,
        //             $bettingLog->betting_result
        //             )
        //         );


        //         if($bettingLog->baccaratLotteryLog->transformationResult){

        //             $this->info('betting_result:'.$lotteryResult->checkLotteryResults($bettingLog->betting_value));

        //             $bettingLog->betting_result = $lotteryResult->checkLotteryResults($bettingLog->betting_value);

        //             $bettingLog->save();
        //         }
        //     });
        // });
        
        $totalNumber = 0;
        $totalCount0 = 0;
        // return;
        BaccaratSimulatedBettingLog::where('betting_id', BettingBaccListener::BETTING_ID)
        ->whereIn('betting_result', [0, 1])
        // ->whereIn('credibility',['ALMOST','MEDIUM'])
        // ->whereIn('credibility',['HIGH'])
        ->orderBy('id')
        ->chunk(100, function (Collection $terraceDeckIds) use(&$totalNumber,&$totalCount0) {

            $totalNumber++;

            // 获取sequence 例如：0011001111100111001010100011110111100111010100100111100001011111001100101011011101101010110011000111
            $sequence = $terraceDeckIds->pluck('betting_result')->implode('');

            //如何获取 sequence 中 连续出现 0 的次数,注意是连续出现 0,例如 10000001110000 连续出现 0 的次数为  6 
            preg_match_all('/0+/', $sequence, $matches);
            $maxConsecutiveZeros = 0;
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    $consecutiveZeros = strlen($match);
                    if ($consecutiveZeros > $maxConsecutiveZeros) {
                        $maxConsecutiveZeros = $consecutiveZeros;
                    }
                }
            }
            if ($maxConsecutiveZeros >= 7) {
                $totalCount0++;
            }

            //将 sequence 中以 0 开头 1 结尾的转换为 0，例如：01，001,0001,0000001 转换为 0
            $sequenceConvert = preg_replace('/0+1/', '0', $sequence);


            $totalBets = $terraceDeckIds->count();
            $count0 = $terraceDeckIds->where('betting_result', 0)->count();
            $count1 = $terraceDeckIds->where('betting_result', 1)->count();
            $percentage0 = $count0 / $totalBets * 100;
            $percentage1 = $count1 / $totalBets * 100;

            //sequenceConvert:{$sequenceConvert} 
            $this->info("sequence:{$sequence} Total Bets: {$totalBets} Count of 0: {$count0} ({$percentage0}%) Count of 1: {$count1} ({$percentage1}%),maxConsecutiveZeros:{$maxConsecutiveZeros}");

            //调用 baccarat:random-sequence 命令 并传入 sequence 选项

            // $this->call('baccarat:random-sequence', [
            //     '--sequence' => $sequence,
            // ]);
        });

        $this->info("Total Number: {$totalNumber} Total Count of 0: {$totalCount0} (".($totalCount0 / $totalNumber * 100)."%)");
       
    }

    public function updateBettingResult(): void
    {
        BaccaratSimulatedBettingLog::where('betting_id', 99999)
        ->whereNull('betting_result')
        ->chunk(100, function (Collection $bettingLogs) {
            $bettingLogs->each(function (BaccaratSimulatedBettingLog $bettingLog) {

                //
                $bettingLog->betting_result = 0;
                $bettingLog->save();
            });
        });
    }
}
 