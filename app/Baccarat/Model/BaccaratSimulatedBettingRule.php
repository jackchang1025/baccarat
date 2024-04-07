<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use App\Baccarat\Service\Rule\CustomizeRules;
use App\Baccarat\Service\Rule\RuleEngine;
use App\Baccarat\Service\Rule\RuleInterface;
use Hyperf\Database\Model\Relations\BelongsToMany;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $title 名称
 * @property string $rule 规则
 * @property string $betting_value 投注值
 * @property int $status 状态 (1正常 2停用)
 * @property int $sort 排序
 * @property string $remark 备注
 */
class BaccaratSimulatedBettingRule extends MineModel
{
    protected ?ruleInterface $ruleInterface = null;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_simulated_rule';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'created_at', 'updated_at', 'title', 'rule', 'betting_value', 'status', 'sort', 'remark'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'status' => 'integer', 'sort' => 'integer'];

    public function baccaratSimulatedBetting(): BelongsToMany
    {
        return $this->belongsToMany(BaccaratSimulatedBettingRule::class, 'baccarat_simulated_betting_rule', 'baccarat_simulated_rule_id', 'baccarat_simulated_betting_id');
    }

    public function getRule():RuleInterface
    {
        if (is_null($this->ruleInterface)){
            $this->ruleInterface = new CustomizeRules(pattern: trim($this->rule), bettingValue: $this->betting_value, name: $this->title);
        }
        return $this->ruleInterface;
    }
}
