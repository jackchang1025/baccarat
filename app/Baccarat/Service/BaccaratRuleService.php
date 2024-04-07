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

use App\Baccarat\Mapper\BaccaratRuleMapper;
use Mine\Abstracts\AbstractService;

/**
 * 规则服务类
 */
class BaccaratRuleService extends AbstractService
{
    /**
     * @var BaccaratRuleMapper
     */
    public $mapper;

    public function __construct(BaccaratRuleMapper $mapper)
    {
        $this->mapper = $mapper;
    }
}