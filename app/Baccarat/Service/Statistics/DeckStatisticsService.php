<?php

declare(strict_types=1);

namespace App\Baccarat\Service\Statistics;

use App\Baccarat\Model\BaccaratLotteryLog;
use Hyperf\Database\Model\Collection;

class DeckStatisticsService
{
    /**
     * 获取统计数据
     * @param Collection $lotteryLogs
     * @return array
     */
    public function getStatistics(Collection $lotteryLogs): array
    {
        $stats = $this->initializeStats();
        $credibilityGroups = $this->getCredibilityGroups($lotteryLogs);
        
        $this->initializeCredibilityStats($stats, $credibilityGroups);
        $this->calculateStatistics($stats, $lotteryLogs);
        $this->calculateWinRates($stats);
        
        return $stats;
    }
    
    /**
     * 初始化统计数据结构
     * @return array
     */
    private function initializeStats(): array
    {
        return [
            'total_bets' => 0,
            'total_wins' => 0,
            'total_losses' => 0,
            'total_ties' => 0,
            'total_win_rate' => 0,
            'betting_string' => '',
            'credibility_stats' => []
        ];
    }
    
    /**
     * 获取可信度分组
     * @param Collection $lotteryLogs
     * @return array
     */
    private function getCredibilityGroups(Collection $lotteryLogs): array
    {
        $groups = [];
        foreach ($lotteryLogs as $log) {
            /** @var BaccaratLotteryLog $log */
            if ($log?->bettingLog?->credibility) {
                $groups[] = $log->bettingLog->credibility;
            }
        }
        
        return array_values(array_unique($groups));
    }
    
    /**
     * 初始化可信度统计数据
     * @param array $stats
     * @param array $credibilityGroups
     */
    private function initializeCredibilityStats(array &$stats, array $credibilityGroups): void
    {
        foreach ($credibilityGroups as $credibility) {
            $stats['credibility_stats'][$credibility] = [
                'total' => 0,
                'wins' => 0,
                'losses' => 0,
                'ties' => 0,
                'win_rate' => 0
            ];
        }
    }
    
    /**
     * 计算统计数据
     * @param array $stats
     * @param Collection $lotteryLogs
     */
    private function calculateStatistics(array &$stats, Collection $lotteryLogs): void
    {
        foreach ($lotteryLogs as $log) {
            /** @var BaccaratLotteryLog $log */
            if (!$log->bettingLog || $log?->bettingLog?->betting_result === null) {
                continue;
            }

            $stats['betting_string'] .= $log->bettingLog->betting_result;
            $betting = $log->bettingLog;
            $credibility = $betting->credibility;
            
            $this->updateTotalStats($stats, $betting->betting_result);
            
            if ($credibility && isset($stats['credibility_stats'][$credibility])) {
                $this->updateCredibilityStats($stats['credibility_stats'][$credibility], $betting->betting_result);
            }
        }
    }
    
    /**
     * 更新总体统计
     * @param array $stats
     * @param string $result
     */
    private function updateTotalStats(array &$stats, string $result): void
    {
        $stats['total_bets']++;
        
        switch ($result) {
            case '1':
                $stats['total_wins']++;
                break;
            case '0':
                $stats['total_losses']++;
                break;
            case '2':
                $stats['total_ties']++;
                break;
        }
    }
    
    /**
     * 更新可信度统计
     * @param array $credStats
     * @param string $result
     */
    private function updateCredibilityStats(array &$credStats, string $result): void
    {
        $credStats['total']++;
        
        switch ($result) {
            case '1':
                $credStats['wins']++;
                break;
            case '0':
                $credStats['losses']++;
                break;
            case '2':
                $credStats['ties']++;
                break;
        }
    }
    
    /**
     * 计算胜率
     * @param array $stats
     */
    private function calculateWinRates(array &$stats): void
    {
        // 计算总体胜率
        $effectiveTotal = $stats['total_bets'] - $stats['total_ties'];
        $stats['total_win_rate'] = $this->calculateWinRate(
            $stats['total_wins'],
            $effectiveTotal
        );
        
        // 计算各可信度胜率
        foreach ($stats['credibility_stats'] as &$credStats) {
            $effectiveTotal = $credStats['total'] - $credStats['ties'];
            $credStats['win_rate'] = $this->calculateWinRate(
                $credStats['wins'],
                $effectiveTotal
            );
        }
    }
    
    /**
     * 计算单个胜率
     * @param int $wins
     * @param int $total
     * @return float
     */
    private function calculateWinRate(int $wins, int $total): float
    {
        return $total > 0 ? round(($wins / $total) * 100, 2) : 0;
    }
} 