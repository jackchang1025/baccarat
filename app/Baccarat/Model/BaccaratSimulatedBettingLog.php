<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Sequence\Sequence;
use Hyperf\Database\Model\RelationNotFoundException;
use Hyperf\Database\Model\Relations\belongsTo;
use Hyperf\Database\Model\Relations\HasOne;
use InvalidArgumentException;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property int $betting_id 投注id
 * @property int $terrace_deck_id 牌靴id
 * @property string $issue 期号
 * @property string $betting_value 投注值
 * @property string $betting_result 投注结果
 * @property int $status 状态 (1正常 2停用)
 * @property string $remark 备注
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
    protected array $fillable = ['id', 'betting_id','terrace_deck_id','issue', 'betting_value', 'betting_result', 'status', 'remark', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'betting_id' => 'integer','issue' => 'integer','terrace_deck_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function deleting():void
    {
        $this->baccaratBettingRuleLog()->delete();
    }

    public function setBettingResultAttribute($value): string
    {
        if (!empty($value) && !in_array($value, [LotteryResult::BETTING_WIN, LotteryResult::BETTING_LOSE,LotteryResult::BETTING_TIE])){
            throw new InvalidArgumentException("Invalid character in sequence: $value");
        }

        $this->attributes['betting_result'] = $value;
        return $value;
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

    public function baccaratLotteryLog() : HasOne
    {
        if (!$this->created_at){
            throw RelationNotFoundException::make($this->getModel(), 'baccaratLotteryLog');
        }

        $instance = (new BaccaratLotteryLog)->getShardingModel($this->created_at);

        return $this->newHasOne(
            $instance->newQuery(),
            $this,
            sprintf("%s.%s", $instance->getTable(), 'issue'),
            'issue',
        );
    }

    public function isWin(): bool
    {
        return $this->betting_result === Sequence::WIN->value;
    }

}
