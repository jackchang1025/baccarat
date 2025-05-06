<?php

declare(strict_types=1);

namespace App\Baccarat\Controller;

use App\Baccarat\Service\DeckStatisticsService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Mine\Annotation\Auth;
use Mine\Annotation\Permission;
use Mine\MineController;
use Psr\Http\Message\ResponseInterface;
use Mine\Middlewares\CheckModuleMiddleware;
use Hyperf\HttpServer\Annotation\Middleware;

/**
 * 牌靴统计控制器
 * Class DeckStatisticsController
 */
#[Controller(prefix: "baccarat/statistics"), Auth]
#[Middleware(middleware: CheckModuleMiddleware::class)]
class DeckStatisticsController extends MineController
{
    /**
     * 业务处理服务
     * DeckStatisticsService
     */
    #[Inject]
    protected DeckStatisticsService $service;

    /**
     * 获取总体统计数据
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("overall"), Permission("baccarat:statistics, baccarat:statistics:overall")]
    public function getOverallStats(): ResponseInterface
    {
        try {
            $data = $this->service->getOverallStats();
            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 获取可信度统计数据
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("credibility"), Permission("baccarat:statistics, baccarat:statistics:credibility")]
    public function getCredibilityStats(): ResponseInterface
    {
        try {
            $data = $this->service->getCredibilityStats();
            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取指定房间的统计数据
     * @param int $terraceId
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("terrace/{terraceId}"), Permission("baccarat:statistics:terrace")]
    public function getTerraceStats(int $terraceId): ResponseInterface
    {
        try {
            $data = $this->service->getTerraceStats($terraceId);
            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取指定日期范围的统计数据
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("date-range"), Permission("baccarat:statistics:date")]
    public function getDateRangeStats(): ResponseInterface
    {
        try {
            $startDate = $this->request->input('start_date');
            $endDate = $this->request->input('end_date');
            
            $data = $this->service->getDateRangeStats($startDate, $endDate);
            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 导出统计数据
     * @return ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[GetMapping("export"), Permission("baccarat:statistics:export")]
    public function export(): ResponseInterface
    {
        try {
            $data = $this->service->getExportData($this->request->all());
            return $this->service->export($data, 'BaccaratStatistics', '百家乐统计数据');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
} 