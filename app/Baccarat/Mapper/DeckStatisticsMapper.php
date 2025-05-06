<?php

declare(strict_types=1);

namespace App\Baccarat\Mapper;

use App\Baccarat\Model\BaccaratDeckStatistics;
use Hyperf\Database\Model\Builder;
use Mine\Abstracts\AbstractMapper;

/**
 * 牌靴统计Mapper类
 */
class DeckStatisticsMapper extends AbstractMapper
{
    /**
     * @var BaccaratDeckStatistics
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratDeckStatistics::class;
    }

    /**
     * 获取总体统计数据
     * @return array
     */
    public function getOverallStats(): array
    {
        // 聚合查询总体统计数据
        $overall = $this->model::query()
            ->selectRaw('
                SUM(total_bets) as total_bets,
                SUM(total_wins) as total_wins,
                SUM(total_losses) as total_losses,
                SUM(total_ties) as total_ties,
                ROUND(AVG(total_win_rate), 2) as avg_win_rate
            ')
            ->first()
            ->toArray();

        // 计算各项占比
        $total = $overall['total_bets'] ?: 1;
        $pieData = [
            ['name' => '胜', 'value' => $overall['total_wins'], 'percentage' => round($overall['total_wins'] / $total * 100, 2)],
            ['name' => '负', 'value' => $overall['total_losses'], 'percentage' => round($overall['total_losses'] / $total * 100, 2)],
            ['name' => '和', 'value' => $overall['total_ties'], 'percentage' => round($overall['total_ties'] / $total * 100, 2)]
        ];

        return [
            'summary' => $overall,
            'pieData' => $pieData
        ];
    }

    /**
     * 获取可信度统计数据
     * @return array
     */
    public function getCredibilityStats(): array
    {
        // 获取所有统计记录
        $records = $this->model::query()->get();
        
        // 初始化可信度统计数据
        $credibilityStats = [];
        
        // 合并所有记录的可信度统计
        foreach ($records as $record) {
            $stats = $record->credibility_stats;
            if (!$stats) continue;
            
            foreach ($stats as $level => $data) {
                if (!isset($credibilityStats[$level])) {
                    $credibilityStats[$level] = [
                        'total' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'ties' => 0
                    ];
                }
                
                $credibilityStats[$level]['total'] += $data['total'];
                $credibilityStats[$level]['wins'] += $data['wins'];
                $credibilityStats[$level]['losses'] += $data['losses'];
                $credibilityStats[$level]['ties'] += $data['ties'];
            }
        }
        
        // 计算每个可信度的胜率
        foreach ($credibilityStats as &$stats) {
            $effectiveTotal = $stats['total'] - $stats['ties'];
            $stats['win_rate'] = $effectiveTotal > 0 
                ? round(($stats['wins'] / $effectiveTotal) * 100, 2)
                : 0;
        }
        
        // 格式化为图表数据
        $chartData = [
            'xAxis' => array_keys($credibilityStats),
            'series' => [
                [
                    'name' => '总投注',
                    'type' => 'bar',
                    'data' => array_column($credibilityStats, 'total')
                ],
                [
                    'name' => '胜',
                    'type' => 'bar',
                    'data' => array_column($credibilityStats, 'wins')
                ],
                [
                    'name' => '负',
                    'type' => 'bar',
                    'data' => array_column($credibilityStats, 'losses')
                ],
                [
                    'name' => '和',
                    'type' => 'bar',
                    'data' => array_column($credibilityStats, 'ties')
                ],
                [
                    'name' => '胜率',
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'data' => array_column($credibilityStats, 'win_rate')
                ]
            ]
        ];

        return [
            'stats' => $credibilityStats,
            'chartData' => $chartData
        ];
    }

    /**
     * 获取房间基础统计数据
     * @param int $terraceId
     * @return array
     */
    public function getTerraceBaseStats(int $terraceId): array
    {
        return $this->model::query()
            ->where('terrace_id', $terraceId)
            ->selectRaw('
                SUM(total_bets) as total_bets,
                SUM(total_wins) as total_wins,
                SUM(total_losses) as total_losses,
                SUM(total_ties) as total_ties,
                ROUND(AVG(total_win_rate), 2) as avg_win_rate
            ')
            ->first()
            ->toArray();
    }

    /**
     * 获取房间可信度统计数据
     * @param int $terraceId
     * @return array
     */
    public function getTerraceCredibilityStats(int $terraceId): array
    {
        $records = $this->model::query()
            ->where('terrace_id', $terraceId)
            ->get();
            
        return $this->aggregateCredibilityStats($records);
    }

    /**
     * 获取房间每日统计趋势
     * @param int $terraceId
     * @return array
     */
    public function getTerraceDailyTrends(int $terraceId): array
    {
        return $this->model::query()
            ->where('terrace_id', $terraceId)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as deck_count,
                SUM(total_bets) as total_bets,
                SUM(total_wins) as total_wins,
                ROUND(AVG(total_win_rate), 2) as avg_win_rate
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * 获取日期范围内的基础统计
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDateRangeBaseStats(string $startDate, string $endDate): array
    {
        return $this->model::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                SUM(total_bets) as total_bets,
                SUM(total_wins) as total_wins,
                SUM(total_losses) as total_losses,
                SUM(total_ties) as total_ties,
                ROUND(AVG(total_win_rate), 2) as avg_win_rate
            ')
            ->first()
            ->toArray();
    }

    /**
     * 获取日期范围内的可信度统计
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDateRangeCredibilityStats(string $startDate, string $endDate): array
    {
        $records = $this->model::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        return $this->aggregateCredibilityStats($records);
    }

    /**
     * 获取日期范围内的趋势数据
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getDateRangeTrends(string $startDate, string $endDate): array
    {
        return $this->model::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as deck_count,
                SUM(total_bets) as total_bets,
                SUM(total_wins) as total_wins,
                ROUND(AVG(total_win_rate), 2) as avg_win_rate
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * 聚合可信度统计数据
     * @param \Hyperf\Database\Model\Collection $records
     * @return array
     */
    private function aggregateCredibilityStats($records): array
    {
        $credibilityStats = [];
        
        foreach ($records as $record) {
            $stats = $record->credibility_stats;
            if (!$stats) continue;
            
            foreach ($stats as $level => $data) {
                if (!isset($credibilityStats[$level])) {
                    $credibilityStats[$level] = [
                        'total' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'ties' => 0
                    ];
                }
                
                $credibilityStats[$level]['total'] += $data['total'];
                $credibilityStats[$level]['wins'] += $data['wins'];
                $credibilityStats[$level]['losses'] += $data['losses'];
                $credibilityStats[$level]['ties'] += $data['ties'];
            }
        }
        
        // 计算胜率
        foreach ($credibilityStats as &$stats) {
            $effectiveTotal = $stats['total'] - $stats['ties'];
            $stats['win_rate'] = $effectiveTotal > 0 
                ? round(($stats['wins'] / $effectiveTotal) * 100, 2)
                : 0;
        }
        
        return $credibilityStats;
    }
} 