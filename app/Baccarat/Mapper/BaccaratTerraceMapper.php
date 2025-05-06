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
use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\Coordinates\CalculateCoordinates;
use App\Baccarat\Service\LotteryResult;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\HasMany;
use Mine\Abstracts\AbstractMapper;
use Hyperf\DbConnection\Db;
use App\Baccarat\Service\Statistics\DeckStatisticsService;

/**
 * 台，桌Mapper类
 */
class BaccaratTerraceMapper extends AbstractMapper
{
    /**
     * @var BaccaratTerrace
     */
    public $model;

    public function __construct(
        private readonly DeckStatisticsService $statisticsService
    ) {
        parent::__construct();
    }

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
        return $this->getModel()->firstOrCreate(['code' => $terraceCode], ['code' => $terraceCode, 'title' => $terraceCode]);
    }

    public function getBaccaratTerrace(string $code): BaccaratTerrace|Builder|null
    {
        return $this->getModel()->where('code', $code)->first();
    }

    public function getList(?array $params, bool $isScope = true): array
    {

//        $query = BaccaratSimulatedBettingLog::with([
//            'baccaratTerraceDeck.baccaratTerrace',
//        ]);
//
//        if (isset($params['betting_id']) && filled($params['betting_id'])) {
//            $query->where('betting_id', $params['betting_id']);
//        }
//
//        if (isset($params['terrace_deck_created_at']) && filled($params['terrace_deck_created_at']) && is_array($params['terrace_deck_created_at']) && count($params['terrace_deck_created_at']) == 2) {
//            $query->whereBetween(
//                'created_at',
//                [$params['terrace_deck_created_at'][0], $params['terrace_deck_created_at'][1]]
//            );
//        }
//
//        $BaccaratSimulatedBettingLogList = $query->select('id', 'betting_id', 'terrace_deck_id', 'created_at')->get();
//
//        $baccaratTerraceIds = $BaccaratSimulatedBettingLogList->pluck('baccaratTerraceDeck.baccaratTerrace.id')->unique()->toArray();
//        $baccaratTerraceDeckIds = $BaccaratSimulatedBettingLogList->pluck('baccaratTerraceDeck.id')->unique()->toArray();
//
//        if (!empty($baccaratTerraceIds) && !empty($baccaratTerraceDeckIds)) {
//
//            $s = microtime(true);
//
//            $baccaratTerraceList = BaccaratTerrace::with([
//                'children' => function (HasMany $query) use ($baccaratTerraceDeckIds) {
//                    $query->whereIn('id', $baccaratTerraceDeckIds)
//                        ->selectRaw('id,terrace_id,terrace_id as parent_id,deck_number as title,id as `key`')
//                        ->withCount([
//                            'baccaratLotteryLog as bankerCount' => function ($query) {
//                                $query->where('transformationResult', LotteryResult::BANKER);
//                            },
//                            'baccaratLotteryLog as playerCount' => function ($query) {
//                                $query->where('transformationResult', LotteryResult::PLAYER);
//                            },
//                            'baccaratLotteryLog as tieCount' => function ($query) {
//                                $query->where('transformationResult', LotteryResult::TIE);
//                            }
//                        ])->with([
////                            'baccaratSimulatedBettingLog:id,betting_id,betting_value,betting_result,terrace_deck_id,status,created_at',
////                            'baccaratSimulatedBettingLog.baccaratBettingRuleLog:id,rule,baccarat_betting_log_id',
//                            'baccaratLotteryLog' => function ($query) {
//                                $query->whereNotNull('transformationResult')
//                                    ->select('id', 'terrace_deck_id', 'issue', 'result', 'transformationResult', 'created_at');
//                            },
//                            'baccaratLotteryLog.baccaratSimulatedBettingLog:id,issue,betting_value,betting_result',
//                        ]);
//                }
//            ])
//                ->select('id', 'title', 'code','id as key')
//                ->findMany($baccaratTerraceIds)
//                ->each(function (BaccaratTerrace $baccaratTerrace){
//                    $baccaratTerrace->children->append(['baccaratLotterySequence'])->each(function (BaccaratTerraceDeck $baccaratTerraceDeck) {
//                        $baccaratTerraceDeck->baccaratLotteryLog = (new CalculateCoordinates())->calculateCoordinatesWithCollection($baccaratTerraceDeck->baccaratLotteryLog);
//                    });
//                });
//
//            var_dump(number_format(microtime(true) - $s, 8));
//            return $baccaratTerraceList->toArray();
//        }
//
//        return [];


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

                return $query->selectRaw('id,terrace_id,terrace_id as parent_id,deck_number as title,id as `key`');
            }])
            ->get(['id', 'title', 'code as key'])
            ->append(['parent_id'])
            ->filter(fn(BaccaratTerrace $baccaratTerrace) => $baccaratTerrace->children->isNotEmpty())
            ->each(fn(BaccaratTerrace $baccaratTerrace)=> $baccaratTerrace->children->append(['baccaratBettingSequence']))
            ->toArray();

    }

    /**
     * 获取牌靴日期列表
     * @param array $params
     * @return array
     */
    public function getDeckDates(array $params): array
    {
        $query = BaccaratTerraceDeck::query()
            ->where('terrace_id', $params['terrace_id'])
            ->select(Db::raw('DATE(created_at) as date'))
            ->distinct()
            ->orderBy('date', 'desc');

        return $query->pluck('date')->toArray();
    }

    /**
     * 获取牌靴列表
     * @param array $params
     * @return array
     */
    public function getDeckList(array $params): array
    {
        $query = BaccaratTerraceDeck::query()
            ->with(['baccaratLotteryLog' => function($query) {
                $query->orderBy('issue', 'asc')
                    ->with('bettingLog');
            }])
            ->where('terrace_id', $params['terrace_id'])
            ->whereDate('created_at', $params['date']);

        $decks = $query->orderBy('deck_number', 'asc')->get();
        
        // 计算每个牌靴的统计数据
        $decks->each(function (BaccaratTerraceDeck $deck) {
            // 计算坐标
            $deck->baccaratLotteryLog = (new CalculateCoordinates())
                ->calculateCoordinatesWithCollection($deck->baccaratLotteryLog);
            
            // 添加统计数据
            $deck->statistics = $this->statisticsService->getStatistics($deck->baccaratLotteryLog);
        });
        
        return $decks->toArray();
    }
}