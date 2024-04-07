<?php
declare(strict_types=1);
/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://gitee.com/xmo/MineAdmin
 */

namespace App\Baccarat\Mapper;

use App\Baccarat\Model\BaccaratBettingRuleLog;
use Hyperf\Database\Model\Builder;
use Mine\Abstracts\AbstractMapper;

/**
 * 投注日志规则表Mapper类
 */
class BaccaratBettingRuleMapper extends AbstractMapper
{
    /**
     * @var BaccaratBettingRuleLog
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratBettingRuleLog::class;
    }

    /**
     * 搜索处理器
     * @param Builder $query
     * @param array $params
     * @return Builder
     */
    public function handleSearch(Builder $query, array $params): Builder
    {
        
        // 主键
        if (isset($params['id']) && filled($params['id'])) {
            $query->where('id', '=', $params['id']);
        }

        // 名称
        if (isset($params['title']) && filled($params['title'])) {
            $query->where('title', 'like', '%'.$params['title'].'%');
        }

        // 规则
        if (isset($params['rule']) && filled($params['rule'])) {
            $query->where('rule', 'like', '%'.$params['rule'].'%');
        }

        // 投注值
        if (isset($params['betting_value']) && filled($params['betting_value'])) {
            $query->where('betting_value', 'like', '%'.$params['betting_value'].'%');
        }

        // 创建时间
        if (isset($params['created_at']) && filled($params['created_at']) && is_array($params['created_at']) && count($params['created_at']) == 2) {
            $query->whereBetween(
                'created_at',
                [ $params['created_at'][0], $params['created_at'][1] ]
            );
        }

        // 更新时间
        if (isset($params['updated_at']) && filled($params['updated_at']) && is_array($params['updated_at']) && count($params['updated_at']) == 2) {
            $query->whereBetween(
                'updated_at',
                [ $params['updated_at'][0], $params['updated_at'][1] ]
            );
        }

        return $query;
    }
}