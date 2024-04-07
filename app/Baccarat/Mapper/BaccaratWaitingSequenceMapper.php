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

use App\Baccarat\Model\BaccaratWaitingSequence;
use Hyperf\Database\Model\Builder;
use Mine\Abstracts\AbstractMapper;

/**
 * 表注释Mapper类
 */
class BaccaratWaitingSequenceMapper extends AbstractMapper
{
    /**
     * @var BaccaratWaitingSequence
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratWaitingSequence::class;
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

        // 序列
        if (isset($params['sequence']) && filled($params['sequence'])) {
            $query->where('sequence', 'like', '%'.$params['sequence'].'%');
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

        // 删除时间
        if (isset($params['deleted_at']) && filled($params['deleted_at']) && is_array($params['deleted_at']) && count($params['deleted_at']) == 2) {
            $query->whereBetween(
                'deleted_at',
                [ $params['deleted_at'][0], $params['deleted_at'][1] ]
            );
        }

        // 备注
        if (isset($params['remark']) && filled($params['remark'])) {
            $query->where('remark', 'like', '%'.$params['remark'].'%');
        }

        return $query;
    }
}