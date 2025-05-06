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

use App\Baccarat\Mapper\BaccaratLotteryLogMapper;
use App\Baccarat\Mapper\BaccaratRuleMapper;
use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractService;
use Mine\MineModel;


class BaccaratLotteryLogService extends AbstractService
{
    /**
     * @var BaccaratLotteryLogMapper
     */
    public $mapper;

    public function __construct(BaccaratLotteryLogMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getLotteryLogOrCreate(int $baccaratTerraceDeckId,LotteryResult $lotteryResult):?BaccaratLotteryLog
    {
        //根据期号获取开奖日志
        $baccaratLotteryLog = $this->getLotteryLog($lotteryResult->issue);
        if (!$baccaratLotteryLog){
            //创建牌靴开奖记录
            return $this->createLotteryLog([
                'terrace_deck_id' => $baccaratTerraceDeckId,
                'issue' => $lotteryResult->issue
            ]);
        }
        return null;
    }

    public function getLotteryLog(string|int $issue): BaccaratLotteryLog|null
    {
        return $this->mapper->getLotteryLog($issue);
    }


    public function createLotteryLog(array $data): BaccaratLotteryLog|MineModel
    {
        return $this->mapper->getModel()->create($data);
    }

    public function getTransformationResult(int $terraceDeckId):string
    {
        return $this->mapper->getTransformationResult($terraceDeckId);
    }

    public function updateLotteryLog(LotteryResult $lotteryResult):?BaccaratLotteryLog
    {
        $baccaratLotteryLog = $this->getLotteryLog($lotteryResult->issue);

            if($baccaratLotteryLog && !$baccaratLotteryLog->transformationResult){

                $baccaratLotteryLog->update([
                    'result' => $lotteryResult->result,
                    'transformationResult' => $lotteryResult->getTransformationResult(),
                    'RawData' => $lotteryResult->data,
                ]);
                return $baccaratLotteryLog;
            }
            return null;
    }
}