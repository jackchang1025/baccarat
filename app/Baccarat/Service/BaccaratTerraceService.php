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
use App\Baccarat\Model\BaccaratTerraceDeck;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\HasMany;
use Mine\Abstracts\AbstractService;
use Mine\MineModel;
use function Hyperf\Support\with;

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
}