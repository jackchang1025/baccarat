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

        return $query;
    }

    public function handlePageItems(array $items,array $params): array
    {
        if (!empty($items)){
            foreach ($items as $item){
                if ($item instanceof BaccaratTerraceDeck) {
                    $item->append(['lotteryLogBankerCount', 'lotteryLogTieCount', 'lotteryLogPlayerCount', 'lotteryLogCalculateCoordinates']);
                }
            }
        }
        return $items;
    }

    private function baseBaccaratTerraceDeckQuery(int $terraceId, string $deckNumber, Carbon $startDate, Carbon $endDate): Builder|Model|null {
        return $this->getModel()
            ->where('deck_number', $deckNumber)
            ->where('terrace_id', $terraceId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->first();
    }

    public function getLastBaccaratTerraceDeckWithSubDay(int $terraceId,string $deckNumber): BaccaratTerraceDeck|Builder|null
    {
        return $this->baseBaccaratTerraceDeckQuery($terraceId, $deckNumber, Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay());
    }

    public function getBaccaratTerraceDeckWithToday(int $terraceId,string $deckNumber): BaccaratTerraceDeck|Builder|null
    {
        return $this->baseBaccaratTerraceDeckQuery($terraceId, $deckNumber, Carbon::now()->startOfDay(), Carbon::now()->endOfDay());
    }

    public function getBaccaratTerraceDeckOfTodayAndYesterdayOrCreates(int $terraceId, string $deckNumber): BaccaratTerraceDeck|null
    {
        // 如果是 20 局及以上，且是凌晨 0 到 1 点，则取昨天的数据
        if ($this->isYesterday((int) $deckNumber)){
            return $this->getLastBaccaratTerraceDeckWithSubDay($terraceId,$deckNumber);
        }
        return $this->getBaccaratTerraceDeckWithTodayOrCreate($terraceId,$deckNumber);
    }

    /**
     * @param int $deckNumber
     * @return bool
     */
    protected function isYesterday(int $deckNumber): bool
    {
        $now = Carbon::now();

        return $deckNumber >= 20 && $now->isBetween($now->copy()->setTime(0, 0, 0), $now->copy()->setTime(1, 0, 0));
    }

    public function getBaccaratTerraceDeckWithTodayOrCreate(int $terraceId,string $deckNumber): BaccaratTerraceDeck|\Hyperf\Database\Query\Builder|Model|Builder
    {
        $baccaratTerraceDeck = $this->getBaccaratTerraceDeckWithToday($terraceId,$deckNumber);

        if (is_null($baccaratTerraceDeck)) {
            return $this->getModel()->create(['terrace_id' => $terraceId, 'deck_number' => $deckNumber]);
        }
        return $baccaratTerraceDeck;
    }
}