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

use App\Baccarat\Model\BaccaratTerrace;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Relations\HasMany;
use Mine\Abstracts\AbstractMapper;

/**
 * 台，桌Mapper类
 */
class BaccaratTerraceMapper extends AbstractMapper
{
    /**
     * @var BaccaratTerrace
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratTerrace::class;
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

        // 标识
        if (isset($params['code']) && filled($params['code'])) {
            $query->where('code', 'like', '%' . $params['code'] . '%');
        }

        // 标题
        if (isset($params['title']) && filled($params['title'])) {
            $query->where('title', 'like', '%' . $params['title'] . '%');
        }

        // 创建时间
        if (isset($params['created_at']) && filled($params['created_at']) && is_array($params['created_at']) && count($params['created_at']) == 2) {
            $query->whereBetween(
                'created_at',
                [$params['created_at'][0], $params['created_at'][1]]
            );
        }

        // 更新时间
        if (isset($params['updated_at']) && filled($params['updated_at']) && is_array($params['updated_at']) && count($params['updated_at']) == 2) {
            $query->whereBetween(
                'updated_at',
                [$params['updated_at'][0], $params['updated_at'][1]]
            );
        }

        // 删除时间
        if (isset($params['deleted_at']) && filled($params['deleted_at']) && is_array($params['deleted_at']) && count($params['deleted_at']) == 2) {
            $query->whereBetween(
                'deleted_at',
                [$params['deleted_at'][0], $params['deleted_at'][1]]
            );
        }

        // 备注
        if (isset($params['remark']) && filled($params['remark'])) {
            $query->where('remark', 'like', '%' . $params['remark'] . '%');
        }

        return $query;
    }

    public function getBaccaratTerraceOrCreateByCode(string $terraceCode): BaccaratTerrace|\Mine\MineModel
    {
        return $this->getModel()->firstOrCreate(['code' => $terraceCode], ['title' => $terraceCode]);
    }

    public function getList(?array $params, bool $isScope = true): array
    {
        return $this->listQuerySetting($params, $isScope)
            ->with(['children' => function (HasMany $query) use ($params){

                $query->whereHas('baccaratSimulatedBettingLog',function ($query) use ($params) {
                    if (isset($params['betting_id']) && filled($params['betting_id'])){
                        $query->where('betting_id',$params['betting_id']);
                    }
                });

                if (isset($params['terrace_deck_created_at']) && filled($params['terrace_deck_created_at'])) {
                    $query->whereDate('created_at', $params['terrace_deck_created_at']);
                }

                $query->with(['baccaratSimulatedBettingLog'=>function ($query) use ($params) {
                    if (isset($params['betting_id']) && filled($params['betting_id'])){
                        $query->where('betting_id',$params['betting_id']);
                    }
                }]);

                return $query->selectRaw('id,terrace_id,terrace_id as parent_id,deck_number,id as `key`');
            }])
            ->get(['id', 'title', 'code as key'])
            ->append(['parent_id'])
            ->filter(fn(BaccaratTerrace $baccaratTerrace) => $baccaratTerrace->children->isNotEmpty())
            ->each(fn(BaccaratTerrace $baccaratTerrace)=> $baccaratTerrace->children->append(['baccaratBettingSequence','title']))
            ->toArray();

    }
}