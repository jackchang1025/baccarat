<?php

declare(strict_types=1);

namespace App\Baccarat\Service;

use App\Baccarat\Mapper\DeckStatisticsMapper;
use App\Baccarat\Model\BaccaratDeckStatistics;
use Hyperf\Database\Model\Builder;
use Mine\Abstracts\AbstractService;
use Mine\Annotation\Transaction;

/**
 * 牌靴统计服务类
 */
class DeckStatisticsService extends AbstractService
{
    public function __construct(DeckStatisticsMapper $mapper)
    {
        $this->mapper = $mapper;
    }
    /**
     * 获取总体统计数据
     * @return array
     */
    public function getOverallStats(): array
    {
        return $this->mapper->getOverallStats();
    }

    /**
     * 获取可信度统计数据
     * @return array
     */
    public function getCredibilityStats(): array
    {
        return $this->mapper->getCredibilityStats();
    }

    /**
     * 获取指定房间的统计数据
     * @param int $terraceId
     * @return array
     */
    public function getTerraceStats(int $terraceId): array
    {
        // 获取房间的基础统计数据
        $baseStats = $this->mapper->getTerraceBaseStats($terraceId);
        
        // 获取房间的可信度统计数据
        $credibilityStats = $this->mapper->getTerraceCredibilityStats($terraceId);
        
        // 获取房间的每日统计趋势
        $dailyTrends = $this->mapper->getTerraceDailyTrends($terraceId);
        
        return [
            'baseStats' => $baseStats,
            'credibilityStats' => $credibilityStats,
            'dailyTrends' => $dailyTrends
        ];
    }

    /**
     * 获取日期范围内的统计数据
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDateRangeStats(string $startDate, string $endDate): array
    {
        // 获取日期范围内的基础统计
        $baseStats = $this->mapper->getDateRangeBaseStats($startDate, $endDate);
        
        // 获取日期范围内的可信度统计
        $credibilityStats = $this->mapper->getDateRangeCredibilityStats($startDate, $endDate);
        
        // 获取日期范围内的趋势数据
        $trends = $this->mapper->getDateRangeTrends($startDate, $endDate);
        
        return [
            'baseStats' => $baseStats,
            'credibilityStats' => $credibilityStats,
            'trends' => $trends
        ];
    }

    /**
     * 获取导出数据
     * @param array $params
     * @return array
     */
    public function getExportData(array $params): array
    {
        return $this->mapper->getExportData($params);
    }

    /**
     * 更新统计数据
     * @param int $deckId
     * @param array $stats
     * @return bool
     */
    #[Transaction]
    public function updateStats(int $deckId, array $stats): bool
    {
        try {
            return $this->mapper->updateStats($deckId, $stats);
        } catch (\Throwable $e) {
            $this->logger()->error(
                sprintf('更新牌靴统计数据失败: %s', $e->getMessage()),
                ['exception' => $e]
            );
            return false;
        }
    }

    /**
     * 批量更新统计数据
     * @param array $batchStats
     * @return bool
     */
    #[Transaction]
    public function batchUpdateStats(array $batchStats): bool
    {
        try {
            return $this->mapper->batchUpdateStats($batchStats);
        } catch (\Throwable $e) {
            $this->logger()->error(
                sprintf('批量更新牌靴统计数据失败: %s', $e->getMessage()),
                ['exception' => $e]
            );
            return false;
        }
    }
} 