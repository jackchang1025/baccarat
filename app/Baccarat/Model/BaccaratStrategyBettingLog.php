<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use Hyperf\Database\Model\Relations\BelongsTo;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property int $simulated_betting_id 模拟投注ID
 * @property int $terrace_deck_id 牌靴ID
 * @property string $strategy_type 策略类型
 * @property int $issue 轮次
 * @property float $bet_amount 投注金额
 * @property float $balance 当前余额
 * @property string $result 结果
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class BaccaratStrategyBettingLog extends MineModel
{
    protected ?string $table = 'baccarat_strategy_betting_logs';
    
    protected array $fillable = [
        'simulated_betting_id',
        'terrace_deck_id',
        'strategy_type',
        'issue',
        'bet_amount',
        'balance',
        'result'
    ];
    
    protected array $casts = [
        'simulated_betting_id' => 'integer',
        'terrace_deck_id' => 'integer',
        'round' => 'integer',
        'bet_amount' => 'float',
        'balance' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function simulatedBetting(): BelongsTo
    {
        return $this->belongsTo(BaccaratSimulatedBetting::class, 'simulated_betting_id', 'id');
    }
    
    public function terraceDeck(): BelongsTo
    {
        return $this->belongsTo(BaccaratTerraceDeck::class, 'terrace_deck_id', 'id');
    }
} 