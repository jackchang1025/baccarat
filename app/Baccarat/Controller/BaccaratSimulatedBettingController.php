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

namespace App\Baccarat\Controller;

use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratStrategyBettingLog;
use App\Baccarat\Service\BaccaratSimulatedBettingService;
use App\Baccarat\Request\BaccaratSimulatedBettingRequest;
use App\Baccarat\Service\BettingAmountStrategy\BetStrategyInterface;
use App\Baccarat\Service\BettingAmountStrategy\FlatNote;
use App\Baccarat\Service\BettingAmountStrategy\LayeredStrategy;
use App\Baccarat\Service\BettingAmountStrategy\MartingaleStrategy;
use App\Baccarat\Service\SimulationBettingAmount\Baccarat;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Annotation\Auth;
use Mine\Annotation\RemoteState;
use Mine\Annotation\OperationLog;
use Mine\Annotation\Permission;
use Mine\MineController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Mine\Middlewares\CheckModuleMiddleware;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Di\Container;
use App\Baccarat\Service\BettingAmountStrategy\FixedRatioStrategy;
use App\Baccarat\Service\BettingAmountStrategy\OneThreeTwoSixStrategy;
/**
 * 投注控制器
 * Class BaccaratSimulatedBettingController
 */
#[Controller(prefix: "baccarat/simulatedBetting"), Auth]
#[Middleware(middleware: CheckModuleMiddleware::class)]
class BaccaratSimulatedBettingController extends MineController
{
    /**
     * 业务处理服务
     * BaccaratSimulatedBettingService
     */
    #[Inject]
    protected BaccaratSimulatedBettingService $service;

    
    /**
     * 列表
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("index"), Permission("baccarat:simulatedBetting, baccarat:simulatedBetting:index")]
    public function index(): ResponseInterface
    {
        return $this->success($this->service->getPageList($this->request->all()));
    }

    /**
     * 新增
     * @param BaccaratSimulatedBettingRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("save"), Permission("baccarat:simulatedBetting:save"), OperationLog]
    public function save(BaccaratSimulatedBettingRequest $request): ResponseInterface
    {
        return $this->success(['id' => $this->service->save($request->all())]);
    }

    /**
     * 更新
     * @param int $id
     * @param BaccaratSimulatedBettingRequest $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("update/{id}"), Permission("baccarat:simulatedBetting:update"), OperationLog]
    public function update(int $id, BaccaratSimulatedBettingRequest $request): ResponseInterface
    {
        return $this->service->update($id, $request->all()) ? $this->success() : $this->error();
    }

    /**
     * 读取数据
     * @param int $id
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("read/{id}"), Permission("baccarat:simulatedBetting:read")]
    public function read(int $id): ResponseInterface
    {
        return $this->success($this->service->read($id));
    }

    /**
     * 单个或批量删除数据到回收站
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[DeleteMapping("delete"), Permission("baccarat:simulatedBetting:delete"), OperationLog]
    public function delete(): ResponseInterface
    {
        return $this->service->delete((array) $this->request->input('ids', [])) ? $this->success() : $this->error();
    }

    /**
     * 更改数据状态
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PutMapping("changeStatus"), Permission("baccarat:simulatedBetting:update"), OperationLog]
    public function changeStatus(): ResponseInterface
    {
        return $this->service->changeStatus(
            (int) $this->request->input('setting_generate_tables.id'),
            (string) $this->request->input('statusValue'),
            (string) $this->request->input('statusName', 'status')
        ) ? $this->success() : $this->error();
    }

    /**
     * 数据导入
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("import"), Permission("baccarat:simulatedBetting:import")]
    public function import(): ResponseInterface
    {
        return $this->service->import(\App\Baccarat\Dto\BaccaratSimulatedBettingDto::class) ? $this->success() : $this->error();
    }

    /**
     * 下载导入模板
     * @return ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("downloadTemplate")]
    public function downloadTemplate(): ResponseInterface
    {
        return (new \Mine\MineCollection)->export(\App\Baccarat\Dto\BaccaratSimulatedBettingDto::class, '模板下载', []);
    }

    /**
     * 数据导出
     * @return ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[PostMapping("export"), Permission("baccarat:simulatedBetting:export"), OperationLog]
    public function export(): ResponseInterface
    {
        return $this->service->export($this->request->all(), \App\Baccarat\Dto\BaccaratSimulatedBettingDto::class, '导出数据列表');
    }


    /**
     * 远程万能通用列表接口
     * @return ResponseInterface
     */
    #[PostMapping("remote"), RemoteState(true)]
    public function remote(): ResponseInterface
    {
        return $this->success($this->service->getRemoteList($this->request->all()));
    }

