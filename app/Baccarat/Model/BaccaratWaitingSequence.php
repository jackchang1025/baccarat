<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use Hyperf\Database\Model\SoftDeletes;
use Mine\MineModel;

/**
 * @property int $id 主键
 * @property string $title 名称
 * @property string $sequence 序列
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $deleted_at 删除时间
 * @property string $remark 备注
 */
class BaccaratWaitingSequence extends MineModel
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_waiting_sequence';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'title', 'sequence', 'created_at', 'updated_at', 'deleted_at', 'remark'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
