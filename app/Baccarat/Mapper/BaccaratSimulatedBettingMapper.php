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

use App\Baccarat\Model\BaccaratSimulatedBetting;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Mine\Abstracts\AbstractMapper;
use Mine\MineModel;

/**
 * 投注Mapper类
 */
class BaccaratSimulatedBettingMapper extends AbstractMapper
{
    /**
     * @var BaccaratSimulatedBetting
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratSimulatedBetting::class;
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

        // 名称
        if (isset($params['title']) && filled($params['title'])) {
            $query->where('title', 'like', '%'.$params['title'].'%');
        }

        // 投递序列
        if (isset($params['betting_sequence']) && filled($params['betting_sequence'])) {
            $query->where('betting_sequence', '=', $params['betting_sequence']);
        }

        // 状态 (1正常 2停用)
        if (isset($params['status']) && filled($params['status'])) {
            $query->where('status', '=', $params['status']);
        }

        // 排序
        if (isset($params['sort']) && filled($params['sort'])) {
            $query->where('sort', '=', $params['sort']);
        }

        // 备注
        if (isset($params['remark']) && filled($params['remark'])) {
            $query->where('remark', 'like', '%'.$params['remark'].'%');
        }

        return $query;
    }

    public function read(mixed $id, array $column = ['*']): ?MineModel
    {
        return ($model = $this->model::with(['baccaratSimulatedBettingRule'])->find($id, $column)) ? $model : null;
    }

    public function update(mixed $id, array $data):bool
    {

        $simulatedBettingRoleList = $data['simulated_betting_role_list'] ?? [];

        $this->filterExecuteAttributes($data, true);

        $baccaratSimulatedBetting = $this->model::find($id);

        return $baccaratSimulatedBetting
            && $baccaratSimulatedBetting->update($data)
            && $simulatedBettingRoleList
            && $baccaratSimulatedBetting->baccaratSimulatedBettingRule()->sync($simulatedBettingRoleList);

    }

    public function save(array $data): mixed
    {
        $role_ids = $data['simulated_betting_role_list'] ?? [];
        $this->filterExecuteAttributes($data, true);

        $user = $this->model::create($data);
        $user->baccaratSimulatedBettingRule()->sync($role_ids, false);
        return $user->id;
    }

    public function getBaccaratSimulatedBettingList(array $where = ['status' => 1]): Collection|array
    {
        return $this->getModel()->with(['baccaratSimulatedBettingRule'])->where($where)->get();
    }
}