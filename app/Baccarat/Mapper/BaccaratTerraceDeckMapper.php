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

use App\Baccarat\Model\BaccaratTerraceDeck;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractMapper;

/**
 * 牌靴Mapper类
 */
class BaccaratTerraceDeckMapper extends AbstractMapper
{
    /**
     * @var BaccaratTerraceDeck
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratTerraceDeck::class;
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

        // 台
        if (isset($params['terrace_id']) && filled($params['terrace_id'])) {
            $query->where('terrace_id', '=', $params['terrace_id']);
        }

        // 牌靴编号
        if (isset($params['deck_number']) && filled($params['deck_number'])) {
            $query->where('deck_number', 'like', '%' . $params['deck_number'] . '%');
        }

        // 开奖序列
        if (isset($params['lottery_sequence']) && filled($params['lottery_sequence'])) {
//            $query->where('lottery_sequence', 'like', '%'.$params['lottery_sequence'].'%');
            $query->where('lottery_sequence', 'REGEXP', $params['lottery_sequence']);
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

        // 备注
        if (isset($params['remark']) && filled($params['remark'])) {
            $query->where('remark', 'like', '%' . $params['remark'] . '%');
        }

        $query->with(['baccaratLotteryLog']);

        return $query;
    }

    public function getLastBaccaratTerraceDeck(int $terraceId): BaccaratTerraceDeck|Builder|null
    {
        return $this->getModel()
            ->where('terrace_id', $terraceId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * @param int $terraceId
     * @return Builder|Model
     */
    public function getLastBaccaratTerraceDeckOrCreate(int $terraceId): BaccaratTerraceDeck|Builder
    {
        return $this->getModel()
            ->where('terrace_id', $terraceId)
            ->orderBy('created_at', 'desc')
            ->firstOrCreate(['terrace_id' => $terraceId]);
    }

    public function getBaccaratTerraceDeckWithToday(int $terraceId,string $deckNumber): BaccaratTerraceDeck|Builder|null
    {
        return $this->getModel()
            ->where('deck_number', $deckNumber)
            ->where('terrace_id', $terraceId)
            ->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])
            ->first();
    }

    public function getBaccaratTerraceDeckWithTodayOrCreate(int $terraceId,string $deckNumber): BaccaratTerraceDeck|\Hyperf\Database\Query\Builder|Model|Builder
    {
        $baccaratTerraceDeck = $this->getBaccaratTerraceDeckWithToday($terraceId,$deckNumber);

        return transform($baccaratTerraceDeck,
            fn($baccaratTerraceDeck) => $baccaratTerraceDeck,
            fn()=>$this->getModel()->create(['terrace_id'=>$terraceId, 'deck_number'=>$deckNumber])
        );
    }

}