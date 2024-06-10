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

use App\Baccarat\Model\BaccaratLotteryLog;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractMapper;
use Mine\MineModel;

/**
 * 规则Mapper类
 */
class BaccaratLotteryLogMapper extends AbstractMapper
{
    /**
     * @var BaccaratLotteryLog
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratLotteryLog::class;
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
        return $query;
    }

    public function find(array $condition, array $column = ['*'], ?Carbon $date = null): BaccaratLotteryLog|Model|null
    {
        return $this->getModel($date)->where($condition)->first($column);
    }

    public function all(?\Closure $closure = null, array $column = ['*'], ?Carbon $date = null): array|Collection
    {
        return $this->getModel($date)->where(function ($query) use ($closure) {
            if ($closure instanceof \Closure) {
                $closure($query);
            }
        })->get($column);
    }

    public function firstOrCreate(array $attributes, $values = [], ?Carbon $date = null): BaccaratLotteryLog|Model
    {
        return $this->getModel($date)->firstOrCreate($attributes, $values);
    }

    public function create(array $data, ?Carbon $date = null): BaccaratLotteryLog|Model
    {
        return $this->getModel($date)->create($data);
    }

    public function updateOrCreate(array $attributes, array $data, ?Carbon $date = null): BaccaratLotteryLog|Model|null
    {
        return $this->getModel($date)->updateOrCreate($attributes, $data);
    }

    public function getModel(?Carbon $date = null): MineModel
    {
        $model = parent::getModel();

        $model->getShardingModel($date);

        return $model;
    }
}