<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use App\Baccarat\Service\Coordinates\CalculateCoordinates;
use App\Baccarat\Service\LotteryResult;
use Hyperf\Database\Model\Collection;
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
 * @property Collection|BaccaratLotteryLog[] $baccaratLotteryLog
 * @property Collection|baccaratSimulatedBettingLog[] $baccaratSimulatedBettingLog
 * @property baccaratTerrace $baccaratTerrace
 * @property Collection $calculateCoordinates
 * @property string $baccaratLotterySequence
 * @property string $baccaratBettingSequence
 */
class BaccaratTerraceDeck extends MineModel
{
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

    public function getLabelAttribute($value): string
    {
        return (string) $value;
    }

    public function getTitleAttribute($value): string
    {
        return (string) $value;
    }

//    public function getBankerCountAttribute():int
//    {
//        if ($this->baccaratLotteryLog->isNotEmpty()){
//            return $this->baccaratLotteryLog->where('transformationResult',LotteryResult::BANKER)->count();
//        }
//        return 0;
//    }
//
//    public function getPlayerCountAttribute():int
//    {
//        if ($this->baccaratLotteryLog->isNotEmpty()){
//            return $this->baccaratLotteryLog->where('transformationResult',LotteryResult::PLAYER)->count();
//        }
//        return 0;
//    }
//    public function getTieCountAttribute():int
//    {
//        if ($this->baccaratLotteryLog->isNotEmpty()){
//            return $this->baccaratLotteryLog->where('transformationResult',LotteryResult::TIE)->count();
//        }
//        return 0;
//    }

//

    public function getBaccaratLotterySequenceAttribute(): string
    {
        if ($this->baccaratLotteryLog->isNotEmpty()){
            return $this->baccaratLotteryLog->filter(
                fn(BaccaratLotteryLog $baccaratLotteryLog)=>
                $baccaratLotteryLog->transformationResult &&  $baccaratLotteryLog->transformationResult !== LotteryResult::TIE)
                ->pluck('transformationResult')
                ->implode('');
        }

        return '';
    }

    public function getBaccaratBettingSequenceAttribute(): string
    {
        if ($this->baccaratSimulatedBettingLog->isNotEmpty()){
            return $this->baccaratSimulatedBettingLog
                ->filter(fn(baccaratSimulatedBettingLog $baccaratSimulatedBettingLog)=> $baccaratSimulatedBettingLog->betting_result !== null
                    && $baccaratSimulatedBettingLog->betting_result !== LotteryResult::BETTING_TIE)
                ->pluck('betting_result')
                ->implode('');
        }

        return '';
    }
    public function getCalculateCoordinatesAttribute(): Collection
    {
        if ($this->baccaratLotteryLog->isNotEmpty()){
            $baccaratLotteryLog = new CalculateCoordinates();
            return $baccaratLotteryLog->calculateCoordinatesWithCollection(
                $this->baccaratLotteryLog->filter(fn( BaccaratLotteryLog $baccaratLotteryLog) => $baccaratLotteryLog->transformationResult)
            );
        }

        return new Collection();
    }
    /**
     * 定义 baccaratLotteryLog 关联
     * @return hasMany
     */
    public function baccaratLotteryLog() : hasMany
    {
        return $this->hasMany(BaccaratLotteryLog::class, 'terrace_deck_id', 'id');
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
