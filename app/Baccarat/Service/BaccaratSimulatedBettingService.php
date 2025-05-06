<?php
declare(strict_types=1);
/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://gitee.com/xmo/MineAdmin
 */

namespace App\Baccarat\Service;

use App\Baccarat\Mapper\BaccaratSimulatedBettingMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Redis\LotteryResultService;
use App\Baccarat\Service\Rule\RuleInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Hyperf\Database\Model\Collection;
use Hyperf\Logger\LoggerFactory;
use Mine\Abstracts\AbstractService;
use Psr\Log\LoggerInterface;
use Hyperf\Coroutine\Locker;

/**
 * 投注服务类
 */
class BaccaratSimulatedBettingService extends AbstractService
{
    /**
     * @var BaccaratSimulatedBettingMapper
     */
    public $mapper;

    protected LoggerInterface $logger;

    public function __construct(
        protected BaccaratSimulatedBettingLogService $bettingLogService,
        protected LoggerFactory $loggerFactory,
        protected Output $output,
        protected LotteryResultService $lotteryResultService,
        BaccaratSimulatedBettingMapper               $mapper,
    )
    {
        $this->mapper = $mapper;
        $this->logger = $this->loggerFactory->get('baccarat', 'baccarat');
    }

    public function getBaccaratSimulatedBettingList(array $where = ['status' => 1]): Collection|array
    {
        return $this->mapper->getBaccaratSimulatedBettingList($where);
    }

    public function betting(int $bettingId)
    {
        $betting = BaccaratSimulatedBetting::with(['baccaratSimulatedBettingRule'])->find($bettingId);
        if (!$betting) {
            return null;
        }

        //查询牌桌牌堆按照日期分组
        $BaccaratTerraceDeckDateList = BaccaratTerraceDeck::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();
        if ($BaccaratTerraceDeckDateList->isEmpty()) {
            return null;
        }

        //获取台号
        $baccaratTerraceList = BaccaratTerrace::get();
        if ($baccaratTerraceList->isEmpty()) {
            return null;
        }

        $s = microtime(true);
        //首先清除投注单投注日志
        BaccaratSimulatedBettingLog::where('betting_id', $bettingId)->delete();
        $this->output->info("BaccaratBettingRuleLog delete use s:" . number_format(microtime(true) - $s, 8));

        $parallel = new Parallel(100);

        foreach ($baccaratTerraceList as $baccaratTerrace) {

            foreach ($BaccaratTerraceDeckDateList as $BaccaratTerraceDeckDate) {

                $parallel->add(function () use ($baccaratTerrace,$BaccaratTerraceDeckDate,$betting) {

                    $this->processTerraceData($baccaratTerrace,$BaccaratTerraceDeckDate,$betting);
                });
            }
        }
        $parallel->wait();
    }

    protected function baccaratLotteryLog(BaccaratSimulatedBetting $betting, array $baccaratTerraceDeck): void
    {
        $s = microtime(true);

        $carry = new class {
            public bool $isBetting = false;
            public ?RuleInterface $rule = null;
            public string $string = '';
        };

        $lotteryLogList = array_filter($baccaratTerraceDeck['baccarat_lottery_log'],fn(array $lotteryLog) => !empty($lotteryLog['transformationResult']) && !empty($lotteryLog['RawData']));

        unset($baccaratTerraceDeck['baccarat_lottery_log']);

        array_reduce($lotteryLogList,function ($carry, array $item) use ($betting, $baccaratTerraceDeck) {

            $baccaratLotteryLog = BaccaratLotteryLog::make($item);

            if ($carry->isBetting && $carry->rule){

                $name = "lottery_lock_$baccaratLotteryLog->issue";
                if (Locker::lock($name)){
                    $this->output->warn("开始模拟投注 transformationResult:{$carry->string } title:{$carry->rule->getName()} rule:{$carry->rule->getRule()} betting_value:{$carry->rule->getBettingValue()}");

                    $bettingLog = $this->bettingLogService->getBaccaratSimulatedBettingLog($baccaratLotteryLog->issue,$betting->id);
                    if (!$bettingLog){
                        $this->bettingLogService->saveBettingLogAndRuleLog([
                            'betting_id' => $betting->id,
                            'terrace_deck_id' => $baccaratTerraceDeck['id'],
                            'created_at' => $baccaratLotteryLog->created_at,
                            'issue' => $baccaratLotteryLog->issue,
                            'betting_value' => $carry->rule->getBettingValue(),
                            'betting_result' => $baccaratLotteryLog->getLotteryResult()->checkLotteryResults($carry->rule->getBettingValue())
                        ], $carry->rule);
                    }
                    Locker::unlock($name);
                }
            }

            $carry->string .= $baccaratLotteryLog->transformationResult;
            $carry->isBetting = false;
            $carry->rule = null;

            if ($rule = $betting->getRuleEngine()->applyRulesOnce($carry->string)){
                $carry->isBetting = true;
                $carry->rule = $rule;
            }
            unset($baccaratLotteryLog);
            return $carry;
        },$carry);

        $this->output->info("baccaratTerraceDeck:{$baccaratTerraceDeck['id']} use s:".number_format(microtime(true) - $s, 8));
    }

