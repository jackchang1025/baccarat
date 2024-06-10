<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Mapper\BaccaratLotteryLogMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratLotteryLogBack;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Coroutine\Concurrent;
use Hyperf\Database\Model\Collection;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;

#[Command]
class UpdateLotteryLogTransformationResult extends HyperfCommand
{
    protected Concurrent $concurrent;

    public function __construct(protected ContainerInterface $container,protected BaccaratLotteryLogMapper $lotteryLogMapper)
    {
        parent::__construct('baccarat:update:LotteryLog');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('update all lottery log');
    }

    public function handle()
    {
        Runtime::enableCoroutine(true);

        $this->info("start run");

        $s = microtime(true);

        $this->concurrent = new Concurrent(100);

        $this->updateTransformationResults();
        return;

        //获取百家乐开奖日志表 1000 条记录
        BaccaratLotteryLogBack::chunk(1000,function (Collection $bettingLogs){

            $s = microtime(true);

            $bettingLogs->each(function (BaccaratLotteryLogBack $lotteryLog){
                $this->concurrent->create(function () use ($lotteryLog){
                    $this->updateTransformationResult($lotteryLog);
                });
            });

            $this->info("done 1000 use s:".number_format(microtime(true) - $s, 8));
        });

        $this->info("done use s:".number_format(microtime(true) - $s, 8));
    }

    public function updateTransformationResult(BaccaratLotteryLogBack $lotteryLog): void
    {

        try {

            // 根据 BaccaratLotteryLogBack 的 created_at 获取对应的分表模型
            $lotteryLogModel = $this->lotteryLogMapper->getModel()
                ->getShardingModel($lotteryLog->created_at);// 判断对应分表中是否存在开奖记录

            $lotteryLogNew = $lotteryLogModel->where('issue', $lotteryLog->issue)->first();

            if (!$lotteryLogNew && $lotteryResult = $lotteryLog->getLotteryResult()->getTransformationResult()) {
                //或取开奖结果
                $lotteryLogModel->create([
                    'terrace_deck_id' => $lotteryLog->terrace_deck_id,
                    'issue' => $lotteryLog->issue,
                    'result' => $lotteryLog->result,
                    'RawData' => $lotteryLog->RawData,
                    'transformationResult' => $lotteryResult,
                    'created_at' => $lotteryLog->created_at,
                    'updated_at' => $lotteryLog->updated_at,
                ]);
            }

        } catch (\Exception $e) {

            $this->error($e);
        }
    }

    public function updateTransformationResults(): void
    {
        $this->concurrent = new Concurrent(100);

        $this->lotteryLogMapper->getModel()
            ->whereNull('transformationResult')
            ->whereNotNull('RawData')
            ->chunk(1000,function (Collection $lotteryLogs){

                $lotteryLogs->each(function (BaccaratLotteryLog $lotteryLog){

                    $this->concurrent->create(fn()=>$this->updateLotteryLog($lotteryLog));
                });
        });
    }

    public function updateLotteryLog(BaccaratLotteryLog $lotteryLog): void
    {
        $lotteryLog->transformationResult = $lotteryLog->getLotteryResult()->getTransformationResult();
        $lotteryLog->save();
        $this->info("update lotteryLog:{$lotteryLog->id} transformationResult:{$lotteryLog->transformationResult}");
    }
}
