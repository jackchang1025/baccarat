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

use App\Baccarat\Mapper\BaccaratSimulatedBettingLogMapper;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\Memory\Memory;
use App\Baccarat\Service\Rule\RuleInterface;
use App\Baccarat\Service\SimulationBettingAmount\Baccarat;
use App\Baccarat\Service\SimulationBettingAmount\BaccaratFactory;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractService;

class BaccaratSimulatedBettingLogService extends AbstractService
{
    /**
     * @var BaccaratSimulatedBettingLogMapper
     */
    public $mapper;

    protected Baccarat $baccarat;

    public function __construct(BaccaratSimulatedBettingLogMapper $mapper,protected BaccaratFactory $baccaratFactory,protected Memory $memory)
    {
        $this->mapper = $mapper;
    }

    /**
     * @param array $params
     * @return array
     */
    public function chart(array $params):array
    {
        $baccaratSimulatedBettingLogList = $this->mapper->getModel()
            ->where('betting_id',$params['betting_id'])
            ->whereDate('created_at',$params['date'])
            ->get();

        $totalBetAmount = (float) $params['betTotalAmount'];
        $defaultBetAmount = (float) $params['betDefaultAmount'];

        $betLogList = [];

        if ($baccaratSimulatedBettingLogList->isNotEmpty()){

            $this->memory->initMemoryUsage();
            $s = microtime(true);


            $betLogList = $this->baccaratFactory->create($totalBetAmount,$defaultBetAmount)->play($baccaratSimulatedBettingLogList);

            var_dump("{$params['betting_id']} chart success Use s:" . number_format(microtime(true) - $s, 8)." memory: {$this->memory->format($this->memory->calculateCurrentlyUsedMemory())}");
        }

        return [
            'betLogList'=>$betLogList,
            'sequence'=>$baccaratSimulatedBettingLogList->pluck('betting_result')->implode(''),
        ];
    }
}