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

use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Model\BaccaratRule;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratSimulatedBettingRule;
use App\Baccarat\Service\Rule\RuleInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Db;
use Mine\Abstracts\AbstractService;
use App\Baccarat\Model\BaccaratSimulatedBetting;

class BaccaratSimulatedBettingLogService extends AbstractService
{
    /**
     * @var BaccaratSimulatedBettingLogMapper
     */
    public $mapper;

    public function __construct(BaccaratSimulatedBettingLogMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getBaccaratSimulatedBettingLog(int $issue,?int $bettingId = null): Builder|BaccaratSimulatedBettingLog|null
    {
        return $this->mapper->getModel()
            ->with(['baccaratSimulatedBetting'])
            ->where('issue',$issue)
            ->when($bettingId,fn($query) => $query->where('betting_id',$bettingId))
            ->first();
    }

    public function updateBettingResult(LotteryResult $lotteryResult):Collection
    {
        return $this->mapper->getModel()
            ->where('issue',$lotteryResult->issue)
            ->get()
            ->filter(fn(BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog)=> !$baccaratSimulatedBettingLog->betting_result)
            ->each(fn (BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog) => $baccaratSimulatedBettingLog->update(['betting_result' => $lotteryResult->checkLotteryResults($baccaratSimulatedBettingLog->betting_value)]));
    }

    public function createBettingLogAndRuleLog(array $data,BaccaratSimulatedBettingRule $baccaratRule): BaccaratSimulatedBettingLog|Model
    {
        /**
         * @var BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog
         */
        $baccaratSimulatedBettingLog = $this->mapper->getModel()->create($data);

        $baccaratSimulatedBettingLog->baccaratBettingRuleLog()->create([
            'title' => $baccaratRule->title,
            'rule' => $baccaratRule->rule,
            'betting_value' => $baccaratRule->betting_value,
        ]);
        return $baccaratSimulatedBettingLog;
    }

    public function saveBettingLogAndRuleLog(array $data,RuleInterface $rule): BaccaratSimulatedBettingLog|Model
    {

        //使用事务
//        return Db::transaction(function () use ($data,$rule){
//
//
//        },3);

        /**
         * @var BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog
         */
        $baccaratSimulatedBettingLog = $this->mapper->getModel()->create($data);

        $baccaratSimulatedBettingLog->baccaratBettingRuleLog()->create([
            'title' => $rule->getName(),
            'rule' => $rule->getRule(),
            'created_at' => $baccaratSimulatedBettingLog->created_at,
            'betting_value' => $rule->getBettingValue(),
        ]);

        return $baccaratSimulatedBettingLog;
    }
}