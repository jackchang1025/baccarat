<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use Hyperf\Database\Model\Relations\belongsTo;
use Hyperf\Database\Model\Relations\HasOne;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property int $baccarat_simulated_betting_id 投注id
 * @property string $issue 期号
 * @property string $betting_value 投注值
 * @property string $betting_result 投注结果
 * @property int $status 状态 (1正常 2停用)
 * @property string $remark 备注
 * @property string $credibility 信誉值
 * @property string $confidence 信心值
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property baccaratSimulatedBetting $baccaratSimulatedBetting 投注单
 * @property BaccaratBettingRuleLog $baccaratBettingRuleLog 规则
 * @property BaccaratTerraceDeck $baccaratTerraceDeck 牌靴
 * @property BaccaratLotteryLog $baccaratLotteryLog 开奖日志
 */
class BaccaratSimulatedBettingLog extends MineModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_betting_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'betting_id','terrace_deck_id','issue', 'betting_value', 'betting_result', 'status', 'remark', 'created_at', 'updated_at','credibility','confidence'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'betting_id' => 'integer','terrace_deck_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function deleting():void
    {
        $this->baccaratBettingRuleLog()->delete();
    }

    /**
     * 定义 BaccaratSimulatedBetting 关联
     * @return belongsTo
     */
    public function baccaratSimulatedBetting() : belongsTo
    {
        return $this->belongsTo(BaccaratSimulatedBetting::class, 'betting_id', 'id');
    }

    public function baccaratTerraceDeck() : belongsTo
    {
        return $this->belongsTo(BaccaratTerraceDeck::class, 'terrace_deck_id', 'id');
    }

    public function baccaratBettingRuleLog(): HasOne
    {
        return $this->HasOne(BaccaratBettingRuleLog::class, 'baccarat_betting_log_id', 'id');
    }
    public function baccaratLotteryLog() : belongsTo
    {
        return $this->belongsTo(BaccaratLotteryLog::class, 'issue', 'issue');
    }

}
