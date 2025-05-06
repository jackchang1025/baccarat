<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use App\Baccarat\Service\Rule\RuleEngine;
use Hyperf\Database\Model\Relations\BelongsToMany;
use Hyperf\Database\Model\Relations\HasMany;
use Mine\MineModel;
use function PHPUnit\Framework\isNull;

/**
 * @property int $id 主键
 * @property string $title 名称
 * @property string $betting_sequence 投注序列
 * @property float $initial_amount 初始金额
 * @property float $default_bet 默认投注金额
 * @property float $stop_win 止盈金额
 * @property float $stop_loss 止损金额
 * @property array $strategy_types 策略类型集合
 * @property int $status 状态
 * @property int $sort 排序
 * @property string $remark 备注
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class BaccaratSimulatedBetting extends MineModel
{
    protected ?string $table = 'baccarat_simulated_betting';
    
    protected array $fillable = [
        'title',
        'betting_sequence',
        'initial_amount',
        'default_bet',
        'stop_win',
        'stop_loss',
        'strategy_types',
        'status',
        'sort',
        'remark'
    ];
    
    protected array $casts = [
        'initial_amount' => 'float',
        'default_bet' => 'float',
        'stop_win' => 'float',
        'stop_loss' => 'float',
        'strategy_types' => 'array',
        'status' => 'integer',
        'sort' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function baccaratSimulatedBettingRule(): BelongsToMany
    {
        return $this->belongsToMany(BaccaratSimulatedBettingRule::class, 'baccarat_simulated_betting_rule', 'betting_id', 'rule_id');
    }

    public function baccaratSimulatedBettingLog(): HasMany
    {
        return $this->hasMany(BaccaratSimulatedBettingLog::class, 'betting_id', 'id');
    }

}