    /**
     * 获取策略投注日志
     * @param int $id
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[GetMapping("strategyLogs/{id}"), Permission("baccarat:simulatedBetting:read")]
    public function getStrategyLogs(int $id): ResponseInterface
    {
        try {
            $logs = BaccaratStrategyBettingLog::query()
                ->where('simulated_betting_id', $id)
                ->orderBy('issue')
                ->get()
                ->groupBy('strategy_type');

            $data = [
                'issues' => [],
                'flatNote' => [],
                'layered' => [],
                'martingale' => [],
                'sequences' => []
            ];

            // 获取所有期号
            $issues = $logs->flatten()->pluck('issue')->unique()->sort()->values();
            $data['issues'] = $issues->toArray();

            // 整理每个策略的数据
            foreach ($logs as $strategyType => $strategyLogs) {
                $key = lcfirst(str_replace('Strategy', '', $strategyType));
                
                // 计算最大连赢和最大连输
                $sequence = $strategyLogs->pluck('result')->join('');
                $maxWinStreak = $this->getMaxConsecutive($sequence, '1');
                $maxLoseStreak = $this->getMaxConsecutive($sequence, '0');
                
                $data[$key] = [
                    'balances' => $strategyLogs->pluck('balance')->toArray(),
                    'betAmounts' => $strategyLogs->pluck('bet_amount')->toArray(),
                    'maxWinStreak' => $maxWinStreak,
                    'maxLoseStreak' => $maxLoseStreak,
                ];
            }

            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取最大连续次数
     * @param string $sequence
     * @param string $char
     * @return int
     */
    protected function getMaxConsecutive(string $sequence, string $char): int
    {
        $max = 0;
        $current = 0;
        
        for ($i = 0, $iMax = strlen($sequence); $i < $iMax; $i++) {
            if ($sequence[$i] === $char) {
                $current++;
                $max = max($max, $current);
            } else {
                $current = 0;
            }
        }
        
        return $max;
    }

    /**
     * 重新生成策略统计数据
     * @return ResponseInterface
     */
    #[PostMapping("regenerateStats"), Permission("baccarat:simulatedBetting:update")]
    public function regenerateStats(): ResponseInterface
    {
        try {
            $params = $this->request->all();
            $id = $params['id'] ?? 0;
            $initialAmount = $params['initialAmount'] ?? 0;
            $defaultBet = $params['defaultBet'] ?? 0;

            if (!$id || !$initialAmount || !$defaultBet) {
                return $this->error('参数错误');
            }

            $betting = BaccaratSimulatedBetting::query()->find($id);
            if (!$betting || empty($betting->strategy_types)) {
                return $this->error('投注单不存在或策略类型为空');
            }

            // 只获取第一个策略的开奖序列
            $sequence = BaccaratStrategyBettingLog::query()
                ->where('simulated_betting_id', $id)
                ->where('strategy_type', $betting->strategy_types[0])
                ->orderBy('issue')
                ->pluck('result')
                ->join('');

            if (empty($sequence)) {
                return $this->error('没有找到开奖序列');
            }

            // 使用新的参数重新生成策略统计
            $baccarat = make(Baccarat::class);
            foreach ($betting->strategy_types as $strategyType) {
                $strategy = match ($strategyType) {
                    'FlatNote' => new FlatNote($initialAmount, $defaultBet),
                    'Layered' => new LayeredStrategy($initialAmount, $defaultBet),
                    'Martingale' => new MartingaleStrategy($initialAmount, $defaultBet),
                    'FixedRatio' => new FixedRatioStrategy($initialAmount, $defaultBet),
                    'oneThreeTwoSix' => new OneThreeTwoSixStrategy($initialAmount, $defaultBet),
                    default => throw new \InvalidArgumentException("Unknown strategy type: {$strategyType}")
                };
                $baccarat->addStrategy($strategy);
            }

            // 重新计算结果并返回
            $results = $baccarat->play($sequence);
            
            // 整理返回数据格式
            $data = [
                'issues' => array_keys($results[array_key_first($results)] ?? []),
                'flatNote' => [],
                'layered' => [],
                'martingale' => []
            ];

            foreach ($results as $strategyType => $logs) {
                $key = lcfirst(str_replace('Strategy', '', $strategyType));
                
                // 提取余额和投注金额序列
                $balances = array_column($logs, 'total_amount');
                $betAmounts = array_column($logs, 'bet_amount');
                
                // 计算最大连赢和最大连输
                $sequence = implode('', array_column($logs, 'sequence'));
                $maxWinStreak = $this->getMaxConsecutive($sequence, '1');
                $maxLoseStreak = $this->getMaxConsecutive($sequence, '0');
                
                $data[$key] = [
                    'balances' => $balances,
                    'betAmounts' => $betAmounts,
                    'maxWinStreak' => $maxWinStreak,
                    'maxLoseStreak' => $maxLoseStreak,
                ];
            }

            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}