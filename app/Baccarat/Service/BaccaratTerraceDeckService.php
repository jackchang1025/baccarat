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

namespace App\Baccarat\Service;

use App\Baccarat\Mapper\BaccaratTerraceDeckMapper;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\Coordinates\CalculateCoordinates;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractService;
use Mine\MineModel;

/**
 * 牌靴服务类
 */
class BaccaratTerraceDeckService extends AbstractService
{
    /**
     * @var BaccaratTerraceDeckMapper
     */
    public $mapper;

    public function __construct(BaccaratTerraceDeckMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getBaccaratTerraceDeckListWithBettingLog(int $terraceDeckId): ?BaccaratTerraceDeck
    {
        /**
         * @var BaccaratTerraceDeck $baccaratTerraceDeck
         */
        $baccaratTerraceDeck = $this->mapper->getModel()
            ->where('id',$terraceDeckId)
            ->first();

        if (!$baccaratTerraceDeck){
            return $baccaratTerraceDeck;
        }

        $baccaratLotteryLog = $baccaratTerraceDeck->baccaratLotteryLog()
            ->with([
            'baccaratSimulatedBettingLog:id,issue,betting_id,betting_value,betting_result,terrace_deck_id,status,created_at',
            'baccaratSimulatedBettingLog.baccaratBettingRuleLog:id,rule,baccarat_betting_log_id'
        ])->whereNotNull('transformationResult')
            ->get(['id', 'terrace_deck_id', 'issue', 'result', 'transformationResult', 'created_at']);

        $baccaratTerraceDeck->setRelation('baccaratLotteryLog',$baccaratLotteryLog);
        $baccaratTerraceDeck->append(['lotteryLogBankerCount','lotteryLogPlayerCount','lotteryLogTieCount','lotteryLogCalculateCoordinates']);

        return $baccaratTerraceDeck;
    }

    /**
     * @return Collection
     */
    #[Cacheable(prefix: "baccaratTerraceDeckGroupDate", ttl: 9000)]
    public function getBaccaratTerraceDeckGroupDate(): Collection
    {
        return $this->mapper->getModel()::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->whereDate('created_at', '<', date('Y-m-d'))
            ->get();
    }

    /**
     * @param string $date
     * @return Collection
     */
    #[Cacheable(prefix: "baccaratTerraceDeckByDate", ttl: 9000)]
    public function getBaccaratTerraceDeckByDate(string $date): Collection
    {
        return $this->mapper->getModel()::whereDate('created_at', $date)
            ->get();
    }
}