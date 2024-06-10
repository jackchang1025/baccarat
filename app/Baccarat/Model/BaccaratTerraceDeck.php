<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use App\Baccarat\Service\Coordinates\CalculateCoordinates;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\RelationNotFoundException;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\hasMany;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property int $terrace_id 台
 * @property int $deck_number 牌靴编号
 * @property string $lottery_sequence 开奖序列
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $remark 备注
 * @property string $label
 * @property string $title
 * @property Collection|baccaratSimulatedBettingLog[] $baccaratSimulatedBettingLog
 * @property Collection|BaccaratLotteryLog[] $baccaratLotteryLog
 * @property baccaratTerrace $baccaratTerrace
 * @property Collection $lotteryLogCalculateCoordinates
 * @property string $baccaratLotterySequence
 * @property string $baccaratBettingSequence
 * @property int $lotteryLogBankerCount
 * @property int $lotteryLogTieCount
 * @property int $lotteryLogPlayerCount
 */
class BaccaratTerraceDeck extends MineModel
{

    protected ?string $baccaratLotterySequence = null;
    protected ?string $baccaratBettingSequence = null;

    protected ?Collection $lotteryLogCalculateCoordinates = null;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_terrace_deck';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'terrace_id', 'deck_number', 'lottery_sequence', 'created_at', 'updated_at', 'remark'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'deck_number'=>'integer','terrace_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function getLabelAttribute(): string
    {
        return (string) $this->deck_number;
    }

    public function getTitleAttribute(): string
    {
        return (string) $this->deck_number;
    }

    public function getBaccaratLotterySequenceAttribute(): string
    {
        if ($this->baccaratLotterySequence !== null){
            return $this->baccaratLotterySequence;
        }
        if ($this->baccaratLotteryLog->isNotEmpty()){
            return $this->baccaratLotterySequence = $this->baccaratLotteryLog->filter(
                fn(BaccaratLotteryLog $baccaratLotteryLog)=>
                $baccaratLotteryLog->transformationResult &&  $baccaratLotteryLog->transformationResult !== LotteryResult::TIE)
                ->pluck('transformationResult')
                ->implode('');
        }

        return '';
    }

    public function getBaccaratBettingSequenceAttribute(): string
    {
        if ($this->baccaratBettingSequence !== null){
            return $this->baccaratBettingSequence;
        }

        if ($this->baccaratSimulatedBettingLog->isNotEmpty()){
            return $this->baccaratBettingSequence = $this->baccaratSimulatedBettingLog
                ->filter(fn(baccaratSimulatedBettingLog $baccaratSimulatedBettingLog)=> $baccaratSimulatedBettingLog->betting_result !== null
                    && $baccaratSimulatedBettingLog->betting_result !== LotteryResult::BETTING_TIE)
                ->pluck('betting_result')
                ->implode('');
        }

        return '';
    }
    public function getLotteryLogCalculateCoordinatesAttribute(): Collection
    {
        if ($this->lotteryLogCalculateCoordinates instanceof Collection){
            return $this->lotteryLogCalculateCoordinates;
        }
        if ($this->baccaratLotteryLog->isNotEmpty()){

            $calculateCoordinatesWithCollection = (new CalculateCoordinates())->calculateCoordinatesWithCollection(
                $this->baccaratLotteryLog->filter(fn(BaccaratLotteryLog $baccaratLotteryLog) => $baccaratLotteryLog->transformationResult)
            );
            return $this->lotteryLogCalculateCoordinates = $calculateCoordinatesWithCollection->values();
        }

        return new Collection();
    }

    public function getLotteryLogBankerCountAttribute():int
    {
        return $this->baccaratLotteryLog->where('transformationResult',LotteryResult::BANKER)->count();
    }

    public function getLotteryLogTieCountAttribute():int
    {
        return $this->baccaratLotteryLog->where('transformationResult',LotteryResult::TIE)->count();
    }

    public function getLotteryLogPlayerCountAttribute():int
    {
        return $this->baccaratLotteryLog->where('transformationResult',LotteryResult::PLAYER)->count();
    }

    /**
     * 定义 baccaratLotteryLog 关联,因为 baccaratLotteryLog 模型使用时间分表，所以需要通过 created_at 来获取分表表名
     * baccaratLotteryLog 定义的关联关系不支持模型预加载，因为此时 $this->created_at 为 null 无法获取分表表名
     * @return hasMany
     */
    public function baccaratLotteryLog(): hasMany
    {
        if (!$this->created_at){
            throw RelationNotFoundException::make($this->getModel(), 'baccaratLotteryLog');
        }

        $instance = (new BaccaratLotteryLog)->getShardingModel($this->created_at);

        return $this->newHasMany(
            $instance->newQuery(),
            $this,
            "{$instance->getTable()}.terrace_deck_id",
            $this->getKeyName()
        );
    }

    public function baccaratSimulatedBettingLog() : hasMany
    {
        return $this->hasMany(BaccaratSimulatedBettingLog::class, 'terrace_deck_id', 'id');
    }

    public function baccaratTerrace() : belongsTo
    {
        return $this->belongsTo(BaccaratTerrace::class, 'terrace_id', 'id');
    }
}
