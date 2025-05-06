<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use App\Baccarat\Service\LotteryResult;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\Database\Model\SoftDeletes;
use Mine\MineModel;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;


/**
 * @property int $id 主键
 * @property int $issue 期号
 * @property int $terrace_deck_id 牌靴id
 * @property string $transformationResult
 * @property string $result
 * @property array $RawData
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $deleted_at 删除时间
 * @property string $remark 备注
 * @property BaccaratTerraceDeck $baccaratTerraceDeck
 * @property-read null|BaccaratSimulatedBettingLog $bettingLog
 */
class BaccaratLotteryLog extends MineModel 
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_lottery_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'terrace_deck_id', 'issue','result','transformationResult','RawData', 'created_at', 'updated_at', 'deleted_at', 'remark'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer','issue' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime','RawData'=>'json'];

    public function baccaratTerraceDeck(): BelongsTo
    {
        return $this->belongsTo(BaccaratTerraceDeck::class, 'terrace_deck_id', 'id');
    }

    public function baccaratSimulatedBettingLog(): HasMany
    {
        return $this->hasMany(BaccaratSimulatedBettingLog::class, 'issue', 'issue');
    }

    public function bettingLog(): HasOne
    {
        return $this->hasOne(BaccaratSimulatedBettingLog::class, 'issue', 'issue');
    }

    public function getLotteryResult(): LotteryResult
    {
       return LotteryResult::fromArray((string) $this->terrace_deck_id, $this->RawData ?? []);
    }
}
