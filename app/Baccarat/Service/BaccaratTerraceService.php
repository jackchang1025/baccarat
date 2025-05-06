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

use App\Baccarat\Mapper\BaccaratTerraceMapper;
use App\Baccarat\Model\BaccaratTerrace;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Mine\Abstracts\AbstractService;

/**
 * 台，桌服务类
 */
class BaccaratTerraceService extends AbstractService
{
    /**
     * @var BaccaratTerraceMapper
     */
    public $mapper;

    public function __construct(BaccaratTerraceMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getBaccaratTerraceOrCreateByCode(string $code): BaccaratTerrace|Model
    {
        return $this->mapper->getBaccaratTerraceOrCreateByCode($code);
    }

    public function getBaccaratTerrace(string $code): BaccaratTerrace|Builder|null
    {
        return $this->mapper->getBaccaratTerrace($code);
    }

    /**
     * 获取牌靴日期列表
     * @param array $params
     * @return array
     */
    public function getDeckDates(array $params): array
    {
        return $this->mapper->getDeckDates($params);
    }

    /**
     * 获取牌靴列表
     * @param array $params
     * @return array
     */
    public function getDeckList(array $params): array
    {
        return $this->mapper->getDeckList($params);
    }
}