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

use App\Baccarat\Mapper\BaccaratBettingRuleLogMapper;
use Mine\Abstracts\AbstractService;

/**
 * 投注日志规则表服务类
 */
class BaccaratBettingRuleLogService extends AbstractService
{
    /**
     * @var BaccaratBettingRuleLogMapper
     */
    public $mapper;

    public function __construct(BaccaratBettingRuleLogMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function group(array $params): array
    {
        return $this->mapper->getModel()->groupBy('rule')
            ->selectRaw("rule, MAX(id) as id,MAX(title) as label, MAX(id) as value")
            ->get()
            ->toArray();
    }
}