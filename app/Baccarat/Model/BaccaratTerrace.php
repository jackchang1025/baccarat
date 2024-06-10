<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property string $code 标识
 * @property string $title 标题
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $deleted_at 删除时间
 * @property string $remark 备注
 * @property Collection|BaccaratTerraceDeck[] $children 牌靴
 * @property Collection|BaccaratTerraceDeck[] $baccaratTerraceDeck 牌靴
 */
class BaccaratTerrace extends MineModel implements CacheableInterface
{
    use Cacheable;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_terrace';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'code', 'title', 'created_at', 'updated_at', 'deleted_at', 'remark'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function getParentIdAttribute(): int
    {
        return 0;
    }

    public function baccaratTerraceDeck(): HasMany
    {
        return $this->hasMany(BaccaratTerraceDeck::class, 'terrace_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BaccaratTerraceDeck::class, 'terrace_id', 'id');
    }
}
