<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use App\Baccarat\Service\Rule\RuleEngine;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsToMany;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Testing\Concerns\InteractsWithModelFactory;
use Mine\MineModel;
use function PHPUnit\Framework\isNull;

/**
 * @property int $id 主键
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $title 名称
 * @property string $betting_sequence 投递序列
 * @property int $status 状态 (1正常 2停用)
 * @property int $sort 排序
 * @property string $remark 备注
 * @property Collection|BaccaratSimulatedBettingRule[] $baccaratSimulatedBettingRule
 * @property Collection|BaccaratSimulatedBettingLog[] $baccaratSimulatedBettingLog
 */
class BaccaratSimulatedBetting extends MineModel
{

    protected ?RuleEngine $ruleEngine = null;
//    public array $appends = ['baccaratSimulatedBettingRule'];
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_simulated_betting';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'created_at', 'updated_at', 'title', 'betting_sequence', 'status', 'sort', 'remark'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'status' => 'integer', 'sort' => 'integer'];

    public function baccaratSimulatedBettingRule(): BelongsToMany
    {
        return $this->belongsToMany(BaccaratSimulatedBettingRule::class, 'baccarat_simulated_betting_rule', 'betting_id', 'rule_id');
    }

    public function baccaratSimulatedBettingLog(): HasMany
    {
        return $this->hasMany(BaccaratSimulatedBettingLog::class, 'betting_id', 'id');
    }

    public function getRuleEngine():RuleEngine
    {
        if (isNull($this->ruleEngine)){
            $this->ruleEngine = make(RuleEngine::class);
            $this->baccaratSimulatedBettingRule->each(fn (BaccaratSimulatedBettingRule $bettingRule) => $this->ruleEngine->addRule($bettingRule->getRule()));
        }

        return $this->ruleEngine;
    }
}
