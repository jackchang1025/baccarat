<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\Relations\HasOne;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property int $baccarat_betting_log_id 投注日志id
 * @property string $title 名称
 * @property string $rule 规则
 * @property string $betting_value 投注值
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property-read null|BaccaratSimulatedBettingLog $baccaratSimulatedBettingLog 
 */
class BaccaratBettingRuleLog extends MineModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_betting_rule_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'baccarat_betting_log_id', 'title', 'rule', 'betting_value', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'baccarat_betting_log_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function baccaratSimulatedBettingLog(): BelongsTo
    {
        return $this->BelongsTo(BaccaratSimulatedBettingLog::class, 'baccarat_betting_log_id', 'id');
    }
}