    protected function processTerraceData(BaccaratTerrace $baccaratTerrace, BaccaratTerraceDeck $BaccaratTerraceDeckDate,BaccaratSimulatedBetting $betting): void
    {
        $baccaratTerraceDeckList = $this->lotteryResultService->hGetAll($baccaratTerrace->title, $BaccaratTerraceDeckDate->date);

        if ($baccaratTerraceDeckList?->isNotEmpty()){

            $s = microtime(true);

            $baccaratTerraceDeckList->filter(fn(array $item) => !empty($item['baccarat_lottery_log']))
                ->each(function (array $item) use ($betting){

                    $this->baccaratLotteryLog($betting,$item);
            });

            $this->output->info("{$this->lotteryResultService->getFormat($baccaratTerrace->title, $BaccaratTerraceDeckDate->date)} use s:".number_format(microtime(true) - $s, 8));

        }else{
            $this->output->warn("get data is empty redis key: {$this->lotteryResultService->getFormat($baccaratTerrace->title,$BaccaratTerraceDeckDate->date)}");
            Coroutine::sleep(0.5);
        }
    }

    public function lotteryLogBack($baccaratTerraceDeck)
    {
        $carry = new class {
            public bool $isBetting = false;
            public ?RuleInterface $rule = null;
            public string $string = '';
        };

        $baccaratTerraceDeck->baccaratLotteryLog->filter(fn(BaccaratLotteryLog $baccaratLotteryLog) => $baccaratLotteryLog->transformationResult && $baccaratLotteryLog->transformationResult !== LotteryResult::TIE)
            ->reduce(function ($carry, BaccaratLotteryLog $baccaratLotteryLog) use ($betting, $baccaratTerraceDeck) {

                if ($carry->isBetting && $carry->rule){

                    $name = "lottery_lock_$baccaratLotteryLog->issue";
                    if (Locker::lock($name)){
                        $this->output->warn("开始模拟投注 transformationResult:{$carry->string } title:{$carry->rule->getName()} rule:{$carry->rule->getRule()} betting_value:{$carry->rule->getBettingValue()}");

                        $bettingLog = $this->bettingLogService->getBaccaratSimulatedBettingLog($baccaratLotteryLog->issue,$betting->id);
                        if (!$bettingLog){
                            $this->bettingLogService->saveBettingLogAndRuleLog([
                                'betting_id' => $betting->id,
                                'terrace_deck_id' => $baccaratTerraceDeck->id,
                                'created_at' => $baccaratLotteryLog->created_at,
                                'issue' => $baccaratLotteryLog->issue,
                                'betting_value' => $carry->rule->getBettingValue(),
                                'betting_result' => $baccaratLotteryLog->getLotteryResult()->checkLotteryResults($carry->rule->getBettingValue())
                            ], $carry->rule);
                        }
                        Locker::unlock($name);
                    }
                }

                $carry->string .= $baccaratLotteryLog->transformationResult;
                $carry->isBetting = false;
                $carry->rule = null;

                if ($rule = $betting->getRuleEngine()->applyRulesOnce($carry->string)){
                    $carry->isBetting = true;
                    $carry->rule = $rule;
                }

                return $carry;
            }, $carry);
    }
}