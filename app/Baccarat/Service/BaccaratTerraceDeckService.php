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
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\Coordinates\CalculateCoordinates;
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

    /**
     * 更新开奖序列到牌靴中
     * @param int $terraceDeckId
     * @param string $transformationResult
     * @return MineModel|null
     */
    public function updateLotterySequence(int $terraceDeckId,string $transformationResult): ?MineModel
    {

        if($transformationResult == 'T'){return null;}

        $baccaratTerraceDeck = $this->mapper->read($terraceDeckId);
        if($baccaratTerraceDeck){

            $baccaratTerraceDeck->lottery_sequence.= $transformationResult;
            $baccaratTerraceDeck->save();
        }
        
        return $baccaratTerraceDeck;
    }

    public function getBaccaratTerraceDeckWithToday(int $terraceId,string $deckNumber): Model|\Hyperf\Database\Query\Builder|Builder|null
    {
        return $this->mapper->getBaccaratTerraceDeckWithToday($terraceId,$deckNumber);
    }

    public function getBaccaratTerraceDeckWithTodayOrCreate(int $terraceId,string $deckNumber):BaccaratTerraceDeck
    {
        return $this->mapper->getBaccaratTerraceDeckWithTodayOrCreate($terraceId,$deckNumber);
    }

    /**
     * @param int $terraceId
     * @return Builder|Model
     */
    public function getLastBaccaratTerraceDeckOrCreate(int $terraceId): BaccaratTerraceDeck|Builder
    {
        return $this->mapper->getLastBaccaratTerraceDeckOrCreate($terraceId);
    }

    public function getLastBaccaratTerraceDeck(int $terraceId): BaccaratTerraceDeck|Builder|null
    {
        return $this->mapper->getLastBaccaratTerraceDeck($terraceId);
    }

    public function getBaccaratTerraceDeckListWithBettingLog(int $terraceDeckId): Builder|BaccaratTerraceDeck
    {
        $data = $this->mapper->getModel()
            ->withCount([
                'baccaratLotteryLog as bankerCount' => function ($query) {
                $query->where('transformationResult', LotteryResult::BANKER);
                },
                'baccaratLotteryLog as playerCount' => function ($query) {
                $query->where('transformationResult', LotteryResult::PLAYER);
                },
                'baccaratLotteryLog as tieCount' => function ($query) {
                $query->where('transformationResult', LotteryResult::TIE);
            }])->with([
            'baccaratLotteryLog.baccaratSimulatedBettingLog:id,issue,betting_id,betting_value,betting_result,terrace_deck_id,status,created_at',
            'baccaratLotteryLog.baccaratSimulatedBettingLog.baccaratBettingRuleLog:id,rule,baccarat_betting_log_id',
            'baccaratLotteryLog' => function ($query) {
            $query->whereNotNull('transformationResult')
                ->select('id', 'terrace_deck_id', 'issue', 'result', 'transformationResult', 'created_at');
            },
        ])->where('id',$terraceDeckId)->first();

        if ($data && $data->baccaratLotteryLog->isNotEmpty()){
            $baccaratLotteryLog = new CalculateCoordinates();
            $data->baccarat_lottery_log = $baccaratLotteryLog->calculateCoordinatesWithCollection($data->baccaratLotteryLog);
        }
        return $data;
    }
}