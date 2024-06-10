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

namespace App\Baccarat\Mapper;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Rule\RuleInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractMapper;
use function Hyperf\Tappable\tap;

/**
 * 投注日志表Mapper类
 */
class BaccaratSimulatedBettingLogMapper extends AbstractMapper
{
    /**
     * @var BaccaratSimulatedBettingLog
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratSimulatedBettingLog::class;
    }

    /**
     * 搜索处理器
     * @param Builder $query
     * @param array $params
     * @return Builder
     */
    public function handleSearch(Builder $query, array $params): Builder
    {

        // 主键
        if (isset($params['id']) && filled($params['id'])) {
            $query->where('id', '=', $params['id']);
        }

        // 投注id
        if (isset($params['betting_id']) && filled($params['betting_id'])) {
            $query->where('betting_id', '=', $params['betting_id']);
        }

        // 投注id
        if (isset($params['terrace_deck_id']) && filled($params['terrace_deck_id'])) {
            $query->where('terrace_deck_id', '=', $params['terrace_deck_id']);
        }

        // 期号
        if (isset($params['issue']) && filled($params['issue'])) {
            $query->where('issue', 'like', '%' . $params['issue'] . '%');
        }

        // 投注值
        if (isset($params['betting_value']) && filled($params['betting_value'])) {
            $query->where('betting_value', 'like', '%' . $params['betting_value'] . '%');
        }

        // 投注结果
        if (isset($params['betting_result']) && filled($params['betting_result'])) {
            $query->where('betting_result', 'like', '%' . $params['betting_result'] . '%');
        }

        // 状态 (1正常 2停用)
        if (isset($params['status']) && filled($params['status'])) {
            $query->where('status', '=', $params['status']);
        }

        // 备注
        if (isset($params['remark']) && filled($params['remark'])) {
            $query->where('remark', 'like', '%' . $params['remark'] . '%');
        }

        // 创建时间
        if (isset($params['created_at']) && filled($params['created_at']) && is_array($params['created_at']) && count($params['created_at']) == 2) {
            $query->whereBetween(
                'created_at',
                [$params['created_at'][0], $params['created_at'][1]]
            );
        }

        // 更新时间
        if (isset($params['updated_at']) && filled($params['updated_at']) && is_array($params['updated_at']) && count($params['updated_at']) == 2) {
            $query->whereBetween(
                'updated_at',
                [$params['updated_at'][0], $params['updated_at'][1]]
            );
        }

        // 投注日志规则id
        if (isset($params['betting_rule_log_id']) && filled($params['betting_rule_log_id'])) {

            $query->whereHas('baccaratBettingRuleLog', function ($query) use ($params) {
                $query->where('id', '=', $params['betting_rule_log_id']);
            });
        }

        return $query;
    }

    public function getBaccaratSimulatedBettingLogOrCreate(RuleInterface $rule, array $attributes, array $values = []): BaccaratSimulatedBettingLog
    {
        return tap($this->getModel()->firstOrCreate($attributes, $values), function (BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog) use ($rule) {
            if ($baccaratSimulatedBettingLog->wasRecentlyCreated) {
                $baccaratSimulatedBettingLog->baccaratBettingRuleLog()->create([
                    'title'         => $rule->getName(),
                    'rule'          => $rule->getRule(),
                    'created_at'    => $baccaratSimulatedBettingLog->created_at,
                    'betting_value' => $rule->getBettingValue(),
                ]);
            }
        });
    }

    public function getBettingLogListBettingResultWhereNull(string $issue): Collection
    {
        return $this->getModel()
            ->where('issue', $issue)
            ->whereNull('betting_result')
            ->get();
    }
}