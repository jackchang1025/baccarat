<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Coroutine\Concurrent;
use Hyperf\Database\Model\Collection;
use Psr\Container\ContainerInterface;

#[Command]
class UpdateBettingLog extends HyperfCommand
{
    protected Concurrent $concurrent;

    public function __construct(protected ContainerInterface $container,protected BaccaratSimulatedBettingLogMapper $bettingLogMapper)
    {
        parent::__construct('baccarat:update:bettingLog');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        $this->info('start');
        $s = microtime(true);
        $this->concurrent = new Concurrent(100);

       $this->handleBettingLog2();

        $this->info("done use s:".number_format(microtime(true) - $s, 8));
    }

    public function handleBettingLog1(): void
    {
        $dates = ['202413','202414','202415','202416','202417'];

        foreach ($dates as $date){

            $baccaratLotteryLog = BaccaratLotteryLog::make();
            $baccaratLotteryLog->setTable(sprintf('%s_%s',$baccaratLotteryLog->tableName(),$date));

            $baccaratLotteryLog->chunk(1000,function (Collection $baccaratLotteryLogs){
                $baccaratLotteryLogs->each(function (BaccaratLotteryLog $baccaratLotteryLog){
                    $baccaratLotteryLog->RawData && $this->concurrent->create(fn() => $this->updateBettingLog1($baccaratLotteryLog));
                });
            });
        }
    }

    public function updateBettingLog1(BaccaratLotteryLog $baccaratLotteryLog): void
    {
        $lotteryResult = $baccaratLotteryLog->getLotteryResult();

        $baccaratLotteryLog->transformationResult = $lotteryResult->getTransformationResult();
        $baccaratLotteryLog->save();

        $this->info("update lotteryLog:{$baccaratLotteryLog->id} transformationResult:{$baccaratLotteryLog->transformationResult}");

        if ($baccaratLotteryLog->baccaratSimulatedBettingLog){
            $baccaratLotteryLog->baccaratSimulatedBettingLog->each(function (BaccaratSimulatedBettingLog $bettingLog) use ($baccaratLotteryLog,$lotteryResult){
                if ($bettingLog->betting_value){
                    $bettingLog->betting_result = $lotteryResult->checkLotteryResults($bettingLog->betting_value);
                    $bettingLog->created_at = $baccaratLotteryLog->created_at;
                    $bettingLog->save();
                    $this->info("update bettingLog:{$bettingLog->id} betting_value:{$bettingLog->betting_value} transformationResult:{$baccaratLotteryLog->transformationResult} {$bettingLog->betting_result}");
                }
            });
        }
    }

    public function handleBettingLog2(): void
    {
        $this->bettingLogMapper->getModel()
            ->whereNull('betting_result')
            ->whereNotNull('betting_value')
            ->chunk(1000,function (Collection $bettingLogs){

                $bettingLogs->each(function (BaccaratSimulatedBettingLog $bettingLog){

                    if ($bettingLog->baccaratLotteryLog?->transformationResult){
                        $this->concurrent->create(fn() => $this->updateBettingLog2($bettingLog));
                    }
                });
        });
    }

    public function updateBettingLog2(BaccaratSimulatedBettingLog $bettingLog): void
    {
        $bettingLog->betting_result = $bettingLog->baccaratLotteryLog->getLotteryResult()->checkLotteryResults($bettingLog->betting_value);
        $bettingLog->created_at = $bettingLog->baccaratLotteryLog->created_at;
        $bettingLog->save();
        $this->info("update bettingLog:{$bettingLog->id} betting_value:{$bettingLog->betting_value} transformationResult:{$bettingLog->baccaratLotteryLog->transformationResult} {$bettingLog->betting_result}");
    }
}
