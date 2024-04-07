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
use App\Baccarat\Model\BaccaratRule;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractMapper;

/**
 * 规则Mapper类
 */
class BaccaratLotteryLogMapper extends AbstractMapper
{
    /**
     * @var BaccaratLotteryLog
     */
    public $model;

    public function assignModel()
    {
        $this->model = BaccaratLotteryLog::class;
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
        return $query;
    }

    public function getLotteryLog(string $issue): BaccaratLotteryLog|Builder|null
    {
        return $this->getModel()->where('issue',$issue)->first();
    }

    public function getTransformationResult(int $terraceDeckId):string
    {
        $commentsContents = $this->getModel()
        ->where('terrace_deck_id',$terraceDeckId)
        ->get()
        ->pluck('transformationResult')
        ->filter(fn($item) => !empty($item))
        ->map(fn($item) => str_replace('T', '', $item))
        ->toArray();

    // 使用 PHP 的 implode 函数将数组中的评论内容连接成一个字符串
    return implode('', $commentsContents);
    }
}