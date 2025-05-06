<?php

declare(strict_types=1);

namespace App\Baccarat\Model;

use Mine\MineModel;

/**
 * @property int $id 主键
 * @property int $terrace_id 房间ID
 * @property int $terrace_deck_id 牌靴ID
 * @property int $deck_number 牌靴号
 * @property int $total_bets 总投注次数
 * @property int $total_wins 总胜利次数
 * @property int $total_losses 总失败次数
 * @property int $total_ties 总和局次数
 * @property float $total_win_rate 总胜率
 * @property array $credibility_stats 可信度统计数据
 * @property string $betting_sequence 投注结果序列
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class BaccaratDeckStatistics extends MineModel
{
    protected ?string $table = 'baccarat_deck_statistics';
    
    protected array $fillable = [
        'terrace_id',
        'terrace_deck_id',
        'deck_number',
        'total_bets',
        'total_wins',
        'total_losses',
        'total_ties',
        'total_win_rate',
        'credibility_stats',
        'betting_sequence'
    ];
    
    protected array $casts = [
        'credibility_stats' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
} 