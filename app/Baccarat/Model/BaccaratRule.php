<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use Mine\MineModel;

/**
 * @property int $id 主键
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $title 名称
 * @property string $rule 规则
 * @property string $remark 备注
 */
class BaccaratRule extends MineModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'baccarat_rule';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'created_at', 'updated_at', 'title', 'rule', 'remark'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
